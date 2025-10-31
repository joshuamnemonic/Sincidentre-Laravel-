<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Report - Sincidentre</title>
    <link rel="stylesheet" href="{{ asset('css/usernewcss.css') }}">
</head>
<body>

  <!-- Sidebar -->

  <div class="sidebar">
    <h2>Sincidentre</h2>
    <a href="{{ route('dashboard') }}">Home</a>
    <a href="{{ route('newreport') }}" class="active">New Report</a>
    <a href="{{ route('myreports') }}">My Reports</a>
    <a href="{{ route('profile') }}">Profile</a>


<!-- Hidden Logout Form -->
<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
</form>
<a href="#" id="logout-btn">Logout</a>


  </div>

  <script>
  document.getElementById('logout-btn').addEventListener('click', function(e) {
      e.preventDefault();
      document.getElementById('logout-form').submit();
  });
  </script>

  <!-- Main Content -->

  <div class="dashboard">
    <div class="welcome">
        <h1>📝 New Report</h1>
        <p>Submit a new incident report below.</p>
    </div>


<!-- ✅ Success Message -->
@if(session('success'))
  <div class="alert-success">
    {{ session('success') }}
  </div>
@endif

<!-- ❌ Error Message -->
@if($errors->any())
  <div class="alert-error">
      <ul>
          @foreach($errors->all() as $error)
              <li>{{ $error }}</li>
          @endforeach
      </ul>
  </div>
@endif

<!-- ✅ Fixed Form -->
<div class="form-container animate">
  <form id="reportForm" action="{{ route('reports.store') }}" method="POST" enctype="multipart/form-data">
      @csrf

      <!-- Report Title -->
      <div class="form-group">
          <label for="title">Report Title / Subject *</label>
          <input type="text" id="title" name="title" value="{{ old('title') }}" required>
      </div>

      <!-- Category -->
      <div class="form-group">
          <label for="category">Category / Type of Incident *</label>
          <select id="category" name="category" required>
              <option value="">-- Select Category --</option>
              <option value="Bullying" {{ old('category') == 'Bullying' ? 'selected' : '' }}>Bullying</option>
              <option value="Lost Item" {{ old('category') == 'Lost Item' ? 'selected' : '' }}>Lost Item</option>
              <option value="Facility Issue" {{ old('category') == 'Facility Issue' ? 'selected' : '' }}>Facility Issue</option>
              <option value="Accident" {{ old('category') == 'Accident' ? 'selected' : '' }}>Accident</option>
              <option value="Harassment" {{ old('category') == 'Harassment' ? 'selected' : '' }}>Harassment</option>
              <option value="Theft" {{ old('category') == 'Theft' ? 'selected' : '' }}>Theft</option>
              <option value="Other" {{ old('category') == 'Other' ? 'selected' : '' }}>Other</option>
          </select>
      </div>

      <!-- Description -->
      <div class="form-group">
          <label for="description">Description *</label>
          <textarea id="description" name="description" rows="5" required>{{ old('description') }}</textarea>
      </div>

      <!-- Date & Time -->
      <div class="form-row">
          <div class="form-group half">
              <label for="incident_date">Date of Incident *</label>
              <input type="date" id="incident_date" name="incident_date" value="{{ old('incident_date') }}" required>
          </div>

          <div class="form-group half">
              <label for="incident_time">Time of Incident *</label>
              <input type="time" id="incident_time" name="incident_time" value="{{ old('incident_time') }}" required>
          </div>
      </div>

      <!-- Location -->
      <div class="form-group">
          <label for="location">Location *</label>
          <input type="text" id="location" name="location" value="{{ old('location') }}" required>
      </div>

      <!-- Evidence -->
      <div class="form-group">
          <label for="evidence">Upload Evidence (required)</label>
          <input type="file" id="evidence" name="evidence[]" accept="image/*,video/*,.pdf" multiple required> 
          <p class="hint">Max 50MB | Formats: JPG, PNG, PDF, MP4, AVI, MOV</p>
      </div>

      <!-- Buttons -->
      <div class="form-actions">
          <button type="submit" class="btn-submit">Submit Report</button>
          <a href="{{ route('dashboard') }}" class="btn-cancel">Cancel</a>
      </div>
  </form>
</div>


  </div>

  <script src="{{ asset('js/sincidentre.js') }}"></script>

</body>
</html>
