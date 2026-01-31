<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sincidentre Admin')</title>
    <link rel="stylesheet" href="{{ asset('css/newcss.css') }}">
    @stack('styles')
</head>
<body>
    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h2>Sincidentre Admin</h2>
            <nav>
                <ul>
                    <li>
                        <a href="{{ route('admin.admindashboard') }}" 
                           class="{{ request()->routeIs('admin.admindashboard') ? 'active' : '' }}">
                            Overview
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.reports') }}" 
                           class="{{ request()->routeIs('admin.reports') ? 'active' : '' }}">
                            Review Queue
                        </a>
                    <li>
                        <a href="{{ route('admin.handlereports') }}" 
                           class="{{ request()->routeIs('admin.handlereports') ? 'active' : '' }}">
                            Handle Reports
                        </a>
                    </li>
                    </li>
                    <li>
                        <a href="{{ route('admin.users') }}" 
                           class="{{ request()->routeIs('admin.users') ? 'active' : '' }}">
                            Users
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.categories.index') }}" 
                           class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                            Categories
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.departments.index') }}" 
                           class="{{ request()->routeIs('admin.departments.*') ? 'active' : '' }}">
                            Departments
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.activitylogs') }}" 
                           class="{{ request()->routeIs('admin.activitylogs') ? 'active' : '' }}">
                            Activity Logs
                        </a>
                    </li>
                </ul>
            </nav>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"> Logout</button>
            </form>
        </aside>

        <!-- Main Content -->
        <main class="main">
            <!-- Topbar -->
            <header>
                <h1>@yield('page-title', 'Admin Dashboard')</h1>
                @yield('header-search')
            </header>

            <!-- Page Content -->
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>