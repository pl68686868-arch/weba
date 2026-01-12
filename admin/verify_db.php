<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';

$db = Database::getInstance();

echo "Checking database schema...<br>";

// 1. Check if column exists
$checkCol = $db->fetchAll("SHOW COLUMNS FROM categories LIKE 'type'");
if (empty($checkCol)) {
    echo "Column 'type' MISSING. Attempting to add...<br>";
    try {
        $db->query("ALTER TABLE categories ADD COLUMN type ENUM('post', 'podcast') DEFAULT 'post' AFTER slug");
        echo "Column 'type' ADDED successfully.<br>";
    } catch (Exception $e) {
        echo "Error adding column: " . $e->getMessage() . "<br>";
    }
} else {
    echo "Column 'type' EXISTS.<br>";
}

// 2. Show sample data
echo "<br>Checking sample category data:<br>";
$cats = $db->fetchAll("SELECT id, name, type FROM categories LIMIT 5");
foreach ($cats as $c) {
    echo "ID: {$c['id']} | Name: {$c['name']} | Type: " . ($c['type'] ?? 'NULL') . "<br>";
}

echo "<br>Done.";
