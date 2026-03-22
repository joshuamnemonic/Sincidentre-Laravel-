@extends('layouts.admin')

@section('title', 'Review Employee Registration - Sincidentre')

@section('content')
<div class="page-container">
    <header class="page-header">
        <h1>Review Employee Registration</h1>
        <p>Approve or reject this employee registration request</p>
    </header>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-error">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="content-card">
        <div class="registration-details">
            <h2>Registration Details</h2>

            <div class="detail-group">
                <div class="detail-item">
                    <span class="detail-label">Registration ID:</span>
                    <span class="detail-value">#{{ $registration->id }}</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        @if($registration->status === 'pending')
                            <span class="badge badge-warning">Pending Review</span>
                        @elseif($registration->status === 'approved')
                            <span class="badge badge-success">Approved</span>
                        @else
                            <span class="badge badge-error">Rejected</span>
                        @endif
                    </span>
                </div>
            </div>

            <div class="detail-group">
                <h3>Personal Information</h3>

                <div class="detail-item">
                    <span class="detail-label">First Name:</span>
                    <span class="detail-value">{{ $registration->first_name }}</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Last Name:</span>
                    <span class="detail-value">{{ $registration->last_name }}</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Username:</span>
                    <span class="detail-value">
                        <span class="badge badge-info">{{ $registration->username }}</span>
                    </span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Email Address:</span>
                    <span class="detail-value">{{ $registration->email }}</span>
                </div>
            </div>

            <div class="detail-group">
                <h3>Registration Timeline</h3>

                <div class="detail-item">
                    <span class="detail-label">Submitted:</span>
                    <span class="detail-value">{{ $registration->created_at->format('F d, Y \a\t h:i A') }}</span>
                </div>

                @if($registration->reviewed_at)
                    <div class="detail-item">
                        <span class="detail-label">Reviewed:</span>
                        <span class="detail-value">{{ $registration->reviewed_at->format('F d, Y \a\t h:i A') }}</span>
                    </div>

                    @if($registration->reviewer)
                        <div class="detail-item">
                            <span class="detail-label">Reviewed By:</span>
                            <span class="detail-value">{{ $registration->reviewer->first_name }} {{ $registration->reviewer->last_name }}</span>
                        </div>
                    @endif

                    @if($registration->status === 'rejected' && $registration->rejection_reason)
                        <div class="detail-item">
                            <span class="detail-label">Rejection Reason:</span>
                            <span class="detail-value rejection-reason">{{ $registration->rejection_reason }}</span>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        @if($registration->status === 'pending')
            <div class="action-section">
                <h3>Review Actions</h3>

                <div class="action-buttons-container">
                    <!-- Approve Button -->
                    <form action="{{ route('admin.pending-employees.approve', $registration->id) }}"
                          method="POST"
                          onsubmit="return confirm('Are you sure you want to APPROVE this employee registration? An account will be created and the applicant will be notified via email.');">
                        @csrf
                        <button type="submit" class="btn btn-success btn-lg">
                            <span class="btn-icon">✓</span>
                            Approve Registration
                        </button>
                    </form>

                    <!-- Reject Button -->
                    <button type="button" class="btn btn-error btn-lg" onclick="showRejectModal()">
                        <span class="btn-icon">✗</span>
                        Reject Registration
                    </button>
                </div>
            </div>
        @endif

        <div class="back-link">
            <a href="{{ route('admin.pending-employees') }}" class="btn btn-secondary">
                ← Back to Pending Registrations
            </a>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="modal" style="display: none;">
    <div class="modal-backdrop" onclick="closeRejectModal()"></div>
    <div class="modal-panel">
        <div class="modal-header">
            <h3>Reject Registration</h3>
            <button type="button" class="modal-close" onclick="closeRejectModal()">&times;</button>
        </div>
        <form action="{{ route('admin.pending-employees.reject', $registration->id) }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label for="rejection_reason">Rejection Reason <span class="required">*</span></label>
                    <textarea id="rejection_reason"
                              name="rejection_reason"
                              rows="5"
                              placeholder="Please provide a reason for rejecting this registration request..."
                              required></textarea>
                    <small class="form-hint">This reason will be sent to the applicant via email.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="btn btn-error">Reject Registration</button>
            </div>
        </form>
    </div>
</div>

<style>
.registration-details h2 {
    color: rgba(255, 255, 255, 0.95);
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
}

.registration-details h3 {
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 1rem;
    font-size: 1.2rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.detail-group {
    margin-bottom: 2rem;
}

.detail-item {
    display: flex;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-label {
    flex: 0 0 200px;
    color: rgba(255, 255, 255, 0.7);
    font-weight: 500;
}

.detail-value {
    flex: 1;
    color: rgba(255, 255, 255, 0.95);
}

.rejection-reason {
    color: #ef4444;
    font-style: italic;
}

.badge {
    display: inline-block;
    padding: 0.35rem 0.85rem;
    border-radius: 12px;
    font-size: 0.9rem;
    font-weight: 500;
}

.badge-info {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.badge-warning {
    background: rgba(251, 191, 36, 0.15);
    color: #fbbf24;
    border: 1px solid rgba(251, 191, 36, 0.3);
}

.badge-success {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.badge-error {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.action-section {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 2px solid rgba(255, 255, 255, 0.1);
}

.action-section h3 {
    color: rgba(255, 255, 255, 0.95);
    margin-bottom: 1.5rem;
}

.action-buttons-container {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn-lg {
    padding: 0.85rem 1.75rem;
    font-size: 1.05rem;
    font-weight: 600;
}

.btn-icon {
    margin-right: 0.5rem;
    font-size: 1.2rem;
}

.back-link {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
}

/* Modal Styles */
.modal {
    position: fixed;
    inset: 0;
    z-index: 3000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
}

.modal-panel {
    position: relative;
    z-index: 1;
    width: min(500px, 100%);
    background: linear-gradient(180deg, #0b1f53, #0a1536);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-header h3 {
    margin: 0;
    color: #ffffff;
    font-size: 1.25rem;
}

.modal-close {
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.7);
    font-size: 1.75rem;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 4px;
    transition: all 0.2s ease;
    line-height: 1;
}

.modal-close:hover {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.1);
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1.25rem 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500;
}

.required {
    color: #ef4444;
}

.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    color: #ffffff;
    font-family: inherit;
    resize: vertical;
}

.form-group textarea:focus {
    outline: none;
    border-color: rgba(99, 102, 241, 0.5);
    background: rgba(255, 255, 255, 0.08);
}

.form-hint {
    display: block;
    margin-top: 0.5rem;
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.85rem;
}

@media (max-width: 768px) {
    .detail-item {
        flex-direction: column;
        gap: 0.25rem;
    }

    .detail-label {
        flex: none;
    }

    .action-buttons-container {
        flex-direction: column;
    }

    .action-buttons-container .btn {
        width: 100%;
    }
}
</style>

<script>
function showRejectModal() {
    document.getElementById('rejectModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
    document.body.style.overflow = '';
}

// Close modal on escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeRejectModal();
    }
});
</script>
@endsection
