@extends('layouts.admin')

@section('title', 'Departments - Sincidentre Admin')

@section('page-title', 'Department Management')

@section('header-search')
    <button onclick="openModal('addDepartmentModal')">➕ Add Department</button>
@endsection

@section('content')
    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <!-- Departments Table -->
    <section id="departments">
        <h2>All Departments</h2>
        <table border="1" cellspacing="0" cellpadding="8">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Department Name</th>
                    <th>Description</th>
                    <th>Total Users</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($departments as $department)
                    <tr>
                        <td>{{ $department->id }}</td>
                        <td><strong>{{ $department->name }}</strong></td>
                        <td>{{ $department->description ?? 'N/A' }}</td>
                        <td>{{ $department->regular_users_count }} {{ Str::plural('user', $department->regular_users_count) }}</td> <!-- ✅ UPDATED -->
                        <td>{{ $department->created_at->format('M d, Y') }}</td>
                        <td>
                            <button onclick="openModal('editDepartmentModal{{ $department->id }}')">Edit</button>
                            <form action="{{ route('admin.departments.destroy', $department->id) }}" 
                                  method="POST" 
                                  style="display:inline;"
                                  onsubmit="return confirm('Are you sure you want to delete this department?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>

                    <!-- Edit Department Modal -->
                    <div id="editDepartmentModal{{ $department->id }}" class="modal">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3>Edit Department</h3>
                                <span class="close" onclick="closeModal('editDepartmentModal{{ $department->id }}')">&times;</span>
                            </div>
                            <form action="{{ route('admin.departments.update', $department->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="form-group">
                                    <label>Department Name *</label>
                                    <input type="text" name="name" value="{{ $department->name }}" required>
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" rows="3">{{ $department->description }}</textarea>
                                </div>
                                <button type="button" onclick="closeModal('editDepartmentModal{{ $department->id }}')">Cancel</button>
                                <button type="submit">Update Department</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;">No departments found. Add your first department!</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <!-- Add Department Modal -->
    <div id="addDepartmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Department</h3>
                <span class="close" onclick="closeModal('addDepartmentModal')">&times;</span>
            </div>
            <form action="{{ route('admin.departments.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>Department Name *</label>
                    <input type="text" name="name" placeholder="e.g., College of Engineering" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Optional description"></textarea>
                </div>
                <button type="button" onclick="closeModal('addDepartmentModal')">Cancel</button>
                <button type="submit">Add Department</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
</script>
@endpush