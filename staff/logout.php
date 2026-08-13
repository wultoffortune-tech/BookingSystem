<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Destroy all session data
$_SESSION = array();
session_destroy();

// Redirect to staff login
header('Location: login.php');
exit();
