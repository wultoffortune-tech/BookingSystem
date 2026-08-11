<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect based on role
// if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
//     if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
//         header('Location: ../admin/dashboard.php');
//     } else {
//         header('Location: ../home.php');
//     }
//     exit();
// }

// Include database connection
require_once '../config/database.php';

// Include header
include '../includes/header.php';
?>

<!-- ===== LOGIN CSS ===== -->
<link rel="stylesheet" href="login.css">

<!-- ===== LOGIN SECTION ===== -->
<section class="login-section">
    <div class="login-container">
        <div class="login-box">

            <!-- Back to Home
            <div class="login-back">
                <a href="../home.php">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div> -->

            <h2>Sign in to your account</h2>
            <p class="login-desc">Enter your credentials to continue</p>

            <!-- Display Error Message -->
            <?php if (isset($_SESSION['login_error'])): ?>
                <div class="login-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php
                    echo $_SESSION['login_error'];
                    unset($_SESSION['login_error']);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form class="login-form" id="loginForm" action="login-process.php" method="POST">

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="you@example.com"
                        value="<?php echo isset($_COOKIE['user_email']) ? htmlspecialchars($_COOKIE['user_email']) : ''; ?>"
                        required>
                    <span class="error-message" id="emailError"></span>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required>
                        <button type="button" class="toggle-password" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <span class="error-message" id="passwordError"></span>
                </div>



                <button type="submit" class="btn-login" id="loginBtn">
                    <span class="btn-text">Sign In</span>
                    <span class="btn-loader" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>

            </form>

            <div class="login-footer">
                <p>
                    Don't have an account?
                    <a href="../register/register.php">Create one</a>
                </p>
            </div>

            <div class="login-home-link">
                <a href="../home.php">
                    <i class="fas fa-home"></i> Return to Home
                </a>
            </div>

        </div>

        <div id="loginMessage" class="login-message" style="display: none;"></div>
    </div>
</section>

<script src="../assets/js/app.js"></script>
<script src="login.js"></script>

<?php
// Include footer
include '../includes/footer.php';
?>