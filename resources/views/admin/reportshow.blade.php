@extends('layouts.admin')

@section('title', 'Report Details - Sincidentre Department Student Discipline Officer')

@section('page-title', 'Report Details')

@section('content')
    <!-- Success/Error Messages -->
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

    <!-- Report Header -->
    <div class="report-header">
        <div>
            <p class="report-id-line">Report #{{ $report->id }}</p>
        </div>
        <div>
            <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                {{ ucfirst($report->status) }}
            </span>
        </div>
    </div>

    <!-- Main Report Information -->
    <section class="report-details handle-section">
        <h3>Incident Information</h3>
        <table class="handle-report-table">
            <tr>
                <th>Category</th>
                <td>
                    @if($report->category)
                        <span class="category-badge">{{ strtoupper($report->category->main_category_code) }} - {{ $report->category->main_category_name }} / {{ $report->category->name }}</span>
                    @else
                        <span class="category-badge">N/A</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>Description</th>
                <td>{{ $report->description }}</td>
            </tr>
            <tr>
                <th>Date of Incident</th>
                <td>{{ $report->incident_date ? \Carbon\Carbon::parse($report->incident_date)->format('F d, Y') : 'N/A' }}</td>
            </tr>
            <tr>
                <th>Time of Incident</th>
                <td>{{ $report->incident_time ? \Carbon\Carbon::parse($report->incident_time)->format('h:i A') : 'N/A' }}</td>
            </tr>
            <tr>
                <th>Location</th>
                <td>{{ $report->location ?: 'N/A' }}</td>
            </tr>
            <tr>
                <th>Person Involvement</th>
                <td>{{ $report->person_involvement ? ucfirst($report->person_involvement) : 'N/A' }}</td>
            </tr>
        </table>
    </section>

    <!-- Reporter Information -->
    <section class="reporter-info handle-section">
        <h3>Reporter Information</h3>
        <table class="handle-report-table">
            <tr>
                <th>Name</th>
                <td>{{ $report->user->first_name ?? 'Unknown' }} {{ $report->user->last_name ?? '' }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td>{{ $report->user->email ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Department</th>
                <td>{{ $report->user->department->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Submitted At</th>
                <td>
                    {{ $report->created_at->format('F d, Y') }}
                    <small style="color: #999;">({{ $report->created_at->diffForHumans() }})</small>
                </td>
            </tr>
        </table>
    </section>

    <!-- Evidence Section -->
    <section class="evidence-section handle-section">
        <h3>Evidence & Attachments</h3>
        @if($report->evidence)
            @php
                $evidences = is_array($report->evidence) ? $report->evidence : json_decode($report->evidence, true);
            @endphp

            @if(is_array($evidences) && count($evidences) > 0)
                <div class="evidence-grid">
                    @foreach($evidences as $index => $file)
                        @php
                            $extension = pathinfo($file, PATHINFO_EXTENSION);
                        @endphp

                        @if(in_array(strtolower($extension), ['jpg','jpeg','png','gif','webp']))
                            <div class="evidence-item">
                                <img src="{{ asset('storage/' . $file) }}"
                                     alt="Evidence {{ $index + 1 }}"
                                     style="max-width: 100%; border-radius: 8px; cursor: pointer;"
                                     onclick="openImageModal('{{ asset('storage/' . $file) }}')">
                                <p style="text-align: center; margin-top: 5px; font-size: 12px; color: #666;">
                                    Image {{ $index + 1 }}
                                </p>
                            </div>
                        @elseif(in_array(strtolower($extension), ['mp4','webm','ogg','avi','mov']))
                            <div class="evidence-item">
                                <video controls style="max-width: 100%; border-radius: 8px;">
                                    <source src="{{ asset('storage/' . $file) }}" type="video/{{ $extension }}">
                                    Your browser does not support the video tag.
                                </video>
                                <p style="text-align: center; margin-top: 5px; font-size: 12px; color: #666;">
                                    Video {{ $index + 1 }}
                                </p>
                            </div>
                        @elseif(strtolower($extension) === 'pdf')
                            <div class="evidence-item">
                                <a href="{{ asset('storage/' . $file) }}" target="_blank" class="btn-view report-action-btn">
                                    View PDF Document
                                </a>
                            </div>
                        @else
                            <div class="evidence-item">
                                <a href="{{ asset('storage/' . $file) }}" download class="btn-view report-action-btn">
                                    Download {{ strtoupper($extension) }} File
                                </a>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <p style="color: #999; padding: 20px; text-align: center; background: #f5f5f5; border-radius: 8px;">
                    No evidence files attached to this report.
                </p>
            @endif
        @else
            <p style="color: #999; padding: 20px; text-align: center; background: #f5f5f5; border-radius: 8px;">
                No evidence files attached to this report.
            </p>
        @endif
    </section>

    <!-- Action Buttons -->
    <section class="action-buttons">
        @if($report->escalated_to_top_management)
            <div class="alert alert-info">
                <strong>Escalated Report:</strong> This report is view-only in this section and cannot be handled here.
            </div>
        @elseif(in_array(strtolower($report->status), ['pending', 'under review']))
            <form action="{{ route('admin.reports.approve', $report->id) }}" method="POST" style="display:inline-block;">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn-approve report-action-btn" onclick="return confirm('Are you sure you want to approve this report?')">
                    Approve Report
                </button>
            </form>

            <button type="button" class="btn-reject report-action-btn" onclick="openRejectModal()">
                Reject Report
            </button>
        @else
            <div class="alert alert-info">
                <strong>Status:</strong> This report has been {{ $report->status }}.
            </div>
        @endif

        <a href="{{ route('admin.reports') }}" class="btn btn-secondary report-action-btn">Back to New Reports</a>
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
                    <label><strong>Report ID:</strong></label>
                    <p style="background: #f5f5f5; padding: 10px; border-radius: 4px;">#{{ $report->id }}</p>
                </div>
                <div class="form-group">
                    <label for="rejection_reason">Reason for Rejection *</label>
                    <textarea 
                        id="rejection_reason" 
                        name="rejection_reason" 
                        rows="5" 
                        placeholder="Please provide a clear and detailed reason for rejecting this report. This will be visible to the reporter."
                        required
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                    <small style="color: #666;">Minimum 10 characters required</small>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeRejectModal()" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-reject" onclick="return confirm('Are you sure you want to reject this report?')">
                        Reject Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Image Modal for viewing full-size images -->
    <div id="imageModal" class="modal">
        <div class="modal-content" style="max-width: 90%; max-height: 90vh;">
            <span class="close" onclick="closeImageModal()">&times;</span>
            <img id="modalImage" src="" alt="Evidence" style="width: 100%; height: auto; border-radius: 8px;">
        </div>
    </div>
@endsection

@push('styles')
<style>
    .report-id-line {
        margin: 5px 0;
        color: rgba(255, 255, 255, 0.9);
        font-weight: 700;
    }

    .category-meta {
        margin-top: 8px;
        color: rgba(255, 255, 255, 0.88);
    }

    .category-meta strong {
        color: #ffffff;
    }

    .report-action-btn {
        min-width: 180px;
        justify-content: center;
        text-align: center;
    }
</style>
@endpush

@push('scripts')
<script>
    function openRejectModal() {
        document.getElementById('rejectModal').style.display = 'block';
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').style.display = 'none';
        document.getElementById('rejection_reason').value = '';
    }

    function openImageModal(imageSrc) {
        document.getElementById('imageModal').style.display = 'block';
        document.getElementById('modalImage').src = imageSrc;
    }

    function closeImageModal() {
        document.getElementById('imageModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }

    // Auto-hide success/error alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);

    // Validate rejection reason length
    document.getElementById('rejection_reason')?.addEventListener('input', function() {
        const submitBtn = this.closest('form').querySelector('button[type="submit"]');
        if (this.value.length < 10) {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.5';
        } else {
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
        }
    });
</script>
@endpush
