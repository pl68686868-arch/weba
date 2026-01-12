<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';

$db = Database::getInstance();

try {
    echo "Adding 'type' column to 'categories' table...\n";
    
    // Check if column exists
    $columns = $db->fetchAll("SHOW COLUMNS FROM categories LIKE 'type'");
    
    if (empty($columns)) {
        $sql = "ALTER TABLE categories ADD COLUMN type ENUM('post', 'podcast') DEFAULT 'post' AFTER slug";
        $db->query($sql);
        echo "Success: Column 'type' added.\n";
    } else {
        echo "Info: Column 'type' already exists.\n";
    }
    
    // Verify
    $columns = $db->fetchAll("SHOW COLUMNS FROM categories LIKE 'type'");
    print_r($columns);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
