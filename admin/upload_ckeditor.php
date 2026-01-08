<?php declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

header('Content-Type: application/json');

$uploadDir = __DIR__ . '/../assets/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (isset($_FILES['upload']) && $_FILES['upload']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['upload'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (in_array($ext, $allowed)) {
        // Safe filename
        $filename = uniqid() . '-' . createSlug(pathinfo($file['name'], PATHINFO_FILENAME)) . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // CKEditor 4 & 5 compatible response (mostly for CK4 now)
            // Return full path for CKEditor to insert into content
            echo json_encode([
                "uploaded" => 1,
                "fileName" => $filename,
                "url" => "/assets/uploads/" . $filename
            ]);
            
            // Optional: Insert into media table for record keeping
            // IMPORTANT: Store ONLY filename, not full path
            // The path will be constructed using UPLOAD_URL when rendering
            try {
                $db = Database::getInstance();
                $db->insert('media', [
                    'filename' => $filename,
                    'original_filename' => $file['name'],
                    'file_path' => $filename,  // Store only filename
                    'file_type' => $ext,
                    'file_size' => $file['size'],
                    'mime_type' => $file['type'],
                    'uploaded_by' => $_SESSION['user_id']
                ]);
            } catch (Exception $e) {
                // Ignore DB error for upload response, file is safe
            }
            exit;
        }
    }
}

// Error response
echo json_encode([
    "error" => [
        "message" => "Không thể upload ảnh. Vui lòng kiểm tra định dạng hoặc thử lại."
    ]
]);
