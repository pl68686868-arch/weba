<?php
// Admin Login Debugger
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/functions.php';

echo "<h1>Login Logic Debugger</h1>";

try {
    $db = Database::getInstance();
    $auth = new Auth();
    
    $username = 'admin';
    $password = 'admin123';
    
    echo "<h3>1. Testing Database Connection</h3>";
    echo "✅ Connected to: " . DB_NAME . "<br>";
    
    // Use raw PDO to bypass generic exception masking in Database class
    try {
        $sql = "SELECT id, username, email, password_hash, role, full_name 
                FROM users 
                WHERE (username = :username OR email = :email) 
                LIMIT 1";
        
        $stmt = $db->getPDO()->prepare($sql);
        $stmt->execute(['username' => $username, 'email' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("SQL Error: " . $e->getMessage());
    }
    
    if ($user) {
        echo "✅ User found: ID=" . $user['id'] . ", Role=" . $user['role'] . "<br>";
        echo "Stored Hash: " . substr($user['password_hash'], 0, 10) . "...<br>";
    } else {
        echo "❌ User '$username' NOT FOUND in database.<br>";
        exit;
    }
    
    echo "<h3>3. Verifying Password</h3>";
    $verify = password_verify($password, $user['password_hash']);
    if ($verify) {
        echo "✅ password_verify() returned TRUE. Password is correct.<br>";
    } else {
        echo "❌ password_verify() returned FALSE. Password mismatch.<br>";
        echo "Hash Algorithm info: <pre>" . print_r(password_get_info($user['password_hash']), true) . "</pre>";
        exit;
    }
    
    echo "<h3>4. Testing Session Regeneration (Common Crash Point)</h3>";
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        echo "Started new session.<br>";
    }
    
    // Test exact logic from Auth::login
    try {
        if (!headers_sent()) {
             session_regenerate_id(true);
             echo "✅ session_regenerate_id(true) successful.<br>";
        } else {
             echo "⚠️ Headers already sent! Cannot regenerate session.<br>";
             // This warning is usually not fatal unless strict error handler
        }
    } catch (Throwable $e) {
        echo "❌ CRITICAL EXCEPTION during session_regenerate: " . $e->getMessage() . "<br>";
        echo "Trace: " . $e->getTraceAsString();
        exit;
    }
    
    echo "<h3>5. Full Auth::login() Simulation</h3>";
    // We try the actual method
    if ($auth->login($username, $password)) {
        echo "<div style='background:green; color:white; padding:20px;'>";
        echo "✅ Auth::login() returned TRUE. Login should work!";
        echo "</div>";
    } else {
        echo "<div style='background:red; color:white; padding:20px;'>";
        echo "❌ Auth::login() returned FALSE.";
        echo "</div>";
    }

} catch (Throwable $e) {
    echo "<div style='background:red; color:white; padding:20px;'>";
    echo "Global Exception: " . $e->getMessage() . "<br>";
    echo "Trace: <pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}
?>
