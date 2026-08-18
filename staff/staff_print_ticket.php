<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'staff' && $_SESSION['user_role'] != 'admin')) {
    header("Location: ../login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT r.*, u.full_name, u.email, u.phone_number FROM reservation r JOIN users u ON r.passenger_id = u.user_id WHERE r.reservation_id = ?");
$stmt->execute([$id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    die("Ticket not found.");
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Print Ticket</title>
    <style>
        body {
            text-align: center;
            padding: 40px;
            font-family: 'Poppins', sans-serif;
            background: #0F172A;
            color: #E2E8F0;
        }

        .ticket-box {
            background: #1E293B;
            border: 2px dashed #38BDF8;
            padding: 30px;
            max-width: 320px;
            margin: 0 auto;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .logo {
            color: #38BDF8;
            font-size: 24px;
            font-weight: bold;
        }

        .code {
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 3px;
            background: #0F172A;
            padding: 10px;
            margin: 15px 0;
            border-radius: 5px;
            color: #FFFFFF;
            border: 1px solid rgba(255, 255, 255, 0.04);
        }

        hr {
            border: 0;
            border-top: 1px dashed rgba(255, 255, 255, 0.1);
            margin: 15px 0;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="ticket-box">
        <div class="logo">CamExpress</div>
        <div class="code"><?php echo $ticket['booking_code']; ?></div>
        <hr>
        <p><strong>Name:</strong> <?php echo $ticket['full_name']; ?></p>
        <p><strong>Email:</strong> <?php echo $ticket['email']; ?></p>
        <p><strong>Phone:</strong> <?php echo $ticket['phone_number']; ?></p>
        <p><strong>Seat:</strong> <?php echo $ticket['seat_number']; ?></p>
        <p><strong>Fare:</strong> XAF <?php echo number_format($ticket['fare_paid'], 0); ?></p>
        <hr>
        <small>Thank you for riding with CamExpress!</small>
    </div>
    <br>
    <button class="no-print" onclick="window.print()" style="background:#38BDF8; color:#0F172A; border:none; padding:10px 20px; border-radius:5px; cursor:pointer;">🖨️ Print</button>
</body>

</html>