<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Report;
use App\Models\Activity;
use App\Models\Category;
use App\Models\ReportResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HandleReportsController extends Controller
{
    private function applyRoleVisibility($query, $manager)
    {
        if ($manager->is_top_management) {
            return $query->whereHas('category', function ($q) {
                $q->whereIn('classification', ['Major', 'Grave']);
            });
        }

        return $query->whereHas('user', function ($q) use ($manager) {
            $q->where('department_id', $manager->department_id);
        });
    }

    // Show reports from admin's department with filters
    public function index(Request $request)
    {
        $admin = Auth::user();
        $departmentId = $admin->department_id;
        $allowedStatuses = ['approved', 'rejected', 'under review', 'resolved'];
        $selectedStatus = strtolower((string) $request->get('status', ''));
        if (!in_array($selectedStatus, $allowedStatuses, true)) {
            $selectedStatus = '';
        }

        $query = $this->applyRoleVisibility(Report::with(['user', 'category'])
            ->where(function ($q) use ($allowedStatuses) {
                foreach ($allowedStatuses as $index => $status) {
                    if ($index === 0) {
                        $q->whereRaw('LOWER(status) = ?', [$status]);
                    } else {
                        $q->orWhereRaw('LOWER(status) = ?', [$status]);
                    }
                }
            }),
            $admin
        );

        // FILTER: Category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // FILTER: Status
        if ($selectedStatus !== '') {
            $query->whereRaw('LOWER(status) = ?', [$selectedStatus]);
        }

        // FILTER: Reporter name
        if ($request->filled('reporter')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->reporter . '%');
            });
        }

        // FILTER: Date range
        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('created_at', [
                $request->from . ' 00:00:00',
                $request->to . ' 23:59:59'
            ]);
        } elseif ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        } elseif ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        // FILTER: Assigned status (assigned vs unassigned)
        if ($request->filled('assignment')) {
            if ($request->assignment === 'assigned') {
                $query->whereNotNull('assigned_to');
            } elseif ($request->assignment === 'unassigned') {
                $query->whereNull('assigned_to');
            }
        }

        // SORTING
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        if ($sortBy === 'status') {
            $query->orderByRaw("FIELD(LOWER(status), 'approved', 'rejected', 'under review', 'resolved')");
        } elseif ($sortBy === 'priority') {
            // Assuming you have a priority field
            $query->orderBy('priority', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Get filtered reports
        $approvedReports = $query->get();

        // Get all categories for filter dropdown
        $categories = Category::all();

        // Get available statuses for filter
        $statuses = $allowedStatuses;

        // Count reports by status for quick overview
        $statusCounts = [
            'approved' => $this->applyRoleVisibility(Report::whereRaw('LOWER(status) = ?', ['approved']), $admin)->count(),
            'rejected' => $this->applyRoleVisibility(Report::whereRaw('LOWER(status) = ?', ['rejected']), $admin)->count(),
            'under_review' => $this->applyRoleVisibility(Report::whereRaw('LOWER(status) = ?', ['under review']), $admin)->count(),
            'resolved' => $this->applyRoleVisibility(Report::whereRaw('LOWER(status) = ?', ['resolved']), $admin)->count(),
        ];

        return view('admin.handlereports', compact(
            'approvedReports', 
            'categories', 
            'statuses',
            'statusCounts',
            'selectedStatus'
        ));
    }


    // Show specific report (restricted to department)
    public function show($id)
    {
        $admin = Auth::user();

        $report = $this->applyRoleVisibility(
            Report::with(['user', 'category', 'activities.performedBy', 'responses.admin']),
            $admin
        )->findOrFail($id);

        // Get report history/activities
        $activities = Activity::where('report_id', $id)
            ->with('performedBy')
            ->orderBy('created_at', 'desc')
            ->get();

        $responses = ReportResponse::where('report_id', $id)
            ->with('admin')
            ->orderBy('response_number', 'desc')
            ->get();

        return view('admin.handlereports_show', compact('report', 'activities', 'responses'));
    }


    // Update report handling info
    public function update(Request $request, $id)
    {
        $admin = Auth::user();

        $request->validate([
            'assigned_to' => 'nullable|string|max:255',
            'department'  => 'nullable|string|max:255',
            'target_date' => 'nullable|date|after_or_equal:today',
            'remarks'     => 'nullable|string|max:1000',
            'status'      => 'required|in:approved,under review,resolved,rejected',
        ], [
            'target_date.after_or_equal' => 'Target date must be today or a future date.',
            'status.required' => 'Please select a status.',
            'status.in' => 'Invalid status selected.',
        ]);

        $report = $this->applyRoleVisibility(Report::query(), $admin)->findOrFail($id);

        $oldStatus = $report->status;

        DB::transaction(function () use ($request, $report, $admin, $oldStatus) {
            $lockedReport = Report::whereKey($report->id)->lockForUpdate()->firstOrFail();

            $nextResponseNumber = ReportResponse::where('report_id', $lockedReport->id)
                ->max('response_number');
            $nextResponseNumber = ($nextResponseNumber ?? 0) + 1;

            ReportResponse::create([
                'report_id' => $lockedReport->id,
                'dsdo_id' => $admin->id,
                'response_number' => $nextResponseNumber,
                'assigned_to' => $request->assigned_to,
                'department' => $request->department,
                'target_date' => $request->target_date,
                'status' => $request->status,
                'remarks' => $request->remarks,
            ]);

            // Keep report row as the latest snapshot for listing/filtering.
            $lockedReport->update([
                'assigned_to' => $request->assigned_to,
                'department'  => $request->department,
                'target_date' => $request->target_date,
                'remarks'     => $request->remarks,
                'status'      => $request->status,
                'handled_by'  => $admin->id,
                'updated_at'  => now(),
            ]);

            Activity::create([
                'report_id'    => $lockedReport->id,
                'user_id'      => $lockedReport->user_id,
                'action'       => 'Response Added',
                'performed_by' => $admin->id,
                'old_status'   => $oldStatus,
                'new_status'   => $request->status,
                'remarks'      => "Response #{$nextResponseNumber} recorded by {$admin->name}.",
            ]);
        });

        return redirect()
            ->route('admin.handlereports.show', $report->id)
            ->with('success', 'Report updated successfully.');
    }


    // Bulk action for multiple reports
    public function bulkUpdate(Request $request)
    {
        $admin = Auth::user();

        $request->validate([
            'report_ids' => 'required|array',
            'report_ids.*' => 'exists:reports,id',
            'bulk_action' => 'required|in:assign,status_change',
            'assigned_to' => 'required_if:bulk_action,assign|nullable|string',
            'status' => 'required_if:bulk_action,status_change|nullable|in:approved,under review,resolved,rejected',
        ]);

        $reports = $this->applyRoleVisibility(
            Report::whereIn('id', $request->report_ids),
            $admin
        )->get();

        foreach ($reports as $report) {
            if ($request->bulk_action === 'assign' && $request->assigned_to) {
                $report->update([
                    'assigned_to' => $request->assigned_to,
                    'handled_by' => $admin->id,
                ]);

                Activity::create([
                    'report_id' => $report->id,
                    'user_id' => $report->user_id,
                    'action' => 'Bulk Assignment',
                    'performed_by' => $admin->id,
                    'remarks' => "Assigned to {$request->assigned_to} via bulk action",
                ]);
            }

            if ($request->bulk_action === 'status_change' && $request->status) {
                $oldStatus = $report->status;
                $report->update([
                    'status' => $request->status,
                    'handled_by' => $admin->id,
                ]);

                Activity::create([
                    'report_id' => $report->id,
                    'user_id' => $report->user_id,
                    'action' => 'Bulk Status Update',
                    'performed_by' => $admin->id,
                    'old_status' => $oldStatus,
                    'new_status' => $request->status,
                    'remarks' => "Status changed from '{$oldStatus}' to '{$request->status}' via bulk action",
                ]);
            }
        }

        return redirect()
            ->route('admin.handlereports.index')
            ->with('success', count($reports) . ' report(s) updated successfully.');
    }


    // Export filtered reports to CSV
    public function export(Request $request)
    {
        $admin = Auth::user();

        $query = $this->applyRoleVisibility(Report::with(['user', 'category'])
            ->where(function ($q) {
                $q->whereRaw('LOWER(status) = ?', ['approved'])
                  ->orWhereRaw('LOWER(status) = ?', ['rejected'])
                  ->orWhereRaw('LOWER(status) = ?', ['under review'])
                  ->orWhereRaw('LOWER(status) = ?', ['resolved']);
            }),
            $admin
        );

        // Apply same filters as index
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('status')) {
            $query->whereRaw('LOWER(status) = ?', [strtolower((string) $request->status)]);
        }

        $reports = $query->get();

        $filename = 'reports_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($reports) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Title', 'Reporter', 'Category', 'Status', 'Assigned To', 'Date Submitted', 'Target Date']);

            foreach ($reports as $report) {
                fputcsv($file, [
                    $report->id,
                    $report->title,
                    $report->user->name ?? 'Unknown',
                    $report->category->name ?? 'N/A',
                    $report->status,
                    $report->assigned_to ?? 'Unassigned',
                    $report->created_at->format('Y-m-d'),
                    $report->target_date ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

