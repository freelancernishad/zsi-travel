<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header img {
            max-width: 100px;
        }
        .content {
            text-align: center;
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            padding: 15px 30px;
            margin-top: 20px;
            font-size: 16px;
            color: #ffffff !important;
            background-color: #007bff;
            text-decoration: none;
            border-radius: 5px;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #888888;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('images/logo.png') }}" alt="Logo">
        </div>
        <div class="content">
            <h1>Email Verification</h1>
            <p>Hello {{ $user->name }},</p>
            <p>Thank you for registering with us. Please click the button below to verify your email address and complete your registration:</p>
            <a href="{{ $verify_url.'?token=' . $user->email_verification_hash }}" class="button">Verify Email</a>
        </div>
        <div class="footer">
            <p>If you did not create an account, no further action is required.</p>
            <p>Thank you,<br>{{ env('APP_NAME') }}</p>
        </div>
    </div>
</body>
</html>
