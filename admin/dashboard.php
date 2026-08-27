<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Check if user is logged in AND is admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login/login.php');
    exit();
}

// ✅ Check if user has admin role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../home.php');
    exit();
}

// Include database connection
require_once '../config/database.php';

// Get statistics
$stats = [];

// Total schedules
$stmt = $pdo->query("SELECT COUNT(*) as total FROM schedule WHERE expired = 0");
$result = $stmt->fetch();
$stats['total_schedules'] = $result ? $result['total'] : 0;

// Total routes
$stmt = $pdo->query("SELECT COUNT(*) as total FROM route");
$result = $stmt->fetch();
$stats['total_routes'] = $result ? $result['total'] : 0;

// Total buses
$stmt = $pdo->query("SELECT COUNT(*) as total FROM bus WHERE status = 'active'");
$result = $stmt->fetch();
$stats['total_buses'] = $result ? $result['total'] : 0;

// Total users
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$result = $stmt->fetch();
$stats['total_users'] = $result ? $result['total'] : 0;

// Today's schedules
$stmt = $pdo->query("SELECT COUNT(*) as total FROM schedule WHERE DATE(departure_time) = CURDATE() AND expired = 0");
$result = $stmt->fetch();
$stats['today_schedules'] = $result ? $result['total'] : 0;

// Upcoming schedules (next 7 days)
$stmt = $pdo->query("SELECT COUNT(*) as total FROM schedule WHERE DATE(departure_time) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND expired = 0");
$result = $stmt->fetch();
$stats['upcoming_schedules'] = $result ? $result['total'] : 0;

// Expired schedules
$stmt = $pdo->query("SELECT COUNT(*) as total FROM schedule WHERE expired = 1");
$result = $stmt->fetch();
$stats['expired_schedules'] = $result ? $result['total'] : 0;

// Total reservations
$stmt = $pdo->query("SELECT COUNT(*) as total FROM reservation");
$result = $stmt->fetch();
$stats['total_reservations'] = $result ? $result['total'] : 0;

// Confirmed bookings
$stmt = $pdo->query("SELECT COUNT(*) as total FROM reservation WHERE status = 'confirmed'");
$result = $stmt->fetch();
$stats['confirmed_bookings'] = $result ? $result['total'] : 0;

// Used bookings
$stmt = $pdo->query("SELECT COUNT(*) as total FROM reservation WHERE status = 'used'");
$result = $stmt->fetch();
$stats['used_bookings'] = $result ? $result['total'] : 0;

// Pending bookings
$stmt = $pdo->query("SELECT COUNT(*) as total FROM reservation WHERE status = 'pending'");
$result = $stmt->fetch();
$stats['pending_bookings'] = $result ? $result['total'] : 0;

// Cancelled bookings
$stmt = $pdo->query("SELECT COUNT(*) as total FROM reservation WHERE status = 'cancelled'");
$result = $stmt->fetch();
$stats['cancelled_bookings'] = $result ? $result['total'] : 0;

// Total revenue
$paymentTableExists = $pdo->query("SHOW TABLES LIKE 'payment'")->fetch();
if ($paymentTableExists) {
    $stmt = $pdo->query("SELECT SUM(amount) as total FROM payment WHERE payment_status = 'completed'");
    $result = $stmt->fetch();
    $stats['total_revenue'] = (isset($result['total']) && $result['total'] !== null) ? $result['total'] : 0;
} else {
    $statusColumn = $pdo->query("SHOW COLUMNS FROM `reservation` LIKE 'status'")->fetch();
    if ($statusColumn) {
        $stmt = $pdo->query("SELECT SUM(fare_paid) as total FROM reservation WHERE status = 'confirmed'");
    } else {
        $stmt = $pdo->query("SELECT SUM(fare_paid) as total FROM reservation");
    }
    $result = $stmt->fetch();
    $stats['total_revenue'] = (isset($result['total']) && $result['total'] !== null) ? $result['total'] : 0;
}

// Recent bookings - simplified query
try {
    $statusColumn = $pdo->query("SHOW COLUMNS FROM `reservation` LIKE 'status'")->fetch();
    $dateColumn = $pdo->query("SHOW COLUMNS FROM `reservation` LIKE 'reservation_date'")->fetch();

    $selectExtras = '';
    $selectExtras .= $statusColumn ? ', r.status' : ', "pending" AS status';
    $selectExtras .= $dateColumn ? ', r.reservation_date' : ', NOW() AS reservation_date';

    $sql = "SELECT r.seat_number, r.fare_paid, r.booking_code" . $selectExtras . ", u.full_name, u.email
            FROM reservation r
            JOIN users u ON r.passenger_id = u.user_id
            ORDER BY r.reservation_id DESC
            LIMIT 10";

    $stmt = $pdo->query($sql);
    $recent_bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    $recent_bookings = [];
}

// ✅ Get admin name from session
$admin_name = 'Admin';
if (isset($_SESSION['user_name']) && !empty($_SESSION['user_name'])) {
    $admin_name = $_SESSION['user_name'];
} elseif (isset($_SESSION['admin_name']) && !empty($_SESSION['admin_name'])) {
    $admin_name = $_SESSION['admin_name'];
} elseif (isset($_SESSION['full_name']) && !empty($_SESSION['full_name'])) {
    $admin_name = $_SESSION['full_name'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CamExpress</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>
<link rel="stylesheet" href="../admin/style.css">

<body>

    <!-- ===== ADMIN HEADER ===== -->
    <header class="admin-header">
        <a href="dashboard.php" class="logo">
            <i class="fas fa-bus"></i>
            <span>CamExpress Admin</span>
        </a>
        <div class="admin-info">
            <span>
                <i class="fas fa-user"></i>
                <?php echo htmlspecialchars($admin_name); ?>
            </span>
            <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <!-- ===== ADMIN CONTENT ===== -->
    <div class="admin-container">

        <!-- ===== WELCOME ===== -->
        <div class="welcome-section">
            <h1>Welcome back, <span><?php echo htmlspecialchars($admin_name); ?></span> 👋!</h1>
            <p class="subtitle">Here's what's happening with your bus reservation system today.</p>
        </div>

        <!-- ===== STATS ===== -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-number"><?php echo $stats['total_schedules']; ?></div>
                <div class="stat-label">Active Schedules</div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon"><i class="fas fa-route"></i></div>
                <div class="stat-number"><?php echo $stats['total_routes']; ?></div>
                <div class="stat-label">Total Routes</div>
            </div>
            <div class="stat-card purple">
                <div class="stat-icon"><i class="fas fa-bus"></i></div>
                <div class="stat-number"><?php echo $stats['total_buses']; ?></div>
                <div class="stat-label">Active Buses</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card cyan">
                <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
                <div class="stat-number"><?php echo $stats['total_reservations']; ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card pink">
                <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div class="stat-number">XAF <?php echo number_format($stats['total_revenue'], 0); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>

        <!-- ===== BOOKING STATUS STATS ===== -->
        <div class="stats-grid" style="margin-bottom:32px;">
            <div class="stat-card green">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-number"><?php echo $stats['confirmed_bookings']; ?></div>
                <div class="stat-label">Confirmed</div>
            </div>
            <div class="stat-card yellow">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-number"><?php echo $stats['pending_bookings']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card blue">
                <div class="stat-icon"><i class="fas fa-check-double"></i></div>
                <div class="stat-number"><?php echo $stats['used_bookings']; ?></div>
                <div class="stat-label">Used</div>
            </div>
            <div class="stat-card red">
                <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                <div class="stat-number"><?php echo $stats['cancelled_bookings']; ?></div>
                <div class="stat-label">Cancelled</div>
            </div>
        </div>

        <!-- ===== ADMIN ACTIONS ===== -->
        <div class="admin-actions">
            <a href="schedules/index.php" class="primary"><i class="fas fa-calendar-alt"></i> Manage Schedules</a>
            <a href="schedules/create.php" class="green"><i class="fas fa-plus"></i> Add Schedule</a>
            <a href="routes/index.php"><i class="fas fa-route"></i> Manage Routes</a>
            <a href="../schedule.php"><i class="fas fa-eye"></i> View Public Schedules</a>
            <a href="../home.php"><i class="fas fa-home"></i> View Website</a>
            <a href="users.php" class="danger"><i class="fas fa-users-cog"></i> Manage Users</a>
            <a href="../staff/staff_dashboard.php"><i class="fas fa-user-shield"></i> Manage Staff</a>
            <!-- <li>
                <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i> Reports & Analytics
                </a>
            </li> -->

        </div>

        <!-- ===== RECENT BOOKINGS ===== -->
        <div class="recent-section">
            <div class="section-header">
                <h2><i class="fas fa-clock" style="color: #38BDF8; margin-right: 8px;"></i> Recent Bookings</h2>
            </div>

            <?php if (isset($recent_bookings) && count($recent_bookings) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Passenger</th>
                                <th>Seat</th>
                                <th>Code</th>
                                <th>Fare</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_bookings as $booking): ?>
                                <?php
                                $is_used = (isset($booking['status']) && $booking['status'] == 'used');
                                ?>
                                <tr class="<?php echo $is_used ? 'used-row' : ''; ?>">
                                    <td>
                                        <strong style="<?php echo $is_used ? 'color:#94A3B8;' : ''; ?>">
                                            <?php echo isset($booking['full_name']) ? htmlspecialchars($booking['full_name']) : 'Unknown'; ?>
                                        </strong>
                                        <span style="display: block; font-size: 12px; color: <?php echo $is_used ? '#64748B;' : '#94A3B8;'; ?>">
                                            <?php echo isset($booking['email']) ? htmlspecialchars($booking['email']) : ''; ?>
                                        </span>
                                    </td>
                                    <td style="<?php echo $is_used ? 'color:#94A3B8;' : ''; ?>">
                                        <?php echo isset($booking['seat_number']) ? htmlspecialchars($booking['seat_number']) : 'N/A'; ?>
                                    </td>
                                    <td>
                                        <span class="booking-code" style="font-weight:600; color:#38BDF8; <?php echo $is_used ? 'text-decoration:line-through;' : ''; ?>">
                                            <?php echo isset($booking['booking_code']) ? htmlspecialchars($booking['booking_code']) : 'N/A'; ?>
                                        </span>
                                        <?php if ($is_used): ?>
                                            <span style="color:#38BDF8; font-size:10px; margin-left:4px;">(Used)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="<?php echo $is_used ? 'color:#94A3B8;' : ''; ?>">
                                        XAF <?php echo isset($booking['fare_paid']) ? number_format($booking['fare_paid'], 0) : '0'; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status = isset($booking['status']) ? $booking['status'] : 'pending';
                                        ?>
                                        <span class="badge badge-<?php echo $status; ?>">
                                            <?php if ($is_used): ?>
                                                <i class="fas fa-check-double"></i> Used
                                            <?php else: ?>
                                                <?php echo ucfirst($status); ?>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td style="<?php echo $is_used ? 'color:#94A3B8;' : ''; ?>">
                                        <?php
                                        $date = isset($booking['reservation_date']) ? $booking['reservation_date'] : 'now';
                                        echo date('d/m/Y H:i', strtotime($date));
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <p>No bookings yet. When users book tickets, they will appear here.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

</body>

</html>