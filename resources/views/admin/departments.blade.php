@extends('layouts.admin')

@section('title', 'Departments - Sincidentre Department Student Discipline Officer')

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
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $department)
                        <tr>
                            <td>
                                <strong>{{ $department->name }}</strong>
                            </td>
                            <td>
                                <span class="badge">{{ $department->regular_users_count }}</span>
                                {{ Str::plural('user', $department->regular_users_count) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" style="text-align:center; padding: 40px;">
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
            <form id="addDepartmentForm" action="{{ route('admin.departments.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="add_name">Department Name *</label>
                    <input type="text" 
                           id="add_name"
                           name="name" 
                           placeholder="e.g., College of Engineering" 
                           required>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeModal('addDepartmentModal')" class="btn-cancel">
                        Cancel
                    </button>
                    <button type="button" onclick="confirmAddDepartment()" class="btn-submit">Add Department</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirm Add Department Modal -->
    <div id="confirmDepartmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm New Department</h3>
                <span class="close" onclick="document.getElementById('confirmDepartmentModal').style.display='none'">&times;</span>
            </div>
            <p style="padding: 1rem 1.875rem;">Are you sure you want to add <strong id="confirmDepartmentName"></strong> as a new department?</p>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="document.getElementById('confirmDepartmentModal').style.display='none'">Cancel</button>
                <button type="button" class="btn-submit" onclick="document.getElementById('addDepartmentForm').submit()">Yes, Add Department</button>
            </div>
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

    function confirmAddDepartment() {
        const name = document.getElementById('add_name').value.trim();
        if (!name) {
            document.getElementById('add_name').reportValidity();
            return;
        }
        document.getElementById('confirmDepartmentName').textContent = '"' + name + '"';
        closeModal('addDepartmentModal');
        document.getElementById('confirmDepartmentModal').style.display = 'flex';
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.modal').forEach(function(m) {
            document.body.appendChild(m);
        });
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal').forEach(function(m) {
                m.style.display = 'none';
            });
        }
    });
</script>
@endpush
