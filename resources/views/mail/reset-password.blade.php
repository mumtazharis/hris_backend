<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <style>
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4F46E5;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 8px;
        }

        .footer {
            font-size: 12px;
            color: #999;
            margin-top: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Hello {{ $user }},</h2>

        <p>We received a request to reset your password for your account at <strong>{{ config('app.name') }}</strong>.</p>

        <p>Click the button below to reset your password. This link will expire in 60 minutes.</p>

        <p style="text-align: center;">
            <a href="{{ $resetUrl }}" class="button">Reset Password</a>
        </p>

        <p>If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>

        <p>Thanks,<br>The {{ config('app.name') }} Team</p>

        <div class="footer">
            If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:<br>
            <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
        </div>
    </div>
</body>
</html>
