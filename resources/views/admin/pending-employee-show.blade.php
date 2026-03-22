@extends('layouts.admin')

@section('title', 'Review Employee Registration - Sincidentre')

@section('content')

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ── Registration Header ── --}}
    <div class="report-header">
        <div>
            <p class="report-id-line">Registration #{{ $registration->id }}</p>
        </div>
        <div>
            @if($registration->status === 'pending')
                <span class="status pending">Pending Review</span>
            @elseif($registration->status === 'approved')
                <span class="status approved">Approved</span>
            @else
                <span class="status rejected">Rejected</span>
            @endif
        </div>
    </div>

    {{-- ================================================================
         REGISTRATION INFORMATION — Collapsible Groups
         ================================================================ --}}
    <section class="handle-section">
        <h3 class="section-main-title">Registration Details</h3>

        {{-- ── GROUP 1: Personal Information (open by default) ── --}}
        <div class="detail-group">
            <button class="detail-group-toggle" aria-expanded="true" aria-controls="grp-personal-body">
                <span class="detail-group-icon">👤</span>
                <span class="detail-group-label">Personal Information</span>
                <span class="detail-group-chevron">▾</span>
            </button>
            <div class="detail-group-body" id="grp-personal-body">
                <div class="rdt-stack">
                    <div class="rdt-row">
                        <span class="rdt-label">First Name</span>
                        <span class="rdt-value">{{ $registration->first_name }}</span>
                    </div>
                    <div class="rdt-row">
                        <span class="rdt-label">Last Name</span>
                        <span class="rdt-value">{{ $registration->last_name }}</span>
                    </div>
                    <div class="rdt-row">
                        <span class="rdt-label">Username</span>
                        <span class="rdt-value">
                            <span class="reg-badge reg-badge-info">{{ $registration->username }}</span>
                        </span>
                    </div>
                    <div class="rdt-row">
                        <span class="rdt-label">Email Address</span>
                        <span class="rdt-value">{{ $registration->email }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── GROUP 2: Registration Timeline (collapsed) ── --}}
        <div class="detail-group">
            <button class="detail-group-toggle" aria-expanded="false" aria-controls="grp-timeline-body">
                <span class="detail-group-icon">🕐</span>
                <span class="detail-group-label">Registration Timeline</span>
                <span class="detail-group-chevron">▾</span>
            </button>
            <div class="detail-group-body collapsed" id="grp-timeline-body">
                <div class="rdt-stack">
                    <div class="rdt-row">
                        <span class="rdt-label">Submitted</span>
                        <span class="rdt-value">{{ $registration->created_at->format('F d, Y \a\t h:i A') }}</span>
                    </div>
                    @if($registration->reviewed_at)
                    <div class="rdt-row">
                        <span class="rdt-label">Reviewed</span>
                        <span class="rdt-value">{{ $registration->reviewed_at->format('F d, Y \a\t h:i A') }}</span>
                    </div>
                    @if($registration->reviewer)
                    <div class="rdt-row">
                        <span class="rdt-label">Reviewed By</span>
                        <span class="rdt-value">{{ $registration->reviewer->first_name }} {{ $registration->reviewer->last_name }}</span>
                    </div>
                    @endif
                    @if($registration->status === 'rejected' && $registration->rejection_reason)
                    <div class="rdt-row rdt-row-block">
                        <span class="rdt-label">Rejection Reason</span>
                        <span class="rdt-value rdt-rejection">{{ $registration->rejection_reason }}</span>
                    </div>
                    @endif
                    @endif
                </div>
            </div>
        </div>

    </section>

    {{-- ================================================================
         REVIEW ACTIONS
         ================================================================ --}}
    @if($registration->status === 'pending')
    <section class="action-buttons reg-action-section">
        <form action="{{ route('admin.pending-employees.approve', $registration->id) }}" method="POST"
              onsubmit="return confirm('Are you sure you want to APPROVE this employee registration? An account will be created and the applicant will be notified via email.');">
            @csrf
            <button type="submit" class="btn-approve report-action-btn">
                Approve Registration
            </button>
        </form>

        <button type="button" class="btn-reject report-action-btn" onclick="showRejectModal()">
            Reject Registration
        </button>
    </section>
    @endif

    <div class="reg-back-link">
        <a href="{{ route('admin.pending-employees') }}" class="btn btn-secondary">← Back to Pending Registrations</a>
    </div>

    {{-- Reject Modal --}}
    <div id="rejectModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reject Registration #{{ $registration->id }}</h3>
                <span class="close" onclick="closeRejectModal()">&times;</span>
            </div>
            <form action="{{ route('admin.pending-employees.reject', $registration->id) }}" method="POST">
                @csrf
                <div class="form-group" style="padding: 1.25rem 1.5rem 0;">
                    <label for="rejection_reason">Rejection Reason <span style="color:#ef4444;">*</span></label>
                    <textarea id="rejection_reason" name="rejection_reason" rows="5"
                        placeholder="Please provide a reason for rejecting this registration request..."
                        required></textarea>
                    <small style="display:block;margin-top:0.4rem;color:rgba(255,255,255,0.55);font-size:0.82rem;">This reason will be sent to the applicant via email.</small>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeRejectModal()">Cancel</button>
                    <button type="submit" class="btn-reject">Reject Registration</button>
                </div>
            </form>
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

    .rdt-rejection {
        color: #fca5a5;
        font-style: italic;
    }

    /* ── Badge ── */
    .reg-badge {
        display: inline-block;
        padding: 0.2rem 0.65rem;
        border-radius: 2rem;
        font-size: 0.82rem;
        font-weight: 600;
    }

    .reg-badge-info {
        background: rgba(59,130,246,0.15);
        color: #93c5fd;
        border: 1px solid rgba(59,130,246,0.3);
    }

    /* ── Action section ── */
    .reg-action-section {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: center;
    }

    .reg-action-section form { display: contents; }

    .report-action-btn {
        min-width: 160px;
        justify-content: center;
        text-align: center;
    }

    .reg-back-link {
        padding: 1rem 1.25rem 0.5rem;
    }

    /* ── Mobile ── */
    @media (max-width: 768px) {
        .detail-group-toggle { padding: 0.875rem 1rem; font-size: 0.9rem; }
        .rdt-stack { padding: 0.25rem 1rem 0.75rem; }

        .rdt-row { flex-direction: column; gap: 0.15rem; padding: 0.55rem 0; }
        .rdt-label { flex: none; font-size: 0.7rem; }
        .rdt-value { font-size: 0.88rem; }

        .reg-action-section {
            flex-direction: column;
            padding: 0 1rem;
        }

        .reg-action-section form,
        .reg-action-section button {
            width: 100%;
            display: block;
        }

        .report-action-btn {
            width: 100% !important;
            min-width: 0 !important;
            box-sizing: border-box;
        }

        .reg-back-link { padding: 0.875rem 1rem 0.5rem; }
        .reg-back-link .btn { width: 100%; text-align: center; justify-content: center; }
    }

    @media (max-width: 480px) {
        .rdt-stack { padding: 0.25rem 0.875rem 0.65rem; }
        .detail-group-toggle { padding: 0.75rem 0.875rem; }
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

    function showRejectModal() {
        document.getElementById('rejectModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeRejectModal();
    });

    window.onclick = function (e) {
        if (e.target.classList.contains('modal')) closeRejectModal();
    };
</script>
@endpush