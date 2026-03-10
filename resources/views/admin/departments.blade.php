@extends('layouts.admin')

@section('title', 'Departments - Sincidentre Admin')

@section('page-title', 'Department Management')

@section('header-search')
    <button onclick="openModal('addDepartmentModal')" class="btn-add">➕ Add Department</button>
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

    <!-- Stats Summary -->
    <div class="stats-grid" style="margin-bottom: 2rem;">
        <div class="stat-card">
            <h4>Total Departments</h4>
            <p class="stat-number">{{ $departments->count() }}</p>
        </div>
        <div class="stat-card">
            <h4>Total Users</h4>
            <p class="stat-number">{{ $departments->sum('regular_users_count') }}</p>
        </div>
    </div>

    <!-- Departments Table -->
    <section id="departments">
        <div class="section-header">
            <h2>All Departments</h2>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Department Name</th>
                        <th>Total Users</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $department)
                        <tr>
                    
                            <td>
                                <strong>{{ $department->name }}</strong>
                                @if($department->description)
                                    <br>
                                    <small style="color: #999;">{{ Str::limit($department->description, 60) }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge">{{ $department->regular_users_count }}</span>
                                {{ Str::plural('user', $department->regular_users_count) }}
                            </td>
                            <td>
                                {{ $department->created_at->format('M d, Y') }}
                                <br>
                                <small style="color: #999;">{{ $department->created_at->diffForHumans() }}</small>
                            </td>
                            <td>
                                <button onclick="openModal('editDepartmentModal{{ $department->id }}')" class="btn-edit">
                                    ✏️ Edit
                                </button>
                                <form action="{{ route('admin.departments.destroy', $department->id) }}" 
                                      method="POST" 
                                      style="display:inline;"
                                      onsubmit="return confirm('Are you sure you want to delete {{ $department->name }}? This action cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-delete">🗑️ Delete</button>
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
                                        <label for="edit_name_{{ $department->id }}">Department Name *</label>
                                        <input type="text" 
                                               id="edit_name_{{ $department->id }}"
                                               name="name" 
                                               value="{{ $department->name }}" 
                                               placeholder="e.g., College of Engineering"
                                               required>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit_description_{{ $department->id }}">Description (Optional)</label>
                                        <textarea name="description" 
                                                  id="edit_description_{{ $department->id }}"
                                                  rows="3" 
                                                  placeholder="Brief description of this department">{{ $department->description }}</textarea>
                                        <small style="color: #666;">This will appear as a subtitle in the table</small>
                                    </div>
                                    <div class="modal-actions">
                                        <button type="button" onclick="closeModal('editDepartmentModal{{ $department->id }}')" class="btn-cancel">
                                            Cancel
                                        </button>
                                        <button type="submit" class="btn-submit">Update Department</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center; padding: 40px;">
                                <p style="color: #999; margin: 0;">No departments found.</p>
                                <small>Click "Add Department" to create your first department!</small>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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
                    <label for="add_name">Department Name *</label>
                    <input type="text" 
                           id="add_name"
                           name="name" 
                           placeholder="e.g., College of Engineering" 
                           required>
                </div>
                <div class="form-group">
                    <label for="add_description">Description (Optional)</label>
                    <textarea name="description" 
                              id="add_description"
                              rows="3" 
                              placeholder="Brief description of this department"></textarea>
                    <small style="color: #666;">This will appear as a subtitle in the table</small>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeModal('addDepartmentModal')" class="btn-cancel">
                        Cancel
                    </button>
                    <button type="submit" class="btn-submit">Add Department</button>
                </div>
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

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);

    // Prevent accidental form submission on Enter key in textarea
    document.querySelectorAll('textarea').forEach(textarea => {
        textarea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.stopPropagation();
            }
        });
    });
</script>
@endpush