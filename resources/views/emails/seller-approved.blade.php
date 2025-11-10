<!doctype html>
<html>
<head>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <style>
    body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial; color: #111827; }
    .btn { display:inline-block;padding:12px 20px;border-radius:8px;background:#4f46e5;color:white;text-decoration:none;font-weight:600; }
  </style>
</head>
<body>
  <h2>Hi {{ $user->name ?? 'Seller' }},</h2>

  <p>Good news — your seller application has been <strong>approved</strong>.</p>

  @if($sellerType === 'long_term')
    <p>You have been approved as a <strong>Long-term seller</strong>. Please finish setting up your account to access the seller dashboard.</p>
  @else
    <p>You were approved as a <strong>one-time / business seller</strong>. You can use the link below to set your password and sign in.</p>
  @endif

  <p style="margin-top:18px;">
    <a class="btn" href="{{ $setPasswordUrl }}">Set your password &amp; sign in</a>
  </p>

  <p style="color:#6b7280;margin-top:14px;">
    If the button doesn't work, copy and paste this URL into your browser:<br>
    <small>{{ $setPasswordUrl }}</small>
  </p>

  <p style="margin-top:18px">If you weren't expecting this email, contact support.</p>
  <p style="margin-top:8px;color:#6b7280">Thanks,<br/>Tech Resolute Team</p>
</body>
</html>
