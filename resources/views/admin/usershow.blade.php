@extends('layouts.admin')

@section('title', 'User Details - Sincidentre Department Student Discipline Officer')

@section('page-title', '👤 User Details')

@section('header-search')
    <div style="display: flex; gap: 10px;">
        <a href="{{ route('admin.users') }}" class="btn-back">← Back to Users</a>
        @if(!$user->is_department_student_discipline_officer)
            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn-edit">✏️ Edit User</a>
        @endif
    </div>
@endsection

@section('content')
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
        <h2>Account Information</h2>
        <table border="1" cellspacing="0" cellpadding="8" width="100%">
            <tr>
                <th>User ID</th>
                <td>#{{ $user->id }}</td>
            </tr>
            <tr>
                <th>Full Name</th>
                <td>{{ $user->first_name }} {{ $user->last_name }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td>{{ $user->email }}</td>
            </tr>
            <tr>
                <th>Department</th>
                <td>{{ $user->department->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Phone</th>
                <td>{{ $user->phone ?? 'N/A' }}</td>
            </tr>
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
            <tr>
                <th>Date Registered</th>
                <td>{{ $user->created_at->format('F d, Y h:i A') }}</td>
            </tr>
            <tr>
                <th>Last Updated</th>
                <td>{{ $user->updated_at->format('F d, Y h:i A') }}</td>
            </tr>
            <tr>
                <th>Total Reports Submitted</th>
                <td><strong>{{ $user->reports->count() }}</strong> {{ Str::plural('report', $user->reports->count()) }}</td>
            </tr>
        </table>

        <!-- Suspension/Deactivation Info -->
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
            <div style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="{{ route('admin.users.edit', $user->id) }}" class="btn-edit">
                    ✏️ Edit User
                </a>

                @if($user->status === 'active')
                    <button onclick="openSuspendModal()" class="btn-suspend">
                        🚫 Suspend User
                    </button>
                    <button onclick="openDeactivateModal()" class="btn-delete">
                        🗑️ Deactivate Account
                    </button>
                @elseif($user->status === 'suspended')
                    <form method="POST" action="{{ route('admin.users.activate', $user->id) }}" style="display:inline-block;">
                        @csrf
                        <button type="submit" class="btn-activate" onclick="return confirm('Reactivate this user?')">
                            ✅ Reactivate User
                        </button>
                    </form>
                    <button onclick="openDeactivateModal()" class="btn-delete">
                        🗑️ Deactivate Account
                    </button>
                @elseif($user->status === 'deactivated')
                    <form method="POST" action="{{ route('admin.users.activate', $user->id) }}" style="display:inline-block;">
                        @csrf
                        <button type="submit" class="btn-activate" onclick="return confirm('Reactivate this user?')">
                            ✅ Reactivate Account
                        </button>
                    </form>
                @endif
            </div>
        @else
            <div class="alert alert-info" style="margin-top: 20px;">
                <strong>Note:</strong> This is a Department Student Discipline Officer account and cannot be suspended or deactivated from this interface.
            </div>
        @endif
    </section>

    <!-- Activity Summary -->
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

    <!-- User's Reports Section -->
    @if($user->reports->count() > 0)
        <section style="margin-top: 40px;">
            <h2>📋 Reports Submitted by This User</h2>
            <div class="table-wrapper">
                <table border="1" cellspacing="0" cellpadding="8">
                    <thead>
                        <tr>
                            <th>Report ID</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Incident Date</th>
                            <th>Status</th>
                            <th>Submitted At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($user->reports as $report)
                            <tr>
                                <td>#{{ $report->id }}</td>
                                <td>{{ $report->title }}</td>
                                <td>{{ $report->category->name ?? 'N/A' }}</td>
                                <td>{{ \Carbon\Carbon::parse($report->incident_date)->format('M d, Y') }}</td>
                                <td>
                                    <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                                        {{ ucfirst($report->status) }}
                                    </span>
                                </td>
                                <td>{{ $report->submitted_at ? $report->submitted_at->format('M d, Y') : 'N/A' }}</td>
                                <td>
                                    <a href="{{ route('admin.reports.show', $report->id) }}" class="btn-view">View Report</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
                        <li>Allow reactivation later if needed</li>
                    </ul>
                </div>
                <div class="form-group">
                    <label>Reason for Deactivation (Optional)</label>
                    <textarea name="reason" rows="3" placeholder="Optional reason..."></textarea>
                </div>
                <button type="button" onclick="closeModal('deactivateModal')">Cancel</button>
                <button type="submit" class="btn-delete">Deactivate Account</button>
            </form>
        </div>
    </div>
@endsection

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

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
</script>
@endpush


