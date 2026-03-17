<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Report;
use App\Models\Activity;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;
use App\Models\Department;
use App\Services\ReportRoutingService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

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

        $departments = Department::query()
            ->orderBy('name')
            ->pluck('name')
            ->values();

        return view('user.newreport', compact('categories', 'categoriesByMain', 'departments'));
    }

    /**
     * Store a new report.
     */
    public function store(Request $request, ReportRoutingService $routingService)
    {
        $allowedRoleValues = ['Student', 'Faculty', 'Employee/Staff'];
        $allowedEvidenceExtensions = 'jpg,jpeg,png,gif,webp,bmp,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,rar,7z,mp3,wav,m4a,aac,ogg,flac,mp4,mov,avi,mkv,wmv,webm';

        $hasMultiplePersons = filter_var($request->input('person_has_multiple'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true;
        if (!$hasMultiplePersons) {
            $request->merge(['additional_persons' => null]);
        }

        $validator = Validator::make($request->all(), [
            'main_category_code' => ['required', 'string', Rule::exists('categories', 'main_category_code')],
            'category_id' => ['required', 'integer', 'exists:categories,id'],

            'person_involvement' => ['required', Rule::in(['known', 'unknown', 'none', 'unsure'])],
            'person_full_name' => ['nullable', 'string', 'max:255'],
            'person_college_department' => ['nullable', 'string', 'max:255'],
            'person_role' => ['nullable', Rule::in($allowedRoleValues)],
            'person_contact_number' => 'nullable|string|max:50',
            'person_email_address' => 'nullable|email|max:255',
            'person_has_multiple' => 'nullable|boolean',
            'additional_persons' => 'nullable|array',
            'additional_persons.*.full_name' => 'nullable|string|max:255',
            'additional_persons.*.college_department' => 'nullable|string|max:255',
            'additional_persons.*.role' => ['nullable', Rule::in($allowedRoleValues)],
            'additional_persons.*.contact_number' => 'nullable|string|max:50',
            'additional_persons.*.email_address' => 'nullable|email|max:255',
            'unknown_person_details' => 'nullable|string|max:3000',
            'technical_facility_details' => 'nullable|string|max:3000',

            'description' => 'required|string',
            'incident_date' => 'required|date',
            'incident_time' => 'required',
            'location' => 'required|string|max:255',
            'location_details' => 'nullable|string|max:255',

            'has_witnesses' => 'required|boolean',
            'witness_details' => 'required_if:has_witnesses,1|array',
            'witness_details.*.name' => 'required_if:has_witnesses,1|string|max:255',
            'witness_details.*.address' => 'required_if:has_witnesses,1|string|max:255',
            'witness_details.*.contact_number' => 'required_if:has_witnesses,1|string|max:50',
            'incident_additional_sheets.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',

            'informant_contact_number' => 'nullable|string|max:50',
            'evidence' => 'required|array|min:1',
            'evidence.*' => 'required|file|mimes:' . $allowedEvidenceExtensions . '|max:51200',
        ]);

        $validator->after(function ($validator) use ($request, $allowedRoleValues) {
            $personInvolvement = (string) $request->input('person_involvement');
            $hasMultiple = filter_var($request->input('person_has_multiple'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($personInvolvement === 'known') {
                if (!$request->filled('person_full_name')) {
                    $validator->errors()->add('person_full_name', 'Full Name is required when person identity is known.');
                }
                if (!$request->filled('person_college_department')) {
                    $validator->errors()->add('person_college_department', 'College/Department is required when person identity is known.');
                }
                if (!$request->filled('person_role') || !in_array($request->input('person_role'), $allowedRoleValues, true)) {
                    $validator->errors()->add('person_role', 'A valid role is required when person identity is known.');
                }
                if ($hasMultiple === null) {
                    $validator->errors()->add('person_has_multiple', 'Please indicate if multiple persons are involved.');
                }
                if ($hasMultiple === true && empty($request->input('additional_persons', []))) {
                    $validator->errors()->add('additional_persons', 'Add at least one additional person when multiple persons are involved.');
                }

                if ($hasMultiple === true) {
                    foreach ((array) $request->input('additional_persons', []) as $index => $person) {
                        if (!is_array($person)) {
                            $validator->errors()->add("additional_persons.{$index}.full_name", 'Additional person entry is invalid.');
                            continue;
                        }

                        if (trim((string) ($person['full_name'] ?? '')) === '') {
                            $validator->errors()->add("additional_persons.{$index}.full_name", 'Full name is required for each additional person.');
                        }

                        if (trim((string) ($person['college_department'] ?? '')) === '') {
                            $validator->errors()->add("additional_persons.{$index}.college_department", 'College/Department is required for each additional person.');
                        }

                        if (!in_array((string) ($person['role'] ?? ''), $allowedRoleValues, true)) {
                            $validator->errors()->add("additional_persons.{$index}.role", 'A valid role is required for each additional person.');
                        }
                    }
                }
            }

            if ($personInvolvement === 'unknown' && !$request->filled('unknown_person_details')) {
                $validator->errors()->add('unknown_person_details', 'Unknown identity details are required when identity is unknown.');
            }

            $category = Category::find($request->input('category_id'));
            if ($category && (string) $category->main_category_code !== (string) $request->input('main_category_code')) {
                $validator->errors()->add('category_id', 'Selected category does not match the selected main category group.');
            }

            if ($category) {
                $mainName = strtolower((string) $category->main_category_name);
                $isTechnicalOrFacility = str_contains($mainName, 'technical') || str_contains($mainName, 'facility');
                if ($isTechnicalOrFacility && !$request->filled('technical_facility_details')) {
                    $validator->errors()->add('technical_facility_details', 'Technical/Facility details are required for this category.');
                }
            }
        });

        $validated = $validator->validate();

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

        $location = $validated['location'];
        if (!empty($validated['location_details'])) {
            $location .= ' - ' . $validated['location_details'];
        }

        $reportPayload = [
            'category_id'   => $validated['category_id'],
            'main_category_code' => $validated['main_category_code'],
            'description'   => $validated['description'],
            'incident_date' => $validated['incident_date'],
            'incident_time' => $validated['incident_time'],
            'location'      => $location,
            'evidence'      => !empty($evidencePaths) ? json_encode($evidencePaths) : null,
            'person_full_name'             => $validated['person_involvement'] === 'known' ? ($validated['person_full_name'] ?? null) : null,
            'person_college_department'    => $validated['person_involvement'] === 'known' ? ($validated['person_college_department'] ?? null) : null,
            'person_role'                  => $validated['person_involvement'] === 'known' ? ($validated['person_role'] ?? null) : null,
            'person_contact_number'        => $validated['person_contact_number'] ?? null,
            'person_email_address'         => $validated['person_email_address'] ?? null,
            'person_involvement'           => $validated['person_involvement'],
            'unknown_person_details'       => $validated['person_involvement'] === 'unknown' ? ($validated['unknown_person_details'] ?? null) : null,
            'technical_facility_details'   => $validated['technical_facility_details'] ?? null,
            'person_has_multiple'          => $validated['person_involvement'] === 'known' ? (bool) ($validated['person_has_multiple'] ?? false) : false,
            'additional_persons'           => ($validated['person_involvement'] === 'known' && !empty($validated['additional_persons'])) ? $validated['additional_persons'] : null,
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
            'status'        => Report::STATUS_PENDING,
            'user_id'       => Auth::id(),
        ];

        // Backward compatibility: some environments may still have a required reports.title column.
        if (Schema::hasColumn('reports', 'title')) {
            $reportPayload['title'] = 'LLCC Incident Report';
        }

        $report = Report::create($reportPayload);

        $routingService->autoAssign($report, 'submission', Auth::id(), false);

        // ❌ REMOVED - Don't log user submissions in activity logs
        // Activity logs should only track admin actions

        return redirect()->route('newreport')->with('success', '✅ Report submitted successfully!');
    }

    public function show(Request $request, $id) {
        $report = Report::with([
            'category',
            'responses.admin',
            'activities.admin',
        ])->findOrFail($id);

        $notifKey = trim((string) $request->query('notif_key', ''));
        $goto = trim((string) $request->query('goto', ''));

        if ($notifKey !== '' && Auth::check() && Auth::id() === (int) $report->user_id) {
            $isValidNotifKey = preg_match('/^(timeline|hearing)-\d+$/', $notifKey) === 1;
            $notifReportId = (int) substr($notifKey, strpos($notifKey, '-') + 1);

            if ($isValidNotifKey && $notifReportId === (int) $report->id) {
                $sessionKey = 'read_notifications_' . Auth::id();
                $readKeys = collect(session($sessionKey, []));

                // Mark both notification variants for the same report as read.
                $pairedKeys = collect([
                    'timeline-' . $report->id,
                    'hearing-' . $report->id,
                ]);

                $mergedKeys = $readKeys
                    ->merge($pairedKeys)
                    ->map(fn ($key) => (string) $key)
                    ->unique()
                    ->values()
                    ->all();

                session([$sessionKey => $mergedKeys]);
            }

            $allowedAnchors = ['admin-response-timeline', 'case-records'];
            $anchor = in_array($goto, $allowedAnchors, true) ? ('#' . $goto) : '';

            return redirect()->to(route('report.show', $report->id) . $anchor);
        }

        return view('user.reportshow', compact('report'));
    }

    public function approve($id) {
        $manager = Auth::user();

        $reportQuery = Report::with('category');
        if ($manager->is_top_management) {
            $positionCode = trim((string) ($manager->routing_position_code ?? ''));
            $fullName = strtolower(trim((string) (($manager->first_name ?? '') . ' ' . ($manager->last_name ?? ''))));

            $reportQuery->where(function ($query) use ($positionCode, $fullName) {
                if ($positionCode !== '') {
                    $query->where('assigned_position_code', $positionCode);
                }

                if ($fullName !== '') {
                    if ($positionCode !== '') {
                        $query->orWhereRaw('LOWER(assigned_to) = ?', [$fullName]);
                    } else {
                        $query->whereRaw('LOWER(assigned_to) = ?', [$fullName]);
                    }
                }

                if ($positionCode === '' && $fullName === '') {
                    $query->whereRaw('1 = 0');
                }
            });
        } else {
            $reportQuery->whereHas('user', function ($query) use ($manager) {
                $query->where('department_id', $manager->department_id);
            });
        }

        $report = $reportQuery->findOrFail($id);
        Gate::authorize('decide', $report);
        $oldStatus = $report->status;

        if (!Report::canTransition($oldStatus, Report::STATUS_APPROVED)) {
            return redirect()->back()->with('error', 'Only pending reports can be approved.');
        }
        
        $report->status = Report::STATUS_APPROVED;
        $report->handled_by = $manager->id;
        $report->save();

        // ✅ Log admin action - use Auth::id() not full name
        Activity::create([
            'report_id'    => $report->id,
            'user_id'      => $report->user_id,      // ✅ Report owner
            'action'       => 'Report Approved',
            'performed_by' => $manager->id,
            'old_status'   => $oldStatus,
            'new_status'   => Report::STATUS_APPROVED,
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
            $positionCode = trim((string) ($manager->routing_position_code ?? ''));
            $fullName = strtolower(trim((string) (($manager->first_name ?? '') . ' ' . ($manager->last_name ?? ''))));

            $reportQuery->where(function ($query) use ($positionCode, $fullName) {
                if ($positionCode !== '') {
                    $query->where('assigned_position_code', $positionCode);
                }

                if ($fullName !== '') {
                    if ($positionCode !== '') {
                        $query->orWhereRaw('LOWER(assigned_to) = ?', [$fullName]);
                    } else {
                        $query->whereRaw('LOWER(assigned_to) = ?', [$fullName]);
                    }
                }

                if ($positionCode === '' && $fullName === '') {
                    $query->whereRaw('1 = 0');
                }
            });
        } else {
            $reportQuery->whereHas('user', function ($query) use ($manager) {
                $query->where('department_id', $manager->department_id);
            });
        }

        $report = $reportQuery->findOrFail($id);
        Gate::authorize('decide', $report);
        $oldStatus = $report->status;

        if (!Report::canTransition($oldStatus, Report::STATUS_REJECTED)) {
            return redirect()->back()->with('error', 'Only pending reports can be rejected.');
        }
        
        $report->status = Report::STATUS_REJECTED;
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
            'new_status'   => Report::STATUS_REJECTED,
            'remarks'      => 'Rejection reason: ' . $request->rejection_reason,
        ]);
        
        return redirect()->back()->with('success', 'Report rejected successfully.');
    }
}