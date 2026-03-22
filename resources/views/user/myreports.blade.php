@extends('layouts.app')

@section('title', 'My Reports - Sincidentre')

@section('content')
    @php
        $statusFlow = [
            \App\Models\Report::STATUS_PENDING,
            \App\Models\Report::STATUS_APPROVED,
            \App\Models\Report::STATUS_REJECTED,
            \App\Models\Report::STATUS_UNDER_REVIEW,
            \App\Models\Report::STATUS_RESOLVED,
        ];

        $searchTerm = trim((string) ($search ?? ''));
        $selectedStatuses = $selectedStatuses ?? [];
        $queryWithoutPage = request()->except('page');

        $statusCssClass = function (string $status) {
            return strtolower(str_replace(' ', '-', $status));
        };

        $renderHighlight = function (?string $value) use ($searchTerm) {
            $safeValue = e((string) $value);
            if ($searchTerm === '') {
                return new \Illuminate\Support\HtmlString($safeValue);
            }
            $pattern = '/' . preg_quote($searchTerm, '/') . '/i';
            $highlighted = preg_replace($pattern, '<mark>$0</mark>', $safeValue);
            return new \Illuminate\Support\HtmlString($highlighted ?? $safeValue);
        };

        $sortUrl = function (string $column) use ($sort, $direction, $queryWithoutPage) {
            $nextDirection = ($sort === $column && $direction === 'asc') ? 'desc' : 'asc';
            return route('myreports', array_merge($queryWithoutPage, [
                'sort'      => $column,
                'direction' => $nextDirection,
            ]));
        };

        $activeFilterCount = 0;
        if (!empty($search))      $activeFilterCount++;
        if (!empty($categoryId))  $activeFilterCount++;
        if (!empty($range))       $activeFilterCount++;
        if (!empty($selectedStatuses)) $activeFilterCount++;
    @endphp

    <div class="welcome">
        <h1>My Reports</h1>
    </div>

    <div class="recent-reports animate myreports-advanced">
        <h3>Report Management Workspace</h3>

        {{-- ================================================================
             FILTER FORM
             On mobile: collapsed by default behind a toggle button.
             On desktop: always visible.
             ================================================================ --}}
        <div class="filter-panel">

            {{-- Mobile toggle row ──────────────────────────────── --}}
            <div class="filter-mobile-topbar">
                {{-- Search always visible --}}
                <div class="filter-mobile-search">
                    <label for="search-input-mobile" class="sr-only">Search reports</label>
                    <input
                        type="search"
                        id="search-input-mobile"
                        name="search"
                        form="myreports-filter-form"
                        placeholder="Search reports…"
                        value="{{ $search }}"
                        autocomplete="off"
                        class="filter-search-input"
                    >
                </div>

                {{-- Filter toggle button --}}
                <button type="button" id="filter-toggle-btn" class="filter-toggle-btn" aria-expanded="false" aria-controls="filter-collapsible">
                    <span class="filter-toggle-icon">⚙️</span>
                    <span class="filter-toggle-label">Filters</span>
                    @if($activeFilterCount > 0)
                        <span class="filter-active-badge">{{ $activeFilterCount }}</span>
                    @endif
                </button>
            </div>

            {{-- Collapsible filter body ─────────────────────────── --}}
            <form method="GET" action="{{ route('myreports') }}" id="myreports-filter-form" aria-label="Filter my reports">

                <div id="filter-collapsible" class="filter-collapsible-body" aria-hidden="true">

                    <div class="filter-inner-grid">

                        {{-- Search (desktop — hidden on mobile since we have the topbar one) --}}
                        <div class="filter-field filter-field-search desktop-only">
                            <label for="search-input" class="filter-label">Search</label>
                            <input
                                type="search"
                                id="search-input"
                                name="search"
                                placeholder="Search by ID, category, location, status"
                                value="{{ $search }}"
                                autocomplete="off"
                                aria-label="Search by ID, category, location, status"
                            >
                        </div>

                        {{-- Category --}}
                        <div class="filter-field">
                            <label for="category_id" class="filter-label">Category</label>
                            <select id="category_id" name="category_id" aria-label="Filter by category">
                                <option value="">All Categories</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" {{ (int) $categoryId === (int) $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Date range --}}
                        <div class="filter-field">
                            <label for="range" class="filter-label">Date Range</label>
                            <select id="range" name="range" aria-label="Filter by date range">
                                <option value="">All Dates</option>
                                <option value="7d"     {{ $range === '7d'     ? 'selected' : '' }}>Last 7 days</option>
                                <option value="30d"    {{ $range === '30d'    ? 'selected' : '' }}>Last 30 days</option>
                                <option value="custom" {{ $range === 'custom' ? 'selected' : '' }}>Custom range</option>
                            </select>
                        </div>

                        {{-- Status --}}
                        <div class="filter-field">
                            <label for="status-filter" class="filter-label">Status</label>
                            <select name="status" id="status-filter" class="status-dropdown" aria-label="Filter by status">
                                <option value="">All Statuses</option>
                                @foreach ($statusFlow as $status)
                                    <option value="{{ $status }}" {{ in_array($status, $selectedStatuses, true) ? 'selected' : '' }}>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Custom date inputs --}}
                        <div class="filter-field filter-field-from {{ $range === 'custom' ? '' : 'hidden' }}" id="custom-date-range-from">
                            <label for="from" class="filter-label">From</label>
                            <input type="date" id="from" name="from" value="{{ $from }}" max="{{ now()->toDateString() }}">
                        </div>

                        <div class="filter-field filter-field-to {{ $range === 'custom' ? '' : 'hidden' }}" id="custom-date-range-to">
                            <label for="to" class="filter-label">To</label>
                            <input type="date" id="to" name="to" value="{{ $to }}" max="{{ now()->toDateString() }}">
                        </div>

                    </div>

                    {{-- Filter actions --}}
                    <div class="filter-actions">
                        <button type="submit" id="apply-filters-btn" class="filter-apply-btn">Apply Filters</button>
                        <a href="{{ route('myreports') }}" class="filter-clear-btn">Clear All</a>
                    </div>

                </div>

                <input type="hidden" name="sort"      value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">
                <div id="loading-state" class="loading-state hidden">Loading reports…</div>

            </form>
        </div>

        {{-- ================================================================
             REPORT LIST
             Desktop: standard table.
             Mobile: compact cards (2 lines max per report).
             ================================================================ --}}

        {{-- Desktop table ───────────────────────────────────────── --}}
        <div class="table-wrapper desktop-table">
            <table class="report-table">
                <thead>
                    <tr>
                        <th><a href="{{ $sortUrl('id') }}" class="sort-link">ID @if($sort==='id')<span class="sort-indicator">{{ strtoupper($direction) }}</span>@endif</a></th>
                        <th>Category</th>
                        <th><a href="{{ $sortUrl('incident_date') }}" class="sort-link">Date of Incident @if($sort==='incident_date')<span class="sort-indicator">{{ strtoupper($direction) }}</span>@endif</a></th>
                        <th>Time of Incident</th>
                        <th>Location</th>
                        <th>Specify</th>
                        <th><a href="{{ $sortUrl('status') }}" class="sort-link">Status @if($sort==='status')<span class="sort-indicator">{{ strtoupper($direction) }}</span>@endif</a></th>
                        <th><a href="{{ $sortUrl('submitted_at') }}" class="sort-link">Submitted @if($sort==='submitted_at')<span class="sort-indicator">{{ strtoupper($direction) }}</span>@endif</a></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($myReports as $report)
                        <tr class="report-row" tabindex="0"
                            data-href="{{ route('report.show', $report->id) }}"
                            aria-label="Open report {{ $report->id }}">
                            <td>{{ $report->id }}</td>
                            <td>{!! $renderHighlight(strtoupper(optional($report->category)->main_category_code ?: 'N/A')) !!}</td>
                            <td>{{ optional($report->incident_date)->format('M d, Y') }}</td>
                            <td>{{ optional($report->incident_time)->format('h:i A') }}</td>
                            <td>{!! $renderHighlight($report->location) !!}</td>
                            <td>{!! $renderHighlight($report->location_details ?? 'N/A') !!}</td>
                            <td><span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">{{ $report->status }}</span></td>
                            <td>{{ optional($report->submitted_at)->format('M d, Y') ?? optional($report->created_at)->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-state">No reports found. Try changing your search text, filters, or date range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile cards ─────────────────────────────────────────── --}}
        <div class="mobile-report-list">
            @forelse ($myReports as $report)
                <a href="{{ route('report.show', $report->id) }}" class="mobile-report-card">
                    <div class="mrc-top">
                        <div class="mrc-id">#{{ $report->id }}</div>
                        <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">{{ $report->status }}</span>
                    </div>
                    <div class="mrc-category">
                        {!! $renderHighlight(
                            strtoupper(optional($report->category)->main_category_code ?: '?')
                            . ' — '
                            . (optional($report->category)->main_category_name ?: 'Unknown Category')
                        ) !!}
                    </div>
                    <div class="mrc-bottom">
                        <span class="mrc-meta">📍 {!! $renderHighlight($report->location) !!}</span>
                        <span class="mrc-meta">🗓 {{ optional($report->incident_date)->format('M d, Y') }}</span>
                    </div>
                </a>
            @empty
                <div class="mobile-empty-state">
                    No reports found. Try changing your search or filters.
                </div>
            @endforelse
        </div>

        {{-- ================================================================
             PAGINATION FOOTER
             ================================================================ --}}
        <div class="results-footer">
            <p class="results-summary" aria-live="polite">
                @if ($myReports->count() > 0)
                    Showing {{ $myReports->firstItem() }}–{{ $myReports->lastItem() }} of {{ $myReports->total() }} reports
                @else
                    Showing 0 reports
                @endif
            </p>

            @if ($myReports->hasPages())
                <nav class="simple-pager" aria-label="My Reports pagination">
                    @if ($myReports->onFirstPage())
                        <span class="pager-link disabled">← Prev</span>
                    @else
                        <a class="pager-link" href="{{ $myReports->previousPageUrl() }}">← Prev</a>
                    @endif

                    <span class="pager-page">{{ $myReports->currentPage() }} / {{ $myReports->lastPage() }}</span>

                    @if ($myReports->hasMorePages())
                        <a class="pager-link" href="{{ $myReports->nextPageUrl() }}">Next →</a>
                    @else
                        <span class="pager-link disabled">Next →</span>
                    @endif
                </nav>
            @endif
        </div>

    </div>
@endsection

@push('styles')
<style>
/* ================================================================
   FILTER PANEL
   ================================================================ */
.filter-panel {
    border-bottom: 1px solid rgba(255,255,255,0.12);
}

/* Mobile topbar — search + toggle button */
.filter-mobile-topbar {
    display: none;
    flex-direction: row;
    align-items: center;
    gap: 0.6rem;
    padding: 0.875rem 1rem;
    width: 100%;
    box-sizing: border-box;
    overflow: hidden;
}

.filter-mobile-search {
    flex: 1 1 0%;
    min-width: 0;
    overflow: hidden;
}

.filter-search-input {
    width: 100% !important;
    min-width: 0 !important;
    box-sizing: border-box !important;
    display: block;
}

.filter-toggle-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.65rem 0.75rem;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.25);
    border-radius: 0.5rem;
    color: #fff;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    flex: 0 0 auto;
}

.filter-toggle-btn[aria-expanded="true"] {
    background: rgba(255,255,255,0.18);
    border-color: rgba(255,255,255,0.4);
}

.filter-active-badge {
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

/* Collapsible body */
.filter-collapsible-body {
    overflow: hidden;
}

.filter-collapsible-body.collapsed {
    max-height: 0 !important;
}

/* Inner grid of filter fields */
.filter-inner-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.75rem;
    padding: 1rem 1.25rem 0;
}

.filter-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.filter-label {
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: rgba(255,255,255,0.7);
}

.filter-field input,
.filter-field select {
    width: 100%;
}

.filter-field.hidden {
    display: none;
}

/* Filter actions row */
.filter-actions {
    display: flex;
    gap: 0.75rem;
    padding: 0.875rem 1.25rem 1rem;
    align-items: center;
}

.filter-apply-btn {
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

.filter-apply-btn:hover { background: #1d4ed8; }

.filter-clear-btn {
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

.filter-clear-btn:hover {
    background: rgba(255,255,255,0.08);
    color: #fff;
}

/* ================================================================
   DESKTOP TABLE (unchanged behaviour)
   ================================================================ */
.desktop-table { display: block; }
.mobile-report-list { display: none; }

.myreports-advanced .sort-link {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    color: #fff;
    text-decoration: none;
}

.myreports-advanced .sort-link:hover { text-decoration: underline; }

.myreports-advanced .sort-indicator {
    font-size: 0.7rem;
    opacity: 0.85;
}

.myreports-advanced mark {
    background: rgba(253,230,138,0.9);
    color: #111827;
    border-radius: 0.2rem;
    padding: 0 0.15rem;
}

.myreports-advanced .report-row { cursor: pointer; }

.myreports-advanced .report-row:focus-visible {
    outline: 2px solid #93c5fd;
    outline-offset: -2px;
}

.myreports-advanced .empty-state {
    text-align: center;
    padding: 2rem;
    color: rgba(255,255,255,0.92);
    font-weight: 500;
}

/* ================================================================
   MOBILE REPORT CARDS
   ================================================================ */
.mobile-report-card {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    padding: 0.875rem 1rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    text-decoration: none;
    color: inherit;
    transition: background 0.15s;
}

.mobile-report-card:last-child { border-bottom: none; }

.mobile-report-card:active,
.mobile-report-card:hover {
    background: rgba(255,255,255,0.06);
}

.mrc-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.mrc-id {
    font-size: 0.78rem;
    font-weight: 700;
    color: rgba(255,255,255,0.55);
    letter-spacing: 0.3px;
}

.mrc-category {
    font-size: 0.92rem;
    font-weight: 600;
    color: #fff;
    line-height: 1.3;
}

.mrc-bottom {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem 1rem;
    margin-top: 0.1rem;
}

.mrc-meta {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.6);
}

.mobile-empty-state {
    padding: 2rem 1rem;
    text-align: center;
    color: rgba(255,255,255,0.7);
    font-size: 0.95rem;
}

/* ================================================================
   RESULTS FOOTER & PAGINATION
   ================================================================ */
.myreports-advanced .results-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
    padding: 1rem 1.5rem 1.25rem;
    border-top: 1px solid rgba(255,255,255,0.15);
}

.myreports-advanced .results-summary {
    color: rgba(255,255,255,0.75);
    margin: 0;
    font-size: 0.88rem;
}

.myreports-advanced .simple-pager {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}

.myreports-advanced .pager-link,
.myreports-advanced .pager-page {
    padding: 0.45rem 0.75rem;
    border-radius: 0.5rem;
    border: 1px solid rgba(255,255,255,0.25);
    color: #fff;
    text-decoration: none;
    font-size: 0.85rem;
    background: rgba(255,255,255,0.08);
}

.myreports-advanced .pager-link.disabled {
    opacity: 0.4;
    cursor: not-allowed;
    pointer-events: none;
}

.myreports-advanced .hidden { display: none !important; }

.myreports-advanced .loading-state {
    padding: 0 1.25rem 0.75rem;
    color: rgba(255,255,255,0.8);
    font-size: 0.88rem;
    font-weight: 600;
}

.myreports-advanced .sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0,0,0,0);
    border: 0;
}

/* ================================================================
   RESPONSIVE
   ================================================================ */
@media (max-width: 768px) {
    /* Show mobile topbar, hide desktop search inside collapsible */
    .filter-mobile-topbar { display: flex; }
    .desktop-only { display: none !important; }

    /* Collapsible filter body: collapsed by default on mobile */
    .filter-collapsible-body {
        max-height: 0;
        overflow: hidden;
    }

    .filter-collapsible-body:not(.collapsed) {
        max-height: 600px; /* enough for all filters */
        overflow: visible;
    }

    /* Filters stack to single column on mobile */
    .filter-inner-grid {
        grid-template-columns: 1fr;
        padding: 0.75rem 0.875rem 0;
        gap: 0.6rem;
    }

    .filter-actions {
        padding: 0.75rem 0.875rem 0.875rem;
    }

    .filter-apply-btn,
    .filter-clear-btn {
        flex: 1;
        text-align: center;
        justify-content: center;
    }

    /* Hide desktop table, show mobile cards */
    .desktop-table { display: none; }
    .mobile-report-list { display: block; }

    /* Compact pagination */
    .myreports-advanced .results-footer {
        padding: 0.875rem 1rem;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}

@media (max-width: 480px) {
    .filter-mobile-topbar {
        padding: 0.75rem 0.875rem;
    }

    .mrc-category { font-size: 0.88rem; }
    .mrc-meta     { font-size: 0.76rem; }

    .myreports-advanced .results-footer {
        padding: 0.75rem 0.875rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    /* ── Filter toggle (mobile) ── */
    const toggleBtn        = document.getElementById('filter-toggle-btn');
    const collapsibleBody  = document.getElementById('filter-collapsible');

    if (toggleBtn && collapsibleBody) {
        toggleBtn.addEventListener('click', function () {
            const isExpanded = toggleBtn.getAttribute('aria-expanded') === 'true';
            toggleBtn.setAttribute('aria-expanded', String(!isExpanded));
            collapsibleBody.setAttribute('aria-hidden', String(isExpanded));
            collapsibleBody.classList.toggle('collapsed', isExpanded);
        });

        /* On desktop always show filter body */
        function syncDesktop() {
            if (window.innerWidth > 768) {
                collapsibleBody.classList.remove('collapsed');
                collapsibleBody.removeAttribute('aria-hidden');
            } else {
                /* On mobile, keep current state unless expanded */
                if (toggleBtn.getAttribute('aria-expanded') !== 'true') {
                    collapsibleBody.classList.add('collapsed');
                }
            }
        }

        syncDesktop();
        window.addEventListener('resize', syncDesktop);
    }

    /* ── Custom date range visibility ── */
    const rangeInput     = document.getElementById('range');
    const fromWrapper    = document.getElementById('custom-date-range-from');
    const toWrapper      = document.getElementById('custom-date-range-to');

    function updateDateRangeVisibility() {
        if (!rangeInput) return;
        const isCustom = rangeInput.value === 'custom';
        fromWrapper?.classList.toggle('hidden', !isCustom);
        toWrapper?.classList.toggle('hidden', !isCustom);
    }

    if (rangeInput) {
        rangeInput.addEventListener('change', updateDateRangeVisibility);
        updateDateRangeVisibility();
    }

    /* ── Loading state ── */
    const filterForm  = document.getElementById('myreports-filter-form');
    const applyBtn    = document.getElementById('apply-filters-btn');
    const loadingEl   = document.getElementById('loading-state');
    const searchMobile = document.getElementById('search-input-mobile');
    const searchDesktop = document.getElementById('search-input');
    let debounceTimer = null;

    function showLoading() {
        if (applyBtn)   { applyBtn.disabled = true; applyBtn.textContent = 'Loading…'; }
        if (loadingEl)  { loadingEl.classList.remove('hidden'); }
        if (filterForm) { filterForm.setAttribute('aria-busy', 'true'); }
    }

    /* Sync mobile search value into the hidden desktop input before submit */
    function syncSearchAndSubmit() {
        if (searchDesktop && searchMobile) {
            searchDesktop.value = searchMobile.value;
        }
        showLoading();
        filterForm?.submit();
    }

    /* Debounced search on mobile input */
    if (searchMobile) {
        searchMobile.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(syncSearchAndSubmit, 500);
        });
    }

    /* Debounced search on desktop input */
    if (searchDesktop) {
        searchDesktop.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                showLoading();
                filterForm?.submit();
            }, 500);
        });
    }

    if (filterForm) {
        filterForm.addEventListener('submit', showLoading);
    }

    /* ── Clickable table rows (desktop) ── */
    document.querySelectorAll('.report-row[data-href]').forEach(function (row) {
        row.addEventListener('click', function () {
            window.location.href = row.dataset.href;
        });
        row.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                window.location.href = row.dataset.href;
            }
        });
    });
})();
</script>
@endpush