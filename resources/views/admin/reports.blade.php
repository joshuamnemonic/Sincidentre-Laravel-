@extends('layouts.admin')

@section('title', 'New Reports - Sincidentre Admin')

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
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Reporter</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reports as $report)
                        <tr>
                            <td>{{ $report->id }}</td>
                            <td>{{ $report->title }}</td>
                            <td>{{ $report->user->first_name ?? 'Unknown' }} {{ $report->user->last_name ?? '' }}</td>
                            <td>{{ $report->category->name ?? 'N/A' }}</td>
                            <td>{{ $report->incident_date ? \Carbon\Carbon::parse($report->incident_date)->format('F d, Y') : 'N/A' }}</td>
                            <td>
                                <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                                    {{ $report->status }}
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="{{ route('admin.reports.show', $report->id) }}" class="btn-view">View</a>
                                    
                                    <form method="POST" action="{{ route('admin.reports.approve', $report->id) }}" style="display:inline;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn-approve">Approve</button>
                                    </form>

                                    <button type="button" class="btn-reject" onclick="openRejectModal({{ $report->id }})">Reject</button>
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
                        <label><strong>Report Title:</strong></label>
                        <p>{{ $report->title }}</p>
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