@extends('layouts.admin')

@section('title', 'Department Student Discipline Officer Profile - Sincidentre')

@section('page-title', '')

@section('content')
    @php
        $user = Auth::user();
        $registrantType = strtolower(trim((string) ($user->registrant_type ?? '')));
        $accountRoleLabel = $user->is_top_management
            ? 'Top Management'
            : ($user->is_department_student_discipline_officer
                ? 'Department Student Discipline Officer'
                : match ($registrantType) {
                    'student' => 'Student',
                    'faculty' => 'Faculty',
                    'employee_staff' => 'Employee/Staff',
                    'employee/staff' => 'Employee/Staff',
                    'employee staff' => 'Employee/Staff',
                    default => 'N/A',
                });
        $profilePhoto = $user->profile_picture ? asset($user->profile_picture) : asset('images/default-avatar.png');
        $displayOffice = $user->department->name ?? ($user->employee_office ?? 'N/A');
    @endphp

    <div class="profile-container">

        <!-- Success Message -->
        @if(session('success'))
            <div class="alert alert-success" style="background: rgba(34, 197, 94, 0.18); border: 1px solid rgba(34, 197, 94, 0.45); color: #4ade80; padding: 1rem 1.5rem; border-radius: 0.6rem; margin-bottom: 1.5rem;">
                ✓ {{ session('success') }}
            </div>
            <script>
                setTimeout(() => {
                    let alertBox = document.querySelector('.alert-success');
                    if (alertBox) {
                        alertBox.style.opacity = '0';
                        alertBox.style.transition = 'opacity 0.3s ease';
                        setTimeout(() => alertBox.style.display = 'none', 300);
                    }
                }, 3000);
            </script>
        @endif

        <!-- Error Messages -->
        @if ($errors->any())
            <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.18); border: 1px solid rgba(239, 68, 68, 0.45); color: #f87171; padding: 1rem 1.5rem; border-radius: 0.6rem; margin-bottom: 1.5rem;">
                <ul style="margin: 0; padding-left: 1.2rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Profile Header Section -->
        <section style="text-align: center; margin-bottom: 2rem;">
            <div style="display: inline-block; position: relative;">
                <img src="{{ $profilePhoto }}" 
                     alt="Profile Picture" 
                     style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid rgba(255,255,255,0.3); box-shadow: 0 8px 24px rgba(0,0,0,0.2);"
                     id="profile-picture-preview-main">
            </div>
            
            <h2 style="color: white; margin-top: 1rem; font-size: 1.8rem; font-weight: 700;">
                {{ $user->first_name }} {{ $user->last_name }}
            </h2>
            <p style="color: rgba(255,255,255,0.8); font-size: 0.95rem; margin: 0.5rem 0;">
                📧 {{ $user->email }}
            </p>
            <p style="color: rgba(255,255,255,0.7); font-size: 0.9rem; margin: 0.25rem 0;">
                🏢 {{ $displayOffice }}
            </p>
            <span class="role-badge admin" style="margin-top: 0.75rem; display: inline-block;">
                {{ $accountRoleLabel }}
            </span>
        </section>

        <!-- Account Settings -->
        <section class="settings-section">
            <h3>Account Settings</h3>
            <h4>Profile Details</h4>
            <form class="settings-form" method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" novalidate>
                @csrf
                @method('PATCH')
                <input type="hidden" name="form_type" value="profile">

                <div class="form-group">
                    <label for="profile_picture">Profile Picture</label>
                    <div class="profile-file-picker">
                        <input
                            type="file"
                            id="profile_picture"
                            name="profile_picture"
                            class="visually-hidden-file"
                            accept="image/jpeg,image/png,image/webp"
                        >
                        <label for="profile_picture" class="profile-file-button">Choose Profile Picture</label>
                        <small id="profile-picture-file-name" class="profile-selected-file" aria-live="polite">No profile chosen</small>
                    </div>
                    @error('profile_picture')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                    <small class="form-hint">Accepted: JPG, PNG, WEBP. Max size: 2MB.</small>
                </div>

                <div class="form-group preview-group">
                    <label>Preview</label>
                    <img src="{{ $profilePhoto }}" alt="Selected profile picture preview" class="profile-pic profile-pic-preview" id="profile-picture-preview-form">
                </div>

                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" value="{{ $user->first_name }}" readonly>
                    <small class="form-hint">Managed by your registration profile.</small>
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" value="{{ $user->last_name }}" readonly>
                    <small class="form-hint">Managed by your registration profile.</small>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                    <small class="form-hint">Use an active email that is not used by another account.</small>
                </div>

                <div class="form-group">
                    <label for="phone">Contact Number</label>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        inputmode="numeric"
                        autocomplete="tel"
                        value="{{ old('phone', $user->phone ?? '') }}"
                        placeholder="e.g. +639171234567"
                    >
                    @error('phone')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                    <small class="form-hint">You may use digits, spaces, plus sign, dashes, and parentheses.</small>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="save-btn">Save Profile Details</button>
                    <button type="reset" class="cancel-btn">Reset</button>
                </div>
            </form>

            <h4>Change Password</h4>
            <form class="settings-form" method="POST" action="{{ route('admin.profile.update') }}" novalidate>
                @csrf
                @method('PATCH')
                <input type="hidden" name="form_type" value="password">

                <div class="form-group password-group">
                    <label for="current_password">Current Password</label>
                    <div class="password-input-wrap">
                        <input type="password" id="current_password" name="current_password" autocomplete="current-password">
                        <button type="button" class="toggle-password" data-target="current_password" aria-label="Show current password">Show</button>
                    </div>
                    @error('current_password')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group password-group">
                    <label for="new_password">New Password</label>
                    <div class="password-input-wrap">
                        <input type="password" id="new_password" name="new_password" autocomplete="new-password">
                        <button type="button" class="toggle-password" data-target="new_password" aria-label="Show new password">Show</button>
                    </div>
                    @error('new_password')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                    <small class="form-hint">Use at least 8 characters with letters and numbers.</small>
                </div>

                <div class="form-group password-group">
                    <label for="new_password_confirmation">Confirm New Password</label>
                    <div class="password-input-wrap">
                        <input type="password" id="new_password_confirmation" name="new_password_confirmation" autocomplete="new-password">
                        <button type="button" class="toggle-password" data-target="new_password_confirmation" aria-label="Show password confirmation">Show</button>
                    </div>
                    @error('new_password_confirmation')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-buttons">
                    <button type="submit" class="save-btn">Update Password</button>
                    <button type="reset" class="cancel-btn">Reset</button>
                </div>
            </form>
        </section>
    </div>
@endsection

@push('styles')
<style>
    .profile-container {
        animation: fadeIn 0.45s ease-out both;
    }

    .settings-section h3 {
        color: #ffffff;
        font-size: 1.4rem;
        margin: 0;
        padding: 1.4rem 2rem 0.75rem;
    }

    .settings-section h4 {
        color: #ffffff;
        margin: 0;
        padding: 0 2rem 1rem;
        font-size: 1.1rem;
        font-weight: 700;
    }

    .settings-form {
        padding: 0 2rem 2rem;
    }

    .settings-form + h4 {
        margin-top: 0.5rem;
    }

    .profile-file-picker {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .visually-hidden-file {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        pointer-events: none;
    }

    .profile-file-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 44px;
        padding: 0 1.25rem;
        border-radius: 999px;
        border: none;
        background: #2563eb;
        color: #fff;
        font-weight: 600;
        cursor: pointer;
    }

    .profile-selected-file,
    .form-hint {
        color: rgba(255, 255, 255, 0.78);
        font-size: 0.8rem;
    }

    .settings-form input[type='text'],
    .settings-form input[type='email'],
    .settings-form input[type='tel'],
    .settings-form input[type='password'] {
        width: 100%;
        padding: 0.85rem 1rem;
        border-radius: 0.75rem;
        border: 2px solid rgba(255, 255, 255, 0.26);
        background: #ffffff;
        color: #111827;
        -webkit-text-fill-color: #111827;
    }

    .settings-form input[readonly] {
        background: #f3f4f6;
        color: #4b5563;
        -webkit-text-fill-color: #4b5563;
    }

    .settings-form input::placeholder {
        color: #6b7280;
    }

    .preview-group .profile-pic-preview {
        width: 90px;
        height: 90px;
        margin-top: 0.5rem;
    }

    .form-buttons {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .form-buttons .save-btn,
    .form-buttons .cancel-btn {
        flex: 1;
        min-height: 44px;
        padding: 0 1.25rem;
        border-radius: 999px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .save-btn {
        background: #2563eb;
        color: #ffffff;
    }

    .save-btn:hover {
        background: #1d4ed8;
    }

    .cancel-btn {
        background: #4b5563;
        color: #ffffff;
    }

    .cancel-btn:hover {
        background: #374151;
    }

    .password-input-wrap {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .password-input-wrap input {
        flex: 1;
    }

    .toggle-password {
        flex: 0 0 auto;
        min-height: 44px;
        padding: 0 1rem;
        border-radius: 999px;
        border: none;
        background: #2563eb;
        color: #fff;
        font-weight: 600;
        cursor: pointer;
    }

    .toggle-password:hover {
        background: #1d4ed8;
    }

    .field-error {
        display: block;
        margin-top: 0.35rem;
        color: #fecaca;
        font-weight: 600;
    }
    
    input[type="file"]::file-selector-button {
        padding: 0.5rem 1rem;
        background: rgba(255,255,255,0.2);
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: 0.4rem;
        color: white;
        cursor: pointer;
        margin-right: 0.5rem;
        transition: background 0.2s ease;
    }
    
    input[type="file"]::file-selector-button:hover {
        background: rgba(255,255,255,0.3);
    }

    #profile-picture-preview-main {
        transition: all 0.3s ease;
    }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        const profileInput = document.getElementById('profile_picture');
        const previewMain = document.getElementById('profile-picture-preview-main');

        if (!profileInput || !previewMain) {
            return;
        }

        profileInput.addEventListener('change', function (event) {
            const file = event.target.files && event.target.files[0];
            if (!file) {
                return;
            }

            const reader = new FileReader();
            reader.onload = function (readerEvent) {
                previewMain.src = String(readerEvent.target && readerEvent.target.result ? readerEvent.target.result : previewMain.src);
            };
            reader.readAsDataURL(file);
        });

        document.querySelectorAll('.toggle-password').forEach(function (button) {
            button.addEventListener('click', function () {
                const targetId = button.getAttribute('data-target');
                const input = targetId ? document.getElementById(targetId) : null;
                if (!input) {
                    return;
                }

                const nextType = input.type === 'password' ? 'text' : 'password';
                input.type = nextType;
                button.textContent = nextType === 'password' ? 'Show' : 'Hide';
            });
        });
    })();
</script>
@endpush

