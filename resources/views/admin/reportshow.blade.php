@extends('layouts.admin')

@section('title', 'Report Details - Sincidentre Admin')

@section('page-title', '📄 Report Details')

@section('content')
    <p>Full information about this reported incident.</p>

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

    <section>
        <table border="1" cellspacing="0" cellpadding="8" width="100%">
            <tr>
                <th>Report ID</th>
                <td>#{{ $report->id }}</td>
            </tr>
            <tr>
                <th>Title</th>
                <td>{{ $report->title }}</td>
            </tr>
            <tr>
                <th>Category</th>
                <td>{{ $report->category }}</td>
            </tr>
            <tr>
                <th>Description</th>
                <td>{{ $report->description }}</td>
            </tr>
            <tr>
                <th>Date of Incident</th>
                <td>{{ $report->incident_date }}</td>
            </tr>
            <tr>
                <th>Time of Incident</th>
                <td>{{ $report->incident_time }}</td>
            </tr>
            <tr>
                <th>Location</th>
                <td>{{ $report->location }}</td>
            </tr>
            <tr>
                <th>Submitted By</th>
                <td>{{ $report->user->first_name ?? 'Unknown' }} {{ $report->user->last_name ?? '' }}</td>
            </tr>
            <tr>
                <th>Submitted At</th>
                <td>{{ $report->submitted_at?->format('F d, Y h:i A') }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                        {{ ucfirst($report->status) }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>Evidence</th>
                <td>
                    @if($report->evidence)
                        @php
                            $evidences = is_array($report->evidence) ? $report->evidence : json_decode($report->evidence, true);
                        @endphp
                        
                        @if(is_array($evidences) && count($evidences) > 0)
                            <div class="evidence-grid">
                                @foreach($evidences as $file)
                                    @php
                                        $extension = pathinfo($file, PATHINFO_EXTENSION);
                                    @endphp

                                    @if(in_array(strtolower($extension), ['jpg','jpeg','png','gif','webp']))
                                        <div class="evidence-item">
                                            <img src="{{ asset('storage/' . $file) }}" alt="Evidence Image" style="max-width: 300px; margin: 5px;">
                                        </div>
                                    @elseif(in_array(strtolower($extension), ['mp4','webm','ogg','avi','mov']))
                                        <div class="evidence-item">
                                            <video controls style="max-width: 300px; margin: 5px;">
                                                <source src="{{ asset('storage/' . $file) }}" type="video/{{ $extension }}">
                                                Your browser does not support the video tag.
                                            </video>
                                        </div>
                                    @elseif(strtolower($extension) === 'pdf')
                                        <div class="evidence-item">
                                            <a href="{{ asset('storage/' . $file) }}" target="_blank" class="btn-view">📄 View PDF</a>
                                        </div>
                                    @else
                                        <div class="evidence-item">
                                            <a href="{{ asset('storage/' . $file) }}" target="_blank" class="btn-view">📂 Download File</a>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            No evidence files attached.
                        @endif
                    @else
                        No evidence files attached.
                    @endif
                </td>
            </tr>
        </table>

        <div style="margin-top: 20px;">
            @if($report->status === 'pending' || $report->status === 'under review')
                <form action="{{ route('admin.reports.approve', $report->id) }}" method="POST" style="display:inline-block;">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn-approve">✅ Approve</button>
                </form>

                <button type="button" class="btn-reject" onclick="openRejectModal()">❌ Reject</button>
            @else
                <p><em>This report has already been {{ $report->status }}.</em></p>
            @endif

            <p style="margin-top: 20px;">
                <a href="{{ route('admin.reports') }}" class="btn-back">⬅ Back to Reports</a>
            </p>
        </div>
    </section>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reject Report #{{ $report->id }}</h3>
                <span class="close" onclick="closeRejectModal()">&times;</span>
            </div>
            <form method="POST" action="{{ route('admin.reports.reject', $report->id) }}">
                @csrf
                @method('PATCH')
                <div class="form-group">
                    <label><strong>Report Title:</strong></label>
                    <p>{{ $report->title }}</p>
                </div>
                <div class="form-group">
                    <label for="rejection_reason">Reason for Rejection *</label>
                    <textarea 
                        id="rejection_reason" 
                        name="rejection_reason" 
                        rows="4" 
                        placeholder="Please provide a clear reason for rejecting this report..."
                        required></textarea>
                </div>
                <button type="button" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" style="background-color: #dc3545; color: white;">Reject Report</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function openRejectModal() {
        document.getElementById('rejectModal').style.display = 'block';
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
</script>
@endpush