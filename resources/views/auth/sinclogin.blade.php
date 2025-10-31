<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sincidentre - Login & Register</title>
  <link rel="stylesheet" href="{{ asset('css/newlogincss.css') }}">
  <script>
    function showRegister() {
      document.getElementById("login-box").style.display = "none";
      document.getElementById("register-box").style.display = "block";
    }
    function showLogin() {
      document.getElementById("register-box").style.display = "none";
      document.getElementById("login-box").style.display = "block";
    }
  </script>
</head>
<body>

  <div class="container">
    <h1>Sincidentre</h1>
    <p class="subtitle">School Incident Reporting System</p>

    <!-- Login Form -->
    <div class="form-box" id="login-box">
      <h2>Login</h2>
      <form action="{{ route('sinclogin.post') }}" method="POST">
    @csrf
        <div class="input-group">
          <label>Email</label>
          <input type="email" name="email" placeholder="Enter your LLCC email" required>
        </div>
        <div class="input-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="Enter your password" required>
        </div>
        <button type="submit" class="btn">Login</button>
      </form>
      <p class="switch">Don't have an account? <a href="#" onclick="showRegister()">Register</a></p>
    </div>
    <!-- Show login errors -->
@if ($errors->any())
  <div style="color: red; margin-top:10px;">
    @foreach ($errors->all() as $error)
      <p>{{ $error }}</p>
    @endforeach
  </div>
@endif

<!-- Success message (e.g., after register) -->
@if (session('success'))
  <div style="color: green; margin-top:10px;">
    {{ session('success') }}
  </div>
@endif


    <!-- Register Form -->
    <div class="form-box" id="register-box" style="display:none;">
  <h2>Register</h2>
  <form action="{{ route('sincregister.post') }}" method="POST">
    @csrf

    <div class="input-group">
      <label>First Name</label>
      <input type="text" name="first_name" placeholder="Enter your first name" required>
    </div>

    <div class="input-group">
      <label>Last Name</label>
      <input type="text" name="last_name" placeholder="Enter your last name" required>
    </div>

    <div class="input-group">
      <label>LLCC Email</label>
      <input type="email" name="email" placeholder="example@llcc.edu.ph" pattern=".+@llcc\.edu\.ph" required>
    </div>

    <div class="input-group">
      <label>Password</label>
      <input type="password" name="password" placeholder="Create a password" required>
    </div>

    <div class="input-group">
      <label>Confirm Password</label>
      <input type="password" name="password_confirmation" placeholder="Confirm your password" required>
    </div>

    <button type="submit" class="btn">Register</button>
  </form>
  <p class="switch">Already have an account? <a href="#" onclick="showLogin()">Login</a></p>
</div>



