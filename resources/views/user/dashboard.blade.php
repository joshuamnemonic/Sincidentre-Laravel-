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
            <h3>Dashboard Overview</h3>

            <div class="overview-grid" aria-label="Dashboard overview">
                <section class="overview-card overview-attention">
                    <h4>Attention Required</h4>
                    <p class="overview-metric">{{ $attentionReports }}</p>
                    <p class="overview-text">Pending, under review, and rejected reports that may need action.</p>
                    <a href="{{ route('myreports', ['status' => [\App\Models\Report::STATUS_PENDING, \App\Models\Report::STATUS_UNDER_REVIEW, \App\Models\Report::STATUS_REJECTED]]) }}" class="btn-primary overview-link">View Priority Reports</a>
                </section>

                <section class="overview-card overview-health">
                    <h4>My Report Health</h4>
                    <p class="overview-text">Resolution rate this month</p>
                    <p class="overview-metric">{{ $resolutionRate }}%</p>
                    <div class="health-meta">
                        <span>{{ $monthlyResolvedReports }} resolved</span>
                        <span>{{ $monthlyTotalReports }} total this month</span>
                    </div>
                </section>

                <section class="overview-card overview-activity">
                    <h4>Recent Activity</h4>
                    <ul class="activity-list">
                        @forelse ($recentActivity as $activity)
                            <li>
                                <span class="activity-title">Report {{ $activity->id }} - {{ $activity->status }}</span>
                                <span class="activity-meta">{{ optional($activity->updated_at)->format('M d, Y h:i A') }}</span>
                            </li>
                        @empty
                            <li>
                                <span class="activity-title">No recent activity</span>
                            </li>
                        @endforelse
                    </ul>
                </section>
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
            display: none;
        }

        .dashboard-lite .cards {
            gap: 0.85rem;
            margin-bottom: 1.25rem;
        }

        .dashboard-lite .cards .card {
            padding: 1rem 0.75rem;
            border-radius: 1.1rem;
        }

        .dashboard-lite .cards .card h3 {
            margin: 0 0 0.5rem;
            font-size: 0.76rem;
            letter-spacing: 0.8px;
        }

        .dashboard-lite .cards .card p {
            font-size: 2.05rem;
        }

        .dashboard-lite .overview-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.85rem;
            padding: 1rem 1.25rem;
        }

        .dashboard-lite .overview-card {
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 0.8rem;
            background: rgba(255, 255, 255, 0.06);
            padding: 0.95rem;
        }

        .dashboard-lite .overview-card h4 {
            margin: 0 0 0.45rem;
            color: #ffffff;
            font-size: 0.95rem;
            font-weight: 700;
        }

        .dashboard-lite .overview-text {
            color: rgba(255, 255, 255, 0.86);
            margin: 0;
            font-size: 0.82rem;
            line-height: 1.35;
        }

        .dashboard-lite .overview-metric {
            margin: 0.35rem 0;
            color: #ffffff;
            font-size: 1.7rem;
            font-weight: 800;
            line-height: 1;
        }

        .dashboard-lite .overview-link {
            margin-top: 0.65rem;
            min-height: 40px;
            font-size: 0.88rem;
            padding: 0.6rem 0.85rem;
            width: 100%;
        }

        .dashboard-lite .health-meta {
            margin-top: 0.4rem;
            display: flex;
            gap: 0.65rem;
            flex-wrap: wrap;
            color: rgba(255, 255, 255, 0.82);
            font-size: 0.8rem;
        }

        .dashboard-lite .activity-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
        }

        .dashboard-lite .activity-list li {
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
            padding-bottom: 0.45rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .dashboard-lite .activity-list li:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .dashboard-lite .activity-title {
            color: #ffffff;
            font-size: 0.83rem;
            font-weight: 600;
        }

        .dashboard-lite .activity-meta {
            color: rgba(255, 255, 255, 0.78);
            font-size: 0.74rem;
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
                gap: 0.6rem;
            }

            .dashboard-lite .cards .card {
                padding: 0.75rem 0.55rem;
                border-radius: 0.9rem;
            }

            .dashboard-lite .cards .card h3 {
                font-size: 0.68rem;
                margin-bottom: 0.35rem;
                letter-spacing: 0.55px;
            }

            .dashboard-lite .cards .card p {
                font-size: 1.55rem;
            }

            .dashboard-lite .overview-grid {
                grid-template-columns: 1fr;
                gap: 0.65rem;
                padding: 0.8rem;
            }

            .dashboard-lite .overview-card {
                padding: 0.8rem;
            }

            .dashboard-lite .overview-metric {
                font-size: 1.45rem;
            }
        }

        @media (max-width: 480px) {
            .dashboard-lite .cards .card {
                padding: 0.65rem 0.45rem;
            }

            .dashboard-lite .cards .card p {
                font-size: 1.35rem;
            }

            .dashboard-lite .cards .card h3 {
                font-size: 0.62rem;
                letter-spacing: 0.4px;
            }
        }
    </style>
@endpush

