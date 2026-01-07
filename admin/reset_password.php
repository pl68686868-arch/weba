<?php
// Admin Password Reset Tool
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';

echo "<h1>Admin Password Reset</h1>";

try {
    $db = Database::getInstance();
    $auth = new Auth();
    
    $username = 'admin';
    $password = 'admin123';
    $email = 'admin@example.com';
    
    // Check if user exists
    $user = $db->fetchOne("SELECT * FROM users WHERE username = :username", ['username' => $username]);
    
    if ($user) {
        // Update existing user
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $db->update('users', ['password_hash' => $hash], 'id = :id', ['id' => $user['id']]);
        echo "<div style='color:green; padding:20px; border:2px solid green;'>";
        echo "✅ Password updated successfully!<br>";
        echo "User: <strong>$username</strong><br>";
        echo "New Password: <strong>$password</strong>";
        echo "</div>";
    } else {
        // Create new user if not exists
        $data = [
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'full_name' => 'Administrator',
            'role' => 'admin'
        ];
        
        if ($auth->createUser($data)) {
            echo "<div style='color:green; padding:20px; border:2px solid green;'>";
            echo "✅ Admin user created successfully!<br>";
            echo "User: <strong>$username</strong><br>";
            echo "Password: <strong>$password</strong>";
            echo "</div>";
        } else {
            echo "<div style='color:red;'>Failed to create user.</div>";
        }
    }
    
    echo "<p><a href='/admin/login.php'>👉 Go to Login Page</a></p>";
    echo "<p style='color:red; font-size:12px;'>WARNING: Delete this file (admin/reset_password.php) immediately after use!</p>";

} catch (Exception $e) {
    echo "<div style='color:red;'>Error: " . $e->getMessage() . "</div>";
}
