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
$cancel_type = '';

// ========================================
// HANDLE CANCELLATION - FIXED
// ========================================
if (isset($_GET['cancel']) && $_GET['cancel'] == 'true') {
    try {
        $pdo->beginTransaction();

        // Get schedule_id and status from reservation
        $stmt = $pdo->prepare("SELECT schedule_id, status FROM reservation WHERE reservation_id = ? AND passenger_id = ?");
        $stmt->execute([$reservation_id, $user_id]);
        $res = $stmt->fetch();

        if ($res) {
            if ($res['status'] == 'cancelled') {
                $cancel_message = '⚠️ This booking is already cancelled.';
                $cancel_type = 'error';
            } elseif ($res['status'] == 'used') {
                $cancel_message = '❌ This ticket has already been used and cannot be cancelled.';
                $cancel_type = 'error';
            } else {
                // Update reservation status to cancelled
                $stmt = $pdo->prepare("UPDATE reservation SET status = 'cancelled', seats_released = 1 WHERE reservation_id = ? AND passenger_id = ?");
                $stmt->execute([$reservation_id, $user_id]);

                // Increase available seats back
                $stmt = $pdo->prepare("UPDATE schedule SET available_seats = available_seats + 1 WHERE schedule_id = ?");
                $stmt->execute([$res['schedule_id']]);

                $pdo->commit();
                $cancel_message = '✅ Your booking has been cancelled successfully.';
                $cancel_type = 'success';

                // Redirect to bookings page after cancellation
                header('Location: bookings.php?cancelled=true');
                exit();
            }
        } else {
            $cancel_message = '❌ Booking not found.';
            $cancel_type = 'error';
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

// ========================================
// HANDLE PAYMENT - FIXED WITH PAYMENT TABLE
// ========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_now'])) {
    if ($reservation['status'] === 'confirmed') {
        $success = 'This reservation is already confirmed.';
    } elseif ($reservation['status'] === 'cancelled') {
        $error = 'This booking has been cancelled and cannot be paid for.';
    } else {
        try {
            // Generate transaction reference
            $transaction_ref = 'PAY-' . strtoupper(uniqid()) . '-' . date('YmdHis');

            // Check if payment table exists
            $paymentTable = $pdo->query("SHOW TABLES LIKE 'payment'")->fetch();

            if ($paymentTable) {
                // Check columns in payment table
                $columns = [];
                $colQuery = $pdo->query("SHOW COLUMNS FROM payment");
                while ($col = $colQuery->fetch(PDO::FETCH_ASSOC)) {
                    $columns[] = $col['Field'];
                }

                // Build payment insert based on available columns
                $paymentFields = ['reservation_id', 'payment_method', 'amount', 'payment_status', 'transaction_reference', 'payment_date'];
                $paymentValues = [$reservation_id, 'cash', $reservation['fare_paid'], 'completed', $transaction_ref, date('Y-m-d H:i:s')];
                $paymentPlaceholders = ['?', '?', '?', '?', '?', '?'];

                // Add user_id if column exists
                if (in_array('user_id', $columns)) {
                    $paymentFields[] = 'user_id';
                    $paymentValues[] = $user_id;
                    $paymentPlaceholders[] = '?';
                }

                $paymentSql = "INSERT INTO payment (" . implode(', ', $paymentFields) . ") 
                              VALUES (" . implode(', ', $paymentPlaceholders) . ")";

                $insert = $pdo->prepare($paymentSql);
                $insert->execute($paymentValues);
            }

            // Update reservation status to confirmed
            $update = $pdo->prepare("UPDATE reservation SET status = 'confirmed' WHERE reservation_id = ?");
            $update->execute([$reservation_id]);

            $reservation['status'] = 'confirmed';
            $success = '✅ Payment completed successfully! Your booking is now confirmed.';

            // Refresh reservation data
            $reservation = $pdo->prepare("SELECT r.*, s.departure_time, s.arrival_time, rt.original_city, rt.destination, b.bus_name, b.bus_type FROM reservation r JOIN schedule s ON r.schedule_id = s.schedule_id JOIN route rt ON s.route_id = rt.route_id JOIN bus b ON s.bus_id = b.bus_id WHERE r.reservation_id = ? AND r.passenger_id = ?");
            $reservation->execute([$reservation_id, $user_id]);
            $reservation = $reservation->fetch();
        } catch (PDOException $e) {
            error_log('[payment.php] payment failed: ' . $e->getMessage());
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

    /* ===== BOOKING CODE DISPLAY ===== */
    .booking-code-row {
        background: rgba(56, 189, 248, 0.04);
        border: 1px solid rgba(56, 189, 248, 0.06);
        border-radius: 10px;
        padding: 12px 16px;
        margin-top: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .booking-code-row .code-label {
        color: #94A3B8;
        font-size: 13px;
    }

    .booking-code-row .code-label i {
        color: #38BDF8;
        margin-right: 6px;
    }

    .payment-status.used {
        background: rgba(56, 189, 248, 0.1);
        color: #38BDF8;
    }

    .booking-code-row .code-value {
        font-size: 20px;
        font-weight: 700;
        color: #38BDF8;
        letter-spacing: 2px;
        font-family: monospace;
    }

    .booking-code-row .code-value i {
        color: #34D399;
        font-size: 16px;
        margin-right: 6px;
    }

    .payment-status {
        display: inline-block;
        padding: 4px 16px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
    }

    .payment-status.confirmed {
        background: rgba(52, 211, 153, 0.1);
        color: #34D399;
    }

    .payment-status.pending {
        background: rgba(245, 158, 11, 0.1);
        color: #F59E0B;
    }

    .payment-status.cancelled {
        background: rgba(239, 68, 68, 0.1);
        color: #EF4444;
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

    .btn-pay:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }

    /* ===== CANCEL BUTTON ===== */
    .btn-cancel-booking {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        background: rgba(239, 68, 68, 0.06);
        color: #EF4444;
        border: 1px solid rgba(239, 68, 68, 0.06);
        border-radius: 12px;
        text-decoration: none;
        font-weight: 500;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s ease;
        cursor: pointer;
        margin-top: 12px;
        width: 100%;
        justify-content: center;
        font-size: 14px;
    }

    .btn-cancel-booking:hover {
        background: #EF4444;
        color: #FFFFFF;
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(239, 68, 68, 0.15);
        text-decoration: none;
    }

    .btn-cancel-booking i {
        font-size: 14px;
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
        width: 100%;
        text-align: center;
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
        max-width: 1000px;
        margin-left: auto;
        margin-right: auto;
    }

    .success-box {
        background: rgba(52, 211, 153, 0.06);
        border: 1px solid rgba(52, 211, 153, 0.06);
        border-radius: 12px;
        padding: 14px 18px;
        color: #34D399;
        margin-bottom: 24px;
        max-width: 1000px;
        margin-left: auto;
        margin-right: auto;
    }

    .message-box {
        padding: 14px 20px;
        border-radius: 12px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 10px;
        max-width: 1000px;
        margin-left: auto;
        margin-right: auto;
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

    .payment-info {
        background: rgba(56, 189, 248, 0.04);
        border: 1px solid rgba(56, 189, 248, 0.06);
        border-radius: 8px;
        padding: 12px 16px;
        margin: 12px 0;
    }

    .payment-info p {
        color: #94A3B8;
        font-size: 13px;
        margin: 4px 0;
    }

    .payment-info .ref {
        color: #38BDF8;
        font-weight: 600;
        font-family: monospace;
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

        .booking-code-row {
            flex-direction: column;
            text-align: center;
            gap: 4px;
        }

        .booking-code-row .code-value {
            font-size: 18px;
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
            <div class="message-box <?php echo $cancel_type; ?>">
                <i class="fas <?php echo $cancel_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($cancel_message); ?>
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
                    <span class="value">
                        <span class="payment-status <?php echo $reservation['status']; ?>">
                            <i class="fas <?php
                                            echo $reservation['status'] == 'confirmed' ? 'fa-check-circle'
                                                : ($reservation['status'] == 'cancelled' ? 'fa-times-circle'
                                                    : ($reservation['status'] == 'used' ? 'fa-check-double'
                                                        : 'fa-clock'));
                                            ?>"></i>
                            <?php echo ucfirst(htmlspecialchars($reservation['status'])); ?>
                        </span>
                    </span>
                </div>

                <!-- ===== BOOKING CODE DISPLAY ===== -->
                <?php if (isset($reservation['booking_code']) && !empty($reservation['booking_code'])): ?>
                    <div class="booking-code-row">
                        <span class="code-label">
                            <i class="fas fa-ticket-alt"></i> Booking Code
                        </span>
                        <span class="code-value">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($reservation['booking_code']); ?>
                        </span>
                    </div>
                <?php endif; ?>

                <div class="detail-row" style="border-bottom: none; padding-top: 16px;">
                    <span class="label" style="font-weight: 700; color: #FFFFFF;">Total Amount</span>
                    <span class="value price-value">XAF <?php echo number_format($reservation['fare_paid'], 0); ?></span>
                </div>
            </div>

            <!-- Right Column: Payment & Actions -->
            <div class="payment-card">
                <h3><i class="fas fa-credit-card"></i> Payment Method</h3>

                <?php if ($reservation['status'] !== 'confirmed' && $reservation['status'] !== 'cancelled' && $reservation['status'] !== 'used'): ?>
                    <!-- Payment Form for Pending Bookings -->
                    <form method="POST" action="payment.php?reservation_id=<?php echo $reservation_id; ?>">
                        <input type="hidden" name="reservation_id" value="<?php echo $reservation_id; ?>">

                        <div style="margin-bottom:20px; color:#94A3B8; font-size:14px;">
                            <p><i class="fas fa-info-circle" style="color:#38BDF8;"></i> Click pay to confirm and complete the reservation.</p>
                            <p style="margin-top:8px; font-size:13px; color:#64748B;">
                                <i class="fas fa-shield-alt" style="color:#38BDF8;"></i> Secure payment via cash at counter
                            </p>
                        </div>

                        <button type="submit" name="pay_now" class="btn-pay">
                            <i class="fas fa-check-circle"></i> Pay XAF <?php echo number_format($reservation['fare_paid'], 0); ?>
                        </button>
                    </form>

                    <!-- Cancel Button for Pending Bookings -->
                    <a href="payment.php?reservation_id=<?php echo $reservation_id; ?>&cancel=true"
                        class="btn-cancel-booking"
                        onclick="return confirm('Are you sure you want to cancel this booking?\n\nRoute: <?php echo addslashes($reservation['original_city'] . ' → ' . $reservation['destination']); ?>\nSeat: <?php echo $reservation['seat_number']; ?>\n\nThis action cannot be undone.');">
                        <i class="fas fa-times"></i> Cancel Booking
                    </a>

                <?php elseif ($reservation['status'] === 'confirmed'): ?>
                    <!-- Confirmed Booking -->
                    <div style="margin-bottom:20px; color:#34D399; font-size:14px;">
                        <p><i class="fas fa-check-circle"></i> Your payment is already confirmed. No further action is needed.</p>
                        <p style="margin-top:8px; color:#94A3B8; font-size:13px;">
                            <i class="fas fa-ticket-alt"></i> Your ticket is ready
                        </p>
                    </div>

                    <?php
                    // Check if payment record exists
                    $paymentCheck = $pdo->prepare("SELECT transaction_reference, payment_date, payment_method FROM payment WHERE reservation_id = ?");
                    $paymentCheck->execute([$reservation_id]);
                    $payment = $paymentCheck->fetch();
                    ?>

                    <?php if ($payment): ?>
                        <div class="payment-info">
                            <p><i class="fas fa-receipt"></i> Payment Details:</p>
                            <p>Method: <strong style="color:#FFFFFF;"><?php echo ucfirst($payment['payment_method']); ?></strong></p>
                            <p>Reference: <span class="ref"><?php echo htmlspecialchars($payment['transaction_reference']); ?></span></p>
                            <p>Date: <?php echo date('d M Y H:i', strtotime($payment['payment_date'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Cancel Button for Confirmed Bookings -->
                    <a href="payment.php?reservation_id=<?php echo $reservation_id; ?>&cancel=true"
                        class="btn-cancel-booking"
                        onclick="return confirm('Are you sure you want to cancel this confirmed booking?\n\nRoute: <?php echo addslashes($reservation['original_city'] . ' → ' . $reservation['destination']); ?>\nSeat: <?php echo $reservation['seat_number']; ?>\n\nThis action cannot be undone.');">
                        <i class="fas fa-times"></i> Cancel Booking
                    </a>
                <?php elseif ($reservation['status'] === 'used'): ?>
                    <!-- Used Ticket -->
                    <div style="margin-bottom:20px; color:#38BDF8; font-size:14px;">
                        <p><i class="fas fa-check-double"></i> This ticket has already been used and cannot be modified.</p>
                    </div>

                <?php elseif ($reservation['status'] === 'cancelled'): ?>
                    <!-- Cancelled Booking -->
                    <div style="margin-bottom:20px; color:#EF4444; font-size:14px;">
                        <p><i class="fas fa-ban"></i> This booking has been cancelled.</p>
                        <p style="margin-top:8px; color:#94A3B8; font-size:13px;">
                            The seat has been released and is available for booking.
                        </p>
                    </div>
                <?php endif; ?>

                <div style="margin-top:20px; color:#64748B; font-size:13px; padding-top:16px; border-top:1px solid rgba(255,255,255,0.04);">
                    <i class="fas fa-lock" style="color:#38BDF8;"></i> Secure transaction
                </div>

                <a href="bookings.php" class="btn-bookings">
                    <i class="fas fa-arrow-left"></i> View My Bookings
                </a>
            </div>

        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>