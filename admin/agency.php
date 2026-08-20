<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login/login.php");
    exit();
}

$admin_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Admin';
$admin_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'admin@camexpress.cm';

// First, check if agency table exists and its structure
try {
    // Check if agency table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'agency'")->fetch();

    if (!$tableCheck) {
        // If agency table doesn't exist, create it
        $pdo->exec("CREATE TABLE IF NOT EXISTS `agency` (
            `agency_id` int(11) NOT NULL AUTO_INCREMENT,
            `agency_code` varchar(20) DEFAULT NULL,
            `name` varchar(100) NOT NULL,
            `location` varchar(100) DEFAULT NULL,
            `city` varchar(100) DEFAULT NULL,
            `phone_number` varchar(20) DEFAULT NULL,
            `email` varchar(100) DEFAULT NULL,
            `address` text DEFAULT NULL,
            `status` enum('active','inactive','closed') DEFAULT 'active',
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`agency_id`),
            UNIQUE KEY `agency_code` (`agency_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1");
    }

    // Check what columns exist in agency table
    $columns = [];
    $colQuery = $pdo->query("SHOW COLUMNS FROM agency");
    while ($col = $colQuery->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $col['Field'];
    }

    // Build query based on existing columns
    $selectFields = "a.*";
    $joinClause = "";

    // Check if admin_id exists in agency table
    if (in_array('admin_id', $columns)) {
        $selectFields .= ", u.full_name as admin_name";
        $joinClause = "LEFT JOIN users u ON a.admin_id = u.user_id";
    }

    // Check if staff_id exists in agency table
    if (in_array('staff_id', $columns)) {
        $selectFields .= ", s.full_name as staff_name";
        if (empty($joinClause)) {
            $joinClause = "LEFT JOIN staff st ON a.staff_id = st.staff_id LEFT JOIN users s ON st.user_id = s.user_id";
        } else {
            $joinClause .= " LEFT JOIN staff st ON a.staff_id = st.staff_id LEFT JOIN users s ON st.user_id = s.user_id";
        }
    }

    // Get all agencies
    $sql = "SELECT " . $selectFields . " 
            FROM agency a
            " . $joinClause . "
            ORDER BY a.agency_id DESC";

    $stmt = $pdo->query($sql);
    $agencies = $stmt->fetchAll();
} catch (PDOException $e) {
    // If table doesn't exist yet, set empty array
    $agencies = [];
    error_log("Agency table error: " . $e->getMessage());
}

// Get stats
try {
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
        FROM agency");
    $stats = $stmt->fetch();
} catch (PDOException $e) {
    $stats = ['total' => 0, 'active' => 0, 'inactive' => 0];
}

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

    .stat-card:hover {
        transform: translateY(-2px);
        border-color: rgba(139, 92, 246, 0.1);
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
        white-space: nowrap;
    }

    table td {
        padding: 12px 16px;
        color: #CBD5E1;
        border-bottom: 1px solid rgba(255, 255, 255, 0.02);
        vertical-align: middle;
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
        transform: translateY(-2px);
    }

    .btn-action.btn-delete {
        background: rgba(239, 68, 68, 0.06);
        color: #EF4444;
        border: 1px solid rgba(239, 68, 68, 0.06);
    }

    .btn-action.btn-delete:hover {
        background: #EF4444;
        color: #FFFFFF;
        transform: translateY(-2px);
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
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
    }

    .agency-code {
        color: #8B5CF6;
        font-weight: 600;
        font-family: monospace;
        font-size: 12px;
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

    .no-data .btn-first {
        display: inline-block;
        margin-top: 12px;
        padding: 10px 24px;
        background: #8B5CF6;
        color: #FFFFFF;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .no-data .btn-first:hover {
        background: #7C3AED;
        transform: translateY(-2px);
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
                    <h1>Manage <span>Agencies</span></h1>
                    <p>Manage all sales agencies/offices.</p>
                </div>
                <a href="agency_add.php" class="btn-add"><i class="fas fa-plus"></i> Add Agency</a>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card purple">
                <div class="stat-number"><?php echo isset($stats['total']) ? $stats['total'] : 0; ?></div>
                <div class="stat-label">Total Agencies</div>
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
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($agencies) && count($agencies) > 0): ?>
                            <?php foreach ($agencies as $agency): ?>
                                <tr>
                                    <td>#<?php echo $agency['agency_id']; ?></td>
                                    <td><span class="agency-code"><?php echo isset($agency['agency_code']) ? htmlspecialchars($agency['agency_code']) : 'N/A'; ?></span></td>
                                    <td><strong><?php echo isset($agency['name']) ? htmlspecialchars($agency['name']) : 'N/A'; ?></strong></td>
                                    <td><?php echo isset($agency['location']) ? htmlspecialchars($agency['location']) : 'N/A'; ?></td>
                                    <td><?php echo isset($agency['city']) ? htmlspecialchars($agency['city']) : 'N/A'; ?></td>
                                    <td><?php echo isset($agency['phone_number']) ? htmlspecialchars($agency['phone_number']) : 'N/A'; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo isset($agency['status']) ? $agency['status'] : 'inactive'; ?>">
                                            <?php echo isset($agency['status']) ? ucfirst($agency['status']) : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="agency_edit.php?id=<?php echo $agency['agency_id']; ?>" class="btn-action btn-edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="agency_delete.php?id=<?php echo $agency['agency_id']; ?>" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this agency?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">
                                    <div class="no-data">
                                        <i class="fas fa-store"></i>
                                        <h3>No Agencies</h3>
                                        <p>No agencies/offices have been created yet.</p>
                                        <a href="agency_add.php" class="btn-first">
                                            <i class="fas fa-plus"></i> Create First Agency
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