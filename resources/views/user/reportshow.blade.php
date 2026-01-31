@extends('layouts.app')

@section('title', 'Report Details - Sincidentre')

@section('content')
    <header>
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
                <label>Date</label>
                <p>{{ \Carbon\Carbon::parse($report->incident_date)->format('F d, Y') }}</p> <!-- ✅ Better formatting -->
            </div>
            <div class="detail-item">
                <label>Time</label>
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

    <!-- Admin Response Section -->
    <section id="admin-response" class="animate">
        @if($report->status === 'Rejected' && $report->rejection_reason)
            {{-- Report was rejected - show rejection reason --}}
            <h2>❌ Report Rejected</h2>
            <div class="details-grid">
                <div class="detail-item full-width" style="background-color: #fee; padding: 15px; border-left: 4px solid #d00;">
                    <label style="color: #d00;">Reason for Rejection</label>
                    <p>{{ $report->rejection_reason }}</p>
                </div>
            </div>

        @elseif($report->assigned_to || $report->department || $report->target_date || $report->remarks)
            {{-- Report is being handled - show handling details --}}
            <h2>🛠 Admin Handling Details</h2>
            <div class="details-grid">
                <div class="detail-item">
                    <label>Assigned To</label>
                    <p>{{ $report->assigned_to ?? 'Not yet assigned' }}</p>
                </div>
                <div class="detail-item">
                    <label>Department</label>
                    <p>{{ $report->department ?? 'N/A' }}</p>
                </div>
                <div class="detail-item">
                    <label>Target Date</label>
                    <p>{{ $report->target_date ? \Carbon\Carbon::parse($report->target_date)->format('F d, Y') : 'No target date set' }}</p>
                </div>
                <div class="detail-item full-width">
                    <label>Admin Remarks</label>
                    <p>{{ $report->remarks ?? 'No remarks yet' }}</p>
                </div>
            </div>

        @else
            {{-- Report is pending - no response yet --}}
            <h2>🕓 Awaiting Admin Response</h2>
            <p class="no-data">Your report is still pending review or has not been handled by the admin yet.</p>
        @endif
    </section>

    <!-- Activity Timeline Section -->
    <section id="activity-timeline" class="animate">
        <h2>📋 Activity Timeline</h2>
        
        @php
            $activities = $report->activities()->orderBy('created_at', 'desc')->get();
        @endphp

        @if($activities->count() > 0)
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
                                By: <strong>
        @if($activity->admin)
          {{ $activity->admin->name }}
        @else
          Unknown Admin
        @endif
      </strong> <!-- ✅ FIXED -->
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="no-data">No activity recorded yet for this report.</p>
        @endif
    </section>
@endsection