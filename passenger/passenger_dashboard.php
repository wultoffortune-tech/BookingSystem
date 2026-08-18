<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get booking statistics
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_bookings,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_count,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count
    FROM reservation WHERE passenger_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Get upcoming bookings
$stmt = $pdo->prepare("SELECT r.*, s.departure_time, rt.original_city, rt.destination, b.bus_name
                       FROM reservation r
                       JOIN schedule s ON r.schedule_id = s.schedule_id
                       JOIN route rt ON s.route_id = rt.route_id
                       JOIN bus b ON s.bus_id = b.bus_id
                       WHERE r.passenger_id = ? 
                       AND r.status != 'cancelled'
                       AND s.departure_time > NOW()
                       ORDER BY s.departure_time ASC
                       LIMIT 5");
$stmt->execute([$user_id]);
$upcoming_bookings = $stmt->fetchAll();

// No header include - using custom layout
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passenger Dashboard - CamExpress</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #0F172A;
            color: #E2E8F0;
            min-height: 100vh;
            display: flex;
        }

        /* ========================================
           SIDEBAR
        ======================================== */
        .sidebar {
            width: 280px;
            min-height: 100vh;
            background: #1E293B;
            border-right: 1px solid rgba(255, 255, 255, 0.04);
            padding: 24px 16px;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            overflow-y: auto;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            margin-bottom: 20px;
            text-decoration: none;
        }

        /* .sidebar .brand .logo {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #3B82F6, #2563EB);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #FFFFFF;
        } */
        .sidebar .logo-image{
    width: 60px;
    height:60px;
    border-radius:50px; 
    display: flex;
    align-items: center;
    justify-content: center;
}

        .sidebar .brand .brand-name {
            font-size: 20px;
            font-weight: 700;
            color: #FFFFFF;
        }

        .sidebar .brand .brand-name span {
            color: #3B82F6;
        }

        /* User Profile */
        .sidebar .user-profile {
            text-align: center;
            padding: 16px 0 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            margin-bottom: 20px;
        }

        .sidebar .user-profile .avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3B82F6, #2563EB);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 28px;
            font-weight: 700;
            color: #FFFFFF;
            border: 3px solid rgba(59, 130, 246, 0.2);
        }

        .sidebar .user-profile .user-name {
            color: #FFFFFF;
            font-size: 16px;
            font-weight: 600;
        }

        .sidebar .user-profile .user-email {
            color: #94A3B8;
            font-size: 13px;
            margin-top: 2px;
        }

        .sidebar .user-profile .user-role {
            display: inline-block;
            padding: 2px 14px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(59, 130, 246, 0.06);
            color: #3B82F6;
            border: 1px solid rgba(59, 130, 246, 0.06);
            margin-top: 6px;
        }

        /* Navigation */
        .sidebar .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar .nav-menu li {
            margin-bottom: 4px;
        }

        .sidebar .nav-menu li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 10px;
            color: #94A3B8;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
            font-family: 'Poppins', sans-serif;
        }

        .sidebar .nav-menu li a i {
            width: 20px;
            font-size: 16px;
            color: #475569;
            transition: all 0.3s ease;
        }

        .sidebar .nav-menu li a:hover {
            background: rgba(255, 255, 255, 0.04);
            color: #FFFFFF;
        }

        .sidebar .nav-menu li a:hover i {
            color: #3B82F6;
        }

        .sidebar .nav-menu li a.active {
            background: rgba(59, 130, 246, 0.06);
            color: #3B82F6;
            border: 1px solid rgba(59, 130, 246, 0.06);
        }

        .sidebar .nav-menu li a.active i {
            color: #3B82F6;
        }

        .sidebar .nav-menu li a .nav-badge {
            margin-left: auto;
            display: inline-block;
            padding: 1px 10px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
        }

        .sidebar .nav-menu li a .nav-badge.yellow {
            background: rgba(245, 158, 11, 0.06);
            color: #F59E0B;
            border: 1px solid rgba(245, 158, 11, 0.06);
        }

        .sidebar .nav-menu li a .nav-badge.green {
            background: rgba(16, 185, 129, 0.06);
            color: #10B981;
            border: 1px solid rgba(16, 185, 129, 0.06);
        }

        .sidebar .nav-menu li a .nav-badge.red {
            background: rgba(239, 68, 68, 0.06);
            color: #EF4444;
            border: 1px solid rgba(239, 68, 68, 0.06);
        }

        .sidebar .nav-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.04);
            margin: 12px 16px;
        }

        /* Sidebar Footer */
        .sidebar .sidebar-footer {
            position: absolute;
            bottom: 20px;
            left: 16px;
            right: 16px;
            padding-top: 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.04);
        }

        .sidebar .sidebar-footer .version {
            color: #475569;
            font-size: 12px;
            text-align: center;
        }

        /* ========================================
           MAIN CONTENT
        ======================================== */
        .main-content {
            margin-left: 280px;
            padding: 30px 40px;
            flex: 1;
            min-height: 100vh;
        }

        .main-content .page-header {
            margin-bottom: 30px;
        }

        .main-content .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #FFFFFF;
        }

        .main-content .page-header h1 span {
            color: #3B82F6;
        }

        .main-content .page-header p {
            color: #94A3B8;
            margin-top: 4px;
        }

        /* ===== STATS GRID ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #1E293B;
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 12px;
            padding: 16px 20px;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            border-color: rgba(59, 130, 246, 0.1);
        }

        .stat-card .stat-icon {
            font-size: 20px;
            margin-bottom: 4px;
        }

        .stat-card .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #FFFFFF;
        }

        .stat-card .stat-label {
            color: #94A3B8;
            font-size: 12px;
            margin-top: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card.blue .stat-icon {
            color: #3B82F6;
        }

        .stat-card.green .stat-icon {
            color: #10B981;
        }

        .stat-card.yellow .stat-icon {
            color: #F59E0B;
        }

        .stat-card.red .stat-icon {
            color: #EF4444;
        }

        /* ===== UPCOMING BOOKINGS ===== */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            margin-top: 24px;
        }

        .section-header h2 {
            color: #FFFFFF;
            font-size: 18px;
            font-weight: 600;
        }

        .section-header h2 i {
            color: #3B82F6;
            margin-right: 8px;
        }

        .section-header .view-all {
            color: #3B82F6;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .section-header .view-all:hover {
            color: #2563EB;
        }

        .booking-list {
            display: grid;
            gap: 10px;
        }

        .booking-item {
            background: #1E293B;
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
            flex-wrap: wrap;
            gap: 12px;
        }

        .booking-item:hover {
            border-color: rgba(59, 130, 246, 0.1);
        }

        .booking-item .booking-info {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .booking-item .booking-route {
            font-size: 15px;
            font-weight: 600;
            color: #FFFFFF;
        }

        .booking-item .booking-route i {
            color: #3B82F6;
            margin: 0 6px;
            font-size: 13px;
        }

        .booking-item .booking-details {
            display: flex;
            gap: 12px;
            color: #94A3B8;
            font-size: 12px;
            flex-wrap: wrap;
        }

        .booking-item .booking-details span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .booking-item .booking-details i {
            color: #3B82F6;
            font-size: 11px;
        }

        .status-badge {
            padding: 3px 12px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.pending {
            background: rgba(245, 158, 11, 0.1);
            color: #F59E0B;
            border: 1px solid rgba(245, 158, 11, 0.1);
        }

        .status-badge.confirmed {
            background: rgba(16, 185, 129, 0.1);
            color: #10B981;
            border: 1px solid rgba(16, 185, 129, 0.1);
        }

        .status-badge.cancelled {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
            border: 1px solid rgba(239, 68, 68, 0.1);
        }

        .booking-actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-sm-primary {
            background: rgba(59, 130, 246, 0.1);
            color: #3B82F6;
            border: 1px solid rgba(59, 130, 246, 0.1);
        }

        .btn-sm-primary:hover {
            background: #3B82F6;
            color: #FFFFFF;
        }

        .btn-sm-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
            border: 1px solid rgba(239, 68, 68, 0.1);
        }

        .btn-sm-danger:hover {
            background: #EF4444;
            color: #FFFFFF;
        }

        .btn-sm-success {
            background: rgba(16, 185, 129, 0.1);
            color: #10B981;
            border: 1px solid rgba(16, 185, 129, 0.1);
        }

        .btn-sm-success:hover {
            background: #10B981;
            color: #FFFFFF;
        }

        .no-bookings {
            text-align: center;
            padding: 40px 20px;
            background: #1E293B;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.04);
        }

        .no-bookings i {
            font-size: 40px;
            color: #3B82F6;
            opacity: 0.3;
            margin-bottom: 12px;
        }

        .no-bookings h3 {
            color: #FFFFFF;
            margin-bottom: 6px;
            font-size: 18px;
        }

        .no-bookings p {
            color: #94A3B8;
            font-size: 14px;
        }

        .no-bookings .btn-find {
            display: inline-block;
            margin-top: 12px;
            padding: 10px 24px;
            background: #3B82F6;
            color: #FFFFFF;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .no-bookings .btn-find:hover {
            background: #2563EB;
            transform: translateY(-2px);
        }

        /* ========================================
           MOBILE TOGGLE
        ======================================== */
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 16px;
            left: 16px;
            z-index: 1001;
            background: #1E293B;
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 8px;
            padding: 10px 12px;
            color: #FFFFFF;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .mobile-toggle:hover {
            background: rgba(255, 255, 255, 0.04);
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        /* ========================================
           RESPONSIVE
        ======================================== */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .mobile-toggle {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .sidebar-overlay.active {
                display: block;
            }

            .main-content {
                margin-left: 0;
                padding: 80px 16px 30px;
            }

            .main-content .page-header h1 {
                font-size: 22px;
            }

            .booking-item {
                flex-direction: column;
                align-items: stretch;
            }

            .booking-item .booking-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
            }

            .booking-actions {
                justify-content: flex-start;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .booking-item {
                padding: 14px;
            }

            .booking-item .booking-details {
                flex-direction: column;
                gap: 4px;
            }

            .sidebar {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <!-- ===== MOBILE TOGGLE ===== -->
    <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle sidebar">
        <i class="fas fa-bars"></i>
    </button>

    <!-- ===== SIDEBAR OVERLAY ===== -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ===== SIDEBAR ===== -->
    <aside class="sidebar" id="sidebar">
        <!-- Brand -->
        <a href="../home.php" class="brand">
            <img class="logo-image" src="../assets/images/logo.png" alt="CamExpress">
            <!-- <div class="logo"><i class="fas fa-bus"></i></div> 
            <div class="brand-name">Cam<span>Express</span></div>  -->
        </a>

        <!-- User Profile -->
        <div class="user-profile">
            <div class="avatar">
                <?php echo substr($user['full_name'], 0, 1); ?>
            </div>
            <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
            <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
            <span class="user-role">
                <i class="fas fa-user"></i> Passenger
            </span>
        </div>

        <!-- Navigation Menu -->
        <ul class="nav-menu">
            <li>
                <a href="passenger_dashboard.php" class="active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="../schedule.php">
                    <i class="fas fa-search"></i> Find Buses
                </a>
            </li>
            <li>
                <a href="../bookings.php">
                    <i class="fas fa-ticket-alt"></i> My Bookings
                    <?php if ($stats['pending_count'] > 0): ?>
                        <span class="nav-badge yellow"><?php echo $stats['pending_count']; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="../payment.php">
                    <i class="fas fa-credit-card"></i> Make Payment
                    <?php if ($stats['pending_count'] > 0): ?>
                        <span class="nav-badge yellow">Pay</span>
                    <?php endif; ?>
                </a>
            </li>
            <div class="nav-divider"></div>
            <li>
                <a href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>

        <!-- Sidebar Footer -->
        <div class="sidebar-footer">
            <div class="version">CamExpress v2.0</div>
        </div>
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1>Welcome back, <span><?php echo htmlspecialchars($user['full_name']); ?></span>! 👋</h1>
            <p>Manage your bookings, find new trips, and track your travel history.</p>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
                <div class="stat-number"><?php echo $stats['total_bookings']; ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-number"><?php echo $stats['confirmed_count']; ?></div>
                <div class="stat-label">Confirmed</div>
            </div>
            <div class="stat-card yellow">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-number"><?php echo $stats['pending_count']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card red">
                <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                <div class="stat-number"><?php echo $stats['cancelled_count']; ?></div>
                <div class="stat-label">Cancelled</div>
            </div>
        </div>

        <!-- Upcoming Bookings -->
        <div class="section-header">
            <h2><i class="fas fa-calendar-alt"></i> Upcoming Trips</h2>
            <a href="../bookings.php" class="view-all">View All →</a>
        </div>

        <?php if (count($upcoming_bookings) > 0): ?>
            <div class="booking-list">
                <?php foreach ($upcoming_bookings as $booking): ?>
                    <div class="booking-item">
                        <div class="booking-info">
                            <div class="booking-route">
                                <?php echo htmlspecialchars($booking['original_city']); ?>
                                <i class="fas fa-arrow-right"></i>
                                <?php echo htmlspecialchars($booking['destination']); ?>
                            </div>
                            <div class="booking-details">
                                <span><i class="fas fa-bus"></i> <?php echo htmlspecialchars($booking['bus_name']); ?></span>
                                <span><i class="fas fa-chair"></i> Seat <?php echo htmlspecialchars($booking['seat_number']); ?></span>
                                <span><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($booking['departure_time'])); ?></span>
                                <span><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($booking['departure_time'])); ?></span>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <span class="status-badge <?php echo $booking['status']; ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                            <div class="booking-actions">
                                <?php if ($booking['status'] != 'cancelled'): ?>
                                    <a href="../booking.php?schedule_id=<?php echo $booking['schedule_id']; ?>" class="btn-sm btn-sm-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="../bookings.php?cancel=<?php echo $booking['reservation_id']; ?>" class="btn-sm btn-sm-danger" onclick="return confirm('Are you sure you want to cancel this booking?')">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                <?php endif; ?>
                                <?php if ($booking['status'] == 'pending'): ?>
                                    <a href="../payment.php?reservation_id=<?php echo $booking['reservation_id']; ?>" class="btn-sm btn-sm-success">
                                        <i class="fas fa-credit-card"></i> Pay
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-bookings">
                <i class="fas fa-calendar-plus"></i>
                <h3>No Upcoming Trips</h3>
                <p>You don't have any upcoming bookings. Start planning your next journey!</p>
                <a href="../schedule.php" class="btn-find">
                    <i class="fas fa-search"></i> Find Buses
                </a>
            </div>
        <?php endif; ?>

    </main>

    <!-- ===== SIDEBAR TOGGLE SCRIPT ===== -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileToggle = document.getElementById('mobileToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');

            function toggleSidebar() {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('active');

                const icon = mobileToggle.querySelector('i');
                if (sidebar.classList.contains('open')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }

            mobileToggle.addEventListener('click', toggleSidebar);

            overlay.addEventListener('click', function() {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
                const icon = mobileToggle.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            });

            // Close sidebar when clicking a link (on mobile)
            sidebar.querySelectorAll('a').forEach(function(link) {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        sidebar.classList.remove('open');
                        overlay.classList.remove('active');
                        const icon = mobileToggle.querySelector('i');
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                });
            });
        });
    </script>

</body>

</html>