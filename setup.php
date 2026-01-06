<?php
declare(strict_types=1);

/**
 * Database Setup and Test Script
 * 
 * This script imports the schema and tests the database connection
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';

echo "<!DOCTYPE html>\n";
echo "<html lang='vi'>\n<head>\n";
echo "<meta charset='UTF-8'>\n";
echo "<title>Database Setup</title>\n";
echo "<style>body {font-family: monospace; padding: 20px; background: #f5f5f5;} .success {color: green;} .error {color: red;} pre {background: white; padding: 10px; border: 1px solid #ddd;}</style>\n";
echo "</head>\n<body>\n";
echo "<h1>Database Setup Script</h1>\n";

try {
    // Read schema file
    $schemaFile = __DIR__ . '/database/schema.sql';
    
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: {$schemaFile}");
    }
    
    $sql = file_get_contents($schemaFile);
    
    if ($sql === false) {
        throw new Exception("Could not read schema file");
    }
    
    echo "<p>✓ Schema file loaded</p>\n";
    
    // Connect to MySQL without selecting database
    $dsn = sprintf("mysql:host=%s;charset=%s", DB_HOST, DB_CHARSET);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<p class='success'>✓ Connected to MySQL server</p>\n";
    
    // Execute schema - split by semicolons (simple approach)
    // Note: This is a simplified approach. For production, use proper SQL parser
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^(--|\/\*|\#)/', $stmt) &&
                   strlen($stmt) > 10;
        }
    );
    
    echo "<p>Found " . count($statements) . " SQL statements</p>\n";
    
    foreach ($statements as $i => $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Ignore some expected errors
                if (strpos($e->getMessage(), 'database exists') === false) {
                    echo "<p class='error'>Warning on statement " . ($i + 1) . ": " . htmlspecialchars($e->getMessage()) . "</p>\n";
                }
            }
        }
    }
    
    echo "<p class='success'>✓ Schema executed successfully</p>\n";
    
    // Test database connection using our Database class
    $db = Database::getInstance();
    
    echo "<p class='success'>✓ Database class initialized</p>\n";
    
    // Test: Count tables
    $tables = $db->fetchAll("SHOW TABLES FROM " . DB_NAME);
    echo "<p class='success'>✓ Database '" . DB_NAME . "' has " . count($tables) . " tables</p>\n";
    
    echo "<h2>Tables Created:</h2>\n<pre>\n";
    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        $count = $db->fetchColumn("SELECT COUNT(*) FROM {$tableName}");
        echo str_pad($tableName, 30) . " ({$count} rows)\n";
    }
    echo "</pre>\n";
    
    // Test: Verify default data
    echo "<h2>Default Data:</h2>\n";
    
    $adminUser = $db->fetchOne("SELECT id, username, email, role FROM users WHERE role = 'admin' LIMIT 1");
    if ($adminUser) {
        echo "<p class='success'>✓ Admin user exists:</p>\n<pre>";
        echo "  Username: {$adminUser['username']}\n";
        echo "  Email: {$adminUser['email']}\n";
        echo "  Default password: admin123 (PLEASE CHANGE THIS!)\n";
        echo "</pre>\n";
    }
    
    $categories = $db->fetchAll("SELECT name FROM categories ORDER BY display_order");
    if (count($categories) > 0) {
        echo "<p class='success'>✓ " . count($categories) . " categories (4 Pillars) created:</p>\n<pre>\n";
        foreach ($categories as $cat) {
            echo "  - {$cat['name']}\n";
        }
        echo "</pre>\n";
    }
    
    $languages = $db->fetchAll("SELECT code, name, is_default FROM languages");
    if (count($languages) > 0) {
        echo "<p class='success'>✓ " . count($languages) . " languages configured:</p>\n<pre>\n";
        foreach ($languages as $lang) {
            $default = $lang['is_default'] ? ' (default)' : '';
            echo "  - {$lang['name']} ({$lang['code']}){$default}\n";
        }
        echo "</pre>\n";
    }
    
    echo "<hr>\n";
    echo "<h2 class='success'>✅ Database setup completed successfully!</h2>\n";
    echo "<p><strong>Next steps:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Access admin panel: <a href='/admin/login.php'>/admin/login.php</a></li>\n";
    echo "<li>Login with: admin / admin123</li>\n";
    echo "<li><strong style='color:red;'>IMPORTANT: Change the default admin password immediately!</strong></li>\n";
    echo "</ul>\n";
    
    // Security reminder
    echo "<hr>\n";
    echo "<p style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; border-radius: 5px;'>\n";
    echo "<strong>⚠️ Security Warning:</strong><br>\n";
    echo "For production deployment:<br>\n";
    echo "1. Delete this setup.php file<br>\n";
    echo "2. Change the default admin password<br>\n";
    echo "3. Update config.php with production database credentials<br>\n";
    echo "4. Set display_errors to '0' in config.php<br>\n";
    echo "5. Enable HTTPS in .htaccess<br>\n";
    echo "</p>\n";
    
} catch (PDOException $e) {
    echo "<p class='error'>✗ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "</body>\n</html>";
