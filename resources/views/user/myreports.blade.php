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
                'sort' => $column,
                'direction' => $nextDirection,
            ]));
        };
    @endphp

    <div class="welcome">
        <h1>My Reports</h1>
    </div>

    <div class="recent-reports animate myreports-advanced">
        <h3>Report Management Workspace</h3>

        <form method="GET" action="{{ route('myreports') }}" class="filter-bar" id="myreports-filter-form" aria-label="Filter my reports">
            <div class="filter-row filter-row-main">
                <label for="search-input" class="sr-only">Search reports</label>
                <input
                    type="search"
                    id="search-input"
                    name="search"
                    placeholder="Search by ID, category, location, status"
                    value="{{ $search }}"
                    autocomplete="off"
                    aria-label="Search by ID, category, location, status"
                >

                <label for="category_id" class="sr-only">Category</label>
                <select id="category_id" name="category_id" aria-label="Filter by category">
                    <option value="">All Categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" {{ (int) $categoryId === (int) $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>

                <label for="range" class="sr-only">Date range</label>
                <select id="range" name="range" aria-label="Filter by date range">
                    <option value="">All Dates</option>
                    <option value="7d" {{ $range === '7d' ? 'selected' : '' }}>Last 7 days</option>
                    <option value="30d" {{ $range === '30d' ? 'selected' : '' }}>Last 30 days</option>
                    <option value="custom" {{ $range === 'custom' ? 'selected' : '' }}>Custom range</option>
                </select>
            </div>

            <div class="filter-row filter-row-custom-date {{ $range === 'custom' ? '' : 'hidden' }}" id="custom-date-range">
                <label for="from">From</label>
                <input type="date" id="from" name="from" value="{{ $from }}" max="{{ now()->toDateString() }}">

                <label for="to">To</label>
                <input type="date" id="to" name="to" value="{{ $to }}" max="{{ now()->toDateString() }}">
            </div>

            <div class="filter-row filter-row-status" role="group" aria-label="Filter by status">
                @foreach ($statusFlow as $status)
                    <label class="status-chip status-{{ $statusCssClass($status) }}">
                        <input type="checkbox" name="status[]" value="{{ $status }}" {{ in_array($status, $selectedStatuses, true) ? 'checked' : '' }}>
                        <span>{{ $status }}</span>
                    </label>
                @endforeach
            </div>

            <div class="filter-row filter-row-actions">
                <button type="submit" id="apply-filters-btn">Apply Filters</button>
                <a href="{{ route('myreports') }}" class="btn-secondary">Clear</a>
            </div>

            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">
            <div id="loading-state" class="loading-state hidden">Loading reports...</div>
        </form>

        <div class="table-wrapper">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>
                            <a href="{{ $sortUrl('id') }}" class="sort-link">
                                ID
                                @if ($sort === 'id')
                                    <span class="sort-indicator">{{ strtoupper($direction) }}</span>
                                @endif
                            </a>
                        </th>
                        <th>
                            Category
                        </th>
                        <th>
                            <a href="{{ $sortUrl('incident_date') }}" class="sort-link">
                                Date of Incident
                                @if ($sort === 'incident_date')
                                    <span class="sort-indicator">{{ strtoupper($direction) }}</span>
                                @endif
                            </a>
                        </th>
                        <th>Time of Incident</th>
                        <th>Location</th>
                        <th>
                            <a href="{{ $sortUrl('status') }}" class="sort-link">
                                Status
                                @if ($sort === 'status')
                                    <span class="sort-indicator">{{ strtoupper($direction) }}</span>
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="{{ $sortUrl('submitted_at') }}" class="sort-link">
                                Submitted
                                @if ($sort === 'submitted_at')
                                    <span class="sort-indicator">{{ strtoupper($direction) }}</span>
                                @endif
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($myReports as $report)
                        <tr class="report-row" tabindex="0" data-href="{{ route('report.show', $report->id) }}" aria-label="Open report {{ $report->id }} details">
                            <td>{{ $report->id }}</td>
                            <td>{!! $renderHighlight(strtoupper(optional($report->category)->main_category_code ?: 'N/A')) !!}</td>
                            <td>{{ optional($report->incident_date)->format('M d, Y') }}</td>
                            <td>{{ optional($report->incident_time)->format('h:i A') }}</td>
                            <td>{!! $renderHighlight($report->location) !!}</td>
                            <td>
                                <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">{{ $report->status }}</span>
                            </td>
                            <td>{{ optional($report->submitted_at)->format('M d, Y') ?? optional($report->created_at)->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-state">No reports found. Try changing your search text, filters, or date range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="results-footer">
            <p class="results-summary" aria-live="polite">
                @if ($myReports->count() > 0)
                    Showing {{ $myReports->firstItem() }} to {{ $myReports->lastItem() }} of {{ $myReports->total() }} reports
                @else
                    Showing 0 reports
                @endif
            </p>

            @if ($myReports->hasPages())
                <nav class="simple-pager" aria-label="My Reports pagination">
                    @if ($myReports->onFirstPage())
                        <span class="pager-link disabled">Previous</span>
                    @else
                        <a class="pager-link" href="{{ $myReports->previousPageUrl() }}">Previous</a>
                    @endif

                    <span class="pager-page">Page {{ $myReports->currentPage() }} of {{ $myReports->lastPage() }}</span>

                    @if ($myReports->hasMorePages())
                        <a class="pager-link" href="{{ $myReports->nextPageUrl() }}">Next</a>
                    @else
                        <span class="pager-link disabled">Next</span>
                    @endif
                </nav>
            @endif
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .myreports-advanced .filter-bar {
            display: flex;
            flex-direction: column;
            gap: 0.9rem;
        }

        .myreports-advanced .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
        }

        .myreports-advanced .filter-row-main input,
        .myreports-advanced .filter-row-main select {
            flex: 1 1 220px;
        }

        .myreports-advanced .filter-row-custom-date label {
            color: #fff;
            font-weight: 600;
        }

        .myreports-advanced .filter-row-custom-date input {
            flex: 1 1 150px;
        }

        .myreports-advanced .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.55rem 0.8rem;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            color: #fff;
            background: rgba(255, 255, 255, 0.08);
            cursor: pointer;
        }

        .myreports-advanced .status-chip input {
            width: auto;
            min-width: 0;
            accent-color: #fff;
        }

        .myreports-advanced .status-chip input:checked + span {
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 0.2rem;
        }

        .myreports-advanced .status-chip.status-pending {
            border-color: var(--status-pending-border);
            background: var(--status-pending-bg);
            color: var(--status-pending-color);
        }

        .myreports-advanced .status-chip.status-approved {
            border-color: var(--status-approved-border);
            background: var(--status-approved-bg);
            color: var(--status-approved-color);
        }

        .myreports-advanced .status-chip.status-rejected {
            border-color: var(--status-rejected-border);
            background: var(--status-rejected-bg);
            color: var(--status-rejected-color);
        }

        .myreports-advanced .status-chip.status-under-review {
            border-color: var(--status-review-border);
            background: var(--status-review-bg);
            color: var(--status-review-color);
        }

        .myreports-advanced .status-chip.status-resolved {
            border-color: var(--status-resolved-border);
            background: var(--status-resolved-bg);
            color: var(--status-resolved-color);
        }

        .myreports-advanced .sort-link {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: #fff;
            text-decoration: none;
        }

        .myreports-advanced .sort-link:hover {
            text-decoration: underline;
        }

        .myreports-advanced .sort-indicator {
            font-size: 0.7rem;
            opacity: 0.85;
            letter-spacing: 0.04rem;
        }

        .myreports-advanced mark {
            background: rgba(253, 230, 138, 0.9);
            color: #111827;
            border-radius: 0.2rem;
            padding: 0 0.15rem;
        }

        .myreports-advanced .report-row {
            cursor: pointer;
        }

        .myreports-advanced .report-row:focus-visible {
            outline: 2px solid #93c5fd;
            outline-offset: -2px;
        }

        .myreports-advanced .empty-state {
            text-align: center;
            padding: 2rem;
            color: rgba(255, 255, 255, 0.92);
            font-weight: 500;
        }

        .myreports-advanced .results-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            padding: 1rem 1.5rem 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
        }

        .myreports-advanced .results-summary {
            color: rgba(255, 255, 255, 0.92);
            margin: 0;
            font-size: 0.92rem;
        }

        .myreports-advanced .simple-pager {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .myreports-advanced .pager-link,
        .myreports-advanced .pager-page {
            padding: 0.55rem 0.8rem;
            border-radius: 0.6rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #fff;
            text-decoration: none;
            font-size: 0.9rem;
            background: rgba(255, 255, 255, 0.08);
        }

        .myreports-advanced .pager-link.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .myreports-advanced .hidden {
            display: none;
        }

        .myreports-advanced .loading-state {
            color: #fff;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .myreports-advanced .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            border: 0;
        }

        @media (max-width: 768px) {
            .myreports-advanced .results-footer {
                padding: 1rem;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            const filterForm = document.getElementById('myreports-filter-form');
            const rangeInput = document.getElementById('range');
            const customDateRange = document.getElementById('custom-date-range');
            const applyButton = document.getElementById('apply-filters-btn');
            const loadingState = document.getElementById('loading-state');
            const searchInput = document.getElementById('search-input');
            let debounceTimer = null;

            function updateDateRangeVisibility() {
                if (!rangeInput || !customDateRange) {
                    return;
                }

                if (rangeInput.value === 'custom') {
                    customDateRange.classList.remove('hidden');
                } else {
                    customDateRange.classList.add('hidden');
                }
            }

            function submitWithLoadingState() {
                if (!filterForm) {
                    return;
                }

                if (applyButton) {
                    applyButton.disabled = true;
                    applyButton.textContent = 'Loading...';
                }

                if (loadingState) {
                    loadingState.classList.remove('hidden');
                }

                filterForm.setAttribute('aria-busy', 'true');
                filterForm.submit();
            }

            if (rangeInput) {
                rangeInput.addEventListener('change', updateDateRangeVisibility);
            }

            updateDateRangeVisibility();

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    window.clearTimeout(debounceTimer);
                    debounceTimer = window.setTimeout(submitWithLoadingState, 500);
                });
            }

            if (filterForm) {
                filterForm.addEventListener('submit', function () {
                    if (applyButton) {
                        applyButton.disabled = true;
                        applyButton.textContent = 'Loading...';
                    }

                    if (loadingState) {
                        loadingState.classList.remove('hidden');
                    }

                    filterForm.setAttribute('aria-busy', 'true');
                });
            }

            document.querySelectorAll('.report-row[data-href]').forEach(function (row) {
                row.addEventListener('click', function () {
                    window.location.href = row.dataset.href;
                });

                row.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        window.location.href = row.dataset.href;
                    }
                });
            });
        })();
    </script>
@endpush
