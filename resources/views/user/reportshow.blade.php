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

    <!-- Report Details Section -->
    <section id="report-details-view" class="animate">
        <h2>Report Information</h2>
        <div class="details-grid">
            <div class="detail-item">
                <label>Category</label>
                @if($report->category)
                    <p>{{ strtoupper($report->category->main_category_code) }} - {{ $report->category->main_category_name }} / {{ $report->category->name }}</p>
                    <small>
                        Classification: {{ $report->category->classification }}
                    </small>
                @else
                    <p>N/A</p>
                @endif
            </div>
            <div class="detail-item">
                <label>Date of Incident</label>
                <p>{{ \Carbon\Carbon::parse($report->incident_date)->format('F d, Y') }}</p> <!-- ✅ Better formatting -->
            </div>
            <div class="detail-item">
                <label>Time of Incident</label>
                <p>{{ \Carbon\Carbon::parse($report->incident_time)->format('h:i A') }}</p> <!-- ✅ Better formatting -->
            </div>
            <div class="detail-item">
                <label>Location</label>
                <p>{{ $report->location }}</p>
            </div>
            <div class="detail-item">
                <label>Please Specify</label>
                <p>{{ $report->location_details ?: 'N/A' }}</p>
            </div>
            <div class="detail-item">
                <label>Status</label>
                <p><span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">{{ ucfirst($report->status) }}</span></p>
            </div>
            <div class="detail-item full-width">
                <label>Description</label>
                <p>{{ $report->description }}</p>
            </div>

            @php
                $personInvolvementLabel = [
                    'known' => 'Yes, known identity',
                    'unknown' => 'Yes, unknown identity',
                    'none' => 'No person involved',
                    'unsure' => 'Not sure yet',
                ][(string) $report->person_involvement] ?? ucfirst((string) $report->person_involvement);

                $incidentAdditionalSheets = [];
                if (!empty($report->incident_additional_sheets)) {
                    $decodedIncidentSheets = json_decode((string) $report->incident_additional_sheets, true);
                    if (is_array($decodedIncidentSheets)) {
                        $incidentAdditionalSheets = $decodedIncidentSheets;
                    }
                }
            @endphp

            @if(!empty($report->person_involvement))
                <div class="detail-item">
                    <label>Person Involvement</label>
                    <p>{{ $personInvolvementLabel }}</p>
                </div>
            @endif

            @if(!empty($report->person_full_name))
                <div class="detail-item">
                    <label>Person Full Name</label>
                    <p>{{ $report->person_full_name }}</p>
                </div>
            @endif

            @if(!empty($report->person_college_department))
                <div class="detail-item">
                    <label>Person College/Department</label>
                    <p>{{ $report->person_college_department }}</p>
                </div>
            @endif

            @if(!empty($report->person_role))
                <div class="detail-item">
                    <label>Person Role</label>
                    <p>{{ $report->person_role }}</p>
                </div>
            @endif

            @if(!empty($report->person_contact_number))
                <div class="detail-item">
                    <label>Person Contact Number</label>
                    <p>{{ $report->person_contact_number }}</p>
                </div>
            @endif

            @if(!empty($report->person_email_address))
                <div class="detail-item">
                    <label>Person Email Address</label>
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
                    <label>Technical/Facility Specific Details</label>
                    <p>{{ $report->technical_facility_details }}</p>
                </div>
            @endif

            <div class="detail-item">
                <label>Were There Multiple Persons Involved?</label>
                <p>{{ $report->person_has_multiple ? 'Yes' : 'No' }}</p>
            </div>

            @if(is_array($report->additional_persons) && count($report->additional_persons) > 0)
                <div class="detail-item full-width">
                    <label>Additional Persons</label>
                    <ul>
                        @foreach($report->additional_persons as $person)
                            <li>
                                {{ $person['full_name'] ?? 'Unnamed Person' }}
                                @if(!empty($person['college_department'])) - {{ $person['college_department'] }} @endif
                                @if(!empty($person['role'])) ({{ $person['role'] }}) @endif
                                @if(!empty($person['contact_number'])) | {{ $person['contact_number'] }} @endif
                                @if(!empty($person['email_address'])) | {{ $person['email_address'] }} @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="detail-item">
                <label>Were There Any Witnesses?</label>
                <p>{{ $report->has_witnesses ? 'Yes' : 'No' }}</p>
            </div>

            @if(is_array($report->witness_details) && count($report->witness_details) > 0)
                <div class="detail-item full-width">
                    <label>Witness Details</label>
                    <ul>
                        @foreach($report->witness_details as $witness)
                            <li>
                                {{ $witness['name'] ?? ($witness['full_name'] ?? 'Unnamed Witness') }}
                                @if(!empty($witness['address'])) - {{ $witness['address'] }} @endif
                                @if(!empty($witness['contact_number'])) | {{ $witness['contact_number'] }} @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(count($incidentAdditionalSheets) > 0)
                <div class="detail-item full-width">
                    <label>Incident Additional Sheets</label>
                    <ul>
                        @foreach($incidentAdditionalSheets as $sheetPath)
                            <li>
                                <a href="{{ asset('storage/' . $sheetPath) }}" target="_blank" class="file-link">{{ basename($sheetPath) }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(!empty($report->informant_full_name))
                <div class="detail-item">
                    <label>Informant Full Name</label>
                    <p>{{ $report->informant_full_name }}</p>
                </div>
            @endif

            @if(!empty($report->informant_college_department))
                <div class="detail-item">
                    <label>Informant College/Department</label>
                    <p>{{ $report->informant_college_department }}</p>
                </div>
            @endif

            @if(!empty($report->informant_role))
                <div class="detail-item">
                    <label>Informant Role</label>
                    <p>{{ $report->informant_role }}</p>
                </div>
            @endif

            @if(!empty($report->informant_contact_number))
                <div class="detail-item">
                    <label>Informant Contact Number</label>
                    <p>{{ $report->informant_contact_number }}</p>
                </div>
            @endif

            @if(!empty($report->informant_email_address))
                <div class="detail-item">
                    <label>Informant Email Address</label>
                    <p>{{ $report->informant_email_address }}</p>
                </div>
            @endif

            @if($report->submitted_at)
                <div class="detail-item">
                    <label>Submitted At</label>
                    <p>{{ $report->submitted_at->format('F d, Y h:i A') }}</p>
                </div>
            @endif
        </div>
    </section>

    <section id="case-records" class="animate">
        <h2>Case Records and Disciplinary Actions</h2>

        @if($report->hearing_date || $report->hearing_time || $report->hearing_venue)
            <div class="timeline-item" style="margin-bottom:10px;">
                <div class="timeline-content">
                    <div class="timeline-header">
                        <strong>Hearing Notification (2303 Replacement)</strong>
                    </div>
                    <div class="timeline-remarks"><strong>Date:</strong> {{ $report->hearing_date ? $report->hearing_date->format('F d, Y') : 'N/A' }}</div>
                    <div class="timeline-remarks"><strong>Time:</strong> {{ $report->hearing_time ? \Carbon\Carbon::parse($report->hearing_time)->format('h:i A') : 'N/A' }}</div>
                    <div class="timeline-remarks"><strong>Venue:</strong> {{ $report->hearing_venue ?: 'N/A' }}</div>
                    @if($canConfirmHearingNotice && !$hasConfirmedHearingNotice)
                        <form action="{{ route('reports.confirmHearingNotice', $report->id) }}" method="POST" style="margin-top:8px;">
                            @csrf
                            <button type="submit">Confirm Receipt of Hearing Notification</button>
                        </form>
                    @elseif($canConfirmHearingNotice && $hasConfirmedHearingNotice)
                        <div class="timeline-remarks" style="margin-top:8px; color:#86efac; font-weight:600;">
                            Hearing notification already confirmed
                            @if($existingConfirmation?->created_at)
                                ({{ $existingConfirmation->created_at->format('M d, Y h:i A') }})
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if($report->reprimand_document_path)
            <div class="timeline-item" style="margin-bottom:10px;">
                <div class="timeline-content">
                    <div class="timeline-header">
                        <strong>Form 2304 - Written Reprimand has been issued to the person found to have violated school rules</strong>
                    </div>
                    <div class="timeline-remarks"><strong>Acknowledgment:</strong>
                        {{ $report->student_acknowledged_reprimand_at ? $report->student_acknowledged_reprimand_at->format('M d, Y h:i A') : 'Pending' }}
                    </div>
                    @if(auth()->check() && auth()->id() === $report->user_id && !$report->student_acknowledged_reprimand_at)
                        <form action="{{ route('reports.acknowledge-reprimand', $report->id) }}" method="POST" style="margin-top:8px;">
                            @csrf
                            <button type="submit">Acknowledge Receipt of Form 2304</button>
                        </form>
                    @endif
                </div>
            </div>
        @endif

        @if($report->suspension_document_path)
            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-header">
                        <strong>Form 2305 - {{ $report->disciplinary_action ?? 'Disciplinary Action' }}</strong>
                    </div>
                    <div class="timeline-remarks">
                        <a href="{{ asset('storage/' . $report->suspension_document_path) }}" target="_blank" class="file-link">Open Stored Form 2305</a>
                    </div>
                    <div class="timeline-remarks"><strong>Effective Date:</strong>
                        {{ $report->suspension_effective_date ? $report->suspension_effective_date->format('F d, Y') : 'N/A' }}
                    </div>
                </div>
            </div>
        @endif

        @if(!$report->reprimand_document_path && !$report->suspension_document_path)
            <p class="no-data">No disciplinary actions have been issued for this case yet.</p>
        @endif
    </section>

    <!-- Evidence Section -->
    <section id="evidence-section" class="animate">
        <h2>📎 Submitted Evidence</h2>
        @if($report->evidence)
            @php
                $evidences = json_decode($report->evidence, true);
            @endphp
            @if(is_array($evidences) && count($evidences) > 0)
                <div class="evidence-grid">
                    @foreach($evidences as $file)
                        @php
                            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                            $fileUrl = asset('storage/' . $file);
                            $fileName = basename($file);
                            $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true);
                            $isVideo = in_array($extension, ['mp4', 'webm', 'ogg'], true);
                            $isPdf = $extension === 'pdf';
                        @endphp

                        @if($isImage)
                            <button
                                type="button"
                                class="evidence-item evidence-trigger"
                                data-evidence-type="image"
                                data-evidence-url="{{ $fileUrl }}"
                                data-evidence-name="{{ $fileName }}"
                                aria-label="View submitted image file {{ $fileName }}"
                            >
                                <img src="{{ $fileUrl }}" alt="Submitted evidence image {{ $fileName }}">
                            </button>
                        @elseif($isVideo)
                            <button
                                type="button"
                                class="evidence-item evidence-trigger"
                                data-evidence-type="video"
                                data-evidence-url="{{ $fileUrl }}"
                                data-evidence-name="{{ $fileName }}"
                                aria-label="View submitted video file {{ $fileName }}"
                            >
                                <video muted preload="metadata" aria-hidden="true">
                                    <source src="{{ $fileUrl }}" type="video/{{ $extension }}">
                                </video>
                            </button>
                        @elseif($isPdf)
                            <button
                                type="button"
                                class="evidence-item evidence-trigger"
                                data-evidence-type="pdf"
                                data-evidence-url="{{ $fileUrl }}"
                                data-evidence-name="{{ $fileName }}"
                                aria-label="View submitted PDF file {{ $fileName }}"
                            >
                                <iframe src="{{ $fileUrl }}" title="Submitted PDF preview {{ $fileName }}" aria-hidden="true"></iframe>
                            </button>
                        @else
                            <a
                                href="{{ $fileUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="evidence-item evidence-item-file"
                                aria-label="Open submitted file {{ $fileName }}"
                            >
                                <div class="evidence-file-meta">
                                    <strong>File</strong>
                                    <span>{{ $fileName }}</span>
                                </div>
                            </a>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="no-data">No evidence files available.</p>
            @endif
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

    <!-- Unified Department Student Discipline Officer Response Timeline -->
    <section id="admin-response-timeline" class="animate">
        <h2>📋Response Timeline</h2>

        @php
            $responses = $report->responses->sortByDesc('response_number');
            $activities = $report->activities->sortByDesc('created_at');
        @endphp

        @if($responses->count() > 0)
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
                                Status: <span class="new-status">{{ ucfirst($response->status) }}</span>
                            </div>

                            <div class="timeline-remarks">
                                <strong>Response Type:</strong> {{ $response->response_type ?? 'Handling Response' }}
                            </div>

                            <div class="timeline-remarks">
                                <strong>Assigned to:</strong> {{ $response->assigned_to ?? 'Unassigned' }}
                            </div>
                            <div class="timeline-remarks">
                                <strong>Department:</strong> {{ $response->department ?? 'N/A' }}
                            </div>
                            <div class="timeline-remarks">
                                <strong>Target date:</strong>
                                {{ $response->target_date ? \Carbon\Carbon::parse($response->target_date)->format('F d, Y') : 'No target date set' }}
                            </div>
                            <div class="timeline-remarks">
                                <strong>Remarks:</strong> {{ $response->remarks ?? 'No remarks' }}
                            </div>
                            <div class="timeline-remarks">
                                <strong>Attachment:</strong>
                                @if($response->attachment_path)
                                    <a href="{{ asset('storage/' . $response->attachment_path) }}" target="_blank" class="file-link">View attachment</a>
                                @else
                                    None
                                @endif
                            </div>

                            <div class="timeline-footer">
                                By: <strong>{{ $response->admin->name ?? 'Unknown Department Student Discipline Officer' }}</strong>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif($report->assigned_to || $report->department || $report->target_date || $report->remarks)
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <div class="timeline-header">
                            <strong>Initial Handling Details</strong>
                            <span class="timeline-date">{{ $report->updated_at->format('M d, Y h:i A') }}</span>
                        </div>

                        <div class="timeline-status-change">
                            Status: <span class="new-status">{{ ucfirst($report->status) }}</span>
                        </div>
                        <div class="timeline-remarks">
                            <strong>Assigned to:</strong> {{ $report->assigned_to ?? 'Unassigned' }}
                        </div>
                        <div class="timeline-remarks">
                            <strong>Department:</strong> {{ $report->department ?? 'N/A' }}
                        </div>
                        <div class="timeline-remarks">
                            <strong>Target date:</strong>
                            {{ $report->target_date ? \Carbon\Carbon::parse($report->target_date)->format('F d, Y') : 'No target date set' }}
                        </div>
                        <div class="timeline-remarks">
                            <strong>Remarks:</strong> {{ $report->remarks ?? 'No remarks' }}
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
                                    <span class="old-status">{{ ucfirst($activity->old_status) }}</span>
                                    →
                                    <span class="new-status">{{ ucfirst($activity->new_status) }}</span>
                                </div>
                            @endif

                            @if($activity->remarks)
                                <div class="timeline-remarks">
                                    <strong>Remarks:</strong> {{ $activity->remarks }}
                                </div>
                            @endif

                            <div class="timeline-footer">
                                By: <strong>{{ $activity->admin->name ?? 'Unknown Department Student Discipline Officer' }}</strong>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="no-data">Your report is still pending review or has no department student discipline officer response yet.</p>
        @endif

        @if($report->status === 'Rejected' && $report->rejection_reason)
            <div class="timeline" style="margin-top: 12px;">
                <div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content" style="border-left: 4px solid #d00;">
                        <div class="timeline-header">
                            <strong>Report Rejected</strong>
                            <span class="timeline-date">{{ $report->updated_at->format('M d, Y h:i A') }}</span>
                        </div>
                        <div class="timeline-remarks">
                            <strong>Reason:</strong> {{ $report->rejection_reason }}
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </section>
@endsection

@push('styles')
<style>
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

    .evidence-modal.show {
        display: flex;
    }

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

    body.evidence-modal-open {
        overflow: hidden;
    }

    @media (max-width: 768px) {
        .evidence-modal {
            padding: 0.5rem;
        }

        .evidence-modal-dialog {
            max-height: 94vh;
        }

        .evidence-modal-header,
        .evidence-modal-footer {
            padding: 0.7rem 0.8rem;
        }

        .evidence-modal-body {
            padding: 0.5rem;
        }

        .evidence-modal-body img,
        .evidence-modal-body video,
        .evidence-modal-body iframe {
            max-height: calc(94vh - 145px);
        }
    }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        const modal = document.getElementById('evidenceModal');
        const modalBody = document.getElementById('evidenceModalBody');
        const modalOpenNewTab = document.getElementById('evidenceModalOpenNewTab');

        if (!modal || !modalBody || !modalOpenNewTab) {
            return;
        }

        const closeModal = function () {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('evidence-modal-open');
            modalBody.innerHTML = '';
            modalOpenNewTab.removeAttribute('href');
        };

        const openModal = function (type, url, name) {
            if (!url) {
                return;
            }

            modalBody.innerHTML = '';
            const title = document.getElementById('evidenceModalTitle');
            if (title && name) {
                title.textContent = name;
            }

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
                const type = trigger.getAttribute('data-evidence-type') || 'image';
                const url = trigger.getAttribute('data-evidence-url') || '';
                const name = trigger.getAttribute('data-evidence-name') || 'Submitted Evidence';
                openModal(type, url, name);
            });
        });

        modal.querySelectorAll('[data-evidence-close]').forEach(function (closer) {
            closer.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('show')) {
                closeModal();
            }
        });
    })();
</script>
@endpush
