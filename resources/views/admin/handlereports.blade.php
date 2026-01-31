@extends('layouts.admin')

@section('title', 'Handle Reports - Sincidentre Admin')

@section('page-title', 'Handle Reports')

@section('header-search')
    <input type="search" placeholder="Search reports...">
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

    <!-- Approved Reports Table -->
    <section id="handle-reports">
        <h2>Approved Reports List</h2>
        <p>These reports have been approved and are ready to be handled.</p>

        <table border="1" cellspacing="0" cellpadding="8">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Reporter</th>
                    <th>Category</th>
                    <th>Date Submitted</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($approvedReports as $report)
    <tr>
        <td>{{ $report->id }}</td>
        <td>{{ $report->title }}</td>
        <td>{{ $report->user->name ?? 'Unknown' }}</td>  <!-- ✅ This should work now -->
        <td>{{ $report->category->name ?? 'N/A' }}</td>
        <td>{{ $report->created_at->format('M d, Y') }}</td>
        <td>
            <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                {{ ucfirst($report->status) }}
            </span>
        </td>
        <td>{{ $report->assigned_to ?? 'Unassigned' }}</td>
        <td>
            <a href="{{ route('admin.handlereports.show', $report->id) }}" class="btn-handle">Handle</a>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" style="text-align:center;">No approved reports yet.</td>
    </tr>
@endforelse
            </tbody>
        </table>
    </section>
@endsection