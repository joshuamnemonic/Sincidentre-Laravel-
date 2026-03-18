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
                <th>Please Specify</th>
                <td>{{ $report->location_details ?: 'N/A' }}</td>
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

    <section class="reporter-info handle-section">
        <h3>Section 1: Information About the Person/s Involved in the Incident</h3>
        <table class="handle-report-table">
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
            <h3>Additional Involved Persons</h3>
            <table class="handle-report-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>College/Department</th>
                        <th>Role</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report->additional_persons as $index => $person)
                        @php
                            $resolvedAdditionalPersonName = trim((string) (
                                $person['full_name']
                                ?? $person['name']
                                ?? $person['person_full_name']
                                ?? $person['fullname']
                                ?? $person['fullName']
                                ?? ''
                            ));
                            $resolvedAdditionalPersonDept = trim((string) (
                                $person['college_department']
                                ?? $person['department']
                                ?? $person['person_college_department']
                                ?? ''
                            ));
                            $resolvedAdditionalPersonRole = trim((string) (
                                $person['role']
                                ?? $person['person_role']
                                ?? $person['id_number']
                                ?? ''
                            ));
                            $resolvedAdditionalPersonEmail = trim((string) (
                                $person['email_address']
                                ?? $person['email']
                                ?? $person['person_email_address']
                                ?? ''
                            ));
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $resolvedAdditionalPersonName !== '' ? $resolvedAdditionalPersonName : 'N/A' }}</td>
                            <td>{{ $resolvedAdditionalPersonDept !== '' ? $resolvedAdditionalPersonDept : 'N/A' }}</td>
                            <td>{{ $resolvedAdditionalPersonRole !== '' ? $resolvedAdditionalPersonRole : 'N/A' }}</td>
                            <td>{{ $resolvedAdditionalPersonEmail !== '' ? $resolvedAdditionalPersonEmail : 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <section class="reporter-info handle-section">
        <h3>Section 2: Information About the Incident</h3>
        <table class="handle-report-table">
            <tr>
                <th>Description</th>
                <td>{{ $report->description ?: 'N/A' }}</td>
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
                <th>Please Specify</th>
                <td>{{ $report->location_details ?: 'N/A' }}</td>
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
    </section>

    <section class="reporter-info handle-section">
        <h3>Section 3: Information About the Informant</h3>
        <table class="handle-report-table">
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
        @can('decide', $report)
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

            @if($report->escalated_to_top_management)
                <div class="alert alert-info" style="margin-top: 10px;">
                    <strong>Escalated Report:</strong> This case has been escalated to Top Management.
                </div>
            @endif
        @else
            <div class="alert alert-info">
                @if($report->escalated_to_top_management)
                    <strong>Escalated Report:</strong> This report is pending Top Management decision.
                @else
                    <strong>Status:</strong> This report has been {{ $report->status }}.
                @endif
            </div>
        @endcan

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
                    <p style="background: #f5f5f5; color: #000000; padding: 10px; border-radius: 4px;">#{{ $report->id }}</p>
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
