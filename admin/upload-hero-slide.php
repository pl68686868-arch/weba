<?php
/**
 * Simple Upload for Settings (Hero Slides)
 * No DB logging, just save file and return URL
 */

// Clean output
while (@ob_end_clean());
header('Content-Type: application/json');

// Load config for UPLOAD_URL
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';

// Check auth
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// Check file
if (!isset($_FILES['file'])) {
    die(json_encode(['success' => false, 'message' => 'No file uploaded']));
}

$file = $_FILES['file'];
$uploadDir = __DIR__ . '/../assets/uploads/';

// Create dir
if (!file_exists($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// Validate type
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
    die(json_encode(['success' => false, 'message' => 'Invalid file type']));
}

// Generate filename
$filename = uniqid() . '.' . $ext;
$targetPath = $uploadDir . $filename;

// Move file
if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    echo json_encode([
        'success' => true,
        'data' => [
            'filename' => $filename,
            'url' => UPLOAD_URL . '/' . $filename
        ]
    ]);
} else {
    die(json_encode(['success' => false, 'message' => 'Upload failed']));
}
