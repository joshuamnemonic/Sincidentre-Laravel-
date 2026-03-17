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
                        <th>Actions</th>
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

                                    @if(!Auth::user()->is_top_management && in_array($report->category->classification ?? '', ['Major', 'Grave']) && !$report->escalated_to_top_management)
                                        <form method="POST" action="{{ route('admin.reports.escalate', $report->id) }}" class="inline-action-form">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-secondary report-action-btn" onclick="return confirm('Escalate this report to Top Management?')">Escalate</button>
                                        </form>
                                    @endif
                                    
                                    <form method="POST" action="{{ route('admin.reports.approve', $report->id) }}" class="inline-action-form">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-approve report-action-btn">Approve</button>
                                    </form>

                                    <button type="button" class="btn btn-reject report-action-btn" onclick="openRejectModal({{ $report->id }})">Reject</button>
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

    @foreach ($reports as $report)
        <div id="rejectModal{{ $report->id }}" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Reject Report #{{ $report->id }}</h3>
                    <span class="close" onclick="closeRejectModal({{ $report->id }})">&times;</span>
                </div>
                <form method="POST" action="{{ route('admin.reports.reject', $report->id) }}">
                    @csrf
                    @method('PATCH')
                    <div class="form-group">
                        <label><strong>Report ID:</strong></label>
                        <p>#{{ $report->id }}</p>
                    </div>
                    <div class="form-group">
                        <label><strong>Category:</strong></label>
                        <p>{{ $report->category->name ?? 'N/A' }}</p>
                    </div>
                    <div class="form-group">
                        <label for="rejection_reason{{ $report->id }}">Reason for Rejection *</label>
                        <textarea
                            id="rejection_reason{{ $report->id }}"
                            name="rejection_reason"
                            rows="4"
                            placeholder="Please provide a clear reason for rejecting this report..."
                            required></textarea>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="closeRejectModal({{ $report->id }})">Cancel</button>
                        <button type="submit" class="btn-reject">Reject Report</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
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
        min-width: 320px;
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
    // Move all modals to document.body so they escape the transformed .dashboard container
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.modal').forEach(function(modal) {
            document.body.appendChild(modal);
        });
    });

    function openRejectModal(reportId) {
        var modal = document.getElementById('rejectModal' + reportId);
        if (!modal) return;
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeRejectModal(reportId) {
        var modal = document.getElementById('rejectModal' + reportId);
        if (!modal) return;
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
            document.body.style.overflow = '';
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.modal').forEach(function(modal) {
                modal.style.display = 'none';
            });
            document.body.style.overflow = '';
        }
    });
</script>
@endpush
