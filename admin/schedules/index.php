<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header('Location: ../../login/login.php');
//     exit();
// }

// Include database connection
require_once '../../config/database.php';

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $schedule_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM schedule WHERE schedule_id = ?");
    $stmt->execute([$schedule_id]);
    header('Location: index.php?deleted=1');
    exit();
}

// Get all schedules with route and bus information
$sql = "SELECT s.*, 
        r.original_city, r.destination, r.base_fare,
        b.bus_name, b.bus_type, b.total_seats
        FROM schedule s
        JOIN route r ON s.route_id = r.route_id
        JOIN bus b ON s.bus_id = b.bus_id
        ORDER BY s.departure_time ASC";

$stmt = $pdo->query($sql);
$schedules = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedules - CamExpress Admin</title>
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
            color: #FFFFFF;
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
            background: rgba(239, 68, 68, 0.10);
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 24px;
        }

        .admin-header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .admin-header-section h1 {
            font-size: 26px;
            font-weight: 700;
        }

        .admin-header-section .subtitle {
            color: #94A3B8;
            font-size: 14px;
        }

        .btn-add {
            padding: 10px 24px;
            background: #38BDF8;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add:hover {
            background: #0EA5E9;
            transform: translateY(-2px);
        }

        .btn-back {
            padding: 10px 20px;
            background: #1E293B;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255, 255, 255, 0.04);
        }

        .btn-back:hover {
            background: #334155;
        }

        .table-responsive {
            overflow-x: auto;
            background: #1E293B;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.02);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        table th {
            text-align: left;
            padding: 14px 16px;
            color: #94A3B8;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }

        table td {
            padding: 14px 16px;
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

        .badge-active {
            background: rgba(56, 189, 248, 0.06);
            color: #38BDF8;
        }

        .badge-expired {
            background: rgba(239, 68, 68, 0.06);
            color: #EF4444;
        }

        .badge-available {
            background: rgba(52, 211, 153, 0.06);
            color: #34D399;
        }

        .badge-full {
            background: rgba(239, 68, 68, 0.06);
            color: #EF4444;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .actions a {
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .actions .edit {
            background: rgba(56, 189, 248, 0.06);
            color: #38BDF8;
        }

        .actions .edit:hover {
            background: rgba(56, 189, 248, 0.12);
        }

        .actions .delete {
            background: rgba(239, 68, 68, 0.06);
            color: #EF4444;
        }

        .actions .delete:hover {
            background: rgba(239, 68, 68, 0.12);
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #94A3B8;
        }

        .no-results i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .alert-success {
            background: rgba(52, 211, 153, 0.06);
            border: 1px solid rgba(52, 211, 153, 0.06);
            color: #34D399;
        }

        .alert-success i {
            margin-right: 8px;
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

            .admin-header-section {
                flex-direction: column;
                align-items: flex-start;
            }

            .table-responsive {
                font-size: 13px;
            }

            table th,
            table td {
                padding: 10px 12px;
            }
        }
    </style>
</head>

<body>

    <header class="admin-header">
        <a href="../dashboard.php" class="logo">
            <i class="fas fa-bus"></i>
            <span>CamExpress Admin</span>
        </a>
        <div class="admin-info">
            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars(isset($_SESSION['admin_name']) && $_SESSION['admin_name'] !== '' ? $_SESSION['admin_name'] : 'Admin'); ?></span>
            <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <div class="admin-container">

        <div class="admin-header-section">
            <div>
                <h1>Manage Schedules</h1>
                <p class="subtitle">View and manage all bus schedules</p>
            </div>
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <a href="create.php" class="btn-add"><i class="fas fa-plus"></i> Add Schedule</a>
                <a href="../dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Schedule deleted successfully!
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <?php if (count($schedules) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Route</th>
                            <th>Bus</th>
                            <th>Departure</th>
                            <th>Arrival</th>
                            <th>Seats</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $schedule): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($schedule['original_city']); ?></strong>
                                    <i class="fas fa-arrow-right" style="color: #475569; font-size: 12px; margin: 0 4px;"></i>
                                    <strong><?php echo htmlspecialchars($schedule['destination']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($schedule['bus_name']); ?>
                                    <span style="color: #64748B; font-size: 12px; display: block;">
                                        <?php echo htmlspecialchars($schedule['bus_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($schedule['departure_time'])); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($schedule['arrival_time'])); ?></td>
                                <td>
                                    <span class="badge <?php echo $schedule['available_seats'] > 0 ? 'badge-available' : 'badge-full'; ?>">
                                        <?php echo $schedule['available_seats']; ?>/<?php echo $schedule['total_seats']; ?>
                                    </span>
                                </td>
                                <td>XAF <?php echo number_format($schedule['price'] ?: $schedule['base_fare'], 0); ?></td>
                                <td>
                                    <?php
                                    $isExpired = ($schedule['expired'] == 1 || strtotime($schedule['departure_time']) <= time());
                                    ?>
                                    <span class="badge <?php echo $isExpired ? 'badge-expired' : 'badge-active'; ?>">
                                        <?php echo $isExpired ? 'Expired' : 'Active'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="edit.php?id=<?php echo $schedule['schedule_id']; ?>" class="edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete=<?php echo $schedule['schedule_id']; ?>"
                                            class="delete"
                                            onclick="return confirm('Are you sure you want to delete this schedule?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-calendar-times"></i>
                    <p>No schedules found. Click "Add Schedule" to create one.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

</body>

</html>