<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    header('Location: ../home.php');
    exit();
}

// Include database connection
require_once('../config/database.php');

// Include header
include('../includes/header.php');
?>

<!-- ===== REGISTER CSS ===== -->
<link rel="stylesheet" href="register.css">

<!-- ===== REGISTER SECTION ===== -->
<section class="register-section">
    <div class="register-container">

        <!-- Register Box -->
        <div class="register-box">

            <!-- Logo -->
            <div class="register-logo form-logo-card">
                <img src="<?php echo $base_url; ?>assets/images/logo.png" alt="CamExpress logo" class="logo-image">
                <div class="logo-text">
                    <span>CamExpress</span>
                </div>
            </div>


            <h2>Create your account</h2>
            <p class="register-desc">Join us and start booking your tickets</p>

            <!-- Display Error Message -->
            <?php if (isset($_SESSION['register_error'])): ?>
                <div class="register-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php
                    echo $_SESSION['register_error'];
                    unset($_SESSION['register_error']);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Register Form -->
            <form class="register-form" id="registerForm" action="./registerprocess.php" method="POST">

                <!-- Full Name -->
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        placeholder="wultof fortune"
                        required
                        autocomplete="name">
                    <span class="error-message" id="nameError"></span>
                </div>
                <!-- Date of Birth -->

                <div class="form-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date"
                        id="date_of_birth"
                        name="date_of_birth"
                        required>
                    <span class="error-message" id="dateError"></span>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="you@example.com"
                        required
                        autocomplete="email">
                    <span class="error-message" id="emailError"></span>
                </div>

                <!-- Phone Number -->
                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input
                        type="tel"
                        id="phone_number"
                        name="phone_number"

                        placeholder="+237 6XX XXX XXX"
                        required

                        autocomplete="tel">

                    <span class="error-message" id="phoneError"></span>
                </div>

                <!-- ID Number -->
                <div class="form-group">
                    <label for="id_number">ID Number</label>
                    <input
                        type="text"
                        id="id_number"
                        name="id_number"
                        placeholder="Enter your ID number"
                        required>
                    <span class="error-message" id="idError"></span>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Min 8 characters"
                            required
                            autocomplete="new-password">
                        <button type="button" class="toggle-password" id="togglePassword" aria-pressed="false">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength" id="passwordStrength">

                        <span class="strength-text">the min is 8 characters</span>

                    </div>
                    <span class="error-message" id="passwordError"></span>
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Confirm your password"
                            required
                            autocomplete="new-password">
                        <button type="button" class="toggle-password" id="toggleConfirmPassword" aria-pressed="false">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <span class="error-message" id="confirmError"></span>
                </div>



                <!-- Register Button -->
                <button type="submit" class="btn-register" id="registerBtn" name="submit">
                    <span class="btn-text">Create Account</span>
                    <span class="btn-loader" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>

            </form>

            <!-- Login Link -->
            <div class="register-footer">
                <p>
                    Already have an account?
                    <a href="../login/login.php">Sign in</a>
                </p>
            </div>

        </div>

        <!-- Messages -->
        <div id="registerMessage" class="register-message" style="display: none;"></div>

    </div>
</section>

<!-- ===== REGISTER JAVASCRIPT ===== -->
<script src="../assets/js/app.js"></script>
<?php
// Include footer
include('../includes/footer.php');
?>