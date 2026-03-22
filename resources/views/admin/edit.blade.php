@extends('layouts.admin')

@section('title', 'Edit User - Sincidentre Department Student Discipline Officer')

@section('page-title', '✏️ Edit User')

@section('header-search')
    <a href="{{ route('admin.users.show', $user->id) }}" class="btn-back">← Back to User Details</a>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
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

            <div class="edit-user-actions">
                <button type="submit" class="btn-primary">💾 Save Changes</button>
                <a href="{{ route('admin.users.show', $user->id) }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </section>
@endsection

@push('styles')
<style>
    /* ── Constrain the whole form section ── */
    section {
        max-width: 540px;
    }

    /* ── Form inputs consistent with admin UI ── */
    section .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        margin-bottom: 1rem;
        max-width: 480px;
    }

    section .form-group label {
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: rgba(255,255,255,0.7);
    }

    section .form-group input,
    section .form-group select {
        width: 100%;
        min-height: 44px;
        padding: 0.75rem 1rem;
        background: #ffffff;
        border: 2px solid var(--glass-border);
        border-radius: 0.6rem;
        color: #1f2937;
        font-size: 0.9rem;
        font-family: inherit;
        box-sizing: border-box;
    }

    section .form-group input:focus,
    section .form-group select:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
    }

    section .form-group input::placeholder { color: #9ca3af; }

    section .form-group select option {
        color: #1f2937;
        background: #ffffff;
    }

    /* ── Action buttons ── */
    .edit-user-actions {
        display: flex;
        gap: 0.65rem;
        flex-wrap: wrap;
        margin-top: 1.5rem;
    }

    .edit-user-actions .btn-primary,
    .edit-user-actions .btn-secondary {
        min-height: 42px;
        padding: 0.65rem 1.5rem;
        border-radius: 0.6rem;
        font-weight: 600;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        box-sizing: border-box;
    }

    @media (max-width: 768px) {
        section .form-group {
            max-width: 100%;
        }

        .edit-user-actions {
            flex-direction: column;
        }

        .edit-user-actions .btn-primary,
        .edit-user-actions .btn-secondary {
            width: 100%;
        }
    }
</style>
@endpush