<?php declare(strict_types=1);

// Diagnostic script to identify post save errors
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/functions.php';

echo "<h1>POST Save Debug</h1>";
echo "<h2>1. Authentication Check</h2>";
try {
    $auth = new Auth();
    $auth->requireLogin();
    echo "✅ Authentication OK<br>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
} catch (Exception $e) {
    echo "❌ Auth Error: " . $e->getMessage() . "<br>";
    die();
}

echo "<h2>2. Database Connection</h2>";
try {
    $db = Database::getInstance();
    echo "✅ Database connected<br>";
} catch (Exception $e) {
    echo "❌ DB Error: " . $e->getMessage() . "<br>";
    die();
}

echo "<h2>3. Posts Table Structure</h2>";
try {
    $pdo = $db->getPDO();
    $stmt = $pdo->query("DESCRIBE posts");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        $nullStyle = $col['Null'] === 'NO' ? 'background-color: #ffe6e6;' : '';
        echo "<tr style='{$nullStyle}'>";
        echo "<td><strong>" . $col['Field'] . "</strong></td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p><em>Red rows = NOT NULL (must be provided)</em></p>";
} catch (Exception $e) {
    echo "❌ Table Error: " . $e->getMessage() . "<br>";
}

echo "<h2>4. Test Insert Simulation</h2>";
echo "<p>Simulating what happens when you save a post...</p>";

// Simulate form data
$testData = [
    'title' => 'Test Post ' . date('Y-m-d H:i:s'),
    'slug' => 'test-post-' . time(),
    'content' => '<p>This is test content.</p>',
    'excerpt' => 'Test excerpt',
    'category_id' => 1, // Assuming category 1 exists
    'status' => 'draft',
    'featured_image' => '',
    'meta_title' => 'Test Meta Title',
    'meta_description' => 'Test meta description',
    'author_id' => $_SESSION['user_id']
];

echo "<h3>Data to insert:</h3>";
echo "<pre>" . print_r($testData, true) . "</pre>";

try {
    $db->beginTransaction();
    $postId = $db->insert('posts', $testData);
    $db->rollback(); // Don't actually save it
    echo "✅ Test insert would succeed! (rolled back, no actual data saved)<br>";
    echo "Generated Post ID would be: {$postId}<br>";
} catch (Exception $e) {
    $db->rollback();
    echo "❌ Insert FAILED with error:<br>";
    echo "<pre style='background-color: #ffe6e6; padding: 10px; border: 2px solid red;'>";
    echo htmlspecialchars($e->getMessage());
    echo "</pre>";
    
    // Try to get more detailed PDO error
    try {
        $pdo = $db->getPDO();
        $errorInfo = $pdo->errorInfo();
        if ($errorInfo[0] !== '00000') {
            echo "<h4>PDO Error Details:</h4>";
            echo "<pre>";
            echo "SQLSTATE: " . $errorInfo[0] . "\n";
            echo "Driver Error Code: " . $errorInfo[1] . "\n";
            echo "Driver Error Message: " . $errorInfo[2] . "\n";
            echo "</pre>";
        }
    } catch (Exception $e2) {
        echo "Could not get PDO error info: " . $e2->getMessage();
    }
}

echo "<h2>5. Categories Check</h2>";
try {
    $categories = $db->fetchAll("SELECT id, name FROM categories LIMIT 5");
    if (empty($categories)) {
        echo "⚠️ <strong>WARNING: No categories found!</strong> You need at least one category before creating a post.<br>";
    } else {
        echo "✅ Found " . count($categories) . " categories:<br>";
        foreach ($categories as $cat) {
            echo "- ID {$cat['id']}: {$cat['name']}<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Categories Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><a href='/admin/posts-new.php'>← Back to Create Post</a></p>";
