@extends('layouts.app')

@section('title', 'User Profile - Sincidentre')

@section('content')
    @php
        $user = Auth::user();
        $registrantType = strtolower(trim((string) ($user->registrant_type ?? '')));
        $roleMap = [
            'student' => 'Student',
            'faculty' => 'Faculty',
            'employee_staff' => 'Employee/Staff',
            'employee/staff' => 'Employee/Staff',
            'employee staff' => 'Employee/Staff',
        ];
        $displayRole = $roleMap[$registrantType] ?? ($registrantType !== '' ? ucwords(str_replace('_', ' ', $registrantType)) : 'N/A');
        $displayOffice = $user->department->name ?? ($user->employee_office ?? 'N/A');
        $profilePhoto = $user->profile_picture ? asset($user->profile_picture) : asset('images/default-avatar.png');
    @endphp

    <div class="profile-container animate">

        @if(session('success'))
            <div class="alert alert-success" role="status" aria-live="polite">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" role="alert" aria-live="assertive">
                <strong>Please review the highlighted fields below.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="profile-header">
            <div style="display: inline-block; position: relative;">
                <img src="{{ $profilePhoto }}" alt="Profile Picture" class="profile-pic" id="profile-picture-preview-main">
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
            <span class="user-role-badge" style="margin-top: 0.75rem; display: inline-block;">
                {{ $displayRole }}
            </span>
        </div>

        <div class="settings-section">
            <h3>⚙️ Account Settings</h3>
            <h4>Profile Details</h4>
            <form class="settings-form" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" novalidate>
                @csrf
                @method('PATCH')
                <input type="hidden" name="form_type" value="profile">

                <div class="form-group">
                    <label for="profile_picture">Profile Picture</label>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/webp">
                    @error('profile_picture')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                    <small class="form-hint">Accepted: JPG, PNG, WEBP. Max size: 2MB.</small>
                </div>

                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" name="remove_profile_picture" value="1" {{ old('remove_profile_picture') ? 'checked' : '' }}>
                        Remove current profile picture
                    </label>
                    @error('remove_profile_picture')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
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
                    <input type="email" id="email" value="{{ $user->email }}" readonly>
                    <small class="form-hint">Email changes require administrator verification.</small>
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
            <form class="settings-form" method="POST" action="{{ route('profile.update') }}" novalidate>
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
                    <small class="form-hint" id="password-strength-hint">Use at least 8 characters with letters and numbers.</small>
                    <small class="form-hint" id="password-strength-result" aria-live="polite"></small>
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
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .user-role-badge {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.35);
            color: #ffffff;
            padding: 0.45rem 0.85rem;
            border-radius: 999px;
            font-size: 0.84rem;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .settings-form input[type='tel'] {
            width: 100%;
            padding: 1rem 1.25rem;
            background: rgba(255, 255, 255, 0.25);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 0.75rem;
            color: white;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .settings-form input[type='tel']:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.35);
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.1);
            transform: translateY(-1px);
        }

        .settings-form + h4 {
            margin-top: 2.25rem;
        }

        .checkbox-group input[type='checkbox'] {
            width: auto;
            margin-right: 0.5rem;
        }

        .preview-group .profile-pic-preview {
            width: 90px;
            height: 90px;
            margin-top: 0.5rem;
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
            padding: 0.65rem 0.9rem;
            border-radius: 0.6rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            font-weight: 600;
        }

        .field-error {
            display: block;
            margin-top: 0.35rem;
            color: #fecaca;
            font-weight: 600;
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            const profileInput = document.getElementById('profile_picture');
            const previewTargets = [
                document.getElementById('profile-picture-preview-main'),
                document.getElementById('profile-picture-preview-form'),
            ].filter(Boolean);

            if (profileInput) {
                profileInput.addEventListener('change', function (event) {
                    const file = event.target.files && event.target.files[0];
                    if (!file) {
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function (readerEvent) {
                        previewTargets.forEach(function (target) {
                            target.src = String(readerEvent.target && readerEvent.target.result ? readerEvent.target.result : target.src);
                        });
                    };
                    reader.readAsDataURL(file);
                });
            }

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

            const newPasswordInput = document.getElementById('new_password');
            const strengthResult = document.getElementById('password-strength-result');

            if (newPasswordInput && strengthResult) {
                newPasswordInput.addEventListener('input', function () {
                    const value = newPasswordInput.value;
                    if (!value) {
                        strengthResult.textContent = '';
                        return;
                    }

                    const hasLetters = /[A-Za-z]/.test(value);
                    const hasNumbers = /\d/.test(value);
                    const longEnough = value.length >= 8;

                    if (longEnough && hasLetters && hasNumbers) {
                        strengthResult.textContent = 'Password strength: Strong';
                    } else if (value.length >= 6 && (hasLetters || hasNumbers)) {
                        strengthResult.textContent = 'Password strength: Medium';
                    } else {
                        strengthResult.textContent = 'Password strength: Weak';
                    }
                });
            }
        })();
    </script>
@endpush