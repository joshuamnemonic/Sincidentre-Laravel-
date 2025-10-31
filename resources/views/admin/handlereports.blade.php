<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sincidentre - Handle Reports</title>
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
      <header>
        <h1>Handle Reports</h1>
        <input type="search" placeholder="Search reports...">
      </header>

      <!-- Approved Reports Table -->
      <section id="handle-reports">
        <h2>Approved Reports List</h2>
        <p>These reports have been approved and are ready to be handled.</p>

        <table border="1" cellspacing="0" cellpadding="8">
          <thead>
            <tr>
              <th>ID</th>
              <th>Title</th>
              <th>Reporter</th>
              <th>Category</th>
              <th>Date Submitted</th>
              <th>Status</th>
              <th>Assigned To</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($approvedReports as $report)
              <tr>
                <td>{{ $report->id }}</td>
                <td>{{ $report->title }}</td>
                <td>{{ $report->user->name }}</td>
                <td>{{ $report->category }}</td>
                <td>{{ $report->created_at->format('Y-m-d') }}</td>
                <td>{{ ucfirst($report->status) }}</td>
                <td>{{ $report->assigned_to ?? 'Unassigned' }}</td>
                <td>
                  <a href="{{ route('admin.handlereports.show', $report->id) }}">Handle</a>
                </td>
              </tr>
            @empty
              <tr><td colspan="8" style="text-align:center;">No approved reports yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </section>
    </main>
  </div>
</body>
</html>
