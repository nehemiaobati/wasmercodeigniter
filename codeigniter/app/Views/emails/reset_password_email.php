<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Request</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f7; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .header { background-color: #0d6efd; color: white; padding: 20px; text-align: center; border-top-left-radius: 8px; border-top-right-radius: 8px; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px; }
        .content p { margin: 0 0 15px; }
        .button-container { text-align: center; margin: 25px 0; }
        .button { display: inline-block; padding: 12px 25px; background-color: #0d6efd; color: #ffffff !important; text-decoration: none; border-radius: 5px; font-weight: 600; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #777; }
        .link { word-break: break-all; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Password Reset Request</h1>
        </div>
        <div class="content">
            <p>Hello <?= esc($name) ?>,</p>
            <p>We received a request to reset the password for your account. To proceed, please click the button below:</p>
            <div class="button-container">
                <a href="<?= esc($resetLink, 'attr') ?>" class="button">Reset Your Password</a>
            </div>
            <p>If you did not request a password reset, you can safely ignore this email. No changes will be made to your account.</p>
            <p>Please note that this link is valid for one hour.</p>
            <hr>
            <p style="font-size: 12px; color: #777;">If you're having trouble with the button, copy and paste the URL below into your web browser:<br>
                <a href="<?= esc($resetLink, 'attr') ?>" class="link"><?= esc($resetLink) ?></a>
            </p>
        </div>
        <div class="footer">
            <p>&copy; <?= date('Y') ?> AFRIKENKID. All rights reserved.</p>
        </div>
    </div>
</body>
</html>