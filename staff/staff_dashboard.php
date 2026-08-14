<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'staff' && $_SESSION['user_role'] != 'admin')) {
    header("Location: ../login.php");
    exit();
}

// Get Statistics for Staff
$stats = [];

// Total bookings
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings");
$stmt->execute();
$stats['total_bookings'] = $stmt->fetchColumn();

// Active tickets
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE status = 'active'");
$stmt->execute();
$stats['active_tickets'] = $stmt->fetchColumn();

// Used tickets
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE status = 'used'");
$stmt->execute();
$stats['used_tickets'] = $stmt->fetchColumn();

// Recent bookings
$stmt = $pdo->prepare("SELECT * FROM bookings ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get Staff Name
$staff_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Staff';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - CamExpress</title>
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

        /* ===== STAFF HEADER ===== */
        .staff-header {
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

        .staff-header .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            font-weight: 700;
            color: #38BDF8;
            text-decoration: none;
        }

        .staff-header .staff-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .staff-header .staff-info span {
            color: #94A3B8;
            font-size: 14px;
        }

        .staff-header .staff-info span i {
            color: #38BDF8;
            margin-right: 6px;
        }

        .staff-header .staff-info .logout-btn {
            padding: 8px 20px;
            background: rgba(239, 68, 68, 0.06);
            color: #EF4444;
            border: 1px solid rgba(239, 68, 68, 0.06);
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .staff-header .staff-info .logout-btn:hover {
            background: rgba(239, 68, 68, 0.12);
        }

        /* ===== STAFF CONTAINER ===== */
        .staff-container {
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

        /* Stat Colors */
        .stat-card.blue .stat-icon {
            color: #38BDF8;
        }

        .stat-card.green .stat-icon {
            color: #34D399;
        }

        .stat-card.orange .stat-icon {
            color: #F59E0B;
        }

        /* ===== STAFF ACTIONS ===== */
        .staff-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 32px;
        }

        .staff-actions a {
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

        .staff-actions a:hover {
            background: #38BDF8;
            border-color: #38BDF8;
            transform: translateY(-2px);
        }

        .staff-actions a.primary {
            background: #38BDF8;
            border-color: #38BDF8;
        }

        .staff-actions a.primary:hover {
            background: #0EA5E9;
            border-color: #0EA5E9;
        }

        .staff-actions a.green {
            background: #34D399;
            border-color: #34D399;
        }

        .staff-actions a.green:hover {
            background: #10B981;
            border-color: #10B981;
        }

        /* ===== RECENT BOOKINGS (STAFF VIEW) ===== */
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

        .badge-active {
            background: rgba(52, 211, 153, 0.06);
            color: #34D399;
            border: 1px solid rgba(52, 211, 153, 0.06);
        }

        .badge-used {
            background: rgba(56, 189, 248, 0.06);
            color: #38BDF8;
            border: 1px solid rgba(56, 189, 248, 0.06);
        }

        .badge-cancelled {
            background: rgba(239, 68, 68, 0.06);
            color: #EF4444;
            border: 1px solid rgba(239, 68, 68, 0.06);
        }

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

        /* Responsive */
        @media (max-width: 768px) {
            .staff-header {
                padding: 12px 16px;
                flex-wrap: wrap;
                gap: 10px;
            }

            .staff-container {
                padding: 20px 16px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .staff-actions {
                flex-direction: column;
            }

            .staff-actions a {
                width: 100%;
                justify-content: center;
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

    <!-- ===== STAFF HEADER ===== -->
    <header class="staff-header">
        <a href="staff_dashboard.php" class="logo">
            <i class="fas fa-bus"></i>
            <span>CamExpress Staff</span>
        </a>
        <div class="staff-info">
            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($staff_name); ?></span>
            <a href="staff_logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <!-- ===== STAFF CONTENT ===== -->
    <div class="staff-container">
        <div class="welcome-section">
            <h1>Welcome back, <span><?php echo htmlspecialchars($staff_name); ?></span>!</h1>
            <p class="subtitle">Manage passenger bookings and validate tickets.</p>
        </div>

        <!-- ===== STATS ===== -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
                <div class="stat-number"><?php echo $stats['total_bookings']; ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-number"><?php echo $stats['active_tickets']; ?></div>
                <div class="stat-label">Active Tickets</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon"><i class="fas fa-history"></i></div>
                <div class="stat-number"><?php echo $stats['used_tickets']; ?></div>
                <div class="stat-label">Used Tickets</div>
            </div>
        </div>

        <!-- ===== STAFF ACTIONS ===== -->
        <div class="staff-actions">
            <a href="staff_create_booking.php" class="primary"><i class="fas fa-user-plus"></i> Assist Booking</a>
            <a href="staff_verify_ticket.php" class="green"><i class="fas fa-check-circle"></i> Verify Ticket</a>
        </div>

        <!-- ===== RECENT BOOKINGS ===== -->
        <div class="recent-section">
            <div class="section-header">
                <h2><i class="fas fa-clock" style="color: #38BDF8; margin-right: 8px;"></i> Recent Bookings</h2>
            </div>

            <?php if (count($recent_bookings) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Passenger</th>
                                <th>Route</th>
                                <th>Seat</th>
                                <th>Code</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_bookings as $booking): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($booking['customer_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($booking['route']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['seat_number']); ?></td>
                                    <td style="font-weight: 600; color: #38BDF8;"><?php echo htmlspecialchars($booking['ticket_code']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $booking['status']; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <p>No bookings yet. Assist a passenger to create a booking!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>