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

$error = '';
$success = '';

// Get routes for dropdown
$routes = $pdo->query("SELECT route_id, original_city, destination, base_fare FROM route ORDER BY original_city")->fetchAll();
$buses = $pdo->query("SELECT bus_id, bus_name, bus_type, total_seats FROM bus WHERE status = 'active'")->fetchAll();

// Handle booking assistance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assist_booking'])) {

    $passenger_name = trim($_POST['passenger_name'] ?? '');
    $passenger_email = trim($_POST['passenger_email'] ?? '');
    $passenger_phone = trim($_POST['passenger_phone'] ?? '');
    $route_id = (int)($_POST['route_id'] ?? 0);
    $bus_id = (int)($_POST['bus_id'] ?? 0);
    $departure_date = $_POST['departure_date'] ?? '';
    $departure_time = $_POST['departure_time'] ?? '';
    $seat_number = trim($_POST['seat_number'] ?? '');
    $fare_paid = (float)($_POST['fare_paid'] ?? 0);

    if (empty($passenger_name) || empty($passenger_email) || empty($seat_number) || $route_id <= 0 || $bus_id <= 0) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            // Check if passenger exists or create new
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
            $stmt->execute(['email' => $passenger_email]);
            $user = $stmt->fetch();

            if ($user) {
                $user_id = $user['user_id'];
            } else {
                // Create new user (simplified registration for staff-assisted)
                $hashed_password = password_hash('password123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone_number, password, role) 
                                       VALUES (:name, :email, :phone, :password, 'user')");
                $stmt->execute([
                    'name' => $passenger_name,
                    'email' => $passenger_email,
                    'phone' => $passenger_phone,
                    'password' => $hashed_password
                ]);
                $user_id = $pdo->lastInsertId();
            }

            // Get or create schedule
            $departure_datetime = $departure_date . ' ' . $departure_time . ':00';

            $stmt = $pdo->prepare("SELECT schedule_id, available_seats FROM schedule 
                                   WHERE route_id = :route_id AND bus_id = :bus_id 
                                   AND DATE(departure_time) = :date 
                                   AND TIME(departure_time) = :time");
            $stmt->execute([
                'route_id' => $route_id,
                'bus_id' => $bus_id,
                'date' => $departure_date,
                'time' => $departure_time . ':00'
            ]);
            $schedule = $stmt->fetch();

            if (!$schedule) {
                // Create new schedule
                $stmt = $pdo->prepare("INSERT INTO schedule (route_id, bus_id, departure_time, arrival_time, available_seats, price) 
                                       SELECT :route_id, :bus_id, :departure, :arrival, :seats, :price");
                $stmt->execute([
                    'route_id' => $route_id,
                    'bus_id' => $bus_id,
                    'departure' => $departure_datetime,
                    'arrival' => date('Y-m-d H:i:s', strtotime($departure_datetime . ' + 3 hours')),
                    'seats' => 40,
                    'price' => $fare_paid
                ]);
                $schedule_id = $pdo->lastInsertId();
            } else {
                $schedule_id = $schedule['schedule_id'];
                if ($schedule['available_seats'] <= 0) {
                    $error = 'This bus is fully booked. Please select another bus.';
                    throw new Exception($error);
                }
            }

            // Create reservation
            $stmt = $pdo->prepare("INSERT INTO reservation 
                                   (passenger_id, schedule_id, seat_number, fare_paid, status) 
                                   VALUES (?, ?, ?, ?, 'confirmed')");
            $stmt->execute([$user_id, $schedule_id, $seat_number, $fare_paid]);
            $reservation_id = $pdo->lastInsertId();

            // Update available seats
            $stmt = $pdo->prepare("UPDATE schedule SET available_seats = available_seats - 1 WHERE schedule_id = ?");
            $stmt->execute([$schedule_id]);

            $success = '✅ Booking created successfully! Reservation #' . $reservation_id;
        } catch (Exception $e) {
            $error = $e->getMessage() ?: 'Unable to complete booking. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assist Booking - CamExpress Staff</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="staff.css">
    <style>
        .assist-container {
            padding: 100px 24px 60px;
            max-width: 800px;
            margin: 0 auto;
        }

        .assist-card {
            background: #1E293B;
            border-radius: 16px;
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.04);
            margin-bottom: 24px;
        }

        .assist-card h2 {
            color: #FFFFFF;
            font-size: 22px;
            margin-bottom: 16px;
        }

        .assist-card h2 i {
            color: #8B5CF6;
            margin-right: 10px;
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
        .form-group select {
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
        .form-group select:focus {
            outline: none;
            border-color: #8B5CF6;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .btn-assist {
            padding: 14px 32px;
            background: linear-gradient(135deg, #8B5CF6, #7C3AED);
            color: #FFFFFF;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-assist:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(139, 92, 246, 0.15);
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

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .assist-container {
                padding: 80px 16px 40px;
            }
        }
    </style>
</head>

<body>

    <div class="assist-container">

        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
            <h1 style="color: #FFFFFF; font-size: 28px;">
                <i class="fas fa-hand-holding-heart" style="color: #8B5CF6;"></i> Assist Booking
            </h1>
            <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <div class="assist-card">
            <h2><i class="fas fa-user-plus"></i> Create Booking for Passenger</h2>

            <?php if ($success): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">

                <div class="form-row">
                    <div class="form-group">
                        <label for="passenger_name"><i class="fas fa-user"></i> Passenger Name *</label>
                        <input type="text" id="passenger_name" name="passenger_name" placeholder="Enter full name" required>
                    </div>
                    <div class="form-group">
                        <label for="passenger_email"><i class="fas fa-envelope"></i> Email *</label>
                        <input type="email" id="passenger_email" name="passenger_email" placeholder="Enter email" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="passenger_phone"><i class="fas fa-phone"></i> Phone Number</label>
                    <input type="tel" id="passenger_phone" name="passenger_phone" placeholder="Enter phone number">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="route_id"><i class="fas fa-route"></i> Route *</label>
                        <select id="route_id" name="route_id" required>
                            <option value="">Select Route</option>
                            <?php foreach ($routes as $route): ?>
                                <option value="<?php echo $route['route_id']; ?>">
                                    <?php echo htmlspecialchars($route['original_city']); ?> → <?php echo htmlspecialchars($route['destination']); ?>
                                    (XAF <?php echo number_format($route['base_fare'], 0); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="bus_id"><i class="fas fa-bus"></i> Bus *</label>
                        <select id="bus_id" name="bus_id" required>
                            <option value="">Select Bus</option>
                            <?php foreach ($buses as $bus): ?>
                                <option value="<?php echo $bus['bus_id']; ?>">
                                    <?php echo htmlspecialchars($bus['bus_name']); ?> (<?php echo htmlspecialchars($bus['bus_type']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="departure_date"><i class="fas fa-calendar"></i> Departure Date *</label>
                        <input type="date" id="departure_date" name="departure_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="departure_time"><i class="fas fa-clock"></i> Departure Time *</label>
                        <input type="time" id="departure_time" name="departure_time" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="seat_number"><i class="fas fa-chair"></i> Seat Number *</label>
                        <input type="text" id="seat_number" name="seat_number" placeholder="e.g. S1, S2, A1" required>
                    </div>
                    <div class="form-group">
                        <label for="fare_paid"><i class="fas fa-money-bill"></i> Fare (XAF)</label>
                        <input type="number" id="fare_paid" name="fare_paid" placeholder="5000" step="100" min="0">
                    </div>
                </div>

                <button type="submit" name="assist_booking" class="btn-assist">
                    <i class="fas fa-check-circle"></i> Create Booking
                </button>
            </form>
        </div>

    </div>

</body>

</html>