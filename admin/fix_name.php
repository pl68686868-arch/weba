<?php
// Fix Admin Name Tool
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';

echo "<h1>Fix Admin Name</h1>";

try {
    $db = Database::getInstance();
    
    $username = 'admin';
    $newName = 'Dương Trần Minh Đoàn';
    
    // Update proper name
    $db->update('users', ['full_name' => $newName], 'username = :username', ['username' => $username]);
    
    echo "<div style='color:green; padding:20px; border:2px solid green;'>";
    echo "✅ Name updated successfully!<br>";
    echo "User: <strong>$username</strong><br>";
    echo "New Name: <strong>$newName</strong>";
    echo "</div>";
    
    echo "<p><a href='/admin/dashboard.php'>👉 Go to Dashboard</a></p>";
    echo "<p style='color:red; font-size:12px;'>WARNING: Delete this file (admin/fix_name.php) immediately after use!</p>";

} catch (Exception $e) {
    echo "<div style='color:red;'>Error: " . $e->getMessage() . "</div>";
}
