<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <title>Password Reset Request</title>
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

        .reset-button {
            display: inline-block;
            padding: 14px 28px;
            font-size: 16px;
            font-weight: bold;
            color: #ffffff;
            background-color: #fe0002;
            text-decoration: none;
            border-radius: 8px;
            margin: 20px 0;
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

            .reset-button {
                background-color: #ffffff;
                color: #fe0002;
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
                <h2>Set Password</h2>
            </div>
            <div class="email-body">
                <p>Hello {{ $messageContent['name'] ?? 'User' }},</p>

                <p>Kindly click the button below to set your password:</p>

                <p style="text-align: center;">
                    <a href="{{ $messageContent['resetUrl'] }}" class="reset-button" target="_blank">Set Password</a>
                </p>

                <p>This link will expire in <strong>15 minutes</strong> and can only be used once.</p>

                <p>If you did not request this, please ignore this email.</p>

                <p>Regards,<br>Maypas Homes Team</p>
            </div>
            <div class="email-footer">
                &copy; {{ date('Y') }} Maypas Homes. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
