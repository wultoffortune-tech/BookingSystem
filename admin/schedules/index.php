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

</head>
<link rel="stylesheet" href="../schedules/style.css">

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