<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sincidentre')</title>
    
    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('css/usernewcss.css') }}">
    <script src="{{ asset('sinci.js') }}"></script>
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
                <!-- Replace 'logo.png' with your actual logo path -->
                <img src="{{ asset('images/sincidentrelogo.png') }}" alt="Sincidentre Logo" class="sidebar-logo">
                <div class="logo-text">
                    <h2>SINCIDENTRE</h2>
                    <span class="logo-tagline">User Panel</span>
                </div>
            </div>
        </div>

        <!-- User Profile Card -->
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
                <p>{{ Auth::user()->email }}</p>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" 
               class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="nav-icon">🏠</span>
                <span class="nav-text">Dashboard</span>
            </a>

            <a href="{{ route('newreport') }}" 
               class="nav-link {{ request()->routeIs('newreport') ? 'active' : '' }}">
                <span class="nav-icon">📝</span>
                <span class="nav-text">New Report</span>
            </a>

            <a href="{{ route('myreports') }}" 
               class="nav-link {{ request()->routeIs('myreports') ? 'active' : '' }}">
                <span class="nav-icon">📋</span>
                <span class="nav-text">My Reports</span>
            </a>

            <a href="{{ route('profile') }}" 
               class="nav-link {{ request()->routeIs('profile') ? 'active' : '' }}">
                <span class="nav-icon">👤</span>
                <span class="nav-text">Profile</span>
            </a>
        </nav>

        <!-- Logout Section -->
        <div class="sidebar-footer">
            <!-- Hidden logout form -->
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>

            <!-- Logout Button -->
            <button type="button" id="logout-btn" class="logout-btn">
                <span class="nav-icon">🚪</span>
                <span class="nav-text">Logout</span>
            </button>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="dashboard">
        @yield('content')
    </main>

    <!-- Scripts -->
    <script>
        // Logout functionality
        document.getElementById('logout-btn').addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                document.getElementById('logout-form').submit();
            }
        });

        // Mobile menu functions
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

        // Close mobile menu when clicking a link
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 768) {
                    closeMobileMenu();
                }
            });
        });

        // Close menu on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                closeMobileMenu();
            }
        });
    </script>

    <script src="{{ asset('js/sincidentre.js') }}"></script>
    @stack('scripts')
</body>
</html>