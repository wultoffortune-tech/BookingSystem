<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'staff' && $_SESSION['user_role'] != 'admin')) {
    header("Location: ../login.php");
    exit();
}

$ticket_data = null;
$error = "";

if (isset($_POST['booking_code'])) {
    $code = trim($_POST['booking_code']);
    $stmt = $pdo->prepare("SELECT r.*, u.full_name, u.email, u.phone_number FROM reservation r JOIN users u ON r.passenger_id = u.user_id WHERE r.booking_code = ?");
    $stmt->execute([$code]);
    $ticket_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket_data) $error = "❌ Invalid Ticket Code.";
    elseif ($ticket_data['status'] !== 'confirmed') $error = "Ticket status is " . $ticket_data['status'] . ".";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Verify Ticket - CamExpress</title>
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

        .container {
            background: #1E293B;
            padding: 40px;
            border-radius: 12px;
            max-width: 750px;
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.04);
        }

        .header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            padding-bottom: 20px;
            margin-bottom: 25px;
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

        .search-box {
            display: flex;
            gap: 12px;
        }

        .search-box input {
            flex: 1;
            padding: 14px 16px;
            background: #0F172A;
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 8px;
            color: #FFFFFF;
<<<<<<< HEAD
=======
            font-size: 15px;
>>>>>>> 278447dbacb8319f2c179d04ccc94166e0027099
            outline: none;
            transition: 0.3s;
        }

        .search-box input:focus {
            border-color: #38BDF8;
        }

        .search-box button {
            padding: 14px 24px;
            background: #38BDF8;
            color: #0F172A;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .search-box button:hover {
            background: #0EA5E9;
            transform: translateY(-2px);
        }

        .error-msg {
            background: rgba(239, 68, 68, 0.06);
            color: #EF4444;
            padding: 14px;
            border-radius: 8px;
            margin-top: 20px;
            border: 1px solid rgba(239, 68, 68, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }

<<<<<<< HEAD
        .ticket-card {
            background: #0F172A;
            padding: 25px;
            border-radius: 8px;
=======
        /* Ticket Found Card */
        .ticket-card {
            background: #0F172A;
            padding: 25px;
            border-radius: 10px;
>>>>>>> 278447dbacb8319f2c179d04ccc94166e0027099
            margin-top: 25px;
            border: 1px solid rgba(255, 255, 255, 0.04);
        }

        .ticket-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .ticket-header h3 {
            margin: 0;
            color: #FFFFFF;
        }

        .status-active {
            background: rgba(52, 211, 153, 0.06);
            color: #34D399;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            border: 1px solid rgba(52, 211, 153, 0.1);
        }

        .ticket-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .ticket-item {
            display: flex;
            flex-direction: column;
        }

        .ticket-item strong {
            color: #94A3B8;
            font-size: 12px;
            text-transform: uppercase;
        }

        .ticket-item span {
            color: #FFFFFF;
            font-weight: 500;
            font-size: 16px;
            margin-top: 2px;
        }

        .ticket-code {
            color: #38BDF8 !important;
            font-weight: 700 !important;
            font-size: 18px !important;
        }

        .action-row {
            margin-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.04);
            padding-top: 20px;
            display: flex;
            gap: 12px;
        }

        .btn-print {
<<<<<<< HEAD
            background: #10B981;
=======
            background: #34D399;
>>>>>>> 278447dbacb8319f2c179d04ccc94166e0027099
            color: #0F172A;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            flex: 1;
            text-align: center;
            transition: 0.2s;
        }

        .btn-print:hover {
            background: #10B981;
            transform: translateY(-2px);
        }

        .btn-mark {
            background: #F59E0B;
            color: #0F172A;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            flex: 1;
            transition: 0.2s;
        }

        .btn-mark:hover {
            background: #D97706;
            transform: translateY(-2px);
        }

        .footer {
            margin-top: 25px;
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

        @media (max-width: 600px) {
            .search-box {
                flex-direction: column;
            }

            .ticket-grid {
                grid-template-columns: 1fr;
            }

            .action-row {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2><i class="fa-solid fa-check-circle"></i> Verify Ticket</h2>
            <p>Enter the 8-digit code to validate a passenger's booking</p>
        </div>
        <form method="POST">
            <div class="search-box">
<<<<<<< HEAD
                <input type="text" name="booking_code" placeholder="e.g. 80451233" required>
=======
                <input type="text" name="ticket_code" placeholder="e.g. 710F483250316" required>
>>>>>>> 278447dbacb8319f2c179d04ccc94166e0027099
                <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Verify</button>
            </div>
        </form>
        <?php if ($error): ?><div class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?></div><?php endif; ?>
<<<<<<< HEAD
=======

>>>>>>> 278447dbacb8319f2c179d04ccc94166e0027099
        <?php if ($ticket_data): ?>
            <div class="ticket-card">
                <div class="ticket-header">
                    <h3>Booking Details</h3><span class="status-active"><i class="fa-regular fa-circle-check"></i> <?php echo ucfirst($ticket_data['status']); ?></span>
                </div>
                <div class="ticket-grid">
<<<<<<< HEAD
                    <div class="ticket-item"><strong>Code</strong><span class="ticket-code"><?php echo $ticket_data['booking_code']; ?></span></div>
                    <div class="ticket-item"><strong>Passenger</strong><span><?php echo $ticket_data['full_name']; ?></span></div>
                    <div class="ticket-item"><strong>Email</strong><span><?php echo $ticket_data['email']; ?></span></div>
                    <div class="ticket-item"><strong>Phone</strong><span><?php echo $ticket_data['phone_number']; ?></span></div>
                    <div class="ticket-item"><strong>Seat</strong><span><?php echo $ticket_data['seat_number']; ?></span></div>
                    <div class="ticket-item"><strong>Fare</strong><span>XAF <?php echo number_format($ticket_data['fare_paid'], 0); ?></span></div>
                </div>
                <div class="action-row">
                    <a href="staff_print_ticket.php?id=<?php echo $ticket_data['reservation_id']; ?>" target="_blank" class="btn-print"><i class="fa-solid fa-print"></i> Print Ticket</a>
                </div>
            </div>
        <?php endif; ?>
        <div class="footer"><a href="staff_dashboard.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Dashboard</a></div>
=======
                    <div class="ticket-item"><strong>Code</strong><span class="ticket-code"><?php echo $ticket_data['ticket_code']; ?></span></div>
                    <div class="ticket-item"><strong>Passenger</strong><span><?php echo $ticket_data['customer_name']; ?></span></div>
                    <div class="ticket-item"><strong>Phone</strong><span><?php echo $ticket_data['customer_phone']; ?></span></div>
                    <div class="ticket-item"><strong>Route</strong><span><?php echo $ticket_data['route']; ?></span></div>
                    <div class="ticket-item"><strong>Date</strong><span><?php echo $ticket_data['travel_date']; ?></span></div>
                    <div class="ticket-item"><strong>Seat</strong><span><?php echo $ticket_data['seat_number']; ?></span></div>
                </div>
                <div class="action-row">
                    <a href="staff_print_ticket.php?id=<?php echo $ticket_data['id']; ?>" target="_blank" class="btn-print"><i class="fa-solid fa-print"></i> Print Ticket</a>
                    <form method="POST" action="staff_mark_used.php" style="flex:1;">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket_data['id']; ?>">
                        <button type="submit" class="btn-mark"><i class="fa-solid fa-check"></i> Mark Used</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        <div class="footer">
            <a href="staff_dashboard.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
        </div>
>>>>>>> 278447dbacb8319f2c179d04ccc94166e0027099
    </div>
</body>

</html>