<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Report;
use App\Models\Activity;

class ReportManagementController extends Controller
{
    /**
     * Display a listing of the reports (Review Queue).
     */
    public function index(Request $request)
    {
        $query = Report::with('user') // eager load reporter
            ->whereIn('status', ['Pending', 'Under Review']);

        // 🔍 Search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                  ->orWhere('category', 'like', "%$search%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('name', 'like', "%$search%");
                  });
            });
        }

        $reports = $query->orderBy('incident_date', 'desc')->get();

        return view('admin.reports', compact('reports'));
    }

    /**
     * Show the details of a single report.
     */
    public function show($id)
    {
        $report = Report::with('user')->findOrFail($id);

        return view('admin.reportshow', compact('report'));
    }

    /**
     * Approve a report
     */
    public function approve($id)
    {
        $report = Report::findOrFail($id);
        $report->status = 'Approved';
        $report->save();

        return redirect()->route('admin.reports')
                         ->with('success', 'Report approved successfully.');
    }

    /**
     * Reject a report
     */
    public function reject($id)
    {
        $report = Report::findOrFail($id);
        $report->status = 'Rejected';
        $report->save();

        return redirect()->route('admin.reports')
                         ->with('error', 'Report rejected.');
    }

    public function handleReports()
{
    // Fetch all approved reports
    $approvedReports = Report::where('status', 'Approved')->latest()->get();

    return view('admin.handlereports', compact('approvedReports'));
}

public function showHandleReport($id)
{
    $report = Report::findOrFail($id);
    $activities = $report->activities()->latest()->get(); // optional if you have an activity log
    return view('admin.handle_single', compact('report', 'activities'));
}

public function updateHandled(Request $request, $id)
{
    $report = Report::findOrFail($id);

    // ✅ Update the report details
    $report->update([
        'assigned_to' => $request->assigned_to,
        'department' => $request->department,
        'target_date' => $request->target_date,
        'remarks' => $request->remarks,
        'status' => $request->status,
    ]);

    // ✅ Log the action in the activities table
    Activity::create([
        'report_id' => $report->id,
        'action' => 'Report updated',
        'performed_by' => auth()->user()->name,
        'remarks' => $request->remarks ?? 'No remarks provided',
    ]);

    // ✅ Redirect back to the same page with a success message
    return redirect()
        ->route('admin.handlereports.show', $report->id)
        ->with('success', 'Report updated and logged successfully.');
}

}
