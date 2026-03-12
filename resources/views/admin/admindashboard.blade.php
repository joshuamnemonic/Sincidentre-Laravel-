@extends('layouts.admin')

@section('title', 'Sincidentre Admin Dashboard')

@section('page-title')
    <div>
        <span style="font-size: 1.2em; font-weight: bold;">
            Welcome, {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}
        </span>
        @if(Auth::user()->department)
            <br>
            <span style="font-size: 0.85em; color: #666; font-weight: normal;">
                {{ Auth::user()->department->name }}
            </span>
        @endif
    </div>
@endsection

@section('content')
    <!-- Stats Cards -->
    <section class="stats">
        <a href="{{ route('admin.users') }}" class="card card-link">
            <h3>Total Users</h3>
            <p>{{ $totalUsers }}</p>
        </a>
        <a href="{{ route('admin.handlereports', ['status' => 'Pending']) }}" class="card card-link">
            <h3>Pending</h3>
            <p>{{ $pendingReports }}</p>
        </a>
        <a href="{{ route('admin.handlereports', ['status' => 'Approved']) }}" class="card card-link">
            <h3>Approved</h3>
            <p>{{ $approvedReports }}</p>
        </a>
        <a href="{{ route('admin.handlereports', ['status' => 'Rejected']) }}" class="card card-link">
            <h3>Rejected</h3>
            <p>{{ $rejectedReports }}</p>
        </a>
        <a href="{{ route('admin.handlereports', ['status' => 'Under Review']) }}" class="card card-link">
            <h3>Under Review</h3>
            <p>{{ $underReview }}</p>
        </a>
        <a href="{{ route('admin.handlereports', ['status' => 'Resolved']) }}" class="card card-link">
            <h3>Resolved</h3>
            <p>{{ $resolvedReports }}</p>
        </a>
    </section>

    <!-- Reports Table -->
    <section id="reports">
        <h2>Recent Reports</h2>
        <table border="1" cellspacing="0" cellpadding="8">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Reporter</th>
                    <th>Category</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentReports as $report)
                    <tr>
                        <td>{{ $report->id }}</td>
                        <td>{{ $report->title }}</td>
                        <td>{{ $report->user->name }}</td>
                        <td>{{ $report->category->name ?? 'N/A' }}</td>
                        <td>{{ \Carbon\Carbon::parse($report->incident_date)->format('M d, Y') }}</td>
                        <td>
                            <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                                {{ ucfirst($report->status) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.reports.show', $report->id) }}" class="btn-view">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center;">No reports found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection