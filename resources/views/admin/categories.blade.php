@extends('layouts.admin')

@section('title', 'Categories Management - Sincidentre Admin')

@section('page-title', 'Categories Management')

@section('header-search')
    <button onclick="openModal('addCategoryModal')" class="btn-add">➕ Add Category</button>
@endsection

@section('content')
    <p>Manage the different types of incident categories used in Sincidentre.</p>

    @if(session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif

    <!-- Add Category Modal -->
    <div id="addCategoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Category</h3>
                <span class="close" onclick="closeModal('addCategoryModal')">&times;</span>
            </div>
            <form id="addCategoryForm" action="{{ route('admin.categories.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="categoryName">Category Name *</label>
                    <input type="text" id="categoryName" name="categoryName" placeholder="e.g., Cheating/Plagiarism" required>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeModal('addCategoryModal')" class="btn-cancel">Cancel</button>
                    <button type="button" onclick="confirmAddCategory()" class="btn-submit">Add Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirm Add Category Modal -->
    <div id="confirmCategoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm New Category</h3>
                <span class="close" onclick="closeModal('confirmCategoryModal')">&times;</span>
            </div>
            <p style="padding: 1rem 1.875rem;">Are you sure you want to add <strong id="confirmCategoryName"></strong> as a new category?</p>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('confirmCategoryModal')">Cancel</button>
                <button type="button" class="btn-submit" onclick="document.getElementById('addCategoryForm').submit()">Yes, Add Category</button>
            </div>
        </div>
    </div>

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
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    function confirmAddCategory() {
        const name = document.getElementById('categoryName').value.trim();
        if (!name) {
            document.getElementById('categoryName').reportValidity();
            return;
        }
        document.getElementById('confirmCategoryName').textContent = '"' + name + '"';
        closeModal('addCategoryModal');
        openModal('confirmCategoryModal');
    }

    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal').forEach(function(m) {
                m.style.display = 'none';
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.modal').forEach(function(m) {
            document.body.appendChild(m);
        });
    });
</script>
@endpush
