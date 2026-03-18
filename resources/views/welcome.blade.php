<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Sincidentre - Login & Register</title>
<link rel="stylesheet" href="{{ asset('css/newlogincss.css') }}">
</head>
<body>

<!-- Animated Background -->
<div class="background-animation">
  <div class="circle circle-1"></div>
  <div class="circle circle-2"></div>
  <div class="circle circle-3"></div>
</div>

<div class="login-container">
  
  <!-- Logo and Branding Section -->
  <div class="brand-section">
    <div class="logo-wrapper">
      <!-- Replace 'logo.png' with your actual logo path -->
      <img src="{{ asset('images/sincidentrelogo.png') }}" alt="Sincidentre Logo" class="logo-image">
    </div>
    <h1 class="brand-title">SINCIDENTRE</h1>
    <p class="brand-subtitle">School Incident Reporting System</p>
    <div class="brand-tagline">
      <span class="icon">🔒</span>
      <span>Secure • Confidential • Trusted</span>
    </div>
  </div>

  <!-- Login Form -->
  <div class="form-box" id="login-box">
    <div class="form-header">
      <h2>Welcome Back</h2>
      <p>Sign in to continue</p>
    </div>

    <!-- Show login errors -->
    @if ($errors->any())
      <div class="alert alert-error">
        <span class="alert-icon">⚠️</span>
        <div class="alert-content">
          @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
          @endforeach
        </div>
      </div>
    @endif

    <!-- Success message -->
    @if (session('success'))
      <div class="alert alert-success">
        <span class="alert-icon">✓</span>
        <div class="alert-content">
          <p>{{ session('success') }}</p>
        </div>
      </div>
    @endif

    <form action="{{ route('sinclogin.post') }}" method="POST">
      @csrf
      
      <div class="input-group">
        <label for="login-email">
          <span class="label-icon">📧</span>
          Email Address
        </label>
        <input type="email" 
               id="login-email"
               name="email" 
               placeholder="your.email@llcc.edu.ph" 
               required
               autocomplete="email">
      </div>

      <div class="input-group">
        <label for="login-password">
          <span class="label-icon">🔑</span>
          Password
        </label>
        <input type="password" 
               id="login-password"
               name="password" 
               placeholder="Enter your password" 
               required
               autocomplete="current-password">
      </div>

      <button type="submit" class="btn btn-primary">
        <span>Sign In</span>
        <span class="btn-icon">→</span>
      </button>
    </form>

    <div class="form-footer">
      <p>Don't have an account? 
        <a href="#" onclick="showRegister(event)" class="link-primary">Create Account</a>
      </p>
    </div>
  </div>

  <!-- Register Form -->
  <div class="form-box" id="register-box" style="display:none;">
    <div class="form-header">
      <h2>Create Account</h2>
      <p>Join Sincidentre today</p>
    </div>

    <form action="{{ route('sincregister.post') }}" method="POST">
      @csrf
      
      <div class="input-row">
        <div class="input-group">
          <label for="first-name">
            <span class="label-icon">👤</span>
            First Name
          </label>
          <input type="text" 
                 id="first-name"
                 name="first_name" 
                 placeholder="John" 
                 required
                 autocomplete="given-name">
        </div>

        <div class="input-group">
          <label for="last-name">
            <span class="label-icon">👤</span>
            Last Name
          </label>
          <input type="text" 
                 id="last-name"
                 name="last_name" 
                 placeholder="Doe" 
                 required
                 autocomplete="family-name">
        </div>
      </div>

      <div class="input-group" id="register-email-group">
        <label for="register-email" id="register-email-label">
          <span class="label-icon">📧</span>
          LLCC Email
        </label>
        <input type="email" 
               id="register-email"
               name="email" 
               placeholder="your.name@llcc.edu.ph" 
               pattern=".+@llcc\.edu\.ph" 
               required
               autocomplete="email">
        <small class="input-hint" id="register-email-hint">Must be a valid @llcc.edu.ph email</small>
      </div>

      <div class="input-group">
        <label for="registrant-type">
          <span class="label-icon">🎓</span>
          I am registering as
        </label>
        <select id="registrant-type" name="registrant_type" required>
          <option value="" disabled selected>Select one</option>
          <option value="student">Student</option>
          <option value="faculty">Faculty</option>
          <option value="employee_staff">Employee / Staff</option>
        </select>
      </div>

      <div id="student-faculty-fields">
      <div class="input-group">
        <label for="department">
          <span class="label-icon">🏫</span>
          Department / College
        </label>
        <select id="department" name="department_id">
          <option value="" disabled selected>Select your college</option>
          @foreach($departments as $department)
            <option value="{{ $department->id }}">{{ $department->name }}</option>
          @endforeach
        </select>
      </div>
      </div>

      <div id="employee-fields" style="display:none;">
        <div class="input-group">
          <label for="employee-access-code">
            <span class="label-icon">🔐</span>
            Code Provided by Top Management
          </label>
          <input type="password"
                 id="employee-access-code"
                 name="employee_access_code"
                 placeholder="Enter provided access code"
                 autocomplete="off">
          <label for="show-employee-code" style="display:flex;align-items:center;gap:0.5rem;margin-top:0.5rem;font-size:0.85rem;color:#ffffff;">
            <input type="checkbox" id="show-employee-code">
            Show code while typing
          </label>
          <small class="input-hint">Enter the code issued by Top Management to auto-verify Employee/Staff registration.</small>
        </div>
      </div>

      <div class="input-group">
        <label for="register-password">
          <span class="label-icon">🔑</span>
          Password
        </label>
        <input type="password" 
               id="register-password"
               name="password" 
               placeholder="Create a strong password" 
               required
               autocomplete="new-password">
        <small class="input-hint">Minimum 8 characters</small>
      </div>

      <div class="input-group">
        <label for="password-confirm">
          <span class="label-icon">🔑</span>
          Confirm Password
        </label>
        <input type="password" 
               id="password-confirm"
               name="password_confirmation" 
               placeholder="Re-enter your password" 
               required
               autocomplete="new-password">
      </div>

      <button type="submit" class="btn btn-primary">
        <span>Create Account</span>
        <span class="btn-icon">→</span>
      </button>
    </form>

    <div class="form-footer">
      <p>Already have an account? 
        <a href="#" onclick="showLogin(event)" class="link-primary">Sign In</a>
      </p>
    </div>
  </div>

  <!-- Footer Info -->
  <div class="page-footer">
    <p>© 2026 Sincidentre. All rights reserved.</p>
    <p class="footer-links">
      <a href="#">Privacy Policy</a> • 
      <a href="#">Terms of Service</a> • 
      <a href="#">Contact Support</a>
    </p>
  </div>

</div>

<script>
function showRegister(event) {
  if (event) event.preventDefault();
  const loginBox = document.getElementById("login-box");
  const registerBox = document.getElementById("register-box");
  
  loginBox.style.opacity = "0";
  loginBox.style.transform = "translateY(-20px)";
  
  setTimeout(() => {
    loginBox.style.display = "none";
    registerBox.style.display = "block";
    setTimeout(() => {
      registerBox.style.opacity = "1";
      registerBox.style.transform = "translateY(0)";
    }, 10);
  }, 300);
}

function showLogin(event) {
  if (event) event.preventDefault();
  const loginBox = document.getElementById("login-box");
  const registerBox = document.getElementById("register-box");
  
  registerBox.style.opacity = "0";
  registerBox.style.transform = "translateY(-20px)";
  
  setTimeout(() => {
    registerBox.style.display = "none";
    loginBox.style.display = "block";
    setTimeout(() => {
      loginBox.style.opacity = "1";
      loginBox.style.transform = "translateY(0)";
    }, 10);
  }, 300);
}

// Add smooth transitions on load
document.addEventListener('DOMContentLoaded', function() {
  const formBox = document.querySelector('.form-box');
  if (formBox) {
    setTimeout(() => {
      formBox.style.opacity = "1";
      formBox.style.transform = "translateY(0)";
    }, 100);
  }

  const registrantType = document.getElementById('registrant-type');
  const registerEmailGroup = document.getElementById('register-email-group');
  const registerEmailLabel = document.getElementById('register-email-label');
  const registerEmailHint = document.getElementById('register-email-hint');
  const studentFacultyFields = document.getElementById('student-faculty-fields');
  const employeeFields = document.getElementById('employee-fields');
  const department = document.getElementById('department');
  const registerEmail = document.getElementById('register-email');
  const employeeAccessCode = document.getElementById('employee-access-code');
  const showEmployeeCode = document.getElementById('show-employee-code');

  function toggleRegistrantFields() {
    if (!registrantType) return;

    const isEmployee = registrantType.value === 'employee_staff';

    if (studentFacultyFields) {
      studentFacultyFields.style.display = isEmployee ? 'none' : 'block';
    }

    if (employeeFields) {
      employeeFields.style.display = isEmployee ? 'block' : 'none';
    }

    if (registerEmailGroup) {
      registerEmailGroup.style.display = isEmployee ? 'none' : 'block';
    }

    if (registerEmailLabel && !isEmployee) {
      registerEmailLabel.innerHTML = '<span class="label-icon">📧</span>LLCC Email';
    }

    if (registerEmailHint && !isEmployee) {
      registerEmailHint.textContent = 'Must be a valid @llcc.edu.ph email';
    }

    if (department) {
      department.required = !isEmployee;
      if (isEmployee) {
        department.value = '';
      }
    }

    if (registerEmail) {
      registerEmail.required = !isEmployee;
      if (isEmployee) {
        registerEmail.value = '';
      }
    }

    if (employeeAccessCode) {
      employeeAccessCode.required = isEmployee;
      if (!isEmployee) {
        employeeAccessCode.value = '';
        employeeAccessCode.type = 'password';
      }
    }

    if (showEmployeeCode && !isEmployee) {
      showEmployeeCode.checked = false;
    }

    if (showEmployeeCode && employeeAccessCode) {
      employeeAccessCode.type = showEmployeeCode.checked ? 'text' : 'password';
    }
  }

  if (showEmployeeCode && employeeAccessCode) {
    showEmployeeCode.addEventListener('change', function () {
      employeeAccessCode.type = this.checked ? 'text' : 'password';
    });
  }

  if (registrantType) {
    registrantType.addEventListener('change', toggleRegistrantFields);
    toggleRegistrantFields();
  }
});
</script>

</body>
</html>