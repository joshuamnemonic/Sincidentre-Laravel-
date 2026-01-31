@extends('layouts.admin')

@section('title', 'User Management - Sincidentre Admin')

@section('page-title', 'User Management')

@section('header-search')
    <form method="GET" action="{{ route('admin.users') }}" style="display: inline-flex; gap: 10px;">
        <input type="search" name="search" placeholder="Search users..." value="{{ request('search') }}">
        <select name="status" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
            <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
            <option value="deactivated" {{ request('status') == 'deactivated' ? 'selected' : '' }}>Deactivated</option>
        </select>
        <select name="department" onchange="this.form.submit()">
            <option value="">All Departments</option>
            @foreach($departments as $dept)
                <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>
                    {{ $dept->name }}
                </option>
            @endforeach
        </select>
    </form>
@endsection

@section('content')
    <p>Manage all registered users of Sincidentre.</p>

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

    <!-- Statistics Cards -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
        <div class="stat-card">
            <h4>Total Users</h4>
            <p class="stat-number">{{ $totalUsers }}</p>
        </div>
        <div class="stat-card">
            <h4>Active Users</h4>
            <p class="stat-number" style="color: #28a745;">{{ $activeUsers }}</p>
        </div>
        <div class="stat-card">
            <h4>Suspended Users</h4>
            <p class="stat-number" style="color: #ffc107;">{{ $suspendedUsers }}</p>
        </div>
        <div class="stat-card">
            <h4>Deactivated Users</h4>
            <p class="stat-number" style="color: #dc3545;">{{ $deactivatedUsers }}</p>
        </div>
    </div>

    <!-- Users Table -->
    <section>
        <h2>Registered Users</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Reports</th>
                        <th>Date Registered</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->department->name ?? 'N/A' }}</td>
                            <td>{{ $user->reports_count }} {{ Str::plural('report', $user->reports_count) }}</td>
                            <td>{{ $user->created_at->format('M d, Y') }}</td>
                            <td>
                                @if($user->status === 'active')
                                    <span class="status active">Active</span>
                                @elseif($user->status === 'suspended')
                                    <span class="status suspended">Suspended</span>
                                @else
                                    <span class="status deactivated">Deactivated</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="{{ route('admin.users.show', $user->id) }}" class="btn-view">View</a>
                                    
                                    @if(!$user->is_admin)
                                        <a href="{{ route('admin.users.edit', $user->id) }}" class="btn-edit">Edit</a>
                                        
                                        @if($user->status === 'active')
                                            <button onclick="openSuspendModal({{ $user->id }}, '{{ $user->name }}')" class="btn-suspend">
                                                Suspend
                                            </button>
                                        @elseif($user->status === 'suspended')
                                            <form action="{{ route('admin.users.activate', $user->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn-activate" onclick="return confirm('Reactivate this user?')">
                                                    Activate
                                                </button>
                                            </form>
                                        @elseif($user->status === 'deactivated')
                                            <form action="{{ route('admin.users.activate', $user->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn-activate" onclick="return confirm('Reactivate this user?')">
                                                    Reactivate
                                                </button>
                                            </form>
                                        @endif
                                        
                                        @if($user->status !== 'deactivated')
                                            <button onclick="openDeactivateModal({{ $user->id }}, '{{ $user->name }}')" class="btn-delete">
                                                Deactivate
                                            </button>
                                        @endif
                                    @else
                                        <span style="color: #999; font-size: 0.9em;">Admin Account</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div style="margin-top: 20px;">
            {{ $users->appends(request()->query())->links() }}
        </div>
    </section>

    <!-- Suspend Modal -->
    <div id="suspendModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>⏸️ Suspend User</h3>
                <span class="close" onclick="closeModal('suspendModal')">&times;</span>
            </div>
            <form id="suspendForm" method="POST">
                @csrf
                <p>You are about to suspend: <strong id="suspendUserName"></strong></p>
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
            <form id="deactivateForm" method="POST">
                @csrf
                <p>You are about to deactivate: <strong id="deactivateUserName"></strong></p>
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
    function openSuspendModal(userId, userName) {
        document.getElementById('suspendModal').style.display = 'block';
        document.getElementById('suspendUserName').textContent = userName;
        document.getElementById('suspendForm').action = `/admin/users/${userId}/suspend`;
    }

    function openDeactivateModal(userId, userName) {
        document.getElementById('deactivateModal').style.display = 'block';
        document.getElementById('deactivateUserName').textContent = userName;
        document.getElementById('deactivateForm').action = `/admin/users/${userId}/deactivate`;
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