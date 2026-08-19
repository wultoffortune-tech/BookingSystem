<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'staff' && $_SESSION['user_role'] != 'admin')) {
    header("Location: ../login/login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die("Invalid ticket ID.");
}

$stmt = $pdo->prepare("SELECT r.*, u.full_name, u.email, u.phone_number, 
                        s.departure_time, s.arrival_time,
                        rt.original_city, rt.destination,
                        b.bus_name, b.bus_type
                       FROM reservation r 
                       JOIN users u ON r.passenger_id = u.user_id 
                       JOIN schedule s ON r.schedule_id = s.schedule_id
                       JOIN route rt ON s.route_id = rt.route_id
                       JOIN bus b ON s.bus_id = b.bus_id
                       WHERE r.reservation_id = ?");
$stmt->execute([$id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    die("Ticket not found.");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Ticket - CamExpress</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            text-align: center;
            padding: 40px 20px;
            font-family: 'Poppins', sans-serif;
            background: #0F172A;
            color: #E2E8F0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .ticket-box {
            background: #1E293B;
            border: 2px solid #3B82F6;
            padding: 40px 35px;
            max-width: 420px;
            width: 100%;
            margin: 0 auto;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            position: relative;
            overflow: hidden;
        }

        .ticket-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #3B82F6, #10B981, #F59E0B);
        }

        .ticket-box .logo {
            color: #3B82F6;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .ticket-box .logo i {
            margin-right: 8px;
        }

        .ticket-box .subtitle {
            color: #94A3B8;
            font-size: 13px;
            margin-top: 4px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .divider {
            border: 0;
            border-top: 2px dashed rgba(255, 255, 255, 0.06);
            margin: 20px 0;
        }

        .code {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: 4px;
            background: #0F172A;
            padding: 12px 20px;
            margin: 15px 0 10px;
            border-radius: 8px;
            color: #3B82F6;
            border: 1px solid rgba(59, 130, 246, 0.1);
            font-family: 'Courier New', monospace;
        }

        .route-display {
            background: rgba(56, 189, 248, 0.04);
            border: 1px solid rgba(56, 189, 248, 0.06);
            border-radius: 10px;
            padding: 12px 16px;
            margin: 12px 0;
            text-align: center;
        }

        .route-display .route-text {
            font-size: 20px;
            font-weight: 700;
            color: #FFFFFF;
        }

        .route-display .route-text i {
            color: #3B82F6;
            margin: 0 8px;
            font-size: 16px;
        }

        .route-display .route-time {
            color: #94A3B8;
            font-size: 13px;
            margin-top: 4px;
        }

        .route-display .route-time i {
            color: #3B82F6;
            margin-right: 4px;
        }

        .ticket-details {
            text-align: left;
            margin: 15px 0;
        }

        .ticket-details .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.02);
        }

        .ticket-details .detail-row:last-child {
            border-bottom: none;
        }

        .ticket-details .detail-label {
            color: #94A3B8;
            font-size: 13px;
            font-weight: 500;
        }

        .ticket-details .detail-value {
            color: #FFFFFF;
            font-weight: 600;
            font-size: 14px;
        }

        .ticket-details .detail-value.highlight {
            color: #3B82F6;
        }

        .ticket-details .detail-value.paid {
            color: #10B981;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 10px;
        }

        .status-badge.confirmed {
            background: rgba(16, 185, 129, 0.06);
            color: #10B981;
            border: 1px solid rgba(16, 185, 129, 0.06);
        }

        .status-badge.pending {
            background: rgba(245, 158, 11, 0.06);
            color: #F59E0B;
            border: 1px solid rgba(245, 158, 11, 0.06);
        }

        .status-badge.cancelled {
            background: rgba(239, 68, 68, 0.06);
            color: #EF4444;
            border: 1px solid rgba(239, 68, 68, 0.06);
        }

        .status-badge.used {
            background: rgba(56, 189, 248, 0.06);
            color: #38BDF8;
            border: 1px solid rgba(56, 189, 248, 0.06);
        }

        .footer-text {
            margin-top: 15px;
            color: #64748B;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        .footer-text i {
            color: #10B981;
            margin-right: 4px;
        }

        .assisted-badge {
            display: inline-block;
            background: rgba(59, 130, 246, 0.06);
            color: #3B82F6;
            padding: 2px 12px;
            border-radius: 50px;
            font-size: 11px;
            border: 1px solid rgba(59, 130, 246, 0.06);
        }

        .bus-info {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin: 8px 0;
            flex-wrap: wrap;
        }

        .bus-info span {
            color: #94A3B8;
            font-size: 13px;
        }

        .bus-info i {
            color: #3B82F6;
            margin-right: 4px;
        }

        .actions {
            margin-top: 25px;
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-print {
            background: #3B82F6;
            color: #FFFFFF;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-print:hover {
            background: #2563EB;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-close {
            background: rgba(255, 255, 255, 0.04);
            color: #94A3B8;
            border: 1px solid rgba(255, 255, 255, 0.06);
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-close:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #FFFFFF;
        }

        /* ===== PRINT STYLES ===== */
        @media print {
            body {
                background: #FFFFFF;
                padding: 20px;
            }

            .ticket-box {
                background: #FFFFFF;
                border: 2px solid #3B82F6;
                box-shadow: none;
            }

            .ticket-box::before {
                display: none;
            }

            .ticket-box .logo {
                color: #2563EB;
            }

            .ticket-box .subtitle {
                color: #6B7280;
            }

            .code {
                background: #F3F4F6;
                color: #2563EB;
                border: 1px solid #E5E7EB;
            }

            .route-display {
                background: #F3F4F6;
                border-color: #E5E7EB;
            }

            .route-display .route-text {
                color: #1F2937;
            }

            .route-display .route-time {
                color: #6B7280;
            }

            .ticket-details .detail-label {
                color: #6B7280;
            }

            .ticket-details .detail-value {
                color: #1F2937;
            }

            .ticket-details .detail-value.highlight {
                color: #2563EB;
            }

            .ticket-details .detail-value.paid {
                color: #059669;
            }

            .divider {
                border-top-color: #E5E7EB;
            }

            .footer-text {
                color: #9CA3AF;
            }

            .actions {
                display: none !important;
            }

            .status-badge.confirmed {
                background: rgba(16, 185, 129, 0.1);
                color: #059669;
                border: 1px solid rgba(16, 185, 129, 0.1);
            }

            .status-badge.pending {
                background: rgba(245, 158, 11, 0.1);
                color: #D97706;
                border: 1px solid rgba(245, 158, 11, 0.1);
            }

            .status-badge.cancelled {
                background: rgba(239, 68, 68, 0.1);
                color: #DC2626;
                border: 1px solid rgba(239, 68, 68, 0.1);
            }

            .status-badge.used {
                background: rgba(56, 189, 248, 0.1);
                color: #0284C7;
                border: 1px solid rgba(56, 189, 248, 0.1);
            }

            .assisted-badge {
                background: rgba(59, 130, 246, 0.1);
                color: #2563EB;
                border: 1px solid rgba(59, 130, 246, 0.1);
            }

            .bus-info span {
                color: #6B7280;
            }
        }

        @media (max-width: 480px) {
            .ticket-box {
                padding: 25px 20px;
            }

            .code {
                font-size: 24px;
                padding: 10px 16px;
            }

            .ticket-details .detail-row {
                flex-direction: column;
                padding: 6px 0;
            }

            .ticket-details .detail-value {
                font-size: 16px;
                margin-top: 2px;
            }

            .route-display .route-text {
                font-size: 16px;
            }

            .actions {
                flex-direction: column;
                width: 100%;
            }

            .actions button {
                width: 100%;
            }

            .bus-info {
                flex-direction: column;
                gap: 4px;
            }
        }
    </style>
</head>

<body>
    <div class="ticket-box">
        <div class="logo">
            <i class="fas fa-bus"></i> CamExpress
        </div>
        <div class="subtitle">Bus Ticket</div>

        <div class="code">#<?php echo htmlspecialchars($ticket['booking_code']); ?></div>

        <!-- ===== ROUTE DISPLAY ===== -->
        <div class="route-display">
            <div class="route-text">
                <?php echo htmlspecialchars($ticket['original_city']); ?>
                <i class="fas fa-arrow-right"></i>
                <?php echo htmlspecialchars($ticket['destination']); ?>
            </div>
            <div class="route-time">
                <i class="fas fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($ticket['departure_time'])); ?>
                &nbsp;|&nbsp;
                <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($ticket['departure_time'])); ?>
                &nbsp;→&nbsp;
                <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($ticket['arrival_time'])); ?>
            </div>
        </div>

        <!-- ===== BUS INFO ===== -->
        <div class="bus-info">
            <span><i class="fas fa-bus"></i> <?php echo htmlspecialchars($ticket['bus_name']); ?></span>
            <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($ticket['bus_type']); ?></span>
        </div>

        <hr class="divider">

        <div class="ticket-details">
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-user"></i> Passenger</span>
                <span class="detail-value"><?php echo htmlspecialchars($ticket['full_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-envelope"></i> Email</span>
                <span class="detail-value"><?php echo htmlspecialchars($ticket['email']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-phone"></i> Phone</span>
                <span class="detail-value"><?php echo htmlspecialchars($ticket['phone_number']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-chair"></i> Seat</span>
                <span class="detail-value highlight"><?php echo htmlspecialchars($ticket['seat_number']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-money-bill"></i> Fare</span>
                <span class="detail-value paid">XAF <?php echo number_format($ticket['fare_paid'], 0); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-credit-card"></i> Payment</span>
                <span class="detail-value paid">✅ Paid (Cash)</span>
            </div>
            <?php if (isset($ticket['assisted_by_staff']) && $ticket['assisted_by_staff'] == 1): ?>
                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-user-tie"></i> Assisted By</span>
                    <span class="detail-value">Staff</span>
                </div>
            <?php endif; ?>
        </div>

        <hr class="divider">

        <div>
            <span class="status-badge <?php echo $ticket['status']; ?>">
                <i class="fas <?php
                    echo $ticket['status'] == 'confirmed' ? 'fa-check-circle'
                        : ($ticket['status'] == 'pending' ? 'fa-clock'
                        : ($ticket['status'] == 'used' ? 'fa-check-double'
                        : 'fa-times-circle'));
                ?>"></i>
                <?php echo ucfirst($ticket['status']); ?>
            </span>
            <?php if (isset($ticket['assisted_by_staff']) && $ticket['assisted_by_staff'] == 1): ?>
                <span class="assisted-badge">
                    <i class="fas fa-user-tie"></i> Staff Assisted
                </span>
            <?php endif; ?>
        </div>

        <div class="footer-text">
            <i class="fas fa-check-circle"></i> Thank you for riding with CamExpress!
        </div>
    </div>

    <div class="actions">
        <button class="btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Print Ticket
        </button>
        <button class="btn-close" onclick="window.close()">
            <i class="fas fa-times"></i> Close
        </button>
    </div>

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</body>

</html>