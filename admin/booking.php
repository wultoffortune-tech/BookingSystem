<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check admin access
if (
    !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true ||
    !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'
) {
    header('Location: ../login/login.php');
    exit();
}

// Include database connection
require_once '../config/database.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'], $_POST['reservation_id'])) {
    $reservationToConfirm = (int)$_POST['reservation_id'];

    if ($reservationToConfirm > 0) {
        try {
            $pdo->beginTransaction();

            $bookingStmt = $pdo->prepare("SELECT fare_paid, status FROM reservation WHERE reservation_id = ?");
            $bookingStmt->execute([$reservationToConfirm]);
            $booking = $bookingStmt->fetch();

            if (!$booking) {
                throw new Exception('Selected booking was not found.');
            }

            if ($booking['status'] === 'confirmed') {
                $success = 'This booking is already confirmed.';
            } else {
                $paymentTable = $pdo->query("SHOW TABLES LIKE 'payment'")->fetch();
                $transactionRef = strtoupper(uniqid('ADM'));

                if ($paymentTable) {
                    $paymentStmt = $pdo->prepare("SELECT payment_id FROM payment WHERE reservation_id = ? AND payment_status <> 'completed' ORDER BY payment_id ASC LIMIT 1");
                    $paymentStmt->execute([$reservationToConfirm]);
                    $paymentRow = $paymentStmt->fetch();

                    if ($paymentRow) {
                        $updatePayment = $pdo->prepare("UPDATE payment SET payment_status = 'completed', payment_method = 'admin-confirmed', amount = ?, transaction_reference = ?, payment_date = NOW() WHERE payment_id = ?");
                        $updatePayment->execute([$booking['fare_paid'], $transactionRef, $paymentRow['payment_id']]);
                    } else {
                        $insertPayment = $pdo->prepare("INSERT INTO payment (reservation_id, payment_method, amount, payment_status, transaction_reference, payment_date) VALUES (?, 'admin-confirmed', ?, 'completed', ?, NOW())");
                        $insertPayment->execute([$reservationToConfirm, $booking['fare_paid'], $transactionRef]);
                    }
                }

                $updateReservation = $pdo->prepare("UPDATE reservation SET status = 'confirmed' WHERE reservation_id = ?");
                $updateReservation->execute([$reservationToConfirm]);
                $pdo->commit();

                $success = 'Payment has been confirmed successfully.';
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Could not confirm payment. Please try again.';
        }
    } else {
        $error = 'Invalid booking selected.';
    }

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'error' => $error]);
        exit();
    }
}

// Get all bookings
$stmt = $pdo->query("SELECT r.*, u.full_name, u.email, u.phone_number, 
                     rt.original_city, rt.destination, s.departure_time, s.arrival_time,
                     b.bus_name
                     FROM reservation r
                     JOIN users u ON r.passenger_id = u.user_id
                     JOIN schedule s ON r.schedule_id = s.schedule_id
                     JOIN route rt ON s.route_id = rt.route_id
                     JOIN bus b ON s.bus_id = b.bus_id
                     ORDER BY r.reservation_id DESC");
$bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - CamExpress Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
        }

        .admin-header {
            background: #1E293B;
            padding: 16px 32px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .admin-header .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            font-weight: 700;
            color: #38BDF8;
            text-decoration: none;
        }

        .admin-header .logo i {
            font-size: 24px;
        }

        .admin-header .admin-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .admin-header .admin-info span {
            color: #94A3B8;
            font-size: 14px;
        }

        .admin-header .admin-info .logout-btn {
            padding: 8px 20px;
            background: rgba(239, 68, 68, 0.06);
            color: #EF4444;
            border: 1px solid rgba(239, 68, 68, 0.06);
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .admin-header .admin-info .logout-btn:hover {
            background: rgba(239, 68, 68, 0.12);
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 24px;
        }

        .admin-container h1 {
            font-size: 26px;
            font-weight: 700;
            color: #FFFFFF;
            margin-bottom: 4px;
        }

        .admin-container .subtitle {
            color: #94A3B8;
            font-size: 14px;
            margin-bottom: 24px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            background: #1E293B;
            border-radius: 12px;
            overflow: hidden;
        }

        table th {
            text-align: left;
            padding: 12px 16px;
            color: #94A3B8;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }

        table td {
            padding: 12px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.02);
        }

        table tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-pending {
            background: rgba(245, 158, 11, 0.06);
            color: #F59E0B;
        }

        .badge-confirmed {
            background: rgba(52, 211, 153, 0.06);
            color: #34D399;
        }

        .badge-cancelled {
            background: rgba(239, 68, 68, 0.06);
            color: #EF4444;
        }

        .badge-completed {
            background: rgba(56, 189, 248, 0.06);
            color: #38BDF8;
        }

        .btn-back {
            display: inline-block;
            padding: 10px 20px;
            background: #1E293B;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            border: 1px solid rgba(255, 255, 255, 0.04);
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: #334155;
        }

        .btn-confirm {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 14px;
            background: #22c55e;
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-confirm:hover {
            background: #16a34a;
        }

        .message {
            border-radius: 16px;
            padding: 18px 22px;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .message.success {
            background: rgba(52, 211, 153, 0.08);
            color: #bbf7d0;
            border: 1px solid rgba(52, 211, 153, 0.18);
        }

        .message.error {
            background: rgba(239, 68, 68, 0.1);
            color: #fecaca;
            border: 1px solid rgba(239, 68, 68, 0.18);
        }

        .route-text {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .route-text i {
            color: #475569;
            font-size: 10px;
        }

        @media (max-width: 768px) {
            .admin-header {
                padding: 12px 16px;
                flex-wrap: wrap;
                gap: 10px;
            }

            .admin-container {
                padding: 20px 16px;
            }

            table {
                font-size: 13px;
            }

            table th,
            table td {
                padding: 8px 10px;
            }
        }
    </style>
</head>


<body>

    <header class="admin-header">
        <a href="dashboard.php" class="logo">
            <i class="fas fa-bus"></i>
            <span>CamExpress Admin</span>
        </a>
        <div class="admin-info">
            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars(isset($_SESSION['user_name']) && $_SESSION['user_name'] !== '' ? $_SESSION['user_name'] : (isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Admin')); ?></span>
            <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <div class="admin-container">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
            <div>
                <h1>Manage Bookings</h1>
                <p class="subtitle">View and manage all customer bookings</p>
            </div>
            <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <div id="admin-message" class="message <?php echo $success ? 'success' : 'error'; ?>" style="display: <?php echo ($success || $error) ? 'block' : 'none'; ?>;">
            <?php echo htmlspecialchars($success ?: $error); ?>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Passenger</th>
                        <th>Route</th>
                        <th>Bus</th>
                        <th>Seat</th>
                        <th>Fare</th>
                        <th>Status</th>
                        <th>Action</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($bookings) > 0): ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td>#<?php echo $booking['reservation_id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($booking['full_name']); ?></strong>
                                    <span style="display: block; font-size: 12px; color: #94A3B8;"><?php echo htmlspecialchars($booking['email']); ?></span>
                                </td>
                                <td>
                                    <div class="route-text">
                                        <?php echo htmlspecialchars($booking['original_city']); ?>
                                        <i class="fas fa-arrow-right"></i>
                                        <?php echo htmlspecialchars($booking['destination']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($booking['bus_name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['seat_number']); ?></td>
                                <td>XAF <?php echo number_format($booking['fare_paid'], 0); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo isset($booking['status']) ? $booking['status'] : 'pending'; ?>">
                                        <?php echo ucfirst(isset($booking['status']) ? $booking['status'] : 'Pending'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php $bookingStatus = isset($booking['status']) ? $booking['status'] : 'pending'; ?>
                                    <?php if ($bookingStatus !== 'confirmed'): ?>
                                        <form class="confirm-payment-form" method="POST" action="booking.php">
                                            <input type="hidden" name="reservation_id" value="<?php echo $booking['reservation_id']; ?>">
                                            <input type="hidden" name="confirm_payment" value="1">
                                            <button type="submit" class="btn-confirm">Confirm</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color:#34D399; font-weight:600;">Confirmed</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime(isset($booking['reservation_date']) ? $booking['reservation_date'] : 'now')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: #94A3B8;">No bookings found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('.confirm-payment-form');
            const messageBox = document.getElementById('admin-message');

            forms.forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    event.preventDefault();

                    const formData = new window.FormData(form);
                    const reservationId = formData.get('reservation_id');
                    const button = form.querySelector('button');

                    button.disabled = true;
                    button.textContent = 'Confirming...';

                    fetch('booking.php', {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                messageBox.className = 'message success';
                                messageBox.textContent = data.success;
                                messageBox.style.display = 'block';

                                const row = form.closest('tr');
                                const statusCell = row.querySelector('td:nth-child(7) span');
                                const actionCell = row.querySelector('td:nth-child(8)');

                                if (statusCell) {
                                    statusCell.className = 'badge badge-confirmed';
                                    statusCell.textContent = 'Confirmed';
                                }

                                if (actionCell) {
                                    actionCell.innerHTML = '<span style="color:#34D399; font-weight:600;">Confirmed</span>';
                                }
                            } else {
                                messageBox.className = 'message error';
                                messageBox.textContent = data.error || 'Unable to confirm payment.';
                                messageBox.style.display = 'block';
                            }
                        })
                        .catch(() => {
                            messageBox.className = 'message error';
                            messageBox.textContent = 'Unable to confirm payment. Please try again.';
                            messageBox.style.display = 'block';
                        })
                        .finally(() => {
                            button.disabled = false;
                            button.textContent = 'Confirm';
                        });
                });
            });
        });
    </script>

</body>

</html>