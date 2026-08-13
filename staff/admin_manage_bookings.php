<?php
session_start();
require_once '../config/database.php';

// Only Admin can access this (optional: check if user_role is 'admin')
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch ALL bookings
$stmt = $pdo->query("SELECT * FROM bookings ORDER BY created_at DESC");
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>

<head>
    <title>Manage All Bookings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css">
    <style>
        body {
            background: #f4f7f6;
            padding: 30px;
            font-family: Arial, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #1e2a38;
            color: white;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-active {
            background: #d4edda;
            color: #155724;
        }

        .badge-used {
            background: #cce5ff;
            color: #004085;
        }

        .badge-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #555;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <a href="staff_dashboard.php" class="back-link">← Back to Dashboard</a>
    <h2>All System Bookings</h2>

    <table>
        <thead>
            <tr>
                <th>Ticket Code</th>
                <th>Passenger</th>
              
                <th>Route</th>
                <th>Date</th>
                <th>Seat</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($bookings) > 0): ?>
                <?php foreach ($bookings as $book): ?>
                    <tr>
                        <td><strong><?php echo $book['ticket_code']; ?></strong></td>
                        <td><?php echo $book['customer_name']; ?></td>
                        
                        <td><?php echo $book['route']; ?></td>
                        <td><?php echo $book['travel_date']; ?></td>
                        <td><?php echo $book['seat_number']; ?></td>
                        <td>
                            <span class="badge badge-<?php echo $book['status']; ?>">
                                <?php echo ucfirst($book['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="staff_print_ticket.php?id=<?php echo $book['id']; ?>" target="_blank">🖨️ Print</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:20px;">No bookings have been made yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>