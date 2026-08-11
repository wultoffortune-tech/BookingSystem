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

// Get all users
$stmt = $pdo->query("SELECT user_id, full_name, email, phone_number, role, created_at FROM users ORDER BY user_id DESC");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - CamExpress Admin</title>
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
            padding: 14px 16px;
            color: #94A3B8;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            background: #1E293B;
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

        .badge-admin {
            background: rgba(56, 189, 248, 0.06);
            color: #38BDF8;
        }

        .badge-user {
            background: rgba(52, 211, 153, 0.06);
            color: #34D399;
        }

        .badge-staff {
            background: rgba(245, 158, 11, 0.06);
            color: #F59E0B;
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
                padding: 10px 12px;
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
                <h1>Manage Users</h1>
                <p class="subtitle">View and manage all registered users</p>
            </div>
            <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['role']; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>