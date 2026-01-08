<?php declare(strict_types=1);

/**
 * Upload Media API
 * 
 * Handles file uploads via AJAX/Drag & Drop
 * 
 * @package Weba
 * @author Danny Duong
 */

header('Content-Type: application/json');
ob_start();
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];
$uploadDir = __DIR__ . '/../assets/uploads/';

// Debug paths
if (!file_exists($uploadDir)) {
    if (!@mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        $error = error_get_last();
        echo json_encode(['success' => false, 'message' => 'Cannot create upload directory: ' . ($error['message'] ?? '')]);
        exit;
    }
}

if (!is_writable($uploadDir)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Upload directory is not writable. Please CHMOD 755 assets/uploads']);
    exit;
}

if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Upload error code: ' . $file['error']]);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];

if (!in_array($ext, $allowed)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File type not supported']);
    exit;
}

// Generate safe filename
$filename = uniqid() . '-' . createSlug(pathinfo($file['name'], PATHINFO_FILENAME)) . '.' . $ext;
$targetPath = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $db = Database::getInstance();
    
    try {
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
        
        // Clear any previous output (whitespace, warnings)
        ob_clean();
        
        echo json_encode([
            'success' => true,
            'message' => 'Upload successful',
            'data' => [
                'id' => $mediaId,
                'filename' => $filename,
                'original_filename' => $file['name'],
                'url' => UPLOAD_URL . '/' . $filename
            ]
        ]);
        exit;
        
    } catch (Exception $e) {
        // If DB fails, remove file?
        @unlink($targetPath);
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
} else {
    ob_clean();
    http_response_code(500);
    $error = error_get_last();
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file. Check permissions. ' . ($error['message'] ?? '')]);
    exit;
}
