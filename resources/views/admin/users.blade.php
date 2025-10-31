<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sincidentre - User Management</title>
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
      <!-- Header -->
      <header>
        <h1>👥 User Management</h1>
        <p>Manage all registered users of Sincidentre.</p>
      </header>

      <!-- Users Table -->
      <section>
        <h2>Registered Users</h2>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>User ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Date Registered</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
  @forelse($users as $user)
    <tr>
      <td>{{ $user->id }}</td>
      <td>{{ $user->name }}</td>
      <td>{{ $user->email }}</td>
      <td>{{ $user->role ? ucfirst($user->role) : ($user->is_admin ? 'Admin' : 'User') }}</td>
      <td>{{ $user->created_at->format('Y-m-d') }}</td>
      <td>{{ $user->status ?? 'Active' }}</td>
      <td>
        <a href="{{ route('admin.users.show', $user->id) }}">View</a> | 
        <form action="{{ route('admin.users.suspend', $user->id) }}" method="POST" style="display:inline;">
          @csrf
          <button type="submit" onclick="return confirm('Suspend this user?')">Suspend</button>
        </form> | 
        <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" style="display:inline;">
          @csrf
          @method('DELETE')
          <button type="submit" onclick="return confirm('Are you sure you want to delete this user?')">Delete</button>
        </form>
      </td>
    </tr>
  @empty
    <tr>
      <td colspan="7">No users found.</td>
    </tr>
  @endforelse
</tbody>
          </table>
        </div>
      </section>
    </main>
  </div>

  <script>
    const sidebar = document.querySelector('.sidebar');
    const menuBtn = document.createElement('button');
    menuBtn.className = 'mobile-menu-toggle';
    menuBtn.innerHTML = '☰';
    document.body.appendChild(menuBtn);
    
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
    
    menuBtn.addEventListener('click', () => {
      sidebar.classList.toggle('active');
      overlay.classList.toggle('active');
    });
    
    overlay.addEventListener('click', () => {
      sidebar.classList.remove('active');
      overlay.classList.remove('active');
    });
  </script>
</body>
</html>
