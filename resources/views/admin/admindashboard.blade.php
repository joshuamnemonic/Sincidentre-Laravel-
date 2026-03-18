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

    <section class="continue-work-wrap">
        <article class="continue-work-card">
            <h2>Continue Last Managed Report</h2>

            @if(!empty($lastManagedReport))
                <p class="continue-work-primary">
                    <strong>Report {{ $lastManagedReport->id }}</strong>
                    <span class="continue-work-meta">
                        {{ \App\Models\Report::labelForStatus($lastManagedReport->status) }}
                        @if($lastManagedReport->category)
                            · {{ strtoupper($lastManagedReport->category->main_category_code) }} - {{ $lastManagedReport->category->name }}
                        @endif
                    </span>
                </p>

                @if(!empty($lastManagedResponse))
                    <p class="continue-work-line">
                        <strong>Last action:</strong>
                        {{ $lastManagedResponse->response_type ?? 'Handling Response' }}
                        @if($lastManagedResponse->created_at)
                            · {{ $lastManagedResponse->created_at->format('M d, Y h:i A') }}
                        @endif
                    </p>
                @endif

                @if(!empty($lastManagedNextAction))
                    <p class="continue-work-line">
                        <strong>Next action:</strong> {{ $lastManagedNextAction }}
                    </p>
                @endif

                <div class="continue-work-actions">
                    <a href="{{ route('admin.handlereports.show', $lastManagedReport->id) }}" class="btn-primary">Continue Report</a>
                </div>
            @else
                <p class="continue-work-empty">No managed reports yet. Start from New Reports or Handle Reports.</p>
            @endif
        </article>
    </section>

@endsection

@push('styles')
<style>
    .continue-work-wrap {
        margin-bottom: 1rem;
        padding: 0 1rem;
    }

    .continue-work-card {
        border: 1px solid rgba(255, 255, 255, 0.24);
        border-radius: 0.95rem;
        background: linear-gradient(135deg, rgba(11, 31, 83, 0.9), rgba(15, 118, 110, 0.65));
        padding: 0.95rem 1rem;
    }

    .continue-work-card h2 {
        margin: 0 0 0.55rem;
        font-size: 1.05rem;
        color: #ffffff;
    }

    .continue-work-primary {
        margin: 0;
        color: #ffffff;
    }

    .continue-work-meta {
        display: block;
        margin-top: 0.18rem;
        color: rgba(255, 255, 255, 0.86);
        font-size: 0.88rem;
    }

    .continue-work-line {
        margin: 0.45rem 0 0;
        color: rgba(255, 255, 255, 0.92);
        font-size: 0.92rem;
    }

    .continue-work-empty {
        margin: 0;
        color: rgba(255, 255, 255, 0.9);
    }

    .continue-work-actions {
        margin-top: 0.75rem;
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
    }

    .continue-work-actions .btn-primary {
        text-decoration: none;
        min-height: 40px;
        padding: 0.65rem 0.95rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

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

    @media (max-width: 768px) {
        .continue-work-wrap {
            padding: 0 0.8rem;
        }

        .continue-work-card {
            padding: 0.8rem 0.85rem;
        }

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
