@extends('layouts.admin')

@section('title', 'Handle Reports - Sincidentre Department Student Discipline Officer')

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
          <div class="report-flow-card">
                <h2>Report Flow</h2>
                <div class="report-flow-track">
                     <a href="{{ route('admin.reports') }}" class="flow-chip">Pending</a>
                     <span class="flow-separator">&gt;</span>
                     <a href="{{ route('admin.handlereports', ['status' => 'approved']) }}" class="flow-chip {{ ($selectedStatus ?? '') === 'approved' ? 'is-active' : '' }}">Approved</a>
                     <span class="flow-separator">/</span>
                     <a href="{{ route('admin.handlereports', ['status' => 'rejected']) }}" class="flow-chip {{ ($selectedStatus ?? '') === 'rejected' ? 'is-active' : '' }}">Rejected</a>
                     <span class="flow-separator">&gt;</span>
                     <a href="{{ route('admin.handlereports', ['status' => 'under review']) }}" class="flow-chip {{ ($selectedStatus ?? '') === 'under review' ? 'is-active' : '' }}">Under Review</a>
                     <span class="flow-separator">&gt;</span>
                     <a href="{{ route('admin.handlereports', ['status' => 'resolved']) }}" class="flow-chip {{ ($selectedStatus ?? '') === 'resolved' ? 'is-active' : '' }}">Resolved</a>
                     <a href="{{ route('admin.handlereports') }}" class="flow-clear">Show All</a>
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
                                No reports found for this flow stage.
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
    .report-flow-card {
        margin-bottom: 20px;
        padding: 14px;
        border: 1px solid rgba(255, 255, 255, 0.24);
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.08);
    }

    .report-flow-track {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .flow-chip {
        padding: 8px 12px;
        border: 1px solid rgba(255, 255, 255, 0.35);
        border-radius: 999px;
        text-decoration: none;
        color: #fff;
        background: rgba(255, 255, 255, 0.08);
        transition: all 0.15s ease-in-out;
    }

    .flow-chip:hover {
        background: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.7);
    }

    .flow-chip.is-active {
        font-weight: 700;
        border-color: #ffffff;
        background: rgba(255, 255, 255, 0.26);
    }

    .flow-separator {
        color: rgba(255, 255, 255, 0.7);
        font-weight: 700;
    }

    .flow-clear {
        margin-left: 8px;
        text-decoration: underline;
        color: #fff;
        font-weight: 600;
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
        .report-flow-track {
            gap: 8px;
        }

        .flow-chip {
            font-size: 13px;
            padding: 7px 10px;
        }

        .flow-clear {
            margin-left: 0;
        }
    }
</style>
@endpush
