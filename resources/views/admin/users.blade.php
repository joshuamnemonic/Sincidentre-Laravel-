@extends('layouts.admin')

@section('title', 'User Management - Sincidentre Department Student Discipline Officer')

@section('page-title', 'User Management')

@section('content')

    <p>Manage all registered users of Sincidentre.</p>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- ── Stats ── --}}
    <div class="stats-grid users-stats-grid">
        <div class="stat-card">
            <h4>{{ $totalUsersTitle ?? 'Total Users' }}</h4>
            <p class="stat-number">{{ $totalUsers }}</p>
        </div>
        <div class="stat-card">
            <h4>Active</h4>
            <p class="stat-number" style="color:#28a745;">{{ $activeUsers }}</p>
        </div>
        <div class="stat-card">
            <h4>Suspended</h4>
            <p class="stat-number" style="color:#ffc107;">{{ $suspendedUsers }}</p>
        </div>
        <div class="stat-card">
            <h4>Deactivated</h4>
            <p class="stat-number" style="color:#dc3545;">{{ $deactivatedUsers }}</p>
        </div>
    </div>

    <section>
        <h2>Registered Users</h2>

        {{-- ================================================================
             UNIFIED FILTER PANEL
             ================================================================ --}}
        @php
            $activeUserFilters = (request()->filled('search') ? 1 : 0)
                + (request()->filled('status') ? 1 : 0)
                + (request()->filled('department') ? 1 : 0);
        @endphp

        <div class="ufp-panel">

            {{-- Mobile topbar --}}
            <div class="ufp-mobile-topbar">
                <div class="ufp-search-wrap">
                    <input
                        type="search"
                        id="ufp-search-mobile"
                        form="user-filter-form"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search users…"
                        autocomplete="off"
                        class="ufp-search-input"
                        aria-label="Search users"
                    >
                </div>
                <button type="button" id="ufp-toggle-btn" class="ufp-toggle-btn" aria-expanded="false" aria-controls="ufp-collapsible">
                    <span>⚙️</span>
                    <span>Filters</span>
                    @if($activeUserFilters > 0)
                        <span class="ufp-active-badge">{{ $activeUserFilters }}</span>
                    @endif
                </button>
            </div>

            <form method="GET" action="{{ route('admin.users') }}" id="user-filter-form">
                <div id="ufp-collapsible" class="ufp-collapsible-body">
                    <div class="ufp-inner-grid">

                        {{-- Search — desktop only --}}
                        <div class="ufp-field ufp-desktop-only">
                            <label class="ufp-label">Search</label>
                            <input
                                type="search"
                                id="ufp-search-desktop"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Search users…"
                                autocomplete="off"
                            >
                        </div>

                        {{-- Status --}}
                        <div class="ufp-field">
                            <label for="ufp-status" class="ufp-label">Status</label>
                            <select name="status" id="ufp-status">
                                <option value="">All Statuses</option>
                                <option value="active"      {{ request('status') === 'active'      ? 'selected' : '' }}>Active</option>
                                <option value="suspended"   {{ request('status') === 'suspended'   ? 'selected' : '' }}>Suspended</option>
                                <option value="deactivated" {{ request('status') === 'deactivated' ? 'selected' : '' }}>Deactivated</option>
                            </select>
                        </div>

                        {{-- Department (conditional) --}}
                        @if(($canFilterDepartment ?? false) === true)
                        <div class="ufp-field">
                            <label for="ufp-department" class="ufp-label">Department</label>
                            <select name="department" id="ufp-department">
                                <option value="">All Departments</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                    </div>

                    <div class="ufp-actions">
                        <button type="submit" class="ufp-apply-btn">Apply Filters</button>
                        @if($activeUserFilters > 0)
                            <a href="{{ route('admin.users') }}" class="ufp-clear-btn">Clear All</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- ================================================================
             DESKTOP TABLE
             ================================================================ --}}
        <div class="table-wrapper desktop-users-table">
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Reports</th>
                        <th>Registered</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php
                            $registrantType = strtolower((string) ($user->registrant_type ?? ''));
                            $userTypeLabel = match ($registrantType) {
                                'student'        => 'Student',
                                'faculty'        => 'Faculty',
                                'employee_staff' => 'Employee/Staff',
                                default          => 'Unspecified',
                            };
                            $deactivationCategoryLabel = '';
                            if ($user->deactivation_category) {
                                $deactivationCategoryLabel = match ($user->deactivation_category) {
                                    'graduated'         => 'Graduated',
                                    'left_institution'  => 'Left Institution',
                                    'duplicate_account' => 'Duplicate Account',
                                    'policy_violation'  => 'Policy Violation',
                                    default             => 'Other',
                                };
                            }
                        @endphp
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $userTypeLabel }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->department->name ?? 'N/A' }}</td>
                            <td>{{ $user->reports_count }} {{ Str::plural('report', $user->reports_count) }}</td>
                            <td>{{ $user->created_at->format('M d, Y') }}</td>
                            <td>
                                @if($user->status === 'active')
                                    <span class="status active">Active</span>
                                @elseif($user->status === 'suspended')
                                    <span class="status suspended">Suspended</span>
                                    @if($user->suspended_until)
                                        <div class="status-sub">Until {{ $user->suspended_until->format('M d, Y h:i A') }}</div>
                                    @endif
                                @else
                                    <span class="status deactivated">Deactivated</span>
                                    @if($deactivationCategoryLabel)
                                        <div class="status-sub">{{ $deactivationCategoryLabel }}</div>
                                    @endif
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.users.show', $user->id) }}" class="btn-view">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align:center;">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ================================================================
             MOBILE CARDS
             ================================================================ --}}
        <div class="mobile-users-list">
            @forelse($users as $user)
                @php
                    $registrantType = strtolower((string) ($user->registrant_type ?? ''));
                    $userTypeLabel = match ($registrantType) {
                        'student'        => 'Student',
                        'faculty'        => 'Faculty',
                        'employee_staff' => 'Employee/Staff',
                        default          => 'Unspecified',
                    };
                    $deactivationCategoryLabel = '';
                    if ($user->deactivation_category) {
                        $deactivationCategoryLabel = match ($user->deactivation_category) {
                            'graduated'         => 'Graduated',
                            'left_institution'  => 'Left Institution',
                            'duplicate_account' => 'Duplicate Account',
                            'policy_violation'  => 'Policy Violation',
                            default             => 'Other',
                        };
                    }
                @endphp
                <a href="{{ route('admin.users.show', $user->id) }}" class="mobile-user-card">
                    <div class="muc-top">
                        <div class="muc-left">
                            <span class="muc-id">#{{ $user->id }}</span>
                            <span class="muc-type">{{ $userTypeLabel }}</span>
                        </div>
                        @if($user->status === 'active')
                            <span class="status active">Active</span>
                        @elseif($user->status === 'suspended')
                            <span class="status suspended">Suspended</span>
                        @else
                            <span class="status deactivated">Deactivated</span>
                        @endif
                    </div>
                    <div class="muc-name">{{ $user->name }}</div>
                    <div class="muc-email">{{ $user->email }}</div>
                    <div class="muc-bottom">
                        @if($user->department->name ?? null)
                            <span class="muc-meta">🏫 {{ $user->department->name }}</span>
                        @endif
                        <span class="muc-meta">📋 {{ $user->reports_count }} {{ Str::plural('report', $user->reports_count) }}</span>
                        <span class="muc-meta">🗓 {{ $user->created_at->format('M d, Y') }}</span>
                        @if($user->status === 'suspended' && $user->suspended_until)
                            <span class="muc-meta muc-warn">Until {{ $user->suspended_until->format('M d, Y') }}</span>
                        @endif
                        @if($user->status === 'deactivated' && $deactivationCategoryLabel)
                            <span class="muc-meta muc-warn">{{ $deactivationCategoryLabel }}</span>
                        @endif
                    </div>
                </a>
            @empty
                <div class="mobile-empty-state">No users found.</div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="users-pagination">
            {{ $users->appends(request()->query())->links() }}
        </div>

    </section>

@endsection

@push('styles')
<style>
    /* ── Stats grid ── */
    .users-stats-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.85rem;
        margin-bottom: 1.1rem;
    }

    .users-stats-grid .stat-card { padding: 0.9rem 0.75rem; border-radius: 0.9rem; }
    .users-stats-grid .stat-card h4 { font-size: 0.84rem; letter-spacing: 0.5px; margin-bottom: 0.4rem; }
    .users-stats-grid .stat-card .stat-number { font-size: 2.15rem; line-height: 1; }

    /* ── Status sub-label ── */
    .status-sub {
        margin-top: 0.3rem;
        font-size: 0.76rem;
        color: rgba(255,255,255,0.75);
    }

    /* ── Pagination wrapper ── */
    .users-pagination { margin-top: 20px; padding: 0 1rem 1rem; }

    /* ================================================================
       UNIFIED FILTER PANEL
       ================================================================ */
    .ufp-panel {
        border-bottom: 1px solid rgba(255,255,255,0.12);
    }

    /* Mobile topbar */
    .ufp-mobile-topbar {
        display: none;
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
    }

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

    .ufp-collapsible-body { overflow: hidden; }
    .ufp-collapsible-body.collapsed { max-height: 0 !important; }

    .ufp-inner-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 0.75rem;
        padding: 1rem 1.25rem 0;
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

    .ufp-field input,
.ufp-field select {
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

.ufp-field input:focus,
.ufp-field select:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
}

.ufp-field input::placeholder { color: #9ca3af; }

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

.ufp-desktop-only { display: flex; }

    .ufp-actions {
        display: flex;
        gap: 0.75rem;
        padding: 0.875rem 1.25rem 1rem;
        align-items: center;
    }

    .ufp-apply-btn {
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
    .ufp-apply-btn:hover { background: #1d4ed8; }

    .ufp-clear-btn {
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
    .ufp-clear-btn:hover { background: rgba(255,255,255,0.08); color: #fff; }

    /* ── Show/hide ── */
    .desktop-users-table { display: block; }
    .mobile-users-list   { display: none; }

    /* ================================================================
       MOBILE USER CARDS
       ================================================================ */
    .mobile-user-card {
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
        padding: 0.875rem 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        text-decoration: none;
        color: inherit;
    }

    .mobile-user-card:last-child { border-bottom: none; }
    .mobile-user-card:active,
    .mobile-user-card:hover { background: rgba(255,255,255,0.05); }

    .muc-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }

    .muc-left {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .muc-id {
        font-size: 0.74rem;
        font-weight: 700;
        color: rgba(255,255,255,0.45);
    }

    .muc-type {
        font-size: 0.75rem;
        font-weight: 700;
        color: #93c5fd;
        background: rgba(96,165,250,0.15);
        border: 1px solid rgba(96,165,250,0.25);
        border-radius: 0.35rem;
        padding: 0.1rem 0.4rem;
    }

    .muc-name {
        font-size: 0.95rem;
        font-weight: 700;
        color: #fff;
    }

    .muc-email {
        font-size: 0.82rem;
        color: rgba(255,255,255,0.65);
    }

    .muc-bottom {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem 0.875rem;
        margin-top: 0.1rem;
    }

    .muc-meta {
        font-size: 0.76rem;
        color: rgba(255,255,255,0.55);
    }

    .muc-warn {
        color: #fbbf24;
        font-weight: 600;
    }

    .mobile-empty-state {
        padding: 2rem 1rem;
        text-align: center;
        color: rgba(255,255,255,0.7);
        font-size: 0.95rem;
    }

    /* ================================================================
       RESPONSIVE
       ================================================================ */
    @media (max-width: 768px) {
        .users-stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.6rem;
        }
        .users-stats-grid .stat-card { padding: 0.75rem 0.55rem; }
        .users-stats-grid .stat-card h4 { font-size: 0.7rem; letter-spacing: 0.3px; margin-bottom: 0.3rem; }
        .users-stats-grid .stat-card .stat-number { font-size: 1.65rem; }

        /* Filter panel */
        .ufp-mobile-topbar { display: flex; }
        .ufp-desktop-only  { display: none !important; }

        .ufp-collapsible-body {
            max-height: 0;
            overflow: hidden;
        }
        .ufp-collapsible-body:not(.collapsed) {
            max-height: 400px;
            overflow: visible;
        }

        .ufp-inner-grid {
            grid-template-columns: 1fr;
            padding: 0.75rem 0.875rem 0;
            gap: 0.6rem;
        }

        .ufp-actions { padding: 0.75rem 0.875rem 0.875rem; }

        .ufp-apply-btn,
        .ufp-clear-btn { flex: 1; text-align: center; justify-content: center; }

        /* Switch table → cards */
        .desktop-users-table { display: none; }
        .mobile-users-list   { display: block; }

        .users-pagination { padding: 0 0.875rem 0.875rem; }
    }

    @media (max-width: 480px) {
        .users-stats-grid .stat-card { padding: 0.65rem 0.45rem; }
        .users-stats-grid .stat-card h4 { font-size: 0.62rem; letter-spacing: 0.2px; }
        .users-stats-grid .stat-card .stat-number { font-size: 1.4rem; }
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var toggleBtn   = document.getElementById('ufp-toggle-btn');
    var collapsible = document.getElementById('ufp-collapsible');
    var searchMobile  = document.getElementById('ufp-search-mobile');
    var searchDesktop = document.getElementById('ufp-search-desktop');
    var filterForm  = document.getElementById('user-filter-form');

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

    /* Sync mobile search → desktop before submit */
    if (filterForm && searchMobile && searchDesktop) {
        filterForm.addEventListener('submit', function () {
            searchDesktop.value = searchMobile.value;
        });
    }

    /* Debounced search on mobile */
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