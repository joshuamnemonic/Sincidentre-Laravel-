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
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <section class="handle-section workflow-summary">
        <h2 class="handle-title-centered">Handling Workflow Summary</h2>
        <table class="handle-report-table">
            <tr>
                <th>Current Stage</th>
                <td>{{ $workflowStage }}</td>
            </tr>
            <tr>
                <th>Last Action</th>
                <td>
                    @if($latestResponse)
                        {{ $latestResponse->response_type ?? 'Handling Response' }}
                        @if($latestResponse->created_at)
                            · {{ $latestResponse->created_at->format('M d, Y h:i A') }}
                        @endif
                    @else
                        No handling response recorded yet.
                    @endif
                </td>
            </tr>
            <tr>
                <th>Next Recommended Action</th>
                <td>{{ $nextRecommendedAction }}</td>
            </tr>
            <tr>
                <th>Available Actions (Current Role)</th>
                <td>{{ $availableActionsLabel }}</td>
            </tr>
        </table>
    </section>

    @if($currentStatus === \App\Models\Report::STATUS_RESOLVED)
        <section class="handle-section">
            <h2 class="handle-title-centered">Resolution Details</h2>
            <table class="handle-report-table">
                <tr>
                    <th>Resolution Type</th>
                    <td>{{ $resolutionType }}</td>
                </tr>
                <tr>
                    <th>Resolved At</th>
                    <td>
                        @if($report->suspension_issued_at)
                            {{ $report->suspension_issued_at->format('F d, Y h:i A') }}
                        @elseif($report->reprimand_issued_at)
                            {{ $report->reprimand_issued_at->format('F d, Y h:i A') }}
                        @elseif($latestResolvedResponse?->created_at)
                            {{ $latestResolvedResponse->created_at->format('F d, Y h:i A') }}
                        @else
                            N/A
                        @endif
                    </td>
                </tr>
                @if(!empty($report->disciplinary_action))
                    <tr>
                        <th>Disciplinary Action</th>
                        <td>{{ $report->disciplinary_action }}</td>
                    </tr>
                @endif
                @if(!empty($report->suspension_days))
                    <tr>
                        <th>Suspension Days</th>
                        <td>{{ $report->suspension_days }}</td>
                    </tr>
                @endif
                @if($report->suspension_effective_date)
                    <tr>
                        <th>Suspension Effective Date</th>
                        <td>{{ $report->suspension_effective_date->format('F d, Y') }}</td>
                    </tr>
                @endif
                @if(!empty($latestResolvedResponse?->remarks))
                    <tr>
                        <th>Resolution Remarks</th>
                        <td>{{ $latestResolvedResponse->remarks }}</td>
                    </tr>
                @endif
            </table>
        </section>
    @endif

    <section id="report-details" class="handle-section">
        <h2 class="handle-title-centered">Submitted Report Details (Form 2302 View)</h2>
        <table class="handle-report-table">
            <tr>
                <th>Report ID</th>
                <td>#{{ $report->id }}</td>
            </tr>
            <tr>
                <th>Category</th>
                <td>
                    @if($report->category)
                        {{ strtoupper($report->category->main_category_code) }} - {{ $report->category->main_category_name }} / {{ $report->category->name }}
                    @else
                        N/A
                    @endif
                </td>
            </tr>
            <tr>
                <th>Category Classification</th>
                <td>{{ $report->category->classification ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                        {{ \App\Models\Report::labelForStatus($report->status) }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>Date Submitted</th>
                <td>{{ $report->submitted_at ? $report->submitted_at->format('F d, Y h:i A') : $report->created_at->format('F d, Y h:i A') }}</td>
            </tr>
            <tr>
                <th>Person Involvement</th>
                <td>{{ $report->person_involvement ? ucfirst($report->person_involvement) : 'N/A' }}</td>
            </tr>
            <tr>
                <th>System Reporter Account</th>
                <td>{{ $report->user->name ?? 'Unknown' }} ({{ $report->user->email ?? 'N/A' }})</td>
            </tr>
        </table>

        <h3 class="handle-card-title handle-spacing-top handle-title-centered">Section 1: Information About the Person/s Involved in the Incident</h3>
        <table class="handle-report-table handle-spacing-top">
            <tr>
                <th>Reported Person Name</th>
                <td>{{ $report->person_full_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Reported Person College/Department</th>
                <td>{{ $report->person_college_department ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Reported Person Role</th>
                <td>{{ $report->person_role ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Reported Person Contact Number</th>
                <td>{{ $report->person_contact_number ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Reported Person Email</th>
                <td>{{ $report->person_email_address ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Multiple Persons Involved</th>
                <td>{{ $report->person_has_multiple ? 'Yes' : 'No' }}</td>
            </tr>
            <tr>
                <th>Unknown Person Details</th>
                <td>{{ $report->unknown_person_details ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Technical/Facility Details</th>
                <td>{{ $report->technical_facility_details ?? 'N/A' }}</td>
            </tr>
        </table>

        @if(is_array($report->additional_persons) && count($report->additional_persons) > 0)
            <h3 class="handle-card-title handle-spacing-top">Additional Involved Persons</h3>
            <table class="handle-report-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>College/Department</th>
                        <th>ID Number</th>
                        <th>Email</th>
                    </tr>
                </thead>
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
        @endif

        <h3 class="handle-card-title handle-spacing-top handle-title-centered">Section 2: Information About the Incident</h3>
        <table class="handle-report-table handle-spacing-top">
            <tr>
                <th>Incident Description</th>
                <td>{{ $report->description ?: 'N/A' }}</td>
            </tr>
            <tr>
                <th>Incident Date</th>
                <td>{{ $report->incident_date ? $report->incident_date->format('F d, Y') : 'N/A' }}</td>
            </tr>
            <tr>
                <th>Incident Time</th>
                <td>{{ $report->incident_time ? \Carbon\Carbon::parse($report->incident_time)->format('h:i A') : 'N/A' }}</td>
            </tr>
            <tr>
                <th>Location</th>
                <td>{{ $report->location ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Please Specify</th>
                <td>{{ $report->location_details ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Were There Any Witnesses?</th>
                <td>{{ $report->has_witnesses ? 'Yes' : 'No' }}</td>
            </tr>
            @if($report->has_witnesses && is_array($report->witness_details) && count($report->witness_details) > 0)
                <tr>
                    <th>Witness Details</th>
                    <td>
                        <ul>
                            @foreach($report->witness_details as $witness)
                                <li>
                                    {{ $witness['name'] ?? ($witness['full_name'] ?? 'Unnamed Witness') }}
                                    @if(!empty($witness['address'])) - {{ $witness['address'] }} @endif
                                    @if(!empty($witness['college_department'])) - {{ $witness['college_department'] }} @endif
                                    @if(!empty($witness['role'])) ({{ $witness['role'] }}) @endif
                                    @if(!empty($witness['contact_number'])) | {{ $witness['contact_number'] }} @endif
                                    @if(!empty($witness['email_address'])) | {{ $witness['email_address'] }} @endif
                                </li>
                            @endforeach
                        </ul>
                    </td>
                </tr>
            @endif
            @if($report->has_witnesses && $report->witness_attachment)
                <tr>
                    <th>Witness Attachment</th>
                    <td>
                        <a href="{{ asset('storage/' . $report->witness_attachment) }}" target="_blank">View Witness Attachment</a>
                    </td>
                </tr>
            @endif
            <tr>
                <th>Additional Incident Sheets</th>
                <td>
                    @if($report->incident_additional_sheets)
                        @php
                            $sheetFiles = is_array($report->incident_additional_sheets)
                                ? $report->incident_additional_sheets
                                : json_decode((string) $report->incident_additional_sheets, true);
                            $sheetFiles = is_array($sheetFiles) ? $sheetFiles : [];
                        @endphp
                        @if(count($sheetFiles) > 0)
                            <ul>
                                @foreach($sheetFiles as $sheetIndex => $sheetFile)
                                    <li>
                                        <a href="{{ asset('storage/' . $sheetFile) }}" target="_blank">View Additional Sheet {{ $sheetIndex + 1 }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            N/A
                        @endif
                    @else
                        N/A
                    @endif
                </td>
            </tr>
        </table>

        <h3 class="handle-card-title handle-spacing-top handle-title-centered">Section 3: Information About the Informant</h3>
        <table class="handle-report-table handle-spacing-top">
            <tr>
                <th>Informant Full Name</th>
                <td>{{ $report->informant_full_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Informant College/Department</th>
                <td>{{ $report->informant_college_department ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Informant Role</th>
                <td>{{ $report->informant_role ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Informant Contact Number</th>
                <td>{{ $report->informant_contact_number ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Informant Email Address</th>
                <td>{{ $report->informant_email_address ?? 'N/A' }}</td>
            </tr>
        </table>
    </section>

    @if($report->evidence)
        <section id="evidence-section" class="handle-section handle-spacing-top">
            <h2>Submitted Evidence</h2>
            @php
                $evidences = json_decode($report->evidence, true);
            @endphp
            @if(is_array($evidences) && count($evidences) > 0)
                <div class="evidence-grid">
                    @foreach($evidences as $file)
                        @php
                            $extension = pathinfo($file, PATHINFO_EXTENSION);
                            $fileName = basename($file);
                            $isImage = in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                        @endphp

                        @if($isImage)
                            <div class="evidence-item">
                                <a href="{{ asset('storage/' . $file) }}" target="_blank" rel="noopener noreferrer">
                                    <img src="{{ asset('storage/' . $file) }}" alt="Evidence Image" class="handle-evidence-image">
                                </a>
                                <div class="evidence-actions">
                                    <a href="{{ asset('storage/' . $file) }}" target="_blank" rel="noopener noreferrer" class="btn-secondary">View</a>
                                    <a href="{{ asset('storage/' . $file) }}" download="{{ $fileName }}" class="btn-secondary">Download</a>
                                </div>
                            </div>
                        @else
                            <div class="evidence-item">
                                <p>{{ $fileName }}</p>
                                <div class="evidence-actions">
                                    <a href="{{ asset('storage/' . $file) }}" target="_blank" rel="noopener noreferrer" class="btn-secondary">View File</a>
                                    <a href="{{ asset('storage/' . $file) }}" download="{{ $fileName }}" class="btn-secondary">Download</a>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    <section id="handling-forms" class="handle-section handle-spacing-top">
        <h2 class="handle-title-centered">Handling Forms</h2>

        @if(!$hasPersonInvolvement && !$isNoPersonInvolved)
            <div class="alert alert-warning">
                Forms 2303, 2304, and 2305 are only available when person involvement is <strong>Known</strong>, <strong>Unknown</strong>, or <strong>Not sure yet</strong>.
            </div>
        @endif

        @if($hasPersonInvolvement)

        <div class="handle-card handle-card-spaced">
            <h3 class="handle-card-title handle-title-centered">2303: Schedule Hearing and Notify Parties</h3>
            <div class="handle-inline-actions handle-small-bottom-space">
                <button type="button" class="btn-secondary" id="toggleStep1Btn" onclick="toggleSection('step1Panel', 'toggleStep1Btn', 'Show 2303 (Reschedule Hearing)', 'Hide 2303')">
                    {{ $showStep1Panel ? 'Hide 2303' : 'Show 2303 (Reschedule Hearing)' }}
                </button>
            </div>
            <div id="step1Panel" style="display: {{ $showStep1Panel ? 'block' : 'none' }};">
            <form action="{{ route('admin.handlereports.schedule-hearing', $report->id) }}" method="POST" enctype="multipart/form-data" class="handle-form-block">
                @csrf
                <div class="handle-grid-3">
                    <div class="form-group handle-form-group">
                        <label><strong>Hearing Date</strong></label><br>
                        <input type="date" name="hearing_date" value="{{ old('hearing_date', optional($report->hearing_date)->format('Y-m-d')) }}" required>
                    </div>
                    <div class="form-group handle-form-group">
                        <label><strong>Hearing Time</strong></label><br>
                        <input type="time" name="hearing_time" step="300" class="field-time field-time-enhanced" value="{{ old('hearing_time', $report->hearing_time ? \Illuminate\Support\Carbon::parse($report->hearing_time)->format('H:i') : '') }}" required>
                        <small class="handle-muted-note">Use 24-hour format. Example: 13:30 for 1:30 PM.</small>
                    </div>
                    <div class="form-group handle-form-group">
                        <label><strong>Venue</strong></label><br>
                        <input type="text" name="hearing_venue" value="{{ old('hearing_venue', $report->hearing_venue) }}" placeholder="Office / room" required>
                    </div>
                </div>
                <div class="handle-grid-3">
                    <div class="form-group handle-form-group">
                        <label><strong>Status</strong></label><br>
                        <input type="text" value="Under Review" class="readonly-field" readonly>
                    </div>
                    <div class="form-group handle-form-group">
                        <label><strong>Attachment (Optional)</strong></label><br>
                        <input type="file" name="step1_attachment" class="file-input-enhanced" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
                    </div>
                </div>
                <div class="form-group handle-form-group">
                    <label><strong>Handling Response Remarks</strong></label><br>
                    <textarea name="step1_remarks" rows="3" placeholder="Visible to reporter/person involved" required>{{ old('step1_remarks') }}</textarea>
                </div>
                <div class="handle-inline-actions">
                    <button type="submit">Save Hearing Schedule</button>
                    <a href="{{ route('admin.handlereports.print-call-slip', $report->id) }}" class="btn-secondary">Download Call Slip (.docx)</a>
                </div>
            </form>
            </div>

            <p class="handle-meta-line"><strong>Last notified:</strong>
                {{ $report->respondent_notified_at ? $report->respondent_notified_at->format('M d, Y h:i A') : 'Not yet notified' }}
            </p>
            <p class="handle-meta-line"><strong>Reporter confirmation:</strong> {{ $reporterConfirmed ? 'Confirmed' : 'Pending confirmation' }}</p>
            <p class="handle-meta-line"><strong>Involved party confirmation:</strong> {{ $involvedConfirmed ? 'Confirmed' : 'Pending confirmation' }}</p>
        </div>

        @if($step1Done)
        <div class="handle-card handle-card-spaced">
            <h3 class="handle-card-title handle-title-centered">Additional Handling Response (Non-Form 2303/2304/2305)</h3>
            <p class="handle-meta-line">
                If the case is still in progress after Form 2303, you can add another handling response here
                without proceeding to Form 2304 yet.
            </p>
            <div class="handle-inline-actions handle-small-bottom-space">
                <button
                    type="button"
                    class="btn-secondary"
                    id="toggleGeneralResponseBtn"
                    onclick="toggleSection('generalResponsePanel', 'toggleGeneralResponseBtn', 'Show Additional Handling Response', 'Hide Additional Handling Response')"
                >
                    {{ $showGeneralResponsePanel ? 'Hide Additional Handling Response' : 'Show Additional Handling Response' }}
                </button>
            </div>

            <div id="generalResponsePanel" style="display: {{ $showGeneralResponsePanel ? 'block' : 'none' }};">
                <form action="{{ route('admin.handlereports.update', $report->id) }}" method="POST" class="handle-form-block">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" value="Under Review">
                    <input type="hidden" name="department_id" value="{{ $handlerDepartmentId }}">

                    <div class="handle-grid-3">
                        <div class="form-group handle-form-group">
                            <label><strong>Department</strong></label><br>
                            <input type="text" value="{{ $handlerDepartmentLabel }}" class="readonly-field" readonly>
                        </div>

                        <div class="form-group handle-form-group">
                            <label><strong>Target Date</strong></label><br>
                            <input type="date" name="target_date" value="{{ old('target_date') }}" required>
                        </div>

                        <div class="form-group handle-form-group">
                            <label><strong>Status</strong></label><br>
                            <input type="text" value="Under Review" class="readonly-field" readonly>
                        </div>
                    </div>

                    @if($isTopManagementUser)
                        <div class="form-group handle-form-group">
                            <label><strong>Assign To (Optional)</strong></label><br>
                            <select name="assigned_to_user_id">
                                <option value="">Keep current assignment</option>
                                @foreach($handlerUsers as $handlerUser)
                                    @php
                                        $handlerName = trim((string) (($handlerUser->first_name ?? '') . ' ' . ($handlerUser->last_name ?? '')));
                                    @endphp
                                    <option value="{{ $handlerUser->id }}" {{ (string) old('assigned_to_user_id') === (string) $handlerUser->id ? 'selected' : '' }}>
                                        {{ $handlerName !== '' ? $handlerName : ($handlerUser->name ?? 'User #' . $handlerUser->id) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="form-group handle-form-group">
                        <label><strong>Handling Response Remarks</strong></label><br>
                        <textarea name="remarks" rows="3" placeholder="Add progress update or follow-up actions while case remains under review" required>{{ old('remarks') }}</textarea>
                    </div>

                    <div class="handle-inline-actions">
                        <button type="submit">Record Additional Handling Response</button>
                    </div>
                </form>
            </div>
        </div>

        @if(!$isTopManagementUser)
        <div class="handle-card handle-card-spaced">
            <h3 class="handle-card-title handle-title-centered">Form 2304: Written Reprimand</h3>
            <form id="issueReprimandForm" action="{{ route('admin.handlereports.issue-reprimand', $report->id) }}" method="POST" enctype="multipart/form-data" class="handle-form-block">
                @csrf
                <input type="hidden" name="step2_status" value="{{ \App\Models\Report::STATUS_RESOLVED }}">
                <div class="handle-grid-3">
                    <div class="form-group handle-form-group">
                        <label><strong>Status</strong></label><br>
                        <input type="text" value="Resolved" class="readonly-field" readonly>
                    </div>
                    <div class="form-group handle-form-group">
                        <label><strong>Target Date (Optional)</strong></label><br>
                        <input type="date" name="step2_target_date" value="{{ old('step2_target_date') }}">
                    </div>
                    <div class="form-group handle-form-group">
                        <label><strong>Attachment (Optional)</strong></label><br>
                        <input type="file" name="step2_attachment" class="file-input-enhanced" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
                    </div>
                </div>
                <div class="form-group handle-form-group">
                    <label><strong>Handling Response Remarks</strong></label><br>
                    <textarea name="step2_remarks" rows="3" placeholder="Visible to reporter/person involved" required>{{ old('step2_remarks') }}</textarea>
                </div>
                <div class="handle-inline-actions handle-align-center">
                    <button type="button" onclick="openConfirmActionModal('issueReprimandForm', 'Confirm before proceeding: Form 2304 should already be printed, properly filled out, and ready to be passed to the student/person who will receive the written reprimand. Continue recording this action?')">Record Written Reprimand and Notify Reporter</button>
                    <a href="{{ route('admin.handlereports.print-reprimand', $report->id) }}" class="btn-secondary">Download Written Reprimand (.docx)</a>
                </div>
            </form>
            <p class="handle-meta-line"><strong>Student acknowledgment:</strong>
                {{ $report->student_acknowledged_reprimand_at ? $report->student_acknowledged_reprimand_at->format('M d, Y h:i A') : 'Pending acknowledgment' }}
            </p>
            @if($step2Done)
                <p class="handle-meta-line"><strong>Step 2 progress:</strong> Completed</p>
            @endif
        </div>
        @endif

        @if($isTopManagementUser)
        <div class="handle-card handle-card-spaced">
            <h3 class="handle-card-title">Form 2305: Suspension or Dismissal</h3>
            <form id="issueSuspensionForm" action="{{ route('admin.handlereports.issue-suspension', $report->id) }}" method="POST" enctype="multipart/form-data" class="handle-grid-3">
                @csrf
                <div class="form-group handle-form-group">
                    <label><strong>Disciplinary Action</strong></label><br>
                    <select name="disciplinary_action" required>
                        <option value="Suspension" {{ old('disciplinary_action', $report->disciplinary_action) === 'Suspension' ? 'selected' : '' }}>Suspension</option>
                        <option value="Dismissal" {{ old('disciplinary_action', $report->disciplinary_action) === 'Dismissal' ? 'selected' : '' }}>Dismissal</option>
                    </select>
                </div>
                <div class="form-group handle-form-group">
                    <label><strong>Suspension Days</strong></label><br>
                    <input type="number" name="suspension_days" min="1" max="365" value="{{ old('suspension_days', $report->suspension_days) }}" placeholder="Required for Suspension">
                </div>
                <div class="form-group handle-form-group">
                    <label><strong>Effective Date</strong></label><br>
                    <input type="date" name="suspension_effective_date" value="{{ old('suspension_effective_date', optional($report->suspension_effective_date)->format('Y-m-d')) }}" required>
                </div>
                <div class="form-group handle-form-group">
                    <label><strong>Status</strong></label><br>
                    <select name="step3_status" required>
                        <option value="{{ \App\Models\Report::STATUS_UNDER_REVIEW }}" {{ old('step3_status', \App\Models\Report::STATUS_RESOLVED) === \App\Models\Report::STATUS_UNDER_REVIEW ? 'selected' : '' }}>Under Review</option>
                        <option value="{{ \App\Models\Report::STATUS_RESOLVED }}" {{ old('step3_status', \App\Models\Report::STATUS_RESOLVED) === \App\Models\Report::STATUS_RESOLVED ? 'selected' : '' }}>Resolved</option>
                    </select>
                </div>
                <div class="form-group handle-form-group">
                    <label><strong>Target Date (Optional)</strong></label><br>
                    <input type="date" name="step3_target_date" value="{{ old('step3_target_date') }}">
                </div>
                <div class="form-group handle-form-group">
                    <label><strong>Attachment (Optional)</strong></label><br>
                    <input type="file" name="step3_attachment" class="file-input-enhanced" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
                </div>
                <div class="form-group handle-form-group handle-grid-full">
                    <label><strong>Handling Response Remarks</strong></label><br>
                    <textarea name="step3_remarks" rows="3" placeholder="Visible to reporter/person involved" required>{{ old('step3_remarks') }}</textarea>
                </div>
                <div class="handle-grid-full handle-inline-actions handle-align-center">
                    <button type="button" onclick="openConfirmActionModal('issueSuspensionForm', 'Confirm before proceeding: Form 2305 should already be printed, properly filled out, and ready to be served to the student/person concerned. Continue recording this action?')">Record 2305 Action and Notify Reporter</button>
                    <a href="{{ route('admin.handlereports.print-suspension', $report->id) }}" class="btn-secondary">Download Suspension Memorandum (.docx)</a>
                    @if($report->suspension_document_path)
                        <a href="{{ asset('storage/' . $report->suspension_document_path) }}" class="btn-secondary" target="_blank">View Stored 2305 Record</a>
                    @endif
                </div>
            </form>
            @if($step3Done)
                <p class="handle-meta-line"><strong>Step 3 progress:</strong> Completed</p>
            @endif
        </div>
        @endif

        @endif

        @if(!$isTopManagementUser)
        <div class="handle-card handle-card-spaced">
            <h3 class="handle-card-title handle-title-centered">Escalation / Reassignment to Other Handler</h3>

            @if($report->escalated_to_top_management)
                <p class="handle-meta-line"><strong>Escalation status:</strong> Already escalated{{ $report->assigned_to ? ' and assigned to ' . $report->assigned_to : '' }}.</p>
            @else
                <div class="handle-inline-actions handle-small-bottom-space">
                    <button type="button" class="btn-secondary" id="toggleEscalationBtn" onclick="toggleSection('escalationPanel', 'toggleEscalationBtn', 'Show Escalation Form', 'Hide Escalation Form')">
                        {{ $showEscalationPanel ? 'Hide Escalation Form' : 'Show Escalation Form' }}
                    </button>
                </div>
                <div id="escalationPanel" style="display: {{ $showEscalationPanel ? 'block' : 'none' }};">
                <form action="{{ route('admin.handlereports.escalate-top-management', $report->id) }}" method="POST" enctype="multipart/form-data" class="handle-form-block">
                    @csrf
                    <div class="handle-grid-3">
                        <div class="form-group handle-form-group">
                            <label><strong>Escalate/Reassign To</strong></label><br>
                            <select name="escalation_target_user_id" required>
                                <option value="" selected disabled>Select target handler</option>
                                @foreach($escalationTargets as $manager)
                                    @php
                                        $managerName = trim((string) (($manager->first_name ?? '') . ' ' . ($manager->last_name ?? '')));
                                        $managerDepartment = trim((string) ($manager->department->name ?? 'No Department'));
                                        $managerOffice = trim((string) ($manager->employee_office ?? ($manager->is_top_management ? 'Top Management' : $managerDepartment)));
                                        $managerRole = $manager->is_top_management ? 'Top Management' : 'DSDO';
                                    @endphp
                                    <option value="{{ $manager->id }}" {{ old('escalation_target_user_id') == $manager->id ? 'selected' : '' }}>
                                        {{ $managerName }} ({{ $managerRole }} - {{ $managerOffice }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="handle-muted-note">You may route this case to Top Management or to another department's DSDO.</small>
                        </div>
                        <div class="form-group handle-form-group handle-grid-span-2">
                            <label><strong>Escalation Note</strong></label><br>
                            <textarea name="escalation_note" rows="3" placeholder="Optional note for case handoff">{{ old('escalation_note') }}</textarea>
                        </div>
                    </div>
                    <div class="handle-grid-3">
                        <div class="form-group handle-form-group">
                            <label><strong>Status</strong></label><br>
                            <input type="text" value="Under Review" class="readonly-field" readonly>
                        </div>
                        <div class="form-group handle-form-group">
                            <label><strong>Target Date (Optional)</strong></label><br>
                            <input type="date" name="step4_target_date" value="{{ old('step4_target_date') }}">
                        </div>
                        <div class="form-group handle-form-group">
                            <label><strong>Attachment (Optional)</strong></label><br>
                            <input type="file" name="step4_attachment" class="file-input-enhanced" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
                        </div>
                    </div>
                    <div class="form-group handle-form-group">
                        <label><strong>Handling Response Remarks</strong></label><br>
                        <textarea name="step4_remarks" rows="3" placeholder="Visible to reporter/person involved" required>{{ old('step4_remarks') }}</textarea>
                    </div>
                    <div class="handle-inline-actions">
                        <button type="submit">Submit Escalation / Reassignment</button>
                        <small class="handle-muted-note">Escalation automatically keeps this case in Under Review.</small>
                    </div>
                </form>
                </div>
            @endif
        </div>
        @endif
        @endif

        @if($isNoPersonInvolved)
        <div class="handle-card handle-card-spaced">
            <h3 class="handle-card-title handle-title-centered">Additional Handling Response (Non-Form 2303/2304/2305)</h3>
            <p class="handle-meta-line">
                No person involved was selected for this report, so only this additional handling response is available.
            </p>
            <div class="handle-inline-actions handle-small-bottom-space">
                <button
                    type="button"
                    class="btn-secondary"
                    id="toggleGeneralResponseBtn"
                    onclick="toggleSection('generalResponsePanel', 'toggleGeneralResponseBtn', 'Show Additional Handling Response', 'Hide Additional Handling Response')"
                >
                    {{ $showGeneralResponsePanel ? 'Hide Additional Handling Response' : 'Show Additional Handling Response' }}
                </button>
            </div>

            <div id="generalResponsePanel" style="display: {{ $showGeneralResponsePanel ? 'block' : 'none' }};">
                <form action="{{ route('admin.handlereports.update', $report->id) }}" method="POST" class="handle-form-block">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" value="Under Review">
                    <input type="hidden" name="department_id" value="{{ $handlerDepartmentId }}">

                    <div class="handle-grid-3">
                        <div class="form-group handle-form-group">
                            <label><strong>Department</strong></label><br>
                            <input type="text" value="{{ $handlerDepartmentLabel }}" class="readonly-field" readonly>
                        </div>

                        <div class="form-group handle-form-group">
                            <label><strong>Target Date</strong></label><br>
                            <input type="date" name="target_date" value="{{ old('target_date') }}" required>
                        </div>

                        <div class="form-group handle-form-group">
                            <label><strong>Status</strong></label><br>
                            <input type="text" value="Under Review" class="readonly-field" readonly>
                        </div>
                    </div>

                    @if($isTopManagementUser)
                        <div class="form-group handle-form-group">
                            <label><strong>Assign To (Optional)</strong></label><br>
                            <select name="assigned_to_user_id">
                                <option value="">Keep current assignment</option>
                                @foreach($handlerUsers as $handlerUser)
                                    @php
                                        $handlerName = trim((string) (($handlerUser->first_name ?? '') . ' ' . ($handlerUser->last_name ?? '')));
                                    @endphp
                                    <option value="{{ $handlerUser->id }}" {{ (string) old('assigned_to_user_id') === (string) $handlerUser->id ? 'selected' : '' }}>
                                        {{ $handlerName !== '' ? $handlerName : ($handlerUser->name ?? 'User #' . $handlerUser->id) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="form-group handle-form-group">
                        <label><strong>Handling Response Remarks</strong></label><br>
                        <textarea name="remarks" rows="3" placeholder="Add progress update or follow-up actions while case remains under review" required>{{ old('remarks') }}</textarea>
                    </div>

                    <div class="handle-inline-actions">
                        <button type="submit">Record Additional Handling Response</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </section>

    <div id="confirmActionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Action</h3>
                <span class="close" onclick="closeConfirmActionModal()">&times;</span>
            </div>
            <p id="confirmActionMessage" style="padding: 1rem 1.875rem;"></p>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeConfirmActionModal()">Cancel</button>
                <button type="button" class="btn-submit" id="confirmActionYesBtn">Yes, Continue</button>
            </div>
        </div>
    </div>

    <section id="response-history" class="handle-section handle-spacing-top">
        <div class="handle-inline-actions handle-small-bottom-space">
            <button type="button" class="btn-secondary" id="toggleResponseHistoryBtn" onclick="toggleSection('responseHistoryPanel', 'toggleResponseHistoryBtn', 'Show Response History', 'Hide Response History')">Show Response History</button>
        </div>

        <div id="responseHistoryPanel" style="display: none;">
            <h2>Response History</h2>

            @forelse($responses as $response)
                <div class="response-card">
                    <div class="response-card-head">
                        <strong>Response #{{ $response->response_number }}</strong>
                        <small>{{ $response->created_at->format('M d, Y h:i A') }}</small>
                    </div>

                    <p class="response-line response-line-first"><strong>Handled by:</strong> {{ $response->admin->name ?? 'Unknown Department Student Discipline Officer' }}</p>
                    <p class="response-line"><strong>Response type:</strong> {{ $response->response_type ?? 'Handling Response' }}</p>
                    <p class="response-line"><strong>Status:</strong> {{ \App\Models\Report::labelForStatus($response->status) }}</p>
                    <p class="response-line"><strong>Assigned to:</strong> {{ $response->assigned_to ?? 'Unassigned' }}</p>
                    <p class="response-line"><strong>Department:</strong> {{ $response->department ?? 'N/A' }}</p>
                    <p class="response-line"><strong>Target date:</strong> {{ $response->target_date ?? 'N/A' }}</p>
                    <p class="response-line"><strong>Remarks:</strong> {{ $response->remarks ?? 'No remarks' }}</p>
                    <p class="response-line"><strong>Attachment:</strong>
                        @if($response->attachment_path)
                            <a href="{{ asset('storage/' . $response->attachment_path) }}" target="_blank">View attachment</a>
                        @else
                            None
                        @endif
                    </p>
                </div>
            @empty
                <p>No responses have been recorded yet.</p>
            @endforelse
        </div>
    </section>
@endsection

@push('styles')
<style>
    .workflow-summary {
        margin-bottom: 1rem;
    }

    .field-time {
        min-width: 180px;
        letter-spacing: 0.02em;
        font-weight: 600;
    }

    .field-time-enhanced {
        border-radius: 10px;
        border: 1px solid rgba(17, 24, 39, 0.25);
        background: #ffffff;
        color: #111111;
        padding: 0.6rem 0.7rem;
        box-shadow: inset 0 0 0 1px rgba(17, 24, 39, 0.04);
    }

    .file-input-enhanced {
        width: 100%;
        border: 1px dashed rgba(255, 255, 255, 0.35);
        border-radius: 10px;
        padding: 0.45rem;
        background: rgba(18, 28, 44, 0.45);
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

    .handle-small-bottom-space {
        margin-bottom: 0.75rem;
    }

    .handle-title-centered {
        text-align: center;
    }

    input[name="suspension_days"] {
        max-width: 220px;
        font-weight: 600;
    }

    .readonly-field {
        background: #ffffff;
        border: 1px solid rgba(17, 24, 39, 0.25);
        color: #111111 !important;
        -webkit-text-fill-color: #111111;
        opacity: 1;
        caret-color: #111111;
        cursor: not-allowed;
    }

    .readonly-field:focus,
    .readonly-field[readonly] {
        color: #111111 !important;
        -webkit-text-fill-color: #111111;
        opacity: 1;
    }

    .readonly-field::selection {
        background: #bfdbfe;
        color: #111111;
    }

    .readonly-field::-moz-selection {
        background: #bfdbfe;
        color: #111111;
    }

    /* Keep text readable when browser renders white form fields. */
    .handle-form-block input:not([type="file"]):not(.readonly-field):not(.field-time-enhanced),
    .handle-form-block select,
    .handle-form-block textarea,
    .handle-grid-3 input:not([type="file"]):not(.readonly-field):not(.field-time-enhanced),
    .handle-grid-3 select,
    .handle-grid-3 textarea {
        background: #ffffff;
        color: #111111;
        caret-color: #111111;
    }

    .handle-form-block input:not([type="file"]):not(.readonly-field):not(.field-time-enhanced)::placeholder,
    .handle-form-block textarea::placeholder,
    .handle-grid-3 input:not([type="file"]):not(.readonly-field):not(.field-time-enhanced)::placeholder,
    .handle-grid-3 textarea::placeholder {
        color: #4b5563;
    }

    .handle-form-block input[type="date"]::-webkit-datetime-edit,
    .handle-form-block input[type="time"]::-webkit-datetime-edit,
    .handle-grid-3 input[type="date"]::-webkit-datetime-edit,
    .handle-grid-3 input[type="time"]::-webkit-datetime-edit {
        color: #111111;
    }

    .handle-form-block select option,
    .handle-grid-3 select option {
        color: #111111;
        background: #ffffff;
    }

    .handle-grid-span-2 {
        grid-column: span 2;
    }

    .handle-section {
        margin-bottom: 1.75rem;
    }

    #handling-forms .handle-card {
        margin-top: 1.15rem;
        padding: 1.1rem 1.2rem;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 12px;
    }

    #handling-forms .handle-grid-3 {
        row-gap: 0.9rem;
    }

    #handling-forms .handle-form-group {
        margin-bottom: 0.85rem;
    }

    .handle-flow-guide {
        margin-top: 1rem;
        margin-bottom: 1.25rem;
        padding: 1rem 1.1rem;
        border: 1px solid rgba(29, 78, 216, 0.35);
        border-radius: 12px;
        background: rgba(29, 78, 216, 0.08);
    }

    .handle-flow-guide-title {
        margin: 0 0 0.55rem;
        font-size: 1.02rem;
        font-weight: 700;
    }

    .handle-flow-list {
        margin: 0;
        padding-left: 1.2rem;
        line-height: 1.65;
    }

    .handle-step-chip {
        display: inline-block;
        margin-right: 0.5rem;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        background: rgba(15, 118, 110, 0.2);
        border: 1px solid rgba(15, 118, 110, 0.45);
        color: #d7fff8;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        vertical-align: middle;
    }

    .handle-step-chip-optional {
        background: rgba(217, 119, 6, 0.16);
        border-color: rgba(217, 119, 6, 0.5);
        color: #ffe9c5;
    }

    .evidence-actions {
        margin-top: 0.6rem;
        display: flex;
        gap: 0.55rem;
        flex-wrap: wrap;
    }

    .evidence-actions .btn-secondary {
        text-decoration: none;
    }

    @media (max-width: 900px) {
        .handle-grid-span-2 {
            grid-column: span 1;
        }

        #handling-forms .handle-card {
            padding: 0.95rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    let confirmActionTargetFormId = null;

    function toggleSection(sectionId, buttonId, showLabel, hideLabel) {
        const section = document.getElementById(sectionId);
        const button = document.getElementById(buttonId);

        if (!section || !button) {
            return;
        }

        const isHidden = section.style.display === 'none' || section.style.display === '';
        section.style.display = isHidden ? 'block' : 'none';
        button.textContent = isHidden ? hideLabel : showLabel;
    }

    function openConfirmActionModal(formId, message) {
        confirmActionTargetFormId = formId;
        const messageEl = document.getElementById('confirmActionMessage');
        if (messageEl) {
            messageEl.textContent = message;
        }
        document.getElementById('confirmActionModal').style.display = 'flex';
    }

    function closeConfirmActionModal() {
        document.getElementById('confirmActionModal').style.display = 'none';
        confirmActionTargetFormId = null;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('confirmActionModal');
        if (modal) {
            document.body.appendChild(modal);
        }

        const yesBtn = document.getElementById('confirmActionYesBtn');
        if (yesBtn) {
            yesBtn.addEventListener('click', function () {
                if (!confirmActionTargetFormId) {
                    closeConfirmActionModal();
                    return;
                }

                const targetForm = document.getElementById(confirmActionTargetFormId);
                if (targetForm) {
                    targetForm.submit();
                }
            });
        }
    });

    window.addEventListener('click', function (event) {
        if (event.target.classList.contains('modal')) {
            closeConfirmActionModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeConfirmActionModal();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        const actionSelect = document.querySelector('select[name="disciplinary_action"]');
        const suspensionDaysInput = document.querySelector('input[name="suspension_days"]');

        if (!actionSelect || !suspensionDaysInput) {
            return;
        }

        const syncSuspensionDaysState = function () {
            const isSuspension = actionSelect.value === 'Suspension';
            suspensionDaysInput.required = isSuspension;

            if (!isSuspension) {
                suspensionDaysInput.value = '';
                suspensionDaysInput.setAttribute('disabled', 'disabled');
                suspensionDaysInput.placeholder = 'Not required for Dismissal';
            } else {
                suspensionDaysInput.removeAttribute('disabled');
                suspensionDaysInput.placeholder = 'Required for Suspension';
            }
        };

        syncSuspensionDaysState();
        actionSelect.addEventListener('change', syncSuspensionDaysState);

    });
</script>
@endpush
