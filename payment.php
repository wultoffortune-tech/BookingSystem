<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login/login.php');
    exit();
}

$reservation_id = isset($_GET['reservation_id']) ? (int)$_GET['reservation_id'] : (isset($_POST['reservation_id']) ? (int)$_POST['reservation_id'] : 0);
if ($reservation_id <= 0) {
    header('Location: bookings.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$cancel_message = '';

// ========================================
// HANDLE CANCELLATION
// ========================================
if (isset($_GET['cancel']) && $_GET['cancel'] == 'true') {
    try {
        $pdo->beginTransaction();

        // Get schedule_id from reservation
        $stmt = $pdo->prepare("SELECT schedule_id FROM reservation WHERE reservation_id = ? AND passenger_id = ?");
        $stmt->execute([$reservation_id, $user_id]);
        $res = $stmt->fetch();

        if ($res) {
            // Update reservation status to cancelled
            $stmt = $pdo->prepare("UPDATE reservation SET status = 'cancelled' WHERE reservation_id = ? AND passenger_id = ?");
            $stmt->execute([$reservation_id, $user_id]);

            // Increase available seats back
            $stmt = $pdo->prepare("UPDATE schedule SET available_seats = available_seats + 1 WHERE schedule_id = ?");
            $stmt->execute([$res['schedule_id']]);

            $pdo->commit();
            $cancel_message = '✅ Your booking has been cancelled successfully.';
            $cancel_type = 'success';
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $cancel_message = '❌ Unable to cancel the booking. Please try again.';
        $cancel_type = 'error';
    }
}

// Select reservation fields
$reservation = $pdo->prepare("SELECT r.*, s.departure_time, s.arrival_time, rt.original_city, rt.destination, b.bus_name, b.bus_type FROM reservation r JOIN schedule s ON r.schedule_id = s.schedule_id JOIN route rt ON s.route_id = rt.route_id JOIN bus b ON s.bus_id = b.bus_id WHERE r.reservation_id = ? AND r.passenger_id = ?");
$reservation->execute([$reservation_id, $user_id]);
$reservation = $reservation->fetch();

if (!$reservation) {
    header('Location: bookings.php');
    exit();
}

// Fallback status if the field is missing
$reservation['status'] = isset($reservation['status']) ? $reservation['status'] : 'pending';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_now'])) {
    if ($reservation['status'] === 'confirmed') {
        $success = 'This reservation is already confirmed.';
    } else {
        try {
            $transaction_ref = strtoupper(uniqid('SIM'));
            $paymentTable = $pdo->query("SHOW TABLES LIKE 'payment'")->fetch();
            $statusColumn = $pdo->query("SHOW COLUMNS FROM `reservation` LIKE 'status'")->fetch();

            if ($paymentTable) {
                try {
                    $insert = $pdo->prepare("INSERT INTO payment (reservation_id, payment_method, amount, payment_status, transaction_reference, payment_date) VALUES (?, ?, ?, 'completed', ?, NOW())");
                    $insert->execute([$reservation_id, 'simulated', $reservation['fare_paid'], $transaction_ref]);
                } catch (PDOException $inner) {
                    error_log('[payment.php] simulated payment insert failed: ' . $inner->getMessage());
                }
            }

            if ($statusColumn) {
                $update = $pdo->prepare("UPDATE reservation SET status = 'confirmed' WHERE reservation_id = ?");
                $update->execute([$reservation_id]);
            }

            $reservation['status'] = 'confirmed';
            $success = 'Payment completed successfully. Your booking is now confirmed.';
        } catch (PDOException $e) {
            $error = 'We could not complete the payment. Please try again.';
        }
    }
}

include 'includes/header.php';
?>

<!-- ===== PAYMENT STYLES ===== -->
<style>
    .payment-section {
        padding: 140px 0 90px;
        min-height: 70vh;
        background: #0F172A;
    }

    .payment-section .section-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .payment-section .section-header .subtitle {
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

    .payment-section .section-header .section-title {
        font-size: 36px;
        font-weight: 700;
        color: #FFFFFF;
        margin-bottom: 8px;
    }

    .payment-section .section-header .section-description {
        color: #94A3B8;
        font-size: 16px;
        max-width: 560px;
        margin: 0 auto;
    }

    .payment-grid {
        display: grid;
        gap: 24px;
        grid-template-columns: 1fr 1fr;
        max-width: 1000px;
        margin: 0 auto;
    }

    .payment-card {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 20px;
        padding: 30px;
    }

    .payment-card h3 {
        color: #FFFFFF;
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 18px;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
    }

    .payment-card h3 i {
        color: #38BDF8;
        margin-right: 8px;
    }

    .payment-card .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        color: #CBD5E1;
        border-bottom: 1px solid rgba(255, 255, 255, 0.02);
    }

    .payment-card .detail-row .label {
        color: #94A3B8;
    }

    .payment-card .detail-row .value {
        color: #FFFFFF;
        font-weight: 500;
    }

    .payment-card .detail-row .price-value {
        color: #38BDF8;
        font-weight: 700;
        font-size: 18px;
    }

    .btn-pay {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, #38BDF8, #0EA5E9);
        color: #0F172A;
        border: none;
        border-radius: 14px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s ease;
        margin-top: 8px;
    }

    .btn-pay:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(56, 189, 248, 0.15);
    }

    /* ===== CANCEL BUTTON ===== */
    .btn-cancel {
        width: 100%;
        padding: 14px;
        background: rgba(239, 68, 68, 0.06);
        color: #EF4444;
        border: 1px solid rgba(239, 68, 68, 0.06);
        border-radius: 14px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s ease;
        margin-top: 12px;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }

    .btn-cancel:hover {
        background: #EF4444;
        color: #FFFFFF;
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(239, 68, 68, 0.15);
    }

    .btn-cancel.disabled {
        opacity: 0.4;
        cursor: not-allowed;
        pointer-events: none;
    }

    .btn-bookings {
        display: inline-block;
        padding: 12px 24px;
        background: rgba(255, 255, 255, 0.04);
        color: #94A3B8;
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 12px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
        margin-top: 16px;
    }

    .btn-bookings:hover {
        background: rgba(255, 255, 255, 0.06);
        color: #FFFFFF;
    }

    .error-box {
        background: rgba(239, 68, 68, 0.06);
        border: 1px solid rgba(239, 68, 68, 0.06);
        border-radius: 12px;
        padding: 14px 18px;
        color: #FCA5A5;
        margin-bottom: 24px;
    }

    .success-box {
        background: rgba(52, 211, 153, 0.06);
        border: 1px solid rgba(52, 211, 153, 0.06);
        border-radius: 12px;
        padding: 14px 18px;
        color: #34D399;
        margin-bottom: 24px;
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

    .cancel-link {
        margin-top: 12px;
        display: block;
        text-align: center;
    }

    @media (max-width: 768px) {
        .payment-grid {
            grid-template-columns: 1fr;
        }

        .payment-section {
            padding: 110px 0 60px;
        }

        .payment-card {
            padding: 20px;
        }
    }
</style>

<!-- ===== PAYMENT SECTION ===== -->
<section class="payment-section">
    <div class="container">

        <div class="section-header">
            <span class="subtitle">Secure Payment</span>
            <h2 class="section-title">Complete Your Booking</h2>
            <p class="section-description">Confirm your payment details to complete the reservation and receive your ticket.</p>
        </div>

        <?php if ($error): ?>
            <div class="error-box">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-box">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($cancel_message): ?>
            <<!-- ✅ CANCEL BUTTON IN PAYMENT PAGE -->
                <div class="cancel-section" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.04);">
                    <a href="bookings.php?cancel=<?php echo $reservation_id; ?>"
                        class="btn-cancel"
                        style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: rgba(239,68,68,0.06); color: #EF4444; border: 1px solid rgba(239,68,68,0.06); border-radius: 12px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;"
                        onclick="return confirm('Are you sure you want to cancel this booking?\n\nThis will delete your reservation and release the seat.');">
                        <i class="fas fa-times"></i> Cancel Booking
                    </a>
                </div>
            <?php endif; ?>

            <div class="payment-grid">

                <!-- Left Column: Booking Summary -->
                <div class="payment-card">
                    <h3><i class="fas fa-ticket-alt"></i> Booking Summary</h3>

                    <div class="detail-row">
                        <span class="label">Route</span>
                        <span class="value">
                            <?php echo htmlspecialchars($reservation['original_city']); ?>
                            <i class="fas fa-arrow-right" style="color:#475569; font-size:12px; margin:0 4px;"></i>
                            <?php echo htmlspecialchars($reservation['destination']); ?>
                        </span>
                    </div>

                    <div class="detail-row">
                        <span class="label">Bus</span>
                        <span class="value"><?php echo htmlspecialchars($reservation['bus_name']); ?> (<?php echo htmlspecialchars($reservation['bus_type']); ?>)</span>
                    </div>

                    <div class="detail-row">
                        <span class="label">Departure</span>
                        <span class="value"><?php echo date('d M Y H:i', strtotime($reservation['departure_time'])); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="label">Arrival</span>
                        <span class="value"><?php echo date('d M Y H:i', strtotime($reservation['arrival_time'])); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="label">Seat Number</span>
                        <span class="value"><?php echo htmlspecialchars($reservation['seat_number']); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="label">Status</span>
                        <span class="value" style="color: <?php echo $reservation['status'] == 'confirmed' ? '#34D399' : ($reservation['status'] == 'cancelled' ? '#EF4444' : '#F59E0B'); ?>;">
                            <?php echo ucfirst(htmlspecialchars($reservation['status'])); ?>
                        </span>
                    </div>

                    <div class="detail-row" style="border-bottom: none; padding-top: 16px;">
                        <span class="label" style="font-weight: 700; color: #FFFFFF;">Total Amount</span>
                        <span class="value price-value">XAF <?php echo number_format($reservation['fare_paid'], 0); ?></span>
                    </div>
                </div>

                <!-- Right Column: Payment & Actions -->
                <div class="payment-card">
                    <h3><i class="fas fa-credit-card"></i> Payment Method</h3>

                    <?php if ($reservation['status'] !== 'confirmed' && $reservation['status'] !== 'cancelled'): ?>
                        <form method="POST" action="payment.php?reservation_id=<?php echo $reservation_id; ?>">
                            <input type="hidden" name="reservation_id" value="<?php echo $reservation_id; ?>">

                            <div style="margin-bottom:20px; color:#94A3B8; font-size:14px;">
                                <p>Click pay to confirm and complete the reservation.</p>
                            </div>

                            <button type="submit" name="pay_now" class="btn-pay">
                                <i class="fas fa-check-circle"></i> Pay XAF <?php echo number_format($reservation['fare_paid'], 0); ?>
                            </button>
                        </form>

                        <!-- ✅ CANCEL BUTTON ADDED HERE -->
                        <div class="cancel-link">
                            <a href="payment.php?reservation_id=<?php echo $reservation_id; ?>&cancel=true"
                                class="btn-cancel"
                                onclick="return confirm('Are you sure you want to cancel this booking?\n\nRoute: <?php echo addslashes($reservation['original_city'] . ' → ' . $reservation['destination']); ?>\nSeat: <?php echo $reservation['seat_number']; ?>\n\nThis action cannot be undone.');">
                                <i class="fas fa-times"></i> Cancel Booking
                            </a>
                        </div>

                    <?php elseif ($reservation['status'] === 'confirmed'): ?>
                        <div style="margin-bottom:20px; color:#34D399; font-size:14px;">
                            <p><i class="fas fa-check-circle"></i> Your payment is already confirmed. No further action is needed.</p>
                        </div>

                        <!-- ✅ CANCEL BUTTON FOR CONFIRMED BOOKINGS -->
                        <div class="cancel-link">
                            <a href="payment.php?reservation_id=<?php echo $reservation_id; ?>&cancel=true"
                                class="btn-cancel"
                                onclick="return confirm('Are you sure you want to cancel this confirmed booking?\n\nRoute: <?php echo addslashes($reservation['original_city'] . ' → ' . $reservation['destination']); ?>\nSeat: <?php echo $reservation['seat_number']; ?>\n\nThis action cannot be undone.');">
                                <i class="fas fa-times"></i> Cancel Booking
                            </a>
                        </div>

                    <?php elseif ($reservation['status'] === 'cancelled'): ?>
                        <div style="margin-bottom:20px; color:#EF4444; font-size:14px;">
                            <p><i class="fas fa-ban"></i> This booking has been cancelled.</p>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top:20px; color:#64748B; font-size:13px; padding-top:16px; border-top:1px solid rgba(255,255,255,0.04);">
                        
                    </div>

                    <a href="bookings.php" class="btn-bookings">
                        <i class="fas fa-arrow-left"></i> View My Bookings
                    </a>
                </div>

            </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>