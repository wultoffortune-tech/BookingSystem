<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Destroy all session data
$_SESSION = array();
session_destroy();

// Clear remember me cookies
setcookie('user_email', '', time() - 3600, '/');
setcookie('user_remember', '', time() - 3600, '/');

// Redirect to home page
header('Location: home.php');
exit();
