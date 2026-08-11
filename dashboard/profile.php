<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login/login.php');
    exit();
}

require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
    $date_of_birth = isset($_POST['date_of_birth']) ? trim($_POST['date_of_birth']) : '';
    $id_number = isset($_POST['id_number']) ? trim($_POST['id_number']) : '';

    if ($full_name === '' || $email === '') {
        $error = 'Full name and email are required.';
    } else {
        $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id <> ?");
        $check->execute([$email, $user_id]);
        if ($check->fetch()) {
            $error = 'This email address is already in use by another account.';
        } else {
            $update = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone_number = ?, date_of_birth = ?, id_number = ? WHERE user_id = ?");
            $update->execute([$full_name, $email, $phone_number, $date_of_birth, $id_number, $user_id]);
            $message = 'Your profile was updated successfully.';
            $_SESSION['full_name'] = $full_name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $full_name;
        }
    }
}

$stmt = $pdo->prepare("SELECT full_name, email, phone_number, date_of_birth, id_number FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

include '../includes/header.php';
?>

<section class="page-section" style="padding: 140px 0 90px;">
    <div class="container">
        <div class="section-header">
            <span class="subtitle">Profile details</span>
            <h2 class="section-title">Edit your account</h2>
            <p class="section-description">Keep your profile information up to date for faster booking and support.</p>
        </div>

        <?php if ($message): ?>
            <div style="background:rgba(52,211,153,0.08); border:1px solid rgba(52,211,153,0.18); color:#a7f3d0; border-radius:20px; padding:18px 22px; margin-bottom:30px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.18); color:#fecaca; border-radius:20px; padding:18px 22px; margin-bottom:30px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="profile.php" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.08); border-radius:24px; padding:36px; max-width:720px;">
            <div style="display:grid; gap:18px;">
                <label style="display:block; color:#cbd5e1; font-weight:600;">Full Name</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars(isset($user['full_name']) ? $user['full_name'] : ''); ?>" required style="width:100%; padding:14px 18px; border-radius:16px; border:1px solid rgba(255,255,255,0.12); background:rgba(255,255,255,0.05); color:#ffffff;">

                <label style="display:block; color:#cbd5e1; font-weight:600;">Email address</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars(isset($user['email']) ? $user['email'] : ''); ?>" required style="width:100%; padding:14px 18px; border-radius:16px; border:1px solid rgba(255,255,255,0.12); background:rgba(255,255,255,0.05); color:#ffffff;">

                <label style="display:block; color:#cbd5e1; font-weight:600;">Phone number</label>
                <input type="tel" name="phone_number" value="<?php echo htmlspecialchars(isset($user['phone_number']) ? $user['phone_number'] : ''); ?>" style="width:100%; padding:14px 18px; border-radius:16px; border:1px solid rgba(255,255,255,0.12); background:rgba(255,255,255,0.05); color:#ffffff;">

                <div style="display:grid; gap:18px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                    <div>
                        <label style="display:block; color:#cbd5e1; font-weight:600;">Date of birth</label>
                        <input type="date" name="date_of_birth" value="<?php echo htmlspecialchars(isset($user['date_of_birth']) ? $user['date_of_birth'] : ''); ?>" style="width:100%; padding:14px 18px; border-radius:16px; border:1px solid rgba(255,255,255,0.12); background:rgba(255,255,255,0.05); color:#ffffff;">
                    </div>
                    <div>
                        <label style="display:block; color:#cbd5e1; font-weight:600;">ID number</label>
                        <input type="text" name="id_number" value="<?php echo htmlspecialchars(isset($user['id_number']) ? $user['id_number'] : ''); ?>" style="width:100%; padding:14px 18px; border-radius:16px; border:1px solid rgba(255,255,255,0.12); background:rgba(255,255,255,0.05); color:#ffffff;">
                    </div>
                </div>

                <button type="submit" style="width:100%; background:#7c5cff; border:none; color:#ffffff; padding:16px 0; border-radius:18px; font-size:16px; font-weight:700; cursor:pointer;">Save changes</button>
            </div>
        </form>
    </div>
</section>

<?php include '../includes/footer.php';
