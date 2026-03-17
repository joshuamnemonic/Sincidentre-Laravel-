<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sincidentre Department Student Discipline Officer')</title>

    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('css/newcss.css') }}">
    @stack('styles')
</head>
<body>

    @php
        $isTopManagement = (bool) (Auth::user()->is_top_management ?? false);
        $managementTitle = $isTopManagement ? 'Top Management' : 'Department Student Discipline Officer';
        $pendingHandlingResponse = session('pending_handling_response');
        $hasPendingHandlingResponse = is_array($pendingHandlingResponse)
            && (int) ($pendingHandlingResponse['report_id'] ?? 0) > 0
            && (int) ($pendingHandlingResponse['user_id'] ?? 0) === (int) (Auth::id() ?? 0);
        $pendingReportId = (int) ($pendingHandlingResponse['report_id'] ?? 0);
    @endphp

    <!-- Mobile Menu Toggle Button -->
    <button class="mobile-menu-toggle" onclick="toggleSidebarMenu()" aria-label="Toggle Menu" aria-expanded="true" aria-controls="sidebar">
        <span class="hamburger-icon">☰</span>
    </button>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" onclick="closeMobileMenu()"></div>

    <!-- Left Sidebar -->
    <aside class="sidebar" id="sidebar">

        <!-- Logo Section -->
        <div class="sidebar-header">
            <div class="logo-container">
                <img src="{{ asset('images/sincidentrelogo.png') }}" alt="Sincidentre Logo" class="sidebar-logo">
                <div class="logo-text">
                    <h2>SINCIDENTRE</h2>
                    <span class="logo-tagline">{{ $managementTitle }} Panel</span>
                </div>
            </div>
        </div>

        <!-- Management Profile Card -->
        <div class="user-profile-card">
            <div class="user-avatar">
                @if(Auth::user()->profile_picture)
                    <img src="{{ asset(Auth::user()->profile_picture) }}" alt="Profile">
                @else
                    <div class="avatar-placeholder">{{ strtoupper(substr(Auth::user()->first_name, 0, 1)) }}</div>
                @endif
            </div>
            <div class="user-info">
                <h3>{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</h3>
                <p>{{ Auth::user()->department->name ?? $managementTitle }}</p>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="sidebar-nav">
            <a href="{{ route('admin.admindashboard') }}"
               class="nav-link {{ request()->routeIs('admin.admindashboard') ? 'active' : '' }}">
                <span class="nav-icon">🏠</span>
                <span class="nav-text">Overview</span>
            </a>

            <a href="{{ route('admin.reports') }}"
               class="nav-link {{ request()->routeIs('admin.reports*') ? 'active' : '' }}">
                <span class="nav-icon">📋</span>
                <span class="nav-text">New Reports</span>
            </a>

            <a href="{{ route('admin.handlereports') }}"
               class="nav-link {{ request()->routeIs('admin.handlereports*') ? 'active' : '' }}">
                <span class="nav-icon">🛠️</span>
                <span class="nav-text">Handle Reports</span>
            </a>

            <a href="{{ route('admin.analytics') }}"
               class="nav-link {{ request()->routeIs('admin.analytics*') ? 'active' : '' }}">
                <span class="nav-icon">📊</span>
                <span class="nav-text">Analytics</span>
            </a>

            <a href="{{ route('admin.users') }}"
               class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                <span class="nav-icon">👥</span>
                <span class="nav-text">Users</span>
            </a>

            @if($isTopManagement)
                <a href="{{ route('admin.categories.index') }}"
                   class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                    <span class="nav-icon">🏷️</span>
                    <span class="nav-text">Categories</span>
                </a>

                <a href="{{ route('admin.departments.index') }}"
                   class="nav-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}">
                    <span class="nav-icon">🏢</span>
                    <span class="nav-text">Departments</span>
                </a>
            @endif

            <a href="{{ route('admin.activitylogs') }}"
               class="nav-link {{ request()->routeIs('admin.activitylogs') ? 'active' : '' }}">
                <span class="nav-icon">📜</span>
                <span class="nav-text">Audit Trail</span>
            </a>

            <a href="{{ route('admin.profile') }}"
               class="nav-link {{ request()->routeIs('admin.profile') ? 'active' : '' }}">
                <span class="nav-icon">👤</span>
                <span class="nav-text">Profile</span>
            </a>
        </nav>

        <!-- Logout Section -->
        <div class="sidebar-footer">
            <form id="admin-logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>

            <button type="button" id="admin-logout-btn" class="logout-btn">
                <span class="nav-icon">🚪</span>
                <span class="nav-text">Logout</span>
            </button>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="dashboard">
        <!-- Topbar -->
        <header>
            <h1>@yield('page-title', $managementTitle . ' Dashboard')</h1>
            @yield('header-search')
        </header>

        @if($hasPendingHandlingResponse)
            <div style="background: rgba(245, 158, 11, 0.2); border: 1px solid rgba(245, 158, 11, 0.55); color: #fde68a; border-radius: 0.6rem; padding: 0.85rem 1rem; margin: 0 0 1rem 0;">
                <strong>Action required:</strong> Complete the Add Handling Response form for Report #{{ $pendingReportId }} before navigating to other pages or logging out.
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Scripts -->
    <script>
        function enableResponsiveTables() {
            document.querySelectorAll('table').forEach(function(table) {
                table.classList.add('responsive-table');

                const headCells = table.querySelectorAll('thead th');
                if (!headCells.length) {
                    return;
                }

                table.querySelectorAll('tbody tr').forEach(function(row) {
                    row.querySelectorAll('td').forEach(function(cell, index) {
                        const headerText = headCells[index] ? headCells[index].textContent.trim() : 'Field';
                        cell.setAttribute('data-label', headerText);
                    });
                });
            });
        }

        document.getElementById('admin-logout-btn').addEventListener('click', function(e) {
            e.preventDefault();
            if (this.hasAttribute('data-pending-response')) {
                alert('Complete the Add Handling Response form before logging out.');
                return;
            }

            if (confirm('Are you sure you want to logout?')) {
                document.getElementById('admin-logout-form').submit();
            }
        });

        enableResponsiveTables();

        function isMobileView() {
            return window.innerWidth < 768;
        }

        function toggleSidebarMenu() {
            if (isMobileView()) {
                toggleMobileMenu();
                return;
            }

            const body = document.body;
            const isCollapsed = body.classList.toggle('sidebar-collapsed');
            document.querySelector('.mobile-menu-toggle').setAttribute('aria-expanded', String(!isCollapsed));
        }

        function toggleMobileMenu() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            const body = document.body;
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            body.classList.toggle('menu-open', sidebar.classList.contains('active'));
            document.querySelector('.mobile-menu-toggle').setAttribute('aria-expanded', String(sidebar.classList.contains('active')));
        }

        function closeMobileMenu() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            const body = document.body;
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            body.classList.remove('menu-open');
            if (isMobileView()) {
                document.querySelector('.mobile-menu-toggle').setAttribute('aria-expanded', 'false');
            }
        }

        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 768) {
                    closeMobileMenu();
                }
            });
        });

        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                closeMobileMenu();
                document.body.classList.remove('menu-open');
                document.querySelector('.mobile-menu-toggle').setAttribute('aria-expanded', String(!document.body.classList.contains('sidebar-collapsed')));
            } else {
                document.body.classList.remove('sidebar-collapsed');
                document.body.classList.remove('menu-open');
                document.querySelector('.mobile-menu-toggle').setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && isMobileView()) {
                closeMobileMenu();
            }
        });

        if (isMobileView()) {
            document.querySelector('.mobile-menu-toggle').setAttribute('aria-expanded', 'false');
        }

        @if($hasPendingHandlingResponse)
            const logoutBtn = document.getElementById('admin-logout-btn');
            if (logoutBtn) {
                logoutBtn.setAttribute('data-pending-response', '1');
                logoutBtn.setAttribute('title', 'Complete Add Handling Response first');
            }
        @endif
    </script>

    @stack('scripts')
</body>
</html>
