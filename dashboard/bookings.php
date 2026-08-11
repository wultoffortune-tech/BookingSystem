<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login/login.php');
    exit();
}

require_once '../config/database.php';
include '../includes/header.php';

$user_id = $_SESSION['user_id'];
$statusColumn = $pdo->query("SHOW COLUMNS FROM `reservation` LIKE 'status'")->fetch();
$dateColumn = $pdo->query("SHOW COLUMNS FROM `reservation` LIKE 'reservation_date'")->fetch();
$selectStatus = $statusColumn ? 'r.status' : "'pending' AS status";
$selectDate = $dateColumn ? 'r.reservation_date' : 'NOW() AS reservation_date';
$orderBy = $dateColumn ? 'r.reservation_date DESC' : 'r.reservation_id DESC';

$sql = "SELECT r.reservation_id, r.seat_number, r.fare_paid, " . $selectStatus . ", " . $selectDate . ",
                              s.departure_time, s.arrival_time, rt.original_city, rt.destination, b.bus_name, b.bus_type
                       FROM reservation r
                       JOIN schedule s ON r.schedule_id = s.schedule_id
                       JOIN route rt ON s.route_id = rt.route_id
                       JOIN bus b ON s.bus_id = b.bus_id
                       WHERE r.passenger_id = ?
                       ORDER BY " . $orderBy;
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();
?>

<section class="page-section" style="padding: 140px 0 90px;">
    <div class="container">
        <div class="section-header">
            <span class="subtitle">Dashboard Bookings</span>
            <h2 class="section-title">Your ticket history</h2>
            <p class="section-description">See all your reservations and manage pending payments.</p>
        </div>

        <?php if (count($bookings) > 0): ?>
            <div style="display:grid; gap:24px;">
                <?php foreach ($bookings as $booking): ?>
                    <div style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.08); border-radius:24px; padding:28px;">
                        <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:16px;">
                            <div>
                                <h3 style="color:#ffffff; margin-bottom:10px;"><?php echo htmlspecialchars($booking['original_city']); ?> <i class="fas fa-arrow-right"></i> <?php echo htmlspecialchars($booking['destination']); ?></h3>
                                <p style="color:#cbd5e1; margin:0 0 8px;"><?php echo htmlspecialchars($booking['bus_name']); ?> (<?php echo htmlspecialchars($booking['bus_type']); ?>)</p>
                                <p style="color:#cbd5e1; margin:0;">Departure: <?php echo date('d M Y H:i', strtotime($booking['departure_time'])); ?></p>
                            </div>
                            <div style="text-align:right; min-width:170px;">
                                <p style="color:#94a3b8; margin:0 0 8px;">Seat <?php echo htmlspecialchars($booking['seat_number']); ?></p>
                                <p style="color:#38bdf8; font-size:20px; font-weight:700; margin:0 0 8px;">XAF <?php echo number_format($booking['fare_paid'], 0); ?></p>
                                <span style="display:inline-block; padding:8px 16px; border-radius:999px; background:rgba(59,130,246,0.12); color:#7dd3fc; font-weight:600; text-transform:capitalize;"><?php echo htmlspecialchars($booking['status']); ?></span>
                            </div>
                        </div>
                        <div style="margin-top:22px; display:flex; justify-content:space-between; flex-wrap:wrap; align-items:center; gap:12px;">
                            <small style="color:#94a3b8;">Reserved on <?php echo date('d M Y H:i', strtotime($booking['reservation_date'])); ?></small>
                            <?php if ($booking['status'] === 'pending'): ?>
                                <a href="../payment.php?reservation_id=<?php echo $booking['reservation_id']; ?>" style="background:#7c5cff; color:#ffffff; padding:12px 20px; border-radius:16px; text-decoration:none;">Pay now</a>
                            <?php else: ?>
                                <span style="color:#94a3b8;">Booking confirmed</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results" style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:24px; padding:60px 30px; text-align:center; color:#94a3b8;">
                <i class="fas fa-ticket-alt"></i>
                <h3>No bookings found</h3>
                <p>Book your first ticket to see it appear here.</p>
                <a href="../schedule.php" style="display:inline-block; margin-top:20px; background:#7c5cff; color:#ffffff; padding:14px 28px; border-radius:24px; text-decoration:none;">Browse schedules</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include '../includes/footer.php';
