<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sincidentre - Handle Report #{{ $report->id }}</title>
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
          <li><a href="{{ route('admin.handlereports') }}" class="active">Handle Reports</a></li>
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
        <h1>📝 Handle Report #{{ $report->id }}</h1>
        <p>Review, assign, and update the report details below.</p>
      </header>

      <section id="report-details">
        <h2>Report Information</h2>
        <table border="1" cellspacing="0" cellpadding="8">
          <tr>
            <th>Title</th>
            <td>{{ $report->title }}</td>
          </tr>
          <tr>
            <th>Category</th>
            <td>{{ $report->category }}</td>
          </tr>
          <tr>
            <th>Description</th>
            <td>{{ $report->description }}</td>
          </tr>
          <tr>
            <th>Status</th>
            <td>{{ ucfirst($report->status) }}</td>
          </tr>
        </table>
      </section>

      <section id="handle-form" style="margin-top: 20px;">
        <h2>Update Report Details</h2>

        <form action="{{ route('admin.handlereports.update', $report->id) }}" method="POST">
          @csrf
          <div style="margin-bottom: 10px;">
            <label><strong>Assign to:</strong></label><br>
            <input type="text" name="assigned_to" value="{{ $report->assigned_to ?? '' }}" required>
          </div>

          <div style="margin-bottom: 10px;">
            <label><strong>Department:</strong></label><br>
            <input type="text" name="department" value="{{ $report->department ?? '' }}">
          </div>

          <div style="margin-bottom: 10px;">
            <label><strong>Target Date:</strong></label><br>
            <input type="date" name="target_date" value="{{ $report->target_date ?? '' }}">
          </div>

          <div style="margin-bottom: 10px;">
            <label><strong>Remarks:</strong></label><br>
            <textarea name="remarks" rows="4" cols="50">{{ $report->remarks ?? '' }}</textarea>
          </div>

          <div style="margin-bottom: 10px;">
            <label><strong>Status:</strong></label><br>
            <select name="status">
              <option value="approved" {{ $report->status == 'approved' ? 'selected' : '' }}>Approved</option>
              <option value="under review" {{ $report->status == 'under review' ? 'selected' : '' }}>Under Review</option>
              <option value="resolved" {{ $report->status == 'resolved' ? 'selected' : '' }}>Resolved</option>
            </select>
          </div>

          <button type="submit">💾 Save Changes</button>
        </form>
      </section>
    </main>
  </div>
</body>
</html>
