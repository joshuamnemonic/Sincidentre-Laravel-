<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Reports - Sincidentre</title>
  <link rel="stylesheet" href="{{ asset('css/usernewcss.css') }}">
</head>
<body>

  <!-- Left Sidebar -->
  <div class="sidebar">
    <h2>Sincidentre</h2>
     <a href="{{ route('dashboard') }}" 
     class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Home</a>

  <a href="{{ route('newreport') }}" 
     class="{{ request()->routeIs('newreport') ? 'active' : '' }}">New Report</a>

  <a href="{{ route('myreports') }}" 
     class="{{ request()->routeIs('myreports') ? 'active' : '' }}">My Reports</a>

  <a href="{{ route('profile') }}" 
     class="{{ request()->routeIs('profile') ? 'active' : '' }}">Profile</a>

    <!-- Hidden logout form -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>

    <!-- Logout link -->
    <a href="#" id="logout-btn"> Logout</a>
  </div>

  <script>
  document.getElementById('logout-btn').addEventListener('click', function(e) {
      e.preventDefault();
      document.getElementById('logout-form').submit();
  });
  </script>

  <!-- Right Side Dashboard -->
  <div class="dashboard">
    <div class="welcome">
      <h1>📂 My Reports</h1>
    </div>

    <!-- Reports Table -->
    <div class="recent-reports animate">
      <table class="report-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Category</th>
            <th>Date</th>
            <th>Time</th>
            <th>Location</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse($myReports as $report)
          <tr onclick="window.location='{{ route('report.show', $report->id) }}'" style="cursor: pointer;">
            <td>{{ $report->id }}</td>
            <td>{{ $report->title }}</td>
            <td>{{ $report->category }}</td>
            <td>{{ $report->incident_date }}</td>
            <td>{{ $report->incident_time }}</td>
            <td>{{ $report->location }}</td>
            <td>
              <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                {{ $report->status }}
              </span>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7">No reports found.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <script src="{{ asset('js/sincidentre.js') }}"></script>
</body>
</html>
