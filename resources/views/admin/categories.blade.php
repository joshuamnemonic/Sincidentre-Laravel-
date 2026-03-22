@extends('layouts.admin')

@section('title', 'Categories Management - Sincidentre Department Student Discipline Officer')

@section('page-title', 'Categories Management')

@section('header-search')
    <button onclick="openModal('addCategoryModal')" class="btn-add">➕ Add Category</button>
@endsection

@section('content')
    <p>Manage the different types of incident categories used in Sincidentre.</p>

    @if(session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif

    <!-- Add Category Modal -->
    <div id="addCategoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Category</h3>
                <span class="close" onclick="closeModal('addCategoryModal')">&times;</span>
            </div>
            <form id="addCategoryForm" action="{{ route('admin.categories.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="mainCategoryCode">Main Category Code *</label>
                    <select id="mainCategoryCode" name="mainCategoryCode" required>
                        <option value="">-- Select Main Category --</option>
                        <option value="A">A - Offenses Against Persons and Public Order</option>
                        <option value="B">B - Offenses Against Property and Security</option>
                        <option value="C">C - Academic and Technological Misconduct</option>
                        <option value="D">D - Behavioral and Substance Violations</option>
                        <option value="E">E - Administrative and General Violations</option>
                        <option value="F">F - Offenses Against Public Morals</option>
                        <option value="G">G - Technical and Facility Issues</option>
                        <option value="custom">Other (Add New Main Category)</option>
                    </select>
                </div>
                <div class="form-group" id="customMainCodeWrapper" style="display:none;">
                    <label for="customMainCategoryCode">Custom Main Category Code *</label>
                    <input type="text" id="customMainCategoryCode" name="customMainCategoryCode" maxlength="1" placeholder="e.g., H">
                </div>
                <div class="form-group">
                    <label for="mainCategoryName">Main Category Name *</label>
                    <input type="text" id="mainCategoryName" name="mainCategoryName" required>
                </div>
                <div class="form-group">
                    <label for="categoryName">Category Name *</label>
                    <input type="text" id="categoryName" name="categoryName" placeholder="e.g., Cheating/Plagiarism" required>
                </div>
                <div class="form-group">
                    <label for="classification">Classification *</label>
                    <select id="classification" name="classification" required>
                        <option value="">-- Select Classification --</option>
                        <option value="Minor">Minor</option>
                        <option value="Major">Major</option>
                        <option value="Grave">Grave</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="routing_group_code">Assign to Routing Group *</label>
                    <select id="routing_group_code" name="routing_group_code" required>
                        <option value="">-- Select Routing Group --</option>
                        <option value="disciplinary">DSDO (Disciplinary)</option>
                        <option value="top_management">Top Management</option>
                        <option value="networks_iot">Networks / IoT</option>
                        <option value="facilities_electricity">Facilities / Electricity</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeModal('addCategoryModal')" class="btn-cancel">Cancel</button>
                    <button type="button" onclick="confirmAddCategory()" class="btn-submit">Add Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirm Add Category Modal -->
    <div id="confirmCategoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm New Category</h3>
                <span class="close" onclick="closeModal('confirmCategoryModal')">&times;</span>
            </div>
            <p style="padding: 1rem 1.875rem;">Are you sure you want to add this category?</p>
            <p style="padding: 0 1.875rem 1rem; color: #555;"><strong id="confirmCategorySummary"></strong></p>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('confirmCategoryModal')">Cancel</button>
                <button type="button" class="btn-submit" onclick="document.getElementById('addCategoryForm').submit()">Yes, Add Category</button>
            </div>
        </div>
    </div>

    <!-- Categories Table -->
    <section style="margin-top: 20px;">
        <h2>Existing Categories</h2>

        @php
            $activeCategoryFilters = (request()->filled('search') ? 1 : 0)
                + (request()->filled('main_code') ? 1 : 0)
                + (request()->filled('classification') ? 1 : 0);
        @endphp

        {{-- ── DESKTOP FILTER (original, hidden on mobile) ── --}}
        <div class="category-filter-wrap desktop-filter-wrap">
            <form method="GET" action="{{ route('admin.categories.index') }}" class="category-filter-form">
                <div class="category-filter-field">
                    <label for="main_code">Main Category</label>
                    <select name="main_code" id="main_code">
                        <option value="">All Main Categories</option>
                        @foreach(($mainCodes ?? collect()) as $code => $mainName)
                            <option value="{{ $code }}" {{ ($mainCode ?? '') === $code ? 'selected' : '' }}>
                                {{ $code }} - {{ $mainName }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="category-filter-field">
                    <label for="classification_filter">Classification</label>
                    <select name="classification" id="classification_filter">
                        <option value="">All Classifications</option>
                        <option value="Minor" {{ ($classification ?? '') === 'Minor' ? 'selected' : '' }}>Minor</option>
                        <option value="Major" {{ ($classification ?? '') === 'Major' ? 'selected' : '' }}>Major</option>
                        <option value="Grave" {{ ($classification ?? '') === 'Grave' ? 'selected' : '' }}>Grave</option>
                    </select>
                </div>
                <div class="category-filter-field">
                    <label for="search_filter">Search</label>
                    <input type="text" id="search_filter" name="search" value="{{ $search ?? '' }}" placeholder="Category or main category name">
                </div>
                <div class="category-filter-actions">
                    <button type="submit" class="btn-filter">Apply Filter</button>
                    @if(($mainCode ?? '') !== '' || ($classification ?? '') !== '' || ($search ?? '') !== '')
                        <a href="{{ route('admin.categories.index') }}" class="btn-clear">Clear Filter</a>
                    @endif
                </div>
            </form>
        </div>

        {{-- ── MOBILE UFP PANEL (hidden on desktop) ── --}}
        <div class="ufp-panel mobile-filter-wrap">
            <div class="ufp-mobile-topbar">
                <div class="ufp-search-wrap">
                    <input
                        type="search"
                        id="ufp-search-mobile"
                        form="category-filter-form-mobile"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search categories…"
                        autocomplete="off"
                        class="ufp-search-input"
                        aria-label="Search categories"
                    >
                </div>
                <button type="button" id="ufp-toggle-btn" class="ufp-toggle-btn" aria-expanded="false" aria-controls="ufp-collapsible">
                    <span>⚙️</span>
                    <span>Filters</span>
                    @if($activeCategoryFilters > 0)
                        <span class="ufp-active-badge">{{ $activeCategoryFilters }}</span>
                    @endif
                </button>
            </div>

            <form method="GET" action="{{ route('admin.categories.index') }}" id="category-filter-form-mobile">
                <div id="ufp-collapsible" class="ufp-collapsible-body collapsed">
                    <div class="ufp-inner-grid">
                        <div class="ufp-field">
                            <label for="ufp-main-code" class="ufp-label">Main Category</label>
                            <select name="main_code" id="ufp-main-code">
                                <option value="">All Main Categories</option>
                                @foreach(($mainCodes ?? collect()) as $code => $mainName)
                                    <option value="{{ $code }}" {{ request('main_code') === $code ? 'selected' : '' }}>
                                        {{ $code }} - {{ $mainName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ufp-field">
                            <label for="ufp-classification" class="ufp-label">Classification</label>
                            <select name="classification" id="ufp-classification">
                                <option value="">All Classifications</option>
                                <option value="Minor" {{ request('classification') === 'Minor' ? 'selected' : '' }}>Minor</option>
                                <option value="Major" {{ request('classification') === 'Major' ? 'selected' : '' }}>Major</option>
                                <option value="Grave" {{ request('classification') === 'Grave' ? 'selected' : '' }}>Grave</option>
                            </select>
                        </div>
                        <input type="hidden" id="ufp-search-hidden" name="search" value="{{ request('search') }}">
                    </div>
                    <div class="ufp-actions">
                        <button type="submit" class="ufp-apply-btn">Apply Filters</button>
                        @if($activeCategoryFilters > 0)
                            <a href="{{ route('admin.categories.index') }}" class="ufp-clear-btn">Clear All</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- ================================================================
             DESKTOP TABLE (original columns, with badge upgrades)
             ================================================================ --}}
        <div class="table-wrapper desktop-categories-table">
            <table>
                <thead>
                    <tr>
                        <th>Main</th>
                        <th>Category Name</th>
                        <th>Classification</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td>
                                <span class="cat-code-badge">{{ $category->main_category_code }}</span>
                                {{ $category->main_category_name }}
                            </td>
                            <td>{{ $category->name }}</td>
                            <td>
                                @if($category->classification === 'Minor')
                                    <span class="status active">Minor</span>
                                @elseif($category->classification === 'Major')
                                    <span class="status suspended">Major</span>
                                @elseif($category->classification === 'Grave')
                                    <span class="status deactivated">Grave</span>
                                @else
                                    {{ $category->classification }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">No categories found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ================================================================
             MOBILE CARDS (replaces table on mobile)
             ================================================================ --}}
        <div class="mobile-categories-list">
            @forelse($categories as $category)
                <div class="mobile-category-card">
                    <div class="mcc-top">
                        <div class="mcc-left">
                            <span class="mcc-code">{{ $category->main_category_code }}</span>
                            <span class="mcc-main-name">{{ $category->main_category_name }}</span>
                        </div>
                        <span class="mcc-classification mcc-class-{{ strtolower($category->classification) }}">
                            {{ $category->classification }}
                        </span>
                    </div>
                    <div class="mcc-name">{{ $category->name }}</div>
                </div>
            @empty
                <div class="mobile-empty-state">No categories found.</div>
            @endforelse
        </div>

    </section>
@endsection

@push('scripts')
<script>
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    function confirmAddCategory() {
        const mainCode       = document.getElementById('mainCategoryCode').value.trim();
        const mainName       = document.getElementById('mainCategoryName').value.trim();
        const name           = document.getElementById('categoryName').value.trim();
        const classification = document.getElementById('classification').value.trim();
        if (!mainCode)       { document.getElementById('mainCategoryCode').reportValidity(); return; }
        if (!name)           { document.getElementById('categoryName').reportValidity(); return; }
        if (!classification) { document.getElementById('classification').reportValidity(); return; }
        document.getElementById('confirmCategorySummary').textContent =
            mainCode + ' - ' + mainName + ' / ' + name + ' (' + classification + ')';
        closeModal('addCategoryModal');
        openModal('confirmCategoryModal');
    }

    const mainCategoryMap = {
        A: 'Offenses Against Persons and Public Order',
        B: 'Offenses Against Property and Security',
        C: 'Academic and Technological Misconduct',
        D: 'Behavioral and Substance Violations',
        E: 'Administrative and General Violations',
        F: 'Offenses Against Public Morals',
        G: 'Technical and Facility Issues'
    };

    document.getElementById('mainCategoryCode').addEventListener('change', function () {
        const value = this.value;
        const mainCategoryNameInput = document.getElementById('mainCategoryName');
        const customMainCodeWrapper = document.getElementById('customMainCodeWrapper');
        const customMainCodeInput   = document.getElementById('customMainCategoryCode');

        if (value === 'custom') {
            customMainCodeWrapper.style.display = 'block';
            customMainCodeInput.required = true;
            mainCategoryNameInput.value = '';
            mainCategoryNameInput.readOnly = false;
            mainCategoryNameInput.placeholder = 'Enter new main category name';
            return;
        }

        customMainCodeWrapper.style.display = 'none';
        customMainCodeInput.required = false;
        customMainCodeInput.value = '';
        mainCategoryNameInput.readOnly = false;
        mainCategoryNameInput.placeholder = '';
        mainCategoryNameInput.value = mainCategoryMap[value] || mainCategoryNameInput.value;
    });

    window.addEventListener('click', function (e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal').forEach(function (m) {
                m.style.display = 'none';
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.modal').forEach(function (m) {
            document.body.appendChild(m);
        });
    });

    /* ── Mobile UFP toggle ── */
    (function () {
        var toggleBtn    = document.getElementById('ufp-toggle-btn');
        var collapsible  = document.getElementById('ufp-collapsible');
        var searchMobile = document.getElementById('ufp-search-mobile');
        var searchHidden = document.getElementById('ufp-search-hidden');
        var mobileForm   = document.getElementById('category-filter-form-mobile');

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
    /* ================================================================
       DESKTOP FILTER — original styles, completely unchanged
       ================================================================ */
    .category-filter-wrap {
        margin-bottom: 14px;
    }

    .category-filter-form {
        display: flex;
        gap: 10px;
        align-items: end;
        flex-wrap: wrap;
    }

    .category-filter-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 210px;
        flex: 1 1 220px;
    }

    .category-filter-field label {
        font-weight: 600;
    }

    .category-filter-field select,
    .category-filter-field input {
        width: 100%;
        min-height: 42px;
        padding: 0.65rem 0.8rem;
        background: #ffffff;
        color: #111111;
        border: 1px solid rgba(17, 24, 39, 0.18);
        border-radius: 0.55rem;
    }

    .category-filter-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .category-filter-actions .btn-filter,
    .category-filter-actions .btn-clear {
        min-height: 42px;
        padding: 0.65rem 0.9rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }

    /* ── Code badge (desktop table) ── */
    .cat-code-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        height: 24px;
        padding: 0 0.4rem;
        background: rgba(96,165,250,0.15);
        border: 1px solid rgba(96,165,250,0.3);
        border-radius: 0.35rem;
        font-size: 0.78rem;
        font-weight: 700;
        color: #93c5fd;
        margin-right: 0.4rem;
        vertical-align: middle;
    }

    /* ── Visibility switches ── */
    .desktop-filter-wrap      { display: block; }
    .mobile-filter-wrap       { display: none;  }
    .desktop-categories-table { display: block; }
    .mobile-categories-list   { display: none;  }

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
        max-height: 400px;
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

    .ufp-field select:focus {
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
       MOBILE CATEGORY CARDS
       ================================================================ */
    .mobile-categories-list {
        padding: 0.5rem 0;
    }

    .mobile-category-card {
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
        padding: 0.85rem 1rem;
        margin: 0 0 0.5rem 0;
        background: rgba(255,255,255,0.05);
        border-radius: 0.65rem;
        border: 1px solid rgba(255,255,255,0.08);
    }

    .mcc-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }

    .mcc-left {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        min-width: 0;
    }

    .mcc-code {
        font-size: 0.72rem;
        font-weight: 700;
        color: #93c5fd;
        background: rgba(96,165,250,0.15);
        border: 1px solid rgba(96,165,250,0.25);
        border-radius: 0.3rem;
        padding: 0.1rem 0.4rem;
        flex-shrink: 0;
    }

    .mcc-main-name {
        font-size: 0.76rem;
        color: rgba(255,255,255,0.5);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .mcc-name {
        font-size: 0.92rem;
        font-weight: 600;
        color: #fff;
        line-height: 1.3;
    }

    .mcc-classification {
        font-size: 0.72rem;
        font-weight: 700;
        border-radius: 0.3rem;
        padding: 0.2rem 0.5rem;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .mcc-class-minor {
        color: #86efac;
        background: rgba(134,239,172,0.12);
        border: 1px solid rgba(134,239,172,0.25);
    }

    .mcc-class-major {
        color: #fbbf24;
        background: rgba(251,191,36,0.12);
        border: 1px solid rgba(251,191,36,0.25);
    }

    .mcc-class-grave {
        color: #f87171;
        background: rgba(248,113,113,0.12);
        border: 1px solid rgba(248,113,113,0.25);
    }

    .mobile-empty-state {
        padding: 2rem 1rem;
        text-align: center;
        color: rgba(255,255,255,0.7);
        font-size: 0.95rem;
    }

    /* ================================================================
       RESPONSIVE — mobile breakpoint only
       ================================================================ */
    @media (max-width: 768px) {
        .desktop-filter-wrap      { display: none;  }
        .mobile-filter-wrap       { display: block; }
        .desktop-categories-table { display: none;  }
        .mobile-categories-list   { display: block; }
    }
</style>
@endpush