@extends('layouts.app')

@section('title', 'Report Details - Sincidentre')

@section('content')
    <header class="page-header">
        <h1>Report Details</h1>
        <p>Report ID: #{{ $report->id }}</p>
    </header>

    <!-- Report Details Section -->
    <section id="report-details-view" class="animate">
        <h2>Report Information</h2>
        <div class="details-grid">
            <div class="detail-item">
                <label>Title</label>
                <p>{{ $report->title }}</p>
            </div>
            <div class="detail-item">
                <label>Category</label>
                <p>{{ $report->category->name ?? 'N/A' }}</p> <!-- ✅ FIXED -->
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

    <!-- Unified Admin Response Timeline -->
    <section id="admin-response-timeline" class="animate">
        <h2>📋 Admin Response Timeline</h2>

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

                            <div class="timeline-footer">
                                By: <strong>{{ $response->admin->name ?? 'Unknown Admin' }}</strong>
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
                                By: <strong>{{ $activity->admin->name ?? 'Unknown Admin' }}</strong>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="no-data">Your report is still pending review or has no admin response yet.</p>
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