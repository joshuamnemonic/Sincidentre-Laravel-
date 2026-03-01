<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Report;
use App\Models\Activity;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;

class HandleReportsController extends Controller
{
    // Show reports from admin's department with filters
    public function index(Request $request)
    {
        $admin = Auth::user();
        $departmentId = $admin->department_id;

        $query = Report::with(['user', 'category'])
            ->whereIn('status', ['approved', 'under review', 'resolved'])
            ->whereHas('user', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });

        // FILTER: Category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // FILTER: Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
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
            $query->orderByRaw("FIELD(status, 'approved', 'under review', 'resolved')");
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
        $statuses = ['approved', 'under review', 'resolved'];

        // Count reports by status for quick overview
        $statusCounts = [
            'approved' => Report::where('status', 'approved')
                ->whereHas('user', fn($q) => $q->where('department_id', $departmentId))
                ->count(),
            'under_review' => Report::where('status', 'under review')
                ->whereHas('user', fn($q) => $q->where('department_id', $departmentId))
                ->count(),
            'resolved' => Report::where('status', 'resolved')
                ->whereHas('user', fn($q) => $q->where('department_id', $departmentId))
                ->count(),
        ];

        return view('admin.handlereports', compact(
            'approvedReports', 
            'categories', 
            'statuses',
            'statusCounts'
        ));
    }


    // Show specific report (restricted to department)
    public function show($id)
    {
        $admin = Auth::user();
        $departmentId = $admin->department_id;

        $report = Report::whereHas('user', function($query) use ($departmentId) {
                    $query->where('department_id', $departmentId);
                })
                ->with(['user', 'category', 'activities.performedBy'])
                ->findOrFail($id);

        // Get report history/activities
        $activities = Activity::where('report_id', $id)
            ->with('performedBy')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.handlereports_show', compact('report', 'activities'));
    }


    // Update report handling info
    public function update(Request $request, $id)
    {
        $admin = Auth::user();
        $departmentId = $admin->department_id;

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

        $report = Report::whereHas('user', function($query) use ($departmentId) {
            $query->where('department_id', $departmentId);
        })->findOrFail($id);

        $oldStatus = $report->status;
        $oldAssignedTo = $report->assigned_to;

        $report->update([
            'assigned_to' => $request->assigned_to,
            'department'  => $request->department,
            'target_date' => $request->target_date,
            'remarks'     => $request->remarks,
            'status'      => $request->status,
            'handled_by'  => $admin->id,
            'updated_at'  => now(),
        ]);

        $newStatus = $request->status;

        // Log activity if status changed
        if ($oldStatus !== $newStatus) {
            Activity::create([
                'report_id'    => $report->id,
                'user_id'      => $report->user_id,
                'action'       => 'Status Updated',
                'performed_by' => $admin->id,
                'old_status'   => $oldStatus,
                'new_status'   => $newStatus,
                'remarks'      => "Status changed from '{$oldStatus}' to '{$newStatus}' by {$admin->name}",
            ]);
        }

        // Log activity if assigned person changed
        if ($oldAssignedTo !== $request->assigned_to) {
            Activity::create([
                'report_id'    => $report->id,
                'user_id'      => $report->user_id,
                'action'       => 'Assignment Updated',
                'performed_by' => $admin->id,
                'old_status'   => $oldStatus,
                'new_status'   => $newStatus,
                'remarks'      => "Assigned to: " . ($request->assigned_to ?? 'Unassigned') . " by {$admin->name}",
            ]);
        }

        return redirect()
            ->route('admin.handlereports.show', $report->id)
            ->with('success', 'Report updated successfully.');
    }


    // Bulk action for multiple reports
    public function bulkUpdate(Request $request)
    {
        $admin = Auth::user();
        $departmentId = $admin->department_id;

        $request->validate([
            'report_ids' => 'required|array',
            'report_ids.*' => 'exists:reports,id',
            'bulk_action' => 'required|in:assign,status_change',
            'assigned_to' => 'required_if:bulk_action,assign|nullable|string',
            'status' => 'required_if:bulk_action,status_change|nullable|in:approved,under review,resolved,rejected',
        ]);

        $reports = Report::whereIn('id', $request->report_ids)
            ->whereHas('user', fn($q) => $q->where('department_id', $departmentId))
            ->get();

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
        $departmentId = $admin->department_id;

        $query = Report::with(['user', 'category'])
            ->whereIn('status', ['approved', 'under review', 'resolved'])
            ->whereHas('user', fn($q) => $q->where('department_id', $departmentId));

        // Apply same filters as index
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
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