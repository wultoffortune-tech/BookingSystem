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

// Get booking ID
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($booking_id > 0) {
    $stmt = $pdo->prepare("SELECT r.*, u.full_name, u.email, rt.original_city, rt.destination, s.departure_time
                           FROM reservation r
                           JOIN users u ON r.passenger_id = u.user_id
                           JOIN schedule s ON r.schedule_id = s.schedule_id
                           JOIN route rt ON s.route_id = rt.route_id
                           WHERE r.reservation_id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();
}

// Handle extension
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['extend'])) {
    $reservation_id = (int)$_POST['reservation_id'];
    $new_date = $_POST['new_date'] ?? '';
    $new_time = $_POST['new_time'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    if (empty($new_date) || empty($new_time)) {
        $error = 'Please select a new date and time.';
    } else {
        try {
            $new_datetime = $new_date . ' ' . $new_time . ':00';

            // Check if new schedule exists
            $stmt = $pdo->prepare("SELECT schedule_id FROM schedule 
                                   WHERE DATE(departure_time) = :date 
                                   AND TIME(departure_time) = :time 
                                   AND available_seats > 0");
            $stmt->execute(['date' => $new_date, 'time' => $new_time . ':00']);
            $new_schedule = $stmt->fetch();

            if (!$new_schedule) {
                $error = 'No available bus for the selected date and time.';
            } else {
                // Update reservation
                $stmt = $pdo->prepare("UPDATE reservation SET schedule_id = ?, 
                                       extended_by = ?, extension_reason = ?, 
                                       extended_at = NOW() 
                                       WHERE reservation_id = ?");
                $stmt->execute([$new_schedule['schedule_id'], $_SESSION['staff_id'], $reason, $reservation_id]);

                // Update old schedule seats
                $stmt = $pdo->prepare("UPDATE schedule SET available_seats = available_seats + 1 
                                       WHERE schedule_id = (SELECT schedule_id FROM reservation WHERE reservation_id = ?)");
                $stmt->execute([$reservation_id]);

                // Update new schedule seats
                $stmt = $pdo->prepare("UPDATE schedule SET available_seats = available_seats - 1 
                                       WHERE schedule_id = ?");
                $stmt->execute([$new_schedule['schedule_id']]);

                $message = '✅ Ticket has been extended successfully!';

                // Refresh booking data
                $stmt = $pdo->prepare("SELECT r.*, u.full_name, u.email, rt.original_city, rt.destination, s.departure_time
                                       FROM reservation r
                                       JOIN users u ON r.passenger_id = u.user_id
                                       JOIN schedule s ON r.schedule_id = s.schedule_id
                                       JOIN route rt ON s.route_id = rt.route_id
                                       WHERE r.reservation_id = ?");
                $stmt->execute([$reservation_id]);
                $booking = $stmt->fetch();
            }
        } catch (PDOException $e) {
            $error = '❌ Unable to extend ticket. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Extend Ticket - CamExpress Staff</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="staff.css">
    <style>
        .extend-container {
            padding: 100px 24px 60px;
            max-width: 800px;
            margin: 0 auto;
        }

        .extend-card {
            background: #1E293B;
            border-radius: 16px;
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.04);
            margin-bottom: 24px;
        }

        .extend-card h2 {
            color: #FFFFFF;
            font-size: 22px;
            margin-bottom: 16px;
        }

        .extend-card h2 i {
            color: #3B82F6;
            margin-right: 10px;
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

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #94A3B8;
            margin-bottom: 4px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.02);
            color: #FFFFFF;
            font-size: 15px;
            font-family: 'Poppins', sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3B82F6;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn-extend {
            padding: 12px 32px;
            background: #3B82F6;
            color: #FFFFFF;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-extend:hover {
            background: #2563EB;
            transform: translateY(-2px);
        }

        .btn-extend:disabled {
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <div class="extend-container">

        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
            <h1 style="color: #FFFFFF; font-size: 28px;">
                <i class="fas fa-clock" style="color: #3B82F6;"></i> Extend Ticket
            </h1>
            <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <!-- Search Ticket -->
        <div class="extend-card">
            <h2><i class="fas fa-search"></i> Find Ticket to Extend</h2>
            <form method="GET">
                <div class="search-box">
                    <input type="text" name="id" placeholder="Enter booking ID to extend..." value="<?php echo $booking_id > 0 ? $booking_id : ''; ?>">
                    <button type="submit"><i class="fas fa-search"></i> Find</button>
                </div>
            </form>

            <?php if ($booking_id > 0 && !$booking): ?>
                <div style="color: #EF4444; padding: 16px; text-align: center;">
                    <i class="fas fa-exclamation-circle"></i> Booking not found. Please try again.
                </div>
            <?php endif; ?>
        </div>

        <!-- Extend Ticket -->
        <?php if ($booking): ?>
            <div class="extend-card">
                <h2><i class="fas fa-ticket-alt"></i> Extend Ticket #<?php echo $booking['reservation_id']; ?></h2>

                <?php if ($message): ?>
                    <div class="message success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="message error"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="detail-row">
                    <span class="label">Passenger</span>
                    <span class="value"><?php echo htmlspecialchars($booking['full_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Current Route</span>
                    <span class="value"><?php echo htmlspecialchars($booking['original_city']); ?> → <?php echo htmlspecialchars($booking['destination']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Current Departure</span>
                    <span class="value"><?php echo date('d M Y H:i', strtotime($booking['departure_time'])); ?></span>
                </div>

                <form method="POST" style="margin-top: 20px;">
                    <input type="hidden" name="reservation_id" value="<?php echo $booking['reservation_id']; ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_date"><i class="fas fa-calendar"></i> New Date</label>
                            <input type="date" id="new_date" name="new_date"
                                min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="new_time"><i class="fas fa-clock"></i> New Time</label>
                            <input type="time" id="new_time" name="new_time" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reason"><i class="fas fa-comment"></i> Reason for Extension</label>
                        <textarea id="reason" name="reason" placeholder="Enter reason for extending this ticket..."></textarea>
                    </div>

                    <button type="submit" name="extend" class="btn-extend">
                        <i class="fas fa-clock"></i> Extend Ticket
                    </button>
                </form>
            </div>
        <?php endif; ?>

    </div>

</body>

</html>