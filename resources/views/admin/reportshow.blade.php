@extends('layouts.admin')

@section('title', 'Report Details - Sincidentre Department Student Discipline Officer')

@section('page-title', 'Report Details')

@section('content')

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- ── Report Header ── --}}
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

    {{-- ================================================================
         SECTION 1 — Incident Information
         ================================================================ --}}
    <section class="report-details handle-section">
        <h3>Incident Information</h3>
        <div class="detail-table-wrapper">
            <table class="handle-report-table rdt">
                @if($report->category)
                <tr>
                    <th>Category</th>
                    <td><span class="category-badge">{{ strtoupper($report->category->main_category_code) }} - {{ $report->category->main_category_name }} / {{ $report->category->name }}</span></td>
                </tr>
                @endif
                @if($report->description)
                <tr><th>Description</th><td>{{ $report->description }}</td></tr>
                @endif
                @if($report->incident_date)
                <tr><th>Date of Incident</th><td>{{ \Carbon\Carbon::parse($report->incident_date)->format('F d, Y') }}</td></tr>
                @endif
                @if($report->incident_time)
                <tr><th>Time of Incident</th><td>{{ \Carbon\Carbon::parse($report->incident_time)->format('h:i A') }}</td></tr>
                @endif
                @if($report->location)
                <tr><th>Location</th><td>{{ $report->location }}</td></tr>
                @endif
                @if($report->location_details)
                <tr><th>Please Specify</th><td>{{ $report->location_details }}</td></tr>
                @endif
                @if($report->person_involvement)
                <tr><th>Person Involvement</th><td>{{ ucfirst($report->person_involvement) }}</td></tr>
                @endif
            </table>
        </div>
    </section>

    {{-- ================================================================
         SECTION 2 — Reporter Information
         ================================================================ --}}
    <section class="reporter-info handle-section">
        <h3>Reporter Information</h3>
        <div class="detail-table-wrapper">
            <table class="handle-report-table rdt">
                <tr>
                    <th>Name</th>
                    <td>{{ $report->user->first_name ?? 'Unknown' }} {{ $report->user->last_name ?? '' }}</td>
                </tr>
                @if($report->user->email ?? null)
                <tr><th>Email</th><td>{{ $report->user->email }}</td></tr>
                @endif
                @if($report->user->department->name ?? null)
                <tr><th>Department</th><td>{{ $report->user->department->name }}</td></tr>
                @endif
                <tr>
                    <th>Submitted At</th>
                    <td>
                        {{ $report->created_at->format('F d, Y') }}
                        <small class="text-muted-sm">({{ $report->created_at->diffForHumans() }})</small>
                    </td>
                </tr>
            </table>
        </div>
    </section>

    {{-- ================================================================
         SECTION 3 — Person/s Involved
         ================================================================ --}}
    <section class="reporter-info handle-section">
        <h3>Section 1: Person/s Involved in the Incident</h3>
        <div class="detail-table-wrapper">
            <table class="handle-report-table rdt">
                @if($report->person_full_name)
                <tr><th>Name</th><td>{{ $report->person_full_name }}</td></tr>
                @endif
                @if($report->person_college_department)
                <tr><th>College / Department</th><td>{{ $report->person_college_department }}</td></tr>
                @endif
                @if($report->person_role)
                <tr><th>Role</th><td>{{ $report->person_role }}</td></tr>
                @endif
                @if($report->person_contact_number)
                <tr><th>Contact Number</th><td>{{ $report->person_contact_number }}</td></tr>
                @endif
                @if($report->person_email_address)
                <tr><th>Email</th><td>{{ $report->person_email_address }}</td></tr>
                @endif
                <tr>
                    <th>Multiple Persons?</th>
                    <td>{{ $report->person_has_multiple ? 'Yes' : 'No' }}</td>
                </tr>
                @if($report->unknown_person_details)
                <tr><th>Unknown Person Details</th><td>{{ $report->unknown_person_details }}</td></tr>
                @endif
                @if($report->technical_facility_details)
                <tr><th>Technical / Facility Details</th><td>{{ $report->technical_facility_details }}</td></tr>
                @endif
            </table>
        </div>

        {{-- Additional Persons --}}
        @if(is_array($report->additional_persons) && count($report->additional_persons) > 0)
            <h3 style="padding-top:0;">Additional Involved Persons</h3>

            {{-- Desktop sub-table --}}
            <div class="detail-table-wrapper desktop-subtable">
                <table class="handle-report-table rdt">
                    <thead>
                        <tr>
                            <th>#</th><th>Name</th><th>College/Dept</th><th>Role</th><th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report->additional_persons as $index => $person)
                            @php
                                $apName  = trim((string)($person['full_name'] ?? $person['name'] ?? $person['person_full_name'] ?? ''));
                                $apDept  = trim((string)($person['college_department'] ?? $person['department'] ?? ''));
                                $apRole  = trim((string)($person['role'] ?? $person['person_role'] ?? ''));
                                $apEmail = trim((string)($person['email_address'] ?? $person['email'] ?? ''));
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $apName ?: 'N/A' }}</td>
                                <td>{{ $apDept ?: 'N/A' }}</td>
                                <td>{{ $apRole ?: 'N/A' }}</td>
                                <td>{{ $apEmail ?: 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile person cards --}}
            <div class="mobile-person-cards">
                @foreach($report->additional_persons as $index => $person)
                    @php
                        $apName  = trim((string)($person['full_name'] ?? $person['name'] ?? $person['person_full_name'] ?? ''));
                        $apDept  = trim((string)($person['college_department'] ?? $person['department'] ?? ''));
                        $apRole  = trim((string)($person['role'] ?? $person['person_role'] ?? ''));
                        $apEmail = trim((string)($person['email_address'] ?? $person['email'] ?? ''));
                    @endphp
                    <div class="mpc-card">
                        <div class="mpc-header">Person {{ $index + 2 }}</div>
                        <div class="mpc-body">
                            @if($apName)<div><span>Name:</span> {{ $apName }}</div>@endif
                            @if($apDept)<div><span>Dept:</span> {{ $apDept }}</div>@endif
                            @if($apRole)<div><span>Role:</span> {{ $apRole }}</div>@endif
                            @if($apEmail)<div><span>Email:</span> {{ $apEmail }}</div>@endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- ================================================================
         SECTION 4 — Incident Details
         ================================================================ --}}
    <section class="reporter-info handle-section">
        <h3>Section 2: Information About the Incident</h3>
        <div class="detail-table-wrapper">
            <table class="handle-report-table rdt">
                @if($report->description)
                <tr><th>Description</th><td>{{ $report->description }}</td></tr>
                @endif
                @if($report->incident_date)
                <tr><th>Date</th><td>{{ \Carbon\Carbon::parse($report->incident_date)->format('F d, Y') }}</td></tr>
                @endif
                @if($report->incident_time)
                <tr><th>Time</th><td>{{ \Carbon\Carbon::parse($report->incident_time)->format('h:i A') }}</td></tr>
                @endif
                @if($report->location)
                <tr><th>Location</th><td>{{ $report->location }}</td></tr>
                @endif
                @if($report->location_details)
                <tr><th>Please Specify</th><td>{{ $report->location_details }}</td></tr>
                @endif
                <tr>
                    <th>Witnesses?</th>
                    <td>{{ $report->has_witnesses ? 'Yes' : 'No' }}</td>
                </tr>
                @if($report->has_witnesses && is_array($report->witness_details) && count($report->witness_details) > 0)
                <tr>
                    <th>Witness Details</th>
                    <td>
                        <ul class="detail-list">
                            @foreach($report->witness_details as $witness)
                                <li>
                                    {{ $witness['name'] ?? ($witness['full_name'] ?? 'Unnamed Witness') }}
                                    @if(!empty($witness['address'])) — {{ $witness['address'] }} @endif
                                    @if(!empty($witness['contact_number'])) | {{ $witness['contact_number'] }} @endif
                                </li>
                            @endforeach
                        </ul>
                    </td>
                </tr>
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
                <tr>
                    <th>Additional Sheets</th>
                    <td>
                        <ul class="detail-list">
                            @foreach($sheetFiles as $si => $sheetFile)
                                <li><a href="{{ asset('storage/' . $sheetFile) }}" target="_blank" class="detail-link">📄 View Sheet {{ $si + 1 }}</a></li>
                            @endforeach
                        </ul>
                    </td>
                </tr>
                @endif
            </table>
        </div>
    </section>

    {{-- ================================================================
         SECTION 5 — Informant
         ================================================================ --}}
    @if($report->informant_full_name || $report->informant_email_address)
    <section class="reporter-info handle-section">
        <h3>Section 3: Information About the Informant</h3>
        <div class="detail-table-wrapper">
            <table class="handle-report-table rdt">
                @if($report->informant_full_name)
                <tr><th>Full Name</th><td>{{ $report->informant_full_name }}</td></tr>
                @endif
                @if($report->informant_college_department)
                <tr><th>College / Department</th><td>{{ $report->informant_college_department }}</td></tr>
                @endif
                @if($report->informant_role)
                <tr><th>Role</th><td>{{ $report->informant_role }}</td></tr>
                @endif
                @if($report->informant_contact_number)
                <tr><th>Contact Number</th><td>{{ $report->informant_contact_number }}</td></tr>
                @endif
                @if($report->informant_email_address)
                <tr><th>Email</th><td>{{ $report->informant_email_address }}</td></tr>
                @endif
            </table>
        </div>
    </section>
    @endif

    {{-- ================================================================
         SECTION 6 — Evidence
         ================================================================ --}}
    <section class="evidence-section handle-section">
        <h3>Evidence &amp; Attachments</h3>
        @php
            $evidences = $report->evidence
                ? (is_array($report->evidence) ? $report->evidence : json_decode($report->evidence, true))
                : [];
            $evidences = is_array($evidences) ? $evidences : [];
        @endphp

        @if(count($evidences) > 0)
            <div class="evidence-grid">
                @foreach($evidences as $index => $file)
                    @php
                        $ext     = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        $fileUrl = asset('storage/' . $file);
                        $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp']);
                        $isVideo = in_array($ext, ['mp4','webm','ogg','avi','mov']);
                        $isPdf   = $ext === 'pdf';
                    @endphp
                    <div class="evidence-item">
                        @if($isImage)
                            <img src="{{ $fileUrl }}" alt="Evidence {{ $index + 1 }}"
                                 onclick="openImageModal('{{ $fileUrl }}')"
                                 class="evidence-img">
                            <p class="evidence-label">🖼️ Image {{ $index + 1 }}</p>
                        @elseif($isVideo)
                            <video controls class="evidence-video">
                                <source src="{{ $fileUrl }}" type="video/{{ $ext }}">
                            </video>
                            <p class="evidence-label">🎬 Video {{ $index + 1 }}</p>
                        @elseif($isPdf)
                            <div class="evidence-file-placeholder">📄</div>
                            <p class="evidence-label">
                                <a href="{{ $fileUrl }}" target="_blank" class="detail-link">View PDF {{ $index + 1 }}</a>
                            </p>
                        @else
                            <div class="evidence-file-placeholder">📁</div>
                            <p class="evidence-label">
                                <a href="{{ $fileUrl }}" download class="detail-link">Download {{ strtoupper($ext) }} File</a>
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="no-data">No evidence files attached to this report.</p>
        @endif
    </section>

    {{-- ================================================================
         SECTION 7 — Actions
         ================================================================ --}}
    <section class="action-buttons">
        @can('decide', $report)
            <form action="{{ route('admin.reports.approve', $report->id) }}" method="POST" style="display:inline-block;">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn-approve report-action-btn"
                    onclick="return confirm('Are you sure you want to approve this report?')">
                    Approve Report
                </button>
            </form>

            <button type="button" class="btn-reject report-action-btn" onclick="openRejectModal()">
                Reject Report
            </button>

            @can('escalate', $report)
                <form action="{{ route('admin.reports.escalate', $report->id) }}" method="POST" style="display:inline-block; margin-left: 8px;">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn-escalate report-action-btn"
                        onclick="return confirm('Are you sure you want to escalate this report to Top Management?')">
                        Escalate
                    </button>
                </form>
            @endcan

            @if($report->escalated_to_top_management)
                <div class="alert alert-info" style="margin-top:10px;">
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

        <a href="{{ route('admin.reports') }}" class="btn btn-secondary report-action-btn">← Back to New Reports</a>
    </section>

    {{-- Reject Modal --}}
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
                    <p style="background:#f5f5f5;color:#000;padding:10px;border-radius:4px;">#{{ $report->id }}</p>
                </div>
                <div class="form-group">
                    <label for="rejection_reason">Reason for Rejection *</label>
                    <textarea id="rejection_reason" name="rejection_reason" rows="5"
                        placeholder="Please provide a clear and detailed reason for rejecting this report. This will be visible to the reporter."
                        required></textarea>
                    <small style="color:#999;">Minimum 10 characters required</small>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeRejectModal()" class="btn-cancel">Cancel</button>
                    <button type="submit" class="btn-reject"
                        onclick="return confirm('Are you sure you want to reject this report?')">
                        Reject Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Image Modal --}}
    <div id="imageModal" class="modal">
        <div class="modal-content" style="max-width:90%;max-height:90vh;">
            <span class="close" onclick="closeImageModal()">&times;</span>
            <img id="modalImage" src="" alt="Evidence" style="width:100%;height:auto;border-radius:8px;">
        </div>
    </div>

@endsection

@push('styles')
<style>
    /* ── Report header ── */
    .report-id-line {
        margin: 5px 0;
        color: rgba(255,255,255,0.9);
        font-weight: 700;
    }

    .text-muted-sm {
        color: rgba(255,255,255,0.5);
        font-size: 0.8rem;
        margin-left: 0.35rem;
    }

    /* ── Remove all row/cell borders from the 5 detail sections ── */
    .rdt,
    .rdt tr,
    .rdt th,
    .rdt td {
        border: none !important;
    }

    /* ── Detail list (witnesses, sheets) ── */
    .detail-list {
        margin: 0;
        padding-left: 1.2rem;
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
    }

    .detail-link {
        color: #93c5fd;
        text-decoration: none;
    }

    .detail-link:hover { text-decoration: underline; }

    /* ── Evidence ── */
    .evidence-img {
        max-width: 100%;
        border-radius: 8px;
        cursor: pointer;
        display: block;
    }

    .evidence-video {
        max-width: 100%;
        border-radius: 8px;
        display: block;
    }

    .evidence-file-placeholder {
        font-size: 2.5rem;
        text-align: center;
        padding: 1rem 0 0.5rem;
    }

    .evidence-label {
        text-align: center;
        margin-top: 6px;
        font-size: 0.8rem;
        color: rgba(255,255,255,0.7);
    }

    /* ── Action buttons ── */
    .report-action-btn {
        min-width: 160px;
        justify-content: center;
        text-align: center;
    }

    /* ── Desktop sub-table / mobile person cards ── */
    .desktop-subtable { display: block; }
    .mobile-person-cards { display: none; }

    .mpc-card {
        border: 1px solid rgba(255,255,255,0.15);
        border-radius: 0.6rem;
        overflow: hidden;
        margin-bottom: 0.6rem;
    }

    .mpc-header {
        background: rgba(255,255,255,0.1);
        padding: 0.45rem 0.875rem;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: rgba(255,255,255,0.75);
    }

    .mpc-body {
        padding: 0.65rem 0.875rem;
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
        font-size: 0.88rem;
        color: rgba(255,255,255,0.9);
    }

    .mpc-body span {
        font-weight: 600;
        color: rgba(255,255,255,0.55);
        font-size: 0.76rem;
        margin-right: 0.25rem;
    }

    /* ================================================================
       MOBILE
       ================================================================ */
    @media (max-width: 768px) {

        /* Stack th/td vertically */
        .rdt { display: block; width: 100%; }

        .rdt tr {
            display: block;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding: 0.65rem 0.875rem;
        }

        .rdt tr:last-child { border-bottom: none; }

        .rdt th,
        .rdt td {
            display: block;
            width: 100%;
            padding: 0 !important;
            border: none !important;
            background: transparent !important;
            white-space: normal;
            word-break: break-word;
        }

        .rdt th {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: rgba(255,255,255,0.55);
            margin-bottom: 0.2rem;
        }

        .rdt td {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.95);
        }

        /* Hide multi-column sub-table, show cards */
        .desktop-subtable { display: none; }
        .mobile-person-cards { display: block; }

        /* Action buttons full width */
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            padding: 1rem;
        }

        .report-action-btn {
            width: 100% !important;
            min-width: 0 !important;
            box-sizing: border-box;
        }

        .action-buttons form {
            display: block;
            width: 100%;
        }

        .action-buttons form button {
            width: 100%;
        }

        /* Evidence grid single column */
        .evidence-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 480px) {
        .rdt tr { padding: 0.55rem 0.75rem; }
        .evidence-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@push('scripts')
<script>
    function openRejectModal() {
        document.getElementById('rejectModal').style.display = 'flex';
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').style.display = 'none';
        document.getElementById('rejection_reason').value = '';
    }

    function openImageModal(src) {
        document.getElementById('imageModal').style.display = 'flex';
        document.getElementById('modalImage').src = src;
    }

    function closeImageModal() {
        document.getElementById('imageModal').style.display = 'none';
    }

    window.onclick = function (e) {
        if (e.target.classList.contains('modal')) e.target.style.display = 'none';
    };

    // Auto-hide alerts
    setTimeout(function () {
        document.querySelectorAll('.alert').forEach(function (el) {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 500);
        });
    }, 5000);

    // Validate rejection reason length
    document.getElementById('rejection_reason')?.addEventListener('input', function () {
        const btn = this.closest('form').querySelector('button[type="submit"]');
        btn.disabled = this.value.length < 10;
        btn.style.opacity = btn.disabled ? '0.5' : '1';
    });
</script>
@endpush