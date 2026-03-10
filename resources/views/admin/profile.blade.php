@extends('layouts.admin')

@section('title', 'Admin Profile - Sincidentre')

@section('page-title', 'My Profile')

@section('content')
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
                <img src="{{ Auth::user()->profile_picture ? asset(Auth::user()->profile_picture) : asset('images/default-avatar.png') }}" 
                     alt="Profile Picture" 
                     style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid rgba(255,255,255,0.3); box-shadow: 0 8px 24px rgba(0,0,0,0.2);">
            </div>
            
            <h2 style="color: white; margin-top: 1rem; font-size: 1.8rem; font-weight: 700;">
                {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}
            </h2>
            <p style="color: rgba(255,255,255,0.8); font-size: 0.95rem; margin: 0.5rem 0;">
                📧 {{ Auth::user()->email }}
            </p>
            <p style="color: rgba(255,255,255,0.7); font-size: 0.9rem; margin: 0.25rem 0;">
                🏢 {{ Auth::user()->department->name ?? 'Admin' }}
            </p>
            <span class="role-badge admin" style="margin-top: 0.75rem; display: inline-block;">
                Admin
            </span>
        </section>

        <!-- Account Settings -->
        <section>
            <h2>⚙️ Account Settings</h2>
            
            <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" style="padding: 2rem;">
                @csrf
                @method('PATCH')

                <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem; max-width: 600px;">
                    
                    <!-- Profile Picture -->
                    <div class="form-group">
                        <label for="profile_picture" style="display: block; margin-bottom: 0.5rem; color: white; font-weight: 600;">
                            📷 Profile Picture
                        </label>
                        <input type="file" 
                               id="profile_picture" 
                               name="profile_picture" 
                               accept="image/*"
                               style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.15); border: 2px solid rgba(255,255,255,0.26); border-radius: 0.6rem; color: white; cursor: pointer;">
                        <small style="color: rgba(255,255,255,0.6); font-size: 0.8rem; display: block; margin-top: 0.25rem;">
                            Recommended: Square image, at least 200x200px
                        </small>
                    </div>

                    <!-- First Name -->
                    <div class="form-group">
                        <label for="first_name" style="display: block; margin-bottom: 0.5rem; color: white; font-weight: 600;">
                            👤 First Name
                        </label>
                        <input type="text" 
                               id="first_name" 
                               name="first_name" 
                               value="{{ Auth::user()->first_name }}" 
                               readonly
                               style="width: 100%; padding: 0.825rem 1.1rem; background: rgba(255,255,255,0.1); border: 2px solid rgba(255,255,255,0.15); border-radius: 0.6rem; color: rgba(255,255,255,0.6); cursor: not-allowed;">
                    </div>

                    <!-- Last Name -->
                    <div class="form-group">
                        <label for="last_name" style="display: block; margin-bottom: 0.5rem; color: white; font-weight: 600;">
                            👤 Last Name
                        </label>
                        <input type="text" 
                               id="last_name" 
                               name="last_name" 
                               value="{{ Auth::user()->last_name }}" 
                               readonly
                               style="width: 100%; padding: 0.825rem 1.1rem; background: rgba(255,255,255,0.1); border: 2px solid rgba(255,255,255,0.15); border-radius: 0.6rem; color: rgba(255,255,255,0.6); cursor: not-allowed;">
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label for="email" style="display: block; margin-bottom: 0.5rem; color: white; font-weight: 600;">
                            📧 Email
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="{{ Auth::user()->email }}" 
                               readonly
                               style="width: 100%; padding: 0.825rem 1.1rem; background: rgba(255,255,255,0.1); border: 2px solid rgba(255,255,255,0.15); border-radius: 0.6rem; color: rgba(255,255,255,0.6); cursor: not-allowed;">
                    </div>

                    <!-- Contact Number -->
                    <div class="form-group">
                        <label for="phone" style="display: block; margin-bottom: 0.5rem; color: white; font-weight: 600;">
                            📞 Contact Number
                        </label>
                        <input type="text" 
                               id="phone" 
                               name="phone" 
                               value="{{ Auth::user()->phone ?? '' }}" 
                               placeholder="Enter contact number"
                               style="width: 100%; padding: 0.825rem 1.1rem; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.26); border-radius: 0.6rem; color: white;">
                    </div>

                    <!-- Divider -->
                    <div style="border-top: 1px solid rgba(255,255,255,0.2); margin: 1rem 0;"></div>

                    <!-- Change Password Section -->
                    <h3 style="color: white; font-size: 1.2rem; margin: 0 0 1rem 0;">
                        🔒 Change Password
                    </h3>

                    <!-- Current Password -->
                    <div class="form-group">
                        <label for="current_password" style="display: block; margin-bottom: 0.5rem; color: white; font-weight: 600;">
                            Current Password
                        </label>
                        <input type="password" 
                               id="current_password" 
                               name="current_password"
                               placeholder="Enter current password"
                               style="width: 100%; padding: 0.825rem 1.1rem; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.26); border-radius: 0.6rem; color: white;">
                    </div>

                    <!-- New Password -->
                    <div class="form-group">
                        <label for="new_password" style="display: block; margin-bottom: 0.5rem; color: white; font-weight: 600;">
                            New Password
                        </label>
                        <input type="password" 
                               id="new_password" 
                               name="new_password"
                               placeholder="Enter new password"
                               style="width: 100%; padding: 0.825rem 1.1rem; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.26); border-radius: 0.6rem; color: white;">
                        <small style="color: rgba(255,255,255,0.6); font-size: 0.8rem; display: block; margin-top: 0.25rem;">
                            Minimum 8 characters
                        </small>
                    </div>

                    <!-- Confirm New Password -->
                    <div class="form-group">
                        <label for="new_password_confirmation" style="display: block; margin-bottom: 0.5rem; color: white; font-weight: 600;">
                            Confirm New Password
                        </label>
                        <input type="password" 
                               id="new_password_confirmation" 
                               name="new_password_confirmation"
                               placeholder="Confirm new password"
                               style="width: 100%; padding: 0.825rem 1.1rem; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.26); border-radius: 0.6rem; color: white;">
                    </div>

                    <!-- Form Buttons -->
                    <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                        <button type="submit" class="btn-primary" style="flex: 1;">
                            💾 Save Changes
                        </button>
                        <button type="reset" class="btn-secondary" style="flex: 1;">
                            ❌ Cancel
                        </button>
                    </div>
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
</style>
@endpush
