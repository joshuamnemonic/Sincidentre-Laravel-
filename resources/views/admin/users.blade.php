@extends('layouts.admin')

@section('title', 'User Management - Sincidentre Department Student Discipline Officer')

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
        @if(($canFilterDepartment ?? false) === true)
            <select name="department" onchange="this.form.submit()">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>
        @endif
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
    <div class="stats-grid users-stats-grid">
        <div class="stat-card">
            <h4>{{ $totalUsersTitle ?? 'Total Users' }}</h4>
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
                        <th>Type</th>
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
                            <td>
                                @php
                                    $registrantType = strtolower((string) ($user->registrant_type ?? ''));
                                    $userTypeLabel = match ($registrantType) {
                                        'student' => 'Student',
                                        'faculty' => 'Faculty',
                                        'employee_staff' => 'Employee/Staff',
                                        default => 'Unspecified',
                                    };
                                @endphp
                                {{ $userTypeLabel }}
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->department->name ?? 'N/A' }}</td>
                            <td>{{ $user->reports_count }} {{ Str::plural('report', $user->reports_count) }}</td>
                            <td>{{ $user->created_at->format('M d, Y') }}</td>
                            <td>
                                @if($user->status === 'active')
                                    <span class="status active">Active</span>
                                @elseif($user->status === 'suspended')
                                    <span class="status suspended">Suspended</span>
                                    @if($user->suspended_until)
                                        <div style="margin-top: 0.3rem; font-size: 0.78rem; color: rgba(255, 255, 255, 0.82);">
                                            Until {{ $user->suspended_until->format('M d, Y h:i A') }}
                                        </div>
                                    @endif
                                @else
                                    <span class="status deactivated">Deactivated</span>
                                    @if($user->deactivation_category)
                                        @php
                                            $deactivationCategoryLabel = match ($user->deactivation_category) {
                                                'graduated' => 'Graduated',
                                                'left_institution' => 'Left Institution',
                                                'duplicate_account' => 'Duplicate Account',
                                                'policy_violation' => 'Policy Violation',
                                                default => 'Other',
                                            };
                                        @endphp
                                        <div style="margin-top: 0.3rem; font-size: 0.78rem; color: rgba(255, 255, 255, 0.82);">
                                            {{ $deactivationCategoryLabel }}
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td>
                                <div class="action-buttons" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                                    <a href="{{ route('admin.users.show', $user->id) }}" class="btn-view">View</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align:center;">No users found.</td>
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

@endsection

@push('styles')
<style>
    .users-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.85rem;
        margin-bottom: 1.1rem;
    }

    .users-stats-grid .stat-card {
        padding: 0.9rem 0.75rem;
        border-radius: 0.9rem;
    }

    .users-stats-grid .stat-card h4 {
        font-size: 0.84rem;
        letter-spacing: 0.5px;
        margin-bottom: 0.4rem;
    }

    .users-stats-grid .stat-card .stat-number,
    .users-stats-grid .stat-card p {
        font-size: 2.15rem;
        line-height: 1;
    }

    @media (max-width: 768px) {
        .users-stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.6rem;
        }

        .users-stats-grid .stat-card {
            padding: 0.75rem 0.55rem;
        }

        .users-stats-grid .stat-card h4 {
            font-size: 0.7rem;
            letter-spacing: 0.3px;
            margin-bottom: 0.3rem;
        }

        .users-stats-grid .stat-card .stat-number,
        .users-stats-grid .stat-card p {
            font-size: 1.65rem;
        }
    }

    @media (max-width: 480px) {
        .users-stats-grid .stat-card {
            padding: 0.65rem 0.45rem;
        }

        .users-stats-grid .stat-card h4 {
            font-size: 0.62rem;
            letter-spacing: 0.2px;
        }

        .users-stats-grid .stat-card .stat-number,
        .users-stats-grid .stat-card p {
            font-size: 1.4rem;
        }
    }
</style>
@endpush



