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
                <label>Status</label>
                <p><span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">{{ ucfirst($report->status) }}</span></p>
            </div>
            <div class="detail-item full-width">
                <label>Description</label>
                <p>{{ $report->description }}</p>
            </div>
        </div>
    </section>

    <section id="case-records" class="animate">
        <h2>Case Records and Disciplinary Forms</h2>

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
            <p class="no-data">No disciplinary forms have been issued for this case yet.</p>
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
                            $extension = pathinfo($file, PATHINFO_EXTENSION);
                        @endphp

                        @if(in_array(strtolower($extension), ['jpg','jpeg','png','gif']))
                            <div class="evidence-item">
                                <img src="{{ asset('storage/' . $file) }}" alt="Evidence Image">
                            </div>
                        @elseif(in_array(strtolower($extension), ['mp4','webm','ogg']))
                            <div class="evidence-item">
                                <video controls>
                                    <source src="{{ asset('storage/' . $file) }}" type="video/{{ $extension }}">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                        @elseif(strtolower($extension) === 'pdf')
                            <div class="evidence-item">
                                <iframe src="{{ asset('storage/' . $file) }}"></iframe>
                            </div>
                        @else
                            <div class="evidence-item">
                                <a href="{{ asset('storage/' . $file) }}" target="_blank" class="file-link">📂 View File</a>
                            </div>
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

    <!-- Unified Department Student Discipline Officer Response Timeline -->
    <section id="admin-response-timeline" class="animate">
        <h2>📋 Department Student Discipline Officer Response Timeline</h2>

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
