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

    <!-- Login Type Selection -->
    <div class="input-group login-type-group">
      <label class="login-type-label">I am logging in as:</label>
      <details class="login-type-collapsible" id="login-type-collapse" open>
        <summary class="login-type-summary">I am logging in as:</summary>
        <div class="login-type-content">
          <div class="radio-inline-group">
            <label class="radio-option">
              <input type="radio" name="login_type" value="student_faculty" checked onchange="toggleLoginType()">
              Student / Faculty
            </label>
            <label class="radio-option">
              <input type="radio" name="login_type" value="employee" onchange="toggleLoginType()">
              Employee / Staff
            </label>
          </div>
        </div>
      </details>
    </div>

    <form action="{{ route('sinclogin.post') }}" method="POST" id="loginForm">
      @csrf

      <!-- Hidden field to track login type -->
      <input type="hidden" name="login_type" id="hidden_login_type" value="student_faculty">

      <!-- Student/Faculty Login Fields -->
      <div id="student-faculty-login" class="login-fields">
        <div class="input-group">
          <label for="login-email">
            <span class="label-icon">📧</span>
            Email Address
          </label>
          <input type="email"
                 id="login-email"
                 name="email"
                 placeholder="your.email@llcc.edu.ph">
        </div>
      </div>

      <!-- Employee Login Fields -->
      <div id="employee-login" class="login-fields" style="display:none;">
        <div class="input-group">
          <label for="login-username">
            <span class="label-icon">👤</span>
            Username
          </label>
          <input type="text"
                 id="login-username"
                 name="username"
                 placeholder="Enter your username">
        </div>
      </div>

      <!-- Common Password Field -->
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
          Username (LLCC Email)
        </label>
        <input type="email" 
               id="register-email"
               name="email" 
               placeholder="username@llcc.edu.ph" 
               pattern=".+@llcc\.edu\.ph" 
               required
               autocomplete="email">
        <small class="input-hint" id="register-email-hint">Use your official @llcc.edu.ph username/email</small>
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
          @foreach(\App\Models\Department::all() as $department)
            <option value="{{ $department->id }}">{{ $department->name }}</option>
          @endforeach
        </select>
      </div>
      </div>

      <div id="employee-fields" style="display:none;">
        <div class="input-group">
          <label for="employee-username">
            <span class="label-icon">👤</span>
            Username
          </label>
          <input type="text"
                 id="employee-username"
                 name="employee_username"
                 placeholder="Enter your username (no @llcc.edu.ph)"
                 autocomplete="username">
          <small class="input-hint">This will be your login username. Do not include @llcc.edu.ph</small>
        </div>

        <div class="input-group">
          <label for="employee-email">
            <span class="label-icon">📧</span>
            Email Address
          </label>
          <input type="email"
                 id="employee-email"
                 name="employee_email"
                 placeholder="your.email@example.com"
                 autocomplete="email">
          <small class="input-hint">Use your personal email address for notifications</small>
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
    <p>© {{ date('Y') }} Sincidentre. All rights reserved.</p>
    <p class="footer-links">
      <button type="button" onclick="openPrivacyModal()">Privacy Policy</button> •
      <button type="button" onclick="openTermsModal()">Terms of Service</button> •
      <button type="button" onclick="openSupportModal()">Contact Support</button>
    </p>
  </div>

</div>

<!-- Privacy Policy Modal -->
<div id="privacyModal" class="policy-modal" aria-hidden="true">
  <div class="policy-modal-backdrop" onclick="closePolicyModals()"></div>
  <div class="policy-modal-panel" role="dialog" aria-modal="true" aria-labelledby="privacyModalTitle">
    <div class="policy-modal-header">
      <h3 id="privacyModalTitle">Privacy Policy</h3>
      <button type="button" class="policy-modal-close" onclick="closePolicyModals()" aria-label="Close">&times;</button>
    </div>
    <div class="policy-modal-body">
      <div class="policy-content">
        <h4>Information We Collect</h4>
        <p>We collect information you provide directly to us, such as when you create an account, submit reports, or contact us for support.</p>

        <h4>How We Use Your Information</h4>
        <p>We use the information we collect to:</p>
        <ul>
          <li>Provide, maintain, and improve our services</li>
          <li>Process and manage incident reports</li>
          <li>Send you technical notices and support messages</li>
          <li>Respond to your comments and questions</li>
        </ul>

        <h4>Information Sharing</h4>
        <p>We do not sell, trade, or otherwise transfer your personal information to third parties without your consent, except as described in this policy.</p>

        <h4>Data Security</h4>
        <p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>

        <h4>Contact Us</h4>
        <p>If you have any questions about this Privacy Policy, please contact us through the Contact Support option.</p>
      </div>
    </div>
  </div>
</div>

<!-- Terms of Service Modal -->
<div id="termsModal" class="policy-modal" aria-hidden="true">
  <div class="policy-modal-backdrop" onclick="closePolicyModals()"></div>
  <div class="policy-modal-panel" role="dialog" aria-modal="true" aria-labelledby="termsModalTitle">
    <div class="policy-modal-header">
      <h3 id="termsModalTitle">Terms of Service</h3>
      <button type="button" class="policy-modal-close" onclick="closePolicyModals()" aria-label="Close">&times;</button>
    </div>
    <div class="policy-modal-body">
      <div class="policy-content">
        <h4>Acceptance of Terms</h4>
        <p>By accessing and using Sincidentre, you accept and agree to be bound by the terms and provision of this agreement.</p>

        <h4>Use License</h4>
        <p>Permission is granted to temporarily access Sincidentre for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title.</p>

        <h4>User Account</h4>
        <p>When you create an account, you must provide information that is accurate, complete, and current at all times. You are responsible for safeguarding your account credentials.</p>

        <h4>Prohibited Uses</h4>
        <p>You may not use Sincidentre:</p>
        <ul>
          <li>For any unlawful purpose or to solicit others to perform unlawful acts</li>
          <li>To violate any international, federal, provincial, or state regulations, rules, laws, or local ordinances</li>
          <li>To submit false or misleading information</li>
          <li>To interfere with or circumvent the security features of the service</li>
        </ul>

        <h4>Report Content</h4>
        <p>You are responsible for the content of reports you submit. All reports should be truthful, accurate, and submitted in good faith.</p>

        <h4>Termination</h4>
        <p>We may terminate or suspend your account and access to the service immediately, without prior notice, for conduct that we believe violates these Terms of Service.</p>
      </div>
    </div>
  </div>
</div>

<!-- Contact Support Modal -->
<div id="supportModal" class="policy-modal" aria-hidden="true">
  <div class="policy-modal-backdrop" onclick="closePolicyModals()"></div>
  <div class="policy-modal-panel" role="dialog" aria-modal="true" aria-labelledby="supportModalTitle">
    <div class="policy-modal-header">
      <h3 id="supportModalTitle">Contact Support</h3>
      <button type="button" class="policy-modal-close" onclick="closePolicyModals()" aria-label="Close">&times;</button>
    </div>
    <div class="policy-modal-body">
      <div class="policy-content">
        <h4>Get Help</h4>
        <p>We're here to help you with any questions or issues you may have with Sincidentre.</p>

        <div class="support-options">
          <div class="support-option">
            <h5>📧 Email Support</h5>
            <p>For general inquiries and technical support:</p>
            <p><strong>support@sincidentre.edu</strong></p>
          </div>

          <div class="support-option">
            <h5>📞 Phone Support</h5>
            <p>For urgent matters during business hours:</p>
            <p><strong>(555) 123-4567</strong></p>
            <p><small>Monday - Friday, 8:00 AM - 5:00 PM</small></p>
          </div>

          <div class="support-option">
            <h5>🏢 In-Person Support</h5>
            <p>Visit the IT Help Desk:</p>
            <p><strong>Administration Building, Room 101</strong></p>
            <p><small>Monday - Friday, 9:00 AM - 4:00 PM</small></p>
          </div>

          <div class="support-option">
            <h5>📋 Report Issues</h5>
            <p>For technical issues with the system:</p>
            <p><strong>ithelp@sincidentre.edu</strong></p>
          </div>
        </div>

        <div class="support-note">
          <p><strong>Note:</strong> For incident-related questions, please contact the Student Discipline Office directly at <strong>discipline@sincidentre.edu</strong></p>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
/* Footer link buttons */
.footer-links button {
  background: none;
  border: none;
  color: inherit;
  cursor: pointer;
  text-decoration: none;
  font-size: inherit;
  padding: 0;
  font-family: inherit;
}

.footer-links button:hover {
  text-decoration: underline;
}

/* Policy Modal Styles */
.policy-modal {
  position: fixed;
  inset: 0;
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 4000;
  padding: 1rem;
}

.policy-modal.show {
  display: flex;
}

.policy-modal-backdrop {
  position: absolute;
  inset: 0;
  background: rgba(2, 6, 23, 0.8);
  cursor: pointer;
}

.policy-modal-panel {
  position: relative;
  z-index: 1;
  width: min(700px, 100%);
  max-height: 80vh;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  background: linear-gradient(180deg, #0b1f53, #0a1536);
  color: #ffffff;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
  display: flex;
  flex-direction: column;
}

.policy-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  flex-shrink: 0;
}

.policy-modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
  color: #ffffff;
}

.policy-modal-close {
  background: none;
  border: none;
  color: rgba(255, 255, 255, 0.7);
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: all 0.2s ease;
  line-height: 1;
}

.policy-modal-close:hover {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.1);
}

.policy-modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem;
}

.policy-content h4 {
  color: #ffffff;
  font-size: 1.1rem;
  margin: 1.5rem 0 0.75rem 0;
  padding-bottom: 0.25rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.policy-content h4:first-child {
  margin-top: 0;
}

.policy-content h5 {
  color: #ffffff;
  font-size: 1rem;
  margin: 1rem 0 0.5rem 0;
}

.policy-content p {
  color: rgba(255, 255, 255, 0.9);
  line-height: 1.6;
  margin: 0.75rem 0;
}

.policy-content ul {
  color: rgba(255, 255, 255, 0.9);
  line-height: 1.6;
  margin: 0.75rem 0;
  padding-left: 1.5rem;
}

.policy-content li {
  margin: 0.25rem 0;
}

.support-options {
  margin: 1.5rem 0;
}

.support-option {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.support-option h5 {
  margin-top: 0;
  color: #ffffff;
  font-size: 1rem;
}

.support-option p {
  margin: 0.5rem 0;
}

.support-option strong {
  color: #ffffff;
}

.support-note {
  background: rgba(255, 193, 7, 0.1);
  border-left: 4px solid #ffc107;
  padding: 1rem;
  border-radius: 4px;
  margin-top: 1.5rem;
}

.support-note p {
  margin: 0;
  color: rgba(255, 255, 255, 0.95);
}

/* Login Type Radio Buttons */
.login-type-collapsible {
  border: 0;
  padding: 0;
  margin: 0;
}

.login-type-summary {
  display: none;
  list-style: none;
}

.login-type-summary::-webkit-details-marker {
  display: none;
}

.login-type-content {
  margin-top: 0.5rem;
}

.radio-inline-group {
  display: flex;
  gap: 1rem;
  margin-top: 0.5rem;
  flex-wrap: wrap;
}

.radio-option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  padding: 0.75rem 1.25rem;
  border: 2px solid rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  transition: all 0.2s ease;
  flex: 1;
  min-width: 150px;
}

.radio-option:hover {
  border-color: rgba(99, 102, 241, 0.5);
  background: rgba(99, 102, 241, 0.05);
}

.radio-option input[type="radio"] {
  width: auto;
  margin: 0;
  cursor: pointer;
}

.radio-option input[type="radio"]:checked + span,
.radio-option:has(input[type="radio"]:checked) {
  border-color: rgba(99, 102, 241, 0.8);
  background: rgba(99, 102, 241, 0.1);
  font-weight: 600;
}

.login-fields {
  transition: opacity 0.3s ease;
}

@media (max-width: 480px) {
  .login-type-label {
    display: none;
  }

  .login-type-collapsible {
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 0.75rem;
    padding: 0.5rem 0.75rem;
    background: rgba(255, 255, 255, 0.05);
  }

  .login-type-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 600;
    color: white;
    font-size: 0.9rem;
    cursor: pointer;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  }

  .login-type-summary::after {
    content: "▾";
    transition: transform 0.2s ease;
  }

  .login-type-collapsible[open] .login-type-summary::after {
    transform: rotate(180deg);
  }

  .login-type-content {
    margin-top: 0.75rem;
  }

  .login-type-collapsible:not([open]) .login-type-content {
    display: none;
  }
}
</style>

<script>
// Toggle between student/faculty and employee login
function toggleLoginType() {
  const studentFacultyLogin = document.getElementById('student-faculty-login');
  const employeeLogin = document.getElementById('employee-login');
  const loginEmail = document.getElementById('login-email');
  const loginUsername = document.getElementById('login-username');
  const hiddenLoginType = document.getElementById('hidden_login_type');

  const selectedType = document.querySelector('input[name="login_type"]:checked').value;
  hiddenLoginType.value = selectedType;

  if (selectedType === 'employee') {
    // Show employee login
    studentFacultyLogin.style.display = 'none';
    employeeLogin.style.display = 'block';
    loginEmail.required = false;
    loginEmail.value = '';
    loginUsername.required = true;
  } else {
    // Show student/faculty login
    studentFacultyLogin.style.display = 'block';
    employeeLogin.style.display = 'none';
    loginEmail.required = true;
    loginUsername.required = false;
    loginUsername.value = '';
  }
}

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
  const employeeUsername = document.getElementById('employee-username');
  const employeeEmail = document.getElementById('employee-email');
  const loginTypeCollapse = document.getElementById('login-type-collapse');

  if (loginTypeCollapse && window.matchMedia('(max-width: 480px)').matches) {
    loginTypeCollapse.open = false;
  }

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

    if (registerEmailLabel) {
      registerEmailLabel.innerHTML = '<span class="label-icon">📧</span>Username (LLCC Email)';
    }

    if (registerEmailHint) {
      registerEmailHint.textContent = 'Use your official @llcc.edu.ph username/email.';
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

    if (employeeUsername) {
      employeeUsername.required = isEmployee;
      if (!isEmployee) {
        employeeUsername.value = '';
      }
    }

    if (employeeEmail) {
      employeeEmail.required = isEmployee;
      if (!isEmployee) {
        employeeEmail.value = '';
      }
    }
  }

  if (registrantType) {
    registrantType.addEventListener('change', toggleRegistrantFields);
    toggleRegistrantFields();
  }
});

// Policy Modal Functions
function openPrivacyModal() {
  const modal = document.getElementById('privacyModal');
  if (modal) {
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }
}

function openTermsModal() {
  const modal = document.getElementById('termsModal');
  if (modal) {
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }
}

function openSupportModal() {
  const modal = document.getElementById('supportModal');
  if (modal) {
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }
}

function closePolicyModals() {
  const modals = ['privacyModal', 'termsModal', 'supportModal'];
  modals.forEach(function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal && modal.classList.contains('show')) {
      modal.classList.remove('show');
      modal.setAttribute('aria-hidden', 'true');
    }
  });
  document.body.style.overflow = '';
}

// Close modals on escape key
document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') {
    const policyModals = ['privacyModal', 'termsModal', 'supportModal'];
    const openPolicyModal = policyModals.find(function(modalId) {
      const modal = document.getElementById(modalId);
      return modal && modal.classList.contains('show');
    });

    if (openPolicyModal) {
      closePolicyModals();
      return;
    }
  }
});
</script>

</body>
</html>
