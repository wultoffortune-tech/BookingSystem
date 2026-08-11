<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login/login.php');
    exit();
}

require_once '../../config/database.php';

$error = '';
$success = '';

$routeColumns = [];
try {
    $columns = $pdo->query("SHOW COLUMNS FROM `route`")->fetchAll();
    foreach ($columns as $column) {
        $routeColumns[$column['Field']] = true;
    }
} catch (PDOException $e) {
    $routeColumns = [];
}

$hasDescription = isset($routeColumns['description']);
$hasCreatedAt = isset($routeColumns['created_at']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $original_city = isset($_POST['original_city']) ? trim($_POST['original_city']) : '';
    $destination = isset($_POST['destination']) ? trim($_POST['destination']) : '';
    $base_fare = isset($_POST['base_fare']) ? (float)$_POST['base_fare'] : 0;
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

    if ($original_city === '' || $destination === '') {
        $error = 'Please provide both origin and destination.';
    } else {
        try {
            $insertCols = ['original_city', 'destination', 'base_fare'];
            $placeholders = ['?', '?', '?'];
            $insertParams = [$original_city, $destination, $base_fare];

            if ($hasDescription) {
                $insertCols[] = 'description';
                $placeholders[] = '?';
                $insertParams[] = $description;
            }

            if ($hasCreatedAt) {
                $insertCols[] = 'created_at';
                $placeholders[] = 'NOW()';
            }

            $sql = 'INSERT INTO route (' . implode(', ', $insertCols) . ') VALUES (' . implode(', ', $placeholders) . ')';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($insertParams);
            $success = 'Route added successfully.';
        } catch (PDOException $e) {
            $error = 'Could not add route: ' . $e->getMessage();
        }
    }
}

$selectCols = ['route_id', 'original_city', 'destination', 'base_fare'];
if ($hasDescription) {
    $selectCols[] = 'description';
}
if ($hasCreatedAt) {
    $orderBy = 'created_at DESC';
} else {
    $orderBy = 'route_id DESC';
}

$routes = $pdo->query('SELECT ' . implode(', ', $selectCols) . ' FROM route ORDER BY ' . $orderBy)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Routes - CamExpress</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #0F172A;
            color: #E2E8F0;
            font-family: 'Poppins', sans-serif;
        }

        .admin-header {
            background: #1E293B;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .admin-header a {
            color: #38BDF8;
            text-decoration: none;
            font-weight: 600;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px;
        }

        .card {
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 18px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .card h2 {
            color: #fff;
            margin-bottom: 12px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #cbd5e1;
        }

        input,
        textarea {
            width: 100%;
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.04);
            color: #fff;
            margin-bottom: 16px;
        }

        button {
            background: #7c5cff;
            color: #fff;
            border: none;
            padding: 14px 22px;
            border-radius: 14px;
            cursor: pointer;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        th {
            color: #94a3b8;
            font-weight: 600;
        }

        td {
            color: #e2e8f0;
        }

        .alert {
            padding: 16px 18px;
            border-radius: 14px;
            margin-bottom: 16px;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.16);
            color: #fecaca;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.16);
            color: #bbf7d0;
        }
    </style>
</head>

<body>
    <header class="admin-header">
        <a href="../dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        <a href="../logout.php">Logout</a>
    </header>
    <div class="container">
        <div class="card">
            <h2>Add new route</h2>
            <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
            <form method="POST" action="index.php">
                <label for="original_city">Origin</label>
                <input type="text" id="original_city" name="original_city" required>

                <label for="destination">Destination</label>
                <input type="text" id="destination" name="destination" required>

                <label for="base_fare">Base Fare</label>
                <input type="number" id="base_fare" name="base_fare" min="0" step="0.01" value="0">

                <?php if ($hasDescription): ?>
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                <?php endif; ?>

                <button type="submit">Save Route</button>
            </form>
        </div>

        <div class="card">
            <h2>Existing routes</h2>
            <table>
                <thead>
                    <tr>
                        <th>Route</th>
                        <th>Base Fare</th>
                        <?php if ($hasDescription): ?>
                            <th>Description</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($routes as $route): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($route['original_city'] . ' → ' . $route['destination']); ?></td>
                            <td>XAF <?php echo number_format($route['base_fare'], 0); ?></td>
                            <?php if ($hasDescription): ?>
                                <td><?php echo htmlspecialchars(isset($route['description']) ? $route['description'] : ''); ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>