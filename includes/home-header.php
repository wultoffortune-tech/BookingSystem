<?php
$base_url = '/booking-system/';
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
                <span>CamExpress</span>
            </a>
            <ul class="nav-links">
                <li><a href="<?php echo $base_url; ?>home.php" class="active"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="<?php echo $base_url; ?>routes.php"><i class="fas fa-route"></i> Routes</a></li>
                <li><a href="<?php echo $base_url; ?>schedule.php"><i class="fas fa-calendar-alt"></i> Schedule</a></li>

                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                    <li><a href="<?php echo $base_url; ?>bookings.php"><i class="fas fa-ticket-alt"></i> Bookings</a></li>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <li><a href="<?php echo $base_url; ?>admin/dashboard.php"><i class="fas fa-user-shield"></i> Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo $base_url; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <?php else: ?>
                    <li><a href="<?php echo $base_url; ?>login/login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="<?php echo $base_url; ?>register/register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>