<?php
/**
 * Debug Upload - Simple test to isolate the issue
 */

// Start clean
ob_start();
header('Content-Type: application/json');

// Test 1: Basic PHP execution
$debug = [
    'test' => 'Debug Upload API',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'steps' => []
];

$debug['steps'][] = 'Step 1: PHP execution OK';

// Test 2: Check if config loads
try {
    require_once __DIR__ . '/../config/config.php';
    $debug['steps'][] = 'Step 2: Config loaded OK';
    $debug['upload_url'] = defined('UPLOAD_URL') ? UPLOAD_URL : 'NOT DEFINED';
} catch (Exception $e) {
    $debug['steps'][] = 'Step 2 FAILED: ' . $e->getMessage();
    ob_end_clean();
    echo json_encode($debug);
    exit;
}

// Test 3: Check upload directory
$uploadDir = __DIR__ . '/../assets/uploads/';
$debug['upload_dir'] = $uploadDir;
$debug['dir_exists'] = file_exists($uploadDir) ? 'YES' : 'NO';
$debug['dir_writable'] = is_writable($uploadDir) ? 'YES' : 'NO';

if (!file_exists($uploadDir)) {
    $debug['steps'][] = 'Step 3: Directory does not exist, attempting to create...';
    if (@mkdir($uploadDir, 0755, true)) {
        $debug['steps'][] = 'Step 3: Directory created successfully';
    } else {
        $debug['steps'][] = 'Step 3 FAILED: Cannot create directory';
        $debug['error'] = error_get_last();
    }
} else {
    $debug['steps'][] = 'Step 3: Directory exists';
}

// Test 4: Check if Database class loads
try {
    require_once __DIR__ . '/../includes/Database.php';
    $debug['steps'][] = 'Step 4: Database class loaded OK';
} catch (Exception $e) {
    $debug['steps'][] = 'Step 4 FAILED: ' . $e->getMessage();
}

// Test 5: Check if Auth class loads
try {
    require_once __DIR__ . '/../includes/Auth.php';
    $debug['steps'][] = 'Step 5: Auth class loaded OK';
} catch (Exception $e) {
    $debug['steps'][] = 'Step 5 FAILED: ' . $e->getMessage();
}

// Test 6: Check if functions load
try {
    require_once __DIR__ . '/../includes/functions.php';
    $debug['steps'][] = 'Step 6: Functions loaded OK';
} catch (Exception $e) {
    $debug['steps'][] = 'Step 6 FAILED: ' . $e->getMessage();
}

// Test 7: Check if file was uploaded
if (isset($_FILES['file'])) {
    $debug['steps'][] = 'Step 7: File uploaded';
    $debug['file_info'] = [
        'name' => $_FILES['file']['name'],
        'size' => $_FILES['file']['size'],
        'type' => $_FILES['file']['type'],
        'error' => $_FILES['file']['error']
    ];
} else {
    $debug['steps'][] = 'Step 7: No file uploaded (this is OK for GET request)';
}

// Clean output and send
ob_end_clean();
echo json_encode($debug, JSON_PRETTY_PRINT);
