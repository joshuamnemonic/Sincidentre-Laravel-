<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    /**
     * Show the Admin Dashboard (Overview)
     */
    public function index()
    {
        // Dashboard statistics
        $totalReports = Report::count();
        $pendingReports = Report::where('status', 'Pending')->count();
        $underReview = Report::where('status', 'Under Review')->count();
        $resolvedReports = Report::where('status', 'Resolved')->count();
        $totalUsers = User::count();

        // Fetch the most recent reports
        $recentReports = Report::latest()->take(10)->get();

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
        $pendingReports = Report::whereIn('status', ['Pending', 'Under Review'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.reports.index', compact('pendingReports'));
    }

    /**
     * Show the details of a single report
     */
    public function show($id)
    {
        $report = Report::with('user', 'categoryRelation')->findOrFail($id);
        return view('admin.reports.show', compact('report'));
    }

    /**
     * Approve a report
     */
    public function approve($id)
    {
        $report = Report::findOrFail($id);
        $report->status = 'Approved';
        $report->save();

        return redirect()->route('admin.reports')->with('success', 'Report approved successfully.');
    }

    /**
     * Reject a report
     */
    public function reject($id)
    {
        $report = Report::findOrFail($id);
        $report->status = 'Rejected';
        $report->save();

        return redirect()->route('admin.reports')->with('error', 'Report rejected successfully.');
    }
    
}
