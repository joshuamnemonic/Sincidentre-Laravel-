<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
     * Store a new report.
     */
    public function store(Request $request)
    {
        // ✅ Validate the incoming request
        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'category'      => 'required|string',
            'description'   => 'required|string',
            'incident_date' => 'required|date',
            'incident_time' => 'required',
            'location'      => 'required|string|max:255',
            'evidence.*'    => 'nullable|file|mimes:jpg,jpeg,png,mp4,mov,avi|max:10240',

        ]);

        // 🔍 Debug (optional) - uncomment to check inputs
        // dd($validated);

        // ✅ Handle multiple file uploads
        $evidencePaths = [];
        if ($request->hasFile('evidence')) {
            foreach ($request->file('evidence') as $file) {
                $path = $file->store('evidence', 'public'); // stored in storage/app/public/evidence
                $evidencePaths[] = $path;
            }
        }

        // ✅ Create and save the report
        $report = Report::create([
            'title'         => $validated['title'],
            'category'      => $validated['category'],
            'description'   => $validated['description'],
            'incident_date' => $validated['incident_date'],
            'incident_time' => $validated['incident_time'],
            'location'      => $validated['location'],
            'evidence'      => !empty($evidencePaths) ? json_encode($evidencePaths) : null,
            'submitted_at'  => now(),
            'status'        => 'Pending',
            'user_id'       => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Report submitted successfully!');
    }
    public function show($id) {
    $report = Report::findOrFail($id);
    return view('user.reportshow', compact('report'));
    
}
public function approve($id) {
    $report = Report::findOrFail($id);
    $report->status = 'Approved';
    $report->save();
    return redirect()->back()->with('success', 'Report approved successfully.');
}

public function reject($id) {
    $report = Report::findOrFail($id);
    $report->status = 'Rejected';
    $report->save();
    return redirect()->back()->with('success', 'Report rejected successfully.');
}


}
