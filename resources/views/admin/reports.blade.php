@extends('layouts.admin')

@section('title', 'New Reports - Sincidentre Department Student Discipline Officer')

@section('page-title', 'New Reports')

@section('content')
    <!-- Success/Error Messages -->
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

    <!-- Reports Table -->
    <section id="reports">
        <div class="table-wrapper">
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
                                    <div>
                                        <small style="color:#c0392b; font-weight:600;">Escalated to Top Management</small>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="action-buttons reports-action-buttons">
                                    <a href="{{ route('admin.reports.show', $report->id) }}" class="btn btn-view report-action-btn">View</a>
                                </div>
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
    </section>

@endsection

@push('styles')
<style>
    .reports-table th,
    .reports-table td {
        white-space: normal;
    }

    .reports-table td {
        vertical-align: middle;
    }

    .reports-table th:last-child,
    .reports-table td:last-child {
        min-width: 140px;
    }

    .category-meta-text {
        color: rgba(255, 255, 255, 0.82);
    }

    .reports-action-buttons {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        flex-wrap: nowrap;
        gap: 0.5rem;
    }

    .inline-action-form {
        display: inline-flex;
        margin: 0;
        flex: 0 0 auto;
    }

    .report-action-btn {
        width: 108px;
        min-width: 108px;
        min-height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        margin: 0;
        line-height: 1.1;
    }

    @media (max-width: 768px) {
        .reports-table th:last-child,
        .reports-table td:last-child {
            min-width: 0;
        }

        .reports-action-buttons {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 0.45rem;
        }

        .inline-action-form {
            width: 100%;
            display: block;
        }

        .report-action-btn {
            width: 100%;
            min-width: 0;
            box-sizing: border-box;
        }

        .reports-action-buttons > a.report-action-btn,
        .reports-action-buttons > button.report-action-btn,
        .reports-action-buttons .inline-action-form .report-action-btn {
            width: 100% !important;
            min-width: 100% !important;
            max-width: 100% !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Reserved for future New Reports page interactions.
</script>
@endpush
