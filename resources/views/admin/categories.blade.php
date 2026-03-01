@extends('layouts.admin')

@section('title', 'Categories Management - Sincidentre Admin')

@section('page-title', 'Categories Management')

@section('content')
    <p>Manage the different types of incident categories used in Sincidentre.</p>

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
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td>{{ $category->id }}</td>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->created_at ? $category->created_at->format('Y-m-d') : 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">No categories found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

@push('scripts')
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
@endpush
