<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in as staff, redirect to dashboard
if (isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

require_once '../config/database.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - CamExpress</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="staff.css">
</head>

<body>

    <div class="staff-login-container">
        <div class="staff-login-box">

            <div class="staff-login-logo">
                <i class="fas fa-user-tie"></i>
                <h2>Staff Portal</h2>
                <p>Enter your credentials to access the staff dashboard</p>
            </div>

            <?php if (isset($_SESSION['staff_login_error'])): ?>
                <div class="login-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php
                    echo $_SESSION['staff_login_error'];
                    unset($_SESSION['staff_login_error']);
                    ?>
                </div>
            <?php endif; ?>

            <form action="login-process.php" method="POST">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" id="email" name="email" placeholder="staff@camexpress.cm" required>
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn-staff-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="staff-login-footer">
                <p>Return to <a href="../index.php">Home Page</a></p>
            </div>

        </div>
    </div>

</body>

</html>