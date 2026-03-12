@extends('layouts.app')

@section('title', 'My Reports - Sincidentre')

@section('content')
    <div class="welcome">
        <h1>My Reports</h1>
    </div>

    <!-- Reports Table -->
    <div class="recent-reports animate">
        <form method="GET" action="{{ route('myreports') }}" class="filter-bar">
            <input type="text" name="search" placeholder="🔍 Search reports..."
                   value="{{ $search ?? '' }}" />
            <select name="status">
                <option value="">All Statuses</option>
                @foreach(['Pending', 'Approved', 'Rejected', 'Under Review', 'Resolved'] as $s)
                    <option value="{{ $s }}" {{ (isset($status) && strtolower($status) === strtolower($s)) ? 'selected' : '' }}>
                        {{ $s }}
                    </option>
                @endforeach
            </select>
            <button type="submit">Filter</button>
            @if($search || $status)
                <a href="{{ route('myreports') }}" class="btn-secondary">Clear</a>
            @endif
        </form>

        <table class="report-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Date of Incident</th>
                    <th>Time of Incident</th>
                    <th>Location</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($myReports as $report)
                <tr onclick="window.location='{{ route('report.show', $report->id) }}'" style="cursor: pointer;">
                    <td>{{ $report->id }}</td>
                    <td>{{ $report->title }}</td>
                    <td>{{ $report->category->name ?? 'N/A' }}</td> <!-- ✅ FIXED -->
                    <td>{{ \Carbon\Carbon::parse($report->incident_date)->format('M d, Y') }}</td> <!-- ✅ Better formatting -->
                    <td>{{ \Carbon\Carbon::parse($report->incident_time)->format('h:i A') }}</td> <!-- ✅ Better formatting -->
                    <td>{{ $report->location }}</td>
                    <td>
                        <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                            {{ $report->status }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center;">No reports found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection