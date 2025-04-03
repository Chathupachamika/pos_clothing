<!DOCTYPE html>
<html>
<head>
    <title>Reset Your Password</title>
</head>
<body>
    <p>Hello,</p>
    <p>You requested a password reset. Click the link below to reset your password:</p>
    <a href="{{ url('/reset-password/' . $token) }}">Reset Password</a>
    <p>If you did not request this, please ignore this email.</p>
</body>
</html>
