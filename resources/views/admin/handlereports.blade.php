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

     <!-- Report Flow + Handle Reports Table -->
    <section id="handle-reports"> 
          <div style="margin-bottom: 20px;">
                <h2>Report Flow</h2>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                     <a href="{{ route('admin.reports') }}"
                         style="padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; text-decoration: none;">Pending</a>
                     <span>&gt;</span>
                     <a href="{{ route('admin.handlereports', ['status' => 'approved']) }}"
                         style="padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; text-decoration: none; {{ ($selectedStatus ?? '') === 'approved' ? 'font-weight: 700; border-color: #333;' : '' }}">Approved</a>
                     <span>/</span>
                     <a href="{{ route('admin.handlereports', ['status' => 'rejected']) }}"
                         style="padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; text-decoration: none; {{ ($selectedStatus ?? '') === 'rejected' ? 'font-weight: 700; border-color: #333;' : '' }}">Rejected</a>
                     <span>&gt;</span>
                     <a href="{{ route('admin.handlereports', ['status' => 'under review']) }}"
                         style="padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; text-decoration: none; {{ ($selectedStatus ?? '') === 'under review' ? 'font-weight: 700; border-color: #333;' : '' }}">Under Review</a>
                     <span>&gt;</span>
                     <a href="{{ route('admin.handlereports', ['status' => 'resolved']) }}"
                         style="padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; text-decoration: none; {{ ($selectedStatus ?? '') === 'resolved' ? 'font-weight: 700; border-color: #333;' : '' }}">Resolved</a>
                     <a href="{{ route('admin.handlereports') }}" style="margin-left: 8px; text-decoration: none;">Show All</a>
                </div>
          </div>

        <!-- Filter Form -->
        <div class="filter-container" style="margin-bottom: 20px;">
            <form method="GET" action="{{ route('admin.handlereports') }}" style="display: flex; gap: 10px; align-items: center;">
                @if(($selectedStatus ?? '') !== '')
                    <input type="hidden" name="status" value="{{ $selectedStatus }}">
                @endif
                <label for="category_filter">Filter by Category:</label>
                <select name="category" id="category_filter" style="padding: 8px; min-width: 200px;">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn-filter" style="padding: 8px 16px;">Apply Filter</button>
                @if(request('category'))
                    <a href="{{ route('admin.handlereports', array_filter(['status' => $selectedStatus ?? ''])) }}" class="btn-clear" style="padding: 8px 16px; text-decoration: none;">Clear Filter</a>
                @endif
            </form>
        </div>

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
                        <td>{{ $report->user->name ?? 'Unknown' }}</td>
                        <td>{{ $report->category->name ?? 'N/A' }}</td>
                        <td>{{ $report->created_at->format('M d, Y') }}</td>
                        <td>
                            <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                                {{ ucfirst($report->status) }}
                            </span>
                        </td>
                        <td>{{ $report->assigned_to ?? 'Unassigned' }}</td>
                        <td>
                            <form action="{{ route('admin.handlereports.show', $report->id) }}" method="GET" style="display: inline;">
                                <button type="submit" class="btn-handle">Handle</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;">
                            @if(($selectedStatus ?? '') !== '')
                                No {{ ucwords(str_replace('_', ' ', $selectedStatus)) }} reports found.
                            @else
                                No reports found for this flow stage.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection