@extends('layouts.app')

@section('title', 'User Profile - Sincidentre')

@section('content')
    @php
        $roleMap = [
            'student' => 'Student',
            'faculty' => 'Faculty',
            'employee_staff' => 'Employee/Staff',
        ];
        $displayRole = $roleMap[Auth::user()->registrant_type ?? ''] ?? 'User';
    @endphp

    <div class="profile-container animate">

        <!-- ✅ Success Message -->
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
            <script>
                setTimeout(() => {
                    let alertBox = document.querySelector('.alert-success');
                    if (alertBox) alertBox.style.display = 'none';
                }, 3000);
            </script>
        @endif

        <!-- ✅ Error Messages -->
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Profile Header -->
        <div class="profile-header">
            <img src="{{ Auth::user()->profile_picture ? asset(Auth::user()->profile_picture) : asset('images/default-avatar.png') }}" 
                 alt="Profile Picture" class="profile-pic">

            <h2>{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</h2>
            <p>Email: {{ Auth::user()->email }}</p>
            <p>Role: {{ $displayRole }}</p>
        </div>

        <!-- Account Settings -->
        <div class="settings-section">
            <h3>Account Settings</h3>
            <form class="settings-form" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                <label for="profile_picture">Profile Picture</label>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*">

                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="{{ Auth::user()->first_name }}" readonly>

                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="{{ Auth::user()->last_name }}" readonly>

                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ Auth::user()->email }}" readonly>

                <label for="phone">Contact Number</label>
                <input type="text" id="phone" name="phone" value="{{ Auth::user()->phone ?? '' }}" placeholder="Enter contact number">

                <h4>Change Password</h4>
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password">

                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password">

                <label for="new_password_confirmation">Confirm New Password</label>
                <input type="password" id="new_password_confirmation" name="new_password_confirmation">

                <div class="form-buttons">
                    <button type="submit" class="save-btn">Save Changes</button>
                    <button type="reset" class="cancel-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>
@endsection