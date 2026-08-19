<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login/login.php");
    exit();
}

$admin_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Admin';
$admin_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'admin@camexpress.cm';

// Get all counters
$stmt = $pdo->query("SELECT c.*, u.full_name as admin_name 
                     FROM counter c 
                     LEFT JOIN users u ON c.admin_id = u.user_id
                     ORDER BY c.counter_id DESC");
$counters = $stmt->fetchAll();

// Get stats
$stmt = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
    FROM counter");
$stats = $stmt->fetch();

include '../includes/header.php';
?>

<style>
    .page-section {
        padding: 140px 0 90px;
        background: #0F172A;
        min-height: 70vh;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 24px;
    }

    .page-header {
        margin-bottom: 30px;
    }

    .page-header h1 {
        font-size: 28px;
        font-weight: 700;
        color: #FFFFFF;
    }

    .page-header h1 span {
        color: #8B5CF6;
    }

    .page-header p {
        color: #94A3B8;
        margin-top: 4px;
    }

    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: #1E293B;
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 12px;
        padding: 16px 20px;
        text-align: center;
    }

    .stat-card .stat-number {
        font-size: 28px;
        font-weight: 700;
        color: #FFFFFF;
    }

    .stat-card .stat-label {
        color: #94A3B8;
        font-size: 12px;
        margin-top: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-card.purple .stat-number {
        color: #8B5CF6;
    }

    .stat-card.green .stat-number {
        color: #10B981;
    }

    .stat-card.red .stat-number {
        color: #EF4444;
    }

    .table-wrapper {
        background: #1E293B;
        border: 1px solid rgba(255, 255, 255, 0.04);
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
        padding: 12px 16px;
        text-align: left;
        color: #94A3B8;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        background: rgba(255, 255, 255, 0.02);
    }

    table td {
        padding: 12px 16px;
        color: #CBD5E1;
        border-bottom: 1px solid rgba(255, 255, 255, 0.02);
    }

    table tr:hover td {
        background: rgba(255, 255, 255, 0.02);
    }

    .status-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 50px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-badge.active {
        background: rgba(16, 185, 129, 0.06);
        color: #10B981;
        border: 1px solid rgba(16, 185, 129, 0.06);
    }

    .status-badge.inactive {
        background: rgba(239, 68, 68, 0.06);
        color: #EF4444;
        border: 1px solid rgba(239, 68, 68, 0.06);
    }

    .status-badge.closed {
        background: rgba(245, 158, 11, 0.06);
        color: #F59E0B;
        border: 1px solid rgba(245, 158, 11, 0.06);
    }

    .btn-action {
        padding: 4px 12px;
        border-radius: 6px;
        border: none;
        font-size: 11px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: 'Poppins', sans-serif;
        text-decoration: none;
        display: inline-block;
    }

    .btn-action.btn-edit {
        background: rgba(59, 130, 246, 0.06);
        color: #3B82F6;
        border: 1px solid rgba(59, 130, 246, 0.06);
    }

    .btn-action.btn-edit:hover {
        background: #3B82F6;
        color: #FFFFFF;
    }

    .btn-action.btn-delete {
        background: rgba(239, 68, 68, 0.06);
        color: #EF4444;
        border: 1px solid rgba(239, 68, 68, 0.06);
    }

    .btn-action.btn-delete:hover {
        background: #EF4444;
        color: #FFFFFF;
    }

    .btn-add {
        background: #8B5CF6;
        color: #FFFFFF;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: 'Poppins', sans-serif;
        text-decoration: none;
        display: inline-block;
    }

    .btn-add:hover {
        background: #7C3AED;
        transform: translateY(-2px);
    }

    .no-data {
        text-align: center;
        padding: 40px 20px;
        color: #94A3B8;
    }

    .no-data i {
        font-size: 40px;
        color: #8B5CF6;
        opacity: 0.3;
        margin-bottom: 12px;
        display: block;
    }

    .no-data h3 {
        color: #FFFFFF;
        margin-bottom: 6px;
        font-size: 18px;
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .page-section {
            padding: 110px 0 60px;
        }

        .header-actions {
            flex-direction: column;
            align-items: flex-start;
        }
    }

    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<section class="page-section">
    <div class="container">
        <div class="page-header">
            <div class="header-actions">
                <div>
                    <h1>Manage <span>Counters</span></h1>
                    <p>Manage all sales counters/agencies.</p>
                </div>
                <a href="counter_add.php" class="btn-add"><i class="fas fa-plus"></i> Add Counter</a>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card purple">
                <div class="stat-number"><?php echo isset($stats['total']) ? $stats['total'] : 0; ?></div>
                <div class="stat-label">Total Counters</div>
            </div>
            <div class="stat-card green">
                <div class="stat-number"><?php echo isset($stats['active']) ? $stats['active'] : 0; ?></div>
                <div class="stat-label">Active</div>
            </div>
            <div class="stat-card red">
                <div class="stat-number"><?php echo isset($stats['inactive']) ? $stats['inactive'] : 0; ?></div>
                <div class="stat-label">Inactive</div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-wrapper">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Location</th>
                            <th>City</th>
                            <th>Phone</th>
                            <th>Admin</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($counters) > 0): ?>
                            <?php foreach ($counters as $counter): ?>
                                <tr>
                                    <td>#<?php echo $counter['agency_id']; ?></td>
                                    <td><span style="color:#8B5CF6; font-weight:600;"><?php echo htmlspecialchars($counter['counter_code']); ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($counter['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($counter['location']); ?></td>
                                    <td><?php echo htmlspecialchars($counter['city']); ?></td>
                                    <td><?php echo htmlspecialchars($counter['phone_number']); ?></td>
                                    <td><?php echo isset($counter['admin_name']) ? htmlspecialchars($counter['admin_name']) : 'N/A'; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo isset($counter['status']) ? $counter['status'] : 'inactive'; ?>">
                                            <?php echo isset($counter['status']) ? ucfirst($counter['status']) : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="counter_edit.php?id=<?php echo $counter['agency_id']; ?>" class="btn-action btn-edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="counter_delete.php?id=<?php echo $counter['agency_id']; ?>" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this counter?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9">
                                    <div class="no-data">
                                        <i class="fas fa-store"></i>
                                        <h3>No Counters</h3>
                                        <p>No counters/agencies have been created yet.</p>
                                        <a href="counter_add.php" style="display:inline-block; margin-top:12px; padding:10px 24px; background:#8B5CF6; color:#FFFFFF; border-radius:8px; text-decoration:none; font-weight:600;">
                                            <i class="fas fa-plus"></i> Create First Counter
                                        </a>
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

<?php include '../includes/footer.php'; ?>