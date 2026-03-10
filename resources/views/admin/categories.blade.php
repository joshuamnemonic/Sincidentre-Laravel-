@extends('layouts.admin')

@section('title', 'Categories Management - Sincidentre Admin')

@section('page-title', 'Categories Management')

@section('content')
    <p>Manage the different types of incident categories used in Sincidentre.</p>

    @if(session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif

    <!-- Add Category Form -->
    <section style="margin-bottom: 30px;">
        <h2>Add New Category</h2>
        <form action="{{ route('admin.categories.store') }}" method="POST" class="category-form" style="display: flex; gap: 10px; align-items: center;">
            @csrf
            <label for="categoryName" style="margin: 0; font-weight: 500;">Category Name:</label>
            <input type="text" id="categoryName" name="categoryName" required style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; width: 250px;">
            <button type="submit" style="padding: 8px 16px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 500; white-space: nowrap;">Add Category</button>
        </form>
    </section>

    <!-- Categories Table -->
    <section style="margin-top: 20px;">
        <h2>Existing Categories</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Category Name</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
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
