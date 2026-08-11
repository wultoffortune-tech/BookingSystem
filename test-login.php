<?php
require_once 'config/database.php';

$email = 'admin@camexpress.cm';
$password = 'Admin123';

echo "<h2>Testing Login</h2>";

try {
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        echo "❌ Users table does not exist!<br>";
        exit();
    }
    echo "✅ Users table exists.<br>";

    // Find user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "❌ User not found: " . $email . "<br>";

        // Create user
        $hashed = password_hash('Admin123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (full_name, email, phone_number, id_number, password, role) 
                VALUES ('Admin User', 'admin@camexpress.cm', '+237 6XX XXX XXX', 'ADMIN001', :password, 'admin')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['password' => $hashed]);
        echo "✅ Admin user created!<br>";

        // Fetch again
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    echo "<pre>";
    print_r($user);
    echo "</pre>";

    // Test password
    if (password_verify($password, $user['password'])) {
        echo "✅ Password matches!<br>";
        echo "✅ You can login with:<br>";
        echo "Email: admin@camexpress.cm<br>";
        echo "Password: Admin123<br>";
    } else {
        echo "❌ Password does not match!<br>";
        echo "Stored hash: " . $user['password'] . "<br>";

        // Update password
        $new_hash = password_hash('Admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE email = :email");
        $stmt->execute(['password' => $new_hash, 'email' => $email]);
        echo "✅ Password updated!<br>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
