<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Report;
use App\Models\Activity;
use Illuminate\Support\Facades\Auth;

class HandleReportsController extends Controller
{
    // Show all approved reports from admin's department only
    public function index()
{
    $admin = Auth::user();
    $departmentId = $admin->department_id;

    $approvedReports = Report::whereIn('status', ['approved', 'under review', 'resolved'])
                     ->whereHas('user', function($query) use ($departmentId) {
                         $query->where('department_id', $departmentId);
                     })
                     ->with(['user', 'category']) 
                     ->orderByRaw("FIELD(status, 'approved', 'under review', 'resolved')")
                     ->latest()
                     ->get();

    return view('admin.handlereports', compact('approvedReports'));
}

    // Show specific approved report for handling (only from admin's department)
    public function show($id)
    {
        $admin = Auth::user();
        $departmentId = $admin->department_id;

        // Make sure the report belongs to admin's department
        $report = Report::whereHas('user', function($query) use ($departmentId) {
                    $query->where('department_id', $departmentId);
                })
                ->with(['user', 'category'])
                ->findOrFail($id);

        return view('admin.handlereports_show', compact('report'));
    }

    // Update report handling info (only for reports in admin's department)
    public function update(Request $request, $id)
{
    $admin = Auth::user();
    $departmentId = $admin->department_id;

    $request->validate([
        'assigned_to' => 'nullable|string|max:255',
        'department'  => 'nullable|string|max:255',
        'target_date' => 'nullable|date',
        'remarks'     => 'nullable|string',
        'status'      => 'required|in:approved,under review,resolved,rejected',
    ]);

    // Fetch report (department check)
    $report = Report::whereHas('user', function($query) use ($departmentId) {
        $query->where('department_id', $departmentId);
    })->findOrFail($id);

    // ✅ DEFINE OLD STATUS HERE
    $oldStatus = $report->status;

    // Update report
    $report->update([
        'assigned_to' => $request->assigned_to,
        'department'  => $request->department,
        'target_date' => $request->target_date,
        'remarks'     => $request->remarks,
        'status'      => $request->status,
        'handled_by'  => $admin->id,
    ]);

    // ✅ DEFINE NEW STATUS
    $newStatus = $request->status;

    // ✅ LOG ONLY IF STATUS CHANGED
    if ($oldStatus !== $newStatus) {
        Activity::create([
    'report_id'    => $report->id,
    'user_id'      => $report->user_id,      // ✅ reporter
    'action'       => 'Report Status Updated',
    'performed_by' => $admin->id,             // ✅ admin
    'old_status'   => $oldStatus,              // ✅ before
    'new_status'   => $newStatus,              // ✅ after
    'remarks'      => "Status changed from {$oldStatus} to {$newStatus}",
]);
    }

    return redirect()
        ->route('admin.handlereports.show', $report->id)
        ->with('success', 'Report updated successfully.');
}

}