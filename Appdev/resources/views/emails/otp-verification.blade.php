<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LABERS - Email Verification</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        .logo {
            display: inline-block;
            padding: 12px 24px;
            border: 2px solid #FD7C21;
            color: #FD7C21;
            font-weight: bold;
            font-size: 18px;
            border-radius: 8px;
            text-decoration: none;
            margin-bottom: 20px;
        }
        .title {
            color: #FD7C21;
            font-size: 28px;
            font-weight: 600;
            margin: 0;
        }
        .subtitle {
            color: #6c757d;
            font-size: 16px;
            margin: 10px 0 0 0;
        }
        .content {
            margin: 30px 0;
        }
        .otp-container {
            background: linear-gradient(135deg, rgba(253, 124, 33, 0.1) 0%, rgba(255, 157, 0, 0.1) 100%);
            border: 2px solid #FD7C21;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        .otp-label {
            color: #4E413B;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .otp-code {
            font-size: 36px;
            font-weight: bold;
            color: #FD7C21;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
            margin: 0;
        }
        .otp-note {
            color: #6c757d;
            font-size: 12px;
            margin-top: 15px;
        }
        .instructions {
            background: #f8f9fa;
            border-left: 4px solid #FD7C21;
            padding: 20px;
            margin: 30px 0;
            border-radius: 0 8px 8px 0;
        }
        .instructions h3 {
            color: #4E413B;
            margin: 0 0 15px 0;
            font-size: 16px;
        }
        .instructions ul {
            margin: 0;
            padding-left: 20px;
        }
        .instructions li {
            color: #6c757d;
            margin-bottom: 8px;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .warning-icon {
            color: #f39c12;
            font-weight: bold;
            margin-right: 8px;
        }
        .warning-text {
            color: #856404;
            font-size: 14px;
            margin: 0;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e9ecef;
        }
        .footer-text {
            color: #6c757d;
            font-size: 14px;
            margin: 0;
        }
        .footer-link {
            color: #FD7C21;
            text-decoration: none;
        }
        .expiry {
            color: #dc3545;
            font-weight: 600;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">LABERS</div>
            <h1 class="title">Email Verification</h1>
            <p class="subtitle">Complete your LABERS registration</p>
        </div>

        <div class="content">
            <p>Hello <strong>{{ $name }}</strong>,</p>
            
            <p>Thank you for registering with LABERS! To complete your account setup, please verify your email address using the verification code below:</p>

            <div class="otp-container">
                <div class="otp-label">Your Verification Code</div>
                <p class="otp-code">{{ $otp }}</p>
                <p class="otp-note">Enter this code in the verification window</p>
            </div>

            <div class="instructions">
                <h3>How to verify your email:</h3>
                <ul>
                    <li>Return to the LABERS registration page</li>
                    <li>Enter the 6-digit code above in the verification field</li>
                    <li>Click "Verify & Create Account" to complete registration</li>
                </ul>
            </div>

            <div class="warning">
                <p class="warning-text">
                    <span class="warning-icon">⚠️</span>
                    <span class="expiry">This verification code will expire in 10 minutes.</span>
                    If you don't verify within this time, you'll need to restart the registration process.
                </p>
            </div>

            <p>If you didn't request this verification code, please ignore this email. Your email address will not be registered without verification.</p>
        </div>

        <div class="footer">
            <p class="footer-text">
                This email was sent by LABERS - Gordon College Laboratory Booking System.<br>
                If you have any questions, please contact your system administrator.
            </p>
        </div>
    </div>
</body>
</html>