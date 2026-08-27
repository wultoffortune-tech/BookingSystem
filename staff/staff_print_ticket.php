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

</head>
<link rel="stylesheet" href="../staff/style.css">

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