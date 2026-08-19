<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'staff' && $_SESSION['user_role'] != 'admin')) {
    header("Location: ../login/login.php");
    exit();
}

$ticket_data = null;
$error = "";
$success = "";

// Handle "Mark as Used" action (posted from the action-row form below)
if (isset($_POST['mark_used'], $_POST['reservation_id'])) {
    $reservation_id = (int)$_POST['reservation_id'];
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT reservation_id, status FROM reservation WHERE reservation_id = ? FOR UPDATE");
        $stmt->execute([$reservation_id]);
        $res = $stmt->fetch();

        if ($res && $res['status'] === 'confirmed') {
            $stmt = $pdo->prepare("UPDATE reservation SET status = 'used' WHERE reservation_id = ?");
            $stmt->execute([$reservation_id]);
            $pdo->commit();
            $success = "✅ Ticket marked as used.";
        } else {
            $pdo->rollBack();
            $error = $res
                ? "⚠️ Only a confirmed ticket can be marked as used (current status: " . $res['status'] . ")."
                : "❌ Ticket not found.";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Mark used error: " . $e->getMessage());
        $error = "❌ Unable to update the ticket. Please try again.";
    }
}

if (isset($_POST['booking_code'])) {
    $code = trim($_POST['booking_code']);
    $stmt = $pdo->prepare("SELECT r.*, u.full_name, u.email, u.phone_number,
                            s.departure_time, s.arrival_time,
                            rt.original_city, rt.destination,
                            b.bus_name, b.bus_type
                           FROM reservation r
                           JOIN users u ON r.passenger_id = u.user_id
                           JOIN schedule s ON r.schedule_id = s.schedule_id
                           JOIN route rt ON s.route_id = rt.route_id
                           JOIN bus b ON s.bus_id = b.bus_id
                           WHERE r.booking_code = ?");
    $stmt->execute([$code]);
    $ticket_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket_data) {
        $error = $error ?: "❌ Invalid Ticket Code.";
    } elseif (!in_array($ticket_data['status'], ['confirmed', 'used'], true)) {
        $error = $error ?: "Ticket status is " . $ticket_data['status'] . ".";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Verify Ticket - CamExpress</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            justify-content: center;
            align-items: center;
            padding: 40px;
        }

        .container {
            background: #1E293B;
            padding: 40px;
            border-radius: 12px;
            max-width: 800px;
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.04);
        }

        .header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .header h2 {
            margin: 0;
            color: #FFFFFF;
        }

        .header h2 i {
            color: #3B82F6;
            margin-right: 12px;
        }

        .header p {
            color: #94A3B8;
            margin-top: 6px;
        }

        .search-box {
            display: flex;
            gap: 12px;
        }

        .search-box input {
            flex: 1;
            padding: 14px 16px;
            background: #0F172A;
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 8px;
            color: #FFFFFF;
            font-size: 15px;
            outline: none;
            transition: 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .search-box input:focus {
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-box input::placeholder {
            color: #475569;
        }

        .search-box button {
            padding: 14px 24px;
            background: #3B82F6;
            color: #FFFFFF;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .search-box button:hover {
            background: #2563EB;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .error-msg {
            background: rgba(239, 68, 68, 0.06);
            color: #EF4444;
            padding: 14px;
            border-radius: 8px;
            margin-top: 20px;
            border: 1px solid rgba(239, 68, 68, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-msg i {
            font-size: 18px;
        }

        .success-msg {
            background: rgba(16, 185, 129, 0.06);
            color: #10B981;
            padding: 14px;
            border-radius: 8px;
            margin-top: 20px;
            border: 1px solid rgba(16, 185, 129, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .success-msg i {
            font-size: 18px;
        }

        /* Ticket Found Card */
        .ticket-card {
            background: #0F172A;
            padding: 25px;
            border-radius: 10px;
            margin-top: 25px;
            border: 1px solid rgba(255, 255, 255, 0.04);
        }

        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            padding-bottom: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .ticket-header h3 {
            margin: 0;
            color: #FFFFFF;
        }

        .status-badge {
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-badge i {
            margin-right: 6px;
        }

        .status-badge.confirmed {
            background: rgba(16, 185, 129, 0.06);
            color: #10B981;
            border: 1px solid rgba(16, 185, 129, 0.1);
        }

        .status-badge.used {
            background: rgba(56, 189, 248, 0.06);
            color: #38BDF8;
            border: 1px solid rgba(56, 189, 248, 0.1);
        }

        .status-badge.pending {
            background: rgba(245, 158, 11, 0.06);
            color: #F59E0B;
            border: 1px solid rgba(245, 158, 11, 0.1);
        }

        .status-badge.cancelled {
            background: rgba(239, 68, 68, 0.06);
            color: #EF4444;
            border: 1px solid rgba(239, 68, 68, 0.1);
        }

        /* Route Display in Verify */
        .verify-route {
            background: rgba(56, 189, 248, 0.04);
            border: 1px solid rgba(56, 189, 248, 0.06);
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
            text-align: center;
        }

        .verify-route .route-text {
            font-size: 18px;
            font-weight: 600;
            color: #FFFFFF;
        }

        .verify-route .route-text i {
            color: #3B82F6;
            margin: 0 8px;
            font-size: 14px;
        }

        .verify-route .route-time {
            color: #94A3B8;
            font-size: 13px;
            margin-top: 4px;
        }

        .verify-route .route-time i {
            color: #3B82F6;
            margin-right: 4px;
        }

        .verify-bus-info {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin: 8px 0 16px;
            flex-wrap: wrap;
        }

        .verify-bus-info span {
            color: #94A3B8;
            font-size: 13px;
        }

        .verify-bus-info i {
            color: #3B82F6;
            margin-right: 4px;
        }

        .ticket-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .ticket-item {
            display: flex;
            flex-direction: column;
        }

        .ticket-item strong {
            color: #94A3B8;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .ticket-item span {
            color: #FFFFFF;
            font-weight: 500;
            font-size: 16px;
            margin-top: 2px;
        }

        .ticket-code {
            color: #3B82F6 !important;
            font-weight: 700 !important;
            font-size: 18px !important;
            letter-spacing: 1px;
        }

        .action-row {
            margin-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.04);
            padding-top: 20px;
            display: flex;
            gap: 12px;
        }

        .action-row form {
            flex: 1;
            margin: 0;
        }

        .btn-print {
            background: #10B981;
            color: #FFFFFF;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            flex: 1;
            text-align: center;
            transition: 0.2s;
            font-family: 'Poppins', sans-serif;
        }

        .btn-print:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-mark {
            background: #F59E0B;
            color: #0F172A;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            flex: 1;
            transition: 0.2s;
            font-family: 'Poppins', sans-serif;
        }

        .btn-mark:hover {
            background: #D97706;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .footer {
            margin-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.04);
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .back-link {
            color: #94A3B8;
            text-decoration: none;
            transition: 0.3s;
        }

        .back-link:hover {
            color: #3B82F6;
        }

        .footer span {
            color: #475569;
            font-size: 13px;
        }

        @media (max-width: 600px) {
            .search-box {
                flex-direction: column;
            }

            .ticket-grid {
                grid-template-columns: 1fr;
            }

            .action-row {
                flex-direction: column;
            }

            .container {
                padding: 25px;
            }

            .verify-route .route-text {
                font-size: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2><i class="fa-solid fa-check-circle"></i> Verify Ticket</h2>
            <p>Enter the 8-digit code to validate a passenger's booking</p>
        </div>

        <form method="POST">
            <div class="search-box">
                <input type="text" name="booking_code" placeholder="e.g. 80451233" required>
                <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Verify</button>
            </div>
        </form>

        <?php if ($success): ?>
            <div class="success-msg">
                <i class="fa-solid fa-circle-check"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-msg">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($ticket_data): ?>
            <div class="ticket-card">
                <div class="ticket-header">
                    <h3>Booking Details</h3>
                    <span class="status-badge <?php echo $ticket_data['status']; ?>">
                        <i class="fa-regular fa-circle-check"></i> <?php echo ucfirst($ticket_data['status']); ?>
                    </span>
                </div>

                <!-- ===== ROUTE DISPLAY ===== -->
                <div class="verify-route">
                    <div class="route-text">
                        <?php echo htmlspecialchars($ticket_data['original_city']); ?>
                        <i class="fas fa-arrow-right"></i>
                        <?php echo htmlspecialchars($ticket_data['destination']); ?>
                    </div>
                    <div class="route-time">
                        <i class="fas fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($ticket_data['departure_time'])); ?>
                        &nbsp;|&nbsp;
                        <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($ticket_data['departure_time'])); ?>
                        &nbsp;→&nbsp;
                        <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($ticket_data['arrival_time'])); ?>
                    </div>
                </div>

                <!-- ===== BUS INFO ===== -->
                <div class="verify-bus-info">
                    <span><i class="fas fa-bus"></i> <?php echo htmlspecialchars($ticket_data['bus_name']); ?></span>
                    <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($ticket_data['bus_type']); ?></span>
                </div>

                <div class="ticket-grid">
                    <div class="ticket-item">
                        <strong>Code</strong>
                        <span class="ticket-code"><?php echo $ticket_data['booking_code']; ?></span>
                    </div>
                    <div class="ticket-item">
                        <strong>Passenger</strong>
                        <span><?php echo htmlspecialchars($ticket_data['full_name']); ?></span>
                    </div>
                    <div class="ticket-item">
                        <strong>Email</strong>
                        <span><?php echo htmlspecialchars($ticket_data['email']); ?></span>
                    </div>
                    <div class="ticket-item">
                        <strong>Phone</strong>
                        <span><?php echo htmlspecialchars($ticket_data['phone_number']); ?></span>
                    </div>
                    <div class="ticket-item">
                        <strong>Seat</strong>
                        <span><?php echo htmlspecialchars($ticket_data['seat_number']); ?></span>
                    </div>
                    <div class="ticket-item">
                        <strong>Fare</strong>
                        <span>XAF <?php echo number_format($ticket_data['fare_paid'], 0); ?></span>
                    </div>
                </div>
                <div class="action-row">
                    <a href="staff_print_ticket.php?id=<?php echo $ticket_data['reservation_id']; ?>" target="_blank" class="btn-print">
                        <i class="fa-solid fa-print"></i> Print Ticket
                    </a>
                    <?php if ($ticket_data['status'] === 'confirmed'): ?>
                        <form method="POST">
                            <input type="hidden" name="mark_used" value="1">
                            <input type="hidden" name="reservation_id" value="<?php echo $ticket_data['reservation_id']; ?>">
                            <input type="hidden" name="booking_code" value="<?php echo htmlspecialchars($ticket_data['booking_code']); ?>">
                            <button type="submit" class="btn-mark"
                                onclick="return confirm('Marquer le billet #<?php echo htmlspecialchars($ticket_data['booking_code']); ?> comme utilisé ?\n\nCette action ne peut pas être annulée.');">
                                <i class="fa-solid fa-ticket"></i> Marquer comme utilisé
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="footer">
            <a href="staff_dashboard.php" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> Dashboard
            </a>
            <span>Logged in as Staff</span>
        </div>
    </div>
</body>

</html>