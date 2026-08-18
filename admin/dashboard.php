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

    $sql = "SELECT r.seat_number, r.fare_paid" . $selectExtras . ", u.full_name, u.email
            FROM reservation r
            JOIN users u ON r.passenger_id = u.user_id
            ORDER BY r.reservation_id DESC
            LIMIT 5";

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
    <style>
        /* ===== RESET ===== */
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
        }

        /* ===== ADMIN HEADER ===== */
        .admin-header {
            background: #1E293B;
            padding: 16px 32px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .admin-header .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            font-weight: 700;
            color: #38BDF8;
            text-decoration: none;
        }

        .admin-header .logo i {
            font-size: 24px;
        }

        .admin-header .admin-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .admin-header .admin-info span {
            color: #94A3B8;
            font-size: 14px;
        }

        .admin-header .admin-info span i {
            color: #38BDF8;
            margin-right: 6px;
        }

        .admin-header .admin-info .logout-btn {
            padding: 8px 20px;
            background: rgba(239, 68, 68, 0.06);
            color: #EF4444;
            border: 1px solid rgba(239, 68, 68, 0.06);
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .admin-header .admin-info .logout-btn:hover {
            background: rgba(239, 68, 68, 0.12);
        }

        .admin-header .admin-info .logout-btn i {
            margin-right: 6px;
        }

        /* ===== ADMIN CONTAINER ===== */
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px 24px;
        }

        /* ===== WELCOME SECTION ===== */
        .welcome-section {
            margin-bottom: 32px;
        }

        .welcome-section h1 {
            font-size: 28px;
            font-weight: 700;
            color: #FFFFFF;
        }

        .welcome-section h1 span {
            color: #38BDF8;
        }

        .welcome-section .subtitle {
            color: #94A3B8;
            font-size: 14px;
            margin-top: 4px;
        }

        /* ===== STATS GRID ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: #1E293B;
            padding: 20px 24px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.02);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            border-color: rgba(56, 189, 248, 0.10);
            transform: translateY(-2px);
        }

        .stat-card .stat-icon {
            font-size: 20px;
            color: #38BDF8;
            margin-bottom: 6px;
        }

        .stat-card .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #FFFFFF;
        }

        .stat-card .stat-label {
            font-size: 13px;
            color: #94A3B8;
            margin-top: 4px;
        }

        .stat-card .stat-change {
            font-size: 12px;
            margin-top: 6px;
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
        }

        .stat-card .stat-change.up {
            background: rgba(52, 211, 153, 0.06);
            color: #34D399;
        }

        .stat-card .stat-change.down {
            background: rgba(239, 68, 68, 0.06);
            color: #EF4444;
        }

        /* ===== STATS COLORS ===== */
        .stat-card.blue .stat-icon {
            color: #38BDF8;
        }

        .stat-card.green .stat-icon {
            color: #34D399;
        }

        .stat-card.purple .stat-icon {
            color: #8B5CF6;
        }

        .stat-card.orange .stat-icon {
            color: #F59E0B;
        }

        .stat-card.pink .stat-icon {
            color: #EC4899;
        }

        .stat-card.cyan .stat-icon {
            color: #06B6D4;
        }

        /* ===== ADMIN ACTIONS ===== */
        .admin-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 32px;
        }

        .admin-actions a {
            padding: 12px 24px;
            background: #1E293B;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            border: 1px solid rgba(255, 255, 255, 0.04);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .admin-actions a:hover {
            background: #38BDF8;
            border-color: #38BDF8;
            transform: translateY(-2px);
        }

        .admin-actions a i {
            font-size: 16px;
        }

        .admin-actions a.primary {
            background: #38BDF8;
            border-color: #38BDF8;
        }

        .admin-actions a.primary:hover {
            background: #0EA5E9;
            border-color: #0EA5E9;
        }

        .admin-actions a.green {
            background: #34D399;
            border-color: #34D399;
        }

        .admin-actions a.green:hover {
            background: #10B981;
            border-color: #10B981;
        }

        .admin-actions a.danger {
            background: rgba(239, 68, 68, 0.06);
            color: #EF4444;
            border-color: rgba(239, 68, 68, 0.06);
        }

        .admin-actions a.danger:hover {
            background: #EF4444;
            color: #FFFFFF;
            border-color: #EF4444;
        }

        /* ===== RECENT BOOKINGS ===== */
        .recent-section {
            background: #1E293B;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.02);
            padding: 24px;
        }

        .recent-section .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .recent-section .section-header h2 {
            font-size: 18px;
            font-weight: 600;
            color: #FFFFFF;
        }

        .recent-section .section-header a {
            color: #38BDF8;
            text-decoration: none;
            font-size: 14px;
        }

        .recent-section .section-header a:hover {
            text-decoration: underline;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        table th {
            text-align: left;
            padding: 12px 16px;
            color: #94A3B8;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }

        table td {
            padding: 12px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.02);
        }

        table tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-pending {
            background: rgba(245, 158, 11, 0.06);
            color: #F59E0B;
        }

        .badge-confirmed {
            background: rgba(52, 211, 153, 0.06);
            color: #34D399;
        }

        .badge-cancelled {
            background: rgba(239, 68, 68, 0.06);
            color: #EF4444;
        }

        .badge-completed {
            background: rgba(56, 189, 248, 0.06);
            color: #38BDF8;
        }

        /* ===== NO DATA ===== */
        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: #94A3B8;
        }

        .no-data i {
            font-size: 40px;
            margin-bottom: 12px;
            opacity: 0.3;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .admin-header {
                padding: 12px 16px;
                flex-wrap: wrap;
                gap: 10px;
            }

            .admin-container {
                padding: 20px 16px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .admin-actions {
                flex-direction: column;
            }

            .admin-actions a {
                width: 100%;
                justify-content: center;
            }

            .recent-section {
                padding: 16px;
            }

            table {
                font-size: 13px;
            }

            table th,
            table td {
                padding: 8px 10px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .welcome-section h1 {
                font-size: 22px;
            }
        }
    </style>
</head>

<body>

    <!-- ===== ADMIN HEADER ===== -->
    <header class="admin-header">
        <a href="dashboard.php" class="logo">
            <i class="fas fa-bus"></i>
            <span>CamExpress Admin</span>
        </a>
        <div class="admin-info">
            <!-- ✅ FIXED: Admin name display with fallback -->
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
            <h1>Welcome back, <span><?php echo htmlspecialchars($admin_name); ?></span>👋!</h1>
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

        <!-- ===== ADMIN ACTIONS ===== -->
        <div class="admin-actions">
            <a href="schedules/index.php" class="primary"><i class="fas fa-calendar-alt"></i> Manage Schedules</a>
            <a href="schedules/create.php" class="green"><i class="fas fa-plus"></i> Add Schedule</a>
            <a href="routes/index.php" class="blue"><i class="fas fa-route"></i> Manage Routes</a>
            <a href="../schedule.php"><i class="fas fa-eye"></i> View Public Schedules</a>
            <a href="../home.php"><i class="fas fa-home"></i> View Website</a>
            <a href="users.php" class="danger"><i class="fas fa-users-cog"></i> Manage Users</a>
            <a href="../staff/staff_dashboard.php" class="warning"><i class="fas fa-user-shield"></i> Manage Staff</a>
            <a href="../admin/counters.php" class="active"><i class="fas fa-cash-register"></i> Manage Counters</a>
        </div>

        <!-- ===== RECENT BOOKINGS ===== -->
        <div class="recent-section">
            <div class="section-header">
                <h2><i class="fas fa-clock" style="color: #38BDF8; margin-right: 8px;"></i> Recent Bookings</h2>
                <!-- <a href="bookings.php">View All →</a> -->
            </div>

            <?php if (isset($recent_bookings) && count($recent_bookings) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Passenger</th>
                                <th>Seat</th>
                                <th>Fare</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_bookings as $booking): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo isset($booking['full_name']) ? htmlspecialchars($booking['full_name']) : 'Unknown'; ?></strong>
                                        <span style="display: block; font-size: 12px; color: #94A3B8;"><?php echo isset($booking['email']) ? htmlspecialchars($booking['email']) : ''; ?></span>
                                    </td>
                                    <td><?php echo isset($booking['seat_number']) ? htmlspecialchars($booking['seat_number']) : 'N/A'; ?></td>
                                    <td>XAF <?php echo isset($booking['fare_paid']) ? number_format($booking['fare_paid'], 0) : '0'; ?></td>
                                    <td>
                                        <?php
                                        $status = isset($booking['status']) ? $booking['status'] : 'pending';
                                        ?>
                                        <span class="badge badge-<?php echo $status; ?>">
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                    </td>
                                    <td>
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