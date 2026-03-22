@extends('layouts.admin')

@section('title', 'Handle Report ' . $report->id . ' - Sincidentre Department Student Discipline Officer')

@section('page-title', 'Handle Report ' . $report->id)

@section('content')
    @php
        $currentStatus = \App\Models\Report::normalizeStatus($report->status);
        $personInvolvement = strtolower((string) ($report->person_involvement ?? ''));
        $hasPersonInvolvement = in_array($personInvolvement, ['known', 'unknown', 'unsure'], true);
        $isNoPersonInvolved = $personInvolvement === 'none';
        $isTopManagementUser = (bool) (Auth::user()->is_top_management ?? false);
        $step1Done = (bool) ($report->hearing_date && $report->hearing_time && $report->hearing_venue);
        $step2Done = (bool) $report->reprimand_issued_at;
        $step3Done = (bool) $report->suspension_issued_at;
        $step1Errors = $errors->hasAny(['hearing_date', 'hearing_time', 'hearing_venue', 'step1_remarks', 'step1_target_date', 'step1_attachment']);
        $step2Errors = $errors->hasAny(['step2_remarks', 'step2_target_date', 'step2_status', 'step2_attachment']);
        $step3Errors = $errors->hasAny(['disciplinary_action', 'suspension_days', 'suspension_effective_date', 'step3_remarks', 'step3_target_date', 'step3_status', 'step3_attachment']);
        $step4Errors = $errors->hasAny(['escalation_target_user_id', 'escalation_note', 'step4_remarks', 'step4_target_date', 'step4_attachment']);
        $generalResponseErrors = $errors->hasAny(['department_id', 'target_date', 'remarks', 'status', 'assigned_to_user_id']);
        $showStep1Panel = !$step1Done || $step1Errors;
        $showEscalationPanel = $step4Errors;
        $showGeneralResponsePanel = $generalResponseErrors;
        $handlerDepartmentId = old('department_id', optional(Auth::user())->department_id);
        $handlerDepartmentLabel = optional(optional(Auth::user())->department)->name
            ?? (optional(Auth::user())->employee_office ?? 'N/A');
        $resolvedResponses = collect($responses ?? [])->filter(function ($response) {
            return \App\Models\Report::normalizeStatus($response->status ?? null) === \App\Models\Report::STATUS_RESOLVED;
        });
        $latestResolvedResponse = $resolvedResponses->sortByDesc('response_number')->first();
        $resolutionType = 'Resolution recorded in handling response';
        if ($report->suspension_issued_at || $report->suspension_document_path) {
            $resolutionType = 'Resolved via Form 2305 (Suspension/Dismissal)';
        } elseif ($report->reprimand_issued_at || $report->reprimand_document_path) {
            $resolutionType = 'Resolved via Form 2304 (Written Reprimand)';
        }
        $latestResponse = collect($responses ?? [])->sortByDesc('response_number')->first();
        $workflowStage = 'Initial handling review';
        if ($report->suspension_issued_at) {
            $workflowStage = 'Final action recorded (Form 2305)';
        } elseif ($report->reprimand_issued_at) {
            $workflowStage = 'Written reprimand recorded (Form 2304)';
        } elseif ($step1Done) {
            $workflowStage = 'Hearing scheduled / case under review';
        } elseif ($currentStatus === \App\Models\Report::STATUS_APPROVED) {
            $workflowStage = 'Approved and awaiting hearing schedule';
        }
        $nextRecommendedAction = 'Open relevant handling form and continue case updates.';
        if (!$step1Done) {
            $nextRecommendedAction = 'Schedule hearing (2303) or escalate/reassign immediately if needed.';
        } elseif (!$isTopManagementUser && !$step2Done) {
            $nextRecommendedAction = 'Record written reprimand (2304) or add additional handling response.';
        } elseif ($isTopManagementUser && !$step3Done) {
            $nextRecommendedAction = 'Record final disciplinary action via Form 2305 or add additional handling response.';
        } elseif ($currentStatus === \App\Models\Report::STATUS_RESOLVED) {
            $nextRecommendedAction = 'Case resolved. Review resolution details and response history.';
        }
        $availableActionsLabel = $isTopManagementUser
            ? '2303, Additional Handling Response, 2305'
            : '2303, Additional Handling Response, 2304, Escalation/Reassignment';
        $reporterConfirmed = \App\Models\Activity::where('report_id', $report->id)
            ->where('action', 'Reporter Hearing Notice Confirmed')
            ->exists();
        $involvedConfirmed = \App\Models\Activity::where('report_id', $report->id)
            ->where('action', 'Involved Party Hearing Notice Confirmed')
            ->exists();
    @endphp

    <p>Review the report and add progressive handling responses below.</p>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- ================================================================
         WORKFLOW SUMMARY
         ================================================================ --}}
    <section class="handle-section workflow-summary">
        <h2 class="handle-title-centered">Handling Workflow Summary</h2>
        <div class="info-stack">
            <div class="info-row">
                <span class="info-label">Current Stage</span>
                <span class="info-value">{{ $workflowStage }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Last Action</span>
                <span class="info-value">
                    @if($latestResponse)
                        {{ $latestResponse->response_type ?? 'Handling Response' }}
                        @if($latestResponse->created_at)
                            &middot; {{ $latestResponse->created_at->format('M d, Y h:i A') }}
                        @endif
                    @else
                        No handling response recorded yet.
                    @endif
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Next Action</span>
                <span class="info-value">{{ $nextRecommendedAction }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Available Actions</span>
                <span class="info-value">{{ $availableActionsLabel }}</span>
            </div>
        </div>
    </section>

    {{-- ================================================================
         RESOLUTION DETAILS
         ================================================================ --}}
    @if($currentStatus === \App\Models\Report::STATUS_RESOLVED)
        <section class="handle-section">
            <h2 class="handle-title-centered">Resolution Details</h2>
            <div class="info-stack">
                <div class="info-row">
                    <span class="info-label">Resolution Type</span>
                    <span class="info-value">{{ $resolutionType }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Resolved At</span>
                    <span class="info-value">
                        @if($report->suspension_issued_at)
                            {{ $report->suspension_issued_at->format('F d, Y h:i A') }}
                        @elseif($report->reprimand_issued_at)
                            {{ $report->reprimand_issued_at->format('F d, Y h:i A') }}
                        @elseif($latestResolvedResponse?->created_at)
                            {{ $latestResolvedResponse->created_at->format('F d, Y h:i A') }}
                        @else
                            N/A
                        @endif
                    </span>
                </div>
                @if(!empty($report->disciplinary_action))
                <div class="info-row">
                    <span class="info-label">Disciplinary Action</span>
                    <span class="info-value">{{ $report->disciplinary_action }}</span>
                </div>
                @endif
                @if(!empty($report->suspension_days))
                <div class="info-row">
                    <span class="info-label">Suspension Days</span>
                    <span class="info-value">{{ $report->suspension_days }}</span>
                </div>
                @endif
                @if($report->suspension_effective_date)
                <div class="info-row">
                    <span class="info-label">Effective Date</span>
                    <span class="info-value">{{ $report->suspension_effective_date->format('F d, Y') }}</span>
                </div>
                @endif
                @if(!empty($latestResolvedResponse?->remarks))
                <div class="info-row">
                    <span class="info-label">Remarks</span>
                    <span class="info-value">{{ $latestResolvedResponse->remarks }}</span>
                </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ================================================================
         SUBMITTED REPORT DETAILS — Collapsible Groups (matches user side)
         ================================================================ --}}
    <section id="report-details" class="handle-section">
        <h2 class="handle-title-centered">Report Information (Form 2302 View)</h2>

        {{-- ── GROUP 1: Incident Overview (open by default) ── --}}
        <div class="detail-group">
            <button class="detail-group-toggle" aria-expanded="true" aria-controls="adm-grp-overview-body">
                <span class="detail-group-icon">📋</span>
                <span class="detail-group-label">Incident Overview</span>
                <span class="detail-group-chevron">▾</span>
            </button>
            <div class="detail-group-body" id="adm-grp-overview-body">
                <div class="info-stack">
                    <div class="info-row">
                        <span class="info-label">Report ID</span>
                        <span class="info-value">#{{ $report->id }}</span>
                    </div>
                    @if($report->category)
                    <div class="info-row">
                        <span class="info-label">Category</span>
                        <span class="info-value">{{ strtoupper($report->category->main_category_code) }} - {{ $report->category->main_category_name }} / {{ $report->category->name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Classification</span>
                        <span class="info-value">
                            <span class="classification-badge {{ strtolower($report->category->classification ?? '') }}">
                                {{ $report->category->classification ?? 'N/A' }}
                            </span>
                        </span>
                    </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="info-value">
                            <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                                {{ \App\Models\Report::labelForStatus($report->status) }}
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date Submitted</span>
                        <span class="info-value">{{ $report->submitted_at ? $report->submitted_at->format('F d, Y h:i A') : $report->created_at->format('F d, Y h:i A') }}</span>
                    </div>
                    @if($report->person_involvement)
                    <div class="info-row">
                        <span class="info-label">Person Involvement</span>
                        <span class="info-value">{{ ucfirst($report->person_involvement) }}</span>
                    </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label">Reporter Account</span>
                        <span class="info-value">{{ $report->user->name ?? 'Unknown' }} ({{ $report->user->email ?? 'N/A' }})</span>
                    </div>
                    @if($report->description)
                    <div class="info-row info-row-block">
                        <span class="info-label">Description</span>
                        <span class="info-value">{{ $report->description }}</span>
                    </div>
                    @endif
                    @if($report->incident_date)
                    <div class="info-row">
                        <span class="info-label">Incident Date</span>
                        <span class="info-value">{{ $report->incident_date->format('F d, Y') }}</span>
                    </div>
                    @endif
                    @if($report->incident_time)
                    <div class="info-row">
                        <span class="info-label">Incident Time</span>
                        <span class="info-value">{{ \Carbon\Carbon::parse($report->incident_time)->format('h:i A') }}</span>
                    </div>
                    @endif
                    @if($report->location)
                    <div class="info-row">
                        <span class="info-label">Location</span>
                        <span class="info-value">{{ $report->location }}</span>
                    </div>
                    @endif
                    @if($report->location_details)
                    <div class="info-row">
                        <span class="info-label">Please Specify</span>
                        <span class="info-value">{{ $report->location_details }}</span>
                    </div>
                    @endif
                    @php
                        $sheetFiles = [];
                        if ($report->incident_additional_sheets) {
                            $decoded = is_array($report->incident_additional_sheets)
                                ? $report->incident_additional_sheets
                                : json_decode((string) $report->incident_additional_sheets, true);
                            $sheetFiles = is_array($decoded) ? $decoded : [];
                        }
                    @endphp
                    @if(count($sheetFiles) > 0)
                    <div class="info-row info-row-block">
                        <span class="info-label">Additional Sheets</span>
                        <ul class="info-list">
                            @foreach($sheetFiles as $si => $sf)
                                <li><a href="{{ asset('storage/' . $sf) }}" target="_blank" class="info-link">📄 View Sheet {{ $si + 1 }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── GROUP 2: Person/s Involved (collapsed by default) ── --}}
        @php
            $hasPersonData = $report->person_full_name
                || $report->person_college_department
                || $report->person_role
                || $report->person_contact_number
                || $report->person_email_address
                || $report->unknown_person_details
                || $report->technical_facility_details
                || (is_array($report->additional_persons) && count($report->additional_persons) > 0);
        @endphp
        @if($hasPersonData)
        <div class="detail-group">
            <button class="detail-group-toggle" aria-expanded="false" aria-controls="adm-grp-persons-body">
                <span class="detail-group-icon">👤</span>
                <span class="detail-group-label">Person/s Involved</span>
                <span class="detail-group-chevron">▾</span>
            </button>
            <div class="detail-group-body collapsed" id="adm-grp-persons-body">
                <div class="info-stack">
                    @if($report->person_full_name)
                    <div class="info-row"><span class="info-label">Name</span><span class="info-value">{{ $report->person_full_name }}</span></div>
                    @endif
                    @if($report->person_college_department)
                    <div class="info-row"><span class="info-label">College / Dept</span><span class="info-value">{{ $report->person_college_department }}</span></div>
                    @endif
                    @if($report->person_role)
                    <div class="info-row"><span class="info-label">Role</span><span class="info-value">{{ $report->person_role }}</span></div>
                    @endif
                    @if($report->person_contact_number)
                    <div class="info-row"><span class="info-label">Contact</span><span class="info-value">{{ $report->person_contact_number }}</span></div>
                    @endif
                    @if($report->person_email_address)
                    <div class="info-row"><span class="info-label">Email</span><span class="info-value">{{ $report->person_email_address }}</span></div>
                    @endif
                    <div class="info-row"><span class="info-label">Multiple Persons?</span><span class="info-value">{{ $report->person_has_multiple ? 'Yes' : 'No' }}</span></div>
                    @if($report->unknown_person_details)
                    <div class="info-row info-row-block"><span class="info-label">Unknown Person</span><span class="info-value">{{ $report->unknown_person_details }}</span></div>
                    @endif
                    @if($report->technical_facility_details)
                    <div class="info-row info-row-block"><span class="info-label">Tech / Facility</span><span class="info-value">{{ $report->technical_facility_details }}</span></div>
                    @endif
                </div>

                @if(is_array($report->additional_persons) && count($report->additional_persons) > 0)
                    <div class="adm-subsection-label">Additional Involved Persons</div>
                    <div class="desktop-only-block" style="padding: 0 1.5rem 1rem;">
                        <table class="handle-report-table">
                            <thead><tr><th>#</th><th>Name</th><th>College/Dept</th><th>ID/Role</th><th>Email</th></tr></thead>
                            <tbody>
                                @foreach($report->additional_persons as $index => $person)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $person['full_name'] ?? ($person['name'] ?? 'N/A') }}</td>
                                    <td>{{ $person['college_department'] ?? 'N/A' }}</td>
                                    <td>{{ $person['role'] ?? ($person['id_number'] ?? 'N/A') }}</td>
                                    <td>{{ $person['email_address'] ?? 'N/A' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mobile-only-block" style="padding: 0 1rem 1rem;">
                        @foreach($report->additional_persons as $index => $person)
                        <div class="mobile-person-card">
                            <div class="mpc-head">Person {{ $index + 2 }}</div>
                            <div class="mpc-body">
                                @if(!empty($person['full_name'] ?? $person['name'] ?? null))<div><span>Name:</span> {{ $person['full_name'] ?? $person['name'] }}</div>@endif
                                @if(!empty($person['college_department'] ?? null))<div><span>Dept:</span> {{ $person['college_department'] }}</div>@endif
                                @if(!empty($person['role'] ?? $person['id_number'] ?? null))<div><span>Role/ID:</span> {{ $person['role'] ?? $person['id_number'] }}</div>@endif
                                @if(!empty($person['email_address'] ?? null))<div><span>Email:</span> {{ $person['email_address'] }}</div>@endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        @endif

        {{-- ── GROUP 3: Witnesses (collapsed by default) ── --}}
        @if($report->has_witnesses || (is_array($report->witness_details) && count($report->witness_details) > 0))
        <div class="detail-group">
            <button class="detail-group-toggle" aria-expanded="false" aria-controls="adm-grp-witnesses-body">
                <span class="detail-group-icon">👁️</span>
                <span class="detail-group-label">Witnesses</span>
                <span class="detail-group-chevron">▾</span>
            </button>
            <div class="detail-group-body collapsed" id="adm-grp-witnesses-body">
                <div class="info-stack">
                    <div class="info-row"><span class="info-label">Were There Witnesses?</span><span class="info-value">{{ $report->has_witnesses ? 'Yes' : 'No' }}</span></div>
                    @if($report->has_witnesses && is_array($report->witness_details) && count($report->witness_details) > 0)
                    <div class="info-row info-row-block">
                        <span class="info-label">Witness Details</span>
                        <ul class="info-list">
                            @foreach($report->witness_details as $witness)
                                <li>
                                    {{ $witness['name'] ?? ($witness['full_name'] ?? 'Unnamed Witness') }}
                                    @if(!empty($witness['address'])) — {{ $witness['address'] }} @endif
                                    @if(!empty($witness['contact_number'])) | {{ $witness['contact_number'] }} @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    @if($report->has_witnesses && $report->witness_attachment)
                    <div class="info-row">
                        <span class="info-label">Witness Attachment</span>
                        <span class="info-value"><a href="{{ asset('storage/' . $report->witness_attachment) }}" target="_blank" class="info-link">View Attachment</a></span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- ── GROUP 4: Informant (collapsed by default) ── --}}
        @if($report->informant_full_name || $report->informant_email_address)
        <div class="detail-group">
            <button class="detail-group-toggle" aria-expanded="false" aria-controls="adm-grp-informant-body">
                <span class="detail-group-icon">📝</span>
                <span class="detail-group-label">Informant</span>
                <span class="detail-group-chevron">▾</span>
            </button>
            <div class="detail-group-body collapsed" id="adm-grp-informant-body">
                <div class="info-stack">
                    @if($report->informant_full_name)
                    <div class="info-row"><span class="info-label">Full Name</span><span class="info-value">{{ $report->informant_full_name }}</span></div>
                    @endif
                    @if($report->informant_college_department)
                    <div class="info-row"><span class="info-label">College / Dept</span><span class="info-value">{{ $report->informant_college_department }}</span></div>
                    @endif
                    @if($report->informant_role)
                    <div class="info-row"><span class="info-label">Role</span><span class="info-value">{{ $report->informant_role }}</span></div>
                    @endif
                    @if($report->informant_contact_number)
                    <div class="info-row"><span class="info-label">Contact</span><span class="info-value">{{ $report->informant_contact_number }}</span></div>
                    @endif
                    @if($report->informant_email_address)
                    <div class="info-row"><span class="info-label">Email</span><span class="info-value">{{ $report->informant_email_address }}</span></div>
                    @endif
                </div>
            </div>
        </div>
        @endif

    </section>


    {{-- ================================================================
         EVIDENCE
         ================================================================ --}}
    @if($report->evidence)
        @php $evidences = json_decode($report->evidence, true); @endphp
        @if(is_array($evidences) && count($evidences) > 0)
        <section id="evidence-section" class="handle-section">
            <h2>Submitted Evidence</h2>
            <div class="evidence-grid">
                @foreach($evidences as $file)
                    @php
                        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        $fileName  = basename($file);
                        $isImage   = in_array($extension, ['jpg','jpeg','png','gif','webp']);
                    @endphp
                    <div class="evidence-item">
                        @if($isImage)
                            <a href="{{ asset('storage/' . $file) }}" target="_blank" rel="noopener noreferrer">
                                <img src="{{ asset('storage/' . $file) }}" alt="Evidence Image" class="handle-evidence-image">
                            </a>
                        @else
                            <div class="evidence-file-icon">📁</div>
                            <p class="evidence-file-name">{{ $fileName }}</p>
                        @endif
                        <div class="evidence-actions">
                            <a href="{{ asset('storage/' . $file) }}" target="_blank" rel="noopener noreferrer" class="btn-secondary evidence-btn">View</a>
                            <a href="{{ asset('storage/' . $file) }}" download="{{ $fileName }}" class="btn-secondary evidence-btn">Download</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
        @endif
    @endif

    {{-- ================================================================
         HANDLING FORMS
         ================================================================ --}}
    <section id="handling-forms" class="handle-section">
        <h2 class="handle-title-centered">Handling Forms</h2>

        @if(!$hasPersonInvolvement && !$isNoPersonInvolved)
            <div class="alert alert-warning">
                Forms 2303, 2304, and 2305 are only available when person involvement is <strong>Known</strong>, <strong>Unknown</strong>, or <strong>Not sure yet</strong>.
            </div>
        @endif

        @if($hasPersonInvolvement)

        {{-- ── Form Selector ── --}}
        <div class="handle-card handle-card-spaced">
            <div class="hcard-header">
                <h3 class="hcard-title">Select Handling Form</h3>
            </div>
            <div class="hform-grid" style="margin-bottom: 0;">
                <div class="form-group">
                    <label><strong>Choose Form to Use</strong></label>
                    <select id="formSelector" onchange="showSelectedForm(this.value)">
                        <option value="">-- Select a form --</option>
                        <option value="form2303">Form 2303: Schedule Hearing / Call Slip</option>
                        <option value="additionalResponse">Additional Handling Response</option>
                        @if(!$isTopManagementUser)
                        <option value="form2304">Form 2304: Written Reprimand</option>
                        @endif
                        @if($isTopManagementUser)
                        <option value="form2305">Form 2305: Suspension or Dismissal</option>
                        @endif
                    </select>
                </div>
            </div>
        </div>

        {{-- ── 2303: Schedule Hearing ── --}}
        <div id="form2303Panel" class="handle-card handle-card-spaced" style="display: none;">
            <div class="hcard-header">
                <h3 class="hcard-title">Form 2303: Schedule Hearing and Notify Parties</h3>
                @if($step1Done)
                <span class="status-badge status-resolved" style="font-size: 0.8rem; padding: 0.3rem 0.6rem;">Issued</span>
                @endif
            </div>

            @if($step1Done)
            <div class="alert alert-info" style="margin-bottom: 1rem;">
                <strong>Hearing Already Scheduled:</strong>
                {{ optional($report->hearing_date)->format('F d, Y') }} at
                {{ $report->hearing_time ? \Illuminate\Support\Carbon::parse($report->hearing_time)->format('h:i A') : 'N/A' }}
                ({{ $report->hearing_venue ?? 'No venue specified' }})
                <br><small>You may reschedule by updating the details below.</small>
            </div>
            @endif

            <form action="{{ route('admin.handlereports.schedule-hearing', $report->id) }}" method="POST" enctype="multipart/form-data" class="hform">
                @csrf
                <div class="hform-grid">
                    <div class="form-group">
                        <label><strong>Hearing Date</strong></label>
                        <input type="date" name="hearing_date" value="{{ old('hearing_date', optional($report->hearing_date)->format('Y-m-d')) }}" required>
                    </div>
                    <div class="form-group">
                        <label><strong>Hearing Time</strong></label>
                        <input type="time" name="hearing_time" step="300" class="field-time field-time-enhanced"
                            value="{{ old('hearing_time', $report->hearing_time ? \Illuminate\Support\Carbon::parse($report->hearing_time)->format('H:i') : '') }}" required>
                        <small class="handle-muted-note">24-hour format. E.g. 13:30 for 1:30 PM.</small>
                    </div>
                    <div class="form-group">
                        <label><strong>Venue</strong></label>
                        <input type="text" name="hearing_venue" value="{{ old('hearing_venue', $report->hearing_venue) }}" placeholder="Office / room" required>
                    </div>
                    <div class="form-group">
                        <label><strong>Status</strong></label>
                        <input type="text" value="Under Review" class="readonly-field" readonly>
                    </div>
                    <div class="form-group">
                        <label><strong>Attachment (Optional)</strong></label>
                        <input type="file" name="step1_attachment" class="file-input-enhanced" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
                    </div>
                    <div class="form-group hform-full">
                        <label><strong>Handling Response Remarks</strong></label>
                        <textarea name="step1_remarks" rows="3" placeholder="Visible to reporter/person involved" required>{{ old('step1_remarks') }}</textarea>
                    </div>
                </div>
                <div class="hform-actions">
                    <button type="submit">{{ $step1Done ? 'Reschedule Hearing' : 'Save Hearing Schedule' }}</button>
                    <a href="{{ route('admin.handlereports.print-call-slip', $report->id) }}" class="btn-secondary">Download Call Slip (.docx)</a>
                </div>
            </form>

            <div class="hcard-meta">
                <span><strong>Last notified:</strong> {{ $report->respondent_notified_at ? $report->respondent_notified_at->format('M d, Y h:i A') : 'Not yet notified' }}</span>
                <span><strong>Reporter:</strong> {{ $reporterConfirmed ? '✅ Confirmed' : '⏳ Pending' }}</span>
                <span><strong>Involved party:</strong> {{ $involvedConfirmed ? '✅ Confirmed' : '⏳ Pending' }}</span>
            </div>
        </div>

        {{-- ── Additional Handling Response ── --}}
        <div id="additionalResponsePanel" class="handle-card handle-card-spaced" style="display: none;">
            <div class="hcard-header">
                <h3 class="hcard-title">Additional Handling Response</h3>
            </div>
            <p class="hcard-desc">Add a progress update without proceeding to Form 2304/2305 yet.</p>

            <form action="{{ route('admin.handlereports.update', $report->id) }}" method="POST" enctype="multipart/form-data" class="hform">
                @csrf
                @method('PUT')
                <input type="hidden" name="department_id" value="{{ $handlerDepartmentId }}">
                <div class="hform-grid">
                    <div class="form-group">
                        <label><strong>Department</strong></label>
                        <input type="text" value="{{ $handlerDepartmentLabel }}" class="readonly-field" readonly>
                    </div>
                    <div class="form-group">
                        <label><strong>Target Date</strong></label>
                        <input type="date" name="target_date" value="{{ old('target_date') }}" required>
                    </div>
                    <div class="form-group">
                        <label><strong>Status</strong></label>
                        <select name="status" required>
                            <option value="{{ \App\Models\Report::STATUS_UNDER_REVIEW }}" {{ old('status', \App\Models\Report::STATUS_UNDER_REVIEW) === \App\Models\Report::STATUS_UNDER_REVIEW ? 'selected' : '' }}>Under Review</option>
                            @if($canResolve)
                            <option value="{{ \App\Models\Report::STATUS_RESOLVED }}" {{ old('status') === \App\Models\Report::STATUS_RESOLVED ? 'selected' : '' }}>Resolved</option>
                            @endif
                        </select>
                    </div>
                    <div class="form-group">
                        <label><strong>Attachment (Optional)</strong></label>
                        <input type="file" name="attachment" class="file-input-enhanced" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
                    </div>
                    @if($isTopManagementUser)
                    <div class="form-group hform-full">
                        <label><strong>Assign To (Optional)</strong></label>
                        <select name="assigned_to_user_id">
                            <option value="">Keep current assignment</option>
                            @foreach($handlerUsers as $handlerUser)
                                @php $handlerName = trim((string)(($handlerUser->first_name ?? '') . ' ' . ($handlerUser->last_name ?? ''))); @endphp
                                <option value="{{ $handlerUser->id }}" {{ (string) old('assigned_to_user_id') === (string) $handlerUser->id ? 'selected' : '' }}>
                                    {{ $handlerName !== '' ? $handlerName : ($handlerUser->name ?? 'User #' . $handlerUser->id) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="form-group hform-full">
                        <label><strong>Handling Response Remarks</strong></label>
                        <textarea name="remarks" rows="3" placeholder="Add progress update or follow-up actions" required>{{ old('remarks') }}</textarea>
                    </div>
                </div>
                <div class="hform-actions">
                    <button type="submit">Record Additional Handling Response</button>
                </div>
            </form>
        </div>

        {{-- ── Form 2304: Written Reprimand (DSDO only) ── --}}
        @if(!$isTopManagementUser)
        <div id="form2304Panel" class="handle-card handle-card-spaced" style="display: none;">
            <div class="hcard-header">
                <h3 class="hcard-title">Form 2304: Written Reprimand</h3>
                @if($step2Done)
                <span class="status-badge status-resolved" style="font-size: 0.8rem; padding: 0.3rem 0.6rem;">Issued</span>
                @endif
            </div>

            @if($step2Done)
            <div class="alert alert-info" style="margin-bottom: 1rem;">
                <strong>Written Reprimand Already Issued:</strong>
                {{ $report->reprimand_issued_at ? $report->reprimand_issued_at->format('F d, Y h:i A') : 'Date not recorded' }}
                <br><small>You may issue another reprimand if needed.</small>
            </div>
            @endif

            <form id="issueReprimandForm" action="{{ route('admin.handlereports.issue-reprimand', $report->id) }}" method="POST" enctype="multipart/form-data" class="hform">
                @csrf
                <div class="hform-grid">
                    <div class="form-group">
                        <label><strong>Status</strong></label>
                        <select name="step2_status" required>
                            <option value="{{ \App\Models\Report::STATUS_UNDER_REVIEW }}" {{ old('step2_status', \App\Models\Report::STATUS_UNDER_REVIEW) === \App\Models\Report::STATUS_UNDER_REVIEW ? 'selected' : '' }}>Under Review</option>
                            @if($canResolve)
                            <option value="{{ \App\Models\Report::STATUS_RESOLVED }}" {{ old('step2_status') === \App\Models\Report::STATUS_RESOLVED ? 'selected' : '' }}>Resolved</option>
                            @endif
                        </select>
                    </div>
                    <div class="form-group">
                        <label><strong>Target Date (Optional)</strong></label>
                        <input type="date" name="step2_target_date" value="{{ old('step2_target_date') }}">
                    </div>
                    <div class="form-group">
                        <label><strong>Attachment (Optional)</strong></label>
                        <input type="file" name="step2_attachment" class="file-input-enhanced" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
                    </div>
                    <div class="form-group hform-full">
                        <label><strong>Handling Response Remarks</strong></label>
                        <textarea name="step2_remarks" rows="3" placeholder="Visible to reporter/person involved" required>{{ old('step2_remarks') }}</textarea>
                    </div>
                </div>
                <div class="hform-actions">
                    <button type="button" onclick="openConfirmActionModal('issueReprimandForm', 'Confirm before proceeding: Form 2304 should already be printed, properly filled out, and ready to be passed to the student/person who will receive the written reprimand. Continue recording this action?')">
                        Record Written Reprimand and Notify Reporter
                    </button>
                    <a href="{{ route('admin.handlereports.print-reprimand', $report->id) }}" class="btn-secondary">Download Written Reprimand (.docx)</a>
                </div>
            </form>
            <div class="hcard-meta">
                <span><strong>Student acknowledgment:</strong> {{ $report->student_acknowledged_reprimand_at ? $report->student_acknowledged_reprimand_at->format('M d, Y h:i A') : 'Pending acknowledgment' }}</span>
            </div>
        </div>
        @endif

        {{-- ── Form 2305: Suspension/Dismissal (Top Management only) ── --}}
        @if($isTopManagementUser)
        <div id="form2305Panel" class="handle-card handle-card-spaced" style="display: none;">
            <div class="hcard-header">
                <h3 class="hcard-title">Form 2305: Suspension or Dismissal</h3>
                @if($step3Done)
                <span class="status-badge status-resolved" style="font-size: 0.8rem; padding: 0.3rem 0.6rem;">Issued</span>
                @endif
            </div>

            @if($step3Done)
            <div class="alert alert-info" style="margin-bottom: 1rem;">
                <strong>Suspension/Dismissal Already Issued:</strong>
                {{ $report->suspension_issued_at ? $report->suspension_issued_at->format('F d, Y h:i A') : 'Date not recorded' }}
                ({{ $report->disciplinary_action ?? 'Action type not specified' }})
                <br><small>You may issue another action if needed.</small>
            </div>
            @endif

            <form id="issueSuspensionForm" action="{{ route('admin.handlereports.issue-suspension', $report->id) }}" method="POST" enctype="multipart/form-data" class="hform">
                @csrf
                <div class="hform-grid">
                    <div class="form-group">
                        <label><strong>Disciplinary Action</strong></label>
                        <select name="disciplinary_action" required>
                            <option value="Suspension" {{ old('disciplinary_action', $report->disciplinary_action) === 'Suspension' ? 'selected' : '' }}>Suspension</option>
                            <option value="Dismissal"  {{ old('disciplinary_action', $report->disciplinary_action) === 'Dismissal'  ? 'selected' : '' }}>Dismissal</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><strong>Suspension Days</strong></label>
                        <input type="number" name="suspension_days" min="1" max="365"
                            value="{{ old('suspension_days', $report->suspension_days) }}" placeholder="Required for Suspension">
                    </div>
                    <div class="form-group">
                        <label><strong>Effective Date</strong></label>
                        <input type="date" name="suspension_effective_date"
                            value="{{ old('suspension_effective_date', optional($report->suspension_effective_date)->format('Y-m-d')) }}" required>
                    </div>
                    <div class="form-group">
                        <label><strong>Status</strong></label>
                        <select name="step3_status" required>
                            <option value="{{ \App\Models\Report::STATUS_UNDER_REVIEW }}" {{ old('step3_status', \App\Models\Report::STATUS_UNDER_REVIEW) === \App\Models\Report::STATUS_UNDER_REVIEW ? 'selected' : '' }}>Under Review</option>
                            <option value="{{ \App\Models\Report::STATUS_RESOLVED }}"     {{ old('step3_status') === \App\Models\Report::STATUS_RESOLVED     ? 'selected' : '' }}>Resolved</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><strong>Target Date (Optional)</strong></label>
                        <input type="date" name="step3_target_date" value="{{ old('step3_target_date') }}">
                    </div>
                    <div class="form-group">
                        <label><strong>Attachment (Optional)</strong></label>
                        <input type="file" name="step3_attachment" class="file-input-enhanced" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
                    </div>
                    <div class="form-group hform-full">
                        <label><strong>Handling Response Remarks</strong></label>
                        <textarea name="step3_remarks" rows="3" placeholder="Visible to reporter/person involved" required>{{ old('step3_remarks') }}</textarea>
                    </div>
                </div>
                <div class="hform-actions">
                    <button type="button" onclick="openConfirmActionModal('issueSuspensionForm', 'Confirm before proceeding: Form 2305 should already be printed, properly filled out, and ready to be served to the student/person concerned. Continue recording this action?')">
                        Record 2305 Action and Notify Reporter
                    </button>
                    <a href="{{ route('admin.handlereports.print-suspension', $report->id) }}" class="btn-secondary">Download Suspension Memorandum (.docx)</a>
                    @if($report->suspension_document_path)
                        <a href="{{ asset('storage/' . $report->suspension_document_path) }}" class="btn-secondary" target="_blank">View Stored 2305 Record</a>
                    @endif
                </div>
            </form>
        </div>
        @endif

        {{-- ── Escalation (DSDO only) ── --}}
        @if(!$isTopManagementUser)
        <div class="handle-card handle-card-spaced">
            <div class="hcard-header">
                <h3 class="hcard-title">Escalation / Reassignment</h3>
                @if(!$report->escalated_to_top_management)
                <button type="button" class="hcard-toggle btn-secondary" id="toggleEscalationBtn"
                    onclick="toggleSection('escalationPanel', 'toggleEscalationBtn', 'Show', 'Hide')">
                    {{ $showEscalationPanel ? 'Hide' : 'Show' }}
                </button>
                @endif
            </div>

            @if($report->escalated_to_top_management)
                <p class="hcard-desc">Already escalated{{ $report->assigned_to ? ' and assigned to ' . $report->assigned_to : '' }}.</p>
            @else
                <div id="escalationPanel" style="display: {{ $showEscalationPanel ? 'block' : 'none' }};">
                    <form action="{{ route('admin.handlereports.escalate-top-management', $report->id) }}" method="POST" enctype="multipart/form-data" class="hform">
                        @csrf
                        <div class="hform-grid">
                            <div class="form-group hform-full">
                                <label><strong>Escalate / Reassign To</strong></label>
                                <select name="escalation_target_user_id" required>
                                    <option value="" selected disabled>Select target handler</option>
                                    @foreach($escalationTargets as $manager)
                                        @php
                                            $managerName   = trim((string)(($manager->first_name ?? '') . ' ' . ($manager->last_name ?? '')));
                                            $managerOffice = trim((string)($manager->employee_office ?? ($manager->is_top_management ? 'Top Management' : optional($manager->department)->name ?? '')));
                                            $managerRole   = $manager->is_top_management ? 'Top Management' : 'DSDO';
                                        @endphp
                                        <option value="{{ $manager->id }}" {{ old('escalation_target_user_id') == $manager->id ? 'selected' : '' }}>
                                            {{ $managerName }} ({{ $managerRole }} - {{ $managerOffice }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="handle-muted-note">You may route this case to Top Management or to another department's DSDO.</small>
                            </div>
                            <div class="form-group hform-full">
                                <label><strong>Escalation Note</strong></label>
                                <textarea name="escalation_note" rows="3" placeholder="Optional note for case handoff">{{ old('escalation_note') }}</textarea>
                            </div>
                            <div class="form-group">
                                <label><strong>Status</strong></label>
                                <input type="text" value="Under Review" class="readonly-field" readonly>
                            </div>
                            <div class="form-group">
                                <label><strong>Target Date (Optional)</strong></label>
                                <input type="date" name="step4_target_date" value="{{ old('step4_target_date') }}">
                            </div>
                            <div class="form-group">
                                <label><strong>Attachment (Required)</strong></label>
                                <input type="file" name="step4_attachment" class="file-input-enhanced" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip" required>
                                <small class="handle-muted-note">Supporting documents are required to escalate this report.</small>
                            </div>
                            <div class="form-group hform-full">
                                <label><strong>Handling Response Remarks</strong></label>
                                <textarea name="step4_remarks" rows="3" placeholder="Visible to reporter/person involved" required>{{ old('step4_remarks') }}</textarea>
                            </div>
                        </div>
                        <div class="hform-actions">
                            <button type="submit">Submit Escalation / Reassignment</button>
                            <small class="handle-muted-note">Escalation automatically keeps this case in Under Review.</small>
                        </div>
                    </form>
                </div>
            @endif
        </div>
        @endif

        @endif {{-- end $hasPersonInvolvement --}}

        {{-- ── No person involved: general response only ── --}}
        @if($isNoPersonInvolved)
        <div class="handle-card handle-card-spaced">
            <div class="hcard-header">
                <h3 class="hcard-title">Additional Handling Response</h3>
                <button type="button" class="hcard-toggle btn-secondary" id="toggleGeneralResponseBtn"
                    onclick="toggleSection('generalResponsePanel', 'toggleGeneralResponseBtn', 'Show', 'Hide')">
                    {{ $showGeneralResponsePanel ? 'Hide' : 'Show' }}
                </button>
            </div>
            <p class="hcard-desc">No person involved — only additional handling response available.</p>

            <div id="generalResponsePanel" style="display: {{ $showGeneralResponsePanel ? 'block' : 'none' }};">
                <form action="{{ route('admin.handlereports.update', $report->id) }}" method="POST" class="hform">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" value="Under Review">
                    <input type="hidden" name="department_id" value="{{ $handlerDepartmentId }}">
                    <div class="hform-grid">
                        <div class="form-group">
                            <label><strong>Department</strong></label>
                            <input type="text" value="{{ $handlerDepartmentLabel }}" class="readonly-field" readonly>
                        </div>
                        <div class="form-group">
                            <label><strong>Target Date</strong></label>
                            <input type="date" name="target_date" value="{{ old('target_date') }}" required>
                        </div>
                        <div class="form-group">
                            <label><strong>Status</strong></label>
                            <input type="text" value="Under Review" class="readonly-field" readonly>
                        </div>
                        @if($isTopManagementUser)
                        <div class="form-group hform-full">
                            <label><strong>Assign To (Optional)</strong></label>
                            <select name="assigned_to_user_id">
                                <option value="">Keep current assignment</option>
                                @foreach($handlerUsers as $handlerUser)
                                    @php $handlerName = trim((string)(($handlerUser->first_name ?? '') . ' ' . ($handlerUser->last_name ?? ''))); @endphp
                                    <option value="{{ $handlerUser->id }}" {{ (string) old('assigned_to_user_id') === (string) $handlerUser->id ? 'selected' : '' }}>
                                        {{ $handlerName !== '' ? $handlerName : ($handlerUser->name ?? 'User #' . $handlerUser->id) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="form-group hform-full">
                            <label><strong>Handling Response Remarks</strong></label>
                            <textarea name="remarks" rows="3" placeholder="Add progress update or follow-up actions" required>{{ old('remarks') }}</textarea>
                        </div>
                    </div>
                    <div class="hform-actions">
                        <button type="submit">Record Additional Handling Response</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

    </section>

    {{-- Confirm Action Modal --}}
    <div id="confirmActionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Action</h3>
                <span class="close" onclick="closeConfirmActionModal()">&times;</span>
            </div>
            <p id="confirmActionMessage" style="padding: 1rem 1.875rem;"></p>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeConfirmActionModal()">Cancel</button>
                <button type="button" class="btn-submit" id="confirmActionYesBtn">Yes, Continue</button>
            </div>
        </div>
    </div>

    {{-- ================================================================
         RESPONSE HISTORY
         ================================================================ --}}
    <section id="response-history" class="handle-section">
        <div class="detail-group">
            <button class="detail-group-toggle" aria-expanded="false" aria-controls="responseHistoryBody">
                <span class="detail-group-icon">📋</span>
                <span class="detail-group-label">Response History</span>
                <span class="detail-group-chevron">▾</span>
            </button>
            <div class="detail-group-body collapsed" id="responseHistoryBody">
                @forelse($responses as $response)
                    <div class="response-card">
                        <div class="response-card-head">
                            <strong>Response #{{ $response->response_number }}</strong>
                            <small>{{ $response->created_at->format('M d, Y h:i A') }}</small>
                        </div>
                        <div class="response-body">
                            @if(!empty($response->admin->name ?? null))
                            <div class="response-line"><span>Handled by:</span> {{ $response->admin->name }}</div>
                            @endif
                            @if(!empty($response->response_type))
                            <div class="response-line"><span>Type:</span> {{ $response->response_type }}</div>
                            @endif
                            <div class="response-line"><span>Status:</span> {{ \App\Models\Report::labelForStatus($response->status) }}</div>
                            @if(!empty($response->assigned_to))
                            <div class="response-line"><span>Assigned to:</span> {{ $response->assigned_to }}</div>
                            @endif
                            @if(!empty($response->department))
                            <div class="response-line"><span>Department:</span> {{ $response->department }}</div>
                            @endif
                            @if(!empty($response->target_date))
                            <div class="response-line"><span>Target date:</span> {{ $response->target_date }}</div>
                            @endif
                            @if(!empty($response->remarks))
                            <div class="response-line"><span>Remarks:</span> {{ $response->remarks }}</div>
                            @endif
                            @if($response->attachment_path)
                            <div class="response-line"><span>Attachment:</span> <a href="{{ asset('storage/' . $response->attachment_path) }}" target="_blank" class="info-link">View</a></div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p style="padding: 1rem 1.5rem; color: rgba(255,255,255,0.7);">No responses have been recorded yet.</p>
                @endforelse
            </div>
        </div>
    </section>

@endsection

@push('styles')
<style>
    /* ================================================================
       DETAIL GROUPS — collapsible sections (matches user side)
       ================================================================ */
    .detail-group {
        border-bottom: 1px solid rgba(255,255,255,0.12);
    }

    .detail-group:last-of-type {
        border-bottom: none;
    }

    .detail-group-toggle {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 1.5rem;
        background: transparent;
        border: none;
        color: #fff;
        font-size: 0.95rem;
        font-weight: 600;
        cursor: pointer;
        text-align: left;
    }

    .detail-group-toggle:hover {
        background: rgba(255,255,255,0.06);
    }

    .detail-group-icon { font-size: 1rem; flex-shrink: 0; }

    .detail-group-label {
        flex: 1;
        color: rgba(255,255,255,0.95);
    }

    .detail-group-chevron {
        font-size: 0.9rem;
        color: rgba(255,255,255,0.55);
        flex-shrink: 0;
        display: inline-block;
    }

    .detail-group-toggle[aria-expanded="true"] .detail-group-chevron {
        transform: rotate(180deg);
    }

    .detail-group-body {
        overflow: hidden;
        max-height: 9999px;
    }

    .detail-group-body.collapsed {
        max-height: 0 !important;
        overflow: hidden;
    }

    /* Classification badge */
    .classification-badge {
        display: inline-block;
        padding: 0.2rem 0.6rem;
        border-radius: 2rem;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .classification-badge.minor { background: #fbbf24; color: #78350f; }
    .classification-badge.major { background: #f97316; color: #fff; }
    .classification-badge.grave { background: #ef4444; color: #fff; }

    /* Subsection label inside collapsible */
    .adm-subsection-label {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: rgba(255,255,255,0.5);
        padding: 0.5rem 1.5rem 0.25rem;
        border-top: 1px solid rgba(255,255,255,0.08);
    }

    /* ================================================================
       INFO STACK — replaces all handle-report-table th/td layouts
       ================================================================ */
    .info-stack {
        padding: 0.5rem 1.5rem 1rem;
    }

    .info-row {
        display: flex;
        align-items: baseline;
        gap: 0.75rem;
        padding: 0.55rem 0;
        border-bottom: 1px solid rgba(255,255,255,0.07);
    }

    .info-row:last-child { border-bottom: none; }

    .info-row.info-row-block {
        flex-direction: column;
        gap: 0.3rem;
    }

    .info-label {
        flex: 0 0 160px;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: rgba(255,255,255,0.55);
    }

    .info-value {
        flex: 1;
        font-size: 0.92rem;
        color: rgba(255,255,255,0.95);
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .info-list {
        margin: 0;
        padding-left: 1.1rem;
        color: rgba(255,255,255,0.9);
        font-size: 0.9rem;
        line-height: 1.7;
    }

    .info-link {
        color: #93c5fd;
        text-decoration: none;
    }
    .info-link:hover { text-decoration: underline; }

    /* ================================================================
       SUBSECTION TITLES
       ================================================================ */
    .subsection-title {
        font-size: 0.88rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: rgba(255,255,255,0.6);
        padding: 1rem 1.5rem 0.25rem;
        margin: 0;
        border-top: 1px solid rgba(255,255,255,0.1);
    }

    /* ================================================================
       HANDLING CARDS
       ================================================================ */
    .hcard-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
        margin-bottom: 0.65rem;
    }

    .hcard-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #fff;
        flex: 1;
    }

    .hcard-title-center { text-align: center; }

    .hcard-toggle {
        flex-shrink: 0;
        padding: 0.4rem 0.875rem !important;
        font-size: 0.82rem !important;
        min-height: 36px !important;
    }

    .hcard-desc {
        font-size: 0.85rem;
        color: rgba(255,255,255,0.7);
        margin: 0 0 0.75rem;
    }

    .hcard-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.3rem 1.25rem;
        margin-top: 0.75rem;
        padding-top: 0.65rem;
        border-top: 1px solid rgba(255,255,255,0.1);
        font-size: 0.82rem;
        color: rgba(255,255,255,0.75);
    }

    /* Response History header — sits inside section padding naturally */
    .rh-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
        padding: 1.4rem 2rem 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.12);
        background: var(--glass-bg-hover);
    }

    .rh-title {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: #fff;
    }

    /* ================================================================
       HANDLING FORMS
       ================================================================ */
    .hform { padding: 0; border: none !important; margin: 0 !important; }

    .hform-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }

    .hform-full { grid-column: 1 / -1; }

    .hform-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
        align-items: center;
        padding: 0;
        margin-top: 0.25rem;
        border: none !important;
    }

    /* ================================================================
       RESPONSE HISTORY
       ================================================================ */
    .response-body {
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
        margin-top: 0.5rem;
    }

    .response-line {
        font-size: 0.88rem;
        color: rgba(255,255,255,0.88);
    }

    .response-line span {
        font-weight: 700;
        color: rgba(255,255,255,0.55);
        font-size: 0.78rem;
        text-transform: uppercase;
        margin-right: 0.35rem;
    }

    .response-card {
        border-left: 3px solid rgba(93, 197, 253, 0.5);
        padding: 0.75rem 1rem;
        margin: 0.5rem 0;
        background: rgba(255, 255, 255, 0.04);
        border-radius: 0.4rem;
    }

    .response-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
        flex-wrap: wrap;
    }

    .response-card-head strong {
        color: #93c5fd;
        font-size: 0.95rem;
    }

    .response-card-head small {
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.8rem;
    }

    /* ================================================================
       EVIDENCE
       ================================================================ */
    .evidence-btn {
        text-decoration: none;
        font-size: 0.82rem;
        padding: 0 0.875rem !important;
        min-height: 34px !important;
    }

    .evidence-file-icon {
        font-size: 2.5rem;
        text-align: center;
        padding: 0.75rem 0 0.25rem;
    }

    .evidence-file-name {
        text-align: center;
        font-size: 0.78rem;
        color: rgba(255,255,255,0.7);
        word-break: break-all;
        margin: 0 0 0.5rem;
    }

    /* ================================================================
       DESKTOP / MOBILE BLOCK VISIBILITY
       ================================================================ */
    .desktop-only-block { display: block; }
    .mobile-only-block  { display: none; }

    /* Person cards (mobile additional persons) */
    .mobile-person-card {
        border: 1px solid rgba(255,255,255,0.15);
        border-radius: 0.6rem;
        overflow: hidden;
        margin-bottom: 0.5rem;
    }
    .mpc-head {
        background: rgba(255,255,255,0.1);
        padding: 0.4rem 0.875rem;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: rgba(255,255,255,0.7);
    }
    .mpc-body {
        padding: 0.6rem 0.875rem;
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
        font-size: 0.88rem;
        color: rgba(255,255,255,0.9);
    }
    .mpc-body span {
        font-weight: 600;
        color: rgba(255,255,255,0.5);
        font-size: 0.76rem;
        margin-right: 0.2rem;
    }

    #responseHistoryPanel {
        padding: 1rem 1.5rem 1.5rem;
    }
    .workflow-summary { margin-bottom: 1rem; }

    .field-time { min-width: 180px; letter-spacing: 0.02em; font-weight: 600; }

    .field-time-enhanced {
        border-radius: 10px;
        border: 1px solid rgba(17,24,39,0.25);
        background: #ffffff;
        color: #111111;
        padding: 0.6rem 0.7rem;
    }

    .file-input-enhanced {
        width: 100%;
        border: 1px dashed rgba(255,255,255,0.35);
        border-radius: 10px;
        padding: 0.45rem;
        background: rgba(18,28,44,0.45);
        color: #eaf0ff;
    }

    .file-input-enhanced::file-selector-button {
        margin-right: 0.75rem;
        border: 0;
        border-radius: 8px;
        padding: 0.45rem 0.7rem;
        background: linear-gradient(135deg, #1d4ed8, #0f766e);
        color: #fff;
        cursor: pointer;
        font-weight: 600;
    }

    .handle-title-centered { text-align: center; }

    input[name="suspension_days"] { max-width: 220px; font-weight: 600; }

    .readonly-field {
        background: #ffffff !important;
        border: 1px solid rgba(17,24,39,0.25);
        color: #111111 !important;
        -webkit-text-fill-color: #111111;
        opacity: 1;
        cursor: not-allowed;
    }

    .hform input:not([type="file"]):not(.readonly-field):not(.field-time-enhanced),
    .hform select,
    .hform textarea {
        background: #ffffff;
        color: #111111;
        caret-color: #111111;
    }

    .hform input:not([type="file"]):not(.readonly-field):not(.field-time-enhanced)::placeholder,
    .hform textarea::placeholder { color: #4b5563; }

    .hform input[type="date"]::-webkit-datetime-edit,
    .hform input[type="time"]::-webkit-datetime-edit { color: #111111; }

    .hform select option { color: #111111; background: #ffffff; }

    .handle-section { margin-bottom: 1.75rem; }

    #handling-forms .handle-card {
        margin-top: 1.15rem;
        padding: 1.1rem 1.2rem;
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 12px;
    }

    .handle-muted-note {
        display: block;
        margin-top: 0.35rem;
        color: var(--text-muted);
    }

    .evidence-actions {
        margin-top: 0.6rem;
        display: flex;
        gap: 0.55rem;
        flex-wrap: wrap;
    }

    /* ================================================================
       MOBILE
       ================================================================ */
    @media (max-width: 768px) {

        /* Info stack: label on top, value below */
        .info-row {
            flex-direction: column;
            gap: 0.15rem;
            padding: 0.6rem 0;
        }

        .info-label {
            flex: none;
            font-size: 0.72rem;
        }

        .info-stack {
            padding: 0.4rem 1rem 0.75rem;
        }

        .subsection-title {
            padding: 0.875rem 1rem 0.2rem;
        }

        /* hform: single column */
        .hform-grid {
            grid-template-columns: 1fr;
            gap: 0.6rem;
        }

        /* hform actions: stack */
        .hform-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .hform-actions button,
        .hform-actions a {
            width: 100%;
            text-align: center;
            justify-content: center;
        }

        /* hcard header: wrap naturally */
        .hcard-header {
            gap: 0.5rem;
        }

        .hcard-toggle {
            width: 100%;
        }

        /* Handling card padding */
        #handling-forms .handle-card {
            padding: 0.875rem;
        }

        /* Response history */
        .response-card-head {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.2rem;
        }

        /* Evidence */
        .evidence-grid {
            grid-template-columns: 1fr 1fr;
        }

        .evidence-actions {
            flex-direction: column;
        }

        .evidence-btn {
            width: 100%;
            justify-content: center;
        }

        /* Desktop/mobile blocks */
        .desktop-only-block { display: none; }
        .mobile-only-block  { display: block; }

        /* hcard meta: stack */
        .hcard-meta {
            flex-direction: column;
            gap: 0.25rem;
        }
    }

    @media (max-width: 480px) {
        .info-stack { padding: 0.35rem 0.875rem 0.6rem; }
        .subsection-title { padding: 0.75rem 0.875rem 0.2rem; }
        #handling-forms .handle-card { padding: 0.75rem; }
        .evidence-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@push('scripts')
<script>
    /* ── Collapsible detail groups ── */
    (function () {
        document.querySelectorAll(".detail-group-toggle").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var expanded = btn.getAttribute("aria-expanded") === "true";
                var bodyId   = btn.getAttribute("aria-controls");
                var body     = document.getElementById(bodyId);
                if (!body) return;
                btn.setAttribute("aria-expanded", String(!expanded));
                body.classList.toggle("collapsed", expanded);
            });
        });
    })();

    let confirmActionTargetFormId = null;

    function toggleSection(sectionId, buttonId, showLabel, hideLabel) {
        const section = document.getElementById(sectionId);
        const button  = document.getElementById(buttonId);
        if (!section || !button) return;
        const isHidden = section.style.display === 'none' || section.style.display === '';
        section.style.display = isHidden ? 'block' : 'none';
        button.textContent = isHidden ? hideLabel : showLabel;
    }

    function showSelectedForm(formValue) {
        // Hide all form panels
        const panels = ['form2303Panel', 'additionalResponsePanel', 'form2304Panel', 'form2305Panel'];
        panels.forEach(function(panelId) {
            const panel = document.getElementById(panelId);
            if (panel) panel.style.display = 'none';
        });

        // Show selected form panel
        if (formValue) {
            const selectedPanel = document.getElementById(formValue + 'Panel');
            if (selectedPanel) {
                selectedPanel.style.display = 'block';
                selectedPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }

    function openConfirmActionModal(formId, message) {
        confirmActionTargetFormId = formId;
        const el = document.getElementById('confirmActionMessage');
        if (el) el.textContent = message;
        document.getElementById('confirmActionModal').style.display = 'flex';
    }

    function closeConfirmActionModal() {
        document.getElementById('confirmActionModal').style.display = 'none';
        confirmActionTargetFormId = null;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('confirmActionModal');
        if (modal) document.body.appendChild(modal);

        const yesBtn = document.getElementById('confirmActionYesBtn');
        if (yesBtn) {
            yesBtn.addEventListener('click', function () {
                if (!confirmActionTargetFormId) { closeConfirmActionModal(); return; }
                const form = document.getElementById(confirmActionTargetFormId);
                if (form) form.submit();
            });
        }

        // Suspension days toggle
        const actionSelect     = document.querySelector('select[name="disciplinary_action"]');
        const suspensionDaysEl = document.querySelector('input[name="suspension_days"]');

        if (actionSelect && suspensionDaysEl) {
            const sync = function () {
                const isSuspension = actionSelect.value === 'Suspension';
                suspensionDaysEl.required = isSuspension;
                if (!isSuspension) {
                    suspensionDaysEl.value = '';
                    suspensionDaysEl.setAttribute('disabled', 'disabled');
                    suspensionDaysEl.placeholder = 'Not required for Dismissal';
                } else {
                    suspensionDaysEl.removeAttribute('disabled');
                    suspensionDaysEl.placeholder = 'Required for Suspension';
                }
            };
            sync();
            actionSelect.addEventListener('change', sync);
        }
    });

    window.addEventListener('click', function (e) {
        if (e.target.classList.contains('modal')) closeConfirmActionModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeConfirmActionModal();
    });
</script>
@endpush