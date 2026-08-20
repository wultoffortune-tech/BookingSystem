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
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
    SUM(CASE WHEN status = 'used' THEN 1 ELSE 0 END) as used_count
    FROM reservation WHERE passenger_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Get upcoming bookings
$stmt = $pdo->prepare("SELECT r.*, s.departure_time, rt.original_city, rt.destination, b.bus_name, b.bus_type
                       FROM reservation r
                       JOIN schedule s ON r.schedule_id = s.schedule_id
                       JOIN route rt ON s.route_id = rt.route_id
                       JOIN bus b ON s.bus_id = b.bus_id
                       WHERE r.passenger_id = ? 
                       AND r.status != 'cancelled'
                       AND r.status != 'used'
                       AND s.departure_time > NOW()
                       ORDER BY s.departure_time ASC
                       LIMIT 5");
$stmt->execute([$user_id]);
$upcoming_bookings = $stmt->fetchAll();

// Get used/booked history
$stmt = $pdo->prepare("SELECT r.*, s.departure_time, rt.original_city, rt.destination, b.bus_name, b.bus_type
                       FROM reservation r
                       JOIN schedule s ON r.schedule_id = s.schedule_id
                       JOIN route rt ON s.route_id = rt.route_id
                       JOIN bus b ON s.bus_id = b.bus_id
                       WHERE r.passenger_id = ? 
                       AND r.status = 'used'
                       ORDER BY s.departure_time DESC
                       LIMIT 5");
$stmt->execute([$user_id]);
$used_bookings = $stmt->fetchAll();

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

</head>
<link rel="stylesheet" href="../passenger/style.css">
<body>

    <!-- ===== MOBILE TOGGLE ===== -->
    <button class=" mobile-toggle" id="mobileToggle" aria-label="Toggle sidebar">
<i class="fas fa-bars"></i>
</button>

<!-- ===== SIDEBAR OVERLAY ===== -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ===== SIDEBAR ===== -->
<aside class="sidebar" id="sidebar">
    <!-- Brand -->
    <a href="../home.php" class="brand">
        <img class="logo-image" src="../assets/images/logo.png" alt="CamExpress">
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
        <div class="stat-card gray">
            <div class="stat-icon"><i class="fas fa-history"></i></div>
            <div class="stat-number"><?php echo $stats['used_count']; ?></div>
            <div class="stat-label">Used</div>
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
                            <?php if (!empty($booking['booking_code'])): ?>
                                <span class="booking-code-display">
                                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($booking['booking_code']); ?>
                                </span>
                            <?php endif; ?>
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
                                <?php if ($booking['status'] != 'used'): ?>
                                    <a href="../bookings.php?cancel=<?php echo $booking['reservation_id']; ?>" class="btn-sm btn-sm-danger" onclick="return confirm('Are you sure you want to cancel this booking?')">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if ($booking['status'] == 'pending'): ?>
                                <a href="../payment.php?reservation_id=<?php echo $booking['reservation_id']; ?>" class="btn-sm btn-sm-success">
                                    <i class="fas fa-credit-card"></i> Pay
                                </a>
                            <?php endif; ?>
                            <?php if ($booking['status'] == 'used'): ?>
                                <button class="btn-sm btn-sm-gray" disabled>
                                    <i class="fas fa-check-circle"></i> Used
                                </button>
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

    <!-- Used Bookings History -->
    <?php if (count($used_bookings) > 0): ?>
        <div class="section-header">
            <h2><i class="fas fa-history"></i> Recent Completed Trips</h2>
            <a href="../bookings.php" class="view-all">View All →</a>
        </div>

        <div class="booking-list">
            <?php foreach ($used_bookings as $booking): ?>
                <div class="booking-item" style="opacity: 0.7;">
                    <div class="booking-info">
                        <div class="booking-route" style="color: var(--text-light);">
                            <?php echo htmlspecialchars($booking['original_city']); ?>
                            <i class="fas fa-arrow-right"></i>
                            <?php echo htmlspecialchars($booking['destination']); ?>
                        </div>
                        <div class="booking-details">
                            <span><i class="fas fa-bus"></i> <?php echo htmlspecialchars($booking['bus_name']); ?></span>
                            <span><i class="fas fa-chair"></i> Seat <?php echo htmlspecialchars($booking['seat_number']); ?></span>
                            <span><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($booking['departure_time'])); ?></span>
                            <span><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($booking['departure_time'])); ?></span>
                            <?php if (!empty($booking['booking_code'])): ?>
                                <span class="booking-code-display used">
                                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($booking['booking_code']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <span class="status-badge <?php echo $booking['status']; ?>">
                            <?php echo ucfirst($booking['status']); ?>
                        </span>
                        <div class="booking-actions">
                            <a href="../booking.php?schedule_id=<?php echo $booking['schedule_id']; ?>" class="btn-sm btn-sm-primary">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
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