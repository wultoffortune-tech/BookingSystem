<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login/login.php");
    exit();
}

$admin_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Admin';
$admin_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// ========================================
// GENERATE REPORT
// ========================================
if (isset($_POST['generate_report'])) {
    $report_type = isset($_POST['report_type']) ? $_POST['report_type'] : '';
    $date_from = isset($_POST['date_from']) ? $_POST['date_from'] : '';
    $date_to = isset($_POST['date_to']) ? $_POST['date_to'] : '';
    $format = isset($_POST['format']) ? $_POST['format'] : 'html';

    if (empty($report_type)) {
        $error = 'Please select a report type.';
    } else {
        // Generate report based on type
        $report_data = generateReport($pdo, $report_type, $date_from, $date_to);

        // Log the report generation
        $stmt = $pdo->prepare("INSERT INTO report_logs 
                              (report_type, generated_by, date_range_start, date_range_end, parameters, file_format) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $params = json_encode(['date_from' => $date_from, 'date_to' => $date_to]);
        $stmt->execute([$report_type, $admin_id, $date_from, $date_to, $params, $format]);

        // Store report data in session for display
        $_SESSION['report_data'] = $report_data;
        $_SESSION['report_type'] = $report_type;
        $_SESSION['report_format'] = $format;

        $success = '✅ Report generated successfully!';
    }
}

// ========================================
// EXPORT REPORT
// ========================================
if (isset($_GET['export']) && isset($_SESSION['report_data'])) {
    $format = isset($_GET['format']) ? $_GET['format'] : 'pdf';
    $report_data = $_SESSION['report_data'];
    $report_type = isset($_SESSION['report_type']) ? $_SESSION['report_type'] : 'Report';

    exportReport($report_data, $report_type, $format);
}

// ========================================
// FUNCTION TO GENERATE REPORT DATA
// ========================================
function generateReport($pdo, $type, $date_from, $date_to)
{
    $data = [];
    $title = '';

    // Build date condition
    $date_condition = "";
    if (!empty($date_from) && !empty($date_to)) {
        $date_condition = " AND DATE(r.reservation_date) BETWEEN '$date_from' AND '$date_to'";
    } elseif (!empty($date_from)) {
        $date_condition = " AND DATE(r.reservation_date) >= '$date_from'";
    } elseif (!empty($date_to)) {
        $date_condition = " AND DATE(r.reservation_date) <= '$date_to'";
    }

    switch ($type) {
        case 'bookings':
            $title = 'Booking Report';
            $stmt = $pdo->query("SELECT 
                                COUNT(*) as total_bookings,
                                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                                SUM(CASE WHEN status = 'used' THEN 1 ELSE 0 END) as used,
                                SUM(fare_paid) as total_revenue,
                                AVG(fare_paid) as average_fare
                                FROM reservation r
                                WHERE 1=1" . $date_condition);
            $data['summary'] = $stmt->fetch();

            // Get detailed bookings
            $stmt = $pdo->query("SELECT r.*, u.full_name, u.email, 
                                rt.original_city, rt.destination, s.departure_time
                                FROM reservation r
                                JOIN users u ON r.passenger_id = u.user_id
                                JOIN schedule s ON r.schedule_id = s.schedule_id
                                JOIN route rt ON s.route_id = rt.route_id
                                WHERE 1=1" . $date_condition . "
                                ORDER BY r.reservation_date DESC
                                LIMIT 100");
            $data['bookings'] = $stmt->fetchAll();
            break;

        case 'revenue':
            $title = 'Revenue Report';
            $stmt = $pdo->query("SELECT 
                                DATE(r.reservation_date) as date,
                                COUNT(*) as bookings,
                                SUM(fare_paid) as daily_revenue
                                FROM reservation r
                                WHERE status = 'confirmed'" . $date_condition . "
                                GROUP BY DATE(r.reservation_date)
                                ORDER BY date DESC");
            $data['daily_revenue'] = $stmt->fetchAll();

            $stmt = $pdo->query("SELECT 
                                SUM(fare_paid) as total_revenue,
                                AVG(fare_paid) as avg_revenue,
                                COUNT(*) as total_bookings
                                FROM reservation r
                                WHERE status = 'confirmed'" . $date_condition);
            $data['summary'] = $stmt->fetch();
            break;

        case 'users':
            $title = 'User Report';
            $stmt = $pdo->query("SELECT 
                                COUNT(*) as total_users,
                                SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
                                SUM(CASE WHEN role = 'staff' THEN 1 ELSE 0 END) as staff,
                                SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as passengers
                                FROM users");
            $data['summary'] = $stmt->fetch();

            $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 50");
            $data['users'] = $stmt->fetchAll();
            break;

        case 'routes':
            $title = 'Route Performance Report';
            $stmt = $pdo->query("SELECT 
                                rt.route_id,
                                rt.original_city,
                                rt.destination,
                                COUNT(r.reservation_id) as total_bookings,
                                SUM(r.fare_paid) as total_revenue,
                                AVG(r.fare_paid) as avg_fare,
                                COUNT(DISTINCT r.passenger_id) as unique_passengers
                                FROM route rt
                                LEFT JOIN schedule s ON rt.route_id = s.route_id
                                LEFT JOIN reservation r ON s.schedule_id = r.schedule_id
                                WHERE r.status = 'confirmed'" . $date_condition . "
                                GROUP BY rt.route_id
                                ORDER BY total_bookings DESC");
            $data['routes'] = $stmt->fetchAll();
            break;

        case 'staff_performance':
            $title = 'Staff Performance Report';
            $stmt = $pdo->query("SELECT 
                                st.staff_id,
                                u.full_name as staff_name,
                                st.position,
                                COUNT(r.reservation_id) as bookings_assisted,
                                SUM(r.fare_paid) as total_revenue
                                FROM staff st
                                JOIN users u ON st.user_id = u.user_id
                                LEFT JOIN reservation r ON st.staff_id = r.staff_id
                                WHERE r.status = 'confirmed'" . $date_condition . "
                                GROUP BY st.staff_id
                                ORDER BY bookings_assisted DESC");
            $data['staff'] = $stmt->fetchAll();
            break;

        default:
            $data = null;
            break;
    }

    $data['title'] = $title;
    $data['generated_at'] = date('Y-m-d H:i:s');
    $data['date_range'] = [
        'from' => $date_from ?: 'N/A',
        'to' => $date_to ?: 'N/A'
    ];

    return $data;
}

// ========================================
// FUNCTION TO EXPORT REPORT
// ========================================
function exportReport($data, $title, $format)
{
    if ($format == 'pdf') {
        // For PDF export, we'll use a simple HTML print version
        header('Content-Type: text/html');
        header('Content-Disposition: attachment; filename="' . str_replace(' ', '_', $title) . '.html"');
        echo generateReportHTML($data, $title);
        exit();
    } elseif ($format == 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . str_replace(' ', '_', $title) . '.csv"');
        echo generateReportCSV($data, $title);
        exit();
    } elseif ($format == 'json') {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . str_replace(' ', '_', $title) . '.json"');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit();
    }
}

function generateReportHTML($data, $title)
{
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>' . $title . '</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 40px; }
            h1 { text-align: center; color: #2563EB; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th { background: #1E293B; color: white; padding: 10px; text-align: left; }
            td { padding: 8px 10px; border-bottom: 1px solid #E2E8F0; }
            .summary { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin: 20px 0; }
            .summary-box { background: #F1F5F9; padding: 15px; border-radius: 8px; text-align: center; }
            .summary-box .number { font-size: 24px; font-weight: bold; color: #2563EB; }
            .summary-box .label { color: #64748B; }
            .footer { margin-top: 30px; text-align: center; color: #94A3B8; font-size: 12px; }
        </style>
    </head>
    <body>';

    $html .= '<h1>' . $title . '</h1>';
    $html .= '<p style="text-align:center;color:#64748B;">Generated: ' . $data['generated_at'] . '</p>';

    // Add summary if exists
    if (isset($data['summary'])) {
        $html .= '<div class="summary">';
        foreach ($data['summary'] as $key => $value) {
            if (!is_numeric($value)) continue;
            $label = ucwords(str_replace('_', ' ', $key));
            $html .= '<div class="summary-box">
                        <div class="number">' . number_format($value) . '</div>
                        <div class="label">' . $label . '</div>
                     </div>';
        }
        $html .= '</div>';
    }

    // Add table if data exists
    if (isset($data['bookings']) && count($data['bookings']) > 0) {
        $html .= '<table>
                    <thead>
                        <tr>
                            <th>Booking Code</th>
                            <th>Passenger</th>
                            <th>Route</th>
                            <th>Seat</th>
                            <th>Fare</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>';
        foreach ($data['bookings'] as $row) {
            $html .= '<tr>
                        <td>' . $row['booking_code'] . '</td>
                        <td>' . $row['full_name'] . '</td>
                        <td>' . $row['original_city'] . ' → ' . $row['destination'] . '</td>
                        <td>' . $row['seat_number'] . '</td>
                        <td>XAF ' . number_format($row['fare_paid'], 0) . '</td>
                        <td>' . $row['status'] . '</td>
                        <td>' . date('d M Y', strtotime($row['reservation_date'])) . '</td>
                      </tr>';
        }
        $html .= '</tbody></table>';
    }

    $html .= '<div class="footer">Generated by CamExpress Admin System</div>';
    $html .= '</body></html>';

    return $html;
}

function generateReportCSV($data, $title)
{
    $output = fopen('php://output', 'w');

    // Add title and date
    fputcsv($output, [$title]);
    fputcsv($output, ['Generated: ' . $data['generated_at']]);
    fputcsv($output, []);

    if (isset($data['bookings']) && count($data['bookings']) > 0) {
        // Add headers
        fputcsv($output, ['Booking Code', 'Passenger', 'Email', 'Route', 'Seat', 'Fare', 'Status', 'Date']);

        foreach ($data['bookings'] as $row) {
            fputcsv($output, [
                $row['booking_code'],
                $row['full_name'],
                $row['email'],
                $row['original_city'] . ' → ' . $row['destination'],
                $row['seat_number'],
                $row['fare_paid'],
                $row['status'],
                date('d M Y', strtotime($row['reservation_date']))
            ]);
        }
    }

    return '';
}

// Get existing report logs
$stmt = $pdo->prepare("SELECT rl.*, u.full_name as generated_by_name 
                       FROM report_logs rl
                       JOIN users u ON rl.generated_by = u.user_id
                       ORDER BY rl.generated_at DESC
                       LIMIT 20");
$stmt->execute();
$report_logs = $stmt->fetchAll();

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

    .report-card {
        background: #1E293B;
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
    }

    .report-card h3 {
        color: #FFFFFF;
        margin-bottom: 16px;
        font-size: 18px;
    }

    .report-card h3 i {
        color: #8B5CF6;
        margin-right: 8px;
    }

    .form-group {
        margin-bottom: 16px;
    }

    .form-group label {
        display: block;
        color: #94A3B8;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .form-group label i {
        color: #8B5CF6;
        margin-right: 6px;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 12px 16px;
        background: #0F172A;
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 8px;
        color: #FFFFFF;
        font-size: 14px;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s ease;
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #8B5CF6;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.06);
    }

    .form-group select option {
        background: #1E293B;
        color: #FFFFFF;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 16px;
    }

    .btn-primary {
        background: #8B5CF6;
        color: #FFFFFF;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: 'Poppins', sans-serif;
    }

    .btn-primary:hover {
        background: #7C3AED;
        transform: translateY(-2px);
    }

    .btn-success {
        background: #10B981;
        color: #FFFFFF;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: 'Poppins', sans-serif;
        text-decoration: none;
        display: inline-block;
        font-size: 13px;
    }

    .btn-success:hover {
        background: #059669;
        transform: translateY(-2px);
    }

    .btn-info {
        background: #3B82F6;
        color: #FFFFFF;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: 'Poppins', sans-serif;
        text-decoration: none;
        display: inline-block;
        font-size: 13px;
    }

    .btn-info:hover {
        background: #2563EB;
        transform: translateY(-2px);
    }

    .report-preview {
        background: #0F172A;
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 8px;
        padding: 20px;
        margin-top: 16px;
        max-height: 500px;
        overflow: auto;
    }

    .report-preview table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .report-preview th {
        background: rgba(255, 255, 255, 0.02);
        color: #94A3B8;
        padding: 8px 12px;
        text-align: left;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        font-weight: 600;
    }

    .report-preview td {
        padding: 8px 12px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.02);
        color: #CBD5E1;
    }

    .report-preview .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 16px;
    }

    .report-preview .summary-item {
        background: rgba(255, 255, 255, 0.02);
        border-radius: 8px;
        padding: 12px;
        text-align: center;
    }

    .report-preview .summary-item .number {
        font-size: 24px;
        font-weight: 700;
        color: #38BDF8;
    }

    .report-preview .summary-item .label {
        color: #94A3B8;
        font-size: 12px;
    }

    .export-buttons {
        display: flex;
        gap: 8px;
        margin-top: 12px;
        flex-wrap: wrap;
    }

    .error-box {
        background: rgba(239, 68, 68, 0.06);
        color: #EF4444;
        padding: 14px 18px;
        border-radius: 8px;
        border: 1px solid rgba(239, 68, 68, 0.06);
        margin-bottom: 16px;
    }

    .success-box {
        background: rgba(16, 185, 129, 0.06);
        color: #10B981;
        padding: 14px 18px;
        border-radius: 8px;
        border: 1px solid rgba(16, 185, 129, 0.06);
        margin-bottom: 16px;
    }

    .report-logs table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .report-logs th {
        color: #94A3B8;
        padding: 10px 12px;
        text-align: left;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
    }

    .report-logs td {
        padding: 10px 12px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.02);
        color: #CBD5E1;
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }

        .report-preview .summary-grid {
            grid-template-columns: 1fr 1fr;
        }

        .page-section {
            padding: 110px 0 60px;
        }
    }
</style>

<section class="page-section">
    <div class="container">
        <div class="page-header">
            <h1><span>Reports</span> & Analytics</h1>
            <p>Generate, view, and export system reports</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-box"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="success-box"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Generate Report -->
        <div class="report-card">
            <h3><i class="fas fa-plus-circle"></i> Generate Report</h3>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-file-alt"></i> Report Type</label>
                        <select name="report_type" required>
                            <option value="">Select Report Type</option>
                            <option value="bookings">Booking Report</option>
                            <option value="revenue">Revenue Report</option>
                            <option value="users">User Report</option>
                            <option value="routes">Route Performance</option>
                            <option value="staff_performance">Staff Performance</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar-start"></i> Date From</label>
                        <input type="date" name="date_from" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar-end"></i> Date To</label>
                        <input type="date" name="date_to" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-file-export"></i> Export Format</label>
                        <select name="format">
                            <option value="html">HTML (Preview)</option>
                            <option value="pdf">PDF</option>
                            <option value="csv">CSV (Excel)</option>
                            <option value="json">JSON</option>
                        </select>
                    </div>
                    <div class="form-group" style="display:flex; align-items:flex-end;">
                        <button type="submit" name="generate_report" class="btn-primary" style="width:100%;">
                            <i class="fas fa-file-alt"></i> Generate Report
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Report Preview -->
        <?php if (isset($_SESSION['report_data']) && !empty($_SESSION['report_data'])):
            $report_data = $_SESSION['report_data'];
            $report_type = isset($_SESSION['report_type']) ? $_SESSION['report_type'] : '';
            $report_format = isset($_SESSION['report_format']) ? $_SESSION['report_format'] : 'html';
        ?>
            <div class="report-card">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                    <h3><i class="fas fa-chart-bar"></i> <?php echo $report_data['title']; ?></h3>
                    <div class="export-buttons">
                        <a href="?export=1&format=html" class="btn-info" target="_blank"><i class="fas fa-eye"></i> View</a>
                        <a href="?export=1&format=pdf" class="btn-success" target="_blank"><i class="fas fa-file-pdf"></i> PDF</a>
                        <a href="?export=1&format=csv" class="btn-success" target="_blank"><i class="fas fa-file-excel"></i> CSV</a>
                        <a href="?export=1&format=json" class="btn-primary" target="_blank"><i class="fas fa-code"></i> JSON</a>
                    </div>
                </div>

                <div class="report-preview">
                    <?php if (isset($report_data['summary'])): ?>
                        <div class="summary-grid">
                            <?php foreach ($report_data['summary'] as $key => $value): ?>
                                <?php if (is_numeric($value)): ?>
                                    <div class="summary-item">
                                        <div class="number">
                                            <?php if (strpos($key, 'revenue') !== false): ?>
                                                XAF <?php echo number_format($value, 0); ?>
                                            <?php else: ?>
                                                <?php echo number_format($value); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="label"><?php echo ucwords(str_replace('_', ' ', $key)); ?></div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($report_data['bookings']) && count($report_data['bookings']) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Passenger</th>
                                    <th>Route</th>
                                    <th>Seat</th>
                                    <th>Fare</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data['bookings'] as $row): ?>
                                    <tr>
                                        <td><span style="color:#38BDF8;"><?php echo $row['booking_code']; ?></span></td>
                                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td><?php echo $row['original_city']; ?> → <?php echo $row['destination']; ?></td>
                                        <td><?php echo $row['seat_number']; ?></td>
                                        <td>XAF <?php echo number_format($row['fare_paid'], 0); ?></td>
                                        <td><span class="badge badge-<?php echo $row['status']; ?>"><?php echo $row['status']; ?></span></td>
                                        <td><?php echo date('d M Y', strtotime($row['reservation_date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php elseif (isset($report_data['daily_revenue']) && count($report_data['daily_revenue']) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Bookings</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data['daily_revenue'] as $row): ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($row['date'])); ?></td>
                                        <td><?php echo $row['bookings']; ?></td>
                                        <td>XAF <?php echo number_format($row['daily_revenue'], 0); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php elseif (isset($report_data['routes']) && count($report_data['routes']) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Route</th>
                                    <th>Bookings</th>
                                    <th>Revenue</th>
                                    <th>Avg Fare</th>
                                    <th>Passengers</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data['routes'] as $row): ?>
                                    <tr>
                                        <td><?php echo $row['original_city']; ?> → <?php echo $row['destination']; ?></td>
                                        <td><?php echo $row['total_bookings']; ?></td>
                                        <td>XAF <?php echo number_format($row['total_revenue'], 0); ?></td>
                                        <td>XAF <?php echo number_format($row['avg_fare'], 0); ?></td>
                                        <td><?php echo $row['unique_passengers']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php elseif (isset($report_data['staff']) && count($report_data['staff']) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Staff Name</th>
                                    <th>Position</th>
                                    <th>Bookings Assisted</th>
                                    <th>Total Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data['staff'] as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['staff_name']); ?></td>
                                        <td><?php echo $row['position']; ?></td>
                                        <td><?php echo $row['bookings_assisted']; ?></td>
                                        <td>XAF <?php echo number_format($row['total_revenue'], 0); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php elseif (isset($report_data['users']) && count($report_data['users']) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data['users'] as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo isset($row['phone_number']) ? htmlspecialchars($row['phone_number']) : 'N/A'; ?></td>
                                        <td><?php echo $row['role']; ?></td>
                                        <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="color:#94A3B8; text-align:center; padding:20px;">
                            <i class="fas fa-inbox"></i> No data available for this report.
                        </p>
                    <?php endif; ?>

                    <div style="margin-top:12px; color:#64748B; font-size:12px; text-align:right;">
                        Generated: <?php echo $report_data['generated_at']; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Report Logs -->
        <div class="report-card report-logs">
            <h3><i class="fas fa-history"></i> Report Generation History</h3>

            <?php if (count($report_logs) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Report Type</th>
                            <th>Generated By</th>
                            <th>Date Range</th>
                            <th>Format</th>
                            <th>Generated At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_logs as $log): ?>
                            <tr>
                                <td><?php echo ucfirst($log['report_type']); ?></td>
                                <td><?php echo htmlspecialchars($log['generated_by_name']); ?></td>
                                <td>
                                    <?php echo $log['date_range_start'] ? date('d M Y', strtotime($log['date_range_start'])) : 'N/A'; ?>
                                    →
                                    <?php echo $log['date_range_end'] ? date('d M Y', strtotime($log['date_range_end'])) : 'N/A'; ?>
                                </td>
                                <td><?php echo strtoupper($log['file_format']); ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($log['generated_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color:#94A3B8; text-align:center; padding:20px;">
                    <i class="fas fa-inbox"></i> No reports generated yet.
                </p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>