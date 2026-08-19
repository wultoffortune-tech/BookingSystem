<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login/login.php");
    exit();
}

$admin_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Admin';
$admin_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'admin@camexpress.cm';

$error = '';
$success = '';

// Get all admins for dropdown
$stmt = $pdo->query("SELECT user_id, full_name, email FROM users WHERE role = 'admin'");
$admins = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $location = isset($_POST['location']) ? trim($_POST['location']) : '';
    $city = isset($_POST['city']) ? trim($_POST['city']) : '';
    $phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $admin_id = isset($_POST['admin_id']) ? (int)$_POST['admin_id'] : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : 'active';

    if (empty($name) || empty($location) || empty($city)) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            // Generate agency code
            $agency_code = 'CTR-' . strtoupper(substr($name, 0, 3)) . '-' . rand(100, 999);

            $stmt = $pdo->prepare("INSERT INTO agency
                                  (agency_code, name, location, city, phone_number, email, address, admin_id, status) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$agency_code, $name, $location, $city, $phone_number, $email, $address, $admin_id, $status]);

            $success = '✅ agency created successfully!';

            // Redirect after 2 seconds
            header("refresh:2;url=agency.php");
        } catch (PDOException $e) {
            $error = '❌ Database Error: ' . $e->getMessage();
        }
    }
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
        max-width: 800px;
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

    .form-container {
        background: #1E293B;
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 12px;
        padding: 30px;
    }

    .form-group {
        margin-bottom: 18px;
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
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px 16px;
        background: #0F172A;
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 8px;
        color: #FFFFFF;
        font-size: 15px;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s ease;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #8B5CF6;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    }

    .form-group input::placeholder,
    .form-group textarea::placeholder {
        color: #475569;
    }

    .form-group select option {
        background: #1E293B;
        color: #FFFFFF;
    }

    .form-group textarea {
        resize: vertical;
        min-height: 80px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
    }

    .btn-submit {
        background: #8B5CF6;
        color: #FFFFFF;
        border: none;
        padding: 14px 28px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: 'Poppins', sans-serif;
        width: 100%;
    }

    .btn-submit:hover {
        background: #7C3AED;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
    }

    .btn-cancel {
        display: inline-block;
        padding: 12px 24px;
        background: rgba(255, 255, 255, 0.04);
        color: #94A3B8;
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
        font-family: 'Poppins', sans-serif;
    }

    .btn-cancel:hover {
        background: rgba(255, 255, 255, 0.08);
        color: #FFFFFF;
    }

    .error-box {
        background: rgba(239, 68, 68, 0.06);
        color: #EF4444;
        padding: 14px 18px;
        border-radius: 8px;
        border: 1px solid rgba(239, 68, 68, 0.06);
        margin-bottom: 20px;
    }

    .success-box {
        background: rgba(16, 185, 129, 0.06);
        color: #10B981;
        padding: 14px 18px;
        border-radius: 8px;
        border: 1px solid rgba(16, 185, 129, 0.06);
        margin-bottom: 20px;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 10px;
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }

        .page-section {
            padding: 110px 0 60px;
        }

        .form-container {
            padding: 20px;
        }
    }
</style>

<section class="page-section">
    <div class="container">
        <div class="page-header">
            <h1>Add <span>agency</span></h1>
            <p>Create a new sales agency/agency.</p>
        </div>

        <?php if ($error): ?>
            <div class="error-box"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-box"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-store"></i> agency Name *</label>
                        <input type="text" name="name" placeholder="e.g. Central Office" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-code"></i> agency Code</label>
                        <input type="text" value="Auto-generated" disabled style="color:#475569;">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Location *</label>
                        <input type="text" name="location" placeholder="e.g. Main Street" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-city"></i> City *</label>
                        <input type="text" name="city" placeholder="e.g. Yaoundé" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="tel" name="phone_number" placeholder="+237 6XX XXX XXX">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="email" placeholder="agency@example.com">
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-address"></i> Address</label>
                    <textarea name="address" placeholder="Full address of the agency"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user-shield"></i> Admin</label>
                        <select name="admin_id" required>
                            <option value="">Select Admin</option>
                            <?php foreach ($admins as $admin): ?>
                                <option value="<?php echo $admin['user_id']; ?>">
                                    <?php echo htmlspecialchars($admin['full_name'] . ' (' . $admin['email'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-circle"></i> Status</label>
                        <select name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Create agency</button>
                    <a href="agency.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>