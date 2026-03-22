@extends('layouts.admin')

@section('title', 'New Reports - Sincidentre Department Student Discipline Officer')

@section('page-title', 'New Reports')

@section('content')

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <section id="reports">

        {{-- ── Desktop Table ── --}}
        <div class="table-wrapper desktop-reports-table">
            <table class="handle-report-table reports-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Reporter</th>
                        <th>Category</th>
                        <th>Person Involvement</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>View</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reports as $report)
                        <tr>
                            <td>{{ $report->id }}</td>
                            <td>{{ $report->user->first_name ?? 'Unknown' }} {{ $report->user->last_name ?? '' }}</td>
                            <td>
                                @if($report->category)
                                    {{ strtoupper($report->category->main_category_code) }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>{{ $report->person_involvement ? ucfirst($report->person_involvement) : 'N/A' }}</td>
                            <td>{{ $report->incident_date ? \Carbon\Carbon::parse($report->incident_date)->format('F d, Y') : 'N/A' }}</td>
                            <td>
                                <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                                    {{ $report->status }}
                                </span>
                                @if($report->escalated_to_top_management)
                                    <div><small class="escalated-label">Escalated to Top Management</small></div>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.reports.show', $report->id) }}" class="btn btn-view report-action-btn">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;">No reports found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ── Mobile Cards ── --}}
        <div class="mobile-reports-list">
            @forelse ($reports as $report)
                <a href="{{ route('admin.reports.show', $report->id) }}" class="mobile-report-card">
                    <div class="mrc-top">
                        <div class="mrc-left">
                            <span class="mrc-id">#{{ $report->id }}</span>
                            <span class="mrc-category">
                                {{ $report->category ? strtoupper($report->category->main_category_code) : 'N/A' }}
                            </span>
                        </div>
                        <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                            {{ $report->status }}
                        </span>
                    </div>

                    <div class="mrc-reporter">
                        👤 {{ $report->user->first_name ?? 'Unknown' }} {{ $report->user->last_name ?? '' }}
                    </div>

                    <div class="mrc-bottom">
                        @if($report->incident_date)
                        <span class="mrc-meta">🗓 {{ \Carbon\Carbon::parse($report->incident_date)->format('M d, Y') }}</span>
                        @endif
                        @if($report->person_involvement)
                        <span class="mrc-meta">👥 {{ ucfirst($report->person_involvement) }}</span>
                        @endif
                        @if($report->escalated_to_top_management)
                        <span class="mrc-escalated">⚠️ Escalated</span>
                        @endif
                    </div>
                </a>
            @empty
                <div class="mobile-empty-state">No reports found.</div>
            @endforelse
        </div>

    </section>

@endsection

@push('styles')
<style>
    /* ── Desktop table tweaks ── */
    .reports-table th,
    .reports-table td {
        white-space: normal;
        vertical-align: middle;
    }

    .reports-table th:last-child,
    .reports-table td:last-child {
        min-width: 120px;
    }

    .report-action-btn {
        min-width: 90px;
        min-height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-align: center;
    }

    .escalated-label {
        color: #fca5a5;
        font-weight: 600;
        font-size: 0.75rem;
    }

    /* ── Show/hide by breakpoint ── */
    .desktop-reports-table { display: block; }
    .mobile-reports-list   { display: none; }

    /* ── Mobile report cards ── */
    .mobile-report-card {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        padding: 0.875rem 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        text-decoration: none;
        color: inherit;
    }

    .mobile-report-card:last-child { border-bottom: none; }
    .mobile-report-card:active,
    .mobile-report-card:hover { background: rgba(255,255,255,0.05); }

    .mrc-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }

    .mrc-left {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .mrc-id {
        font-size: 0.75rem;
        font-weight: 700;
        color: rgba(255,255,255,0.5);
    }

    .mrc-category {
        font-size: 0.78rem;
        font-weight: 700;
        color: #93c5fd;
        background: rgba(96,165,250,0.15);
        border: 1px solid rgba(96,165,250,0.25);
        border-radius: 0.35rem;
        padding: 0.1rem 0.4rem;
    }

    .mrc-reporter {
        font-size: 0.9rem;
        font-weight: 600;
        color: #fff;
    }

    .mrc-bottom {
        display: flex;
        flex-wrap: wrap;
        gap: 0.3rem 0.875rem;
        margin-top: 0.1rem;
    }

    .mrc-meta {
        font-size: 0.78rem;
        color: rgba(255,255,255,0.6);
    }

    .mrc-escalated {
        font-size: 0.75rem;
        font-weight: 700;
        color: #fca5a5;
    }

    .mobile-empty-state {
        padding: 2rem 1rem;
        text-align: center;
        color: rgba(255,255,255,0.7);
        font-size: 0.95rem;
    }

    /* ── Switch to cards on mobile ── */
    @media (max-width: 768px) {
        .desktop-reports-table { display: none; }
        .mobile-reports-list   { display: block; }
    }
</style>
@endpush

@push('scripts')
<script>
    // Reserved for future New Reports page interactions.
</script>
@endpush