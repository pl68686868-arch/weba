<?php
// ULTRA MINIMAL VERSION - NO EXTRA OUTPUT
@ob_end_clean(); // Kill any existing buffers
ob_start();

// Suppress ALL errors from displaying
@ini_set('display_errors', '0');
@error_reporting(0);

// Load dependencies silently
@require_once __DIR__ . '/../config/config.php';
@require_once __DIR__ . '/../includes/Database.php';
@require_once __DIR__ . '/../includes/Auth.php';
@require_once __DIR__ . '/../includes/functions.php';

// Function to send clean JSON and die
function sendJson($data) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Check auth
try {
    $auth = new Auth();
    $auth->requireLogin();
} catch (Exception $e) {
    sendJson(['success' => false, 'message' => 'Authentication failed']);
}

// Check method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['success' => false, 'message' => 'Method not allowed']);
}

// Check file
if (!isset($_FILES['file'])) {
    sendJson(['success' => false, 'message' => 'No file uploaded']);
}

$file = $_FILES['file'];
$uploadDir = __DIR__ . '/../assets/uploads/';

// Create directory if needed
if (!file_exists($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// Check writable
if (!is_writable($uploadDir)) {
    sendJson(['success' => false, 'message' => 'Upload directory not writable']);
}

// Check upload error
if ($file['error'] !== UPLOAD_ERR_OK) {
    sendJson(['success' => false, 'message' => 'Upload error: ' . $file['error']]);
}

// Check file type
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];

if (!in_array($ext, $allowed)) {
    sendJson(['success' => false, 'message' => 'File type not supported']);
}

// Generate filename
$filename = uniqid() . '-' . createSlug(pathinfo($file['name'], PATHINFO_FILENAME)) . '.' . $ext;
$targetPath = $uploadDir . $filename;

// Move file
if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    sendJson(['success' => false, 'message' => 'Failed to save file']);
}

// Save to database
try {
    $db = Database::getInstance();
    $db->insert('media', [
        'filename' => $filename,
        'original_filename' => $file['name'],
        'file_path' => $filename,
        'file_type' => $ext,
        'file_size' => $file['size'],
        'mime_type' => $file['type'],
        'uploaded_by' => $auth->getUserId()
    ]);
    
    $mediaId = $db->lastInsertId();
    
    // Success!
    sendJson([
        'success' => true,
        'message' => 'Upload successful',
        'data' => [
            'id' => $mediaId,
            'filename' => $filename,
            'original_filename' => $file['name'],
            'url' => UPLOAD_URL . '/' . $filename
        ]
    ]);
    
} catch (Exception $e) {
    @unlink($targetPath);
    sendJson(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
