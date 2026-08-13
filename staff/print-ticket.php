<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if staff is logged in
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($booking_id > 0) {
    $stmt = $pdo->prepare("SELECT r.*, u.full_name, u.email, u.phone_number,
                           s.departure_time, s.arrival_time,
                           rt.original_city, rt.destination,
                           b.bus_name, b.bus_type, b.plate_number
                           FROM reservation r
                           JOIN users u ON r.passenger_id = u.user_id
                           JOIN schedule s ON r.schedule_id = s.schedule_id
                           JOIN route rt ON s.route_id = rt.route_id
                           JOIN bus b ON s.bus_id = b.bus_id
                           WHERE r.reservation_id = ?");
    $stmt->execute([$booking_id]);
    $ticket = $stmt->fetch();
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
            font-family: 'Poppins', sans-serif;
            background: #F5F5F5;
            display: flex;
            justify-content: center;
            padding: 40px 20px;
        }

        .ticket-container {
            max-width: 800px;
            width: 100%;
        }

        .ticket {
            background: #FFFFFF;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
            border: 1px solid #E8EDF2;
        }

        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #F1F5F9;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }

        .ticket-header .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: 700;
            color: #1A1A2E;
        }

        .ticket-header .brand i {
            color: #F59E0B;
        }

        .ticket-header .ticket-id {
            font-size: 14px;
            color: #94A3B8;
        }

        .ticket-header .ticket-id strong {
            color: #1A1A2E;
        }

        .ticket-body {
            display: grid;
            gap: 16px;
        }

        .ticket-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #F8FAFC;
        }

        .ticket-row .label {
            color: #94A3B8;
            font-size: 14px;
        }

        .ticket-row .value {
            color: #1A1A2E;
            font-weight: 600;
            font-size: 15px;
        }

        .ticket-route {
            background: #F8FAFC;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin: 16px 0;
        }

        .ticket-route .cities {
            font-size: 28px;
            font-weight: 700;
            color: #1A1A2E;
        }

        .ticket-route .arrow {
            color: #F59E0B;
            margin: 0 12px;
        }

        .ticket-route .bus-info {
            color: #94A3B8;
            font-size: 14px;
            margin-top: 6px;
        }

        .ticket-footer {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 2px solid #F1F5F9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .ticket-footer .status {
            padding: 6px 16px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .ticket-footer .status.confirmed {
            background: #D1FAE5;
            color: #065F46;
        }

        .ticket-footer .status.pending {
            background: #FEF3C7;
            color: #92400E;
        }

        .btn-print {
            padding: 10px 28px;
            background: #2563EB;
            color: #FFFFFF;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-print:hover {
            background: #1D4ED8;
            transform: translateY(-2px);
        }

        .btn-back {
            display: inline-block;
            padding: 10px 24px;
            background: #F1F5F9;
            color: #1A1A2E;
            text-decoration: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-top: 16px;
        }

        .btn-back:hover {
            background: #E2E8F0;
        }

        @media print {
            body {
                background: white;
                padding: 20px;
            }

            .ticket {
                box-shadow: none;
                border: 1px solid #ddd;
            }

            .no-print {
                display: none !important;
            }

            .btn-print,
            .btn-back {
                display: none !important;
            }
        }

        @media (max-width: 480px) {
            .ticket {
                padding: 20px;
            }

            .ticket-route .cities {
                font-size: 20px;
            }

            .ticket-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }
    </style>
</head>

<body>

    <div class="ticket-container">

        <?php if ($ticket): ?>
            <div class="ticket" id="ticket">

                <!-- Ticket Header -->
                <div class="ticket-header">
                    <div class="brand">
                        <i class="fas fa-bus"></i> CamExpress
                    </div>
                    <div class="ticket-id">
                        Ticket #<strong><?php echo $ticket['reservation_id']; ?></strong>
                    </div>
                </div>

                <!-- Route -->
                <div class="ticket-route">
                    <div class="cities">
                        <?php echo htmlspecialchars($ticket['original_city']); ?>
                        <span class="arrow"><i class="fas fa-arrow-right"></i></span>
                        <?php echo htmlspecialchars($ticket['destination']); ?>
                    </div>
                    <div class="bus-info">
                        <i class="fas fa-bus"></i> <?php echo htmlspecialchars($ticket['bus_name']); ?>
                        (<?php echo htmlspecialchars($ticket['bus_type']); ?>)
                        | <i class="fas fa-chair"></i> Seat <?php echo htmlspecialchars($ticket['seat_number']); ?>
                        | <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($ticket['plate_number']); ?>
                    </div>
                </div>

                <!-- Ticket Details -->
                <div class="ticket-body">
                    <div class="ticket-row">
                        <span class="label">Passenger</span>
                        <span class="value"><?php echo htmlspecialchars($ticket['full_name']); ?></span>
                    </div>
                    <div class="ticket-row">
                        <span class="label">Email</span>
                        <span class="value"><?php echo htmlspecialchars($ticket['email']); ?></span>
                    </div>
                    <div class="ticket-row">
                        <span class="label">Phone</span>
                        <span class="value"><?php echo htmlspecialchars($ticket['phone_number']); ?></span>
                    </div>
                    <div class="ticket-row">
                        <span class="label">Departure</span>
                        <span class="value"><?php echo date('d M Y H:i', strtotime($ticket['departure_time'])); ?></span>
                    </div>
                    <div class="ticket-row">
                        <span class="label">Arrival</span>
                        <span class="value"><?php echo date('d M Y H:i', strtotime($ticket['arrival_time'])); ?></span>
                    </div>
                    <div class="ticket-row">
                        <span class="label">Fare</span>
                        <span class="value">XAF <?php echo number_format($ticket['fare_paid'], 0); ?></span>
                    </div>
                    <div class="ticket-row">
                        <span class="label">Seat Number</span>
                        <span class="value"><?php echo htmlspecialchars($ticket['seat_number']); ?></span>
                    </div>
                </div>

                <!-- Footer -->
                <div class="ticket-footer">
                    <?php
                    // ✅ FIXED: Using isset() instead of ?? operator
                    $status = isset($ticket['status']) ? $ticket['status'] : 'pending';
                    ?>
                    <span class="status <?php echo $status; ?>">
                        <?php echo ucfirst($status); ?>
                    </span>
                    <span style="color: #94A3B8; font-size: 13px;">
                        <i class="fas fa-qrcode"></i> Scan to verify
                    </span>
                </div>

            </div>

            <!-- Action Buttons -->
            <div class="no-print" style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 16px; justify-content: center;">
                <button onclick="window.print()" class="btn-print">
                    <i class="fas fa-print"></i> Print Ticket
                </button>
                <a href="dashboard.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="verify-ticket.php?id=<?php echo $ticket['reservation_id']; ?>" class="btn-back" style="background: #2563EB; color: white;">
                    <i class="fas fa-check"></i> Verify
                </a>
            </div>

        <?php else: ?>
            <div style="background: white; padding: 40px; border-radius: 16px; text-align: center;">
                <i class="fas fa-ticket-alt" style="font-size: 48px; color: #94A3B8; margin-bottom: 16px;"></i>
                <h3 style="color: #1A1A2E; margin-bottom: 8px;">Ticket Not Found</h3>
                <p style="color: #94A3B8;">The requested ticket could not be found.</p>
                <a href="dashboard.php" class="btn-back" style="margin-top: 16px; display: inline-block;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        <?php endif; ?>

    </div>

</body>

</html>