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

// Get all routes and buses for dropdowns
$routes = $pdo->query("SELECT route_id, original_city, destination, base_fare FROM route ORDER BY original_city")->fetchAll();
$buses = $pdo->query("SELECT bus_id, bus_name, bus_type, total_seats FROM bus WHERE status = 'active'")->fetchAll();

$admin_name = isset($_SESSION['admin_name']) && $_SESSION['admin_name'] !== '' ? $_SESSION['admin_name'] : 'Admin';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $route_id = isset($_POST['route_id']) ? (int)$_POST['route_id'] : 0;
    $bus_id = isset($_POST['bus_id']) ? (int)$_POST['bus_id'] : 0;
    $departure_time = isset($_POST['departure_time']) ? trim($_POST['departure_time']) : '';
    $arrival_time = isset($_POST['arrival_time']) ? trim($_POST['arrival_time']) : '';
    $available_seats = isset($_POST['available_seats']) ? (int)$_POST['available_seats'] : 40;
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;

    if ($route_id <= 0 || $bus_id <= 0 || $departure_time === '' || $arrival_time === '') {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            $sql = "INSERT INTO schedule (route_id, bus_id, departure_time, arrival_time, available_seats, price, expired) 
                    VALUES (?, ?, ?, ?, ?, ?, 0)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$route_id, $bus_id, $departure_time, $arrival_time, $available_seats, $price]);

            $success = 'Schedule created successfully!';
        } catch (PDOException $e) {
            $error = 'Error creating schedule: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Schedule - CamExpress Admin</title>
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
        }

        .admin-header .admin-info .logout-btn:hover {
            background: rgba(239, 68, 68, 0.10);
        }

        .admin-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 32px 24px;
        }

        .admin-container h1 {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .admin-container .subtitle {
            color: #94A3B8;
            font-size: 14px;
            margin-bottom: 24px;
        }

        .form-card {
            background: #1E293B;
            padding: 32px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.02);
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #94A3B8;
            margin-bottom: 4px;
        }

        .form-group label .required {
            color: #EF4444;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 10px;
            font-size: 15px;
            font-family: 'Poppins', sans-serif;
            background: rgba(255, 255, 255, 0.02);
            color: #FFFFFF;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #38BDF8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.04);
        }

        .form-group input::placeholder {
            color: #475569;
        }

        .form-group select option {
            background: #1E293B;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
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

        .alert-error {
            background: rgba(239, 68, 68, 0.06);
            border: 1px solid rgba(239, 68, 68, 0.06);
            color: #EF4444;
        }

        .btn-submit {
            padding: 12px 32px;
            background: #38BDF8;
            color: #FFFFFF;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: #0EA5E9;
            transform: translateY(-2px);
        }

        .btn-cancel {
            padding: 12px 32px;
            background: #1E293B;
            color: #FFFFFF;
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn-cancel:hover {
            background: #334155;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 8px;
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

            .form-card {
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions .btn-submit,
            .form-actions .btn-cancel {
                width: 100%;
                text-align: center;
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
            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($admin_name); ?></span>
            <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <div class="admin-container">
        <h1>Add New Schedule</h1>
        <p class="subtitle">Create a new bus schedule for a route</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" action="./create.php">
                <div class="form-group">
                    <label for="route_id">Route <span class="required">*</span></label>
                    <select id="route_id" name="route_id" required>
                        <option value="">Select a route</option>
                        <?php foreach ($routes as $route): ?>
                            <option value="<?php echo $route['route_id']; ?>">
                                <?php echo htmlspecialchars($route['original_city']); ?> →
                                <?php echo htmlspecialchars($route['destination']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="bus_id">Bus <span class="required">*</span></label>
                    <select id="bus_id" name="bus_id" required>
                        <option value="">Select a bus</option>
                        <?php foreach ($buses as $bus): ?>
                            <option value="<?php echo $bus['bus_id']; ?>">
                                <?php echo htmlspecialchars($bus['bus_name']); ?>
                                (<?php echo htmlspecialchars($bus['bus_type']); ?> - <?php echo $bus['total_seats']; ?> seats)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="departure_time">Departure Time <span class="required">*</span></label>
                        <input type="datetime-local" id="departure_time" name="departure_time" required>
                    </div>
                    <div class="form-group">
                        <label for="arrival_time">Arrival Time <span class="required">*</span></label>
                        <input type="datetime-local" id="arrival_time" name="arrival_time" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="available_seats">Available Seats</label>
                        <input type="number" id="available_seats" name="available_seats" value="40" min="0">
                    </div>
                    <div class="form-group">
                        <label for="price">Price (XAF) <span class="required">*</span></label>
                        <input type="number" id="price" name="price" placeholder="5000" step="100" min="0" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Create Schedule</button>
                    <a href="./index.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
                </div>
            </form>
        </div>
    </div>

</body>

</html>