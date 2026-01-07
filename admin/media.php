<?php declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();
$db = Database::getInstance();

$uploadDir = __DIR__ . '/../assets/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$error = '';
$success = '';

// Handle Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Lỗi upload file: Code ' . $file['error'];
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
        
        if (!in_array($ext, $allowed)) {
            $error = 'Định dạng file không hỗ trợ.';
        } else {
            // Generate safe filename
            $filename = uniqid() . '-' . createSlug(pathinfo($file['name'], PATHINFO_FILENAME)) . '.' . $ext;
            $targetPath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                // Insert into DB
                $url = '/assets/uploads/' . $filename;
                $db->insert('media', [
                    'filename' => $filename,
                    'original_filename' => $file['name'],
                    'file_path' => $url,
                    'file_type' => $ext,
                    'file_size' => $file['size'],
                    'mime_type' => $file['type'],
                    'uploaded_by' => $_SESSION['user_id']
                ]);
                
                $success = 'Upload thành công!';
            } else {
                $error = 'Không thể lưu file vào thư mục đích.';
            }
        }
    }
}

// Fetch Media
$mediaItems = $db->fetchAll("SELECT * FROM media ORDER BY created_at DESC LIMIT 50");

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-header">
    <h1>Quản lý Media</h1>
</div>

<?php if ($error): ?> <div class="alert alert-error"><?= htmlspecialchars($error) ?></div> <?php endif; ?>
<?php if ($success): ?> <div class="alert alert-success"><?= htmlspecialchars($success) ?></div> <?php endif; ?>

<div class="card mb-4" style="padding: 24px;">
    <h3>Upload File Mới</h3>
    <form method="POST" enctype="multipart/form-data" style="display: flex; gap: 10px; align-items: center;">
        <input type="file" name="file" required>
        <button type="submit" class="btn">Upload Ngay</button>
    </form>
    <small style="margin-top: 8px; display: block;">Hỗ trợ: JPG, PNG, WEBP, PDF (Max 5MB)</small>
</div>

<div class="media-grid">
    <?php foreach ($mediaItems as $item): ?>
        <div class="media-item">
            <div class="media-preview">
                <?php if (in_array($item['file_type'], ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                    <img src="<?= htmlspecialchars($item['file_path']) ?>" alt="<?= htmlspecialchars($item['original_filename']) ?>">
                <?php else: ?>
                    <div class="file-icon">📄</div>
                <?php endif; ?>
            </div>
            <div class="media-info">
                <p class="filename" title="<?= htmlspecialchars($item['original_filename']) ?>">
                    <?= htmlspecialchars($item['original_filename']) ?>
                </p>
                <div class="media-actions">
                    <input type="text" value="<?= htmlspecialchars($item['file_path']) ?>" readonly onclick="this.select()" class="url-input">
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<style>
    .media-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 20px;
    }
    .media-item {
        background: white;
        border: 1px solid #eee;
        border-radius: 8px;
        overflow: hidden;
    }
    .media-preview {
        height: 150px;
        background: #f9f9f9;
        display: flex;
        align-items: center;
        justify-content: center;
        border-bottom: 1px solid #eee;
    }
    .media-preview img {
        max-width: 100%;
        max-height: 100%;
        object-fit: cover;
    }
    .media-info {
        padding: 12px;
    }
    .filename {
        font-size: 13px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 8px;
    }
    .url-input {
        width: 100%;
        font-size: 12px;
        padding: 4px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background: #f5f5f5;
        cursor: pointer;
    }
    .file-icon {
        font-size: 40px;
    }
    .mb-4 { margin-bottom: 24px; }
</style>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
