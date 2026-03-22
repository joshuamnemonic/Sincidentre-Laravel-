{{ $subject }}

{{ $message }}

Open {{ $appName }}: {{ $portalUrl }}

Sent by {{ $senderName }}.
@if(!empty($supportEmail))
Need help? Email {{ $supportEmail }}.
@endif

This is an automated message from {{ $appName }}. Please do not reply to this email.
