<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sincidentre Admin')</title>

    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('css/newcss.css') }}">
    @stack('styles')
</head>
<body>

    <!-- Mobile Menu Toggle Button -->
    <button class="mobile-menu-toggle" onclick="toggleMobileMenu()" aria-label="Toggle Menu">
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
                    <span class="logo-tagline">Admin Panel</span>
                </div>
            </div>
        </div>

        <!-- Admin Profile Card -->
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
                <p>{{ Auth::user()->department->name ?? 'Admin' }}</p>
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
                <span class="nav-text">Review Queue</span>
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

            <a href="{{ route('admin.activitylogs') }}"
               class="nav-link {{ request()->routeIs('admin.activitylogs') ? 'active' : '' }}">
                <span class="nav-icon">📜</span>
                <span class="nav-text">Activity Logs</span>
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
            <h1>@yield('page-title', 'Admin Dashboard')</h1>
            @yield('header-search')
        </header>

        @yield('content')
    </main>

    <!-- Scripts -->
    <script>
        document.getElementById('admin-logout-btn').addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                document.getElementById('admin-logout-form').submit();
            }
        });

        function toggleMobileMenu() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        function closeMobileMenu() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
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
            }
        });
    </script>

    @stack('scripts')
</body>
</html>