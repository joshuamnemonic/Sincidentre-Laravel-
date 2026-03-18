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

    @php
        $userNotifications = collect();
        $unreadNotificationCount = 0;

        if (Auth::check()) {
            $authUserId = Auth::id();
            $readNotificationKeys = collect(session('read_notifications_' . $authUserId, []));

            $timelineReports = \App\Models\Report::query()
                ->where('user_id', $authUserId)
                ->whereHas('responses')
                ->orderByDesc('updated_at')
                ->limit(8)
                ->get(['id', 'updated_at']);

            $hearingReports = \App\Models\Report::query()
                ->where('user_id', $authUserId)
                ->where(function ($query) {
                    $query->whereNotNull('hearing_date')
                        ->orWhereNotNull('hearing_time')
                        ->orWhereNotNull('hearing_venue');
                })
                ->orderByDesc('updated_at')
                ->limit(8)
                ->get(['id', 'updated_at']);

            foreach ($timelineReports as $reportItem) {
                $notifKey = 'timeline-' . $reportItem->id;
                $userNotifications->push([
                    'key' => $notifKey,
                    'type' => 'timeline',
                    'title' => 'Response Timeline Updated',
                    'message' => 'Case #' . $reportItem->id . ' has a Department Student Discipline Officer response update.',
                    'url' => route('report.show', [
                        'id' => $reportItem->id,
                        'notif_key' => $notifKey,
                        'goto' => 'admin-response-timeline',
                    ]),
                    'time' => $reportItem->updated_at,
                    'is_read' => $readNotificationKeys->contains($notifKey),
                ]);
            }

            foreach ($hearingReports as $reportItem) {
                $notifKey = 'hearing-' . $reportItem->id;
                $userNotifications->push([
                    'key' => $notifKey,
                    'type' => 'hearing',
                    'title' => 'Hearing Notification',
                    'message' => 'Case #' . $reportItem->id . ' has a hearing/case record update.',
                    'url' => route('report.show', [
                        'id' => $reportItem->id,
                        'notif_key' => $notifKey,
                        'goto' => 'case-records',
                    ]),
                    'time' => $reportItem->updated_at,
                    'is_read' => $readNotificationKeys->contains($notifKey),
                ]);
            }

            $userNotifications = $userNotifications
                ->sortByDesc(function ($item) {
                    return $item['time'];
                })
                ->take(12)
                ->values();

            $unreadNotificationCount = $userNotifications->where('is_read', false)->count();
        }
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
        <div class="user-topbar">
            <div class="user-topbar-spacer"></div>

            <div class="notification-wrapper" id="notificationWrapper" data-read-url="{{ route('notifications.read') }}">
                <button
                    type="button"
                    class="notification-bell-btn"
                    id="notificationBell"
                    aria-label="Open notifications"
                    aria-expanded="false"
                    aria-controls="notificationDropdown"
                >
                    <span class="notification-bell-icon">🔔</span>
                    @if($unreadNotificationCount > 0)
                        <span class="notification-badge">{{ $unreadNotificationCount }}</span>
                    @endif
                </button>

                <div class="notification-dropdown" id="notificationDropdown" role="menu" aria-labelledby="notificationBell">
                    <div class="notification-dropdown-header">Notifications</div>

                    @forelse($userNotifications as $notification)
                        <a href="{{ $notification['url'] }}" class="notification-item notification-item-{{ $notification['type'] }} {{ $notification['is_read'] ? 'notification-item-read' : '' }}" data-notification-key="{{ $notification['key'] }}" role="menuitem">
                            <div class="notification-item-title">{{ $notification['title'] }}</div>
                            <div class="notification-item-message">{{ $notification['message'] }}</div>
                            <small class="notification-item-time">{{ optional($notification['time'])->diffForHumans() }}</small>
                        </a>
                    @empty
                        <div class="notification-empty">No notifications yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

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

        // Logout functionality
        document.getElementById('logout-btn').addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                document.getElementById('logout-form').submit();
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

        // Mobile menu functions
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

        const notificationBell = document.getElementById('notificationBell');
        const notificationDropdown = document.getElementById('notificationDropdown');
        const notificationWrapper = document.getElementById('notificationWrapper');
        const readNotificationUrl = notificationWrapper ? String(notificationWrapper.dataset.readUrl || '') : '';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        function closeNotificationDropdown() {
            if (!notificationBell || !notificationDropdown) return;
            notificationDropdown.classList.remove('show');
            notificationBell.setAttribute('aria-expanded', 'false');
        }

        if (notificationBell && notificationDropdown) {
            const updateNotificationBadge = function () {
                const unreadCount = notificationDropdown.querySelectorAll('.notification-item:not(.notification-item-read)').length;
                let badge = notificationBell.querySelector('.notification-badge');

                if (unreadCount > 0) {
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'notification-badge';
                        notificationBell.appendChild(badge);
                    }
                    badge.textContent = String(unreadCount);
                } else if (badge) {
                    badge.remove();
                }
            };

            notificationBell.addEventListener('click', function (event) {
                event.stopPropagation();
                notificationDropdown.classList.toggle('show');
                const isExpanded = notificationDropdown.classList.contains('show');
                notificationBell.setAttribute('aria-expanded', String(isExpanded));
            });

            notificationDropdown.querySelectorAll('.notification-item[data-notification-key]').forEach(function (item) {
                item.addEventListener('click', function (event) {
                    if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                        return;
                    }

                    const targetUrl = item.getAttribute('href') || '';
                    const notifKey = item.getAttribute('data-notification-key') || '';

                    if (!targetUrl || !notifKey || !readNotificationUrl || !csrfToken) {
                        return;
                    }

                    event.preventDefault();

                    fetch(readNotificationUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ notif_key: notifKey }),
                        keepalive: true,
                    })
                        .then(function () {
                            item.classList.add('notification-item-read');
                            updateNotificationBadge();
                        })
                        .catch(function () {
                            // Keep navigation working even if the read-mark request fails.
                        })
                        .finally(function () {
                            window.location.href = targetUrl;
                        });
                });
            });

            document.addEventListener('click', function (event) {
                if (!notificationDropdown.contains(event.target) && !notificationBell.contains(event.target)) {
                    closeNotificationDropdown();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeNotificationDropdown();
                }
            });
        }
    </script>

    <script src="{{ asset('js/sincidentre.js') }}"></script>
    @stack('scripts')
</body>
</html>