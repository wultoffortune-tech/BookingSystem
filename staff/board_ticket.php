<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'staff' && $_SESSION['user_role'] != 'admin')) {
    header("Location: ../login/login.php");
    exit();
}

// Get staff_id
$staff_id = isset($_SESSION['staff_id']) ? $_SESSION['staff_id'] : 0;

// If staff_id is not in session, try to get it from database
if ($staff_id == 0 && isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT staff_id FROM staff WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $staff = $stmt->fetch();
    if ($staff) {
        $staff_id = $staff['staff_id'];
        $_SESSION['staff_id'] = $staff_id;
    }
}

$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
$error = '';
$success = '';
$ticket_data = null;
$conflict_ticket = null;

// ========================================
// HANDLE BOARDING - Mark passenger as boarded
// ========================================
if (isset($_POST['board_ticket'])) {
    $booking_code = isset($_POST['booking_code']) ? trim($_POST['booking_code']) : '';
    $schedule_id = isset($_POST['schedule_id']) ? (int)$_POST['schedule_id'] : 0;
    $force_board = isset($_POST['force_board']) ? true : false;

    if (empty($booking_code)) {
        $error = 'Please enter a booking code.';
    } else {
        try {
            $pdo->beginTransaction();

            // Get ticket details
            $stmt = $pdo->prepare("SELECT r.*, u.full_name, u.email, u.phone_number,
                                   s.departure_time, s.arrival_time,
                                   rt.original_city, rt.destination,
                                   b.bus_name, b.bus_type
                                   FROM reservation r
                                   JOIN users u ON r.passenger_id = u.user_id
                                   JOIN schedule s ON r.schedule_id = s.schedule_id
                                   JOIN route rt ON s.route_id = rt.route_id
                                   JOIN bus b ON s.bus_id = b.bus_id
                                   WHERE r.booking_code = ? AND r.status = 'confirmed'");
            $stmt->execute([$booking_code]);
            $ticket = $stmt->fetch();

            if (!$ticket) {
                throw new Exception("Ticket not found or not confirmed.");
            }

            // Check if schedule_id matches
            if ($schedule_id > 0 && $ticket['schedule_id'] != $schedule_id) {
                throw new Exception("This ticket is for a different schedule.");
            }

            // Check if already boarded
            if ($ticket['boarded'] == 1) {
                throw new Exception("This passenger has already boarded.");
            }

            // ========================================
            // CHECK FOR SEAT CONFLICT
            // ========================================
            $stmt = $pdo->prepare("SELECT r.*, u.full_name, u.email 
                                   FROM reservation r 
                                   JOIN users u ON r.passenger_id = u.user_id 
                                   WHERE r.schedule_id = ? AND r.seat_number = ? 
                                   AND r.boarded = 1 AND r.booking_code != ?");
            $stmt->execute([$ticket['schedule_id'], $ticket['seat_number'], $booking_code]);
            $already_boarded = $stmt->fetch();

            if ($already_boarded && !$force_board) {
                // Conflict detected - show warning
                $conflict_ticket = $already_boarded;
                $pdo->rollBack();
                $error = "⚠️ SEAT CONFLICT: Seat " . $ticket['seat_number'] .
                    " is already occupied by " . $already_boarded['full_name'] .
                    " (Booking: " . $already_boarded['booking_code'] . ")";
                $ticket_data = $ticket;
                // Don't exit, show the conflict resolution form
            } else {
                // Check if departure time has passed
                $departure_time = strtotime($ticket['departure_time']);
                $current_time = time();

                if ($current_time > ($departure_time + 1800)) {
                    throw new Exception("This bus has already departed. Boarding not allowed.");
                }

                // ========================================
                // MARK AS BOARDED AND CHECKED IN
                // ========================================
                $boarded_by = ($staff_id > 0) ? $staff_id : null;

                $stmt = $pdo->prepare("UPDATE reservation 
                                       SET boarded = 1, 
                                           boarded_at = NOW(), 
                                           boarded_by_staff_id = ?,
                                           checked_in = 1,
                                           checked_in_at = NOW()
                                       WHERE reservation_id = ?");
                $stmt->execute([$boarded_by, $ticket['reservation_id']]);

                $pdo->commit();
                $success = "✅ Passenger boarded and checked in successfully!";
                $ticket_data = $ticket;

                // Refresh ticket data
                $stmt = $pdo->prepare("SELECT r.*, u.full_name, u.email, u.phone_number,
                                       s.departure_time, s.arrival_time,
                                       rt.original_city, rt.destination,
                                       b.bus_name, b.bus_type,
                                       us.full_name as boarded_by_name
                                       FROM reservation r
                                       JOIN users u ON r.passenger_id = u.user_id
                                       JOIN schedule s ON r.schedule_id = s.schedule_id
                                       JOIN route rt ON s.route_id = rt.route_id
                                       JOIN bus b ON s.bus_id = b.bus_id
                                       LEFT JOIN staff st ON r.boarded_by_staff_id = st.staff_id
                                       LEFT JOIN users us ON st.user_id = us.user_id
                                       WHERE r.reservation_id = ?");
                $stmt->execute([$ticket['reservation_id']]);
                $ticket_data = $stmt->fetch();
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "❌ " . $e->getMessage();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "❌ Database error: " . $e->getMessage();
        }
    }
}

// ========================================
// HANDLE FORCE BOARDING (Override conflict)
// ========================================
if (isset($_POST['force_board_ticket'])) {
    $booking_code = isset($_POST['booking_code']) ? trim($_POST['booking_code']) : '';
    $schedule_id = isset($_POST['schedule_id']) ? (int)$_POST['schedule_id'] : 0;
    $force_reason = isset($_POST['force_reason']) ? trim($_POST['force_reason']) : 'Seat conflict - overridden by staff';

    if (empty($booking_code)) {
        $error = 'Please enter a booking code.';
    } else {
        try {
            $pdo->beginTransaction();

            // Get ticket details
            $stmt = $pdo->prepare("SELECT r.*, u.full_name, u.email, u.phone_number,
                                   s.departure_time
                                   FROM reservation r
                                   JOIN users u ON r.passenger_id = u.user_id
                                   JOIN schedule s ON r.schedule_id = s.schedule_id
                                   WHERE r.booking_code = ? AND r.status = 'confirmed'");
            $stmt->execute([$booking_code]);
            $ticket = $stmt->fetch();

            if (!$ticket) {
                throw new Exception("Ticket not found or not confirmed.");
            }

            // Mark as boarded
            $boarded_by = ($staff_id > 0) ? $staff_id : null;

            $stmt = $pdo->prepare("UPDATE reservation 
                                   SET boarded = 1, 
                                       boarded_at = NOW(), 
                                       boarded_by_staff_id = ?,
                                       checked_in = 1,
                                       checked_in_at = NOW(),
                                       assistance_notes = CONCAT(IFNULL(assistance_notes, ''), '\nForce boarded: ', ?)
                                   WHERE reservation_id = ?");
            $stmt->execute([$boarded_by, $force_reason, $ticket['reservation_id']]);

            $pdo->commit();
            $success = "✅ Passenger force boarded successfully! (Seat conflict overridden)";
            $ticket_data = $ticket;

            // Refresh ticket data
            $stmt = $pdo->prepare("SELECT r.*, u.full_name, u.email, u.phone_number,
                                   s.departure_time, s.arrival_time,
                                   rt.original_city, rt.destination,
                                   b.bus_name, b.bus_type,
                                   us.full_name as boarded_by_name
                                   FROM reservation r
                                   JOIN users u ON r.passenger_id = u.user_id
                                   JOIN schedule s ON r.schedule_id = s.schedule_id
                                   JOIN route rt ON s.route_id = rt.route_id
                                   JOIN bus b ON s.bus_id = b.bus_id
                                   LEFT JOIN staff st ON r.boarded_by_staff_id = st.staff_id
                                   LEFT JOIN users us ON st.user_id = us.user_id
                                   WHERE r.reservation_id = ?");
            $stmt->execute([$ticket['reservation_id']]);
            $ticket_data = $stmt->fetch();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "❌ " . $e->getMessage();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "❌ Database error: " . $e->getMessage();
        }
    }
}

// ========================================
// GET TICKET DETAILS IF SEARCHED
// ========================================
if (isset($_POST['search_ticket'])) {
    $booking_code = isset($_POST['booking_code']) ? trim($_POST['booking_code']) : '';

    if (!empty($booking_code)) {
        $stmt = $pdo->prepare("SELECT r.*, u.full_name, u.email, u.phone_number,
                               s.departure_time, s.arrival_time,
                               rt.original_city, rt.destination,
                               b.bus_name, b.bus_type,
                               us.full_name as boarded_by_name
                               FROM reservation r
                               JOIN users u ON r.passenger_id = u.user_id
                               JOIN schedule s ON r.schedule_id = s.schedule_id
                               JOIN route rt ON s.route_id = rt.route_id
                               JOIN bus b ON s.bus_id = b.bus_id
                               LEFT JOIN staff st ON r.boarded_by_staff_id = st.staff_id
                               LEFT JOIN users us ON st.user_id = us.user_id
                               WHERE r.booking_code = ?");
        $stmt->execute([$booking_code]);
        $ticket_data = $stmt->fetch();

        if (!$ticket_data) {
            $error = "❌ Ticket not found.";
        }
    }
}

// Get today's schedules
$stmt = $pdo->prepare("SELECT s.schedule_id, s.departure_time,
                       rt.original_city, rt.destination,
                       b.bus_name
                       FROM schedule s
                       JOIN route rt ON s.route_id = rt.route_id
                       JOIN bus b ON s.bus_id = b.bus_id
                       WHERE DATE(s.departure_time) = CURDATE()
                       AND s.expired = 0
                       ORDER BY s.departure_time ASC");
$stmt->execute();
$today_schedules = $stmt->fetchAll();

include '../includes/header.php';
?>

<style>
    .page-section {
        padding: 140px 0 90px;
        background: #0F172A;
        min-height: 70vh;
    }

    .container {
        max-width: 900px;
        margin: 0 auto;
        padding: 0 24px;
    }

    .page-header {
        margin-bottom: 30px;
    }

    .page-header h1 {
        font-size: 28px;
        font-weight: 700;
        color: #FFFFFF;
    }

    .page-header h1 span {
        color: #F59E0B;
    }

    .page-header p {
        color: #94A3B8;
        margin-top: 4px;
    }

    .card {
        background: #1E293B;
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 12px;
        padding: 30px;
        margin-bottom: 20px;
    }

    .card h3 {
        color: #FFFFFF;
        margin-bottom: 16px;
        font-size: 18px;
    }

    .card h3 i {
        color: #F59E0B;
        margin-right: 8px;
    }

    .form-group {
        margin-bottom: 16px;
    }

    .form-group label {
        display: block;
        color: #94A3B8;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .form-group label i {
        color: #F59E0B;
        margin-right: 6px;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 12px 16px;
        background: #0F172A;
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 8px;
        color: #FFFFFF;
        font-size: 15px;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s ease;
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #F59E0B;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
    }

    .form-group input::placeholder {
        color: #475569;
    }

    .form-group select option {
        background: #1E293B;
        color: #FFFFFF;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .btn-primary {
        background: #3B82F6;
        color: #FFFFFF;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: 'Poppins', sans-serif;
    }

    .btn-primary:hover {
        background: #2563EB;
        transform: translateY(-2px);
    }

    .btn-success {
        background: #10B981;
        color: #FFFFFF;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: 'Poppins', sans-serif;
    }

    .btn-success:hover {
        background: #059669;
        transform: translateY(-2px);
    }

    .btn-danger {
        background: #EF4444;
        color: #FFFFFF;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: 'Poppins', sans-serif;
    }

    .btn-danger:hover {
        background: #DC2626;
        transform: translateY(-2px);
    }

    .btn-warning {
        background: #F59E0B;
        color: #0F172A;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: 'Poppins', sans-serif;
    }

    .btn-warning:hover {
        background: #D97706;
        transform: translateY(-2px);
    }

    .error-box {
        background: rgba(239, 68, 68, 0.06);
        color: #EF4444;
        padding: 14px 18px;
        border-radius: 8px;
        border: 1px solid rgba(239, 68, 68, 0.06);
        margin-bottom: 16px;
    }

    .success-box {
        background: rgba(16, 185, 129, 0.06);
        color: #10B981;
        padding: 14px 18px;
        border-radius: 8px;
        border: 1px solid rgba(16, 185, 129, 0.06);
        margin-bottom: 16px;
    }

    .conflict-box {
        background: rgba(239, 68, 68, 0.06);
        color: #EF4444;
        padding: 16px 20px;
        border-radius: 8px;
        border: 1px solid rgba(239, 68, 68, 0.1);
        margin-bottom: 16px;
    }

    .conflict-box .conflict-icon {
        font-size: 24px;
        display: block;
        margin-bottom: 8px;
    }

    .conflict-box .conflict-title {
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .conflict-box .conflict-details {
        background: #0F172A;
        border-radius: 6px;
        padding: 12px;
        margin: 8px 0;
    }

    .conflict-box .conflict-details .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 4px 0;
        font-size: 14px;
    }

    .conflict-box .conflict-details .detail-row .label {
        color: #94A3B8;
    }

    .conflict-box .conflict-details .detail-row .value {
        color: #FFFFFF;
    }

    .conflict-actions {
        display: flex;
        gap: 12px;
        margin-top: 12px;
        flex-wrap: wrap;
    }

    /* Ticket Details */
    .ticket-details {
        background: #0F172A;
        border-radius: 8px;
        padding: 20px;
        margin-top: 16px;
        border: 1px solid rgba(255, 255, 255, 0.04);
    }

    .ticket-details .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.02);
    }

    .ticket-details .detail-row:last-child {
        border-bottom: none;
    }

    .ticket-details .detail-label {
        color: #94A3B8;
        font-size: 13px;
    }

    .ticket-details .detail-value {
        color: #FFFFFF;
        font-weight: 500;
    }

    .ticket-details .detail-value.highlight {
        color: #F59E0B;
    }

    .status-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 50px;
        font-size: 11px;
        font-weight: 600;
    }

    .status-badge.confirmed {
        background: rgba(16, 185, 129, 0.06);
        color: #10B981;
        border: 1px solid rgba(16, 185, 129, 0.06);
    }

    .status-badge.boarded {
        background: rgba(59, 130, 246, 0.06);
        color: #3B82F6;
        border: 1px solid rgba(59, 130, 246, 0.06);
    }

    .status-badge.checked_in {
        background: rgba(245, 158, 11, 0.06);
        color: #F59E0B;
        border: 1px solid rgba(245, 158, 11, 0.06);
    }

    .status-badge.cancelled {
        background: rgba(239, 68, 68, 0.06);
        color: #EF4444;
        border: 1px solid rgba(239, 68, 68, 0.06);
    }

    .btn-action {
        padding: 8px 16px;
        border-radius: 6px;
        border: none;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: 'Poppins', sans-serif;
        text-decoration: none;
        display: inline-block;
        margin: 4px;
    }

    .btn-action.btn-board {
        background: #10B981;
        color: #FFFFFF;
        border: 1px solid #10B981;
    }

    .btn-action.btn-board:hover {
        background: #059669;
        transform: translateY(-2px);
    }

    .btn-action:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    .boarded-success {
        background: rgba(16, 185, 129, 0.04);
        border: 1px solid rgba(16, 185, 129, 0.06);
        border-radius: 8px;
        padding: 12px 16px;
        margin-top: 12px;
        text-align: center;
    }

    .boarded-success i {
        color: #10B981;
        font-size: 20px;
        display: block;
        margin-bottom: 4px;
    }

    .boarded-success .message {
        color: #10B981;
        font-weight: 600;
        font-size: 16px;
    }

    .boarded-success .sub-message {
        color: #94A3B8;
        font-size: 13px;
        margin-top: 4px;
    }

    .staff-warning {
        background: rgba(245, 158, 11, 0.06);
        border: 1px solid rgba(245, 158, 11, 0.06);
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 16px;
        color: #F59E0B;
        font-size: 14px;
    }

    .staff-warning i {
        margin-right: 8px;
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }

        .page-section {
            padding: 110px 0 60px;
        }

        .conflict-actions {
            flex-direction: column;
        }

        .conflict-actions form {
            width: 100%;
        }
    }
</style>

<section class="page-section">
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-bus"></i> Board <span>Passengers</span></h1>
            <p>Enter a booking code to mark passenger as boarded and checked in on the bus.</p>
        </div>

        <?php if ($error): ?>
            <div class="error-box"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-box"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($staff_id == 0 && !$is_admin): ?>
            <div class="staff-warning">
                <i class="fas fa-exclamation-triangle"></i>
                Staff ID not found. Please contact administrator to set up your staff account.
            </div>
        <?php endif; ?>

        <!-- Search Ticket -->
        <div class="card">
            <h3><i class="fas fa-search"></i> Find & Board Passenger</h3>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-qrcode"></i> Booking Code</label>
                        <input type="text" name="booking_code" id="bookingCodeInput" placeholder="Enter 8-digit booking code" required>
                    </div>
                    <div class="form-group" style="display:flex; align-items:flex-end; gap:8px;">
                        <button type="submit" name="search_ticket" class="btn-primary" style="width:100%;">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Conflict Warning -->
        <?php if ($conflict_ticket && $ticket_data): ?>
            <div class="conflict-box">
                <div class="conflict-icon">⚠️</div>
                <div class="conflict-title">Seat Conflict Detected!</div>
                <p style="color:#FCA5A5;">The seat <?php echo $ticket_data['seat_number']; ?> is already occupied by another passenger.</p>

                <div class="conflict-details">
                    <div class="detail-row">
                        <span class="label">Current Passenger</span>
                        <span class="value"><?php echo htmlspecialchars($ticket_data['full_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Current Booking Code</span>
                        <span class="value">#<?php echo htmlspecialchars($ticket_data['booking_code']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Occupying Passenger</span>
                        <span class="value" style="color:#EF4444;"><?php echo htmlspecialchars($conflict_ticket['full_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Occupying Booking Code</span>
                        <span class="value" style="color:#EF4444;">#<?php echo htmlspecialchars($conflict_ticket['booking_code']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Seat</span>
                        <span class="value" style="color:#F59E0B;"><?php echo htmlspecialchars($ticket_data['seat_number']); ?></span>
                    </div>
                </div>

                <div class="conflict-actions">
                    <form method="POST" style="flex:1;">
                        <input type="hidden" name="booking_code" value="<?php echo $ticket_data['booking_code']; ?>">
                        <input type="hidden" name="schedule_id" value="<?php echo $ticket_data['schedule_id']; ?>">
                        <input type="hidden" name="force_board" value="1">
                        <button type="submit" name="board_ticket" class="btn-danger" style="width:100%; padding:12px;">
                            <i class="fas fa-exclamation-triangle"></i> Override & Board Anyway
                        </button>
                    </form>
                    <form method="POST" style="flex:1;">
                        <input type="hidden" name="booking_code" value="<?php echo $ticket_data['booking_code']; ?>">
                        <input type="hidden" name="schedule_id" value="<?php echo $ticket_data['schedule_id']; ?>">
                        <input type="hidden" name="force_reason" value="Seat conflict - alternative seat assigned">
                        <button type="submit" name="force_board_ticket" class="btn-warning" style="width:100%; padding:12px;">
                            <i class="fas fa-undo"></i> Assign Alternative Seat
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Ticket Details -->
        <?php if ($ticket_data && !$conflict_ticket): ?>
            <div class="card">
                <h3><i class="fas fa-ticket-alt"></i> Ticket Details</h3>

                <div class="ticket-details">
                    <div class="detail-row">
                        <span class="detail-label">Booking Code</span>
                        <span class="detail-value highlight">#<?php echo htmlspecialchars($ticket_data['booking_code']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Passenger</span>
                        <span class="detail-value"><?php echo htmlspecialchars($ticket_data['full_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email</span>
                        <span class="detail-value"><?php echo htmlspecialchars($ticket_data['email']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Phone</span>
                        <span class="detail-value"><?php echo htmlspecialchars($ticket_data['phone_number']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Route</span>
                        <span class="detail-value">
                            <?php echo htmlspecialchars($ticket_data['original_city']); ?>
                            <i class="fas fa-arrow-right" style="color:#475569; font-size:12px;"></i>
                            <?php echo htmlspecialchars($ticket_data['destination']); ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Bus</span>
                        <span class="detail-value"><?php echo htmlspecialchars($ticket_data['bus_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Seat</span>
                        <span class="detail-value"><?php echo htmlspecialchars($ticket_data['seat_number']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Departure</span>
                        <span class="detail-value"><?php echo date('d M Y, H:i', strtotime($ticket_data['departure_time'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status</span>
                        <span class="detail-value">
                            <?php if (isset($ticket_data['checked_in']) && $ticket_data['checked_in'] == 1): ?>
                                <span class="status-badge checked_in"><i class="fas fa-check-circle"></i> On Board</span>
                            <?php elseif (isset($ticket_data['boarded']) && $ticket_data['boarded'] == 1): ?>
                                <span class="status-badge boarded"><i class="fas fa-user-check"></i> Boarded</span>
                            <?php else: ?>
                                <span class="status-badge confirmed"><i class="fas fa-clock"></i> Confirmed</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if (isset($ticket_data['boarded']) && $ticket_data['boarded'] == 1 && isset($ticket_data['boarded_by_name'])): ?>
                        <div class="detail-row">
                            <span class="detail-label">Boarded By</span>
                            <span class="detail-value"><?php echo htmlspecialchars($ticket_data['boarded_by_name']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($ticket_data['boarded_at'])): ?>
                        <div class="detail-row">
                            <span class="detail-label">Boarded At</span>
                            <span class="detail-value"><?php echo date('d M Y, H:i', strtotime($ticket_data['boarded_at'])); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Board Action -->
                <div style="margin-top: 16px;">
                    <?php if (isset($ticket_data['checked_in']) && $ticket_data['checked_in'] == 0 && isset($ticket_data['boarded']) && $ticket_data['boarded'] == 0): ?>
                        <form method="POST">
                            <input type="hidden" name="booking_code" value="<?php echo $ticket_data['booking_code']; ?>">
                            <input type="hidden" name="schedule_id" value="<?php echo $ticket_data['schedule_id']; ?>">
                            <button type="submit" name="board_ticket" class="btn-action btn-board" style="width:100%; padding:14px; font-size:16px;">
                                <i class="fas fa-bus"></i> Board Passenger Now
                            </button>
                            <div style="text-align:center; margin-top:8px;">
                                <small style="color:#94A3B8;">
                                    <i class="fas fa-info-circle"></i> This will mark the passenger as boarded and checked in on the bus.
                                </small>
                            </div>
                        </form>
                    <?php elseif (isset($ticket_data['checked_in']) && $ticket_data['checked_in'] == 1): ?>
                        <div class="boarded-success">
                            <i class="fas fa-check-circle"></i>
                            <div class="message">Passenger is already on board!</div>
                            <div class="sub-message">
                                Boarded at: <?php echo date('d M Y, H:i', strtotime($ticket_data['boarded_at'])); ?>
                                <?php if (isset($ticket_data['boarded_by_name'])): ?>
                                    <br>Boarded by: <?php echo htmlspecialchars($ticket_data['boarded_by_name']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Today's Schedules -->
        <div class="card">
            <h3><i class="fas fa-calendar-day"></i> Today's Schedules</h3>
            <?php if (count($today_schedules) > 0): ?>
                <div style="display:grid; gap:8px;">
                    <?php foreach ($today_schedules as $schedule): ?>
                        <div style="background:#0F172A; border-radius:8px; padding:12px 16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                            <div>
                                <strong style="color:#FFFFFF;">
                                    <?php echo htmlspecialchars($schedule['original_city'] . ' → ' . $schedule['destination']); ?>
                                </strong>
                                <div style="color:#94A3B8; font-size:13px;">
                                    <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($schedule['departure_time'])); ?>
                                    <i class="fas fa-bus" style="margin-left:12px;"></i> <?php echo htmlspecialchars($schedule['bus_name']); ?>
                                </div>
                            </div>
                            <span style="color:#F59E0B; font-weight:600;">
                                <?php
                                $departure = strtotime($schedule['departure_time']);
                                $now = time();
                                if ($now > $departure) {
                                    echo 'Departed';
                                } else {
                                    $diff = $departure - $now;
                                    echo gmdate('H:i', $diff);
                                }
                                ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color:#94A3B8; text-align:center; padding:20px;">
                    <i class="fas fa-calendar"></i> No schedules for today.
                </p>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('bookingCodeInput');
        if (input) {
            input.focus();
        }
    });
</script>

<?php include '../includes/footer.php'; ?>