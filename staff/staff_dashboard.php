<?php
session_start();
require_once '../config/database.php';

// SECURITY: ONLY STAFF AND ADMIN CAN ACCESS
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'staff' && $_SESSION['user_role'] != 'admin')) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Staff Dashboard - CamExpress</title>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Link to your main CSS file (Goes UP one folder using ../) -->
    <link rel="stylesheet" href="../style.css">

    <style>
        /* Extra Dashboard Styling */
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: #f4f7f6;
            display: flex;
        }

        .sidebar {
            width: 260px;
            background: #0c0e0f;
            color: white;
            min-height: 100vh;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar h2 {
            color: #08c0e0;
            margin-bottom: 30px;
            font-size: 22px;
        }

        .sidebar a {
            display: block;
            color: #b0b8c1;
            padding: 15px;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 8px;
            transition: 0.3s;
            font-size: 15px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #08c0e0;
            color: white;
        }

        .sidebar a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .main-content {
            flex: 1;
            padding: 30px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            margin: 0;
            font-size: 32px;
            color: #333;
        }

        .stat-card p {
            color: #888;
            margin-top: 8px;
            font-size: 14px;
        }

        .stat-card i {
            font-size: 30px;
            color: #08c0e0;
            margin-bottom: 10px;
        }

        .logout-btn {
            margin-top: 50px;
            border-top: 1px solid #333;
            padding-top: 20px;
        }
    </style>
</head>

<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <h2><i class="fa-solid fa-bus"></i> CamExpress</h2>
        <a href="staff_dashboard.php" class="active"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="staff_create_booking.php"><i class="fa-solid fa-user-plus"></i> Assist in Booking</a>
        <a href="staff_verify_ticket.php"><i class="fa-solid fa-check-circle"></i> Verify Ticket</a>
        <a href="admin_manage_bookings.php"><i class="fa-solid fa-list"></i> All Bookings (Admin)</a>
        <a href="../logout.php" class="logout-btn" style="color: #ff6b6b;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="topbar">
            <h3 style="margin:0;">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h3>
            <div style="background:#e9ecef; padding:5px 15px; border-radius:20px; font-size:13px; color:#555;">
                <i class="fa-solid fa-user-shield"></i> <?php echo ucfirst($_SESSION['user_role']); ?>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fa-solid fa-ticket"></i>
                <h3>0</h3>
                <p>Bookings Today</p>
            </div>
            <div class="stat-card">
                <i class="fa-solid fa-bus"></i>
                <h3>0</h3>
                <p>Active Buses</p>
            </div>
            <div class="stat-card">
                <i class="fa-solid fa-check-circle" style="color: #28a745;"></i>
                <h3>0</h3>
                <p>Verified Tickets</p>
            </div>
        </div>
    </div>
</body>

</html>