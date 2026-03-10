@extends('layouts.admin')

@section('title', 'Audit Trail - Sincidentre Admin')

@section('page-title', 'Audit Trail')

@section('header-search')
  <div style="display: flex; align-items: center; gap: 10px;">
    <span style="color: #666; font-size: 0.9em;">
      Showing activities for: <strong>{{ Auth::user()->department->name ?? 'Your Department' }}</strong>
    </span>
  </div>
@endsection

@section('content')
  <!-- Filter Section -->
  <div class="filter-section" style="margin-bottom: 20px;">
    <form method="GET" action="{{ route('admin.activitylogs') }}" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
      
      <!-- Search -->
      <div class="form-group">
        <label for="search">Search:</label>
        <input type="text" 
               id="search"
               name="search" 
               placeholder="Search activities..." 
               value="{{ request('search') }}"
               style="width: 100%; padding: 8px;">
      </div>

      <!-- Action Filter -->
      <div class="form-group">
        <label for="action">Action Type:</label>
        <select name="action" id="action" style="width: 100%; padding: 8px;">
          <option value="">All Actions</option>
          <option value="Report Approved" {{ request('action') == 'Report Approved' ? 'selected' : '' }}>Report Approved</option>
          <option value="Report Rejected" {{ request('action') == 'Report Rejected' ? 'selected' : '' }}>Report Rejected</option>
          <option value="Status Updated" {{ request('action') == 'Status Updated' ? 'selected' : '' }}>Status Updated</option>
          <option value="Assignment Updated" {{ request('action') == 'Assignment Updated' ? 'selected' : '' }}>Assignment Updated</option>
          <option value="Report Updated" {{ request('action') == 'Report Updated' ? 'selected' : '' }}>Report Updated</option>
        </select>
      </div>

      <!-- Date From -->
      <div class="form-group">
        <label for="from_date">From Date:</label>
        <input type="date" 
               id="from_date"
               name="from" 
               value="{{ request('from') }}" 
               style="width: 100%; padding: 8px;">
      </div>

      <!-- Date To -->
      <div class="form-group">
        <label for="to_date">To Date:</label>
        <input type="date" 
               id="to_date"
               name="to" 
               value="{{ request('to') }}" 
               style="width: 100%; padding: 8px;">
      </div>

      <!-- Filter Buttons -->
      <div class="form-group" style="display: flex; gap: 10px;">
        <button type="submit" class="btn-filter" style="padding: 8px 16px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
          Apply Filters
        </button>
        @if(request()->hasAny(['search', 'action', 'from', 'to']))
          <a href="{{ route('admin.activitylogs') }}" class="btn-clear" style="padding: 8px 16px; background-color: #6c757d; color: white; border-radius: 4px; text-decoration: none; display: inline-block;">
            Clear
          </a>
        @endif
      </div>
    </form>
  </section>

  <!-- Activity Logs Table -->
  <section id="activity-logs">
    <h2>Activity History</h2>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Date & Time</th>
            <th>Performed By</th>
            <th>Action</th>
            <th>Report ID</th>
            <th>Status Change</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($activities as $activity)
            <tr>
              <td>
                {{ $activity->created_at->format('M d, Y H:i') }}
                <br>
                <small style="color: #999;">{{ $activity->created_at->diffForHumans() }}</small>
              </td>
              <td>
                <strong>
                  @if($activity->performedBy)
                    {{ $activity->performedBy->first_name }} {{ $activity->performedBy->last_name }}
                    @if($activity->performedBy->id === Auth::id())
                      <span style="color: #007bff; font-size: 0.85em;">(You)</span>
                    @endif
                  @elseif($activity->admin)
                    {{ $activity->admin->first_name }} {{ $activity->admin->last_name }}
                    @if($activity->admin->id === Auth::id())
                      <span style="color: #007bff; font-size: 0.85em;">(You)</span>
                    @endif
                  @else
                    Unknown Admin
                  @endif
                </strong>
              </td>
              <td>
                <span class="action-badge">{{ $activity->action }}</span>
              </td>
              <td>
                @if($activity->report)
                  <a href="{{ route('admin.reports.show', $activity->report_id) }}" style="color: #007bff; text-decoration: none;">
                    #{{ $activity->report_id }}
                  </a>
                  @if($activity->report->title)
                    <br>
                    <small style="color: #666;">{{ Str::limit($activity->report->title, 30) }}</small>
                  @endif
                @else
                  <span style="color: #999;">N/A</span>
                @endif
              </td>
              <td>
                @if($activity->old_status && $activity->new_status)
                  <span class="status {{ strtolower(str_replace(' ', '-', $activity->old_status)) }}">
                    {{ ucfirst($activity->old_status) }}
                  </span>
                  <span style="color: #999;">→</span>
                  <span class="status {{ strtolower(str_replace(' ', '-', $activity->new_status)) }}">
                    {{ ucfirst($activity->new_status) }}
                  </span>
                @elseif($activity->new_status)
                  <span class="status {{ strtolower(str_replace(' ', '-', $activity->new_status)) }}">
                    {{ ucfirst($activity->new_status) }}
                  </span>
                @else
                  <span style="color: #999;">-</span>
                @endif
              </td>
              <td>
                @if(strlen($activity->remarks) > 50)
                  <span id="short-{{ $activity->id }}">{{ Str::limit($activity->remarks, 50) }}</span>
                  <span id="full-{{ $activity->id }}" style="display: none;">{{ $activity->remarks }}</span>
                  <button onclick="toggleRemarks({{ $activity->id }})" style="background: none; border: none; color: #007bff; cursor: pointer; font-size: 0.85em;">
                    <span id="btn-{{ $activity->id }}">Read more</span>
                  </button>
                @else
                  {{ $activity->remarks }}
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" style="text-align:center; padding: 40px;">
                <p style="color: #999; margin: 0;">No activity logs found.</p>
                <small>Activities from {{ Auth::user()->department->name ?? 'your department' }} will appear here.</small>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top: 20px;">
      {{ $activities->appends(request()->query())->links() }}
    </div>
  </section>
@endsection

@push('scripts')
  <script>
    function toggleRemarks(id) {
      const short = document.getElementById('short-' + id);
      const full = document.getElementById('full-' + id);
      const btn = document.getElementById('btn-' + id);
      
      if (short.style.display === 'none') {
        short.style.display = 'inline';
        full.style.display = 'none';
        btn.textContent = 'Read more';
      } else {
        short.style.display = 'none';
        full.style.display = 'inline';
        btn.textContent = 'Read less';
      }
    }
  </script>
@endpush