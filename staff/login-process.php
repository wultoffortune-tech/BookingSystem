<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ✅ FIXED: Using isset() instead of ?? operator
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($email) || empty($password)) {
        $_SESSION['staff_login_error'] = 'Please fill in all fields.';
        header('Location: login.php');
        exit();
    }

    try {
        // Check if user exists and has staff or admin role
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND role IN ('staff', 'admin')");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {

            $_SESSION['staff_logged_in'] = true;
            $_SESSION['staff_id'] = $user['user_id'];
            $_SESSION['staff_name'] = $user['full_name'];
            $_SESSION['staff_email'] = $user['email'];
            $_SESSION['staff_role'] = $user['role'];

            header('Location: dashboard.php');
            exit();
        } else {
            $_SESSION['staff_login_error'] = 'Invalid email or password.';
            header('Location: login.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['staff_login_error'] = 'Database error. Please try again.';
        header('Location: login.php');
        exit();
    }
} else {
    header('Location: login.php');
    exit();
}
