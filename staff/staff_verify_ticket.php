<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'staff' && $_SESSION['user_role'] != 'admin')) {
    header("Location: login.php");
    exit();
}

$ticket_data = null;
$error = "";

if (isset($_POST['ticket_code'])) {
    $code = strtoupper(trim($_POST['ticket_code']));
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE ticket_code = ?");
    $stmt->execute([$code]);
    $ticket_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket_data) $error = "❌ Invalid Ticket Code.";
    elseif ($ticket_data['status'] !== 'active') $error = "Ticket is already " . $ticket_data['status'] . ".";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Ticket - CamExpress</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css">

    <style>
        /* 1. Matte Solid Background with a subtle pattern */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f2f5f9;
            /* Clean matte grey-blue */
            position: relative;
        }

        /* 2. Clean Solid Card with a Pop of Color Shadow */
        .container {
            background: #ffffff;
            padding: 45px 50px;
            border-radius: 20px;
            max-width: 750px;
            width: 90%;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.06), 0 5px 15px rgba(0, 0, 0, 0.03);
            position: relative;
            z-index: 1;
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        /* 3. Clean Header */
        .header {
            text-align: left;
            margin-bottom: 35px;
        }

        .header h2 {
            margin: 0;
            color: #111827;
            font-size: 26px;
            font-weight: 700;
        }

        .header h2 i {
            color: #3b82f6;
            /* Bright Blue */
            margin-right: 12px;
        }

        .header p {
            color: #6b7280;
            margin-top: 6px;
            font-size: 14px;
        }

        /* 4. Modern Split Search Bar */
        .search-box {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }

        .search-box input {
            flex: 1;
            padding: 16px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            background: #f9fafb;
            transition: all 0.2s ease;
            outline: none;
            color: #1f2937;
            font-weight: 500;
        }

        .search-box input:focus {
            border-color: #3b82f6;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
        }

        .search-box input::placeholder {
            color: #9ca3af;
            font-weight: 400;
        }

        .search-box button {
            padding: 16px 30px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.2s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-box button:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        /* 5. Minimalist Error */
        .error-msg {
            color: #b91c1c;
            background: #fef2f2;
            padding: 14px 18px;
            border-radius: 12px;
            margin-top: 15px;
            font-weight: 500;
            border: 1px solid #fecaca;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* 6. Professional Ticket Card (Like a real printed stub) */
        .ticket-card {
            padding: 25px;
            border-radius: 16px;
            margin-top: 25px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            position: relative;
        }

        /* Decorative perforated line */
        .ticket-card::before {
            content: '';
            position: absolute;
            top: -6px;
            left: 0;
            width: 100%;
            height: 6px;
            background: repeating-linear-gradient(90deg, transparent, transparent 10px, #e5e7eb 10px, #e5e7eb 12px);
        }

        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f3f4f6;
        }

        .ticket-header h3 {
            margin: 0;
            color: #111827;
            font-size: 18px;
            font-weight: 600;
        }

        .status-active {
            color: #065f46;
            background: #d1fae5;
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
        }

        /* 7. Crisp Ticket Grid */
        .ticket-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px 30px;
        }

        .ticket-item {
            display: flex;
            flex-direction: column;
        }

        .ticket-item strong {
            color: #6b7280;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .ticket-item span {
            color: #111827;
            font-weight: 500;
            font-size: 17px;
            margin-top: 4px;
        }

        .ticket-code-display {
            color: #3b82f6 !important;
            font-weight: 700 !important;
            font-size: 18px !important;
            letter-spacing: 1px;
        }

        /* 8. Modern Floating Buttons */
        .action-row {
            margin-top: 25px;
            border-top: 2px solid #f3f4f6;
            padding-top: 20px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-print {
            background: #10b981;
            /* Emerald Green */
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: 0.2s;
            flex: 1;
            justify-content: center;
        }

        .btn-print:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-mark {
            background: #f59e0b;
            /* Amber Orange */
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: 0.2s;
            flex: 1;
            justify-content: center;
        }

        .btn-mark:hover {
            background: #d97706;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        /* 9. Clean Footer */
        .back-link {
            display: block;
            margin-top: 30px;
            color: #6b7280;
            text-decoration: none;
            text-align: center;
            font-weight: 500;
            font-size: 14px;
            transition: 0.2s;
        }

        .back-link:hover {
            color: #3b82f6;
        }

        /* Mobile Responsiveness */
        @media (max-width: 600px) {
            .container {
                padding: 25px;
            }

            .search-box {
                flex-direction: column;
            }

            .search-box button {
                width: 100%;
                justify-content: center;
            }

            .ticket-grid {
                grid-template-columns: 1fr;
            }

            .action-row {
                flex-direction: column;
            }

            .btn-print,
            .btn-mark {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="container">

        <div class="header">
            <h2><i class="fa-solid fa-magnifying-glass"></i> Verify Ticket</h2>
            <p>Enter the ticket code to validate a passenger's booking</p>
        </div>

        <form method="POST">
            <div class="search-box">
                <input type="text" name="ticket_code" placeholder="e.g. CE202608B89A6" required>
                <button type="submit"><i class="fa-solid fa-arrow-right-to-bracket"></i> Verify</button>
            </div>
        </form>

        <?php if ($error): ?>
            <div class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($ticket_data): ?>
            <div class="ticket-card">
                <div class="ticket-header">
                    <h3>Booking Details</h3>
                    <span class="status-active"><i class="fa-regular fa-circle-check"></i> Active</span>
                </div>

                <div class="ticket-grid">
                    <div class="ticket-item">
                        <strong>Ticket Code</strong>
                        <span class="ticket-code-display"><?php echo $ticket_data['ticket_code']; ?></span>
                    </div>
                    <div class="ticket-item">
                        <strong>Passenger</strong>
                        <span><?php echo $ticket_data['customer_name']; ?></span>
                    </div>



                    <div class="ticket-item">
                        <strong>Phone</strong>
                        <span><?php echo $ticket_data['customer_phone']; ?></span>
                    </div>
                    <div class="ticket-item">
                        <strong>Route</strong>
                        <span><?php echo $ticket_data['route']; ?></span>
                    </div>
                    <div class="ticket-item">
                        <strong>Date</strong>
                        <span><?php echo $ticket_data['travel_date']; ?></span>
                    </div>
                    <div class="ticket-item">
                        <strong>Seat</strong>
                        <span><?php echo $ticket_data['seat_number']; ?></span>
                    </div>


                </div>

                <div class="action-row">
                    <a href="staff_print_ticket.php?id=<?php echo $ticket_data['id']; ?>" target="_blank" class="btn-print">
                        <i class="fa-solid fa-print"></i> Print Ticket
                    </a>
                    <form method="POST" action="staff_mark_used.php" style="margin: 0; flex: 1;">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket_data['id']; ?>">
                        <button type="submit" class="btn-mark">
                            <i class="fa-solid fa-check"></i> Mark Used
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <a href="staff_dashboard.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>

</html>