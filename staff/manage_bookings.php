<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'staff' && $_SESSION['user_role'] != 'admin')) {
    header("Location: ../login/login.php");
    exit();
}

$staff_id = isset($_SESSION['staff_id']) ? $_SESSION['staff_id'] : 0;
$error = '';
$success = '';

// ========================================
// HANDLE CANCELLATION - Cancel booking and release seat
// ========================================
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $reservation_id = (int)$_GET['cancel'];
    $reason = isset($_GET['reason']) ? trim($_GET['reason']) : 'Cancelled by staff';

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT reservation_id, schedule_id, seat_number, status, seats_released, passenger_id 
                               FROM reservation WHERE reservation_id = ? FOR UPDATE");
        $stmt->execute([$reservation_id]);
        $reservation = $stmt->fetch();

        if ($reservation) {
            if ($reservation['status'] == 'cancelled') {
                $error = '⚠️ This booking is already cancelled.';
            } elseif ($reservation['status'] == 'used') {
                $error = '❌ This ticket has already been used and cannot be cancelled.';
            } else {
                $stmt = $pdo->prepare("UPDATE reservation 
                                      SET status = 'cancelled', 
                                          seats_released = 1
                                      WHERE reservation_id = ?");
                $stmt->execute([$reservation_id]);

                if ($reservation['seats_released'] == 0 || $reservation['seats_released'] == null) {
                    $stmt = $pdo->prepare("UPDATE schedule SET available_seats = available_seats + 1 WHERE schedule_id = ?");
                    $stmt->execute([$reservation['schedule_id']]);
                }

                $pdo->commit();
                $success = '✅ Booking cancelled successfully! Seat has been released.';
            }
        } else {
            $error = '❌ Booking not found.';
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Cancellation error: " . $e->getMessage());
        $error = '❌ Unable to cancel the booking. Please try again.';
    }
}

// ========================================
// HANDLE DELETE - Permanently delete cancelled ticket
// ========================================
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $reservation_id = (int)$_GET['delete'];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT r.*, u.full_name, u.email, 
                               rt.original_city, rt.destination
                               FROM reservation r
                               JOIN users u ON r.passenger_id = u.user_id
                               JOIN schedule s ON r.schedule_id = s.schedule_id
                               JOIN route rt ON s.route_id = rt.route_id
                               WHERE r.reservation_id = ?");
        $stmt->execute([$reservation_id]);
        $reservation = $stmt->fetch();

        if ($reservation && $reservation['status'] == 'cancelled') {
            $stmt = $pdo->prepare("DELETE FROM reservation WHERE reservation_id = ?");
            $stmt->execute([$reservation_id]);

            $pdo->commit();
            $success = '🗑️ Ticket has been deleted permanently.';
        } else {
            $error = '❌ This ticket cannot be deleted. It must be cancelled first.';
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Delete error: " . $e->getMessage());
        $error = '❌ Unable to delete the ticket. Please try again.';
    }
}






// ========================================
// HANDLE MARK AS USED - Redeem a confirmed ticket at the counter
// ========================================
if (isset($_GET['mark_used']) && is_numeric($_GET['mark_used'])) {
    $reservation_id = (int)$_GET['mark_used'];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT reservation_id, status FROM reservation WHERE reservation_id = ? FOR UPDATE");
        $stmt->execute([$reservation_id]);
        $reservation = $stmt->fetch();

        if ($reservation) {
            if ($reservation['status'] == 'used') {
                $pdo->rollBack();
                $error = '⚠️ This ticket has already been marked as used.';
            } elseif ($reservation['status'] != 'confirmed') {
                $pdo->rollBack();
                $error = '❌ Only confirmed tickets can be marked as used.';
            } else {
                $stmt = $pdo->prepare("UPDATE reservation SET status = 'used' WHERE reservation_id = ?");
                $stmt->execute([$reservation_id]);
                $pdo->commit();
                $success = '✅ Ticket marked as used.';
            }
        } else {
            $pdo->rollBack();
            $error = '❌ Booking not found.';
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Mark used error: " . $e->getMessage());
        $error = '❌ Unable to update the ticket. Please try again.';
    }
}





// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$sql = "SELECT r.*, u.full_name, u.email, u.phone_number,
        s.departure_time, s.arrival_time,
        rt.original_city, rt.destination,
        b.bus_name, b.bus_type
        FROM reservation r
        JOIN users u ON r.passenger_id = u.user_id
        JOIN schedule s ON r.schedule_id = s.schedule_id
        JOIN route rt ON s.route_id = rt.route_id
        JOIN bus b ON s.bus_id = b.bus_id
        WHERE 1=1";

$params = array();

if ($status_filter != 'all') {
    $sql .= " AND r.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR r.booking_code LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$sql .= " ORDER BY r.reservation_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Get counts for stats
$stmt = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
    SUM(CASE WHEN status = 'used' THEN 1 ELSE 0 END) as used
    FROM reservation");
$stats = $stmt->fetch();

$total_bookings = count($bookings);

include '../includes/header.php';
?>

<style>
    :root {
        --primary-blue: #2563EB;
        --primary-blue-light: #3B82F6;
        --primary-blue-dark: #1D4ED8;
        --primary-green: #10B981;
        --primary-yellow: #F59E0B;
        --dark-bg: #0F172A;
        --card-bg: #1E293B;
        --text-light: #94A3B8;
        --text-white: #FFFFFF;
        --border-color: rgba(255, 255, 255, 0.06);
    }

    .manage-bookings {
        padding: 140px 0 90px;
        background: var(--dark-bg);
        min-height: 70vh;
    }

    .manage-bookings .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 24px;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 12px;
    }

    .page-header h1 {
        color: var(--text-white);
        font-size: 28px;
        font-weight: 700;
    }

    .page-header h1 i {
        color: var(--primary-blue-light);
        margin-right: 10px;
    }

    .page-header .total-badge {
        color: var(--text-light);
        font-size: 14px;
        background: var(--card-bg);
        padding: 8px 16px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 16px 20px;
        text-align: center;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        border-color: rgba(59, 130, 246, 0.1);
    }

    .stat-card .stat-number {
        font-size: 28px;
        font-weight: 700;
    }

    .stat-card .stat-label {
        color: var(--text-light);
        font-size: 12px;
        margin-top: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-card.blue .stat-number {
        color: var(--primary-blue-light);
    }

    .stat-card.green .stat-number {
        color: var(--primary-green);
    }

    .stat-card.yellow .stat-number {
        color: var(--primary-yellow);
    }

    .stat-card.red .stat-number {
        color: #EF4444;
    }

    .filter-bar {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 24px;
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
    }

    .filter-group {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .filter-btn {
        padding: 6px 16px;
        border: 1px solid var(--border-color);
        border-radius: 50px;
        background: transparent;
        color: var(--text-light);
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: 'Poppins', sans-serif;
        font-size: 13px;
        text-decoration: none;
    }

    .filter-btn:hover {
        background: rgba(59, 130, 246, 0.06);
        border-color: var(--primary-blue-light);
        color: var(--primary-blue-light);
    }

    .filter-btn.active {
        background: rgba(59, 130, 246, 0.06);
        border-color: var(--primary-blue-light);
        color: var(--primary-blue-light);
    }

    .filter-btn.danger.active {
        background: rgba(239, 68, 68, 0.06);
        border-color: #EF4444;
        color: #EF4444;
    }

    .search-box {
        display: flex;
        gap: 8px;
        flex: 1;
        min-width: 200px;
        margin-left: auto;
    }

    .search-box input {
        flex: 1;
        padding: 8px 14px;
        background: var(--dark-bg);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        color: var(--text-white);
        font-size: 14px;
        font-family: 'Poppins', sans-serif;
        outline: none;
        transition: all 0.3s ease;
    }

    .search-box input:focus {
        border-color: var(--primary-blue-light);
    }

    .search-box input::placeholder {
        color: #475569;
    }

    .search-box .btn-search {
        padding: 8px 16px;
        background: var(--primary-blue);
        color: var(--text-white);
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .search-box .btn-search:hover {
        background: var(--primary-blue-dark);
    }

    .message-box {
        padding: 14px 20px;
        border-radius: 12px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .message-box.success {
        background: rgba(16, 185, 129, 0.06);
        border: 1px solid rgba(16, 185, 129, 0.06);
        color: var(--primary-green);
    }

    .message-box.error {
        background: rgba(239, 68, 68, 0.06);
        border: 1px solid rgba(239, 68, 68, 0.06);
        color: #EF4444;
    }

    .table-wrapper {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
    }

    .table-responsive {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    table th {
        padding: 14px 16px;
        text-align: left;
        color: var(--text-light);
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid var(--border-color);
        background: rgba(255, 255, 255, 0.02);
        white-space: nowrap;
    }

    table td {
        padding: 14px 16px;
        color: #CBD5E1;
        border-bottom: 1px solid rgba(255, 255, 255, 0.02);
        vertical-align: middle;
    }

    table tr:hover td {
        background: rgba(255, 255, 255, 0.02);
    }

    table tr.cancelled-row {
        opacity: 0.6;
    }

    table tr.cancelled-row td .booking-code {
        text-decoration: line-through;
    }

    .booking-code {
        color: var(--primary-blue-light);
        font-weight: 600;
        font-family: monospace;
        font-size: 13px;
        letter-spacing: 0.5px;
    }

    .passenger-info {
        display: flex;
        flex-direction: column;
    }

    .passenger-info .name {
        color: var(--text-white);
        font-weight: 500;
    }

    .passenger-info .email {
        color: var(--text-light);
        font-size: 12px;
        margin-top: 2px;
    }

    .route-info {
        display: flex;
        flex-direction: column;
    }

    .route-info .route {
        color: var(--text-white);
        font-weight: 500;
    }

    .route-info .time {
        color: var(--text-light);
        font-size: 12px;
        margin-top: 2px;
    }

    .route-info .time i {
        color: var(--primary-blue-light);
        margin-right: 4px;
    }

    .seat-number {
        color: var(--primary-blue-light);
        font-weight: 700;
        font-size: 16px;
    }

    .fare-amount {
        color: var(--primary-green);
        font-weight: 600;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 14px;
        border-radius: 50px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-badge.confirmed {
        background: rgba(16, 185, 129, 0.1);
        color: var(--primary-green);
        border: 1px solid rgba(16, 185, 129, 0.1);
    }

    .status-badge.used {
        background: rgba(56, 189, 248, 0.1);
        color: #38BDF8;
        border: 1px solid rgba(56, 189, 248, 0.1);
    }

    .status-badge.pending {
        background: rgba(245, 158, 11, 0.1);
        color: var(--primary-yellow);
        border: 1px solid rgba(245, 158, 11, 0.1);
    }


    .status-badge.cancelled {
        background: rgba(239, 68, 68, 0.1);
        color: #EF4444;
        border: 1px solid rgba(239, 68, 68, 0.1);
    }

    .actions-cell {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }

    .btn-action {
        padding: 5px 12px;
        border-radius: 6px;
        border: none;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: 'Poppins', sans-serif;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .btn-action.btn-view {
        background: rgba(59, 130, 246, 0.06);
        color: var(--primary-blue-light);
        border: 1px solid rgba(59, 130, 246, 0.06);
    }

    .btn-action.btn-view:hover {
        background: var(--primary-blue);
        color: var(--text-white);
    }

    .btn-action.btn-cancel {
        background: rgba(239, 68, 68, 0.06);
        color: #EF4444;
        border: 1px solid rgba(239, 68, 68, 0.06);
    }

    .btn-action.btn-cancel:hover {
        background: #EF4444;
        color: var(--text-white);
    }

    .btn-action.btn-mark-used {
        background: rgba(56, 189, 248, 0.06);
        color: #38BDF8;
        border: 1px solid rgba(56, 189, 248, 0.06);
    }

    .btn-action.btn-mark-used:hover {
        background: #38BDF8;
        color: var(--dark-bg);
    }

    .btn-action.btn-delete {
        background: rgba(239, 68, 68, 0.1);
        color: #EF4444;
        border: 1px solid rgba(239, 68, 68, 0.1);
    }

    .btn-action.btn-delete:hover {
        background: #EF4444;
        color: var(--text-white);
    }

    .no-data {
        text-align: center;
        padding: 60px 20px;
        color: var(--text-light);
    }

    .no-data i {
        font-size: 48px;
        color: var(--primary-blue-light);
        opacity: 0.3;
        margin-bottom: 16px;
        display: block;
    }

    .no-data h3 {
        color: var(--text-white);
        margin-bottom: 8px;
    }

    @media (max-width: 992px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .manage-bookings {
            padding: 110px 0 60px;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .filter-bar {
            flex-direction: column;
        }

        .search-box {
            width: 100%;
            margin-left: 0;
        }

        .filter-group {
            width: 100%;
            justify-content: center;
        }

        .page-header {
            flex-direction: column;
            text-align: center;
        }

        table {
            font-size: 13px;
        }

        table th,
        table td {
            padding: 10px 12px;
        }
    }

    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .actions-cell {
            flex-direction: column;
        }

        .btn-action {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<section class="manage-bookings">
    <div class="container">

        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1><i class="fas fa-ticket-alt"></i> Manage Bookings</h1>
                <p style="color: var(--text-light); margin-top: 4px;">View, cancel, or delete bookings</p>
            </div>
            <div class="total-badge">
                <i class="fas fa-calendar-alt"></i> Total: <?php echo $total_bookings; ?> bookings
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-number"><?php echo isset($stats['total']) ? $stats['total'] : 0; ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card green">
                <div class="stat-number"><?php echo isset($stats['confirmed']) ? $stats['confirmed'] : 0; ?></div>
                <div class="stat-label">Confirmed</div>
            </div>
            <div class="stat-card yellow">
                <div class="stat-number"><?php echo isset($stats['pending']) ? $stats['pending'] : 0; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card red">
                <div class="stat-number"><?php echo isset($stats['cancelled']) ? $stats['cancelled'] : 0; ?></div>
                <div class="stat-label">Cancelled</div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="message-box error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="message-box success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-group">
                <a href="?status=all" class="filter-btn <?php echo $status_filter == 'all' ? 'active' : ''; ?>">All</a>
                <a href="?status=confirmed" class="filter-btn <?php echo $status_filter == 'confirmed' ? 'active' : ''; ?>">Confirmed</a>
                <a href="?status=pending" class="filter-btn <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">Pending</a>
                <a href="?status=cancelled" class="filter-btn danger <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
            </div>
            <form method="GET" class="search-box">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                <input type="text" name="search" placeholder="Search by name, email, or code..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
            </form>
        </div>

        <!-- Bookings Table -->
        <div class="table-wrapper">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Booking Code</th>
                            <th>Passenger</th>
                            <th>Route</th>
                            <th>Seat</th>
                            <th>Fare</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($bookings) > 0): ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr class="<?php echo $booking['status'] == 'cancelled' ? 'cancelled-row' : ''; ?>">
                                    <td>
                                        <span class="booking-code">#<?php echo htmlspecialchars($booking['booking_code']); ?></span>
                                    </td>
                                    <td>
                                        <div class="passenger-info">
                                            <span class="name"><?php echo htmlspecialchars($booking['full_name']); ?></span>
                                            <span class="email"><?php echo htmlspecialchars($booking['email']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="route-info">
                                            <span class="route">
                                                <?php echo htmlspecialchars($booking['original_city']); ?>
                                                <i class="fas fa-arrow-right" style="font-size:10px; color:#475569; margin:0 4px;"></i>
                                                <?php echo htmlspecialchars($booking['destination']); ?>
                                            </span>
                                            <span class="time">
                                                <i class="fas fa-clock"></i> <?php echo date('d M Y, H:i', strtotime($booking['departure_time'])); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="seat-number"><?php echo htmlspecialchars($booking['seat_number']); ?></span>
                                    </td>
                                    <td>
                                        <span class="fare-amount">XAF <?php echo number_format($booking['fare_paid'], 0); ?></span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $booking['status']; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions-cell">
                                            <a href="../payment.php?reservation_id=<?php echo $booking['reservation_id']; ?>" target="_blank" class="btn-action btn-view">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <?php if ($booking['status'] == 'cancelled'): ?>
                                                <a href="?delete=<?php echo $booking['reservation_id']; ?>"
                                                    class="btn-action btn-delete"
                                                    onclick="return confirmDelete(<?php echo $booking['reservation_id']; ?>, '<?php echo htmlspecialchars($booking['booking_code']); ?>', '<?php echo htmlspecialchars($booking['full_name']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php elseif ($booking['status'] == 'used'): ?>
                                                <!-- ticket already redeemed: no destructive actions available -->
                                            <?php else: ?>
                                                <?php if ($booking['status'] == 'confirmed'): ?>
                                                    <a href="#"
                                                        class="btn-action btn-mark-used"
                                                        onclick="confirmMarkUsed(<?php echo $booking['reservation_id']; ?>, '<?php echo htmlspecialchars($booking['booking_code']); ?>', '<?php echo htmlspecialchars($booking['full_name']); ?>')">
                                                        <i class="fas fa-check-double"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="#"
                                                    class="btn-action btn-cancel"
                                                    onclick="confirmCancel(<?php echo $booking['reservation_id']; ?>, '<?php echo htmlspecialchars($booking['booking_code']); ?>', '<?php echo htmlspecialchars($booking['full_name']); ?>')">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="no-data">
                                        <i class="fas fa-inbox"></i>
                                        <h3>No Bookings Found</h3>
                                        <p>Try adjusting your filters or search criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<script>
    function confirmCancel(id, code, name) {
        var reason = prompt('Enter reason for cancelling booking #' + code + ' for ' + name + ':', 'Customer requested cancellation');
        if (reason !== null && reason !== '') {
            if (confirm('Are you sure you want to cancel booking #' + code + '?\n\nReason: ' + reason)) {
                window.location.href = '?cancel=' + id + '&reason=' + encodeURIComponent(reason);
            }
        } else if (reason !== null) {
            alert('Please enter a cancellation reason.');
        }
        return false;
    }

    function confirmDelete(id, code, name) {
        if (confirm('⚠️ Are you sure you want to permanently DELETE ticket #' + code + ' for ' + name + '?\n\nThis action CANNOT be undone!')) {
            return true;
        }
        return false;
    }

    function confirmMarkUsed(id, code, name) {
        if (confirm('Mark ticket #' + code + ' for ' + name + ' as used?\n\nThis indicates the passenger has boarded and cannot be undone from this screen.')) {
            window.location.href = '?mark_used=' + id;
        }
        return false;
    }
</script>

<?php include '../includes/footer.php'; ?>