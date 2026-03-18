@extends('layouts.admin')

@section('title', 'Sincidentre Management Dashboard')

@section('page-title')
    <div>
        <span style="font-size: 1.2em; font-weight: bold;">
            Welcome, {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}!
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
    <section class="stats admin-stats">
        <a href="{{ route('admin.users') }}" class="card card-link status-total">
            <h3>Total Users</h3>
            <p>{{ $totalUsers }}</p>
        </a>
        <a href="{{ route('admin.reports') }}" class="card card-link status-pending">
            <h3>Pending</h3>
            <p>{{ $pendingReports }}</p>
        </a>
        <a href="{{ route('admin.handlereports', ['status' => 'Approved']) }}" class="card card-link status-approved">
            <h3>Approved</h3>
            <p>{{ $approvedReports }}</p>
        </a>
        <a href="{{ route('admin.handlereports', ['status' => 'Rejected']) }}" class="card card-link status-rejected">
            <h3>Rejected</h3>
            <p>{{ $rejectedReports }}</p>
        </a>
        <a href="{{ route('admin.handlereports', ['status' => 'Under Review']) }}" class="card card-link status-under-review">
            <h3>Under Review</h3>
            <p>{{ $underReview }}</p>
        </a>
        <a href="{{ route('admin.handlereports', ['status' => 'Resolved']) }}" class="card card-link status-resolved">
            <h3>Resolved</h3>
            <p>{{ $resolvedReports }}</p>
        </a>
    </section>

    @php
        $priorityStatuses = [
            \App\Models\Report::STATUS_PENDING,
            \App\Models\Report::STATUS_UNDER_REVIEW,
            \App\Models\Report::STATUS_REJECTED,
        ];

        $priorityReports = $recentReports
            ->filter(function ($report) use ($priorityStatuses) {
                return in_array(\App\Models\Report::normalizeStatus($report->status), $priorityStatuses, true);
            })
            ->take(5);
    @endphp

    <section id="dashboard-focus" class="admin-focus">
        <h2>Dashboard Focus</h2>

        <div class="focus-grid">
            <article class="focus-card">
                <h3>Priority Reports</h3>
                <ul class="focus-list">
                    @forelse($priorityReports as $report)
                        <li class="focus-item">
                            <div>
                                <strong>Report {{ $report->id }}</strong>
                                <span class="focus-meta">{{ $report->user->name ?? 'Unknown' }} · {{ optional($report->created_at)->format('M d, Y') }}</span>
                            </div>
                        </li>
                    @empty
                        <li class="focus-empty">No priority reports right now.</li>
                    @endforelse
                </ul>
            </article>

            <article class="focus-card">
                <h3>Recent Updates</h3>
                <ul class="focus-list">
                    @forelse($recentReports->take(5) as $report)
                        <li class="focus-item">
                            <div>
                                <strong>Report {{ $report->id }}</strong>
                                <span class="focus-meta">{{ \App\Models\Report::labelForStatus($report->status) }} · {{ optional($report->updated_at)->format('M d, Y') }}</span>
                            </div>
                        </li>
                    @empty
                        <li class="focus-empty">No recent updates yet.</li>
                    @endforelse
                </ul>
            </article>
        </div>

        <div class="focus-actions">
            <a href="{{ route('admin.handlereports') }}" class="btn-primary">Open Handle Reports</a>
            <a href="{{ route('admin.reports') }}" class="btn-secondary">Open New Reports</a>
        </div>
    </section>
@endsection

@push('styles')
<style>
    .admin-stats {
        gap: 0.85rem;
        margin-bottom: 1.25rem;
    }

    .admin-stats .card {
        padding: 1rem 0.75rem;
        border-radius: 1.1rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }

    .admin-stats .card h3 {
        margin: 0 0 0.5rem;
        font-size: 0.76rem;
        letter-spacing: 0.8px;
        white-space: nowrap;
    }

    .admin-stats .card p {
        font-size: 2.05rem;
        line-height: 1;
    }

    .admin-stats .card-link.status-total {
        color: #ffffff;
        border-color: rgba(255, 255, 255, 0.3);
        background: rgba(255, 255, 255, 0.15);
    }

    .admin-stats .card-link.status-pending {
        color: var(--status-pending-color);
        border-color: var(--status-pending-border);
        background: var(--status-pending-bg);
    }

    .admin-stats .card-link.status-approved {
        color: var(--status-approved-color);
        border-color: var(--status-approved-border);
        background: var(--status-approved-bg);
    }

    .admin-stats .card-link.status-rejected {
        color: var(--status-rejected-color);
        border-color: var(--status-rejected-border);
        background: var(--status-rejected-bg);
    }

    .admin-stats .card-link.status-under-review {
        color: var(--status-review-color);
        border-color: var(--status-review-border);
        background: var(--status-review-bg);
    }

    .admin-stats .card-link.status-resolved {
        color: var(--status-resolved-color);
        border-color: var(--status-resolved-border);
        background: var(--status-resolved-bg);
    }

    .admin-focus {
        margin-top: 0.25rem;
    }

    .focus-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        padding: 0 1rem 1rem;
    }

    .focus-card {
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 0.8rem;
        background: rgba(255, 255, 255, 0.06);
        padding: 0.9rem;
    }

    .focus-card h3 {
        margin: 0 0 0.65rem;
        padding: 0;
        font-size: 1rem;
        color: #ffffff;
    }

    .focus-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
    }

    .focus-item {
        display: flex;
        align-items: flex-start;
        padding: 0.55rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .focus-item:last-child {
        border-bottom: none;
    }

    .focus-meta {
        display: block;
        margin-top: 0.2rem;
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.78);
    }

    .focus-empty {
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.9rem;
        padding: 0.5rem 0;
    }

    .focus-actions {
        display: flex;
        gap: 0.75rem;
        padding: 0 1rem 1rem;
        flex-wrap: wrap;
    }

    .focus-actions .btn-primary,
    .focus-actions .btn-secondary {
        text-decoration: none;
        min-height: 42px;
        padding: 0.7rem 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 1 1 220px;
    }

    @media (max-width: 768px) {
        .admin-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.6rem;
        }

        .admin-stats .card {
            padding: 0.75rem 0.55rem;
            border-radius: 0.9rem;
        }

        .admin-stats .card h3 {
            font-size: 0.68rem;
            margin-bottom: 0.35rem;
            letter-spacing: 0.55px;
        }

        .admin-stats .card p {
            font-size: 1.55rem;
        }

        .focus-grid {
            grid-template-columns: 1fr;
            gap: 0.7rem;
            padding: 0 0.8rem 0.8rem;
        }

        .focus-card {
            padding: 0.75rem;
        }

        .focus-actions {
            padding: 0 0.8rem 0.8rem;
            gap: 0.55rem;
            flex-direction: column;
        }

        .focus-actions .btn-primary,
        .focus-actions .btn-secondary {
            width: 100%;
            flex: 1 1 auto;
        }
    }

    @media (max-width: 480px) {
        .admin-stats .card {
            padding: 0.65rem 0.45rem;
        }

        .admin-stats .card h3 {
            font-size: 0.62rem;
            letter-spacing: 0.4px;
        }

        .admin-stats .card p {
            font-size: 1.35rem;
        }
    }
</style>
@endpush
