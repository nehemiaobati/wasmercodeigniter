<?php
// Stop the script if not accessed from localhost for security
// You can skip this check if you are on a development server
if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1' && $_SERVER['REMOTE_ADDR'] != '::1') {
    // Optional: die("Access Denied");
}

// This displays the current PHP configuration for the web server
phpinfo();
?>