<!doctype html>
<html>
  <body style="font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial;">
    <h2>Hello {{ $name }},</h2>
    <p>Your seller application has been approved. You can set your account password and access the seller dashboard using the button below:</p>
    <p><a href="{{ $setPasswordUrl }}" style="display:inline-block;padding:12px 18px;background:#4f46e5;color:#fff;border-radius:8px;text-decoration:none">Set password &amp; access dashboard</a></p>
    <p style="color:#6b7280">If the button doesn't work, open this link in your browser:</p>
    <p style="font-size:13px;color:#111827">{{ $setPasswordUrl }}</p>
    <p>Thank you,<br/>Tech Mall</p>
  </body>
</html>
