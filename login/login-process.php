<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection - go up one level
require_once '../config/database.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get form data
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) ? true : false;

    $response = array('success' => false, 'message' => '', 'redirect' => '', 'role' => '');

    // Validate inputs
    if (empty($email) || empty($password)) {
        $response['message'] = 'Please fill in all fields.';
        echo json_encode($response);
        exit();
    }

    // Query database for user
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(array('email' => $email));
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if user exists and password matches
        if ($user && password_verify($password, $user['password'])) {

            // Login successful - set session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_role'] = isset($user['role']) ? $user['role'] : 'user';
            $_SESSION['logged_in'] = true;

            // Remember me - set cookie for 30 days
            if ($remember) {
                setcookie('user_email', $email, time() + (86400 * 30), '/');
                setcookie('user_remember', 'true', time() + (86400 * 30), '/');
            }

            $response['success'] = true;
            $response['message'] = 'Login successful!';
            $response['role'] = $user['role'];

            // ✅ FIXED: Correct redirect paths based on role
            if ($user['role'] === 'admin') {
                // Admin redirect
                $response['redirect'] = '../admin/dashboard.php';
            } elseif ($user['role'] === 'staff') {
                // Staff redirect - check if staff record exists
                $stmt2 = $pdo->prepare("SELECT * FROM staff WHERE user_id = ? AND status = 'active'");
                $stmt2->execute([$user['user_id']]);
                $staff = $stmt2->fetch();
                
                if ($staff) {
                    $_SESSION['staff_logged_in'] = true;
                    $_SESSION['staff_id'] = $staff['staff_id'];
                    $_SESSION['staff_position'] = $staff['position'];
                    $response['redirect'] = '../staff/staff_dashboard.php';
                } else {
                    // Staff record not found or inactive - redirect to home
                    $response['redirect'] = '../home.php';
                }
            } else {
                // Regular passenger - redirect to passenger dashboard
                $response['redirect'] = '../passenger/passenger_dashboard.php';
            }

            echo json_encode($response);
            exit();
        } else {
            $response['message'] = 'Invalid email or password. Please try again.';
            echo json_encode($response);
            exit();
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error. Please try again.';
        echo json_encode($response);
        exit();
    }
} else {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit();
}