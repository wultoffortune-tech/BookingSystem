<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'staff' && $_SESSION['user_role'] != 'admin')) {
    header("Location: ../login/login.php");
    exit();
}

$message = "";
$error = "";
$selected_schedule_id = isset($_POST['schedule_id']) ? (int)$_POST['schedule_id'] : 0;
$reserved_seats = [];
$total_seats = 40;

// Fetch schedules for dropdown
$sql = "SELECT s.schedule_id, s.departure_time, s.price, s.available_seats,
        r.original_city, r.destination, b.total_seats
        FROM schedule s 
        JOIN route r ON s.route_id = r.route_id
        JOIN bus b ON s.bus_id = b.bus_id
        WHERE s.expired = 0 AND s.available_seats > 0 
        ORDER BY s.departure_time ASC";
$schedules = $pdo->query($sql)->fetchAll();

// If schedule is selected, get reserved seats
if ($selected_schedule_id > 0) {
    $stmt = $pdo->prepare("SELECT seat_number FROM reservation WHERE schedule_id = ? AND status != 'cancelled'");
    $stmt->execute([$selected_schedule_id]);
    $reserved_seats = array_column($stmt->fetchAll(), 'seat_number');

    // Get total seats for the selected schedule
    $stmt = $pdo->prepare("SELECT b.total_seats 
                           FROM schedule s 
                           JOIN bus b ON s.bus_id = b.bus_id 
                           WHERE s.schedule_id = ?");
    $stmt->execute([$selected_schedule_id]);
    $bus = $stmt->fetch();
    $total_seats = $bus ? (int)$bus['total_seats'] : 40;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_booking'])) {
    try {
        $pdo->beginTransaction();

        // Get form data
        $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
        $date_of_birth = isset($_POST['date_of_birth']) ? trim($_POST['date_of_birth']) : '2000-01-01';
        $id_number = isset($_POST['id_number']) ? trim($_POST['id_number']) : 'WALKIN-' . rand(1000, 9999);
        $schedule_id = isset($_POST['schedule_id']) ? (int)$_POST['schedule_id'] : 0;
        $seat_number = isset($_POST['seat_number']) ? trim($_POST['seat_number']) : '';
        $fare_paid = isset($_POST['fare_paid']) ? (float)$_POST['fare_paid'] : 0;

        // Validate
        if (empty($full_name) || empty($email) || empty($phone_number)) {
            throw new Exception("Please fill in all required fields.");
        }

        if ($schedule_id <= 0) {
            throw new Exception("Please select a schedule.");
        }

        if (empty($seat_number)) {
            throw new Exception("Please select a seat.");
        }

        if ($fare_paid <= 0) {
            throw new Exception("Please enter a valid fare amount.");
        }

        // Check if user already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existing_user = $stmt->fetch();

        if ($existing_user) {
            $passenger_id = $existing_user['user_id'];
        } else {
            // Create new user
            $password_hash = password_hash('guest123', PASSWORD_DEFAULT);
            $role = 'user';

            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone_number, date_of_birth, id_number, password, role) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $phone_number, $date_of_birth, $id_number, $password_hash, $role]);
            $passenger_id = $pdo->lastInsertId();
        }

        // Check if seat is already taken
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reservation WHERE schedule_id = ? AND seat_number = ? AND status != 'cancelled'");
        $stmt->execute([$schedule_id, $seat_number]);
        $check = $stmt->fetch();

        if ($check['count'] > 0) {
            throw new Exception("This seat was just taken. Please choose another seat.");
        }

        // ========================================
        // 1. GENERATE BOOKING CODE
        // ========================================
        $booking_code = str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);

        // ========================================
        // 2. INSERT RESERVATION
        // ========================================
        $stmt = $pdo->prepare("INSERT INTO reservation 
                              (booking_code, passenger_id, schedule_id, seat_number, fare_paid, status, seats_released, assisted_by_staff, assistance_notes) 
                              VALUES (?, ?, ?, ?, ?, 'confirmed', 0, 1, 'Paid in cash at counter by staff')");
        $stmt->execute([$booking_code, $passenger_id, $schedule_id, $seat_number, $fare_paid]);

        $reservation_id = $pdo->lastInsertId();

        // ========================================
        // 3. INSERT PAYMENT RECORD - FIXED
        // ========================================
        $payment_method = 'cash';
        $payment_status = 'completed';
        $transaction_reference = 'CASH-' . strtoupper(uniqid()) . '-' . date('YmdHis');

        // Check if payment table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'payment'")->fetch();
        if ($tableCheck) {
            // Check columns in payment table
            $columns = [];
            $colQuery = $pdo->query("SHOW COLUMNS FROM payment");
            while ($col = $colQuery->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $col['Field'];
            }

            // Build payment insert based on available columns
            $paymentFields = ['reservation_id', 'payment_method', 'amount', 'payment_status', 'transaction_reference', 'payment_date'];
            $paymentValues = [$reservation_id, $payment_method, $fare_paid, $payment_status, $transaction_reference, date('Y-m-d H:i:s')];
            $paymentPlaceholders = ['?', '?', '?', '?', '?', '?'];

            // Add user_id if column exists
            if (in_array('user_id', $columns)) {
                $paymentFields[] = 'user_id';
                $paymentValues[] = $passenger_id;
                $paymentPlaceholders[] = '?';
            }

            $paymentSql = "INSERT INTO payment (" . implode(', ', $paymentFields) . ") 
                          VALUES (" . implode(', ', $paymentPlaceholders) . ")";

            $stmt = $pdo->prepare($paymentSql);
            $stmt->execute($paymentValues);
        }

        // ========================================
        // 4. UPDATE AVAILABLE SEATS
        // ========================================
        $stmt = $pdo->prepare("UPDATE schedule SET available_seats = available_seats - 1 
                               WHERE schedule_id = ? AND available_seats > 0");
        $stmt->execute([$schedule_id]);

        $pdo->commit();

        // ========================================
        // 5. SUCCESS MESSAGE
        // ========================================
        $message = "<div style='background:rgba(16,185,129,0.06); color:#10B981; padding:20px; border-radius:10px; border:1px solid rgba(16,185,129,0.1); margin-bottom:20px;'>
            <strong>✅ Booking Confirmed & Payment Recorded!</strong><br><br>
            <div style='display:grid; grid-template-columns:1fr 1fr; gap:8px;'>
                <div><strong>Passenger:</strong> " . htmlspecialchars($full_name) . "</div>
                <div><strong>Email:</strong> " . htmlspecialchars($email) . "</div>
                <div><strong>Phone:</strong> " . htmlspecialchars($phone_number) . "</div>
                <div><strong>Booking Code:</strong> <span style='font-size:20px; font-weight:700; color:#3B82F6;'>$booking_code</span></div>
                <div><strong>Seat:</strong> " . htmlspecialchars($seat_number) . "</div>
                <div><strong>Fare Paid:</strong> XAF " . number_format($fare_paid, 0) . " <span style='color:#10B981;'>(✅ Paid)</span></div>
                <div><strong>Payment Method:</strong> Cash</div>
                <div><strong>Transaction Ref:</strong> " . $transaction_reference . "</div>
                <div><strong>Status:</strong> <span style='color:#10B981;'>Confirmed</span></div>
                <div><strong>Payment Status:</strong> <span style='color:#10B981;'>Completed</span></div>
                <div><strong>Payment Record:</strong> <span style='color:#10B981;'>✅ Recorded in system</span></div>
            </div>
            <div style='margin-top:12px; padding-top:12px; border-top:1px solid rgba(255,255,255,0.04);'>
                <small>📌 Ticket printed and handed to passenger • Payment recorded in payment table</small>
            </div>
        </div>";

        // Reset form
        $selected_schedule_id = 0;
        $reserved_seats = [];
        $total_seats = 40;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "❌ " . $e->getMessage();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Staff booking error: " . $e->getMessage());
        $error = "❌ Database Error: " . $e->getMessage();
    }
}

// Handle AJAX request for getting reserved seats
if (isset($_GET['get_seats']) && isset($_GET['schedule_id'])) {
    $schedule_id = (int)$_GET['schedule_id'];
    $stmt = $pdo->prepare("SELECT seat_number FROM reservation WHERE schedule_id = ? AND status != 'cancelled'");
    $stmt->execute([$schedule_id]);
    $seats = array_column($stmt->fetchAll(), 'seat_number');
    header('Content-Type: application/json');
    echo json_encode(['reserved_seats' => $seats]);
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Assist Booking - CamExpress</title>
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
            padding: 40px;
        }

        .form-container {
            background: #1E293B;
            padding: 40px;
            border-radius: 12px;
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
            border: 1px solid rgba(255, 255, 255, 0.04);
        }

        .header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            padding-bottom: 20px;
            margin-bottom: 30px;
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

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 500;
            color: #94A3B8;
            font-size: 13px;
        }

        .form-group label i {
            color: #3B82F6;
            margin-right: 6px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 14px 16px;
            background: #0F172A;
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 8px;
            color: #FFFFFF;
            font-size: 15px;
            width: 100%;
            outline: none;
            transition: 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #3B82F6;
            background: #0F172A;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #475569;
        }

        .form-group select option {
            background: #1E293B;
            color: #FFFFFF;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn-submit {
            background: #10B981;
            color: #FFFFFF;
            border: none;
            padding: 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            cursor: pointer;
            transition: 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .btn-submit:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .error-box {
            background: rgba(239, 68, 68, 0.06);
            color: #EF4444;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid rgba(239, 68, 68, 0.1);
            margin-bottom: 20px;
        }

        .footer {
            margin-top: 30px;
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
            border-color: #3B82F6;
            background: rgba(59, 130, 246, 0.04);
            transform: scale(1.05);
        }

        .seat-grid .seat input[type="radio"] {
            display: none;
        }

        .seat-grid .seat.selected {
            border-color: #3B82F6;
            background: rgba(59, 130, 246, 0.08);
            color: #3B82F6;
            font-weight: 600;
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.1);
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
            color: #3B82F6;
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
            background: rgba(59, 130, 246, 0.08);
            border-color: #3B82F6;
        }

        .seat-legend .legend-item .color-box.taken {
            background: rgba(239, 68, 68, 0.04);
            border-color: rgba(239, 68, 68, 0.2);
        }

        .payment-badge {
            background: #10B981;
            color: #FFFFFF;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
        }

        @media (max-width: 992px) {
            .seat-grid {
                grid-template-columns: repeat(6, 1fr);
            }
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full-width {
                grid-column: span 1;
            }

            .form-container {
                padding: 25px;
            }

            .seat-grid {
                grid-template-columns: repeat(4, 1fr);
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
</head>

<body>
    <div class="form-container">
        <div class="header">
            <h2><i class="fa-solid fa-user-plus"></i> Register & Book</h2>
            <p>Register a new passenger, select a trip, choose a seat, and confirm payment</p>
        </div>

        <?php if ($message): ?>
            <?php echo $message; ?>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-box">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="bookingForm">
            <div class="form-grid">
                <!-- Passenger Registration Section -->
                <div class="form-group full-width" style="background:rgba(59,130,246,0.02); border-radius:8px; padding:16px; border:1px solid rgba(59,130,246,0.06);">
                    <label style="color:#3B82F6; font-weight:600; margin-bottom:12px;">
                        <i class="fas fa-user-plus"></i> Passenger Registration
                    </label>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Full Name</label>
                            <input type="text" name="full_name" placeholder="wultof fortune" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email Address</label>
                            <input type="email" name="email" placeholder="wultof@example.com" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Phone Number</label>
                            <input type="text" name="phone_number" placeholder="+237 6XX XXX XXX" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Date of Birth</label>
                            <input type="date" name="date_of_birth">
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label><i class="fas fa-id-card"></i> ID Number (Optional)</label>
                            <input type="text" name="id_number" placeholder="National id">
                        </div>
                    </div>
                </div>

                <!-- Schedule Selection -->
                <div class="form-group full-width">
                    <label><i class="fas fa-bus"></i> Select Schedule (Trip)</label>
                    <select name="schedule_id" id="scheduleSelect" required>
                        <option value="" disabled selected>-- Choose a trip --</option>
                        <?php foreach ($schedules as $sch): ?>
                            <option value="<?php echo $sch['schedule_id']; ?>">
                                <?php echo htmlspecialchars($sch['original_city'] . ' → ' . $sch['destination']); ?>
                                | <?php echo date('d M H:i', strtotime($sch['departure_time'])); ?>
                                | XAF <?php echo number_format($sch['price'], 0); ?>
                                (<?php echo $sch['available_seats']; ?> seats left)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Seat Selection -->
                <div class="form-group full-width">
                    <label><i class="fas fa-chair"></i> Choose Seat</label>

                    <div class="seat-legend">
                        <span class="legend-item">
                            <span class="color-box available"></span> Available
                        </span>
                        <span class="legend-item">
                            <span class="color-box selected"></span> Selected
                        </span>
                        <span class="legend-item">
                            <span class="color-box taken"></span> Taken
                        </span>
                    </div>

                    <div class="seat-grid" id="seatGrid">
                        <?php
                        for ($i = 1; $i <= $total_seats; $i++) {
                            $seat = 'S' . $i;
                            $is_reserved = in_array($seat, $reserved_seats);
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

                <!-- Payment Section -->
                <div class="form-group full-width" style="background:rgba(16,185,129,0.02); border-radius:8px; padding:16px; border:1px solid rgba(16,185,129,0.06);">
                    <label style="color:#10B981; font-weight:600; margin-bottom:12px;">
                        <i class="fas fa-money-bill-wave"></i> Payment
                    </label>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label><i class="fas fa-money-bill"></i> Fare Amount</label>
                            <input type="number" step="0.01" name="fare_paid" placeholder="Enter amount" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-credit-card"></i> Payment Method</label>
                            <input type="text" value="Cash" disabled style="color:#10B981; font-weight:600;">
                            <small style="color:#94A3B8; font-size:12px; margin-top:4px;">✅ Cash payment confirmed at counter</small>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="form-group full-width">
                    <button type="submit" name="confirm_booking" class="btn-submit" id="submitBtn" disabled>
                        <i class="fa-solid fa-check-circle"></i> Confirm & Pay (Cash)
                    </button>
                </div>
            </div>
        </form>

        <div class="footer">
            <a href="staff_dashboard.php" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> Dashboard
            </a>
            <span>Logged in as Staff</span>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const submitBtn = document.getElementById('submitBtn');
            const scheduleSelect = document.getElementById('scheduleSelect');
            const seatGrid = document.getElementById('seatGrid');

            function checkFormValidity() {
                const hasName = document.querySelector('input[name="full_name"]').value.trim() !== '';
                const hasEmail = document.querySelector('input[name="email"]').value.trim() !== '';
                const hasPhone = document.querySelector('input[name="phone_number"]').value.trim() !== '';
                const hasSchedule = scheduleSelect.value > 0;
                const hasSeat = document.querySelector('input[name="seat_number"]:checked') !== null;
                const hasFare = document.querySelector('input[name="fare_paid"]').value > 0;

                submitBtn.disabled = !(hasName && hasEmail && hasPhone && hasSchedule && hasSeat && hasFare);
            }

            document.querySelectorAll('input, select').forEach(input => {
                input.addEventListener('change', checkFormValidity);
                input.addEventListener('input', checkFormValidity);
            });

            scheduleSelect.addEventListener('change', function() {
                const scheduleId = this.value;
                if (scheduleId) {
                    fetch(`staff_create_booking.php?get_seats=1&schedule_id=${scheduleId}`)
                        .then(response => response.json())
                        .then(data => {
                            updateSeatGrid(data.reserved_seats);
                            checkFormValidity();
                        })
                        .catch(error => console.error('Error loading seats:', error));
                }
            });

            seatGrid.addEventListener('click', function(e) {
                const seat = e.target.closest('.seat');
                if (!seat || seat.classList.contains('taken')) return;

                const radio = seat.querySelector('input[type="radio"]');
                if (radio) {
                    document.querySelectorAll('.seat').forEach(s => s.classList.remove('selected'));
                    seat.classList.add('selected');
                    radio.checked = true;
                    checkFormValidity();
                }
            });

            function updateSeatGrid(reservedSeats) {
                const seats = seatGrid.querySelectorAll('.seat');
                seats.forEach(seat => {
                    const radio = seat.querySelector('input[type="radio"]');
                    if (!radio) return;

                    const seatNumber = radio.value;
                    const isReserved = reservedSeats.includes(seatNumber);

                    if (isReserved) {
                        seat.classList.add('taken');
                        seat.classList.remove('selected');
                        radio.disabled = true;
                        radio.checked = false;
                        seat.querySelector('.seat-status').textContent = 'Taken';
                    } else {
                        seat.classList.remove('taken');
                        radio.disabled = false;
                        seat.querySelector('.seat-status').textContent = 'Available';
                    }
                });

                document.querySelectorAll('.seat').forEach(s => s.classList.remove('selected'));
                document.querySelectorAll('input[name="seat_number"]').forEach(r => r.checked = false);
                checkFormValidity();
            }

            checkFormValidity();
        });
    </script>
</body>

</html>