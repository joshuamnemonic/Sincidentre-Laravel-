<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Report;
use App\Models\Activity;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;

class ReportController extends Controller
{
    /**
     * Show the New Report form (for users).
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('user.newreport', compact('categories'));
    }

    /**
     * Store a new report.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'category_id'      => 'required|exists:categories,id',
            'description'      => 'required|string',
            'incident_date'    => 'required|date',
            'incident_time'    => 'required',
            'location'         => 'required|string|max:255',
            'location_details' => 'nullable|string|max:255',
            'evidence.*'       => 'nullable|file|mimes:jpg,jpeg,png,mp4,mov,avi,pdf|max:10240',
        ]);

        $evidencePaths = [];
        if ($request->hasFile('evidence')) {
            foreach ($request->file('evidence') as $file) {
                $path = $file->store('evidences', 'public');
                $evidencePaths[] = $path;
            }
        }

        // Combine location and details
        $location = $validated['location'];
        if (!empty($validated['location_details'])) {
            $location .= ' - ' . $validated['location_details'];
        }

        $report = Report::create([
            'title'         => $validated['title'],
            'category_id'   => $validated['category_id'],
            'description'   => $validated['description'],
            'incident_date' => $validated['incident_date'],
            'incident_time' => $validated['incident_time'],
            'location'      => $location,
            'evidence'      => !empty($evidencePaths) ? json_encode($evidencePaths) : null,
            'submitted_at'  => now(),
            'status'        => 'Pending',
            'user_id'       => Auth::id(),
        ]);

        // ❌ REMOVED - Don't log user submissions in activity logs
        // Activity logs should only track admin actions

        return redirect()->route('newreport')->with('success', '✅ Report submitted successfully!');
    }

    public function show($id) {
        $report = Report::with([
            'category',
            'responses.admin',
            'activities.admin',
        ])->findOrFail($id);

        return view('user.reportshow', compact('report'));
    }

    public function approve($id) {
        $report = Report::findOrFail($id);
        $oldStatus = $report->status;
        
        $report->status = 'Approved';
        $report->handled_by = Auth::id();
        $report->save();

        // ✅ Log admin action - use Auth::id() not full name
        Activity::create([
            'report_id'    => $report->id,
            'user_id'      => $report->user_id,      // ✅ Report owner
            'action'       => 'Report Approved',
            'performed_by' => Auth::id(),            // ✅ Admin ID (not name)
            'old_status'   => $oldStatus,
            'new_status'   => 'Approved',
            'remarks'      => 'Report has been approved',
        ]);

        return redirect()->back()->with('success', 'Report approved successfully.');
    }

    public function reject(Request $request, $id) {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $report = Report::findOrFail($id);
        $oldStatus = $report->status;
        
        $report->status = 'Rejected';
        $report->rejection_reason = $request->rejection_reason;
        $report->handled_by = Auth::id();
        $report->save();

        // ✅ Log admin action
        Activity::create([
            'report_id'    => $report->id,
            'user_id'      => $report->user_id,      // ✅ Report owner
            'action'       => 'Report Rejected',
            'performed_by' => Auth::id(),            // ✅ Admin ID (not name)
            'old_status'   => $oldStatus,
            'new_status'   => 'Rejected',
            'remarks'      => 'Rejection reason: ' . $request->rejection_reason,
        ]);
        
        return redirect()->back()->with('success', 'Report rejected successfully.');
    }
}