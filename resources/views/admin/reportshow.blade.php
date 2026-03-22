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
         REPORT INFORMATION — Collapsible Groups
         ================================================================ --}}
    <section class="report-details handle-section">
        <h3 class="section-main-title">Report Information</h3>

        {{-- ── GROUP 1: Incident Overview (open by default) ── --}}
        <div class="detail-group">
            <button class="detail-group-toggle" aria-expanded="true" aria-controls="grp-incident-body">
                <span class="detail-group-icon">📋</span>
                <span class="detail-group-label">Incident Overview</span>
                <span class="detail-group-chevron">▾</span>
            </button>
            <div class="detail-group-body" id="grp-incident-body">
                <div class="rdt-stack">
                    @if($report->category)
                    <div class="rdt-row">
                        <span class="rdt-label">Category</span>
                        <span class="rdt-value"><span class="category-badge">{{ strtoupper($report->category->main_category_code) }} - {{ $report->category->main_category_name }} / {{ $report->category->name }}</span></span>
                    </div>
                    @endif
                    @if($report->description)
                    <div class="rdt-row rdt-row-block">
                        <span class="rdt-label">Description</span>
                        <span class="rdt-value">{{ $report->description }}</span>
                    </div>
                    @endif
                    @if($report->incident_date)
                    <div class="rdt-row">
                        <span class="rdt-label">Date of Incident</span>
                        <span class="rdt-value">{{ \Carbon\Carbon::parse($report->incident_date)->format('F d, Y') }}</span>
                    </div>
                    @endif
                    @if($report->incident_time)
                    <div class="rdt-row">
                        <span class="rdt-label">Time of Incident</span>
                        <span class="rdt-value">{{ \Carbon\Carbon::parse($report->incident_time)->format('h:i A') }}</span>
                    </div>
                    @endif
                    @if($report->location)
                    <div class="rdt-row">
                        <span class="rdt-label">Location</span>
                        <span class="rdt-value">{{ $report->location }}</span>
                    </div>
                    @endif
                    @if($report->location_details)
                    <div class="rdt-row">
                        <span class="rdt-label">Please Specify</span>
                        <span class="rdt-value">{{ $report->location_details }}</span>
                    </div>
                    @endif
                    @if($report->person_involvement)
                    <div class="rdt-row">
                        <span class="rdt-label">Person Involvement</span>
                        <span class="rdt-value">{{ ucfirst($report->person_involvement) }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── GROUP 3: Person/s Involved (collapsed) ── --}}
        @php
            $hasPersonData = $report->person_full_name || $report->person_college_department
                || $report->person_role || $report->person_contact_number
                || $report->person_email_address || $report->unknown_person_details
                || $report->technical_facility_details
                || (is_array($report->additional_persons) && count($report->additional_persons) > 0);
        @endphp
        @if($hasPersonData)
        <div class="detail-group">
            <button class="detail-group-toggle" aria-expanded="false" aria-controls="grp-persons-body">
                <span class="detail-group-icon">👤</span>
                <span class="detail-group-label">Person/s Involved</span>
                <span class="detail-group-chevron">▾</span>
            </button>
            <div class="detail-group-body collapsed" id="grp-persons-body">
                <div class="rdt-stack">
                    @if($report->person_full_name)
                    <div class="rdt-row"><span class="rdt-label">Name</span><span class="rdt-value">{{ $report->person_full_name }}</span></div>
                    @endif
                    @if($report->person_college_department)
                    <div class="rdt-row"><span class="rdt-label">College / Department</span><span class="rdt-value">{{ $report->person_college_department }}</span></div>
                    @endif
                    @if($report->person_role)
                    <div class="rdt-row"><span class="rdt-label">Role</span><span class="rdt-value">{{ $report->person_role }}</span></div>
                    @endif
                    @if($report->person_contact_number)
                    <div class="rdt-row"><span class="rdt-label">Contact Number</span><span class="rdt-value">{{ $report->person_contact_number }}</span></div>
                    @endif
                    @if($report->person_email_address)
                    <div class="rdt-row"><span class="rdt-label">Email</span><span class="rdt-value">{{ $report->person_email_address }}</span></div>
                    @endif
                    <div class="rdt-row"><span class="rdt-label">Multiple Persons?</span><span class="rdt-value">{{ $report->person_has_multiple ? 'Yes' : 'No' }}</span></div>
                    @if($report->unknown_person_details)
                    <div class="rdt-row rdt-row-block"><span class="rdt-label">Unknown Person Details</span><span class="rdt-value">{{ $report->unknown_person_details }}</span></div>
                    @endif
                    @if($report->technical_facility_details)
                    <div class="rdt-row rdt-row-block"><span class="rdt-label">Technical / Facility Details</span><span class="rdt-value">{{ $report->technical_facility_details }}</span></div>
                    @endif
                </div>

                @if(is_array($report->additional_persons) && count($report->additional_persons) > 0)
                    <div class="grp-sublabel">Additional Involved Persons</div>
                    <div class="desktop-subtable" style="padding: 0 1.25rem 1rem;">
                        <table class="handle-report-table">
                            <thead>
                                <tr><th>#</th><th>Name</th><th>College/Dept</th><th>Role</th><th>Email</th></tr>
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
                    <div class="mobile-person-cards" style="padding: 0 1rem 1rem;">
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
            </div>
        </div>
        @endif

        {{-- ── GROUP 4: Witnesses (collapsed) ── --}}
        @if($report->has_witnesses || (is_array($report->witness_details) && count($report->witness_details) > 0))
        <div class="detail-group">
            <button class="detail-group-toggle" aria-expanded="false" aria-controls="grp-witnesses-body">
                <span class="detail-group-icon">👁️</span>
                <span class="detail-group-label">Witnesses</span>
                <span class="detail-group-chevron">▾</span>
            </button>
            <div class="detail-group-body collapsed" id="grp-witnesses-body">
                <div class="rdt-stack">
                    <div class="rdt-row"><span class="rdt-label">Were There Witnesses?</span><span class="rdt-value">{{ $report->has_witnesses ? 'Yes' : 'No' }}</span></div>
                    @if($report->has_witnesses && is_array($report->witness_details) && count($report->witness_details) > 0)
                    <div class="rdt-row rdt-row-block">
                        <span class="rdt-label">Witness Details</span>
                        <ul class="detail-list">
                            @foreach($report->witness_details as $witness)
                                <li>
                                    {{ $witness['name'] ?? ($witness['full_name'] ?? 'Unnamed Witness') }}
                                    @if(!empty($witness['address'])) — {{ $witness['address'] }} @endif
                                    @if(!empty($witness['contact_number'])) | {{ $witness['contact_number'] }} @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
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
                    <div class="rdt-row rdt-row-block">
                        <span class="rdt-label">Additional Sheets</span>
                        <ul class="detail-list">
                            @foreach($sheetFiles as $si => $sf)
                                <li><a href="{{ asset('storage/' . $sf) }}" target="_blank" class="detail-link">📄 View Sheet {{ $si + 1 }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- ── GROUP 5: Informant (collapsed) ── --}}
        @if($report->informant_full_name || $report->informant_email_address)
        <div class="detail-group">
            <button class="detail-group-toggle" aria-expanded="false" aria-controls="grp-informant-body">
                <span class="detail-group-icon">📝</span>
                <span class="detail-group-label">Informant</span>
                <span class="detail-group-chevron">▾</span>
            </button>
            <div class="detail-group-body collapsed" id="grp-informant-body">
                <div class="rdt-stack">
                    @if($report->informant_full_name)
                    <div class="rdt-row"><span class="rdt-label">Full Name</span><span class="rdt-value">{{ $report->informant_full_name }}</span></div>
                    @endif
                    @if($report->informant_college_department)
                    <div class="rdt-row"><span class="rdt-label">College / Department</span><span class="rdt-value">{{ $report->informant_college_department }}</span></div>
                    @endif
                    @if($report->informant_role)
                    <div class="rdt-row"><span class="rdt-label">Role</span><span class="rdt-value">{{ $report->informant_role }}</span></div>
                    @endif
                    @if($report->informant_contact_number)
                    <div class="rdt-row"><span class="rdt-label">Contact Number</span><span class="rdt-value">{{ $report->informant_contact_number }}</span></div>
                    @endif
                    @if($report->informant_email_address)
                    <div class="rdt-row"><span class="rdt-label">Email</span><span class="rdt-value">{{ $report->informant_email_address }}</span></div>
                    @endif
                </div>
            </div>
        </div>
        @endif

    </section>

    {{-- ================================================================
         EVIDENCE
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
                            <p class="evidence-label"><a href="{{ $fileUrl }}" target="_blank" class="detail-link">View PDF {{ $index + 1 }}</a></p>
                        @else
                            <div class="evidence-file-placeholder">📁</div>
                            <p class="evidence-label"><a href="{{ $fileUrl }}" download class="detail-link">Download {{ strtoupper($ext) }} File</a></p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="no-data">No evidence files attached to this report.</p>
        @endif
    </section>

    {{-- ================================================================
         ACTIONS
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

    .section-main-title {
        padding: 1.2rem 1.5rem 0.5rem;
        margin: 0;
        font-size: 0.88rem;
        font-weight: 700;
        color: rgba(255,255,255,0.55);
        text-transform: uppercase;
        letter-spacing: 0.6px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    /* ── Collapsible groups ── */
    .detail-group { border-bottom: 1px solid rgba(255,255,255,0.1); }
    .detail-group:last-of-type { border-bottom: none; }

    .detail-group-toggle {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 1.5rem;
        background: transparent;
        border: none;
        color: #fff;
        font-size: 0.95rem;
        font-weight: 600;
        cursor: pointer;
        text-align: left;
    }

    .detail-group-toggle:hover { background: rgba(255,255,255,0.05); }
    .detail-group-icon { font-size: 1rem; flex-shrink: 0; }
    .detail-group-label { flex: 1; color: rgba(255,255,255,0.92); }

    .detail-group-chevron {
        font-size: 0.9rem;
        color: rgba(255,255,255,0.5);
        flex-shrink: 0;
        display: inline-block;
    }

    .detail-group-toggle[aria-expanded="true"] .detail-group-chevron {
        transform: rotate(180deg);
    }

    .detail-group-body { overflow: hidden; max-height: 9999px; }
    .detail-group-body.collapsed { max-height: 0 !important; overflow: hidden; }

    /* ── RDT Stack ── */
    .rdt-stack { padding: 0.25rem 1.5rem 0.875rem; }

    .rdt-row {
        display: flex;
        align-items: baseline;
        gap: 0.75rem;
        padding: 0.5rem 0;
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }

    .rdt-row:last-child { border-bottom: none; }

    .rdt-row.rdt-row-block { flex-direction: column; gap: 0.25rem; }

    .rdt-label {
        flex: 0 0 160px;
        font-size: 0.76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: rgba(255,255,255,0.5);
    }

    .rdt-value {
        flex: 1;
        font-size: 0.9rem;
        color: rgba(255,255,255,0.92);
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .grp-sublabel {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: rgba(255,255,255,0.45);
        padding: 0.5rem 1.5rem 0.25rem;
        border-top: 1px solid rgba(255,255,255,0.08);
    }

    /* ── Desktop/Mobile additional persons ── */
    .desktop-subtable { display: block; }
    .mobile-person-cards { display: none; }

    .mpc-card { border: 1px solid rgba(255,255,255,0.15); border-radius: 0.6rem; overflow: hidden; margin-bottom: 0.5rem; }
    .mpc-header { background: rgba(255,255,255,0.1); padding: 0.45rem 0.875rem; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: rgba(255,255,255,0.75); }
    .mpc-body { padding: 0.65rem 0.875rem; display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.88rem; color: rgba(255,255,255,0.9); }
    .mpc-body span { font-weight: 600; color: rgba(255,255,255,0.55); font-size: 0.76rem; margin-right: 0.25rem; }

    /* ── Misc ── */
    .detail-list { margin: 0; padding-left: 1.2rem; display: flex; flex-direction: column; gap: 0.3rem; color: rgba(255,255,255,0.9); font-size: 0.88rem; }
    .detail-link { color: #93c5fd; text-decoration: none; }
    .detail-link:hover { text-decoration: underline; }

    .evidence-img { max-width: 100%; border-radius: 8px; cursor: pointer; display: block; }
    .evidence-video { max-width: 100%; border-radius: 8px; display: block; }
    .evidence-file-placeholder { font-size: 2.5rem; text-align: center; padding: 1rem 0 0.5rem; }
    .evidence-label { text-align: center; margin-top: 6px; font-size: 0.8rem; color: rgba(255,255,255,0.7); }

    .report-action-btn { min-width: 160px; justify-content: center; text-align: center; }

    /* ── Mobile ── */
    @media (max-width: 768px) {
        .detail-group-toggle { padding: 0.875rem 1rem; font-size: 0.9rem; }
        .rdt-stack { padding: 0.25rem 1rem 0.75rem; }
        .rdt-row { flex-direction: column; gap: 0.15rem; padding: 0.55rem 0; }
        .rdt-label { flex: none; font-size: 0.7rem; }
        .rdt-value { font-size: 0.88rem; }
        .grp-sublabel { padding: 0.5rem 1rem 0.25rem; }
        .desktop-subtable { display: none; }
        .mobile-person-cards { display: block; }

        .action-buttons { display: flex; flex-direction: column; gap: 0.6rem; padding: 1rem; }
        .report-action-btn { width: 100% !important; min-width: 0 !important; box-sizing: border-box; }
        .action-buttons form { display: block; width: 100%; }
        .action-buttons form button { width: 100%; }
        .evidence-grid { grid-template-columns: 1fr 1fr; }
    }

    @media (max-width: 480px) {
        .rdt-stack { padding: 0.25rem 0.875rem 0.65rem; }
        .detail-group-toggle { padding: 0.75rem 0.875rem; }
        .evidence-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        document.querySelectorAll('.detail-group-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var expanded = btn.getAttribute('aria-expanded') === 'true';
                var body = document.getElementById(btn.getAttribute('aria-controls'));
                if (!body) return;
                btn.setAttribute('aria-expanded', String(!expanded));
                body.classList.toggle('collapsed', expanded);
            });
        });
    })();

    function openRejectModal() { document.getElementById('rejectModal').style.display = 'flex'; }
    function closeRejectModal() { document.getElementById('rejectModal').style.display = 'none'; document.getElementById('rejection_reason').value = ''; }
    function openImageModal(src) { document.getElementById('imageModal').style.display = 'flex'; document.getElementById('modalImage').src = src; }
    function closeImageModal() { document.getElementById('imageModal').style.display = 'none'; }

    window.onclick = function (e) { if (e.target.classList.contains('modal')) e.target.style.display = 'none'; };

    setTimeout(function () {
        document.querySelectorAll('.alert').forEach(function (el) {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 500);
        });
    }, 5000);

    document.getElementById('rejection_reason')?.addEventListener('input', function () {
        const btn = this.closest('form').querySelector('button[type="submit"]');
        btn.disabled = this.value.length < 10;
        btn.style.opacity = btn.disabled ? '0.5' : '1';
    });
</script>
@endpush