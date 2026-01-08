<?php declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$mediaId = (int)($_POST['id'] ?? 0);

if ($mediaId === 0) {
    echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
    exit;
}

$db = Database::getInstance();

try {
    // Get media info
    $media = $db->fetchOne("SELECT * FROM media WHERE id = :id", ['id' => $mediaId]);
    
    if (!$media) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy file']);
        exit;
    }
    
    // Delete physical file
    $filePath = __DIR__ . '/../assets/uploads/' . $media['filename'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    // Delete from database
    $db->delete('media', 'id = :id', ['id' => $mediaId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã xóa file thành công'
    ]);
    
} catch (Exception $e) {
    error_log('Delete media error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi xóa file: ' . $e->getMessage()
    ]);
}
