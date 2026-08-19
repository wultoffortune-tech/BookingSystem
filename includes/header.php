<?php
$base_url = '/';
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

            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>

            <ul class="nav-links" id="navLinks">
                <!-- Home - Always Visible -->
                <li><a href="<?php echo $base_url; ?>home.php"><i class="fas fa-home"></i> Home</a></li>

                <!-- Public Links -->
                <li><a href="<?php echo $base_url; ?>routes.php"><i class="fas fa-route"></i> Routes</a></li>
                <li><a href="<?php echo $base_url; ?>schedule.php"><i class="fas fa-calendar-alt"></i> Schedule</a></li>

                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                    <!-- Authenticated User Links -->
                    <li><a href="<?php echo $base_url; ?>bookings.php"><i class="fas fa-ticket-alt"></i> Bookings</a></li>

                    <!-- Dashboard - Based on Role -->
                    <?php if (isset($_SESSION['user_role'])): ?>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <!-- Admin Dashboard -->
                            <li><a href="<?php echo $base_url; ?>admin/dashboard.php" class="admin-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <?php elseif ($_SESSION['user_role'] === 'staff'): ?>
                            <!-- Staff Dashboard -->
                            <li><a href="<?php echo $base_url; ?>staff/staff_dashboard.php" class="staff-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <?php else: ?>
                            <!-- Passenger Dashboard -->
                            <li><a href="<?php echo $base_url; ?>passenger/passenger_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Logout -->
                    <li><a href="<?php echo $base_url; ?>logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>

                <?php else: ?>
                    <!-- Public/Unauthenticated Links -->
                    <li><a href="<?php echo $base_url; ?>login/login.php" class="login-link"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="<?php echo $base_url; ?>register/register.php" class="register-link"><i class="fas fa-user-plus"></i> Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <style>
        /* ===== NAVIGATION STYLES ===== */
        .navbar {
            background: #1E293B;
            padding: 12px 0;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .navbar .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Brand / Logo */
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #FFFFFF;
            font-size: 22px;
            font-weight: 700;
        }

        .nav-brand .logo-image {
            height: 40px;
            width: auto;
        }

        .nav-brand span {
            color: #FFFFFF;
            font-weight: 700;
        }

        .nav-brand span::before {
            content: '';
            display: inline-block;
            width: 3px;
            height: 20px;
            background: #3B82F6;
            margin-right: 8px;
            vertical-align: middle;
        }

        /* Navigation Links */
        .nav-links {
            display: flex;
            align-items: center;
            gap: 4px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-links li {
            list-style: none;
        }

        .nav-links a {
            color: #94A3B8;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Poppins', sans-serif;
        }

        .nav-links a i {
            font-size: 15px;
            color: #475569;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            color: #FFFFFF;
            background: rgba(255, 255, 255, 0.04);
        }

        .nav-links a:hover i {
            color: #3B82F6;
        }

        .nav-links a.active {
            color: #3B82F6;
            background: rgba(59, 130, 246, 0.06);
        }

        .nav-links a.active i {
            color: #3B82F6;
        }

        /* Admin Link - Purple */
        .nav-links .admin-link {
            color: #8B5CF6;
        }

        .nav-links .admin-link i {
            color: #8B5CF6;
        }

        .nav-links .admin-link:hover {
            color: #A78BFA;
            background: rgba(139, 92, 246, 0.06);
        }

        .nav-links .admin-link:hover i {
            color: #A78BFA;
        }

        .nav-links .admin-link.active {
            color: #8B5CF6;
            background: rgba(139, 92, 246, 0.06);
        }

        .nav-links .admin-link.active i {
            color: #8B5CF6;
        }

        /* Staff Link - Yellow */
        .nav-links .staff-link {
            color: #F59E0B;
        }

        .nav-links .staff-link i {
            color: #F59E0B;
        }

        .nav-links .staff-link:hover {
            color: #FBBF24;
            background: rgba(245, 158, 11, 0.06);
        }

        .nav-links .staff-link:hover i {
            color: #FBBF24;
        }

        .nav-links .staff-link.active {
            color: #F59E0B;
            background: rgba(245, 158, 11, 0.06);
        }

        .nav-links .staff-link.active i {
            color: #F59E0B;
        }

        /* Login Link - Blue */
        .nav-links .login-link {
            color: #3B82F6;
        }

        .nav-links .login-link i {
            color: #3B82F6;
        }

        .nav-links .login-link:hover {
            background: rgba(59, 130, 246, 0.06);
            color: #3B82F6;
        }

        /* Register Link - Green */
        .nav-links .register-link {
            color: #10B981;
        }

        .nav-links .register-link i {
            color: #10B981;
        }

        .nav-links .register-link:hover {
            background: rgba(16, 185, 129, 0.06);
            color: #10B981;
        }

        /* Logout Link - Red */
        .nav-links .logout-link {
            color: #EF4444;
        }

        .nav-links .logout-link i {
            color: #EF4444;
        }

        .nav-links .logout-link:hover {
            background: rgba(239, 68, 68, 0.06);
            color: #EF4444;
        }

        /* Mobile Toggle Button */
        .nav-toggle {
            display: none;
            background: none;
            border: none;
            color: #FFFFFF;
            font-size: 24px;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .nav-toggle:hover {
            background: rgba(255, 255, 255, 0.04);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .nav-toggle {
                display: block;
            }

            .nav-links {
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background: #1E293B;
                flex-direction: column;
                padding: 16px 24px;
                gap: 4px;
                border-top: 1px solid rgba(255, 255, 255, 0.04);
                display: none;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            }

            .nav-links.open {
                display: flex;
            }

            .nav-links a {
                padding: 12px 16px;
                width: 100%;
                border-radius: 8px;
            }

            .nav-links a i {
                width: 20px;
            }

            .nav-links .nav-divider {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .navbar .container {
                padding: 0 16px;
            }

            .nav-brand {
                font-size: 18px;
            }

            .nav-brand .logo-image {
                height: 32px;
            }

            .nav-links a {
                font-size: 13px;
                padding: 10px 14px;
            }
        }
    </style>

    <script>
        // Mobile Navigation Toggle
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('navToggle');
            const navLinks = document.getElementById('navLinks');

            if (toggleBtn && navLinks) {
                toggleBtn.addEventListener('click', function() {
                    navLinks.classList.toggle('open');
                    const icon = this.querySelector('i');
                    if (navLinks.classList.contains('open')) {
                        icon.classList.remove('fa-bars');
                        icon.classList.add('fa-times');
                    } else {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                });

                // Close menu when clicking outside
                document.addEventListener('click', function(e) {
                    if (!navLinks.contains(e.target) && !toggleBtn.contains(e.target)) {
                        navLinks.classList.remove('open');
                        const icon = toggleBtn.querySelector('i');
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                });

                // Close menu when clicking a link (on mobile)
                navLinks.querySelectorAll('a').forEach(function(link) {
                    link.addEventListener('click', function() {
                        if (window.innerWidth <= 992) {
                            navLinks.classList.remove('open');
                            const icon = toggleBtn.querySelector('i');
                            icon.classList.remove('fa-times');
                            icon.classList.add('fa-bars');
                        }
                    });
                });
            }

            // Highlight active page
            const currentPath = window.location.pathname;
            const links = document.querySelectorAll('.nav-links a');
            links.forEach(function(link) {
                const href = link.getAttribute('href');
                if (href && currentPath.includes(href.replace('../', ''))) {
                    link.classList.add('active');
                }
            });
        });
    </script>