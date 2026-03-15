@extends('layouts.admin')

@section('title', 'Edit User - Sincidentre Department Student Discipline Officer')

@section('page-title', '✏️ Edit User')

@section('header-search')
    <a href="{{ route('admin.users.show', $user->id) }}" class="btn-back">← Back to User Details</a>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section>
        <h2>Edit User Information</h2>
        <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="first_name">First Name *</label>
                <input type="text" 
                       id="first_name" 
                       name="first_name" 
                       value="{{ old('first_name', $user->first_name) }}" 
                       required>
            </div>

            <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input type="text" 
                       id="last_name" 
                       name="last_name" 
                       value="{{ old('last_name', $user->last_name) }}" 
                       required>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       value="{{ old('email', $user->email) }}" 
                       required>
            </div>

            <div class="form-group">
                <label for="department_id">Department *</label>
                <select id="department_id" name="department_id" required>
                    <option value="">-- Select Department --</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" 
                                {{ old('department_id', $user->department_id) == $department->id ? 'selected' : '' }}>
                            {{ $department->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" 
                       id="phone" 
                       name="phone" 
                       value="{{ old('phone', $user->phone) }}" 
                       placeholder="Optional">
            </div>

            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit" class="btn-primary">💾 Save Changes</button>
                <a href="{{ route('admin.users.show', $user->id) }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </section>
@endsection
