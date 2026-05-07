<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <title>Your One-Time Password (OTP)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            background: #f4f4f7;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #333333;
            padding: 0;
            margin: 0;
        }

        .email-wrapper {
            width: 100%;
            padding: 40px 0;
            background-color: #f4f4f7;
        }

        .email-content {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .email-header {
            background-color: #fe0002;
            padding: 20px;
            color: #ffffff;
            text-align: center;
        }

        .email-body {
            padding: 30px;
            line-height: 1.6;
            font-size: 16px;
        }

        .otp-box {
            display: inline-block;
            font-size: 24px;
            font-weight: bold;
            background-color: #f0f0f0;
            padding: 15px 30px;
            border-radius: 8px;
            margin: 20px 0;
            letter-spacing: 6px;
        }

        .email-footer {
            padding: 20px;
            font-size: 12px;
            color: #888888;
            text-align: center;
        }

        @media (prefers-color-scheme: dark) {
            body {
                background-color: #1a1a1a;
                color: #cccccc;
            }

            .email-content {
                background-color: #2a2a2a;
                color: #dddddd;
            }

            .email-header {
                background-color: #fe0002;
            }

            .otp-box {
                background-color: #333333;
                color: #ffffff;
            }

            .email-footer {
                color: #aaaaaa;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-content">
            <div class="email-header">
                <h2>Your OTP Code</h2>
            </div>
            <div class="email-body">
                <p>Hello {{ $messageContent['name'] ?? 'User' }},</p>

                <p>Here is your One-Time Password (OTP) for verification:</p>

                <div class="otp-box">
                    {{ $messageContent['code'] }}
                </div>

                <p>This OTP is valid for <strong>15 minutes</strong>. Please do not share it with anyone.</p>

                <p>If you did not request this, you can safely ignore this email.</p>

                <p>Regards,<br>YourApp Team</p>
            </div>
            <div class="email-footer">
                &copy; {{ date('Y') }} Maypas Estate Manager. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
