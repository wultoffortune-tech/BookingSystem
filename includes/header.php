<?php
$base_url = '/booking-system/';

// Get current page name for active link detection
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CamExpress - Premium Bus Reservation</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/style.css">
</head>

<body>

    <!-- ===== NAVIGATION ===== -->
    <nav class="navbar">
        <div class="container">
            <a href="<?php echo $base_url; ?>home.php" class="nav-brand">
                <img src="<?php echo $base_url; ?>assets/images/logo.png" alt="CamExpress logo" class="logo-image">
                <span>Cam<span class="highlight">Express</span></span>
            </a>
            <ul class="nav-links">
                <!-- HOME -->
                <li>
                    <a href="<?php echo $base_url; ?>home.php" class="<?php echo ($current_page == 'home.php' || $current_page == 'index.php' || $current_page == '') ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i> Home
                    </a>
                </li>

                <!-- ROUTES -->
                <li>
                    <a href="<?php echo $base_url; ?>routes.php" class="<?php echo ($current_page == 'routes.php') ? 'active' : ''; ?>">
                        <i class="fas fa-route"></i> Routes
                    </a>
                </li>

                <!-- SCHEDULE -->
                <li>
                    <a href="<?php echo $base_url; ?>schedule.php" class="<?php echo ($current_page == 'schedule.php') ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i> Schedule
                    </a>
                </li>

                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                    <!-- BOOKINGS -->
                    <li>
                        <a href="<?php echo $base_url; ?>bookings.php" class="<?php echo ($current_page == 'bookings.php') ? 'active' : ''; ?>">
                            <i class="fas fa-ticket-alt"></i> Bookings
                        </a>
                    </li>



                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <!-- ADMIN DASHBOARD -->
                        <li>
                            <a href="<?php echo $base_url; ?>admin/dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                                <i class="fas fa-user-shield"></i> Dashboard
                            </a>
                        </li>
                    <?php endif; ?>



                    <!-- LOGOUT -->
                    <li>
                        <a href="<?php echo $base_url; ?>logout.php" class="<?php echo ($current_page == 'logout.php') ? 'active' : ''; ?>">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>

                <?php else: ?>
                    <!-- LOGIN -->
                    <li>
                        <a href="<?php echo $base_url; ?>login/login.php" class="<?php echo ($current_page == 'login.php') ? 'active' : ''; ?>">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>

                    <!-- REGISTER -->
                    <li>
                        <a href="<?php echo $base_url; ?>register/register.php" class="<?php echo ($current_page == 'register.php') ? 'active' : ''; ?>">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>