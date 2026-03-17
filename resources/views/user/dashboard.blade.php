@extends('layouts.app')

@section('title', 'User Dashboard - Sincidentre')

@section('content')
    @php
        $statusCards = [
            [
                'label' => 'Pending',
                'value' => $pendingReports,
                'status' => \App\Models\Report::STATUS_PENDING,
                'class' => 'status-pending',
            ],
            [
                'label' => 'Approved',
                'value' => $approvedReports,
                'status' => \App\Models\Report::STATUS_APPROVED,
                'class' => 'status-approved',
            ],
            [
                'label' => 'Rejected',
                'value' => $rejectedReports,
                'status' => \App\Models\Report::STATUS_REJECTED,
                'class' => 'status-rejected',
            ],
            [
                'label' => 'Under Review',
                'value' => $underReviewReports,
                'status' => \App\Models\Report::STATUS_UNDER_REVIEW,
                'class' => 'status-under-review',
            ],
            [
                'label' => 'Resolved',
                'value' => $resolvedReports,
                'status' => \App\Models\Report::STATUS_RESOLVED,
                'class' => 'status-resolved',
            ],
        ];
    @endphp

    <div class="dashboard-lite">
        <div class="welcome">
            Welcome, {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}!
        </div>

        <div class="cards">
            <a href="{{ route('myreports') }}" class="card animate card-link status-total">
                <h3>Total Reports</h3>
                <p>{{ $totalReports }}</p>
            </a>

            @foreach ($statusCards as $card)
                <a href="{{ route('myreports', ['status' => [$card['status']]]) }}" class="card animate card-link {{ $card['class'] }}">
                    <h3>{{ $card['label'] }}</h3>
                    <p>{{ $card['value'] }}</p>
                </a>
            @endforeach
        </div>

        <div class="recent-reports animate">
            <h3>Quick Shortcuts</h3>

            <form method="GET" action="{{ route('dashboard') }}" class="shortcut-search">
                <input
                    type="search"
                    name="search"
                    id="dashboard-quick-search"
                    placeholder="Quick search recent reports"
                    value="{{ $search }}"
                    aria-label="Quick search recent reports"
                >
                <button type="submit">Search</button>
                <a href="{{ route('myreports', ['search' => $search, 'status' => $status]) }}" class="btn-secondary">Open in My Reports</a>
            </form>

            <div class="shortcut-chips" role="group" aria-label="Quick status shortcuts">
                <a href="{{ route('dashboard') }}" class="shortcut-chip {{ empty($status) ? 'active' : '' }}">All</a>
                @foreach ($statusCards as $card)
                    <a href="{{ route('dashboard', ['status' => $card['status']]) }}" class="shortcut-chip {{ $card['class'] }} {{ $status === $card['status'] ? 'active' : '' }}">
                        {{ $card['label'] }}
                    </a>
                @endforeach
            </div>

            <div class="table-wrapper">
                <table id="reports-table">
                    <thead>
                        <tr>
                            <th>Report ID</th>
                            <th>Category</th>
                            <th>Submitted</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentReports as $report)
                            <tr class="report-row" tabindex="0" data-href="{{ route('report.show', $report->id) }}">
                                <td>{{ $report->id }}</td>
                                <td>{{ strtoupper(optional($report->category)->main_category_code ?: 'N/A') }}</td>
                                <td>{{ optional($report->submitted_at)->format('M d, Y') ?? optional($report->created_at)->format('M d, Y') }}</td>
                                <td>
                                    <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">{{ $report->status }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty-state">No recent reports found for this quick filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .dashboard-lite .card-link {
            text-decoration: none;
            color: inherit;
            border: 1px solid transparent;
        }

        .dashboard-lite .card-link.status-total {
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.15);
        }

        .dashboard-lite .card-link.status-pending {
            color: var(--status-pending-color);
            border-color: var(--status-pending-border);
            background: var(--status-pending-bg);
        }

        .dashboard-lite .card-link.status-approved {
            color: var(--status-approved-color);
            border-color: var(--status-approved-border);
            background: var(--status-approved-bg);
        }

        .dashboard-lite .card-link.status-rejected {
            color: var(--status-rejected-color);
            border-color: var(--status-rejected-border);
            background: var(--status-rejected-bg);
        }

        .dashboard-lite .card-link.status-under-review {
            color: var(--status-review-color);
            border-color: var(--status-review-border);
            background: var(--status-review-bg);
        }

        .dashboard-lite .card-link.status-resolved {
            color: var(--status-resolved-color);
            border-color: var(--status-resolved-border);
            background: var(--status-resolved-bg);
        }

        .dashboard-lite .shortcut-search {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .dashboard-lite .shortcut-search input {
            flex: 1 1 240px;
        }

        .dashboard-lite .shortcut-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            padding: 0 2rem 1rem;
        }

        .dashboard-lite .shortcut-chip {
            display: inline-flex;
            align-items: center;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #fff;
            background: rgba(255, 255, 255, 0.08);
            font-size: 0.85rem;
            font-weight: 600;
        }

        .dashboard-lite .shortcut-chip.active {
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.25);
        }

        .dashboard-lite .shortcut-chip.status-pending {
            border-color: var(--status-pending-border);
            background: var(--status-pending-bg);
            color: var(--status-pending-color);
        }

        .dashboard-lite .shortcut-chip.status-approved {
            border-color: var(--status-approved-border);
            background: var(--status-approved-bg);
            color: var(--status-approved-color);
        }

        .dashboard-lite .shortcut-chip.status-rejected {
            border-color: var(--status-rejected-border);
            background: var(--status-rejected-bg);
            color: var(--status-rejected-color);
        }

        .dashboard-lite .shortcut-chip.status-under-review {
            border-color: var(--status-review-border);
            background: var(--status-review-bg);
            color: var(--status-review-color);
        }

        .dashboard-lite .shortcut-chip.status-resolved {
            border-color: var(--status-resolved-border);
            background: var(--status-resolved-bg);
            color: var(--status-resolved-color);
        }

        .dashboard-lite .report-row {
            cursor: pointer;
        }

        .dashboard-lite .report-row:focus-visible {
            outline: 2px solid #93c5fd;
            outline-offset: -2px;
        }

        .dashboard-lite .empty-state {
            text-align: center;
            padding: 2rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .dashboard-lite .cards {
                grid-template-columns: repeat(2, 1fr);
            }

            .dashboard-lite .shortcut-chips {
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            document.querySelectorAll('.report-row[data-href]').forEach(function (row) {
                row.addEventListener('click', function () {
                    window.location.href = row.dataset.href;
                });

                row.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        window.location.href = row.dataset.href;
                    }
                });
            });
        })();
    </script>
@endpush
