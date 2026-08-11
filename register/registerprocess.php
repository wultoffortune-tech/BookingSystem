<?php
session_start();

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $date_of_birth = isset($_POST['date_of_birth']) ? trim($_POST['date_of_birth']) : '';
    $id_number = isset($_POST['id_number']) ? trim($_POST['id_number']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $role = 'user';

    if ($full_name === '' || $email === '' || $password === '' || $confirm_password === '') {
        $_SESSION['register_error'] = 'Please fill in all required fields.';
        header('Location: register.php');
        exit;
    }

    if ($password !== $confirm_password) {
        $_SESSION['register_error'] = 'Passwords do not match.';
        header('Location: register.php');
        exit;
    }

    if (strlen($password) < 6) {
        $_SESSION['register_error'] = 'Password must be at least 6 characters long.';
        header('Location: register.php');
        exit;
    }

    try {
        $check = $pdo->prepare('SELECT user_id FROM users WHERE email = ? OR id_number = ? LIMIT 1');
        $check->execute([$email, $id_number]);
        $existing = $check->fetch();

        if ($existing) {
            $_SESSION['register_error'] = 'A user with that email or ID number already exists.';
            header('Location: register.php');
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('INSERT INTO users (full_name, date_of_birth, id_number, email, phone_number, role, password, created_at)
                               VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$full_name, $date_of_birth, $id_number, $email, $phone_number, $role, $hashed_password]);

        header('Location: ../login/login.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['register_error'] = 'Registration error: ' . $e->getMessage();
        header('Location: register.php');
        exit;
    }
}

header('Location: register.php');
exit();
