<?php
session_start();
require_once 'db_connect.php';

$ticket_found = null;
$error = "";

if (isset($_POST['ticket_code'])) {
    $code = strtoupper(trim($_POST['ticket_code']));
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE ticket_code = ?");
    $stmt->execute([$code]);
    $ticket_found = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket_found) $error = "❌ No booking found with that code.";
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>My Bookings - CamExpress</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            padding: 40px;
            background: #f4f7f6;
            display: flex;
            justify-content: center;
            font-family: Arial, sans-serif;
        }

        .container {
            max-width: 600px;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        input {
            padding: 12px;
            width: 70%;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-right: 10px;
        }

        button {
            padding: 12px 20px;
            background: #0072ff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .ticket-box {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: left;
            border: 1px solid #ddd;
        }

        .error {
            color: red;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Check My Booking</h2>
        <p>Enter your 8-digit ticket code given to you by staff.</p>
        <form method="POST">
            <input type="text" name="ticket_code" placeholder="Enter Ticket Code" required>
            <button type="submit">Search</button>
        </form>

        <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>

        <?php if ($ticket_found): ?>
            <div class="ticket-box">
                <h3 style="color: #0072ff;">✅ Ticket Found</h3>
                <p><strong>Passenger:</strong> <?php echo $ticket_found['customer_name']; ?></p>
                <p><strong>Route:</strong> <?php echo $ticket_found['route']; ?></p>
                <p><strong>Date:</strong> <?php echo $ticket_found['travel_date']; ?></p>
                <p><strong>Seat:</strong> <?php echo $ticket_found['seat_number']; ?></p>
                <p><strong>Status:</strong> <span style="color: green; font-weight: bold;"><?php echo ucfirst($ticket_found['status']); ?></span></p>

                <!-- Link to print the ticket -->
                <a href="staff/staff_print_ticket.php?id=<?php echo $ticket_found['id']; ?>" target="_blank" style="display:inline-block; margin-top:10px; background:#28a745; color:white; padding:10px 15px; text-decoration:none; border-radius:5px;">
                    <i class="fa-solid fa-print"></i> Print Ticket
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>