<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'staff' && $_SESSION['user_role'] != 'admin')) {
    header("Location: ../login.php");
    exit();
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ticket_code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
    $stmt = $pdo->prepare("INSERT INTO bookings (customer_name, customer_phone, route, travel_date, seat_number, ticket_code, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");

    if ($stmt->execute([$_POST['customer_name'], $_POST['customer_phone'], $_POST['route'], $_POST['travel_date'], $_POST['seat_number'], $ticket_code, $_SESSION['user_id']])) {
        $message = "<div style='background:rgba(52,211,153,0.06); color:#34D399; padding:15px; border-radius:10px; border:1px solid rgba(52,211,153,0.1); margin-bottom:20px;'>✅ Booking Successful! Ticket Code: <strong>$ticket_code</strong></div>";
    } else {
        $message = "<div style='background:rgba(239,68,68,0.06); color:#EF4444; padding:15px; border-radius:10px; border:1px solid rgba(239,68,68,0.1); margin-bottom:20px;'>❌ Error creating booking.</div>";
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

        .form-group input {
            padding: 14px 16px;
            background: #0F172A;
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 8px;
            color: #FFFFFF;
            font-size: 15px;
            width: 100%;
            outline: none;
            transition: 0.3s;
        }

        .form-group input:focus {
            border-color: #38BDF8;
            background: #0F172A;
        }

        .form-group input::placeholder {
            color: #475569;
        }

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
            <p>Enter passenger details to generate a new ticket</p>
        </div>
        <?php echo $message; ?>
        <form method="POST">
            <div class="form-grid">
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
                </div>
            </div>
        </form>
        <div class="footer">
            <a href="staff_dashboard.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
            <span style="color:#475569; font-size:13px;">Logged in as Staff</span>
        </div>
    </div>
</body>

</html>