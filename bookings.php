<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login/login.php');
    exit();
}

require_once 'config/database.php';

$user_id = $_SESSION['user_id'];

// ========================================
// HANDLE CANCELLATION - Set status to cancelled + Release seat
// ========================================
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $reservation_id = (int)$_GET['cancel'];

    try {
        $pdo->beginTransaction();

        // Lock the row to prevent double cancel
        $stmt = $pdo->prepare("SELECT reservation_id, schedule_id, seat_number, status, seats_released FROM reservation WHERE reservation_id = ? AND passenger_id = ? FOR UPDATE");
        $stmt->execute([$reservation_id, $user_id]);
        $reservation = $stmt->fetch();

        if ($reservation) {
            if ($reservation['status'] == 'cancelled') {
                $_SESSION['cancel_message'] = '⚠️ This booking is already cancelled.';
                $_SESSION['cancel_type'] = 'error';
            } else {
                // 1. Update status to cancelled + mark seat as released
                $stmt = $pdo->prepare("UPDATE reservation SET status = 'cancelled', seats_released = 1, cancelled_at = NOW() WHERE reservation_id = ? AND passenger_id = ?");
                $stmt->execute([$reservation_id, $user_id]);

                // 2. Increase available seats back - only if not already released
                if ($reservation['seats_released'] == 0) {
                    $stmt = $pdo->prepare("UPDATE schedule SET available_seats = available_seats + 1 WHERE schedule_id = ?");
                    $stmt->execute([$reservation['schedule_id']]);
                }

                $pdo->commit();

                $_SESSION['cancel_message'] = '✅ Your booking has been cancelled and seat released successfully.';
                $_SESSION['cancel_type'] = 'success';
            }
        } else {
            $_SESSION['cancel_message'] = '❌ Booking not found.';
            $_SESSION['cancel_type'] = 'error';
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Cancellation error: " . $e->getMessage());
        $_SESSION['cancel_message'] = '❌ Unable to cancel the booking. Please try again.';
        $_SESSION['cancel_type'] = 'error';
    }

    header('Location: bookings.php');
    exit();
}

// ========================================
// HANDLE DELETE - Permanently delete cancelled ticket ONLY if seat released
// ========================================
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $reservation_id = (int)$_GET['delete'];

    try {
        // Only allow deletion if status is 'cancelled' AND seats_released = 1
        $stmt = $pdo->prepare("SELECT status, seats_released FROM reservation WHERE reservation_id = ? AND passenger_id = ?");
        $stmt->execute([$reservation_id, $user_id]);
        $reservation = $stmt->fetch();

        if ($reservation && $reservation['status'] == 'cancelled' && $reservation['seats_released'] == 1) {
            // Permanently delete the cancelled reservation
            $stmt = $pdo->prepare("DELETE FROM reservation WHERE reservation_id = ? AND passenger_id = ?");
            $stmt->execute([$reservation_id, $user_id]);

            $_SESSION['cancel_message'] = '🗑️ Ticket has been deleted permanently.';
            $_SESSION['cancel_type'] = 'success';
        } else {
            $_SESSION['cancel_message'] = '❌ This ticket cannot be deleted. Seat must be released first.';
            $_SESSION['cancel_type'] = 'error';
        }
    } catch (PDOException $e) {
        error_log("Delete error: " . $e->getMessage());
        $_SESSION['cancel_message'] = '❌ Unable to delete the ticket. Please try again.';
        $_SESSION['cancel_type'] = 'error';
    }

    header('Location: bookings.php');
    exit();
}

// ✅ SELECT
$sql = "SELECT r.reservation_id, r.booking_code, r.seat_number, r.fare_paid, 
               r.status, r.seats_released,
               s.departure_time, s.arrival_time, 
               rt.original_city, rt.destination, 
               b.bus_name, b.bus_type
        FROM reservation r
        JOIN schedule s ON r.schedule_id = s.schedule_id
        JOIN route rt ON s.route_id = rt.route_id
        JOIN bus b ON s.bus_id = b.bus_id
        WHERE r.passenger_id = ?
        ORDER BY r.reservation_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();

// Get message if any
// Get message if any
$cancel_message = isset($_SESSION['cancel_message']) ? $_SESSION['cancel_message'] : '';
$cancel_type = isset($_SESSION['cancel_type']) ? $_SESSION['cancel_type'] : '';
unset($_SESSION['cancel_message']);
unset($_SESSION['cancel_type']);
// Count bookings by status
$total_bookings = count($bookings);
$pending = $confirmed = $cancelled = 0;

foreach ($bookings as $b) {
    $status = isset($b['status']) ? $b['status'] : 'pending';
    if ($status == 'pending') $pending++;
    elseif ($status == 'confirmed') $confirmed++;
    elseif ($status == 'cancelled') $cancelled++;
}

include 'includes/header.php';
?>

<!-- ===== BOOKINGS STYLES ===== -->
<style>
    .bookings-section {
        padding: 140px 0 90px;
        min-height: 70vh;
        background: #0F172A;
    }

    .bookings-section .section-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .bookings-section .section-header .subtitle {
        display: inline-block;
        font-size: 12px;
        font-weight: 600;
        color: #38BDF8;
        text-transform: uppercase;
        letter-spacing: 2px;
        background: rgba(56, 189, 248, 0.04);
        padding: 4px 24px;
        border-radius: 50px;
        margin-bottom: 8px;
    }

    .bookings-section .section-header .section-title {
        font-size: 36px;
        font-weight: 700;
        color: #FFFFFF;
        margin-bottom: 8px;
    }

    .bookings-section .section-header .section-description {
        color: #94A3B8;
        font-size: 16px;
        max-width: 560px;
        margin: 0 auto;
    }

    .bookings-seats {
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 30px;
    }

    .bookings-seats .seat {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 12px;
        padding: 12px 24px;
        text-align: center;
    }

    .bookings-seats .seat .number {
        font-size: 24px;
        font-weight: 700;
        color: #FFFFFF;
    }

    .bookings-seats .seat .label {
        font-size: 13px;
        color: #94A3B8;
    }

    .bookings-seats .seat .number.pending {
        color: #F59E0B;
    }

    .bookings-seats .seat .number.confirmed {
        color: #34D399;
    }

    .bookings-seats .seat .number.cancelled {
        color: #EF4444;
    }

    .message-box {
        padding: 14px 20px;
        border-radius: 12px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .message-box.success {
        background: rgba(52, 211, 153, 0.06);
        border: 1px solid rgba(52, 211, 153, 0.06);
        color: #34D399;
    }

    .message-box.error {
        background: rgba(239, 68, 68, 0.06);
        border: 1px solid rgba(239, 68, 68, 0.06);
        color: #EF4444;
    }

    .booking-card {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 20px;
        padding: 24px 28px;
        transition: all 0.3s ease;
        margin-bottom: 16px;
    }

    .booking-card:hover {
        border-color: rgba(56, 189, 248, 0.08);
        background: rgba(255, 255, 255, 0.04);
    }

    .booking-card .booking-header {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 12px;
    }

    .booking-card .booking-header .route {
        font-size: 20px;
        font-weight: 600;
        color: #FFFFFF;
    }

    .booking-card .booking-header .route i {
        color: #475569;
        font-size: 14px;
        margin: 0 8px;
    }

    .status-badge {
        padding: 6px 16px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-badge.pending {
        background: rgba(245, 158, 11, 0.06);
        color: #F59E0B;
        border: 1px solid rgba(245, 158, 11, 0.06);
    }

    .status-badge.confirmed {
        background: rgba(52, 211, 153, 0.06);
        color: #34D399;
        border: 1px solid rgba(52, 211, 153, 0.06);
    }

    .status-badge.cancelled {
        background: rgba(239, 68, 68, 0.06);
        color: #EF4444;
        border: 1px solid rgba(239, 68, 68, 0.06);
    }

    .booking-card .booking-details {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        color: #CBD5E1;
        font-size: 14px;
        margin-bottom: 12px;
    }

    .booking-card .booking-details span {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .booking-card .booking-details i {
        color: #38BDF8;
        width: 16px;
    }

    .booking-card .booking-footer {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
        padding-top: 12px;
        border-top: 1px solid rgba(255, 255, 255, 0.04);
    }

    .booking-card .booking-footer .price {
        font-size: 20px;
        font-weight: 700;
        color: #38BDF8;
    }

    .booking-card .booking-footer .price small {
        font-size: 13px;
        font-weight: 400;
        color: #94A3B8;
    }

    .booking-card .booking-footer .booking-id {
        color: #94A3B8;
        font-size: 13px;
    }

    .booking-card .booking-footer .booking-id .code {
        color: #38BDF8;
        font-weight: 600;
        font-family: monospace;
        font-size: 14px;
        letter-spacing: 1px;
    }

    .btn-cancel {
        padding: 8px 20px;
        background: rgba(239, 68, 68, 0.06);
        color: #EF4444;
        border: 1px solid rgba(239, 68, 68, 0.06);
        border-radius: 10px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-cancel:hover {
        background: #EF4444;
        color: #FFFFFF;
        transform: translateY(-2px);
    }

    .btn-cancel.disabled {
        opacity: 0.4;
        cursor: not-allowed;
        pointer-events: none;
        background: rgba(100, 116, 139, 0.06);
        color: #94A3B8;
        border-color: rgba(100, 116, 139, 0.06);
    }

    .btn-cancel.disabled:hover {
        background: rgba(100, 116, 139, 0.06);
        color: #94A3B8;
        transform: none;
    }

    .btn-delete {
        padding: 8px 20px;
        background: rgba(239, 68, 68, 0.08);
        color: #EF4444;
        border: 1px solid rgba(239, 68, 68, 0.08);
        border-radius: 10px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-delete:hover {
        background: #EF4444;
        color: #FFFFFF;
        transform: translateY(-2px);
    }

    .btn-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .no-results {
        text-align: center;
        padding: 60px 30px;
        color: #94A3B8;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 20px;
    }

    .no-results i {
        font-size: 48px;
        opacity: 0.3;
        margin-bottom: 16px;
    }

    .no-results h3 {
        color: #FFFFFF;
        margin-bottom: 8px;
    }

    .no-results .btn-schedule {
        display: inline-block;
        margin-top: 16px;
        padding: 12px 28px;
        background: #38BDF8;
        color: #0F172A;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .bookings-section {
            padding: 110px 0 60px;
        }

        .booking-card {
            padding: 18px;
        }

        .booking-card .booking-header .route {
            font-size: 17px;
        }

        .bookings-seats {
            gap: 12px;
        }

        .bookings-seats .seat {
            padding: 10px 16px;
            min-width: 80px;
        }

        .btn-actions {
            flex-direction: column;
            width: 100%;
        }

        .btn-actions a,
        .btn-actions button {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<!-- ===== CONFIRMATION SCRIPTS ===== -->
<script>
    function confirmCancel(bookingId, route, seat) {
        if (confirm('Are you sure you want to cancel your booking?\n\nRoute: ' + route + '\nSeat: ' + seat + '\n\n⚠️ This will release the seat and mark the ticket as cancelled.')) {
            window.location.href = 'bookings.php?cancel=' + bookingId;
        }
    }

    function confirmDelete(bookingId, route, seat) {
        if (confirm('Are you sure you want to permanently DELETE this cancelled ticket?\n\nRoute: ' + route + '\nSeat: ' + seat + '\n\n⚠️ This action cannot be undone! The ticket will be permanently removed.')) {
            window.location.href = 'bookings.php?delete=' + bookingId;
        }
    }
</script>

<!-- ===== MY BOOKINGS SECTION ===== -->
<section class="bookings-section">
    <div class="container">

        <div class="section-header">
            <span class="subtitle">My Bookings</span>
            <h2 class="section-title">Your Reserved Tickets</h2>
            <p class="section-description">Review your tickets and manage your bookings.</p>
        </div>

        <!-- Display Message -->
        <?php if ($cancel_message): ?>
            <div class="message-box <?php echo $cancel_type; ?>">
                <i class="fas <?php echo $cancel_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo $cancel_message; ?>
            </div>
        <?php endif; ?>

        <!-- Seats -->
        <div class="bookings-seats">
            <div class="seat">
                <div class="number"><?php echo $total_bookings; ?></div>
                <div class="label">Total Bookings</div>
            </div>
            <?php if ($pending > 0): ?>
                <div class="seat">
                    <div class="number pending"><?php echo $pending; ?></div>
                    <div class="label">Pending</div>
                </div>
            <?php endif; ?>
            <?php if ($confirmed > 0): ?>
                <div class="seat">
                    <div class="number confirmed"><?php echo $confirmed; ?></div>
                    <div class="label">Confirmed</div>
                </div>
            <?php endif; ?>
            <?php if ($cancelled > 0): ?>
                <div class="seat">
                    <div class="number cancelled"><?php echo $cancelled; ?></div>
                    <div class="label">Cancelled</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Bookings List -->
        <?php if (count($bookings) > 0): ?>
            <?php foreach ($bookings as $booking): ?>
                <?php
                $status = isset($booking['status']) ? $booking['status'] : 'pending';
                $is_cancelled = ($status == 'cancelled');
                ?>
                <div class="booking-card" style="<?php echo $is_cancelled ? 'opacity: 0.7; border-color: rgba(239,68,68,0.1);' : ''; ?>">

                    <div class="booking-header">
                        <div class="route">
                            <?php echo htmlspecialchars($booking['original_city']); ?>
                            <i class="fas fa-arrow-right"></i>
                            <?php echo htmlspecialchars($booking['destination']); ?>
                        </div>
                        <div>
                            <span class="status-badge <?php echo $status; ?>">
                                <?php echo $is_cancelled ? '❌ Cancelled' : ucfirst($status); ?>
                            </span>
                        </div>
                    </div>

                    <div class="booking-details">
                        <span><i class="fas fa-bus"></i> <?php echo htmlspecialchars($booking['bus_name']); ?> (<?php echo htmlspecialchars($booking['bus_type']); ?>)</span>
                        <span><i class="fas fa-chair"></i> Seat <?php echo htmlspecialchars($booking['seat_number']); ?></span>
                        <span><i class="fas fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($booking['departure_time'])); ?></span>
                        <span><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($booking['departure_time'])); ?></span>
                        <?php if ($is_cancelled): ?>
                            <span style="color: #EF4444;"><i class="fas fa-ban"></i> Cancelled</span>
                        <?php endif; ?>
                    </div>

                    <div class="booking-footer">
                        <div>
                            <div class="price" style="<?php echo $is_cancelled ? 'color: #EF4444; text-decoration: line-through;' : ''; ?>">
                                XAF <?php echo number_format($booking['fare_paid'], 0); ?>
                                <small>/ seat</small>
                            </div>
                            <div class="booking-id">
                                <i class="fas fa-ticket-alt"></i>
                                Booking #<?php echo $booking['reservation_id']; ?>
                                <?php if (!empty($booking['booking_code'])): ?>
                                    | <span class="code"><?php echo htmlspecialchars($booking['booking_code']); ?></span>
                                <?php endif; ?>
                                <?php if ($is_cancelled): ?>
                                    <span style="color: #EF4444; margin-left: 8px;">(Cancelled)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="btn-actions">
                            <a href="payment.php?reservation_id=<?php echo $booking['reservation_id']; ?>" class="btn-cancel" style="background:rgba(56,189,248,0.06); color:#38BDF8; border-color:rgba(56,189,248,0.06);">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            <?php if ($is_cancelled): ?>
                                <button onclick="confirmDelete(<?php echo $booking['reservation_id']; ?>, '<?php echo addslashes($booking['original_city'] . ' → ' . $booking['destination']); ?>', '<?php echo $booking['seat_number']; ?>')" class="btn-delete">
                                    <i class="fas fa-trash"></i> Delete Ticket
                                </button>
                            <?php else: ?>
                                <button onclick="confirmCancel(<?php echo $booking['reservation_id']; ?>, '<?php echo addslashes($booking['original_city'] . ' → ' . $booking['destination']); ?>', '<?php echo $booking['seat_number']; ?>')" class="btn-cancel">
                                    <i class="fas fa-times"></i> Cancel Booking
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-ticket-alt"></i>
                <h3>No Bookings Yet</h3>
                <p>You haven't booked any tickets yet. Start your journey now!</p>
                <a href="schedule.php" class="btn-schedule">
                    <i class="fas fa-search"></i> Find buses
                </a>
            </div>
        <?php endif; ?>

    </div>
</section>
<?php include 'includes/footer.php'; ?>