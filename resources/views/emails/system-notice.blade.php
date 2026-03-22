<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
  </head>
  <body style="margin:0; padding:0; background:#f3f4f6; font-family: Arial, sans-serif; color:#111827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6; padding:24px 0;">
      <tr>
        <td align="center">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.08);">
            <tr>
              <td style="padding:24px 28px; background:#1e3a8a; color:#ffffff;">
                <h1 style="margin:0; font-size:20px; letter-spacing:0.5px;">{{ $appName }}</h1>
                <p style="margin:6px 0 0; font-size:13px; opacity:0.9;">System Notification</p>
              </td>
            </tr>
            <tr>
              <td style="padding:24px 28px;">
                <h2 style="margin:0 0 12px; font-size:18px; color:#0f172a;">{{ $subject }}</h2>
                <div style="font-size:14px; line-height:1.6; color:#1f2937;">
                  {!! nl2br(e($message)) !!}
                </div>
                <div style="margin-top:20px;">
                  <a href="{{ $portalUrl }}" style="display:inline-block; background:#2563eb; color:#ffffff; padding:10px 18px; border-radius:8px; text-decoration:none; font-weight:600;">Open {{ $appName }}</a>
                </div>
                <p style="margin-top:20px; font-size:12px; color:#6b7280;">
                  Sent by {{ $senderName }}.
                  @if(!empty($supportEmail))
                    Need help? Email <a href="mailto:{{ $supportEmail }}" style="color:#2563eb; text-decoration:none;">{{ $supportEmail }}</a>.
                  @endif
                </p>
              </td>
            </tr>
            <tr>
              <td style="padding:16px 28px; background:#f9fafb; font-size:11px; color:#9ca3af; text-align:center;">
                This is an automated message from {{ $appName }}. Please do not reply to this email.
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
