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

    <!-- Filter Section -->
    <section id="filter-section" style="margin-bottom: 20px;">
       <div class="filter-container">
            <form method="GET" action="{{ route('admin.reports') }}" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                
                <!-- Category Filter -->
                <div class="form-group">
                    <label for="category_filter">Category:</label>
                    <select name="category" id="category_filter" style="width: 100%; padding: 8px;">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="form-group">
                    <label for="status_filter">Status:</label>
                    <select name="status" id="status_filter" style="width: 100%; padding: 8px;">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="under review" {{ request('status') == 'under review' ? 'selected' : '' }}>Under Review</option>
                    </select>
                </div>

                <!-- Date From -->
                <div class="form-group">
                    <label for="from_date">Date From:</label>
                    <input type="date" name="from" id="from_date" value="{{ request('from') }}" style="width: 100%; padding: 8px;">
                </div>

                <!-- Date To -->
                <div class="form-group">
                    <label for="to_date">Date To:</label>
                    <input type="date" name="to" id="to_date" value="{{ request('to') }}" style="width: 100%; padding: 8px;">
                </div>

                <!-- Reporter Name -->
                <div class="form-group">
                    <label for="reporter_filter">Reporter Name:</label>
                    <input type="text" name="reporter" id="reporter_filter" placeholder="Search by name..." value="{{ request('reporter') }}" style="width: 100%; padding: 8px;">
                </div>

                <!-- Filter Buttons -->
                <div class="form-group" style="display: flex; gap: 10px;">
                    <button type="submit" class="btn-filter" style="padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer;">
                        Apply Filters
                    </button>
                    @if(request()->hasAny(['category', 'status', 'from', 'to', 'reporter']))
                        <a href="{{ route('admin.reports') }}" class="btn-clear" style="padding: 8px 16px; border-radius: 4px; text-decoration: none; display: inline-block;">
                            Clear Filters
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Quick Stats -->
        @if(isset($statusCounts))
        <div class="stats-container" style="display: flex; gap: 15px; margin-top: 15px; flex-wrap: wrap;">
            <div class="stat-card" style="padding: 15px; border-radius: 5px; flex: 1; min-width: 150px;">
                <h4 style="margin: 0; font-size: 14px;">Pending</h4>
                <p style="margin: 5px 0 0; font-size: 24px; font-weight: bold;">{{ $statusCounts['pending'] ?? 0 }}</p>
            </div>
            <div class="stat-card" style="padding: 15px; border-radius: 5px; flex: 1; min-width: 150px;">
                <h4 style="margin: 0; font-size: 14px;">Approved</h4>
                <p style="margin: 5px 0 0; font-size: 24px; font-weight: bold;">{{ $statusCounts['approved'] ?? 0 }}</p>
            </div>
            <div class="stat-card" style="padding: 15px; border-radius: 5px; flex: 1; min-width: 150px;">
                <h4 style="margin: 0; font-size: 14px;">Rejected</h4>
                <p style="margin: 5px 0 0; font-size: 24px; font-weight: bold;">{{ $statusCounts['rejected'] ?? 0 }}</p>
            </div>
            <div class="stat-card" style="padding: 15px; border-radius: 5px; flex: 1; min-width: 150px;">
                <h4 style="margin: 0; font-size: 14px;">Under Review</h4>
                <p style="margin: 5px 0 0; font-size: 24px; font-weight: bold;">{{ $statusCounts['under_review'] ?? 0 }}</p>
            </div>
        </div>
        @endif
    </section>

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
                                    <button type="submit">Reject Report</button>
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