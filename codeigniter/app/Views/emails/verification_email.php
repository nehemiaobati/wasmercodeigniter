<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email Address</title>
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
            <h1>Welcome to AFRIKENKID!</h1>
        </div>
        <div class="content">
            <p>Hello <?= esc($name) ?>,</p>
            <p>Thank you for registering. Please click the button below to verify your email address and activate your account:</p>
            <div class="button-container">
                <a href="<?= esc($verificationLink, 'attr') ?>" class="button">Verify Email Address</a>
            </div>
            <p>Once your account is activated, you can log in and start using our services.</p>
            <hr>
            <p style="font-size: 12px; color: #777;">If you're having trouble with the button, copy and paste the URL below into your web browser:<br>
                <a href="<?= esc($verificationLink, 'attr') ?>" class="link"><?= esc($verificationLink) ?></a>
            </p>
        </div>
        <div class="footer">
            <p>&copy; <?= date('Y') ?> AFRIKENKID. All rights reserved.</p>
        </div>
    </div>
</body>
</html>