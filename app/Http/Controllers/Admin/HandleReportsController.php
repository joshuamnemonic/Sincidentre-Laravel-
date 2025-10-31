<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Report;
use App\Models\Activity;

class HandleReportsController extends Controller
{
    // Show all approved reports
    public function index()
    {
        $approvedReports = Report::whereIn('status', ['approved', 'under review', 'resolved'])
                         ->orderByRaw("FIELD(status, 'approved', 'under review', 'resolved')")
                         ->latest()
                         ->get();


        return view('admin.handlereports', compact('approvedReports'));
    }

    // Show specific approved report for handling
    public function show($id)
    {
        $report = Report::findOrFail($id);
        return view('admin.handlereports_show', compact('report'));
    }

    // Update report handling info
    public function update(Request $request, $id)
    {
        $report = Report::findOrFail($id);

        $report->update([
            'assigned_to' => $request->assigned_to,
            'department' => $request->department,
            'target_date' => $request->target_date,
            'remarks' => $request->remarks,
            'status' => $request->status,
        ]);

        // Optional: log admin activity
        Activity::create([
            'report_id' => $report->id,
            'action' => 'Handled Report Updated',
            'performed_by' => auth()->user()->name ?? 'Admin',
            'remarks' => $request->remarks ?? 'No remarks provided',
        ]);
         $report->refresh();

        return redirect()->route('admin.handlereports.show', $report->id)
                         ->with('success', 'Report updated successfully.');
    }
}
