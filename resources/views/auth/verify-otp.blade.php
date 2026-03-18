<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Sincidentre - OTP Verification</title>
<link rel="stylesheet" href="{{ asset('css/newlogincss.css') }}">
</head>
<body>

<div class="background-animation">
    <div class="circle circle-1"></div>
    <div class="circle circle-2"></div>
    <div class="circle circle-3"></div>
</div>

<div class="login-container">

    <div class="brand-section">
        <div class="logo-wrapper">
            <img src="{{ asset('images/sincidentrelogo.png') }}" alt="Sincidentre Logo" class="logo-image">
        </div>
        <h1 class="brand-title">SINCIDENTRE</h1>
        <p class="brand-subtitle">School Incident Reporting System</p>
        <div class="brand-tagline">
            <span class="icon">🔒</span>
            <span>Secure • Confidential • Trusted</span>
        </div>
    </div>

    <div class="form-box" style="display:block;opacity:1;transform:none;">
        <div class="form-header">
            <h2>OTP Verification</h2>
            <p>Enter the 6-digit code sent to {{ $email }}</p>
        </div>

        @if (session('success'))
            <div class="alert alert-success">
                <span class="alert-icon">✓</span>
                <div class="alert-content">
                    <p>{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if ($errors->has('otp'))
            <div class="alert alert-error">
                <span class="alert-icon">⚠️</span>
                <div class="alert-content">
                    @foreach ($errors->get('otp') as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('sincregister.otp.verify') }}">
            @csrf

            <div class="input-group">
                <label for="otp">
                    <span class="label-icon">🔑</span>
                    Verification Code (OTP)
                </label>
                <input type="text"
                             id="otp"
                             name="otp"
                             value="{{ old('otp') }}"
                             maxlength="6"
                             minlength="6"
                             pattern="[0-9]{6}"
                             inputmode="numeric"
                             required
                             autofocus
                             autocomplete="one-time-code"
                             placeholder="Enter 6-digit code">
                <small class="input-hint">Code expires in 10 minutes.</small>
            </div>

            <button type="submit" class="btn btn-primary">
                <span>Verify OTP</span>
                <span class="btn-icon">→</span>
            </button>
        </form>

        <div class="form-footer">
            <form method="POST" action="{{ route('sincregister.otp.resend') }}">
                @csrf
                <button type="submit" class="link-primary" style="background:none;border:none;cursor:pointer;font-size:0.95rem;">
                    Resend OTP
                </button>
            </form>
        </div>
    </div>

</div>

</body>
</html>
