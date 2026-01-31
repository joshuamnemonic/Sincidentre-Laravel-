<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Activity Logs - Sincidentre Admin</title>
  <link rel="stylesheet" href="{{ asset('css/newcss.css') }}">
</head>
<body>
  <div class="layout">
    <!-- Sidebar -->
    <aside class="sidebar">
      <h2>Sincidentre Admin</h2>
      <nav>
        <ul>
          <li><a href="{{ route('admin.admindashboard') }}">Overview</a></li>
          <li><a href="{{ route('admin.reports') }}">Review Queue</a></li>
          <li><a href="{{ route('admin.handlereports') }}">Handle Reports</a></li>
          <li><a href="{{ route('admin.users') }}">Users</a></li>
          <li><a href="{{ route('admin.categories.index') }}">Categories</a></li>
          <li><a href="{{ route('admin.departments.index') }}">Departments</a></li>
          <li><a href="{{ route('admin.activitylogs') }}" class="active">Activity Logs</a></li>
        </ul>
      </nav>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">  Logout</button>
      </form>
    </aside>

    <!-- Main Content -->
    <main class="main">
      <!-- Header with Search -->
      <header>
        <h1>Activity Logs / Audit Trail</h1>
        <form method="get" action="{{ route('admin.activitylogs') }}">
          <input type="text" name="search" placeholder="Search activities…" value="{{ request('search') }}">
          <button type="submit">Search</button>
        </form>
      </header>

      <!-- Activity Logs Table -->
      <section id="activity-logs">
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Date & Time</th>
                <th>Admin</th>
                <th>Action</th>
                <th>Report ID</th>
                <th>Status Change</th>
                <th>Remarks</th>
              </tr>
            </thead>
            <tbody>
@forelse ($activities as $activity)
  <tr>
    <td>{{ $activity->created_at->format('M d, Y H:i') }}</td>
    <td>
      <strong>
        @if($activity->admin)
          {{ $activity->admin->name }}
        @else
          Unknown Admin
        @endif
      </strong>
    </td>
    <td>{{ $activity->action }}</td>
    <td>
      @if($activity->report)
        <a href="{{ route('admin.reports.show', $activity->report_id) }}">#{{ $activity->report_id }}</a>
      @else
        N/A
      @endif
    </td>
    <td>
      @if($activity->old_status && $activity->new_status)
        <span style="color: #999;">{{ ucfirst($activity->old_status) }}</span> 
        → 
        <span style="color: #007bff;">{{ ucfirst($activity->new_status) }}</span>
      @else
        {{ ucfirst($activity->new_status) ?? 'N/A' }}
      @endif
    </td>
    <td>{{ Str::limit($activity->remarks, 50) }}</td>
  </tr>
@empty
  <tr>
    <td colspan="6" style="text-align:center;">No activity logs found.</td>
  </tr>
@endforelse
</tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div style="margin-top: 20px;">
          {{ $activities->links() }}
        </div>
      </section>
    </main>
  </div>
</body>
</html>