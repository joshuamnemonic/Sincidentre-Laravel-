<!DOCTYPE html>

<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sincidentre - User Details</title>
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
    <h1>👤 User Details</h1>
    <a href="{{ route('admin.users') }}">← Back to Users</a>
  </header>

  <section>
    <h2>Account Information</h2>
    <p><strong>User ID:</strong> {{ $user->id }}</p>
    <p><strong>Full Name:</strong> {{ $user->first_name }} {{ $user->last_name }}</p>
    <p><strong>Email:</strong> {{ $user->email }}</p>
    <p><strong>Role:</strong> {{ $user->role ?? 'User' }}</p>
    <p><strong>Status:</strong> {{ $user->status ?? 'Active' }}</p>
    <p><strong>Date Registered:</strong> {{ $user->created_at->format('F d, Y') }}</p>

    <h3>Actions</h3>
    <form method="POST" action="{{ route('admin.users.suspend', $user->id) }}">
      @csrf
      <button type="submit">Suspend User</button>
    </form>

    <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}">
      @csrf
      @method('DELETE')
      <button type="submit" onclick="return confirm('Are you sure you want to delete this user?')">Delete User</button>
    </form>
  </section>
</main>

  </div>
</body>
</html>
