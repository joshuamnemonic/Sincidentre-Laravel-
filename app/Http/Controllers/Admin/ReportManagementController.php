<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Report;
use App\Models\Activity;
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

        // 🔍 Search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                  ->orWhere('category', 'like', "%$search%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('first_name', 'like', "%$search%")
                        ->orWhere('last_name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%");
                  });
            });
        }

        $reports = $query->orderBy('incident_date', 'desc')->get();

        return view('admin.reports', compact('reports'));
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

        return view('admin.reportshow', compact('report'));
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

        $report->status = 'Approved';
        $report->handled_by = $admin->id;
        $report->save();

        // Log the action
        Activity::create([
            'report_id' => $report->id,
            'action' => 'Report approved',
            'performed_by' => $admin->first_name . ' ' . $admin->last_name,
            'remarks' => 'Report has been approved',
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
        ]);

        // Only reject reports from the same department
        $report = Report::whereHas('user', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            })->findOrFail($id);

        $report->status = 'Rejected';
        $report->rejection_reason = $request->rejection_reason;
        $report->handled_by = $admin->id;
        $report->save();

        // Log the action
        Activity::create([
            'report_id' => $report->id,
            'action' => 'Report rejected',
            'performed_by' => $admin->first_name . ' ' . $admin->last_name,
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

        return view('admin.handlereports', compact('approvedReports'));
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

        $activities = $report->activities()->latest()->get();

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
            'target_date' => 'nullable|date',
            'remarks' => 'nullable|string',
            'status' => 'required|in:Pending,Under Review,Approved,Resolved,Rejected',
        ]);

        // Find report (only from admin's department)
        $report = Report::whereHas('user', function($q) use ($departmentId) {
                    $q->where('department_id', $departmentId);
                })->findOrFail($id);

        // Update the report details
        $report->update([
            'assigned_to' => $request->assigned_to,
            'department' => $request->department,
            'target_date' => $request->target_date,
            'remarks' => $request->remarks,
            'status' => $request->status,
            'handled_by' => $admin->id,
        ]);

        // Log the action in the activities table
        Activity::create([
            'report_id' => $report->id,
            'action' => 'Report updated',
            'performed_by' => $admin->first_name . ' ' . $admin->last_name,
            'remarks' => $request->remarks ?? 'No remarks provided',
        ]);

        // Redirect back to the same page with a success message
        return redirect()
            ->route('admin.handlereports.show', $report->id)
            ->with('success', 'Report updated and logged successfully.');
    }
}