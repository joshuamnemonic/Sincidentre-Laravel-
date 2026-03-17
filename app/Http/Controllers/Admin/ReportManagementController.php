<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Report;
use App\Models\Activity;
use App\Models\Category;
use App\Services\ReportRoutingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ReportManagementController extends Controller
{
    private function applyRoleVisibility($query, $manager)
    {
        if ($manager->is_top_management) {
            $positionCode = trim((string) ($manager->routing_position_code ?? ''));
            $fullName = strtolower(trim((string) (($manager->first_name ?? '') . ' ' . ($manager->last_name ?? ''))));

            return $query->where(function ($q) use ($positionCode, $fullName) {
                if ($positionCode !== '') {
                    $q->where('assigned_position_code', $positionCode);
                }

                if ($fullName !== '') {
                    if ($positionCode !== '') {
                        $q->orWhereRaw('LOWER(assigned_to) = ?', [$fullName]);
                    } else {
                        $q->whereRaw('LOWER(assigned_to) = ?', [$fullName]);
                    }
                }

                if ($positionCode === '' && $fullName === '') {
                    $q->whereRaw('1 = 0');
                }
            });
        }

        return $query->whereHas('user', function ($q) use ($manager) {
            $q->where('department_id', $manager->department_id);
        })->whereHas('category', function ($q) {
            $q->whereNotIn('classification', ['Major', 'Grave']);
        });
    }

    /**
     * Display a listing of pending reports for the admin's department.
     */
    public function index(Request $request)
    {
        $admin = Auth::user();

        $reports = $this->applyRoleVisibility(
            Report::with(['user', 'category'])->whereRaw('LOWER(status) = ?', ['pending']),
            $admin
        )
            ->orderBy('incident_date', 'desc')
            ->get();

        return view('admin.reports', compact('reports'));
    }

    /**
     * Show the details of a single report (only from admin's department).
     */
    public function show($id)
    {
        $admin = Auth::user();

        $report = $this->applyRoleVisibility(Report::with(['user', 'category']), $admin)->findOrFail($id);
        Gate::authorize('accessHandling', $report);

        // Get report activities
        $activities = Activity::where('report_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.reportshow', compact('report', 'activities'));
    }

    /**
     * Approve a report (only from admin's department)
     */
    public function approve($id, ReportRoutingService $routingService)
    {
        $admin = Auth::user();

        $report = $this->applyRoleVisibility(Report::query(), $admin)->findOrFail($id);
        Gate::authorize('decide', $report);

        $oldStatus = $report->status;
        $report->status = Report::STATUS_APPROVED;
        $report->handled_by = $admin->id;
        $report->save();

        $routingService->autoAssign($report, 'approval', $admin->id, false);

        // Log the action
        Activity::create([
            'report_id' => $report->id,
            'user_id' => $report->user_id,
            'action' => 'Report Approved',
            'performed_by' => $admin->id,
            'old_status' => $oldStatus,
            'new_status' => Report::STATUS_APPROVED,
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

        // Validate rejection reason
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ], [
            'rejection_reason.required' => 'Please provide a reason for rejection.',
            'rejection_reason.max' => 'Rejection reason must not exceed 1000 characters.',
        ]);

        // Only reject reports from the same department
        $report = $this->applyRoleVisibility(Report::query(), $admin)->findOrFail($id);
        Gate::authorize('decide', $report);

        $oldStatus = $report->status;
        $report->status = Report::STATUS_REJECTED;
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
            'new_status' => Report::STATUS_REJECTED,
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

        $approvedReports = $this->applyRoleVisibility(
            Report::where('status', 'Approved')->with(['user', 'category']),
            $admin
        )->latest()->get();

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

        $report = $this->applyRoleVisibility(Report::with(['user', 'category']), $admin)->findOrFail($id);
        Gate::authorize('accessHandling', $report);

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
        $report = $this->applyRoleVisibility(Report::query(), $admin)->findOrFail($id);
        Gate::authorize('updateHandling', $report);

        $oldStatus = $report->status;
        $oldAssignedTo = $report->assigned_to;

        // Update the report details
        $report->update([
            'assigned_to' => $request->assigned_to,
            'assigned_position_code' => null,
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

        $query = $this->applyRoleVisibility(Report::with(['user', 'category']), $admin);

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
            fputcsv($file, ['ID', 'Reporter', 'Category', 'Status', 'Incident Date', 'Handled By']);

            foreach ($reports as $report) {
                fputcsv($file, [
                    $report->id,
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

    public function escalateToTopManagement($id, ReportRoutingService $routingService)
    {
        $admin = Auth::user();

        if ($admin->is_top_management) {
            return redirect()->route('admin.reports')->with('error', 'Top Management cannot escalate reports to itself.');
        }

        $report = $this->applyRoleVisibility(Report::with('category'), $admin)->findOrFail($id);
        Gate::authorize('escalate', $report);

        if (!$report->category || !in_array($report->category->classification, ['Major', 'Grave'], true)) {
            return redirect()->route('admin.reports')->with('error', 'Only Major or Grave reports can be escalated to Top Management.');
        }

        $report->update([
            'escalated_to_top_management' => true,
            'escalated_at' => now(),
            'escalated_by' => $admin->id,
        ]);

        $routingService->autoAssign($report, 'approval', $admin->id, false);

        Activity::create([
            'report_id' => $report->id,
            'user_id' => $report->user_id,
            'action' => 'Escalated to Top Management',
            'performed_by' => $admin->id,
            'old_status' => $report->status,
            'new_status' => $report->status,
            'remarks' => 'Escalated by ' . $admin->first_name . ' ' . $admin->last_name,
        ]);

        return redirect()->route('admin.reports.show', $report->id)->with('success', 'Report escalated to Top Management.');
    }
}