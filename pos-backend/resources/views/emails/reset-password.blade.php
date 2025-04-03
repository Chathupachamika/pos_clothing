<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .email-wrapper {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin: 20px auto;
        }
        .header {
            background-color: #2563eb;
            padding: 25px 40px;
            text-align: center;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 0.5px;
        }
        .content {
            padding: 40px;
        }
        .title {
            color: #1e293b;
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 25px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 15px;
        }
        p {
            color: #4b5563;
            margin-bottom: 16px;
        }
        .otp-container {
            background-color: #f0f5ff;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            margin: 30px 0;
            border: 1px solid #dbeafe;
        }
        .otp-label {
            font-size: 14px;
            color: #3b82f6;
            margin-bottom: 10px;
            font-weight: 500;
        }
        .otp-code {
            font-size: 32px;
            font-weight: bold;
            letter-spacing: 6px;
            color: #1d4ed8;
            margin: 15px 0;
            padding: 10px;
            background-color: #ffffff;
            border-radius: 6px;
            display: inline-block;
            min-width: 200px;
            border: 1px solid #bfdbfe;
        }
        .otp-expiry {
            font-size: 13px;
            color: #6b7280;
            margin-top: 10px;
        }
        .warning {
            background-color: #fff8f1;
            border-left: 4px solid #f97316;
            padding: 16px;
            margin: 25px 0;
            font-size: 14px;
            color: #9a3412;
            border-radius: 4px;
        }
        .security-list {
            background-color: #f8fafc;
            padding: 20px 25px;
            border-radius: 6px;
            margin: 25px 0;
        }
        .security-list h4 {
            margin-top: 0;
            color: #334155;
        }
        ul {
            padding-left: 20px;
            margin-bottom: 0;
        }
        li {
            margin-bottom: 8px;
            color: #4b5563;
        }
        .help-text {
            margin-top: 30px;
            font-size: 14px;
            color: #64748b;
            background-color: #f1f5f9;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }
        .help-text a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 25px 40px;
            background-color: #f8fafc;
            font-size: 13px;
            color: #64748b;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            margin: 5px 0;
            color: #64748b;
        }
        @media only screen and (max-width: 480px) {
            .content {
                padding: 25px;
            }
            .otp-code {
                font-size: 28px;
                letter-spacing: 4px;
                min-width: 180px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="email-wrapper">
            <div class="header">
                <div class="logo">Hardware POS System</div>
            </div>

            <div class="content">
                <div class="title">Password Reset Verification</div>

                <p>Hello,</p>

                <p>We received a request to reset the password for your Hardware POS System account. To securely reset your password, please use the verification code below:</p>

                <div class="otp-container">
                    <div class="otp-label">YOUR VERIFICATION CODE</div>
                    <div class="otp-code">{{ $otp }}</div>
                    <div class="otp-expiry">This code will expire in 60 minutes</div>
                </div>

                <div class="warning">
                    <strong>Security Notice:</strong> If you didn't request this password reset, please ignore this email or contact our support team immediately as someone may be attempting to access your account.
                </div>

                <div class="security-list">
                    <h4>For your security:</h4>
                    <ul>
                        <li>Never share this verification code with anyone</li>
                        <li>Our team will never ask you for this code via email or phone</li>
                        <li>The code will expire after 60 minutes for your protection</li>
                    </ul>
                </div>

                <div class="help-text">
                    Need assistance? Contact our support team at <a href="mailto:support@hardwarepos.com">support@hardwarepos.com</a>
                </div>
            </div>

            <div class="footer">
                <p>&copy; {{ date('Y') }} Hardware POS System. All rights reserved.</p>
                <p>This is an automated message. Please do not reply to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>