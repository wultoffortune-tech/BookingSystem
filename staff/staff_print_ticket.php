<?php
session_start();
require_once '../config/database.php';

// Note: We allow staff to access, but also passengers can print here if verified.
if (!isset($_SESSION['user_role'])) {
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? $_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
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
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 40px;
            background: #f4f7f6;
        }

        .ticket-box {
            background: white;
            border: 2px dashed #333;
            padding: 30px;
            max-width: 320px;
            margin: 0 auto;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .logo {
            color: #08c0e0;
            font-size: 24px;
            font-weight: bold;
        }

        .code {
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 3px;
            background: #eee;
            padding: 10px;
            margin: 15px 0;
            border-radius: 5px;
        }

        hr {
            border: 0;
            border-top: 1px dashed #ccc;
            margin: 15px 0;
        }

        .btn-print {
            margin-top: 20px;
            padding: 10px 20px;
            background: #08c0e0;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
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
        <h2>CamExpress</h2>
        <div class="code"><?php echo $ticket['ticket_code']; ?></div>
        <hr>
        <p><strong>Name:</strong> <?php echo $ticket['customer_name']; ?></p>
        <p><strong>Route:</strong> <?php echo $ticket['route']; ?></p>
        <p><strong>Date:</strong> <?php echo $ticket['travel_date']; ?></p>
        <p><strong>Seat:</strong> <?php echo $ticket['seat_number']; ?></p>
    </div>
    <br>
    <button class="no-print" onclick="window.print()">🖨️ Print</button>
</body>

</html>