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

$message = '';
$error = '';
$booking = null;
$results = [];

// Get booking ID from URL
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get booking by ID
if ($booking_id > 0) {
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
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();
}

// Handle verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $reservation_id = isset($_POST['reservation_id']) ? (int)$_POST['reservation_id'] : 0;

    if ($reservation_id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE reservation SET status = 'confirmed', verified_by = ? WHERE reservation_id = ?");
            $stmt->execute([$_SESSION['staff_id'], $reservation_id]);
            $message = '✅ Ticket has been verified successfully!';

            // Refresh booking data
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
            $stmt->execute([$reservation_id]);
            $booking = $stmt->fetch();

            if ($booking) {
                $booking['status'] = 'confirmed';
            }
        } catch (PDOException $e) {
            $error = '❌ Unable to verify ticket. Please try again.';
        }
    }
}

// Handle search
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_ticket'])) {
    $search = isset($_POST['search']) ? trim($_POST['search']) : '';

    if (!empty($search)) {
        // ✅ FIXED: Using is_numeric() check instead of ?? operator
        if (is_numeric($search)) {
            // Search by reservation ID
            $stmt = $pdo->prepare("SELECT r.reservation_id, r.seat_number, r.fare_paid, r.status,
                                   u.full_name, u.email,
                                   rt.original_city, rt.destination
                                   FROM reservation r
                                   JOIN users u ON r.passenger_id = u.user_id
                                   JOIN schedule s ON r.schedule_id = s.schedule_id
                                   JOIN route rt ON s.route_id = rt.route_id
                                   WHERE r.reservation_id = :search");
            $stmt->execute(['search' => $search]);
        } else {
            // Search by email or name
            $stmt = $pdo->prepare("SELECT r.reservation_id, r.seat_number, r.fare_paid, r.status,
                                   u.full_name, u.email,
                                   rt.original_city, rt.destination
                                   FROM reservation r
                                   JOIN users u ON r.passenger_id = u.user_id
                                   JOIN schedule s ON r.schedule_id = s.schedule_id
                                   JOIN route rt ON s.route_id = rt.route_id
                                   WHERE u.email LIKE :search OR u.full_name LIKE :search");
            $stmt->execute(['search' => '%' . $search . '%']);
        }
        $results = $stmt->fetchAll();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Ticket - CamExpress Staff</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="staff.css">
    <style>
        .verify-container {
            padding: 100px 24px 60px;
            max-width: 900px;
            margin: 0 auto;
        }

        .verify-card {
            background: #1E293B;
            border-radius: 16px;
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.04);
            margin-bottom: 24px;
        }

        .verify-card h2 {
            color: #FFFFFF;
            font-size: 22px;
            margin-bottom: 16px;
        }

        .verify-card h2 i {
            color: #34D399;
            margin-right: 10px;
        }

        .search-box {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }

        .search-box input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.02);
            color: #FFFFFF;
            font-size: 15px;
        }

        .search-box input:focus {
            outline: none;
            border-color: #F59E0B;
        }

        .search-box button {
            padding: 12px 24px;
            background: #F59E0B;
            color: #0F172A;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.02);
            color: #CBD5E1;
        }

        .detail-row .label {
            color: #94A3B8;
        }

        .detail-row .value {
            color: #FFFFFF;
            font-weight: 500;
        }

        .btn-verify {
            padding: 12px 32px;
            background: #34D399;
            color: #0F172A;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 16px;
        }

        .btn-verify:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .btn-verify:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .message {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
        }

        .message.success {
            background: rgba(52, 211, 153, 0.06);
            border: 1px solid rgba(52, 211, 153, 0.06);
            color: #34D399;
        }

        .message.error {
            background: rgba(239, 68, 68, 0.06);
            border: 1px solid rgba(239, 68, 68, 0.06);
            color: #EF4444;
        }

        .back-link {
            display: inline-block;
            margin-top: 16px;
            color: #F59E0B;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .result-item {
            background: rgba(255, 255, 255, 0.02);
            padding: 16px;
            border-radius: 10px;
            margin-bottom: 12px;
            border: 1px solid rgba(255, 255, 255, 0.04);
        }

        .result-item .route {
            font-weight: 600;
            color: #FFFFFF;
        }

        .result-item .passenger {
            color: #94A3B8;
            font-size: 14px;
        }

        .result-item .actions {
            margin-top: 10px;
        }

        .result-item .actions a {
            padding: 6px 16px;
            background: rgba(59, 130, 246, 0.06);
            color: #3B82F6;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-confirmed {
            background: rgba(52, 211, 153, 0.06);
            color: #34D399;
        }

        .badge-pending {
            background: rgba(245, 158, 11, 0.06);
            color: #F59E0B;
        }

        .badge-cancelled {
            background: rgba(239, 68, 68, 0.06);
            color: #EF4444;
        }

        @media (max-width: 768px) {
            .search-box {
                flex-direction: column;
            }

            .verify-container {
                padding: 80px 16px 40px;
            }
        }
    </style>
</head>

<body>

    <div class="verify-container">

        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
            <h1 style="color: #FFFFFF; font-size: 28px;">
                <i class="fas fa-check-circle" style="color: #34D399;"></i> Verify Ticket
            </h1>
            <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <!-- Search Ticket -->
        <div class="verify-card">
            <h2><i class="fas fa-search"></i> Search Ticket</h2>
            <form method="POST">
                <div class="search-box">
                    <input type="text" name="search" placeholder="Enter booking ID, email, or passenger name..." required>
                    <button type="submit" name="search_ticket"><i class="fas fa-search"></i> Search</button>
                </div>
            </form>

            <?php if (count($results) > 0): ?>
                <h3 style="color: #94A3B8; font-size: 14px; margin-bottom: 12px;">Found <?php echo count($results); ?> result(s)</h3>
                <?php foreach ($results as $result): ?>
                    <div class="result-item">
                        <div class="route">
                            <?php echo htmlspecialchars($result['original_city']); ?>
                            <i class="fas fa-arrow-right" style="color: #475569; font-size: 12px;"></i>
                            <?php echo htmlspecialchars($result['destination']); ?>
                        </div>
                        <div class="passenger">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($result['full_name']); ?>
                            | <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($result['email']); ?>
                            | Seat: <?php echo htmlspecialchars($result['seat_number']); ?>
                            | <span class="badge badge-<?php echo isset($result['status']) ? $result['status'] : 'pending'; ?>">
                                <?php echo isset($result['status']) ? ucfirst($result['status']) : 'Pending'; ?>
                            </span>
                        </div>
                        <div class="actions">
                            <a href="verify-ticket.php?id=<?php echo $result['reservation_id']; ?>">
                                <i class="fas fa-check"></i> Verify This Ticket
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_ticket'])): ?>
                <div style="color: #94A3B8; text-align: center; padding: 20px;">
                    <i class="fas fa-search" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                    No tickets found. Try a different search.
                </div>
            <?php endif; ?>
        </div>

        <!-- Verify Ticket Details -->
        <?php if ($booking): ?>
            <div class="verify-card">
                <h2><i class="fas fa-ticket-alt"></i> Ticket Details</h2>

                <?php if ($message): ?>
                    <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : 'error'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="message error"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="detail-row">
                    <span class="label">Booking ID</span>
                    <span class="value">#<?php echo $booking['reservation_id']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Passenger</span>
                    <span class="value"><?php echo htmlspecialchars($booking['full_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Email</span>
                    <span class="value"><?php echo htmlspecialchars($booking['email']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Phone</span>
                    <span class="value"><?php echo htmlspecialchars($booking['phone_number']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Route</span>
                    <span class="value"><?php echo htmlspecialchars($booking['original_city']); ?> → <?php echo htmlspecialchars($booking['destination']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Bus</span>
                    <span class="value"><?php echo htmlspecialchars($booking['bus_name']); ?> (<?php echo htmlspecialchars($booking['bus_type']); ?>)</span>
                </div>
                <div class="detail-row">
                    <span class="label">Departure</span>
                    <span class="value"><?php echo date('d M Y H:i', strtotime($booking['departure_time'])); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Seat</span>
                    <span class="value"><?php echo htmlspecialchars($booking['seat_number']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Fare</span>
                    <span class="value">XAF <?php echo number_format($booking['fare_paid'], 0); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Status</span>
                    <span class="value">
                        <span class="badge badge-<?php echo isset($booking['status']) ? $booking['status'] : 'pending'; ?>">
                            <?php echo isset($booking['status']) ? ucfirst($booking['status']) : 'Pending'; ?>
                        </span>
                    </span>
                </div>

                <?php if (isset($booking['status']) && $booking['status'] !== 'confirmed'): ?>
                    <form method="POST">
                        <input type="hidden" name="reservation_id" value="<?php echo $booking['reservation_id']; ?>">
                        <button type="submit" name="verify" class="btn-verify">
                            <i class="fas fa-check"></i> Verify This Ticket
                        </button>
                    </form>
                <?php elseif (isset($booking['status']) && $booking['status'] === 'confirmed'): ?>
                    <div style="margin-top: 16px; padding: 12px 16px; background: rgba(52, 211, 153, 0.06); border-radius: 10px; color: #34D399;">
                        <i class="fas fa-check-circle"></i> This ticket has already been verified.
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="reservation_id" value="<?php echo $booking['reservation_id']; ?>">
                        <button type="submit" name="verify" class="btn-verify">
                            <i class="fas fa-check"></i> Verify This Ticket
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>

</body>

</html>