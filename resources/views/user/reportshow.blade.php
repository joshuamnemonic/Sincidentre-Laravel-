@extends('layouts.app')

@section('title', 'Report Details - Sincidentre')

@section('content')
    @php
        $currentUser = auth()->user();
        $userEmail = strtolower(trim((string) ($currentUser->email ?? '')));
        $involvedEmails = [];
        if (!empty($report->person_email_address)) {
            $involvedEmails[] = strtolower(trim((string) $report->person_email_address));
        }
        if (is_array($report->additional_persons)) {
            foreach ($report->additional_persons as $person) {
                $email = strtolower(trim((string) ($person['email_address'] ?? '')));
                if ($email !== '') {
                    $involvedEmails[] = $email;
                }
            }
        }
        $involvedEmails = array_values(array_unique($involvedEmails));
        $isReporter = auth()->check() && auth()->id() === $report->user_id;
        $isInvolvedByEmail = auth()->check() && in_array($userEmail, $involvedEmails, true);
        $canConfirmHearingNotice = auth()->check() && (
            $isReporter ||
            $isInvolvedByEmail
        );

        $confirmationAction = $isReporter
            ? 'Reporter Hearing Notice Confirmed'
            : 'Involved Party Hearing Notice Confirmed';

        $existingConfirmation = auth()->check()
            ? $report->activities
                ->where('user_id', auth()->id())
                ->where('action', $confirmationAction)
                ->sortByDesc('created_at')
                ->first()
            : null;

        $hasConfirmedHearingNotice = (bool) $existingConfirmation;

        $incidentAdditionalSheets = [];
        if (!empty($report->incident_additional_sheets)) {
            $decodedIncidentSheets = json_decode((string) $report->incident_additional_sheets, true);
            if (is_array($decodedIncidentSheets)) {
                $incidentAdditionalSheets = $decodedIncidentSheets;
            }
        }

        $personInvolvementLabel = [
            'known'   => 'Yes, known identity',
            'unknown' => 'Yes, unknown identity',
            'none'    => 'No person involved',
            'unsure'  => 'Not sure yet',
        ][(string) $report->person_involvement] ?? ucfirst((string) $report->person_involvement);

        $hasPersonData = !empty($report->person_involvement)
            || !empty($report->person_full_name)
            || !empty($report->unknown_person_details)
            || !empty($report->technical_facility_details)
            || (is_array($report->additional_persons) && count($report->additional_persons) > 0);
    @endphp

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <header>
        <h1>Report Details</h1>
        <p>Report ID: #{{ $report->id }}</p>
    </header>

    {{-- ================================================================
         REPORT INFORMATION — Collapsible Groups
         ================================================================ --}}
    <section id="report-details-view" class="animate">
        <h2>Report Information</h2>

        {{-- ── GROUP 1: Incident Overview (open by default) ── --}}
        <div class="detail-group">
            <button class="detail-group-toggle" aria-expanded="true" aria-controls="grp-overview-body">
                <span class="detail-group-icon">📋</span>
                <span class="detail-group-label">Incident Overview</span>
                <span class="detail-group-chevron">▾</span>
            </button>
            <div class="detail-group-body" id="grp-overview-body">
                <div class="details-grid">

                    <div class="detail-item full-width">
                        <label>Category</label>
                        @if($report->category)
                            <p>
                                {{ strtoupper($report->category->main_category_code) }} —
                                {{ $report->category->main_category_name }}<br>
                                <span style="font-size:0.9rem;opacity:0.85;">{{ $report->category->name }}</span>
                            </p>
                            <small>Classification:
                                <span class="classification-badge {{ strtolower($report->category->classification) }}">
                                    {{ $report->category->classification }}
                                </span>
                            </small>
                        @else
                            <p>N/A</p>
                        @endif
                    </div>

                    <div class="detail-item">
                        <label>Date of Incident</label>
                        <p>{{ \Carbon\Carbon::parse($report->incident_date)->format('F d, Y') }}</p>
                    </div>

                    <div class="detail-item">
                        <label>Time of Incident</label>
                        <p>{{ \Carbon\Carbon::parse($report->incident_time)->format('h:i A') }}</p>
                    </div>

                    <div class="detail-item">
                        <label>Location</label>
                        <p>{{ $report->location }}</p>
                    </div>

                    @if($report->location_details)
                    <div class="detail-item">
                        <label>Please Specify</label>
                        <p>{{ $report->location_details }}</p>
                    </div>
                    @endif

                    <div class="detail-item">
                        <label>Status</label>
                        <p>
                            <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                                {{ ucfirst($report->status) }}
                            </span>
                        </p>
                    </div>

                    <div class="detail-item full-width">
                        <label>Description</label>
                        <p>{{ $report->description }}</p>
                    </div>

                    @if(count($incidentAdditionalSheets) > 0)
                    <div class="detail-item full-width">
                        <label>Incident Additional Sheets</label>
                        <ul class="detail-file-list">
                            @foreach($incidentAdditionalSheets as $sheetPath)
                                <li>
                                    <a href="{{ asset('storage/' . $sheetPath) }}" target="_blank" class="file-link">
                                        📄 {{ basename($sheetPath) }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                </div>
            </div>
        </div>

        {{-- ── GROUP 2: Person/s Involved (collapsed by default) ── --}}
        @if($hasPersonData)
        <div class="detail-group">
            <button class="detail-group-toggle" aria-expanded="false" aria-controls="grp-persons-body">
                <span class="detail-group-icon">👤</span>
                <span class="detail-group-label">Person/s Involved</span>
                <span class="detail-group-chevron">▾</span>
            </button>
            <div class="detail-group-body collapsed" id="grp-persons-body">
                <div class="details-grid">

                    @if(!empty($report->person_involvement))
                    <div class="detail-item">
                        <label>Person Involvement</label>
                        <p>{{ $personInvolvementLabel }}</p>
                    </div>
                    @endif

                    @if(!empty($report->person_full_name))
                    <div class="detail-item">
                        <label>Full Name</label>
                        <p>{{ $report->person_full_name }}</p>
                    </div>
                    @endif

                    @if(!empty($report->person_college_department))
                    <div class="detail-item">
                        <label>College / Department</label>
                        <p>{{ $report->person_college_department }}</p>
                    </div>
                    @endif

                    @if(!empty($report->person_role))
                    <div class="detail-item">
                        <label>Role</label>
                        <p>{{ $report->person_role }}</p>
                    </div>
                    @endif

                    @if(!empty($report->person_contact_number))
                    <div class="detail-item">
                        <label>Contact Number</label>
                        <p>{{ $report->person_contact_number }}</p>
                    </div>
                    @endif

                    @if(!empty($report->person_email_address))
                    <div class="detail-item">
                        <label>Email Address</label>
                        <p>{{ $report->person_email_address }}</p>
                    </div>
                    @endif

                    @if(!empty($report->unknown_person_details))
                    <div class="detail-item full-width">
                        <label>Unknown Person Details</label>
                        <p>{{ $report->unknown_person_details }}</p>
                    </div>
                    @endif

                    @if(!empty($report->technical_facility_details))
                    <div class="detail-item full-width">
                        <label>Technical / Facility Details</label>
                        <p>{{ $report->technical_facility_details }}</p>
                    </div>
                    @endif

                    <div class="detail-item">
                        <label>Multiple Persons Involved?</label>
                        <p>{{ $report->person_has_multiple ? 'Yes' : 'No' }}</p>
                    </div>

                    @if(is_array($report->additional_persons) && count($report->additional_persons) > 0)
                    <div class="detail-item full-width">
                        <label>Additional Persons</label>
                        <div class="person-cards">
                            @foreach($report->additional_persons as $index => $person)
                            <div class="person-card">
                                <div class="person-card-header">Person {{ $index + 2 }}</div>
                                <div class="person-card-body">
                                    @if(!empty($person['full_name']))<div><span>Name:</span> {{ $person['full_name'] }}</div>@endif
                                    @if(!empty($person['college_department']))<div><span>Dept:</span> {{ $person['college_department'] }}</div>@endif
                                    @if(!empty($person['role']))<div><span>Role:</span> {{ $person['role'] }}</div>@endif
                                    @if(!empty($person['contact_number']))<div><span>Contact:</span> {{ $person['contact_number'] }}</div>@endif
                                    @if(!empty($person['email_address']))<div><span>Email:</span> {{ $person['email_address'] }}</div>@endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                </div>
            </div>
        </div>
        @endif

        {{-- ── GROUP 3: Witnesses (collapsed by default) ── --}}
        @if($report->has_witnesses || (is_array($report->witness_details) && count($report->witness_details) > 0))
        <div class="detail-group">
            <button class="detail-group-toggle" aria-expanded="false" aria-controls="grp-witnesses-body">
                <span class="detail-group-icon">👁️</span>
                <span class="detail-group-label">Witnesses</span>
                <span class="detail-group-chevron">▾</span>
            </button>
            <div class="detail-group-body collapsed" id="grp-witnesses-body">
                <div class="details-grid">

                    <div class="detail-item">
                        <label>Were There Witnesses?</label>
                        <p>{{ $report->has_witnesses ? 'Yes' : 'No' }}</p>
                    </div>

                    @if(is_array($report->witness_details) && count($report->witness_details) > 0)
                    <div class="detail-item full-width">
                        <label>Witness Details</label>
                        <div class="person-cards">
                            @foreach($report->witness_details as $index => $witness)
                            <div class="person-card">
                                <div class="person-card-header">Witness {{ $index + 1 }}</div>
                                <div class="person-card-body">
                                    @php $wName = $witness['name'] ?? ($witness['full_name'] ?? null); @endphp
                                    @if($wName)<div><span>Name:</span> {{ $wName }}</div>@endif
                                    @if(!empty($witness['address']))<div><span>Address:</span> {{ $witness['address'] }}</div>@endif
                                    @if(!empty($witness['contact_number']))<div><span>Contact:</span> {{ $witness['contact_number'] }}</div>@endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                </div>
            </div>
        </div>
        @endif

        {{-- ── GROUP 4: Informant (collapsed by default) ── --}}
        @if(!empty($report->informant_full_name) || !empty($report->informant_email_address))
        <div class="detail-group">
            <button class="detail-group-toggle" aria-expanded="false" aria-controls="grp-informant-body">
                <span class="detail-group-icon">📝</span>
                <span class="detail-group-label">Informant</span>
                <span class="detail-group-chevron">▾</span>
            </button>
            <div class="detail-group-body collapsed" id="grp-informant-body">
                <div class="details-grid">

                    @if(!empty($report->informant_full_name))
                    <div class="detail-item">
                        <label>Full Name</label>
                        <p>{{ $report->informant_full_name }}</p>
                    </div>
                    @endif

                    @if(!empty($report->informant_college_department))
                    <div class="detail-item">
                        <label>College / Department</label>
                        <p>{{ $report->informant_college_department }}</p>
                    </div>
                    @endif

                    @if(!empty($report->informant_role))
                    <div class="detail-item">
                        <label>Role</label>
                        <p>{{ $report->informant_role }}</p>
                    </div>
                    @endif

                    @if(!empty($report->informant_contact_number))
                    <div class="detail-item">
                        <label>Contact Number</label>
                        <p>{{ $report->informant_contact_number }}</p>
                    </div>
                    @endif

                    @if(!empty($report->informant_email_address))
                    <div class="detail-item">
                        <label>Email Address</label>
                        <p>{{ $report->informant_email_address }}</p>
                    </div>
                    @endif

                </div>
            </div>
        </div>
        @endif

        {{-- ── Submitted At ── --}}
        @if($report->submitted_at)
        <div class="detail-submitted-at">
            🕐 Submitted on {{ $report->submitted_at->format('F d, Y \a\t h:i A') }}
        </div>
        @endif

    </section>


    {{-- ================================================================
         SUBMITTED EVIDENCE
         ================================================================ --}}
    <section id="evidence-section" class="animate">
        @php
            $evidences = $report->evidence ? json_decode($report->evidence, true) : [];
            $evidences = is_array($evidences) ? $evidences : [];
            $evidenceCount = count($evidences);
        @endphp

        <h2>📎 Submitted Evidence @if($evidenceCount > 0)<span class="evidence-count-badge">{{ $evidenceCount }}</span>@endif</h2>

        @if($evidenceCount > 0)
            <div class="evidence-grid">
                @foreach($evidences as $file)
                    @php
                        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        $fileUrl   = asset('storage/' . $file);
                        $fileName  = basename($file);
                        $isImage   = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true);
                        $isVideo   = in_array($extension, ['mp4', 'webm', 'ogg'], true);
                        $isPdf     = $extension === 'pdf';
                        $typeIcon  = $isImage ? '🖼️' : ($isVideo ? '🎬' : ($isPdf ? '📄' : '📁'));
                    @endphp

                    @if($isImage)
                        <button type="button" class="evidence-item evidence-trigger"
                            data-evidence-type="image" data-evidence-url="{{ $fileUrl }}" data-evidence-name="{{ $fileName }}"
                            aria-label="View image: {{ $fileName }}">
                            <img src="{{ $fileUrl }}" alt="{{ $fileName }}">
                            <div class="evidence-item-label">
                                <span class="evidence-type-icon">{{ $typeIcon }}</span>
                                <span class="evidence-file-name">{{ $fileName }}</span>
                            </div>
                        </button>
                    @elseif($isVideo)
                        <button type="button" class="evidence-item evidence-trigger evidence-item-nothumb"
                            data-evidence-type="video" data-evidence-url="{{ $fileUrl }}" data-evidence-name="{{ $fileName }}"
                            aria-label="View video: {{ $fileName }}">
                            <div class="evidence-nothumb-icon">🎬</div>
                            <div class="evidence-item-label">
                                <span class="evidence-type-icon">{{ $typeIcon }}</span>
                                <span class="evidence-file-name">{{ $fileName }}</span>
                            </div>
                        </button>
                    @elseif($isPdf)
                        <button type="button" class="evidence-item evidence-trigger evidence-item-nothumb"
                            data-evidence-type="pdf" data-evidence-url="{{ $fileUrl }}" data-evidence-name="{{ $fileName }}"
                            aria-label="View PDF: {{ $fileName }}">
                            <div class="evidence-nothumb-icon">📄</div>
                            <div class="evidence-item-label">
                                <span class="evidence-type-icon">{{ $typeIcon }}</span>
                                <span class="evidence-file-name">{{ $fileName }}</span>
                            </div>
                        </button>
                    @else
                        <a href="{{ $fileUrl }}" target="_blank" rel="noopener noreferrer"
                            class="evidence-item evidence-item-nothumb" aria-label="Open file: {{ $fileName }}">
                            <div class="evidence-nothumb-icon">📁</div>
                            <div class="evidence-item-label">
                                <span class="evidence-type-icon">{{ $typeIcon }}</span>
                                <span class="evidence-file-name">{{ $fileName }}</span>
                            </div>
                        </a>
                    @endif
                @endforeach
            </div>
        @else
            <p class="no-data">No evidence was submitted for this report.</p>
        @endif
    </section>

    <div class="evidence-modal" id="evidenceModal" aria-hidden="true">
        <div class="evidence-modal-backdrop" data-evidence-close></div>
        <div class="evidence-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="evidenceModalTitle">
            <div class="evidence-modal-header">
                <h3 id="evidenceModalTitle">Submitted Evidence</h3>
                <button type="button" class="evidence-modal-close" data-evidence-close aria-label="Close evidence viewer">&times;</button>
            </div>
            <div class="evidence-modal-body" id="evidenceModalBody"></div>
            <div class="evidence-modal-footer">
                <a id="evidenceModalOpenNewTab" class="file-link" target="_blank" rel="noopener noreferrer">Open file in new tab</a>
            </div>
        </div>
    </div>

    {{-- ================================================================
         CASE RECORDS AND DISCIPLINARY ACTIONS
         ================================================================ --}}
    <section id="case-records" class="animate">
        <h2>Report Records and Disciplinary Actions</h2>

        @php
            $hasCaseRecords = $report->hearing_date || $report->hearing_time || $report->hearing_venue
                           || $report->reprimand_document_path
                           || $report->suspension_document_path;
        @endphp

        @if($hasCaseRecords)
            <div class="case-records-list">
                @if($report->hearing_date || $report->hearing_time || $report->hearing_venue)
                <div class="case-record-card hearing">
                    <div class="case-record-icon">📅</div>
                    <div class="case-record-body">
                        <div class="case-record-title">
                            Hearing Notification
                            <span class="case-record-tag tag-hearing">Call Notice</span>
                        </div>
                        <div class="case-record-meta">
                            @if($report->hearing_date)
                            <div class="case-meta-item"><span>Date</span>{{ $report->hearing_date->format('F d, Y') }}</div>
                            @endif
                            @if($report->hearing_time)
                            <div class="case-meta-item"><span>Time</span>{{ \Carbon\Carbon::parse($report->hearing_time)->format('h:i A') }}</div>
                            @endif
                            @if($report->hearing_venue)
                            <div class="case-meta-item"><span>Venue</span>{{ $report->hearing_venue }}</div>
                            @endif
                        </div>
                        @if($canConfirmHearingNotice && !$hasConfirmedHearingNotice)
                            <form action="{{ route('reports.confirmHearingNotice', $report->id) }}" method="POST" class="case-record-action-form">
                                @csrf
                                <button type="submit" class="case-record-btn btn-confirm">✅ Confirm Receipt of Hearing Notice</button>
                            </form>
                        @elseif($canConfirmHearingNotice && $hasConfirmedHearingNotice)
                            <div class="case-record-confirmed">
                                ✅ Hearing notice confirmed
                                @if($existingConfirmation?->created_at)
                                    <span>{{ $existingConfirmation->created_at->format('M d, Y h:i A') }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                @endif

                @if($report->reprimand_document_path)
                <div class="case-record-card reprimand">
                    <div class="case-record-icon">📄</div>
                    <div class="case-record-body">
                        <div class="case-record-title">
                            Written Reprimand Issued
                            <span class="case-record-tag tag-reprimand">OSD Form 2304</span>
                        </div>
                        <div class="case-record-meta">
                            <div class="case-meta-item">
                                <span>Acknowledgment</span>
                                @if($report->student_acknowledged_reprimand_at)
                                    <span class="text-green">Acknowledged on {{ $report->student_acknowledged_reprimand_at->format('M d, Y h:i A') }}</span>
                                @else
                                    <span class="text-amber">Pending acknowledgment</span>
                                @endif
                            </div>
                        </div>
                        @if(auth()->check() && auth()->id() === $report->user_id && !$report->student_acknowledged_reprimand_at)
                            <form action="{{ route('reports.acknowledge-reprimand', $report->id) }}" method="POST" class="case-record-action-form">
                                @csrf
                                <button type="submit" class="case-record-btn btn-acknowledge">✍️ Acknowledge Receipt of Form 2304</button>
                            </form>
                        @endif
                    </div>
                </div>
                @endif

                @if($report->suspension_document_path)
                <div class="case-record-card suspension">
                    <div class="case-record-icon">⛔</div>
                    <div class="case-record-body">
                        <div class="case-record-title">
                            {{ $report->disciplinary_action ?? 'Disciplinary Action Issued' }}
                            <span class="case-record-tag tag-suspension">OSD Form 2305</span>
                        </div>
                        <div class="case-record-meta">
                            @if($report->suspension_effective_date)
                            <div class="case-meta-item"><span>Effective Date</span>{{ $report->suspension_effective_date->format('F d, Y') }}</div>
                            @endif
                            @if($report->suspension_days)
                            <div class="case-meta-item"><span>Duration</span>{{ $report->suspension_days }} day{{ $report->suspension_days > 1 ? 's' : '' }}</div>
                            @endif
                        </div>
                        <a href="{{ asset('storage/' . $report->suspension_document_path) }}" target="_blank" class="case-record-btn btn-view-form">
                            📂 Open Form 2305
                        </a>
                    </div>
                </div>
                @endif

            </div>
        @else
            <p class="no-data">No disciplinary actions have been issued for this case yet.</p>
        @endif
    </section>

    
    {{-- ================================================================
         RESPONSE TIMELINE
         ================================================================ --}}
    <section id="admin-response-timeline" class="animate">
        @php
            $responses  = $report->responses->sortByDesc('response_number');
            $activities = $report->activities->sortByDesc('created_at');
        @endphp

        @if($report->status === 'Rejected' && $report->rejection_reason)
            <div class="rejection-banner">
                <div class="rejection-banner-title">🚫 Report Rejected</div>
                <div class="rejection-banner-reason">{{ $report->rejection_reason }}</div>
                <div class="rejection-banner-date">{{ $report->updated_at->format('M d, Y h:i A') }}</div>
            </div>
        @endif

        {{-- Response History Collapsible --}}
        @if($responses->count() > 0)
            <div class="detail-group">
                <button class="detail-group-toggle" aria-expanded="false" aria-controls="responseHistoryBody">
                    <span class="detail-group-icon">📋</span>
                    <span class="detail-group-label">Response History ({{ $responses->count() }} responses)</span>
                    <span class="detail-group-chevron">▾</span>
                </button>
                <div class="detail-group-body collapsed" id="responseHistoryBody">
                    <div class="timeline">
                        @foreach($responses as $response)
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <strong>Response #{{ $response->response_number }}</strong>
                                        <span class="timeline-date">{{ $response->created_at->format('M d, Y h:i A') }}</span>
                                    </div>
                                    <div class="timeline-status-change">
                                        Status: <span class="status {{ strtolower(str_replace(' ', '-', $response->status)) }}">{{ ucfirst($response->status) }}</span>
                                    </div>
                                    @if(!empty($response->response_type))
                                    <div class="timeline-remarks"><strong>Response Type:</strong> {{ $response->response_type }}</div>
                                    @endif
                                    @if(!empty($response->assigned_to))
                                    <div class="timeline-remarks"><strong>Assigned to:</strong> {{ $response->assigned_to }}</div>
                                    @endif
                                    @if(!empty($response->department))
                                    <div class="timeline-remarks"><strong>Department:</strong> {{ $response->department }}</div>
                                    @endif
                                    @if($response->target_date)
                                    <div class="timeline-remarks"><strong>Target date:</strong> {{ \Carbon\Carbon::parse($response->target_date)->format('F d, Y') }}</div>
                                    @endif
                                    @if(!empty($response->remarks))
                                    <div class="timeline-remarks"><strong>Remarks:</strong> {{ $response->remarks }}</div>
                                    @endif
                                    @if($response->attachment_path)
                                    <div class="timeline-remarks"><strong>Attachment:</strong> <a href="{{ asset('storage/' . $response->attachment_path) }}" target="_blank" class="file-link">📎 View attachment</a></div>
                                    @endif
                                    @if(!empty($response->admin->name ?? null))
                                    <div class="timeline-footer">By: <strong>{{ $response->admin->name }}</strong></div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        @elseif($report->assigned_to || $report->department || $report->target_date || $report->remarks)
            <div class="detail-group">
                <button class="detail-group-toggle" aria-expanded="false" aria-controls="initialHandlingBody">
                    <span class="detail-group-icon">📋</span>
                    <span class="detail-group-label">Handling Details</span>
                    <span class="detail-group-chevron">▾</span>
                </button>
                <div class="detail-group-body collapsed" id="initialHandlingBody">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <strong>Initial Handling Details</strong>
                                    <span class="timeline-date">{{ $report->updated_at->format('M d, Y h:i A') }}</span>
                                </div>
                                <div class="timeline-status-change">
                                    Status: <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">{{ ucfirst($report->status) }}</span>
                                </div>
                                @if(!empty($report->assigned_to))<div class="timeline-remarks"><strong>Assigned to:</strong> {{ $report->assigned_to }}</div>@endif
                                @if(!empty($report->department))<div class="timeline-remarks"><strong>Department:</strong> {{ $report->department }}</div>@endif
                                @if($report->target_date)<div class="timeline-remarks"><strong>Target date:</strong> {{ \Carbon\Carbon::parse($report->target_date)->format('F d, Y') }}</div>@endif
                                @if(!empty($report->remarks))<div class="timeline-remarks"><strong>Remarks:</strong> {{ $report->remarks }}</div>@endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        @elseif($activities->count() > 0)
            <div class="timeline">
                @foreach($activities as $activity)
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <strong>{{ $activity->action }}</strong>
                                <span class="timeline-date">{{ $activity->created_at->format('M d, Y h:i A') }}</span>
                            </div>
                            @if($activity->old_status && $activity->new_status)
                                <div class="timeline-status-change">
                                    Status changed:
                                    <span class="old-status">{{ ucfirst($activity->old_status) }}</span> →
                                    <span class="new-status">{{ ucfirst($activity->new_status) }}</span>
                                </div>
                            @endif
                            @if(!empty($activity->remarks))
                                <div class="timeline-remarks"><strong>Remarks:</strong> {{ $activity->remarks }}</div>
                            @endif
                            @if(!empty($activity->admin->name ?? null))
                            <div class="timeline-footer">By: <strong>{{ $activity->admin->name }}</strong></div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

        @else
            <p class="no-data">Your report is still pending review. No officer response yet.</p>
        @endif

    </section>
@endsection

@push('styles')
<style>
    /* ── Detail Groups ── */
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
        padding: 1.1rem 1.5rem;
        background: transparent;
        border: none;
        color: #fff;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        text-align: left;
    }

    .detail-group-toggle:hover {
        background: rgba(255,255,255,0.07);
    }

    .detail-group-icon {
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .detail-group-label {
        flex: 1;
        color: rgba(255,255,255,0.95);
    }

    .detail-group-chevron {
        font-size: 1rem;
        color: rgba(255,255,255,0.6);
        flex-shrink: 0;
        display: inline-block;
    }

    .detail-group-toggle[aria-expanded="true"] .detail-group-chevron {
        transform: rotate(180deg);
    }

    /* ── Group Body ── */
    .detail-group-body {
        overflow: hidden;
        max-height: 9999px;
    }

    .detail-group-body.collapsed {
        max-height: 0;
    }

    /* ── Person / Witness Cards ── */
    .person-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 0.75rem;
        margin-top: 0.5rem;
    }

    .person-card {
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.15);
        border-radius: 0.6rem;
        overflow: hidden;
    }

    .person-card-header {
        background: rgba(255,255,255,0.12);
        padding: 0.5rem 0.875rem;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: rgba(255,255,255,0.8);
    }

    .person-card-body {
        padding: 0.75rem 0.875rem;
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        font-size: 0.9rem;
        color: rgba(255,255,255,0.9);
    }

    .person-card-body span {
        font-weight: 600;
        color: rgba(255,255,255,0.65);
        font-size: 0.8rem;
        margin-right: 0.25rem;
    }

    /* ── Classification Badge ── */
    .classification-badge {
        display: inline-block;
        padding: 0.2rem 0.6rem;
        border-radius: 2rem;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .classification-badge.minor { background: #fbbf24; color: #78350f; }
    .classification-badge.major { background: #f97316; color: #fff; }
    .classification-badge.grave { background: #ef4444; color: #fff; }

    /* ── File List ── */
    .detail-file-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .detail-file-list li a {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        color: #93c5fd;
        text-decoration: none;
        font-size: 0.9rem;
    }

    .detail-file-list li a:hover {
        text-decoration: underline;
    }

    /* ── Submitted At ── */
    .detail-submitted-at {
        padding: 1rem 1.5rem;
        font-size: 0.85rem;
        color: rgba(255,255,255,0.55);
        border-top: 1px solid rgba(255,255,255,0.1);
        text-align: right;
    }

    /* ── Evidence Modal (unchanged from original) ── */
    .evidence-trigger {
        padding: 0;
        width: 100%;
        text-align: left;
        background: rgba(255, 255, 255, 0.1);
        border: 2px solid rgba(255, 255, 255, 0.2);
    }

    .evidence-item-file {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 120px;
        text-decoration: none;
        color: #ffffff;
        padding: 1rem;
    }

    .evidence-file-meta {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        width: 100%;
    }

    .evidence-file-meta strong {
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.85);
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }

    .evidence-file-meta span {
        font-size: 0.95rem;
        color: #ffffff;
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .evidence-modal {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        padding: 1rem;
    }

    .evidence-modal.show { display: flex; }

    .evidence-modal-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.75);
    }

    .evidence-modal-dialog {
        position: relative;
        width: min(980px, 100%);
        max-height: 90vh;
        background: #102a73;
        border: 1px solid rgba(255, 255, 255, 0.25);
        border-radius: 0.85rem;
        overflow: hidden;
        z-index: 1;
        display: flex;
        flex-direction: column;
    }

    .evidence-modal-header,
    .evidence-modal-footer {
        padding: 0.85rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    }

    .evidence-modal-footer {
        border-top: 1px solid rgba(255, 255, 255, 0.12);
        border-bottom: 0;
    }

    .evidence-modal-header h3 {
        margin: 0;
        color: #ffffff;
        font-size: 1rem;
        font-weight: 700;
    }

    .evidence-modal-close {
        border: 0;
        background: transparent;
        color: #ffffff;
        font-size: 1.8rem;
        line-height: 1;
        cursor: pointer;
        padding: 0;
    }

    .evidence-modal-body {
        padding: 0.8rem;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: auto;
    }

    .evidence-modal-body img,
    .evidence-modal-body video,
    .evidence-modal-body iframe {
        max-width: 100%;
        max-height: calc(90vh - 170px);
        border: 0;
        border-radius: 0.4rem;
        background: #0a163e;
    }

    body.evidence-modal-open { overflow: hidden; }

    /* ── Mobile ── */
    @media (max-width: 768px) {
        .detail-group-toggle {
            padding: 1rem;
            font-size: 0.95rem;
        }

        .details-grid {
            gap: 1rem;
            padding: 1rem;
        }

        .person-cards {
            grid-template-columns: 1fr;
        }

        .detail-submitted-at {
            text-align: left;
            padding: 0.875rem 1rem;
        }

        .evidence-modal { padding: 0.5rem; }
        .evidence-modal-dialog { max-height: 94vh; }

        .evidence-modal-header,
        .evidence-modal-footer { padding: 0.7rem 0.8rem; }

        .evidence-modal-body { padding: 0.5rem; }

        .evidence-modal-body img,
        .evidence-modal-body video,
        .evidence-modal-body iframe { max-height: calc(94vh - 145px); }
    }

    @media (max-width: 480px) {
        .detail-group-toggle {
            padding: 0.875rem;
            font-size: 0.9rem;
        }

        .details-grid {
            padding: 0.875rem;
            gap: 0.875rem;
        }
    }

    /* ================================================================
       CASE RECORDS CARDS
       ================================================================ */
    .case-records-list {
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .case-record-card {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        transition: background 0.2s;
    }

    .case-record-card:last-child {
        border-bottom: none;
    }

    .case-record-card:hover {
        background: rgba(255,255,255,0.04);
    }

    .case-record-card.hearing  { border-left: 4px solid #60a5fa; }
    .case-record-card.reprimand { border-left: 4px solid #fbbf24; }
    .case-record-card.suspension { border-left: 4px solid #ef4444; }

    .case-record-icon {
        font-size: 1.75rem;
        flex-shrink: 0;
        margin-top: 0.1rem;
    }

    .case-record-body {
        flex: 1;
        min-width: 0;
    }

    .case-record-title {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
        font-size: 1rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 0.75rem;
    }

    .case-record-tag {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 0.2rem 0.55rem;
        border-radius: 2rem;
    }

    .tag-hearing    { background: rgba(96,165,250,0.2);  color: #93c5fd; border: 1px solid rgba(96,165,250,0.3); }
    .tag-reprimand  { background: rgba(251,191,36,0.2);  color: #fde68a; border: 1px solid rgba(251,191,36,0.3); }
    .tag-suspension { background: rgba(239,68,68,0.2);   color: #fca5a5; border: 1px solid rgba(239,68,68,0.3); }

    .case-record-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem 1.5rem;
        margin-bottom: 0.75rem;
    }

    .case-meta-item {
        display: flex;
        flex-direction: column;
        font-size: 0.9rem;
        color: rgba(255,255,255,0.9);
    }

    .case-meta-item span:first-child {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: rgba(255,255,255,0.5);
        margin-bottom: 0.15rem;
    }

    .case-record-action-form {
        padding: 0 !important;
        border: none !important;
        margin: 0 !important;
    }

    .case-record-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.6rem 1.1rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        border: none;
        text-decoration: none;
        margin-top: 0.25rem;
    }

    .btn-confirm    { background: rgba(16,185,129,0.2); color: #6ee7b7; border: 1px solid rgba(16,185,129,0.35); }
    .btn-confirm:hover { background: rgba(16,185,129,0.3); }

    .btn-acknowledge { background: rgba(251,191,36,0.2); color: #fde68a; border: 1px solid rgba(251,191,36,0.35); }
    .btn-acknowledge:hover { background: rgba(251,191,36,0.3); }

    .btn-view-form  { background: rgba(96,165,250,0.2); color: #93c5fd; border: 1px solid rgba(96,165,250,0.35); }
    .btn-view-form:hover { background: rgba(96,165,250,0.3); }

    .case-record-confirmed {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.4rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: #86efac;
        margin-top: 0.25rem;
    }

    .case-record-confirmed span {
        font-size: 0.8rem;
        font-weight: 400;
        color: rgba(134,239,172,0.7);
    }

    .text-green { color: #86efac; }
    .text-amber { color: #fbbf24; }

    /* ================================================================
       EVIDENCE IMPROVEMENTS
       ================================================================ */
    .evidence-count-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        background: rgba(96,165,250,0.3);
        border: 1px solid rgba(96,165,250,0.4);
        color: #93c5fd;
        border-radius: 50%;
        font-size: 0.75rem;
        font-weight: 700;
        vertical-align: middle;
        margin-left: 0.5rem;
    }

    .evidence-item {
        position: relative;
        display: flex;
        flex-direction: column;
    }

    .evidence-item img {
        width: 100%;
        height: 160px;
        object-fit: cover;
        display: block;
    }

    .evidence-item-label {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 0.75rem;
        background: rgba(0,0,0,0.3);
        border-top: 1px solid rgba(255,255,255,0.1);
    }

    .evidence-type-icon {
        font-size: 0.85rem;
        flex-shrink: 0;
    }

    .evidence-file-name {
        font-size: 0.78rem;
        color: rgba(255,255,255,0.85);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        flex: 1;
        min-width: 0;
    }

    .evidence-item-nothumb {
        justify-content: space-between;
        min-height: 120px;
    }

    .evidence-nothumb-icon {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        padding: 1rem;
    }

    /* ================================================================
       REJECTION BANNER
       ================================================================ */
    .rejection-banner {
        margin: 1.25rem 1.5rem;
        padding: 1rem 1.25rem;
        background: rgba(239,68,68,0.15);
        border: 1px solid rgba(239,68,68,0.35);
        border-left: 4px solid #ef4444;
        border-radius: 0.6rem;
    }

    .rejection-banner-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #fca5a5;
        margin-bottom: 0.35rem;
    }

    .rejection-banner-reason {
        font-size: 0.9rem;
        color: rgba(255,255,255,0.9);
        margin-bottom: 0.35rem;
    }

    .rejection-banner-date {
        font-size: 0.78rem;
        color: rgba(255,255,255,0.5);
    }

    /* ── Mobile ── */
    @media (max-width: 768px) {
        .case-record-card {
            padding: 1rem;
            gap: 0.75rem;
        }

        .case-record-icon {
            font-size: 1.4rem;
        }

        .case-record-title {
            font-size: 0.95rem;
        }

        .case-record-meta {
            gap: 0.4rem 1rem;
        }

        .rejection-banner {
            margin: 1rem;
        }

        .evidence-item img {
            height: 130px;
        }
    }

    @media (max-width: 480px) {
        .case-record-card {
            padding: 0.875rem;
        }

        .case-record-btn {
            width: 100%;
            justify-content: center;
        }

        .evidence-item img {
            height: 110px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    /* ── Collapsible detail groups ── */
    (function () {
        document.querySelectorAll('.detail-group-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var expanded = btn.getAttribute('aria-expanded') === 'true';
                var bodyId   = btn.getAttribute('aria-controls');
                var body     = document.getElementById(bodyId);
                if (!body) return;
                btn.setAttribute('aria-expanded', String(!expanded));
                body.classList.toggle('collapsed', expanded);
            });
        });
    })();

    /* ── Evidence modal (unchanged from original) ── */
    (function () {
        const modal = document.getElementById('evidenceModal');
        const modalBody = document.getElementById('evidenceModalBody');
        const modalOpenNewTab = document.getElementById('evidenceModalOpenNewTab');

        if (!modal || !modalBody || !modalOpenNewTab) return;

        const closeModal = function () {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('evidence-modal-open');
            modalBody.innerHTML = '';
            modalOpenNewTab.removeAttribute('href');
        };

        const openModal = function (type, url, name) {
            if (!url) return;
            modalBody.innerHTML = '';
            const title = document.getElementById('evidenceModalTitle');
            if (title && name) title.textContent = name;

            if (type === 'image') {
                const img = document.createElement('img');
                img.src = url;
                img.alt = name || 'Submitted evidence image';
                modalBody.appendChild(img);
            } else if (type === 'video') {
                const video = document.createElement('video');
                video.src = url;
                video.controls = true;
                video.autoplay = true;
                modalBody.appendChild(video);
            } else {
                const frame = document.createElement('iframe');
                frame.src = url;
                frame.title = name || 'Submitted evidence file';
                modalBody.appendChild(frame);
            }

            modalOpenNewTab.href = url;
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('evidence-modal-open');
        };

        document.querySelectorAll('.evidence-trigger').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                openModal(
                    trigger.getAttribute('data-evidence-type') || 'image',
                    trigger.getAttribute('data-evidence-url') || '',
                    trigger.getAttribute('data-evidence-name') || 'Submitted Evidence'
                );
            });
        });

        modal.querySelectorAll('[data-evidence-close]').forEach(function (closer) {
            closer.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('show')) closeModal();
        });
    })();
</script>
@endpush