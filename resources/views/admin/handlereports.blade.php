@extends('layouts.admin')

@section('title', 'Handle Reports - Sincidentre Department Student Discipline Officer')

@section('page-title', 'Handle Reports')

@section('header-search')
    <form method="GET" action="{{ route('admin.handlereports') }}" class="handle-header-search">
        @if(request()->filled('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif
        @if(request()->filled('category'))
            <input type="hidden" name="category" value="{{ request('category') }}">
        @endif
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search reports..." aria-label="Search reports">
    </form>
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

     <!-- Filters + Handle Reports Table -->
    <section id="handle-reports"> 
        <!-- Filter Form -->
        <div class="filter-container handle-filter-wrap">
            <form method="GET" action="{{ route('admin.handlereports') }}" class="handle-filter-form">
                @if(request()->filled('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif

                <div class="handle-filter-field">
                    <label for="status_filter">Filter by Status:</label>
                    <select name="status" id="status_filter">
                    <option value="">All Statuses</option>
                    <option value="approved" {{ ($selectedStatus ?? '') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ ($selectedStatus ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="under review" {{ ($selectedStatus ?? '') === 'under review' ? 'selected' : '' }}>Under Review</option>
                    <option value="resolved" {{ ($selectedStatus ?? '') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                </select>
                </div>

                <div class="handle-filter-field">
                    <label for="category_filter">Filter by Category:</label>
                    <select name="category" id="category_filter">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                </div>

                <div class="handle-filter-actions">
                    <button type="submit" class="btn-filter">Apply Filter</button>
                @if(request()->filled('category') || ($selectedStatus ?? '') !== '' || request()->filled('search'))
                    <a href="{{ route('admin.handlereports') }}" class="btn-clear">Clear Filter</a>
                @endif
                </div>
            </form>
        </div>

        <table border="1" cellspacing="0" cellpadding="8">
            <thead>
                <tr>
                    <th>ID</th>
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
                        <td>{{ $report->user->name ?? 'Unknown' }}</td>
                        <td>{{ strtoupper($report->category->main_category_code ?? 'N/A') }}</td>
                        <td>{{ $report->created_at->format('M d, Y') }}</td>
                        <td>
                            <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                                {{ \App\Models\Report::labelForStatus($report->status) }}
                            </span>
                        </td>
                        <td>{{ $report->assigned_to ?? 'Unassigned' }}</td>
                        <td>
                            @if(strtolower((string) $report->status) === strtolower(\App\Models\Report::STATUS_RESOLVED))
                                <a href="{{ route('admin.reports.show', $report->id) }}" class="btn-view">View</a>
                            @else
                                <form action="{{ route('admin.handlereports.show', $report->id) }}" method="GET" style="display: inline;">
                                    <button type="submit" class="btn-handle">Handle</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;">
                            @if(($selectedStatus ?? '') !== '')
                                No {{ ucwords(str_replace('_', ' ', $selectedStatus)) }} reports found.
                            @else
                                No reports found.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if(!(bool) (Auth::user()->is_top_management ?? false))
            <div class="escalated-view-only-section">
                <h2>Escalated Reports (View Only)</h2>
                <p class="escalated-subtitle">These reports are escalated and can only be viewed here. Handling actions are disabled in this section.</p>

                <table border="1" cellspacing="0" cellpadding="8">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Reporter</th>
                            <th>Category</th>
                            <th>Date Submitted</th>
                            <th>Status</th>
                            <th>Escalated At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($escalatedReports as $report)
                            <tr>
                                <td>{{ $report->id }}</td>
                                <td>{{ $report->user->name ?? 'Unknown' }}</td>
                                <td>{{ strtoupper($report->category->main_category_code ?? 'N/A') }}</td>
                                <td>{{ $report->created_at->format('M d, Y') }}</td>
                                <td>
                                    <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                                        {{ \App\Models\Report::labelForStatus($report->status) }}
                                    </span>
                                </td>
                                <td>{{ $report->escalated_at ? $report->escalated_at->format('M d, Y h:i A') : 'N/A' }}</td>
                                <td>
                                    <a href="{{ route('admin.reports.show', $report->id) }}" class="btn-view">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align:center;">No escalated reports found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection

@push('styles')
<style>
    .handle-header-search {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .handle-filter-wrap {
        margin-bottom: 20px;
    }

    .handle-filter-form {
        display: flex;
        gap: 12px;
        align-items: end;
        flex-wrap: wrap;
    }

    .handle-filter-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 210px;
        flex: 1 1 240px;
    }

    .handle-filter-field label {
        font-weight: 600;
        color: rgba(255, 255, 255, 0.9);
    }

    .handle-filter-field select {
        width: 100%;
        min-height: 44px;
        padding: 0.7rem 0.9rem;
        background: #ffffff;
        color: #1f2937;
        border: 2px solid var(--glass-border);
        border-radius: 0.6rem;
    }

    .handle-filter-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .handle-filter-actions .btn-filter,
    .handle-filter-actions .btn-clear {
        min-height: 44px;
        padding: 0.7rem 1rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .escalated-view-only-section {
        margin-top: 28px;
    }

    .escalated-subtitle {
        margin-top: -8px;
        margin-bottom: 12px;
        color: rgba(255, 255, 255, 0.82);
    }

    @media (max-width: 768px) {
        .handle-header-search {
            width: 100%;
        }

        .handle-header-search input {
            width: 100%;
            min-width: 0;
        }

        .handle-filter-form {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }

        .handle-filter-field {
            min-width: 0;
            flex: 1 1 auto;
        }

        .handle-filter-actions {
            width: 100%;
            flex-direction: column;
            align-items: stretch;
        }

        .handle-filter-actions .btn-filter,
        .handle-filter-actions .btn-clear {
            width: 100%;
        }
    }
</style>
@endpush
