<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'staff' && $_SESSION['user_role'] != 'admin')) {
    header("Location: ../login.php");
    exit();
}

$message = "";
<<<<<<< HEAD

// Fetch the first valid schedule ID automatically
$stmt = $pdo->query("SELECT schedule_id FROM schedule WHERE expired = 0 AND available_seats > 0 LIMIT 1");
$first_schedule = $stmt->fetch();
$default_schedule_id = $first_schedule ? $first_schedule['schedule_id'] : 1;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo->beginTransaction();
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone_number = trim($_POST['phone_number']);
        $schedule_id = (int)$_POST['schedule_id'];
        $seat_number = trim($_POST['seat_number']);
        $fare_paid = (float)$_POST['fare_paid'];

        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existing_user = $stmt->fetch();

        if ($existing_user) {
            $passenger_id = $existing_user['user_id'];
        } else {
            $date_of_birth = '2000-01-01';
            $id_number = 'WALKIN-' . rand(1000, 9999);
            $password_hash = password_hash('guest123', PASSWORD_DEFAULT);
            $role = 'passenger';
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone_number, date_of_birth, id_number, password, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $phone_number, $date_of_birth, $id_number, $password_hash, $role]);
            $passenger_id = $pdo->lastInsertId();
        }

        $booking_code = str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("INSERT INTO reservation (booking_code, passenger_id, schedule_id, seat_number, fare_paid, status, seats_released, reservation_date) VALUES (?, ?, ?, ?, ?, 'confirmed', 0, NOW())");
        $stmt->execute([$booking_code, $passenger_id, $schedule_id, $seat_number, $fare_paid]);
        $stmt = $pdo->prepare("UPDATE schedule SET available_seats = available_seats - 1 WHERE schedule_id = ? AND available_seats > 0");
        $stmt->execute([$schedule_id]);
        $pdo->commit();

        $message = "<div style='background:rgba(52,211,153,0.06); color:#34D399; padding:15px; border-radius:10px; border:1px solid rgba(52,211,153,0.1); margin-bottom:20px;'>✅ Booking Successful!<br><strong>Passenger:</strong> " . htmlspecialchars($full_name) . "<br><strong>Code:</strong> $booking_code</div>";
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Staff booking error: " . $e->getMessage());
        $message = "<div style='background:rgba(239,68,68,0.06); color:#EF4444; padding:15px; border-radius:10px; border:1px solid rgba(239,68,68,0.1); margin-bottom:20px;'>❌ Database Error: " . $e->getMessage() . "</div>";
=======
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ticket_code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
    $stmt = $pdo->prepare("INSERT INTO bookings (customer_name, customer_phone, route, travel_date, seat_number, ticket_code, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");

    if ($stmt->execute([$_POST['customer_name'], $_POST['customer_phone'], $_POST['route'], $_POST['travel_date'], $_POST['seat_number'], $ticket_code, $_SESSION['user_id']])) {
        $message = "<div style='background:rgba(52,211,153,0.06); color:#34D399; padding:15px; border-radius:10px; border:1px solid rgba(52,211,153,0.1); margin-bottom:20px;'>✅ Booking Successful! Ticket Code: <strong>$ticket_code</strong></div>";
    } else {
        $message = "<div style='background:rgba(239,68,68,0.06); color:#EF4444; padding:15px; border-radius:10px; border:1px solid rgba(239,68,68,0.1); margin-bottom:20px;'>❌ Error creating booking.</div>";
>>>>>>> 278447dbacb8319f2c179d04ccc94166e0027099
    }
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
        }

        .form-container {
            background: #1E293B;
            padding: 40px;
            border-radius: 12px;
            max-width: 800px;
            width: 100%;
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
            color: #38BDF8;
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

<<<<<<< HEAD
        .form-group input,
        .form-group select {
=======
        .form-group input {
>>>>>>> 278447dbacb8319f2c179d04ccc94166e0027099
            padding: 14px 16px;
            background: #0F172A;
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 8px;
            color: #FFFFFF;
            font-size: 15px;
            width: 100%;
            outline: none;
            transition: 0.3s;
<<<<<<< HEAD
            font-family: 'Poppins', sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus {
=======
        }

        .form-group input:focus {
>>>>>>> 278447dbacb8319f2c179d04ccc94166e0027099
            border-color: #38BDF8;
            background: #0F172A;
        }

<<<<<<< HEAD
        .form-group input::placeholder,
        .form-group select::placeholder {
            color: #475569;
        }

        .form-group select option {
            background: #1E293B;
            color: #FFFFFF;
        }

=======
        .form-group input::placeholder {
            color: #475569;
        }

>>>>>>> 278447dbacb8319f2c179d04ccc94166e0027099
        .btn-submit {
            background: #38BDF8;
            color: #0F172A;
            border: none;
            padding: 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-submit:hover {
            background: #0EA5E9;
            transform: translateY(-2px);
        }

        .footer {
            margin-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.04);
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
        }

        .back-link {
            color: #94A3B8;
            text-decoration: none;
            transition: 0.3s;
        }

        .back-link:hover {
            color: #38BDF8;
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
        }
    </style>
</head>

<body>
    <div class="form-container">
        <div class="header">
            <h2><i class="fa-solid fa-pen-to-square"></i> Assist Booking</h2>
<<<<<<< HEAD
            <p>Select a trip, enter passenger details, and generate a ticket.</p>
=======
            <p>Enter passenger details to generate a new ticket</p>
>>>>>>> 278447dbacb8319f2c179d04ccc94166e0027099
        </div>
        <?php echo $message; ?>
        <form method="POST">
            <div class="form-grid">
<<<<<<< HEAD
                <div class="form-group"><label>Full Name</label><input type="text" name="full_name" placeholder="Passenger Name" required></div>
                <div class="form-group"><label>Email Address</label><input type="email" name="email" placeholder="passenger@example.com" required></div>
                <div class="form-group"><label>Phone Number</label><input type="text" name="phone_number" placeholder="+237 675431233" required></div>
                <div class="form-group"><label>Seat Number</label><input type="text" name="seat_number" placeholder="S12" required></div>
                <div class="form-group full-width">
                    <label>Select Schedule (Trip)</label>
                    <select name="schedule_id" required>
                        <option value="" disabled selected>-- Choose a trip --</option>
                        <?php
                        $sql = "SELECT s.schedule_id, s.departure_time, r.original_city, r.destination, s.price, s.available_seats
                                FROM schedule s JOIN route r ON s.route_id = r.route_id
                                WHERE s.expired = 0 AND s.available_seats > 0 ORDER BY s.departure_time ASC";
                        foreach ($pdo->query($sql) as $sch) {
                            echo "<option value='{$sch['schedule_id']}'>" .
                                htmlspecialchars($sch['original_city'] . ' → ' . $sch['destination']) .
                                " | " . date('d M H:i', strtotime($sch['departure_time'])) .
                                " | XAF " . number_format($sch['price'], 0) .
                                " (Seats: " . $sch['available_seats'] . ")</option>";
                        }
                        ?>
                    </select>
=======
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="customer_name" placeholder="Passenger Name" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="customer_phone" placeholder="+237 675431233" required>
                </div>
                <div class="form-group">
                    <label>Travel Route</label>
                    <input type="text" name="route" placeholder="Yaoundé - Douala" required>
                </div>
                <div class="form-group">
                    <label>Seat Number</label>
                    <input type="text" name="seat_number" placeholder="S12" required>
                </div>
                <div class="form-group full-width">
                    <label>Travel Date</label>
                    <input type="date" name="travel_date" required>
                </div>
                <div class="form-group full-width">
                    <button type="submit" class="btn-submit"><i class="fa-solid fa-check"></i> Create Booking</button>
>>>>>>> 278447dbacb8319f2c179d04ccc94166e0027099
                </div>
                <div class="form-group full-width"><label>Cash Collected (Fare Paid)</label><input type="number" step="0.01" name="fare_paid" placeholder="Enter cash amount (e.g. 4500)" required></div>
                <div class="form-group full-width"><button type="submit" class="btn-submit"><i class="fa-solid fa-check"></i> Create Booking</button></div>
            </div>
        </form>
        <div class="footer">
            <a href="staff_dashboard.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
            <span style="color:#475569; font-size:13px;">Logged in as Staff</span>
        </div>
    </div>
</body>

</html>