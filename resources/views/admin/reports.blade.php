@extends('layouts.admin')

@section('title', 'Review Queue - Sincidentre Admin')

@section('page-title', 'Review Queue')

@section('header-search')
    <form method="get" action="{{ route('admin.reports') }}">
        <input type="text" name="search" placeholder="Search reports…" value="{{ request('search') }}">
        <button type="submit">Search</button>
    </form>
@endsection

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
                            <td>{{ $report->incident_date }}</td>
                            <td>
                                <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                                    {{ $report->status }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.reports.show', $report->id) }}" class="btn-view">View</a> |
                                
                                <form method="POST" action="{{ route('admin.reports.approve', $report->id) }}" style="display:inline;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-approve">Approve</button>
                                </form> |

                                <button type="button" class="btn-reject" onclick="openRejectModal({{ $report->id }})">Reject</button>
                            </td>
                        </tr>

                        <!-- Reject Modal for each report -->
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
                                    <button type="button" onclick="closeRejectModal({{ $report->id }})">Cancel</button>
                                    <button type="submit" style="background-color: #dc3545; color: white;">Reject Report</button>
                                </form>
                            </div>
                        </div>
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

@push('scripts')
<script>
    // Sidebar toggle for mobile
    const sidebar = document.querySelector('.sidebar');
    const menuBtn = document.createElement('button');
    menuBtn.className = 'mobile-menu-toggle';
    menuBtn.innerHTML = '☰';
    document.body.appendChild(menuBtn);
    
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
    
    menuBtn.addEventListener('click', () => {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    });
    
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    });

    // Modal functions
    function openRejectModal(reportId) {
        document.getElementById('rejectModal' + reportId).style.display = 'block';
    }

    function closeRejectModal(reportId) {
        document.getElementById('rejectModal' + reportId).style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
</script>
@endpush