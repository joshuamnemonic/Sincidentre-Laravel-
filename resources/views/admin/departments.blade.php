@extends('layouts.admin')

@section('title', 'Departments - Sincidentre Department Student Discipline Officer')

@section('page-title', 'Department Management')

@section('header-search')
    <button onclick="openModal('addDepartmentModal')" class="btn-add">➕ Add Department</button>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Stats Summary -->
    <div class="stats-grid" style="margin-bottom: 2rem;">
        <div class="stat-card">
            <h4>Total Departments</h4>
            <p class="stat-number">{{ $departments->count() }}</p>
        </div>
        <div class="stat-card">
            <h4>Total Users</h4>
            <p class="stat-number">{{ $departments->sum('regular_users_count') }}</p>
        </div>
    </div>

    <!-- Departments Section -->
    <section id="departments">
        <div class="section-header">
            <h2>All Departments</h2>
        </div>

        {{-- ── MOBILE UFP PANEL (hidden on desktop) ── --}}
        <div class="ufp-panel mobile-filter-wrap">
            <div class="ufp-mobile-topbar">
                <div class="ufp-search-wrap">
                    <input
                        type="search"
                        id="ufp-search-mobile"
                        form="dept-filter-form-mobile"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search departments…"
                        autocomplete="off"
                        class="ufp-search-input"
                        aria-label="Search departments"
                    >
                </div>
            </div>
            <form method="GET" action="{{ route('admin.departments.index') }}" id="dept-filter-form-mobile">
                <input type="hidden" id="ufp-search-hidden" name="search" value="{{ request('search') }}">
            </form>
        </div>

        {{-- ================================================================
             DESKTOP TABLE (original, unchanged)
             ================================================================ --}}
        <div class="table-wrapper desktop-departments-table">
            <table>
                <thead>
                    <tr>
                        <th>Department Name</th>
                        <th>Total Users</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $department)
                        <tr>
                            <td><strong>{{ $department->name }}</strong></td>
                            <td>
                                <span class="badge">{{ $department->regular_users_count }}</span>
                                {{ Str::plural('user', $department->regular_users_count) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" style="text-align:center; padding: 40px;">
                                <p style="color: #999; margin: 0;">No departments found.</p>
                                <small>Click "Add Department" to create your first department!</small>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ================================================================
             MOBILE CARDS
             ================================================================ --}}
        <div class="mobile-departments-list">
            @forelse($departments as $department)
                <div class="mobile-dept-card">
                    <span class="mdc-name">{{ $department->name }}</span>
                    <span class="mdc-count">
                        {{ $department->regular_users_count }}
                        {{ Str::plural('user', $department->regular_users_count) }}
                    </span>
                </div>
            @empty
                <div class="mobile-empty-state">
                    No departments found.<br>
                    <small>Click "Add Department" to create your first department!</small>
                </div>
            @endforelse
        </div>

    </section>

    <!-- Add Department Modal -->
    <div id="addDepartmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Department</h3>
                <span class="close" onclick="closeModal('addDepartmentModal')">&times;</span>
            </div>
            <form id="addDepartmentForm" action="{{ route('admin.departments.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="add_name">Department Name *</label>
                    <input type="text"
                           id="add_name"
                           name="name"
                           placeholder="e.g., College of Engineering"
                           required>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeModal('addDepartmentModal')" class="btn-cancel">Cancel</button>
                    <button type="button" onclick="confirmAddDepartment()" class="btn-submit">Add Department</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirm Add Department Modal -->
    <div id="confirmDepartmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm New Department</h3>
                <span class="close" onclick="document.getElementById('confirmDepartmentModal').style.display='none'">&times;</span>
            </div>
            <p style="padding: 1rem 1.875rem;">Are you sure you want to add <strong id="confirmDepartmentName"></strong> as a new department?</p>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="document.getElementById('confirmDepartmentModal').style.display='none'">Cancel</button>
                <button type="button" class="btn-submit" onclick="document.getElementById('addDepartmentForm').submit()">Yes, Add Department</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    window.onclick = function (event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }

    setTimeout(function () {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);

    document.querySelectorAll('textarea').forEach(textarea => {
        textarea.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) e.stopPropagation();
        });
    });

    function confirmAddDepartment() {
        const name = document.getElementById('add_name').value.trim();
        if (!name) { document.getElementById('add_name').reportValidity(); return; }
        document.getElementById('confirmDepartmentName').textContent = '"' + name + '"';
        closeModal('addDepartmentModal');
        document.getElementById('confirmDepartmentModal').style.display = 'flex';
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.modal').forEach(function (m) {
            document.body.appendChild(m);
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal').forEach(function (m) {
                m.style.display = 'none';
            });
        }
    });

    /* ── Mobile search: debounced auto-submit ── */
    (function () {
        var searchMobile = document.getElementById('ufp-search-mobile');
        var searchHidden = document.getElementById('ufp-search-hidden');
        var mobileForm   = document.getElementById('dept-filter-form-mobile');

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
    .mobile-filter-wrap         { display: none;  }
    .desktop-departments-table  { display: block; }
    .mobile-departments-list    { display: none;  }

    /* ================================================================
       MOBILE UFP PANEL (search only — no filters needed)
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

    /* ================================================================
       MOBILE DEPARTMENT CARDS
       ================================================================ */
    .mobile-departments-list {
        padding: 0.5rem 0;
    }

    .mobile-dept-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.85rem 1rem;
        margin-bottom: 0.5rem;
        background: rgba(255,255,255,0.05);
        border-radius: 0.65rem;
        border: 1px solid rgba(255,255,255,0.08);
    }

    .mdc-name {
        font-size: 0.92rem;
        font-weight: 600;
        color: #fff;
        line-height: 1.3;
        min-width: 0;
        flex: 1 1 0%;
    }

    .mdc-count {
        font-size: 0.76rem;
        font-weight: 600;
        color: #93c5fd;
        background: rgba(96,165,250,0.15);
        border: 1px solid rgba(96,165,250,0.25);
        border-radius: 0.35rem;
        padding: 0.2rem 0.55rem;
        white-space: nowrap;
        flex-shrink: 0;
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
        .mobile-filter-wrap        { display: block; }
        .desktop-departments-table { display: none;  }
        .mobile-departments-list   { display: block; }
    }
</style>
@endpush