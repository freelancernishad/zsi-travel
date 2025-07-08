<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #eaeaea;
            padding: 40px;
            margin: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .otp {
            font-size: 36px;
            font-weight: bold;
            color: #4CAF50;
            text-align: center;
            background-color: #f1f1f1;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: #555;
        }
        .footer a {
            color: #4CAF50;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to {{ env('APP_NAME') }}</h1>
        </div>
        <p style="font-size: 16px; line-height: 1.5; color: #333;">
            Thank you for registering with us! To complete your registration, please use the following One-Time Password (OTP):
        </p>
        <div class="otp">{{ $otp }}</div>
        <p style="font-size: 16px; line-height: 1.5; color: #333;">
            This OTP is valid for a short period. If you did not request this OTP, please ignore this email.
        </p>
        <div class="footer">
            <p>Thank you for choosing our service!</p>
            <p>If you have any questions, feel free to <a target="_blank" href="#">contact us</a>.</p>
        </div>
    </div>
</body>
</html>
