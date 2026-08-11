<?php
// Include database connection
require_once 'config/database.php';

// Check if admin user exists
$email = 'admin@camexpress.cm';

try {
    $stmt = $pdo->prepare("SELECT user_id, full_name, email, role, password FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "<h2>✅ User Found!</h2>";
        echo "<pre>";
        print_r($user);
        echo "</pre>";

        // Check if password is hashed
        if (strlen($user['password']) > 30) {
            echo "<p>✅ Password is properly hashed.</p>";
        } else {
            echo "<p>❌ Password is NOT hashed correctly!</p>";
        }
    } else {
        echo "<h2>❌ User NOT Found!</h2>";
        echo "<p>Creating admin user...</p>";

        // Create admin user
        $hashed_password = password_hash('Admin123', PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (full_name, email, phone_number, id_number, password, role, created_at) 
                VALUES ('Admin User', 'admin@camexpress.cm', '+237 6XX XXX XXX', 'ADMIN001', :password, 'admin', NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['password' => $hashed_password]);

        echo "<p>✅ Admin user created successfully!</p>";
        echo "<p>Email: admin@camexpress.cm</p>";
        echo "<p>Password: Admin123</p>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
