@extends('layouts.admin')

@section('title', 'Categories Management - Sincidentre Department Student Discipline Officer')

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
                    <label for="mainCategoryCode">Main Category Code *</label>
                    <select id="mainCategoryCode" name="mainCategoryCode" required>
                        <option value="">-- Select Main Category --</option>
                        <option value="A">A - Offenses Against Persons and Public Order</option>
                        <option value="B">B - Offenses Against Property and Security</option>
                        <option value="C">C - Academic and Technological Misconduct</option>
                        <option value="D">D - Behavioral and Substance Violations</option>
                        <option value="E">E - Administrative and General Violations</option>
                        <option value="F">F - Offenses Against Public Morals</option>
                        <option value="G">G - Technical and Facility Issues</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="mainCategoryName">Main Category Name *</label>
                    <input type="text" id="mainCategoryName" name="mainCategoryName" readonly required>
                </div>
                <div class="form-group">
                    <label for="categoryName">Category Name *</label>
                    <input type="text" id="categoryName" name="categoryName" placeholder="e.g., Cheating/Plagiarism" required>
                </div>
                <div class="form-group">
                    <label for="classification">Classification *</label>
                    <select id="classification" name="classification" required>
                        <option value="">-- Select Classification --</option>
                        <option value="Minor">Minor</option>
                        <option value="Major">Major</option>
                        <option value="Grave">Grave</option>
                    </select>
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
            <p style="padding: 1rem 1.875rem;">Are you sure you want to add this category?</p>
            <p style="padding: 0 1.875rem 1rem; color: #555;"><strong id="confirmCategorySummary"></strong></p>
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
                        <th>Main</th>
                        <th>Category Name</th>
                        <th>Classification</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td>{{ $category->main_category_code }} - {{ $category->main_category_name }}</td>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->classification }}</td>
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
        const mainCode = document.getElementById('mainCategoryCode').value.trim();
        const mainName = document.getElementById('mainCategoryName').value.trim();
        const name = document.getElementById('categoryName').value.trim();
        const classification = document.getElementById('classification').value.trim();
        if (!mainCode) {
            document.getElementById('mainCategoryCode').reportValidity();
            return;
        }
        if (!name) {
            document.getElementById('categoryName').reportValidity();
            return;
        }
        if (!classification) {
            document.getElementById('classification').reportValidity();
            return;
        }
        document.getElementById('confirmCategorySummary').textContent = mainCode + ' - ' + mainName + ' / ' + name + ' (' + classification + ')';
        closeModal('addCategoryModal');
        openModal('confirmCategoryModal');
    }

    const mainCategoryMap = {
        A: 'Offenses Against Persons and Public Order',
        B: 'Offenses Against Property and Security',
        C: 'Academic and Technological Misconduct',
        D: 'Behavioral and Substance Violations',
        E: 'Administrative and General Violations',
        F: 'Offenses Against Public Morals',
        G: 'Technical and Facility Issues'
    };

    document.getElementById('mainCategoryCode').addEventListener('change', function () {
        const value = this.value;
        document.getElementById('mainCategoryName').value = mainCategoryMap[value] || '';
    });

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

