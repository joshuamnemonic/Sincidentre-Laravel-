@extends('layouts.admin')

@section('title', 'Handle Reports - Sincidentre Department Student Discipline Officer')

@section('page-title', 'Handle Reports')

{{-- Search moved into the unified filter panel below --}}

@section('content')

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <section id="handle-reports">

        @php
            $activeHandleFilters = (request()->filled('search') ? 1 : 0)
                + (($selectedStatus ?? '') !== '' ? 1 : 0)
                + (request()->filled('category') ? 1 : 0);
        @endphp

        <div class="handle-filter-panel">

            {{-- Mobile topbar: search + toggle --}}
            <div class="hfp-mobile-topbar">
                <div class="hfp-search-wrap">
                    <input
                        type="search"
                        id="hfp-search-mobile"
                        form="handle-filter-form"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search reports…"
                        autocomplete="off"
                        class="hfp-search-input"
                        aria-label="Search reports"
                    >
                </div>
                <button type="button" id="hfp-toggle-btn" class="hfp-toggle-btn" aria-expanded="false" aria-controls="hfp-collapsible">
                    <span>⚙️</span>
                    <span>Filters</span>
                    @if($activeHandleFilters > 0)
                        <span class="hfp-active-badge">{{ $activeHandleFilters }}</span>
                    @endif
                </button>
            </div>

            <form method="GET" action="{{ route('admin.handlereports') }}" id="handle-filter-form">
                <div id="hfp-collapsible" class="hfp-collapsible-body">
                    <div class="hfp-inner-grid">

                        {{-- Search — desktop only --}}
                        <div class="hfp-field hfp-desktop-only">
                            <label class="hfp-label">Search</label>
                            <input
                                type="search"
                                id="hfp-search-desktop"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Search reports…"
                                autocomplete="off"
                                aria-label="Search reports"
                            >
                        </div>

                        {{-- Status --}}
                        <div class="hfp-field">
                            <label for="status_filter" class="hfp-label">Status</label>
                            <select name="status" id="status_filter">
                                <option value="">All Statuses</option>
                                @if(!(bool) (Auth::user()->is_top_management ?? false))
                                    <option value="pending"      {{ ($selectedStatus ?? '') === 'pending'      ? 'selected' : '' }}>Pending</option>
                                @endif
                                <option value="approved"     {{ ($selectedStatus ?? '') === 'approved'     ? 'selected' : '' }}>Approved</option>
                                <option value="rejected"     {{ ($selectedStatus ?? '') === 'rejected'     ? 'selected' : '' }}>Rejected</option>
                                <option value="under review" {{ ($selectedStatus ?? '') === 'under review' ? 'selected' : '' }}>Under Review</option>
                                <option value="resolved"     {{ ($selectedStatus ?? '') === 'resolved'     ? 'selected' : '' }}>Resolved</option>
                            </select>
                        </div>

                        {{-- Category --}}
                        <div class="hfp-field">
                            <label for="category_filter" class="hfp-label">Category</label>
                            <select name="category" id="category_filter">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    </div>

                    <div class="hfp-actions">
                        <button type="submit" class="hfp-apply-btn">Apply Filters</button>
                        @if($activeHandleFilters > 0)
                            <a href="{{ route('admin.handlereports') }}" class="hfp-clear-btn">Clear All</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- Desktop table --}}
        <div class="table-wrapper desktop-handle-table">
            <table class="handle-report-table reports-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Reporter</th>
                        <th>Category</th>
                        <th>Date Submitted</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($approvedReports as $report)
                        <tr>
                            <td>{{ $report->id }}</td>
                            <td>{{ $report->user->name ?? 'Unknown' }}</td>
                            <td>{{ strtoupper($report->category->main_category_code ?? 'N/A') }}</td>
                            <td>{{ $report->created_at->format('M d, Y') }}</td>
                            <td>
                                <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                                    {{ \App\Models\Report::labelForStatus($report->status) }}
                                </span>
                            </td>
                            <td>{{ $report->assigned_to ?? 'Unassigned' }}</td>
                            <td>
                                @if(strtolower((string) $report->status) === strtolower(\App\Models\Report::STATUS_RESOLVED))
                                    <a href="{{ route('admin.handlereports.show', $report->id) }}" class="btn-view">View</a>
                                @else
                                    <a href="{{ route('admin.handlereports.show', $report->id) }}" class="btn-handle">Handle</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;">
                                @if(($selectedStatus ?? '') !== '')
                                    No {{ ucwords(str_replace('_', ' ', $selectedStatus)) }} reports found.
                                @else
                                    No reports found.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="mobile-handle-list">
            @forelse($approvedReports as $report)
                @php
                    $isResolved = strtolower((string) $report->status) === strtolower(\App\Models\Report::STATUS_RESOLVED);
                    $actionUrl  = route('admin.handlereports.show', $report->id);
                @endphp
                <div class="mobile-handle-card">
                    <div class="mhc-top">
                        <div class="mhc-left">
                            <span class="mhc-id">#{{ $report->id }}</span>
                            <span class="mhc-cat">{{ strtoupper($report->category->main_category_code ?? 'N/A') }}</span>
                        </div>
                        <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                            {{ \App\Models\Report::labelForStatus($report->status) }}
                        </span>
                    </div>
                    <div class="mhc-reporter">👤 {{ $report->user->name ?? 'Unknown' }}</div>
                    <div class="mhc-bottom">
                        <span class="mhc-meta">🗓 {{ $report->created_at->format('M d, Y') }}</span>
                        @if($report->assigned_to)
                            <span class="mhc-meta">📌 {{ $report->assigned_to }}</span>
                        @endif
                    </div>
                    <div class="mhc-actions">
                        <a href="{{ $actionUrl }}" class="{{ $isResolved ? 'btn-view' : 'btn-handle' }} mhc-btn">
                            {{ $isResolved ? 'View' : 'Handle' }}
                        </a>
                    </div>
                </div>
            @empty
                <div class="mobile-empty-state">
                    @if(($selectedStatus ?? '') !== '')
                        No {{ ucwords(str_replace('_', ' ', $selectedStatus)) }} reports found.
                    @else
                        No reports found.
                    @endif
                </div>
            @endforelse
        </div>

        {{-- Escalated Reports (DSDO only) --}}
        @if(!(bool) (Auth::user()->is_top_management ?? false))
            <div class="escalated-view-only-section">
                <h2>Escalated Reports <span class="escalated-badge">View Only</span></h2>
                <p class="escalated-subtitle">These reports are escalated and can only be viewed here. Handling actions are disabled.</p>

                <div class="table-wrapper desktop-handle-table">
                    <table class="handle-report-table reports-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Reporter</th>
                                <th>Category</th>
                                <th>Date Submitted</th>
                                <th>Status</th>
                                <th>Escalated At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($escalatedReports as $report)
                                <tr>
                                    <td>{{ $report->id }}</td>
                                    <td>{{ $report->user->name ?? 'Unknown' }}</td>
                                    <td>{{ strtoupper($report->category->main_category_code ?? 'N/A') }}</td>
                                    <td>{{ $report->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                                            {{ \App\Models\Report::labelForStatus($report->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $report->escalated_at ? $report->escalated_at->format('M d, Y h:i A') : 'N/A' }}</td>
                                    <td>
                                        <a href="{{ route('admin.reports.show', $report->id) }}" class="btn-view">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align:center;">No escalated reports found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mobile-handle-list">
                    @forelse($escalatedReports as $report)
                        <div class="mobile-handle-card escalated">
                            <div class="mhc-top">
                                <div class="mhc-left">
                                    <span class="mhc-id">#{{ $report->id }}</span>
                                    <span class="mhc-cat">{{ strtoupper($report->category->main_category_code ?? 'N/A') }}</span>
                                </div>
                                <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                                    {{ \App\Models\Report::labelForStatus($report->status) }}
                                </span>
                            </div>
                            <div class="mhc-reporter">👤 {{ $report->user->name ?? 'Unknown' }}</div>
                            <div class="mhc-bottom">
                                <span class="mhc-meta">🗓 {{ $report->created_at->format('M d, Y') }}</span>
                                @if($report->escalated_at)
                                    <span class="mhc-meta">⚠️ Escalated {{ $report->escalated_at->format('M d, Y') }}</span>
                                @endif
                            </div>
                            <div class="mhc-actions">
                                <a href="{{ route('admin.reports.show', $report->id) }}" class="btn-view mhc-btn">View</a>
                            </div>
                        </div>
                    @empty
                        <div class="mobile-empty-state">No escalated reports found.</div>
                    @endforelse
                </div>

            </div>
        @endif

    </section>

@endsection

@push('styles')
<style>
    /* ================================================================
       UNIFIED FILTER PANEL
       ================================================================ */
    .handle-filter-panel {
        border-bottom: 1px solid rgba(255,255,255,0.12);
    }

    /* Mobile topbar */
    .hfp-mobile-topbar {
        display: none;
        flex-direction: row;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 0.875rem;
        width: 100%;
        box-sizing: border-box;
    }

    .hfp-search-wrap {
        flex: 1 1 0%;
        min-width: 0;
        overflow: hidden;
    }

    /* ── FIXED: search input now styled like all other UFP search inputs ── */
    .hfp-search-input {
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

    .hfp-search-input:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
    }

    .hfp-search-input::placeholder { color: #9ca3af; }

    .hfp-toggle-btn {
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

    .hfp-toggle-btn[aria-expanded="true"] {
        background: rgba(255,255,255,0.18);
        border-color: rgba(255,255,255,0.4);
    }

    .hfp-active-badge {
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
        line-height: 1;
    }

    .hfp-collapsible-body { overflow: hidden; }
    .hfp-collapsible-body.collapsed { max-height: 0 !important; }

    .hfp-inner-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 0.75rem;
        padding: 1rem 1.25rem 0;
    }

    .hfp-field {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }

    .hfp-label {
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: rgba(255,255,255,0.7);
    }

    /* ── FIXED: field inputs and selects now styled consistently ── */
    .hfp-field input,
    .hfp-field select {
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

    .hfp-field input:focus,
    .hfp-field select:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
    }

    .hfp-field input::placeholder { color: #9ca3af; }

    .hfp-desktop-only { display: flex; }

    .hfp-actions {
        display: flex;
        gap: 0.75rem;
        padding: 0.875rem 1.25rem 1rem;
        align-items: center;
    }

    .hfp-apply-btn {
        padding: 0.65rem 1.5rem;
        background: #2563eb;
        color: #fff;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        flex: 0 0 auto;
    }

    .hfp-apply-btn:hover { background: #1d4ed8; }

    .hfp-clear-btn {
        padding: 0.65rem 1rem;
        background: transparent;
        border: 1px solid rgba(255,255,255,0.25);
        border-radius: 0.5rem;
        color: rgba(255,255,255,0.8);
        font-size: 0.9rem;
        font-weight: 500;
        text-decoration: none;
        flex: 0 0 auto;
    }

    .hfp-clear-btn:hover {
        background: rgba(255,255,255,0.08);
        color: #fff;
    }

    /* ── Escalated section ── */
    .escalated-view-only-section { margin-top: 28px; }

    .escalated-subtitle {
        padding: 0 2rem 0.75rem;
        color: rgba(255,255,255,0.75);
        font-size: 0.88rem;
        margin: 0;
    }

    .escalated-badge {
        display: inline-block;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background: rgba(251,191,36,0.2);
        border: 1px solid rgba(251,191,36,0.4);
        color: #fde68a;
        padding: 0.15rem 0.5rem;
        border-radius: 2rem;
        vertical-align: middle;
        margin-left: 0.5rem;
    }

    .desktop-handle-table { display: block; }
    .mobile-handle-list   { display: none; }

    .mobile-handle-card {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        padding: 0.875rem 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .mobile-handle-card:last-child { border-bottom: none; }

    .mobile-handle-card.escalated {
        border-left: 3px solid rgba(251,191,36,0.5);
    }

    .mhc-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }

    .mhc-left {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .mhc-id {
        font-size: 0.75rem;
        font-weight: 700;
        color: rgba(255,255,255,0.5);
    }

    .mhc-cat {
        font-size: 0.78rem;
        font-weight: 700;
        color: #93c5fd;
        background: rgba(96,165,250,0.15);
        border: 1px solid rgba(96,165,250,0.25);
        border-radius: 0.35rem;
        padding: 0.1rem 0.4rem;
    }

    .mhc-reporter {
        font-size: 0.9rem;
        font-weight: 600;
        color: #fff;
    }

    .mhc-bottom {
        display: flex;
        flex-wrap: wrap;
        gap: 0.3rem 0.875rem;
    }

    .mhc-meta {
        font-size: 0.78rem;
        color: rgba(255,255,255,0.6);
    }

    .mhc-actions { margin-top: 0.35rem; }

    .mhc-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 36px;
        padding: 0 1rem;
        font-size: 0.85rem;
        border-radius: 999px;
        text-decoration: none;
    }

    .mobile-empty-state {
        padding: 2rem 1rem;
        text-align: center;
        color: rgba(255,255,255,0.7);
        font-size: 0.95rem;
    }

    @media (max-width: 768px) {
        .hfp-mobile-topbar { display: flex; }
        .hfp-desktop-only  { display: none !important; }

        .hfp-collapsible-body {
            max-height: 0;
            overflow: hidden;
        }

        .hfp-collapsible-body:not(.collapsed) {
            max-height: 400px;
            overflow: visible;
        }

        .hfp-inner-grid {
            grid-template-columns: 1fr;
            padding: 0.75rem 0.875rem 0;
            gap: 0.6rem;
        }

        .hfp-actions { padding: 0.75rem 0.875rem 0.875rem; }

        .hfp-apply-btn,
        .hfp-clear-btn {
            flex: 1;
            text-align: center;
            justify-content: center;
        }

        .desktop-handle-table { display: none; }
        .mobile-handle-list   { display: block; }

        .escalated-subtitle { padding: 0 1rem 0.75rem; }
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var toggleBtn     = document.getElementById('hfp-toggle-btn');
    var collapsible   = document.getElementById('hfp-collapsible');
    var searchMobile  = document.getElementById('hfp-search-mobile');
    var searchDesktop = document.getElementById('hfp-search-desktop');

    if (toggleBtn && collapsible) {
        toggleBtn.addEventListener('click', function () {
            var expanded = toggleBtn.getAttribute('aria-expanded') === 'true';
            toggleBtn.setAttribute('aria-expanded', String(!expanded));
            collapsible.classList.toggle('collapsed', expanded);
        });

        function syncDesktop() {
            if (window.innerWidth > 768) {
                collapsible.classList.remove('collapsed');
            } else {
                if (toggleBtn.getAttribute('aria-expanded') !== 'true') {
                    collapsible.classList.add('collapsed');
                }
            }
        }

        syncDesktop();
        window.addEventListener('resize', syncDesktop);
    }

    var filterForm = document.getElementById('handle-filter-form');
    if (filterForm && searchMobile && searchDesktop) {
        filterForm.addEventListener('submit', function () {
            searchDesktop.value = searchMobile.value;
        });
    }

    var debounce = null;
    if (searchMobile && filterForm) {
        searchMobile.addEventListener('input', function () {
            clearTimeout(debounce);
            debounce = setTimeout(function () {
                if (searchDesktop) searchDesktop.value = searchMobile.value;
                filterForm.submit();
            }, 500);
        });
    }
})();
</script>
@endpush
