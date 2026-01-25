<!DOCTYPE html>
<html>
<head>
    <title>Reset Your Password</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4F46E5;
            color: white !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #4338CA;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 14px;
            color: #666;
        }
        .url-fallback {
            word-break: break-all;
            color: #4F46E5;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <h1>Reset Your Password</h1>

    <p>Hi {{ $user->name }},</p>

    <p>We received a request to reset your password for your EyePub account. Click the button below to set a new password:</p>

    <p>
        <a href="{{ $resetUrl }}" class="button">Reset Password</a>
    </p>

    <p>Or copy and paste this link into your browser:</p>
    <p class="url-fallback">{{ $resetUrl }}</p>

    <p><strong>This link will expire in 1 hour.</strong></p>

    <div class="footer">
        <p>If you didn't request a password reset, you can safely ignore this email. Your password will remain unchanged.</p>
        <p>This is an automated message from EyePub. Please do not reply to this email.</p>
    </div>
</body>
</html>
