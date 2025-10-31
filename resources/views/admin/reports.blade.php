<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sincidentre - Review Queue</title>
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
      <!-- Header with Search -->
      <header>
        <h1>Review Queue</h1>
        <form method="get" action="{{ route('admin.reports') }}">
          <input type="text" name="search" placeholder="Search reports…" value="{{ request('search') }}">
          <button type="submit">Search</button>
        </form>
      </header>

      <!-- Reports Table -->
      <section id="reports">
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Reporter</th>
                <th>Category</th>
                <th>Date</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
            @forelse ($reports as $report)
              <tr>
                <td>{{ $report->id }}</td>
                <td>{{ $report->title }}</td>
                <td>{{ $report->user->name ?? 'Unknown' }}</td>
                <td>{{ $report->category }}</td>
                <td>{{ $report->incident_date }}</td>
                <td>{{ $report->status }}</td>
                <td>
                  <a href="{{ route('admin.reports.show', $report->id) }}">View</a> |
                  
                  <form method="POST" action="{{ route('admin.reports.approve', $report->id) }}" style="display:inline;">
                    @csrf
                    @method('PATCH')
                    <button type="submit">Approve</button>
                  </form> |

                  <form method="POST" action="{{ route('admin.reports.reject', $report->id) }}" style="display:inline;">
                    @csrf
                    @method('PATCH')
                    <button type="submit">Reject</button>
                  </form>
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