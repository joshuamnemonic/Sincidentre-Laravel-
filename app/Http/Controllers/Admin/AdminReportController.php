<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\ActivityLogger;

class AdminReportController extends Controller
{
    /**
     * Show the Department Student Discipline Officer Dashboard (Overview)
     */
    public function index()
    {
        $admin = Auth::user();
        $departmentId = $admin->department_id;

        // Dashboard statistics - filtered by department
        $totalReports = Report::whereHas('user', function($query) use ($departmentId) {
            $query->where('department_id', $departmentId);
        })->count();

        $pendingReports = Report::where('status', 'Pending')
            ->whereHas('user', function($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            })->count();

        $underReview = Report::where('status', 'Under Review')
            ->whereHas('user', function($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            })->count();

        $resolvedReports = Report::where('status', 'Resolved')
            ->whereHas('user', function($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            })->count();

        $totalUsers = User::where('department_id', $departmentId)
            ->where('is_department_student_discipline_officer', 0)
            ->count();

        // Fetch the most recent reports from the same department
        $recentReports = Report::whereHas('user', function($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            })
            ->with(['user', 'categoryRelation'])
            ->latest()
            ->take(10)
            ->get();

        // Return the admin dashboard view
        return view('admin.admindashboard', compact(
            'totalReports', 'pendingReports', 'underReview', 'resolvedReports', 'totalUsers', 'recentReports'
        ));
    }

    /**
     * Show the Review Queue (Pending + Under Review reports)
     */
    public function reports()
    {
        $admin = Auth::user();
        $departmentId = $admin->department_id;

        // Only show reports from the same department
        $pendingReports = Report::whereIn('status', ['Pending', 'Under Review'])
            ->whereHas('user', function($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            })
            ->with(['user', 'categoryRelation'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.reports.index', compact('pendingReports'));
    }

    /**
     * Show the details of a single report
     */
    public function show($id)
    {
        $admin = Auth::user();
        $departmentId = $admin->department_id;

        // Make sure the report belongs to a user in the same department
        $report = Report::whereHas('user', function($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            })
            ->with('user', 'categoryRelation')
            ->findOrFail($id);

        return view('admin.reports.show', compact('report'));
    }

    /**
     * Approve a report
     */
    public function approve($id)
    {
        $admin = Auth::user();
        $departmentId = $admin->department_id;

        // Only approve reports from the same department
        $report = Report::whereHas('user', function($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            })->findOrFail($id);

        $report->status = 'Approved';
        $report->handled_by = $admin->id; // Track who handled it
        $report->save();

        return redirect()->route('admin.reports')->with('success', 'Report approved successfully.');
    }

    /**
     * Reject a report
     */
    public function reject($id)
    {
        $admin = Auth::user();
        $departmentId = $admin->department_id;

        // Only reject reports from the same department
        $report = Report::whereHas('user', function($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            })->findOrFail($id);

        $report->status = 'Rejected';
        $report->handled_by = $admin->id; // Track who handled it
        $report->save();

        return redirect()->route('admin.reports')->with('error', 'Report rejected successfully.');
    }
    public function updateStatus(Request $request, $id)
{
    $report = Report::findOrFail($id);

    $oldStatus = $report->status;
    $newStatus = $request->status;

    $report->update(['status' => $newStatus]);

    ActivityLogger::log(
        'Updated Report Status',
        $report->id,
        $oldStatus,
        $newStatus,
        $request->remarks
    );

    return back();
}

}


