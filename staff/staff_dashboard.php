<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'staff' && $_SESSION['user_role'] != 'admin')) {
    header("Location: ../login.php");
    exit();
}

$stats = [];

// Total bookings
$stmt = $pdo->query("SELECT COUNT(*) as total FROM reservation");
$stats['total_bookings'] = $stmt->fetchColumn();

// Active/Confirmed tickets
$stmt = $pdo->query("SELECT COUNT(*) as total FROM reservation WHERE status = 'confirmed'");
$stats['active_tickets'] = $stmt->fetchColumn();

// Cancelled tickets
$stmt = $pdo->query("SELECT COUNT(*) as total FROM reservation WHERE status = 'cancelled'");
$stats['cancelled_tickets'] = $stmt->fetchColumn();

// Recent bookings
$stmt = $pdo->prepare("SELECT r.*, u.full_name 
                       FROM reservation r 
                       JOIN users u ON r.passenger_id = u.user_id 
                       ORDER BY r.reservation_id DESC LIMIT 5");
$stmt->execute();
$recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$staff_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Staff';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Staff Dashboard - CamExpress</title>
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
        }

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
            transition: 0.3s;
        }

        .staff-header .staff-info .logout-btn:hover {
            background: rgba(239, 68, 68, 0.12);
        }

        .staff-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px 24px;
        }

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
            transition: 0.3s;
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

        .stat-card.blue .stat-icon {
            color: #38BDF8;
        }

        .stat-card.green .stat-icon {
            color: #34D399;
        }

        .stat-card.orange .stat-icon {
            color: #F59E0B;
        }

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
            transition: 0.3s;
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

        .badge-confirmed {
            background: rgba(52, 211, 153, 0.06);
            color: #34D399;
        }

        .badge-cancelled {
            background: rgba(239, 68, 68, 0.06);
            color: #EF4444;
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
    <header class="staff-header">
        <a href="staff_dashboard.php" class="logo"><i class="fas fa-bus"></i> <span>CamExpress Staff</span></a>
        <div class="staff-info">
            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($staff_name); ?></span>
            <a href="staff_logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>
    <div class="staff-container">
        <div class="welcome-section">
            <h1>Welcome back, <span><?php echo htmlspecialchars($staff_name); ?></span>!</h1>
            <p class="subtitle">Manage passenger bookings and validate tickets.</p>
        </div>
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
                <div class="stat-number"><?php echo $stats['cancelled_tickets']; ?></div>
                <div class="stat-label">Cancelled Tickets</div>
            </div>
        </div>
        <div class="staff-actions">
            <a href="staff_create_booking.php" class="primary"><i class="fas fa-user-plus"></i> Assist Booking</a>
            <a href="staff_verify_ticket.php" class="green"><i class="fas fa-check-circle"></i> Verify Ticket</a>
        </div>
        <div class="recent-section">
            <div class="section-header">
                <h2><i class="fas fa-clock" style="color:#38BDF8; margin-right:8px;"></i> Recent Bookings</h2>
            </div>
            <?php if (count($recent_bookings) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Passenger</th>
                                <th>Seat</th>
                                <th>Code</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_bookings as $booking): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($booking['full_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($booking['seat_number']); ?></td>
                                    <td style="font-weight:600;color:#38BDF8;"><?php echo htmlspecialchars($booking['booking_code']); ?></td>
                                    <td><span class="badge badge-<?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data"><i class="fas fa-inbox"></i>
                    <p>No bookings yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>