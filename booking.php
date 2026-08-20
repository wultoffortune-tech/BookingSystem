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

// ========================================
// FUNCTION TO GENERATE 8-DIGIT BOOKING CODE
// ========================================
function generateBookingCode($pdo)
{
    $isUnique = false;
    $code = '';

    while (!$isUnique) {
        // Generate a random 8-digit number (between 10,000,000 and 99,999,999)
        $code = str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);

        // Check if code already exists in the database
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservation WHERE booking_code = ?");
        $stmt->execute([$code]);
        if ($stmt->fetchColumn() == 0) {
            $isUnique = true;
        }
    }
    return $code;
}

// Get schedule ID
$schedule_id = isset($_GET['schedule_id']) ? (int)$_GET['schedule_id'] : 0;

if ($schedule_id <= 0) {
    header('Location: schedule.php');
    exit();
}

// VERIFY SCHEDULE EXISTS FIRST - Check both possible table names
try {
    // First check if schedule exists in the 'schedule' table
    $stmt = $pdo->prepare("SELECT s.*, 
                           r.original_city, r.destination, r.base_fare,
                           b.bus_name, b.bus_type, b.total_seats
                           FROM schedule s
                           JOIN route r ON s.route_id = r.route_id
                           JOIN bus b ON s.bus_id = b.bus_id
                           WHERE s.schedule_id = ? AND s.expired = 0");
    $stmt->execute([$schedule_id]);
    $schedule = $stmt->fetch();

    // If not found, try 'schedules' table
    if (!$schedule) {
        $stmt = $pdo->prepare("SELECT s.*, 
                               r.original_city, r.destination, r.base_fare,
                               b.bus_name, b.bus_type, b.total_seats
                               FROM schedules s
                               JOIN route r ON s.route_id = r.route_id
                               JOIN bus b ON s.bus_id = b.bus_id
                               WHERE s.schedule_id = ? AND s.expired = 0");
        $stmt->execute([$schedule_id]);
        $schedule = $stmt->fetch();
    }
} catch (PDOException $e) {
    error_log("Schedule fetch error: " . $e->getMessage());
    header('Location: schedule.php');
    exit();
}

if (!$schedule) {
    $_SESSION['error_message'] = 'Schedule not found or has expired.';
    header('Location: schedule.php');
    exit();
}

// Get user info
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: login/login.php');
    exit();
}

$error = '';
$success = '';

// Calculate display price
$display_price = isset($schedule['price']) && $schedule['price'] > 0
    ? $schedule['price']
    : (isset($schedule['base_fare']) ? $schedule['base_fare'] : 0);

// Get reserved seats - Only count seats that are NOT cancelled
$stmt = $pdo->prepare("SELECT seat_number FROM reservation WHERE schedule_id = ? AND status != 'cancelled'");
$stmt->execute([$schedule_id]);
$reserved = array_column($stmt->fetchAll(), 'seat_number');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $seat_number = isset($_POST['seat_number']) ? trim($_POST['seat_number']) : '';

    if (empty($seat_number)) {
        $error = 'Please select a seat.';
    } elseif (in_array($seat_number, $reserved)) {
        $error = 'This seat is already taken. Please choose another seat.';
    } else {
        try {
            $pdo->beginTransaction();

            // Double check seat is still available
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reservation WHERE schedule_id = ? AND seat_number = ? AND status != 'cancelled'");
            $stmt->execute([$schedule_id, $seat_number]);
            $check = $stmt->fetch();

            if ($check['count'] > 0) {
                $error = 'This seat was just taken. Please choose another seat.';
                $pdo->rollBack();
            } else {
                // Verify schedule still exists and has available seats
                // Try both table names
                $schedule_check = null;

                // Try 'schedule' table first
                $stmt = $pdo->prepare("SELECT schedule_id, available_seats FROM schedule WHERE schedule_id = ? AND expired = 0 AND available_seats > 0 FOR UPDATE");
                $stmt->execute([$schedule_id]);
                $schedule_check = $stmt->fetch();

                // If not found, try 'schedules' table
                if (!$schedule_check) {
                    $stmt = $pdo->prepare("SELECT schedule_id, available_seats FROM schedules WHERE schedule_id = ? AND expired = 0 AND available_seats > 0 FOR UPDATE");
                    $stmt->execute([$schedule_id]);
                    $schedule_check = $stmt->fetch();
                }

                if (!$schedule_check) {
                    $error = 'Schedule is no longer available or has no seats left.';
                    $pdo->rollBack();
                } else {
                    // Generate booking code
                    $booking_code = generateBookingCode($pdo);

                    // Check what columns exist in reservation table
                    $columns = [];
                    try {
                        $stmt = $pdo->query("SHOW COLUMNS FROM reservation");
                        while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $columns[] = $col['Field'];
                        }
                    } catch (PDOException $e) {
                        error_log("Column check error: " . $e->getMessage());
                    }

                    // Build INSERT query based on existing columns
                    $insertFields = ['passenger_id', 'schedule_id', 'seat_number', 'fare_paid', 'status'];
                    $insertValues = [$user_id, $schedule_id, $seat_number, $display_price, 'pending'];
                    $placeholders = ['?', '?', '?', '?', '?'];

                    // Check if booking_code column exists
                    if (in_array('booking_code', $columns)) {
                        $insertFields[] = 'booking_code';
                        $insertValues[] = $booking_code;
                        $placeholders[] = '?';
                    }

                    // Check if seats_released column exists
                    if (in_array('seats_released', $columns)) {
                        $insertFields[] = 'seats_released';
                        $insertValues[] = 0;
                        $placeholders[] = '?';
                    }

                    // Check if created_at column exists
                    if (in_array('created_at', $columns)) {
                        $insertFields[] = 'created_at';
                        $insertValues[] = date('Y-m-d H:i:s');
                        $placeholders[] = '?';
                    }

                    // Build the final query
                    $sql = "INSERT INTO reservation (" . implode(', ', $insertFields) . ") 
                            VALUES (" . implode(', ', $placeholders) . ")";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($insertValues);

                    $reservation_id = $pdo->lastInsertId();

                    // Update available seats - try both tables
                    $stmt = $pdo->prepare("UPDATE schedule SET available_seats = available_seats - 1 
                                           WHERE schedule_id = ? AND available_seats > 0");
                    $stmt->execute([$schedule_id]);

                    // If no rows affected, try 'schedules' table
                    if ($stmt->rowCount() == 0) {
                        $stmt = $pdo->prepare("UPDATE schedules SET available_seats = available_seats - 1 
                                               WHERE schedule_id = ? AND available_seats > 0");
                        $stmt->execute([$schedule_id]);
                    }

                    $pdo->commit();

                    // Redirect to payment
                    header('Location: payment.php?reservation_id=' . $reservation_id);
                    exit();
                }
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Booking error: " . $e->getMessage());

            // Check for specific foreign key errors
            if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
                $error = 'Schedule reference error. The selected bus schedule may have been removed or the schedule_id is invalid.';
            } else {
                $error = 'Unable to complete the reservation. Please try again. Error: ' . $e->getMessage();
            }
        }
    }
}

include 'includes/header.php';
?>

<!-- ===== BOOKING STYLES ===== -->
<style>
    .booking-section {
        padding: 140px 0 90px;
        min-height: 70vh;
        background: #0F172A;
    }

    .booking-section .section-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .booking-section .section-header .subtitle {
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

    .booking-section .section-header .section-title {
        font-size: 36px;
        font-weight: 700;
        color: #FFFFFF;
        margin-bottom: 8px;
    }

    .booking-section .section-header .section-description {
        color: #94A3B8;
        font-size: 16px;
        max-width: 560px;
        margin: 0 auto;
    }

    .booking-grid {
        display: grid;
        gap: 24px;
        grid-template-columns: 2fr 1.2fr;
        max-width: 1100px;
        margin: 0 auto;
    }

    .booking-card {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 20px;
        padding: 30px;
    }

    .booking-card h3 {
        color: #FFFFFF;
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 18px;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
    }

    .booking-card h3 i {
        color: #38BDF8;
        margin-right: 8px;
    }

    .booking-card .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        color: #CBD5E1;
        border-bottom: 1px solid rgba(255, 255, 255, 0.02);
    }

    .booking-card .detail-row .label {
        color: #94A3B8;
    }

    .booking-card .detail-row .value {
        color: #FFFFFF;
        font-weight: 500;
    }

    .booking-card .detail-row .price-value {
        color: #38BDF8;
        font-weight: 700;
        font-size: 18px;
    }

    .form-group {
        margin-bottom: 16px;
    }

    .form-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #94A3B8;
        margin-bottom: 6px;
    }

    .form-group input {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 12px;
        font-size: 15px;
        font-family: 'Poppins', sans-serif;
        background: rgba(255, 255, 255, 0.02);
        color: #FFFFFF;
        transition: all 0.3s ease;
    }

    .form-group input:focus {
        outline: none;
        border-color: #38BDF8;
        box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.04);
    }

    .form-group input:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    /* ===== SEAT GRID ===== */
    .seat-grid {
        display: grid;
        grid-template-columns: repeat(8, 1fr);
        gap: 8px;
        margin: 12px 0 16px;
        padding: 10px;
        background: rgba(255, 255, 255, 0.02);
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.04);
    }

    .seat-grid .seat {
        padding: 10px 4px;
        border: 2px solid rgba(255, 255, 255, 0.06);
        border-radius: 8px;
        cursor: pointer;
        background: rgba(255, 255, 255, 0.02);
        color: #CBD5E1;
        transition: all 0.3s ease;
        text-align: center;
        font-size: 12px;
        font-weight: 500;
        position: relative;
        user-select: none;
    }

    .seat-grid .seat:hover:not(.taken) {
        border-color: #38BDF8;
        background: rgba(56, 189, 248, 0.04);
        transform: scale(1.05);
    }

    .seat-grid .seat input[type="radio"] {
        display: none;
    }

    .seat-grid .seat.selected {
        border-color: #38BDF8;
        background: rgba(56, 189, 248, 0.08);
        color: #38BDF8;
        font-weight: 600;
        box-shadow: 0 0 20px rgba(56, 189, 248, 0.1);
    }

    .seat-grid .seat.taken {
        border-color: rgba(239, 68, 68, 0.2);
        background: rgba(239, 68, 68, 0.04);
        color: #64748B;
        cursor: not-allowed;
        opacity: 0.4;
    }

    .seat-grid .seat.taken::after {
        content: '✕';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 16px;
        color: #EF4444;
        opacity: 0.5;
    }

    .seat-grid .seat .seat-label {
        display: block;
    }

    .seat-grid .seat .seat-status {
        display: block;
        font-size: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 2px;
    }

    .seat-grid .seat.selected .seat-status {
        color: #38BDF8;
    }

    .seat-grid .seat.taken .seat-status {
        color: #64748B;
    }

    .seat-legend {
        display: flex;
        gap: 16px;
        margin: 8px 0 16px;
        flex-wrap: wrap;
    }

    .seat-legend .legend-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #94A3B8;
    }

    .seat-legend .legend-item .color-box {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        border: 1px solid rgba(255, 255, 255, 0.06);
    }

    .seat-legend .legend-item .color-box.available {
        background: rgba(255, 255, 255, 0.02);
    }

    .seat-legend .legend-item .color-box.selected {
        background: rgba(56, 189, 248, 0.08);
        border-color: #38BDF8;
    }

    .seat-legend .legend-item .color-box.taken {
        background: rgba(239, 68, 68, 0.04);
        border-color: rgba(239, 68, 68, 0.2);
    }

    .btn-confirm {
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

    .btn-confirm:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(56, 189, 248, 0.15);
    }

    .btn-confirm:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .error-box {
        background: rgba(239, 68, 68, 0.06);
        border: 1px solid rgba(239, 68, 68, 0.06);
        border-radius: 12px;
        padding: 14px 18px;
        color: #FCA5A5;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 10px;
        max-width: 1100px;
        margin-left: auto;
        margin-right: auto;
    }

    .error-box i {
        font-size: 18px;
    }

    @media (max-width: 992px) {
        .booking-grid {
            grid-template-columns: 1fr;
            max-width: 600px;
        }

        .seat-grid {
            grid-template-columns: repeat(6, 1fr);
        }
    }

    @media (max-width: 768px) {
        .booking-section {
            padding: 110px 0 60px;
        }

        .booking-card {
            padding: 20px;
        }

        .seat-grid {
            grid-template-columns: repeat(4, 1fr);
        }

        .booking-grid {
            max-width: 100%;
        }
    }

    @media (max-width: 480px) {
        .seat-grid {
            grid-template-columns: repeat(3, 1fr);
        }

        .seat-grid .seat {
            padding: 8px 4px;
            font-size: 11px;
        }
    }
</style>

<!-- ===== SEAT SELECTION JAVASCRIPT ===== -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const seats = document.querySelectorAll('.seat');

        seats.forEach(function(seat) {
            seat.addEventListener('click', function(e) {
                if (this.classList.contains('taken')) {
                    return;
                }

                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    seats.forEach(function(s) {
                        const r = s.querySelector('input[type="radio"]');
                        if (r) {
                            r.checked = false;
                        }
                        s.classList.remove('selected');
                    });

                    radio.checked = true;
                    this.classList.add('selected');

                    const confirmBtn = document.getElementById('confirmBtn');
                    if (confirmBtn) {
                        confirmBtn.disabled = false;
                    }
                }
            });
        });

        console.log('✅ Seat selection ready');
    });
</script>

<!-- ===== BOOKING SECTION ===== -->
<section class="booking-section">
    <div class="container">

        <div class="section-header">
            <span class="subtitle">Confirm Your Booking</span>
            <h2 class="section-title">Reserve Your Seat</h2>
            <p class="section-description">Review the trip details and complete the reservation.</p>
        </div>

        <?php if ($error): ?>
            <div class="error-box">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="booking-grid">

            <!-- Left Column: Trip Details -->
            <div class="booking-card">
                <h3><i class="fas fa-route"></i> Trip Details</h3>

                <div class="detail-row">
                    <span class="label">Route</span>
                    <span class="value">
                        <?php echo htmlspecialchars($schedule['original_city']); ?>
                        <i class="fas fa-arrow-right" style="color:#475569; font-size:12px; margin:0 4px;"></i>
                        <?php echo htmlspecialchars($schedule['destination']); ?>
                    </span>
                </div>

                <div class="detail-row">
                    <span class="label">Bus</span>
                    <span class="value"><?php echo htmlspecialchars($schedule['bus_name']); ?> (<?php echo htmlspecialchars($schedule['bus_type']); ?>)</span>
                </div>

                <div class="detail-row">
                    <span class="label">Departure</span>
                    <span class="value"><?php echo date('d M Y H:i', strtotime($schedule['departure_time'])); ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Arrival</span>
                    <span class="value"><?php echo date('d M Y H:i', strtotime($schedule['arrival_time'])); ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Available Seats</span>
                    <span class="value"><?php echo $schedule['available_seats']; ?> / <?php echo $schedule['total_seats']; ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Fare</span>
                    <span class="value price-value">XAF <?php echo number_format($display_price, 0); ?></span>
                </div>
            </div>

            <!-- Right Column: Seat Selection -->
            <div class="booking-card">
                <h3><i class="fas fa-chair"></i> Select Your Seat</h3>

                <form method="POST" action="booking.php?schedule_id=<?php echo $schedule_id; ?>" id="bookingForm">

                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Passenger</label>
                        <input type="text" value="<?php echo isset($user['full_name']) ? htmlspecialchars($user['full_name']) : ''; ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="text" value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-chair"></i> Choose Your Seat</label>

                        <div class="seat-legend">
                            <span class="legend-item">
                                <span class="color-box available"></span>
                                Available
                            </span>
                            <span class="legend-item">
                                <span class="color-box selected"></span>
                                Selected
                            </span>
                            <span class="legend-item">
                                <span class="color-box taken"></span>
                                Taken
                            </span>
                        </div>

                        <div class="seat-grid">
                            <?php
                            $totalSeats = isset($schedule['total_seats']) ? (int)$schedule['total_seats'] : 40;

                            for ($i = 1; $i <= $totalSeats; $i++) {
                                $seat = 'S' . $i;
                                $is_reserved = in_array($seat, $reserved);
                            ?>
                                <label class="seat <?php echo $is_reserved ? 'taken' : ''; ?>">
                                    <input type="radio" name="seat_number" value="<?php echo $seat; ?>"
                                        <?php echo $is_reserved ? 'disabled' : ''; ?>>
                                    <span class="seat-label"><?php echo $seat; ?></span>
                                    <span class="seat-status"><?php echo $is_reserved ? 'Taken' : 'Available'; ?></span>
                                </label>
                            <?php
                            }
                            ?>
                        </div>
                    </div>

                    <button type="submit" class="btn-confirm" id="confirmBtn" disabled>
                        <i class="fas fa-check-circle"></i> Confirm Reservation
                    </button>
                </form>
            </div>

        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>