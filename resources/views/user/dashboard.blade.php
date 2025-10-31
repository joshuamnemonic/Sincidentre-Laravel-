<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Dashboard - Sincidentre</title>
    <script src="sinci.js"></script>
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
    Welcome, {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}!
</div>


    <div class="cards">
  <div class="card animate">
    <h3>Total Reports</h3>
    <p id="total-reports">{{ $totalReports }}</p>
  </div>
  <div class="card animate">
    <h3>Pending</h3>
    <p id="pending-reports">{{ $pendingReports }}</p>
  </div>
  <div class="card animate">
    <h3>Resolved</h3>
    <p id="resolved-reports">{{ $resolvedReports }}</p>
  </div>
  <div class="card animate">
  <h3>Rejected</h3>
  <p id="rejected-reports">{{ $rejectedReports }}</p>
</div>
  <div class="card animate">
  <h3>Approved</h3>
  <p id="rejected-reports">{{ $approvedReports }}</p>
  </div>

</div>

   <div class="recent-reports animate">
    <h3>Recent Reports</h3>

    <form method="GET" action="{{ route('dashboard') }}">
        <input type="text" name="search" id="search-input" placeholder="🔍 Search reports..."
               value="{{ request('search') }}" />
        <button type="submit">Search</button>
    </form>
    <table id="reports-table">
        <thead>
            <tr>
                <th>Report ID</th>
                <th>Title</th>
                <th>Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentReports as $report)
                <tr>
                    <td>#{{ $report->id }}</td>
                    <td>{{ $report->title }}</td>
                    <td>{{ $report->submitted_at->format('F d, Y') }}</td>
                    <td>
                        <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                            {{ $report->status }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

  </div>

  <script src="{{ asset('js/sincidentre.js') }}"></script>
</body>
</html>
