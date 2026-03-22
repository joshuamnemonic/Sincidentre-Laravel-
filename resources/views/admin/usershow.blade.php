@extends('layouts.admin')

@section('title', 'User Details - Sincidentre Department Student Discipline Officer')

@section('page-title', '👤 User Details')

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <section>
        <h2>Account Information</h2>

        {{-- ── DESKTOP: original bordered table ── --}}
        <div class="desktop-account-table">
            <table border="1" cellspacing="0" cellpadding="8" width="100%">
                <tr><th>User ID</th><td>#{{ $user->id }}</td></tr>
                <tr><th>Full Name</th><td>{{ $user->first_name }} {{ $user->last_name }}</td></tr>
                <tr><th>Email</th><td>{{ $user->email }}</td></tr>
                <tr><th>Department</th><td>{{ $user->department->name ?? 'N/A' }}</td></tr>
                <tr><th>Phone</th><td>{{ $user->phone ?? 'N/A' }}</td></tr>
                <tr>
                    <th>Role</th>
                    <td>
                        <span class="role-badge {{ $user->is_department_student_discipline_officer ? 'admin' : 'user' }}">
                            {{ $user->is_department_student_discipline_officer ? 'Department Student Discipline Officer' : 'User' }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        @if($user->status === 'active')
                            <span class="status active">Active</span>
                        @elseif($user->status === 'suspended')
                            <span class="status suspended">Suspended</span>
                        @else
                            <span class="status deactivated">Deactivated</span>
                        @endif
                    </td>
                </tr>
                <tr><th>Date Registered</th><td>{{ $user->created_at->format('F d, Y h:i A') }}</td></tr>
                <tr><th>Last Updated</th><td>{{ $user->updated_at->format('F d, Y h:i A') }}</td></tr>
                <tr>
                    <th>Total Reports Submitted</th>
                    <td><strong>{{ $user->reports->count() }}</strong> {{ Str::plural('report', $user->reports->count()) }}</td>
                </tr>
            </table>
        </div>

        {{-- ── MOBILE: info card ── --}}
        <div class="mobile-account-card">

            {{-- Header: name + status --}}
            <div class="mac-header">
                <div class="mac-name">{{ $user->first_name }} {{ $user->last_name }}</div>
                <div class="mac-header-badges">
                    <span class="role-badge {{ $user->is_department_student_discipline_officer ? 'admin' : 'user' }}">
                        {{ $user->is_department_student_discipline_officer ? 'DSDO' : 'User' }}
                    </span>
                    @if($user->status === 'active')
                        <span class="status active">Active</span>
                    @elseif($user->status === 'suspended')
                        <span class="status suspended">Suspended</span>
                    @else
                        <span class="status deactivated">Deactivated</span>
                    @endif
                </div>
            </div>

            {{-- Fields --}}
            <div class="mac-fields">
                <div class="mac-field">
                    <span class="mac-field-label">User ID</span>
                    <span class="mac-field-value">#{{ $user->id }}</span>
                </div>
                <div class="mac-field">
                    <span class="mac-field-label">Email</span>
                    <span class="mac-field-value">{{ $user->email }}</span>
                </div>
                <div class="mac-field">
                    <span class="mac-field-label">Department</span>
                    <span class="mac-field-value">{{ $user->department->name ?? 'N/A' }}</span>
                </div>
                <div class="mac-field">
                    <span class="mac-field-label">Phone</span>
                    <span class="mac-field-value">{{ $user->phone ?? 'N/A' }}</span>
                </div>
                <div class="mac-field">
                    <span class="mac-field-label">Registered</span>
                    <span class="mac-field-value">{{ $user->created_at->format('M d, Y h:i A') }}</span>
                </div>
                <div class="mac-field">
                    <span class="mac-field-label">Last Updated</span>
                    <span class="mac-field-value">{{ $user->updated_at->format('M d, Y h:i A') }}</span>
                </div>
                <div class="mac-field">
                    <span class="mac-field-label">Reports</span>
                    <span class="mac-field-value">
                        <strong>{{ $user->reports->count() }}</strong> {{ Str::plural('report', $user->reports->count()) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Suspension / Deactivation alerts (both views) --}}
        @if($user->status === 'suspended' && $user->suspension_reason)
            <div class="alert alert-warning" style="margin-top: 20px;">
                <h4>🚫 Account Suspended</h4>
                <p><strong>Reason:</strong> {{ $user->suspension_reason }}</p>
                <p><strong>Suspended on:</strong> {{ $user->suspended_at ? \Carbon\Carbon::parse($user->suspended_at)->format('F d, Y h:i A') : 'N/A' }}</p>
                @if($user->suspendedBy)
                    <p><strong>Suspended by:</strong> {{ $user->suspendedBy->name }}</p>
                @endif
            </div>
        @elseif($user->status === 'deactivated')
            <div class="alert alert-danger" style="margin-top: 20px;">
                <h4>🗑️ Account Deactivated</h4>
                <p>This account has been deactivated and the user cannot log in.</p>
                @if($user->suspension_reason)
                    <p><strong>Reason:</strong> {{ $user->suspension_reason }}</p>
                @endif
            </div>
        @endif

        @if(!$user->is_department_student_discipline_officer)
            <h3 style="margin-top: 30px;">User Actions</h3>
            <div class="usershow-actions">
                <a href="{{ route('admin.users.edit', $user->id) }}" class="btn-edit">✏️ Edit User</a>

                @if($user->status === 'active')
                    <button onclick="openSuspendModal()" class="btn-suspend">🚫 Suspend User</button>
                    <button onclick="openDeactivateModal()" class="btn-delete">🗑️ Deactivate Account</button>
                @elseif($user->status === 'suspended')
                    <form method="POST" action="{{ route('admin.users.activate', $user->id) }}" class="usershow-action-form">
                        @csrf
                        <button type="submit" class="btn-activate" onclick="return confirm('Reactivate this user?')">✅ Reactivate User</button>
                    </form>
                    <button onclick="openDeactivateModal()" class="btn-delete">🗑️ Deactivate Account</button>
                @elseif($user->status === 'deactivated')
                    <form method="POST" action="{{ route('admin.users.activate', $user->id) }}" class="usershow-action-form">
                        @csrf
                        <button type="submit" class="btn-activate" onclick="return confirm('Reactivate this user?')">✅ Reactivate Account</button>
                    </form>
                @endif
            </div>
        @else
            <div class="alert alert-info" style="margin-top: 20px;">
                <strong>Note:</strong> This is a Department Student Discipline Officer account and cannot be suspended or deactivated from this interface.
            </div>
        @endif
    </section>

    {{-- ── Activity Summary ── --}}
    <section style="margin-top: 40px;">
        <h2>📊 Activity Summary</h2>
        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div class="stat-card">
                <h4>Total Reports</h4>
                <p class="stat-number">{{ $user->reports->count() }}</p>
            </div>
            <div class="stat-card">
                <h4>Pending Reports</h4>
                <p class="stat-number" style="color: #ffc107;">{{ $user->reports->where('status', 'Pending')->count() }}</p>
            </div>
            <div class="stat-card">
                <h4>Approved Reports</h4>
                <p class="stat-number" style="color: #28a745;">{{ $user->reports->where('status', 'Approved')->count() }}</p>
            </div>
            <div class="stat-card">
                <h4>Resolved Reports</h4>
                <p class="stat-number" style="color: #007bff;">{{ $user->reports->where('status', 'Resolved')->count() }}</p>
            </div>
        </div>
    </section>

    {{-- ── Reports Section ── --}}
    @if($user->reports->count() > 0)
        <section style="margin-top: 40px;">
            <h2>📋 Reports Submitted by This User</h2>

            {{-- Desktop table (original, unchanged) --}}
            <div class="table-wrapper desktop-reports-table">
                <table border="1" cellspacing="0" cellpadding="8">
                    <thead>
                        <tr>
                            <th>Report ID</th>
                            <th>Category</th>
                            <th>Incident Date</th>
                            <th>Status</th>
                            <th>Submitted At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($user->reports as $report)
                            @php
                                $classification = (string) ($report->category->classification ?? '');
                                $isMajorOrGrave = in_array($classification, ['Major', 'Grave'], true);
                                $isDsdoOnly = (bool) (auth()->user()?->is_department_student_discipline_officer ?? false)
                                    && !(bool) (auth()->user()?->is_top_management ?? false);
                            @endphp
                            <tr>
                                <td>#{{ $report->id }}</td>
                                <td>{{ strtoupper($report->category->main_category_code ?? 'N/A') }}</td>
                                <td>{{ \Carbon\Carbon::parse($report->incident_date)->format('M d, Y') }}</td>
                                <td>
                                    <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                                        {{ ucfirst($report->status) }}
                                    </span>
                                </td>
                                <td>{{ $report->submitted_at ? $report->submitted_at->format('M d, Y') : 'N/A' }}</td>
                                <td>
                                    @if($isMajorOrGrave && $isDsdoOnly)
                                        <button type="button" class="btn-view btn-top-management-notice"
                                            data-report-id="{{ $report->id }}"
                                            data-classification="{{ $classification }}">
                                            View Report
                                        </button>
                                    @else
                                        <a href="{{ route('admin.reports.show', $report->id) }}" class="btn-view">View Report</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile report cards --}}
            <div class="mobile-reports-list">
                @foreach($user->reports as $report)
                    @php
                        $classification = (string) ($report->category->classification ?? '');
                        $isMajorOrGrave = in_array($classification, ['Major', 'Grave'], true);
                        $isDsdoOnly = (bool) (auth()->user()?->is_department_student_discipline_officer ?? false)
                            && !(bool) (auth()->user()?->is_top_management ?? false);
                    @endphp
                    <div class="mobile-report-card">
                        <div class="mrc-top">
                            <span class="mrc-id">#{{ $report->id }}</span>
                            <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                                {{ ucfirst($report->status) }}
                            </span>
                        </div>
                        <div class="mrc-fields">
                            <div class="mrc-field">
                                <span class="mrc-label">Category</span>
                                <span class="mrc-value">{{ strtoupper($report->category->main_category_code ?? 'N/A') }}</span>
                            </div>
                            <div class="mrc-field">
                                <span class="mrc-label">Incident</span>
                                <span class="mrc-value">{{ \Carbon\Carbon::parse($report->incident_date)->format('M d, Y') }}</span>
                            </div>
                            <div class="mrc-field">
                                <span class="mrc-label">Submitted</span>
                                <span class="mrc-value">{{ $report->submitted_at ? $report->submitted_at->format('M d, Y') : 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="mrc-action">
                            @if($isMajorOrGrave && $isDsdoOnly)
                                <button type="button" class="btn-view btn-top-management-notice w-full"
                                    data-report-id="{{ $report->id }}"
                                    data-classification="{{ $classification }}">
                                    View Report
                                </button>
                            @else
                                <a href="{{ route('admin.reports.show', $report->id) }}" class="btn-view w-full">View Report</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

        </section>
    @else
        <section style="margin-top: 40px;">
            <h2>📋 Reports Submitted by This User</h2>
            <p class="no-data">This user has not submitted any reports yet.</p>
        </section>
    @endif

    <!-- Suspend Modal -->
    <div id="suspendModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>⏸️ Suspend User</h3>
                <span class="close" onclick="closeModal('suspendModal')">&times;</span>
            </div>
            <form method="POST" action="{{ route('admin.users.suspend', $user->id) }}">
                @csrf
                <p>You are about to suspend: <strong>{{ $user->name }}</strong></p>
                <div class="form-group">
                    <label>Reason for Suspension *</label>
                    <textarea name="reason" rows="4" placeholder="Explain why this user is being suspended..." required></textarea>
                </div>
                <div class="form-group">
                    <label>Suspended Until *</label>
                    <input type="datetime-local" name="suspended_until" required>
                </div>
                <button type="button" onclick="closeModal('suspendModal')">Cancel</button>
                <button type="submit" class="btn-suspend">Suspend User</button>
            </form>
        </div>
    </div>

    <!-- Deactivate Modal -->
    <div id="deactivateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🗑️ Deactivate User Account</h3>
                <span class="close" onclick="closeModal('deactivateModal')">&times;</span>
            </div>
            <form method="POST" action="{{ route('admin.users.deactivate', $user->id) }}">
                @csrf
                <p>You are about to deactivate: <strong>{{ $user->name }}</strong></p>
                <div class="alert alert-warning">
                    <strong>⚠️ Warning:</strong> Deactivating this account will:
                    <ul>
                        <li>Prevent the user from logging in</li>
                        <li>Keep all their reports and data</li>
                        <li>Require manual reactivation by management</li>
                    </ul>
                </div>
                <div class="form-group">
                    <label>Deactivation Category *</label>
                    <select name="deactivation_category" required>
                        <option value="">Select category</option>
                        <option value="graduated">Graduated</option>
                        <option value="left_institution">Left Institution</option>
                        <option value="duplicate_account">Duplicate Account</option>
                        <option value="policy_violation">Policy Violation</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Reason for Deactivation</label>
                    <textarea name="reason" rows="3" placeholder="Add more context for records (optional)..."></textarea>
                </div>
                <button type="button" onclick="closeModal('deactivateModal')">Cancel</button>
                <button type="submit" class="btn-delete">Deactivate Account</button>
            </form>
        </div>
    </div>
@endsection

@push('styles')
<style>
    /* ── Visibility switches ── */
    .desktop-account-table  { display: block; }
    .mobile-account-card    { display: none;  }
    .desktop-reports-table  { display: block; }
    .mobile-reports-list    { display: none;  }

    /* ================================================================
       MOBILE ACCOUNT INFO CARD
       ================================================================ */
    .mobile-account-card {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 0.75rem;
        overflow: hidden;
    }

    .mac-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.875rem 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        flex-wrap: wrap;
    }

    .mac-name {
        font-size: 1rem;
        font-weight: 700;
        color: #fff;
    }

    .mac-header-badges {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        flex-wrap: wrap;
    }

    .mac-fields {
        display: flex;
        flex-direction: column;
    }

    .mac-field {
        display: flex;
        align-items: baseline;
        gap: 0.6rem;
        padding: 0.6rem 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }

    .mac-field:last-child {
        border-bottom: none;
    }

    .mac-field-label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: rgba(255,255,255,0.4);
        flex-shrink: 0;
        min-width: 88px;
    }

    .mac-field-value {
        font-size: 0.875rem;
        color: #fff;
        flex: 1;
        word-break: break-word;
    }

    /* ================================================================
       MOBILE REPORT CARDS
       ================================================================ */
    .mobile-reports-list {
        padding: 0.5rem 0;
    }

    .mobile-report-card {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
        padding: 0.875rem 1rem;
        margin-bottom: 0.5rem;
        background: rgba(255,255,255,0.05);
        border-radius: 0.65rem;
        border: 1px solid rgba(255,255,255,0.08);
    }

    .mrc-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }

    .mrc-id {
        font-size: 0.82rem;
        font-weight: 700;
        color: rgba(255,255,255,0.5);
    }

    .mrc-fields {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .mrc-field {
        display: flex;
        align-items: baseline;
        gap: 0.5rem;
        font-size: 0.84rem;
    }

    .mrc-label {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: rgba(255,255,255,0.4);
        flex-shrink: 0;
        min-width: 62px;
    }

    .mrc-value {
        color: #fff;
        font-weight: 500;
    }

    .mrc-action {
        margin-top: 0.25rem;
    }

    .mrc-action .w-full,
    .mrc-action a.w-full {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
    }

    /* ── Existing action button styles ── */
    .usershow-actions {
        margin-top: 0.95rem;
        display: flex;
        gap: 0.65rem;
        flex-wrap: wrap;
    }

    .usershow-action-form {
        display: inline-block;
    }

    .usershow-actions > a,
    .usershow-actions > button,
    .usershow-actions .usershow-action-form > button,
    #suspendModal form > button,
    #deactivateModal form > button {
        min-height: 42px;
        min-width: 200px;
        padding: 0.7rem 1rem;
        border-radius: 0.7rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        line-height: 1.2;
        font-weight: 600;
        box-sizing: border-box;
        text-decoration: none;
    }

    #suspendModal form,
    #deactivateModal form {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
    }

    #suspendModal form > p,
    #deactivateModal form > p,
    #deactivateModal .alert,
    #suspendModal .form-group,
    #deactivateModal .form-group {
        width: 100%;
    }

    .btn-top-management-notice { cursor: pointer; }

    /* ================================================================
       RESPONSIVE — mobile breakpoint only
       ================================================================ */
    @media (max-width: 768px) {
        .desktop-account-table  { display: none;  }
        .mobile-account-card    { display: block; }
        .desktop-reports-table  { display: none;  }
        .mobile-reports-list    { display: block; }

        .usershow-actions {
            gap: 0.5rem;
        }

        .usershow-actions > a,
        .usershow-actions > button,
        .usershow-actions .usershow-action-form > button,
        #suspendModal form > button,
        #deactivateModal form > button {
            width: 100%;
            min-width: 0;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function openSuspendModal() {
        document.getElementById('suspendModal').style.display = 'block';
    }

    function openDeactivateModal() {
        document.getElementById('deactivateModal').style.display = 'block';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    document.querySelectorAll('.btn-top-management-notice').forEach(function (button) {
        button.addEventListener('click', function () {
            var reportId = this.getAttribute('data-report-id');
            var classification = this.getAttribute('data-classification');
            alert('Report #' + reportId + ' is classified as ' + classification + ' and is handled by Top Management. Please coordinate with Top Management for updates.');
        });
    });

    window.onclick = function (event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
</script>
@endpush