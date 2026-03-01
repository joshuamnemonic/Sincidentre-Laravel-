<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Report;
use App\Models\Activity;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;

class ReportManagementController extends Controller
{
    /**
     * Display a listing of the reports (Review Queue) - filtered by department.
     */
    public function index(Request $request)
    {
        $admin = Auth::user();
        $departmentId = $admin->department_id;

        $query = Report::with(['user', 'category']) // eager load reporter and category
            ->whereIn('status', ['Pending', 'Under Review'])
            ->whereHas('user', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });

        // 🔍 Search filter (from header search)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('first_name', 'like', "%$search%")
                        ->orWhere('last_name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%");
                  });
            });
        }

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
                $q->where('first_name', 'like', '%' . $request->reporter . '%')
                  ->orWhere('last_name', 'like', '%' . $request->reporter . '%');
            });
        }

        // FILTER: Date range
        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('incident_date', [
                $request->from,
                $request->to
            ]);
        } elseif ($request->filled('from')) {
            $query->whereDate('incident_date', '>=', $request->from);
        } elseif ($request->filled('to')) {
            $query->whereDate('incident_date', '<=', $request->to);
        }

        $reports = $query->orderBy('incident_date', 'desc')->get();

        // Get all categories for filter dropdown
        $categories = Category::all();

        // Get status counts for quick stats
        $statusCounts = [
            'pending' => Report::where('status', 'Pending')
                ->whereHas('user', fn($q) => $q->where('department_id', $departmentId))
                ->count(),
            'approved' => Report::where('status', 'Approved')
                ->whereHas('user', fn($q) => $q->where('department_id', $departmentId))
                ->count(),
            'rejected' => Report::where('status', 'Rejected')
                ->whereHas('user', fn($q) => $q->where('department_id', $departmentId))
                ->count(),
            'under_review' => Report::where('status', 'Under Review')
                ->whereHas('user', fn($q) => $q->where('department_id', $departmentId))
                ->count(),
        ];

        return view('admin.reports', compact('reports', 'categories', 'statusCounts'));
    }

    /**
     * Show the details of a single report (only from admin's department).
     */
    public function show($id)
    {
        $admin = Auth::user();
        $departmentId = $admin->department_id;

        $report = Report::with(['user', 'category'])
            ->whereHas('user', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            })
            ->findOrFail($id);

        // Get report activities
        $activities = Activity::where('report_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.reportshow', compact('report', 'activities'));
    }

    /**
     * Approve a report (only from admin's department)
     */
    public function approve($id)
    {
        $admin = Auth::user();
        $departmentId = $admin->department_id;

        $report = Report::whereHas('user', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            })->findOrFail($id);

        $oldStatus = $report->status;
        $report->status = 'Approved';
        $report->handled_by = $admin->id;
        $report->save();

        // Log the action
        Activity::create([
            'report_id' => $report->id,
            'user_id' => $report->user_id,
            'action' => 'Report Approved',
            'performed_by' => $admin->id,
            'old_status' => $oldStatus,
            'new_status' => 'Approved',
            'remarks' => 'Report has been approved by ' . $admin->first_name . ' ' . $admin->last_name,
        ]);

        return redirect()->route('admin.reports')
                         ->with('success', 'Report approved successfully.');
    }

    /**
     * Reject a report with reason (only from admin's department)
     */
    public function reject(Request $request, $id)
    {
        $admin = Auth::user();
        $departmentId = $admin->department_id;

        // Validate rejection reason
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ], [
            'rejection_reason.required' => 'Please provide a reason for rejection.',
            'rejection_reason.max' => 'Rejection reason must not exceed 1000 characters.',
        ]);

        // Only reject reports from the same department
        $report = Report::whereHas('user', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            })->findOrFail($id);

        $oldStatus = $report->status;
        $report->status = 'Rejected';
        $report->rejection_reason = $request->rejection_reason;
        $report->handled_by = $admin->id;
        $report->save();

        // Log the action
        Activity::create([
            'report_id' => $report->id,
            'user_id' => $report->user_id,
            'action' => 'Report Rejected',
            'performed_by' => $admin->id,
            'old_status' => $oldStatus,
            'new_status' => 'Rejected',
            'remarks' => 'Rejection reason: ' . $request->rejection_reason,
        ]);

        return redirect()->route('admin.reports')
                         ->with('success', 'Report rejected with reason.');
    }

    /**
     * Show all approved reports for handling (from admin's department only)
     */
    public function handleReports()
    {
        $admin = Auth::user();
        $departmentId = $admin->department_id;

        // Fetch all approved reports from the same department
        $approvedReports = Report::where('status', 'Approved')
            ->whereHas('user', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            })
            ->with(['user', 'category'])
            ->latest()
            ->get();

        // Get all categories for filter dropdown
        $categories = Category::all();

        return view('admin.handlereports', compact('approvedReports', 'categories'));
    }

    /**
     * Show single report for handling (only from admin's department)
     */
    public function showHandleReport($id)
    {
        $admin = Auth::user();
        $departmentId = $admin->department_id;

        $report = Report::whereHas('user', function($q) use ($departmentId) {
                    $q->where('department_id', $departmentId);
                })
                ->with(['user', 'category'])
                ->findOrFail($id);

        $activities = Activity::where('report_id', $report->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.handle_single', compact('report', 'activities'));
    }

    /**
     * Update handled report (only from admin's department)
     */
    public function updateHandled(Request $request, $id)
    {
        $admin = Auth::user();
        $departmentId = $admin->department_id;

        // Validate input
        $request->validate([
            'assigned_to' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'target_date' => 'nullable|date|after_or_equal:today',
            'remarks' => 'nullable|string|max:1000',
            'status' => 'required|in:Pending,Under Review,Approved,Resolved,Rejected',
        ], [
            'target_date.after_or_equal' => 'Target date must be today or a future date.',
            'status.required' => 'Please select a status.',
            'remarks.max' => 'Remarks must not exceed 1000 characters.',
        ]);

        // Find report (only from admin's department)
        $report = Report::whereHas('user', function($q) use ($departmentId) {
                    $q->where('department_id', $departmentId);
                })->findOrFail($id);

        $oldStatus = $report->status;
        $oldAssignedTo = $report->assigned_to;

        // Update the report details
        $report->update([
            'assigned_to' => $request->assigned_to,
            'department' => $request->department,
            'target_date' => $request->target_date,
            'remarks' => $request->remarks,
            'status' => $request->status,
            'handled_by' => $admin->id,
        ]);

        // Log status change if status was updated
        if ($oldStatus !== $request->status) {
            Activity::create([
                'report_id' => $report->id,
                'user_id' => $report->user_id,
                'action' => 'Status Updated',
                'performed_by' => $admin->id,
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'remarks' => "Status changed from '{$oldStatus}' to '{$request->status}' by {$admin->first_name} {$admin->last_name}" . ($request->filled('remarks') ? " — Notes: {$request->remarks}" : ''),
            ]);
        }

        // Log assignment change if assigned person was updated
        if ($oldAssignedTo !== $request->assigned_to) {
            Activity::create([
                'report_id' => $report->id,
                'user_id' => $report->user_id,
                'action' => 'Assignment Updated',
                'performed_by' => $admin->id,
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'remarks' => "Assigned to: " . ($request->assigned_to ?? 'Unassigned') . " by {$admin->first_name} {$admin->last_name}",
            ]);
        }

        // General activity log
        Activity::create([
            'report_id' => $report->id,
            'user_id' => $report->user_id,
            'action' => 'Report Updated',
            'performed_by' => $admin->id,
            'old_status' => $oldStatus,
            'remarks' => $request->filled('remarks') ? $request->remarks : "Report updated by {$admin->first_name} {$admin->last_name}",
        ]);

        // Redirect back to the same page with a success message
        return redirect()
            ->route('admin.handlereports.show', $report->id)
            ->with('success', 'Report updated and logged successfully.');
    }

    /**
     * Export filtered reports to CSV
     */
    public function export(Request $request)
    {
        $admin = Auth::user();
        $departmentId = $admin->department_id;

        $query = Report::with(['user', 'category'])
            ->whereHas('user', fn($q) => $q->where('department_id', $departmentId));

        // Apply same filters as index
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('incident_date', [$request->from, $request->to]);
        }

        $reports = $query->get();

        $filename = 'reports_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($reports) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Title', 'Reporter', 'Category', 'Status', 'Incident Date', 'Handled By']);

            foreach ($reports as $report) {
                fputcsv($file, [
                    $report->id,
                    $report->title,
                    ($report->user->first_name ?? '') . ' ' . ($report->user->last_name ?? ''),
                    $report->category->name ?? 'N/A',
                    $report->status,
                    $report->incident_date,
                    $report->handled_by ?? 'Not handled',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}