@extends('layouts.admin')

@section('title', 'Audit Trail - Sincidentre Department Student Discipline Officer')

@section('page-title', 'Audit Trail')

@section('header-search')
  <div style="display: flex; align-items: center; gap: 10px;">
    @if($isTopManagement ?? false)
      <span style="color: #666; font-size: 0.9em;">Showing activities for: <strong>All Departments</strong></span>
    @else
      <span style="color: #666; font-size: 0.9em;">
        Showing activities for: <strong>{{ Auth::user()->department->name ?? 'Your Department' }}</strong>
      </span>
    @endif
  </div>
@endsection

@section('content')

  @php
    $activeAuditFilters = (request()->filled('search') ? 1 : 0)
      + (request()->filled('action') ? 1 : 0)
      + (request()->filled('status') ? 1 : 0)
      + (request()->filled('department') ? 1 : 0)
      + (request()->filled('from') ? 1 : 0)
      + (request()->filled('to') ? 1 : 0);
  @endphp

  {{-- ================================================================
       DESKTOP FILTER (original, hidden on mobile)
       ================================================================ --}}
  <div class="desktop-filter-wrap" style="margin-bottom: 20px;">
    <form method="GET" action="{{ route('admin.activitylogs') }}" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">

      <div class="form-group">
        <label for="search">Search:</label>
        <input type="text" id="search" name="search" placeholder="Search activities..." value="{{ request('search') }}" style="width: 100%; padding: 8px;">
      </div>

      <div class="form-group">
        <label for="action">Action Type:</label>
        <select name="action" id="action" style="width: 100%; padding: 8px;">
          <option value="">All Actions</option>
          @foreach(($actions ?? collect()) as $action)
            <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>{{ $action }}</option>
          @endforeach
        </select>
      </div>

      <div class="form-group">
        <label for="status">Report Status:</label>
        <select name="status" id="status" style="width: 100%; padding: 8px;">
          <option value="">All Statuses</option>
          @foreach(($statuses ?? []) as $status)
            <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ $status }}</option>
          @endforeach
        </select>
      </div>

      @if($isTopManagement ?? false)
      <div class="form-group">
        <label for="department">Department:</label>
        <select name="department" id="department" style="width: 100%; padding: 8px;">
          <option value="">All Departments</option>
          @foreach(($departments ?? collect()) as $department)
            <option value="{{ $department->id }}" {{ (string) request('department') === (string) $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
          @endforeach
        </select>
      </div>
      @endif

      <div class="form-group">
        <label for="from_date">From Date:</label>
        <input type="date" id="from_date" name="from" value="{{ request('from') }}" style="width: 100%; padding: 8px;">
      </div>

      <div class="form-group">
        <label for="to_date">To Date:</label>
        <input type="date" id="to_date" name="to" value="{{ request('to') }}" style="width: 100%; padding: 8px;">
      </div>

      <div class="form-group" style="display: flex; gap: 10px;">
        <button type="submit" class="btn-filter" style="padding: 8px 16px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
          Apply Filters
        </button>
        @if(request()->hasAny(['search', 'action', 'status', 'department', 'from', 'to']))
          <a href="{{ route('admin.activitylogs') }}" class="btn-clear" style="padding: 8px 16px; background-color: #6c757d; color: white; border-radius: 4px; text-decoration: none; display: inline-block;">
            Clear
          </a>
        @endif
      </div>
    </form>
  </div>

  {{-- ================================================================
       MOBILE UFP PANEL (hidden on desktop)
       ================================================================ --}}
  <div class="ufp-panel mobile-filter-wrap">

    <div class="ufp-mobile-topbar">
      <div class="ufp-search-wrap">
        <input
          type="search"
          id="ufp-search-mobile"
          form="audit-filter-form-mobile"
          name="search"
          value="{{ request('search') }}"
          placeholder="Search activities…"
          autocomplete="off"
          class="ufp-search-input"
          aria-label="Search activities"
        >
      </div>
      <button type="button" id="ufp-toggle-btn" class="ufp-toggle-btn" aria-expanded="false" aria-controls="ufp-collapsible">
        <span>⚙️</span>
        <span>Filters</span>
        @if($activeAuditFilters > 0)
          <span class="ufp-active-badge">{{ $activeAuditFilters }}</span>
        @endif
      </button>
    </div>

    <form method="GET" action="{{ route('admin.activitylogs') }}" id="audit-filter-form-mobile">
      <div id="ufp-collapsible" class="ufp-collapsible-body collapsed">
        <div class="ufp-inner-grid">

          <div class="ufp-field">
            <label for="ufp-action" class="ufp-label">Action Type</label>
            <select name="action" id="ufp-action">
              <option value="">All Actions</option>
              @foreach(($actions ?? collect()) as $action)
                <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>{{ $action }}</option>
              @endforeach
            </select>
          </div>

          <div class="ufp-field">
            <label for="ufp-status" class="ufp-label">Report Status</label>
            <select name="status" id="ufp-status">
              <option value="">All Statuses</option>
              @foreach(($statuses ?? []) as $status)
                <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ $status }}</option>
              @endforeach
            </select>
          </div>

          @if($isTopManagement ?? false)
          <div class="ufp-field">
            <label for="ufp-department" class="ufp-label">Department</label>
            <select name="department" id="ufp-department">
              <option value="">All Departments</option>
              @foreach(($departments ?? collect()) as $department)
                <option value="{{ $department->id }}" {{ (string) request('department') === (string) $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
              @endforeach
            </select>
          </div>
          @endif

          <div class="ufp-field">
            <label for="ufp-from" class="ufp-label">From Date</label>
            <input type="date" name="from" id="ufp-from" value="{{ request('from') }}">
          </div>

          <div class="ufp-field">
            <label for="ufp-to" class="ufp-label">To Date</label>
            <input type="date" name="to" id="ufp-to" value="{{ request('to') }}">
          </div>

          <input type="hidden" id="ufp-search-hidden" name="search" value="{{ request('search') }}">

        </div>

        <div class="ufp-actions">
          <button type="submit" class="ufp-apply-btn">Apply Filters</button>
          @if($activeAuditFilters > 0)
            <a href="{{ route('admin.activitylogs') }}" class="ufp-clear-btn">Clear All</a>
          @endif
        </div>
      </div>
    </form>
  </div>

  <!-- Activity Logs Section -->
  <section id="activity-logs">
    <h2>Activity History</h2>

    {{-- ================================================================
         DESKTOP TABLE (original, unchanged)
         ================================================================ --}}
    <div class="table-wrapper desktop-audit-table">
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
                {{ $activity->created_at->format('M d, Y H:i') }}<br>
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
                    Unknown Department Student Discipline Officer
                  @endif
                </strong>
              </td>
              <td><span class="action-badge">{{ $activity->action }}</span></td>
              <td>
                @if($activity->report)
                  <a href="{{ route('admin.reports.show', $activity->report_id) }}" style="color: #007bff; text-decoration: none;">
                    #{{ $activity->report_id }}
                  </a>
                @else
                  <span style="color: #999;">N/A</span>
                @endif
              </td>
              <td>
                @if($activity->old_status && $activity->new_status)
                  <span class="status {{ strtolower(str_replace(' ', '-', $activity->old_status)) }}">{{ ucfirst($activity->old_status) }}</span>
                  <span style="color: #999;">→</span>
                  <span class="status {{ strtolower(str_replace(' ', '-', $activity->new_status)) }}">{{ ucfirst($activity->new_status) }}</span>
                @elseif($activity->new_status)
                  <span class="status {{ strtolower(str_replace(' ', '-', $activity->new_status)) }}">{{ ucfirst($activity->new_status) }}</span>
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

    {{-- ================================================================
         MOBILE CARDS
         ================================================================ --}}
    <div class="mobile-audit-list">
      @forelse ($activities as $activity)
        <div class="mobile-audit-card">

          {{-- Row 1: Date + Action badge --}}
          <div class="mac-top">
            <div class="mac-date">
              <span class="mac-date-main">{{ $activity->created_at->format('M d, Y · H:i') }}</span>
              <span class="mac-date-rel">{{ $activity->created_at->diffForHumans() }}</span>
            </div>
            <span class="action-badge">{{ $activity->action }}</span>
          </div>

          {{-- Row 2: Performed by --}}
          <div class="mac-row">
            <span class="mac-label">By</span>
            <span class="mac-value">
              @if($activity->performedBy)
                {{ $activity->performedBy->first_name }} {{ $activity->performedBy->last_name }}
                @if($activity->performedBy->id === Auth::id())
                  <span class="mac-you">(You)</span>
                @endif
              @elseif($activity->admin)
                {{ $activity->admin->first_name }} {{ $activity->admin->last_name }}
                @if($activity->admin->id === Auth::id())
                  <span class="mac-you">(You)</span>
                @endif
              @else
                <span class="mac-muted">Unknown Officer</span>
              @endif
            </span>
          </div>

          {{-- Row 3: Report ID + Status change --}}
          <div class="mac-row">
            <span class="mac-label">Report</span>
            <span class="mac-value">
              @if($activity->report)
                <a href="{{ route('admin.reports.show', $activity->report_id) }}" class="mac-report-link">#{{ $activity->report_id }}</a>
              @else
                <span class="mac-muted">N/A</span>
              @endif
            </span>
          </div>

          {{-- Row 4: Status change --}}
          @if($activity->old_status || $activity->new_status)
          <div class="mac-row">
            <span class="mac-label">Status</span>
            <span class="mac-value mac-status-wrap">
              @if($activity->old_status && $activity->new_status)
                <span class="status {{ strtolower(str_replace(' ', '-', $activity->old_status)) }}">{{ ucfirst($activity->old_status) }}</span>
                <span class="mac-arrow">→</span>
                <span class="status {{ strtolower(str_replace(' ', '-', $activity->new_status)) }}">{{ ucfirst($activity->new_status) }}</span>
              @elseif($activity->new_status)
                <span class="status {{ strtolower(str_replace(' ', '-', $activity->new_status)) }}">{{ ucfirst($activity->new_status) }}</span>
              @endif
            </span>
          </div>
          @endif

          {{-- Row 5: Details/Remarks --}}
          @if($activity->remarks)
          <div class="mac-remarks">
            @if(strlen($activity->remarks) > 80)
              <span id="mshort-{{ $activity->id }}">{{ Str::limit($activity->remarks, 80) }}</span>
              <span id="mfull-{{ $activity->id }}" style="display:none;">{{ $activity->remarks }}</span>
              <button onclick="toggleRemarks('m{{ $activity->id }}')" class="mac-toggle-btn">
                <span id="btn-m{{ $activity->id }}">Read more</span>
              </button>
            @else
              {{ $activity->remarks }}
            @endif
          </div>
          @endif

        </div>
      @empty
        <div class="mobile-empty-state">
          No activity logs found.<br>
          <small>Activities from {{ Auth::user()->department->name ?? 'your department' }} will appear here.</small>
        </div>
      @endforelse
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
    const full  = document.getElementById('full-'  + id);
    const btn   = document.getElementById('btn-'   + id);
    if (short.style.display === 'none') {
      short.style.display = 'inline';
      full.style.display  = 'none';
      btn.textContent     = 'Read more';
    } else {
      short.style.display = 'none';
      full.style.display  = 'inline';
      btn.textContent     = 'Read less';
    }
  }

  /* ── Mobile UFP toggle ── */
  (function () {
    var toggleBtn    = document.getElementById('ufp-toggle-btn');
    var collapsible  = document.getElementById('ufp-collapsible');
    var searchMobile = document.getElementById('ufp-search-mobile');
    var searchHidden = document.getElementById('ufp-search-hidden');
    var mobileForm   = document.getElementById('audit-filter-form-mobile');

    if (toggleBtn && collapsible) {
      toggleBtn.addEventListener('click', function () {
        var expanded = toggleBtn.getAttribute('aria-expanded') === 'true';
        toggleBtn.setAttribute('aria-expanded', String(!expanded));
        collapsible.classList.toggle('collapsed', expanded);
      });
    }

    if (mobileForm && searchMobile && searchHidden) {
      mobileForm.addEventListener('submit', function () {
        searchHidden.value = searchMobile.value;
      });
    }

    var debounce = null;
    if (searchMobile && mobileForm) {
      searchMobile.addEventListener('input', function () {
        clearTimeout(debounce);
        debounce = setTimeout(function () {
          if (searchHidden) searchHidden.value = searchMobile.value;
          mobileForm.submit();
        }, 500);
      });
    }
  })();
</script>
@endpush

@push('styles')
<style>
  /* ── Visibility switches ── */
  .desktop-filter-wrap  { display: block; }
  .mobile-filter-wrap   { display: none;  }
  .desktop-audit-table  { display: block; }
  .mobile-audit-list    { display: none;  }

  /* ================================================================
     MOBILE UFP PANEL
     ================================================================ */
  .ufp-panel {
    border-bottom: 1px solid rgba(255,255,255,0.12);
    margin-bottom: 14px;
  }

  .ufp-mobile-topbar {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 0.875rem;
    width: 100%;
    box-sizing: border-box;
  }

  .ufp-search-wrap {
    flex: 1 1 0%;
    min-width: 0;
    overflow: hidden;
  }

  .ufp-search-input {
    width: 100% !important;
    min-width: 0 !important;
    box-sizing: border-box !important;
    display: block;
    font-size: 16px;
    padding: 0.75rem 1rem;
    background: #ffffff;
    border: 2px solid var(--glass-border);
    border-radius: 0.6rem;
    color: #1f2937;
    font-family: inherit;
  }

  .ufp-search-input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
  }

  .ufp-search-input::placeholder { color: #9ca3af; }

  .ufp-toggle-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.65rem 0.6rem;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.25);
    border-radius: 0.5rem;
    color: #fff;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    flex: 0 0 auto;
  }

  .ufp-toggle-btn[aria-expanded="true"] {
    background: rgba(255,255,255,0.18);
    border-color: rgba(255,255,255,0.4);
  }

  .ufp-active-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    background: #ef4444;
    border-radius: 50%;
    font-size: 0.7rem;
    font-weight: 700;
    color: #fff;
  }

  .ufp-collapsible-body {
    overflow: hidden;
    max-height: 600px;
  }

  .ufp-collapsible-body.collapsed {
    max-height: 0 !important;
  }

  .ufp-inner-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.6rem;
    padding: 0.75rem 0.875rem 0;
  }

  .ufp-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
  }

  .ufp-label {
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: rgba(255,255,255,0.7);
  }

  .ufp-field select,
  .ufp-field input[type="date"] {
    width: 100%;
    min-height: 44px;
    padding: 0.75rem 1rem;
    background: #ffffff;
    border: 2px solid var(--glass-border);
    border-radius: 0.6rem;
    color: #1f2937;
    font-size: 0.9rem;
    font-family: inherit;
    box-sizing: border-box;
  }

  .ufp-field select:focus,
  .ufp-field input[type="date"]:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
  }

  .ufp-actions {
    display: flex;
    gap: 0.75rem;
    padding: 0.75rem 0.875rem 0.875rem;
    align-items: center;
  }

  .ufp-apply-btn {
    flex: 1;
    padding: 0.65rem 1.5rem;
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    text-align: center;
  }
  .ufp-apply-btn:hover { background: #1d4ed8; }

  .ufp-clear-btn {
    flex: 1;
    padding: 0.65rem 1rem;
    background: transparent;
    border: 1px solid rgba(255,255,255,0.25);
    border-radius: 0.5rem;
    color: rgba(255,255,255,0.8);
    font-size: 0.9rem;
    font-weight: 500;
    text-decoration: none;
    text-align: center;
  }
  .ufp-clear-btn:hover { background: rgba(255,255,255,0.08); color: #fff; }

  /* ================================================================
     MOBILE AUDIT CARDS
     ================================================================ */
  .mobile-audit-list {
    padding: 0.5rem 0;
  }

  .mobile-audit-card {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
    padding: 0.875rem 1rem;
    margin-bottom: 0.5rem;
    background: rgba(255,255,255,0.05);
    border-radius: 0.65rem;
    border: 1px solid rgba(255,255,255,0.08);
  }

  /* Top row: date + action badge */
  .mac-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.5rem;
  }

  .mac-date {
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
  }

  .mac-date-main {
    font-size: 0.82rem;
    font-weight: 700;
    color: #fff;
  }

  .mac-date-rel {
    font-size: 0.72rem;
    color: rgba(255,255,255,0.45);
  }

  /* Label/value rows */
  .mac-row {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
    font-size: 0.84rem;
  }

  .mac-label {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: rgba(255,255,255,0.4);
    flex-shrink: 0;
    min-width: 46px;
  }

  .mac-value {
    color: #fff;
    font-weight: 500;
    flex: 1;
  }

  .mac-muted {
    color: rgba(255,255,255,0.4);
    font-weight: 400;
  }

  .mac-you {
    color: #60a5fa;
    font-size: 0.8rem;
    font-weight: 600;
  }

  .mac-report-link {
    color: #60a5fa;
    text-decoration: none;
    font-weight: 600;
  }

  .mac-status-wrap {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    flex-wrap: wrap;
  }

  .mac-arrow {
    color: rgba(255,255,255,0.4);
    font-size: 0.8rem;
  }

  /* Remarks */
  .mac-remarks {
    font-size: 0.82rem;
    color: rgba(255,255,255,0.65);
    line-height: 1.45;
    padding-top: 0.25rem;
    border-top: 1px solid rgba(255,255,255,0.07);
    margin-top: 0.1rem;
  }

  .mac-toggle-btn {
    background: none;
    border: none;
    color: #60a5fa;
    cursor: pointer;
    font-size: 0.8rem;
    padding: 0;
    margin-left: 0.25rem;
  }

  .mobile-empty-state {
    padding: 2rem 1rem;
    text-align: center;
    color: rgba(255,255,255,0.7);
    font-size: 0.95rem;
    line-height: 1.6;
  }

  /* ================================================================
     RESPONSIVE — mobile breakpoint only
     ================================================================ */
  @media (max-width: 768px) {
    .desktop-filter-wrap { display: none;  }
    .mobile-filter-wrap  { display: block; }
    .desktop-audit-table { display: none;  }
    .mobile-audit-list   { display: block; }
  }
</style>
@endpush