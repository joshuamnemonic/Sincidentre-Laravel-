<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Report Details - Sincidentre</title>
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
    <a href="#" id="logout-btn">Logout</a>
  </div>

  <script>
  document.getElementById('logout-btn').addEventListener('click', function(e) {
      e.preventDefault();
      document.getElementById('logout-form').submit();
  });
  </script>

  <!-- Right Side Dashboard -->
  <div class="dashboard">
    <header>
      <h1>📄 Report Details</h1>
      <p>Report ID: #{{ $report->id }}</p>
    </header>

    <!-- Report Details Section -->
    <section id="report-details-view" class="animate">
      <h2>Report Information</h2>
      <div class="details-grid">
        <div class="detail-item">
          <label>Title</label>
          <p>{{ $report->title }}</p>
        </div>
        <div class="detail-item">
          <label>Category</label>
          <p>{{ $report->category }}</p>
        </div>
        <div class="detail-item">
          <label>Date</label>
          <p>{{ $report->incident_date }}</p>
        </div>
        <div class="detail-item">
          <label>Time</label>
          <p>{{ $report->incident_time }}</p>
        </div>
        <div class="detail-item">
          <label>Location</label>
          <p>{{ $report->location }}</p>
        </div>
        <div class="detail-item">
          <label>Status</label>
          <p><span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">{{ ucfirst($report->status) }}</span></p>
        </div>
        <div class="detail-item full-width">
          <label>Description</label>
          <p>{{ $report->description }}</p>
        </div>
      </div>
    </section>

    <!-- Evidence Section -->
    <section id="evidence-section" class="animate">
      <h2>📎 Submitted Evidence</h2>
      @if($report->evidence)
        @php
          $evidences = json_decode($report->evidence, true);
        @endphp
        @if(is_array($evidences) && count($evidences) > 0)
          <div class="evidence-grid">
            @foreach($evidences as $file)
              @php
                $extension = pathinfo($file, PATHINFO_EXTENSION);
              @endphp

              @if(in_array(strtolower($extension), ['jpg','jpeg','png','gif']))
                <div class="evidence-item">
                  <img src="{{ asset('storage/' . $file) }}" alt="Evidence Image">
                </div>
              @elseif(in_array(strtolower($extension), ['mp4','webm','ogg']))
                <div class="evidence-item">
                  <video controls>
                    <source src="{{ asset('storage/' . $file) }}" type="video/{{ $extension }}">
                    Your browser does not support the video tag.
                  </video>
                </div>
              @elseif(strtolower($extension) === 'pdf')
                <div class="evidence-item">
                  <iframe src="{{ asset('storage/' . $file) }}"></iframe>
                </div>
              @else
                <div class="evidence-item">
                  <a href="{{ asset('storage/' . $file) }}" target="_blank" class="file-link">📂 View File</a>
                </div>
              @endif
            @endforeach
          </div>
        @else
          <p class="no-data">No evidence files available.</p>
        @endif
      @else
        <p class="no-data">No evidence was submitted for this report.</p>
      @endif
    </section>

    <!-- Admin Response Section -->
    <section id="admin-response" class="animate">
      @if($report->assigned_to || $report->department || $report->target_date || $report->remarks)
        <h2>🛠 Admin Handling Details</h2>
        <div class="details-grid">
          <div class="detail-item">
            <label>Assigned To</label>
            <p>{{ $report->assigned_to ?? 'Not yet assigned' }}</p>
          </div>
          <div class="detail-item">
            <label>Department</label>
            <p>{{ $report->department ?? 'N/A' }}</p>
          </div>
          <div class="detail-item">
            <label>Target Date</label>
            <p>{{ $report->target_date ? \Carbon\Carbon::parse($report->target_date)->format('F d, Y') : 'No target date set' }}</p>
          </div>
          <div class="detail-item full-width">
            <label>Admin Remarks</label>
            <p>{{ $report->remarks ?? 'No remarks yet' }}</p>
          </div>
        </div>
      @else
        <h2>🕓 Awaiting Admin Response</h2>
        <p class="no-data">Your report is still pending review or has not been handled by the admin yet.</p>
      @endif
    </section>

    <!-- Back Link -->
    <div class="action-buttons">
      <a href="{{ route('myreports') }}" class="btn-back">⬅ Back to My Reports</a>
    </div>
  </div>

  <script src="{{ asset('js/sincidentre.js') }}"></script>
</body>
</html>