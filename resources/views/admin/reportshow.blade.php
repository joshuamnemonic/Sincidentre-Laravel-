<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Report Details - Sincidentre Admin</title>
  <link rel="stylesheet" href="{{ asset('css/admindashboard.css') }}">
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
        <h1>📄 Report Details</h1>
        <p>Full information about this reported incident.</p>
      </header>

      <section>
        <table border="1" cellspacing="0" cellpadding="8" width="100%">
          <tr><th>Title</th><td>{{ $report->title }}</td></tr>
          <tr><th>Category</th><td>{{ $report->category }}</td></tr>
          <tr><th>Description</th><td>{{ $report->description }}</td></tr>
          <tr><th>Date of Incident</th><td>{{ $report->incident_date }}</td></tr>
          <tr><th>Time of Incident</th><td>{{ $report->incident_time }}</td></tr>
          <tr><th>Location</th><td>{{ $report->location }}</td></tr>
          <tr><th>Submitted By</th>
              <td>{{ $report->user->first_name ?? 'Unknown' }} {{ $report->user->last_name ?? '' }}</td></tr>
          <tr><th>Submitted At</th>
              <td>{{ $report->submitted_at?->format('F d, Y h:i A') }}</td></tr>
          <tr><th>Status</th><td>{{ $report->status }}</td></tr>
          <tr>
            <th>Evidence</th>
            <td>
              @if($report->evidence)
                <img src="{{ asset('storage/' . $report->evidence) }}" alt="Evidence" width="300">
              @else
                No file attached.
              @endif
            </td>
          </tr>
        </table>

        <div style="margin-top: 20px;">
          <form action="{{ route('admin.reports.approve', $report->id) }}" method="POST" style="display:inline-block;">
            @csrf
            @method('PATCH')
            <button type="submit">✅ Approve</button>
          </form>

          <form action="{{ route('admin.reports.reject', $report->id) }}" method="POST" style="display:inline-block;">
            @csrf
            @method('PATCH')
            <button type="submit">❌ Reject</button>
          </form>

          <p style="margin-top: 20px;">
            <a href="{{ route('admin.reports') }}">⬅ Back to Reports</a>
          </p>
        </div>
      </section>
    </main>
  </div>
</body>
</html>

