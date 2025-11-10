<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($subject) ?></title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f7; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .header { background-color: #0d6efd; color: white; padding: 20px; text-align: center; border-top-left-radius: 8px; border-top-right-radius: 8px; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px; }
        .content p { margin: 0 0 15px; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>AFRIKENKID</h1>
        </div>
        <div class="content">
            <p>Hi, <?= esc($username) ?>,</p>
    <?php
        // Sanitize the HTML content to allow only a safe subset of tags.
        // This prevents XSS attacks while preserving basic formatting.
        $allowed_tags = '<p><a><strong><em><ul><ol><li><br><h1><h2><h3><h4><h5><h6>';
        echo strip_tags($body_content, $allowed_tags);
    ?>
</div>
        <div class="footer">
            <p>&copy; <?= date('Y') ?> AFRIKENKID. All rights reserved.</p>
            <p>You are receiving this email because you are a registered user of our service.</p>
        </div>
    </div>
</body>
</html>
