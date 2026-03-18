<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class NewReportController extends Controller
{
    /**
     * Show the New Report form.
     */
    public function create()
    {
        return view('user.newreport');
    }

    /**
     * Handle the report form submission.
     */
    public function store(Request $request)
    {
        // ✅ Validation for multiple file uploads
        $validated = $request->validate([
            'category' => 'required|string|max:100',
            'description' => 'required|string',
            'incident_date' => 'required|date',
            'incident_time' => 'required',
            'location' => 'required|string|max:255',
            'location_details' => 'nullable|string|max:255',
            'evidence' => 'required|array',
            'evidence.*' => 'file|max:51200|mimes:jpg,jpeg,png,mp4,avi,mov,pdf',
        ]);

        // ✅ Handle multiple evidence file uploads
        $evidencePaths = [];
        if ($request->hasFile('evidence')) { // ✅ fixed: should be 'evidence'
            foreach ($request->file('evidence') as $file) {
                $path = $file->store('evidences', 'public'); // ✅ store inside 'evidences' folder
                $evidencePaths[] = $path;
            }
        }

        // ✅ Store report data
        $reportPayload = [
            'category' => $validated['category'],
            'description' => $validated['description'],
            'incident_date' => $validated['incident_date'],
            'incident_time' => $validated['incident_time'],
            'location' => $validated['location'],
            'location_details' => Schema::hasColumn('reports', 'location_details')
                ? ($validated['location_details'] ?? null)
                : null,
            'evidence' => json_encode($evidencePaths), // store multiple paths as JSON
            'status' => 'Pending',
            'submitted_at' => now(),
            'user_id' => Auth::id(),
        ];

        if (Schema::hasColumn('reports', 'title')) {
            $reportPayload['title'] = 'LLCC Incident Report';
        }

        Report::create($reportPayload);

        return redirect()
            ->route('newreport')
            ->with('success', '✅ Report submitted successfully!');
    }
}
