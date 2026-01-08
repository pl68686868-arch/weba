<?php declare(strict_types=1);

/**
 * Featured Image Upload Handler
 * 
 * Handles direct featured image uploads from post edit/create forms
 * 
 * @package Weba
 * @author Danny Duong
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['file'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed']);
    exit;
}

// Validate file size (10MB max)
$maxSize = 10 * 1024 * 1024; // 10MB
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 10MB']);
    exit;
}

try {
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '-featured.' . strtolower($extension);
    
    // Upload directory
    $uploadDir = __DIR__ . '/../assets/uploads/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $targetPath = $uploadDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    // Get image dimensions
    $imageInfo = getimagesize($targetPath);
    $width = $imageInfo[0] ?? null;
    $height = $imageInfo[1] ?? null;
    
    // Save to media library
    $db = Database::getInstance();
    $db->insert('media', [
        'filename' => $filename,
        'original_filename' => $file['name'],
        'file_path' => $filename, // Store only filename
        'file_type' => $extension,
        'file_size' => $file['size'],
        'mime_type' => $mimeType,
        'width' => $width,
        'height' => $height,
        'uploaded_by' => $auth->getUserId()
    ]);
    
    // Return success with filename (NOT full URL)
    echo json_encode([
        'success' => true,
        'filename' => $filename,
        'message' => 'Image uploaded successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Upload failed: ' . $e->getMessage()
    ]);
}
