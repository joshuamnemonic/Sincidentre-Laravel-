<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sincidentre - Categories Management</title>
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
        <h1>📂 Categories Management</h1>
        <p>Manage the different types of incident categories used in Sincidentre.</p>
      </header>

      @if(session('success'))
        <div class="alert success">{{ session('success') }}</div>
      @endif

      <!-- Add Category Form -->
      <section>
        <h2>Add New Category</h2>
        <form action="{{ route('admin.categories.store') }}" method="POST" class="category-form">
          @csrf
          <label for="categoryName">Category Name:</label>
          <input type="text" id="categoryName" name="categoryName" required>
          <button type="submit">Add Category</button>
        </form>
      </section>

      <!-- Categories Table -->
      <section style="margin-top: 20px;">
        <h2>Existing Categories</h2>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Category Name</th>
                <th>Date Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($categories as $category)
              <tr>
                <td>{{ $category->id }}</td>
                <td>{{ $category->name }}</td>
                <td>{{ $category->created_at ? $category->created_at->format('Y-m-d') : 'N/A' }}</td>
                <td>
                  <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" onclick="return confirm('Delete this category?')">🗑️ Delete</button>
                  </form>
                </td>
              </tr>
              @endforeach
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
