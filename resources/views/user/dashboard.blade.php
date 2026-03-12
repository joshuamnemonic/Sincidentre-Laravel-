@extends('layouts.app')

@section('title', 'User Dashboard - Sincidentre')

@section('content')
    <div class="welcome">
        Welcome, {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}!
    </div>

    <div class="cards">
        <div class="card animate">
            <h3>Total Reports</h3>
            <p id="total-reports">{{ $totalReports }}</p>
        </div>
        <div class="card animate">
            <h3>Pending</h3>
            <p id="pending-reports">{{ $pendingReports }}</p>
        </div>
        <div class="card animate">
            <h3>Approved</h3>
            <p id="approved-reports">{{ $approvedReports }}</p>
        </div>
        <div class="card animate">
            <h3>Rejected</h3>
            <p id="rejected-reports">{{ $rejectedReports }}</p>
        </div>
        <div class="card animate">
            <h3>Under Review</h3>
            <p id="under-review-reports">{{ $underReviewReports }}</p>
        </div>
        <div class="card animate">
            <h3>Resolved</h3>
            <p id="resolved-reports">{{ $resolvedReports }}</p>
        </div>
    </div>

    <div class="recent-reports animate">
        <h3>Recent Reports</h3>

        <form method="GET" action="{{ route('dashboard') }}">
            <input type="text" name="search" id="search-input" placeholder="🔍 Search reports..."
                   value="{{ request('search') }}" />
            <button type="submit">Search</button>
        </form>
        
        <table id="reports-table">
            <thead>
                <tr>
                    <th>Report ID</th>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentReports as $report)
                    <tr>
                        <td>{{ $report->id }}</td>
                        <td>{{ $report->title }}</td>
                        <td>{{ $report->submitted_at->format('F d, Y') }}</td>
                        <td>
                            <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                                {{ $report->status }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection