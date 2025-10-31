<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sincidentre Admin Dashboard</title>
  <link rel="stylesheet" href="{{ asset('css/newcss.css') }}">
</head>
<body>
  <div class="layout">
    <!-- Sidebar -->
    <aside class="sidebar">
      <h2>🛡️ Sincidentre Admin</h2>
      <nav>
        <ul>
          <li><a href="{{ route('admin.admindashboard') }}">Overview</a></li>
          <li><a href="{{ route('admin.reports') }}">Review Queue</a></li>
          <li><a href="{{ route('admin.users') }}">Users</a></li>
          <li><a href="{{ route('admin.categories.index') }}">Categories</a></li>
          <li><a href="{{ route('admin.handlereports') }}">Handle Reports</a></li>
        </ul>
      </nav>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">🚪 Logout</button>
      </form>
    </aside>

    <!-- Main Content -->
    <main class="main">
      <!-- Topbar -->
      <header>
        <h1>Admin Dashboard</h1>
        <input type="search" placeholder="Search reports, users, IDs…">
      </header>

      <!-- Stats Cards -->
      <section class="stats">
        <div class="card"><h3>Total Reports</h3><p>{{ $totalReports }}</p></div>
        <div class="card"><h3>Pending</h3><p>{{ $pendingReports }}</p></div>
        <div class="card"><h3>Under Review</h3><p>{{ $underReview }}</p></div>
        <div class="card"><h3>Resolved</h3><p>{{ $resolvedReports }}</p></div>
        <div class="card"><h3>Total Users</h3><p>{{ $totalUsers }}</p></div>
      </section>

      <!-- Reports Table -->
      <section id="reports">
        <h2>Recent Reports</h2>
        <table border="1" cellspacing="0" cellpadding="8">
          <thead>
            <tr>
              <th>ID</th><th>Title</th><th>Reporter</th><th>Category</th><th>Date</th><th>Status</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($recentReports as $report)
              <tr>
                <td>{{ $report->id }}</td>
                <td>{{ $report->title }}</td>
                <td>{{ $report->user->name }}</td>
                <td>{{ $report->category }}</td>
                <td>{{ $report->incident_date }}</td>
                <td>{{ $report->status }}</td>
                <td>
                  <a href="{{ route('admin.reports.show', $report->id) }}">View</a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </section>
    </main>
  </div>
</body>
</html>
