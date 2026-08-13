<?php
session_start();
require_once '../config/database.php';
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'staff' && $_SESSION['user_role'] != 'admin')) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['ticket_id'])) {
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'used' WHERE id = ?");
    $stmt->execute([$_POST['ticket_id']]);
}
header("Location: staff_verify_ticket.php");
exit();
?>