<!DOCTYPE html>
<html>
<head>
    <title>Password Reset Verification Code</title>
</head>
<body>
    <h2>Password Reset Request</h2>
    <p>Hello {{ $user->name }},</p>
    <p>You have requested to reset your password. Please use the following verification code to proceed:</p>
    <h3 style="color: #333; font-size: 24px; letter-spacing: 2px;">{{ $verificationCode }}</h3>
    <p>This code will expire in 10 minutes.</p>
    <p>If you did not request this password reset, please ignore this email.</p>
    <br>
    <p>Thank you,<br>
    {{ config('app.name') }} Team</p>
</body>
</html>