<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Report;
use App\Models\Activity;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    /**
     * Show the New Report form (for users).
     */
    public function create()
    {
        $categories = Category::orderBy('main_category_code')
            ->orderBy('classification')
            ->orderBy('name')
            ->get();

        $categoriesByMain = $categories
            ->groupBy('main_category_code')
            ->map(function ($group) {
                return [
                    'main_name' => $group->first()->main_category_name,
                    'items' => $group->values(),
                ];
            });

        return view('user.newreport', compact('categories', 'categoriesByMain'));
    }

    /**
     * Store a new report.
     */
    public function store(Request $request)
    {
        $allowedRoleValues = ['Student', 'Faculty', 'Employee/Staff'];
        $allowedEvidenceExtensions = 'jpg,jpeg,png,gif,webp,bmp,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,rar,7z,mp3,wav,m4a,aac,ogg,flac,mp4,mov,avi,mkv,wmv,webm';

        $validated = $request->validate([
            'person_full_name'             => 'required|string|max:255',
            'person_college_department'    => 'required|string|max:255',
            'person_role'                  => ['required', Rule::in($allowedRoleValues)],
            'person_contact_number'        => 'nullable|string|max:50',
            'person_email_address'         => 'nullable|email|max:255',
            'person_has_multiple'          => 'required|boolean',
            'additional_persons'           => 'required_if:person_has_multiple,1|array',
            'additional_persons.*.full_name' => 'required_if:person_has_multiple,1|string|max:255',
            'additional_persons.*.college_department' => 'required_if:person_has_multiple,1|string|max:255',
            'additional_persons.*.role'    => ['required_if:person_has_multiple,1', Rule::in($allowedRoleValues)],
            'additional_persons.*.contact_number' => 'nullable|string|max:50',
            'additional_persons.*.email_address' => 'nullable|email|max:255',
            'description'      => 'required|string',
            'incident_date'    => 'required|date',
            'incident_time'    => 'required',
            'location'         => 'required|string|max:255',
            'location_details' => 'nullable|string|max:255',
            'has_witnesses'                => 'required|boolean',
            'witness_details'              => 'required_if:has_witnesses,1|array',
            'witness_details.*.name'       => 'required_if:has_witnesses,1|string|max:255',
            'witness_details.*.address'    => 'required_if:has_witnesses,1|string|max:255',
            'witness_details.*.contact_number' => 'required_if:has_witnesses,1|string|max:50',
            'incident_additional_sheets.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'informant_contact_number'     => 'nullable|string|max:50',
            'category_id'                  => 'required|exists:categories,id',
            'evidence'        => 'required|array|min:1',
            'evidence.*'      => 'required|file|mimes:' . $allowedEvidenceExtensions . '|max:51200',
        ]);

        $user = Auth::user();

        $informantRoleMap = [
            'student' => 'Student',
            'faculty' => 'Faculty',
            'employee_staff' => 'Employee/Staff',
        ];

        $resolvedInformantRole = $informantRoleMap[$user->registrant_type ?? ''] ?? 'Student';

        $evidencePaths = [];
        if ($request->hasFile('evidence')) {
            foreach ($request->file('evidence') as $file) {
                $path = $file->store('evidences', 'public');
                $evidencePaths[] = $path;
            }
        }

        $additionalSheetPaths = [];
        if ($request->hasFile('incident_additional_sheets')) {
            foreach ($request->file('incident_additional_sheets') as $file) {
                $path = $file->store('incident-sheets', 'public');
                $additionalSheetPaths[] = $path;
            }
        }

        $selectedCategory = Category::find($validated['category_id']);
        $generatedTitle = 'LLCC Incident Report - ' . $validated['person_full_name'] . ' - ' . $validated['incident_date'];
        if ($selectedCategory) {
            $generatedTitle .= ' - ' . $selectedCategory->name;
        }

        $location = $validated['location'];
        if (!empty($validated['location_details'])) {
            $location .= ' - ' . $validated['location_details'];
        }

        $report = Report::create([
            'title'         => $generatedTitle,
            'category_id'   => $validated['category_id'],
            'description'   => $validated['description'],
            'incident_date' => $validated['incident_date'],
            'incident_time' => $validated['incident_time'],
            'location'      => $location,
            'evidence'      => !empty($evidencePaths) ? json_encode($evidencePaths) : null,
            'person_full_name'             => $validated['person_full_name'],
            'person_college_department'    => $validated['person_college_department'],
            'person_role'                  => $validated['person_role'],
            'person_contact_number'        => $validated['person_contact_number'] ?? null,
            'person_email_address'         => $validated['person_email_address'] ?? null,
            'person_has_multiple'          => (bool) $validated['person_has_multiple'],
            'additional_persons'           => !empty($validated['additional_persons']) ? $validated['additional_persons'] : null,
            'has_witnesses'                => (bool) $validated['has_witnesses'],
            'witness_attachment'           => null,
            'witness_details'              => !empty($validated['witness_details']) ? $validated['witness_details'] : null,
            'incident_additional_sheets'   => !empty($additionalSheetPaths) ? json_encode($additionalSheetPaths) : null,
            'informant_full_name'          => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
            'informant_college_department' => $user->department?->name ?? ($user->employee_office ?? 'N/A'),
            'informant_role'               => $resolvedInformantRole,
            'informant_contact_number'     => $validated['informant_contact_number'] ?? $user->phone ?? 'N/A',
            'informant_email_address'      => $user->email,
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
        $manager = Auth::user();

        $reportQuery = Report::with('category');
        if ($manager->is_top_management) {
            $reportQuery->whereHas('category', function ($query) {
                $query->whereIn('classification', ['Major', 'Grave']);
            });
        } else {
            $reportQuery->whereHas('user', function ($query) use ($manager) {
                $query->where('department_id', $manager->department_id);
            });
        }

        $report = $reportQuery->findOrFail($id);
        $oldStatus = $report->status;
        
        $report->status = 'Approved';
        $report->handled_by = $manager->id;
        $report->save();

        // ✅ Log admin action - use Auth::id() not full name
        Activity::create([
            'report_id'    => $report->id,
            'user_id'      => $report->user_id,      // ✅ Report owner
            'action'       => 'Report Approved',
            'performed_by' => $manager->id,
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

        $manager = Auth::user();

        $reportQuery = Report::with('category');
        if ($manager->is_top_management) {
            $reportQuery->whereHas('category', function ($query) {
                $query->whereIn('classification', ['Major', 'Grave']);
            });
        } else {
            $reportQuery->whereHas('user', function ($query) use ($manager) {
                $query->where('department_id', $manager->department_id);
            });
        }

        $report = $reportQuery->findOrFail($id);
        $oldStatus = $report->status;
        
        $report->status = 'Rejected';
        $report->rejection_reason = $request->rejection_reason;
        $report->handled_by = $manager->id;
        $report->save();

        // ✅ Log admin action
        Activity::create([
            'report_id'    => $report->id,
            'user_id'      => $report->user_id,      // ✅ Report owner
            'action'       => 'Report Rejected',
            'performed_by' => $manager->id,
            'old_status'   => $oldStatus,
            'new_status'   => 'Rejected',
            'remarks'      => 'Rejection reason: ' . $request->rejection_reason,
        ]);
        
        return redirect()->back()->with('success', 'Report rejected successfully.');
    }
}