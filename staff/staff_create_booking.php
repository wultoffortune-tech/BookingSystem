<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'staff' && $_SESSION['user_role'] != 'admin')) {
    header("Location: login.php");
    exit();
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ticket_code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 13));
    $stmt = $pdo->prepare("INSERT INTO bookings (customer_name, customer_phone, route, travel_date, seat_number, ticket_code, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");

    if ($stmt->execute([$_POST['customer_name'], $_POST['customer_phone'], $_POST['route'], $_POST['travel_date'], $_POST['seat_number'], $ticket_code, $_SESSION['user_id']])) {
        $message = "<div class='alert success'>✅ Booking Successful! Ticket Code: <strong>$ticket_code</strong></div>";
    } else {
        $message = "<div class='alert error'>❌ Error creating booking. Please try again.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assist Booking - CamExpress</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css">

    <style>
        /* 1. Beautiful Animated Gradient Background */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            background-size: 400% 400%;
            animation: gradientMove 15s ease-in-out infinite alternate;
        }

        @keyframes gradientMove {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        /* 2. Professional Classic Card */
        .form-container {
            background: rgba(255, 255, 255, 0.98);
            padding: 45px 50px;
            border-radius: 16px;
            max-width: 850px;
            width: 90%;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
            position: relative;
            overflow: hidden;
        }

        /* 3. Subtle Gold Accent Line on top */
        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, #c9a84c, #f3e5ab, #c9a84c);
        }

        /* 4. Header Styling */
        .form-header {
            text-align: center;
            margin-bottom: 35px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 20px;
        }

        .form-header h2 {
            margin: 0;
            color: #1e2a38;
            font-size: 28px;
            font-weight: 600;
        }

        .form-header h2 i {
            color: #c9a84c;
            margin-right: 10px;
        }

        .form-header p {
            color: #7a7a7a;
            margin-top: 8px;
            font-size: 14px;
        }

        /* 5. Styled Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 15px;
            display: flex;
            align-items: center;
        }

        .alert.success {
            background: #e8f5e9;
            color: #1b5e20;
            border: 1px solid #a5d6a7;
        }

        .alert.error {
            background: #ffebee;
            color: #b71c1c;
            border: 1px solid #ef9a9a;
        }

        /* 6. Two-Column Grid for inputs */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px 30px;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
            font-size: 14px;
        }

        .input-wrapper {
            position: relative;
        }

        /* 7. Beautiful Inputs with Icons */
        .input-wrapper input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            background: #f9fafb;
            transition: all 0.3s ease;
            box-sizing: border-box;
            color: #333;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: #2c5364;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(44, 83, 100, 0.1);
        }

        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0a0a0;
            font-size: 16px;
            transition: color 0.3s;
        }

        .input-wrapper input:focus+i {
            color: #2c5364;
        }

        input[type="date"] {
            color-scheme: light;
        }

        /* 9. Classic Gold Button */
        .btn-submit {
            background: #1e2a38;
            color: white;
            border: none;
            padding: 16px 20px;
            border-radius: 10px;
            cursor: pointer;
            width: 100%;
            font-weight: 600;
            font-size: 16px;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(30, 42, 56, 0.2);
            margin-top: 5px;
        }

        .btn-submit:hover {
            background: #2c5364;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(30, 42, 56, 0.3);
        }

        .btn-submit i {
            margin-right: 8px;
            color: #c9a84c;
        }

        /* 10. Footer Links */
        .form-footer {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #f0f0f0;
            padding-top: 20px;
        }

        .back-link {
            color: #666;
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }

        .back-link:hover {
            color: #2c5364;
            transform: translateX(-5px);
        }

        .staff-badge {
            background: #f0f0f0;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            color: #555;
            font-weight: 600;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .form-container {
                padding: 30px 25px;
                width: 95%;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .form-group.full-width {
                grid-column: span 1;
            }

            .form-footer {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <div class="form-container">

        <div class="form-header">
            <h2><i class="fa-solid fa-user-plus"></i> Assist in Booking</h2>
            <p>Enter customer details to generate a new travel ticket</p>
        </div>

        <?php echo $message; ?>

        <form method="POST">
            <div class="form-grid">
                <!-- Row 1: Name & Phone -->
                <div class="form-group">
                    <label>Customer Full Name</label>
                    <div class="input-wrapper">
                        <input type="text" name="customer_name" placeholder="wultof fortune" required>
                        <i class="fa-solid fa-user"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label>Customer Phone</label>
                    <div class="input-wrapper">
                        <input type="text" name="customer_phone" placeholder="+237 690253423" required>
                        <i class="fa-solid fa-phone"></i>
                    </div>
                </div>

                <!-- Row 2: Route & Seat -->
                <div class="form-group">
                    <label>Route</label>
                    <div class="input-wrapper">
                        <input type="text" name="route" placeholder="dschang, cameroon" required>
                        <i class="fa-solid fa-road"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label>Seat Number</label>
                    <div class="input-wrapper">
                        <input type="text" name="seat_number" placeholder="S12" required>
                        <i class="fa-solid fa-chair"></i>
                    </div>
                </div>

                <!-- Row 3: Date (Spans full width) -->
                <div class="form-group full-width">
                    <label>Travel Date</label>
                    <div class="input-wrapper">
                        <input type="date" name="travel_date" required>
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                </div>

                <!-- Button (Spans full width) -->
                <div class="form-group full-width">
                    <button type="submit" class="btn-submit"><i class="fa-solid fa-ticket"></i> Create Booking</button>
                </div>
            </div>
        </form>

        <div class="form-footer">
            <a href="staff_dashboard.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>

            <!-- THIS IS THE FIXED LINE 351 -->
            <div class="staff-badge"><i class="fa-regular fa-user-circle"></i> <?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'Staff'; ?></div>
        </div>

    </div>
</body>

</html>