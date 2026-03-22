@extends('layouts.admin')

@section('title', 'Pending Employee Registrations - Sincidentre')

@section('content')
<div class="page-container">
    <header class="page-header">
        <h1>Pending Employee Registrations</h1>
        <p>Review and approve/reject employee registration requests</p>
    </header>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-error">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="content-card">
        @if($pendingRegistrations->count() > 0)
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingRegistrations as $registration)
                            <tr>
                                <td data-label="ID">#{{ $registration->id }}</td>
                                <td data-label="Name">{{ $registration->first_name }} {{ $registration->last_name }}</td>
                                <td data-label="Username">
                                    <span class="badge badge-info">{{ $registration->username }}</span>
                                </td>
                                <td data-label="Email">{{ $registration->email }}</td>
                                <td data-label="Status">
                                    @if($registration->status === 'pending')
                                        <span class="badge badge-warning">Pending</span>
                                    @elseif($registration->status === 'approved')
                                        <span class="badge badge-success">Approved</span>
                                    @else
                                        <span class="badge badge-error">Rejected</span>
                                    @endif
                                </td>
                                <td data-label="Submitted">{{ $registration->created_at->format('M d, Y H:i') }}</td>
                                <td data-label="Actions">
                                    <div class="action-buttons">
                                        <a href="{{ route('admin.pending-employees.show', $registration->id) }}"
                                           class="btn btn-sm btn-primary">
                                            View & Review
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pagination-wrapper">
                {{ $pendingRegistrations->links() }}
            </div>
        @else
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <h3>No Pending Registrations</h3>
                <p>There are currently no employee registration requests pending review.</p>
            </div>
        @endif
    </div>
</div>

<style>
.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 500;
}

.badge-info {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.badge-warning {
    background: rgba(251, 191, 36, 0.15);
    color: #fbbf24;
    border: 1px solid rgba(251, 191, 36, 0.3);
}

.badge-success {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.badge-error {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
}

.empty-state-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.empty-state h3 {
    color: rgba(255, 255, 255, 0.95);
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: rgba(255, 255, 255, 0.7);
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}
</style>
@endsection
