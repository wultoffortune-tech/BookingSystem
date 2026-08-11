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
$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM reservation WHERE passenger_id = ?");
$stmt->execute([$user_id]);
$totalBookings = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) AS upcoming FROM reservation r
                       JOIN schedule s ON r.schedule_id = s.schedule_id
                       WHERE r.passenger_id = ? AND s.departure_time > NOW()");
$stmt->execute([$user_id]);
$upcomingBookings = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT r.reservation_id, rt.original_city, rt.destination, s.departure_time
                       FROM reservation r
                       JOIN schedule s ON r.schedule_id = s.schedule_id
                       JOIN route rt ON s.route_id = rt.route_id
                       WHERE r.passenger_id = ?
                       ORDER BY r.reservation_date DESC
                       LIMIT 5");
$stmt->execute([$user_id]);
$recentBookings = $stmt->fetchAll();
?>

<section class="page-section" style="padding: 140px 0 90px;">
    <div class="container">
        <div class="section-header">
            <span class="subtitle">My dashboard</span>
            <h2 class="section-title">Welcome back, <?php echo htmlspecialchars(isset($_SESSION['full_name']) ? $_SESSION['full_name'] : ''); ?></h2>
            <p class="section-description">Manage your bookings, profile, and upcoming trips from a single place.</p>
        </div>

        <div style="display:grid; gap:24px; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); margin-top:30px;">
            <div style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.08); border-radius:24px; padding:28px;">
                <h3 style="color:#ffffff; margin-bottom:10px;">Total bookings</h3>
                <p style="font-size:40px; font-weight:700; color:#38bdf8; margin:0;"><?php echo number_format($totalBookings); ?></p>
            </div>
            <div style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.08); border-radius:24px; padding:28px;">
                <h3 style="color:#ffffff; margin-bottom:10px;">Upcoming trips</h3>
                <p style="font-size:40px; font-weight:700; color:#7c5cff; margin:0;"><?php echo number_format($upcomingBookings); ?></p>
            </div>
            <div style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.08); border-radius:24px; padding:28px;">
                <h3 style="color:#ffffff; margin-bottom:10px;">Profile</h3>
                <p style="color:#cbd5e1; margin-bottom:20px;">Update your details or manage your account settings.</p>
                <a href="profile.php" style="display:inline-block; background:#7c5cff; color:#ffffff; padding:12px 24px; border-radius:18px; text-decoration:none;">Edit profile</a>
            </div>
        </div>

        <div style="margin-top:40px; display:grid; gap:24px;">
            <div style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.08); border-radius:24px; padding:28px;">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:24px;">
                    <div>
                        <h3 style="color:#ffffff; margin-bottom:8px;">Recent bookings</h3>
                        <p style="color:#94a3b8; margin:0;">Your five latest reservations.</p>
                    </div>
                    <a href="../bookings.php" style="color:#7c5cff; font-weight:600; text-decoration:none;">View all bookings</a>
                </div>

                <?php if (count($recentBookings) > 0): ?>
                    <div style="display:grid; gap:16px;">
                        <?php foreach ($recentBookings as $booking): ?>
                            <div style="background:rgba(0,0,0,0.08); border-radius:18px; padding:18px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                                <div>
                                    <strong style="color:#ffffff; display:block; margin-bottom:6px;"><?php echo htmlspecialchars($booking['original_city']); ?> → <?php echo htmlspecialchars($booking['destination']); ?></strong>
                                    <small style="color:#94a3b8;">Departure <?php echo date('d M Y H:i', strtotime($booking['departure_time'])); ?></small>
                                </div>
                                <span style="background:rgba(124,92,255,0.15); color:#c7d2fe; padding:8px 16px; border-radius:999px; text-transform:capitalize;">
                                    <?php echo htmlspecialchars(isset($booking['status']) ? $booking['status'] : 'pending'); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color:#94a3b8;">No recent bookings yet. Visit the schedules page to make a reservation.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php';
