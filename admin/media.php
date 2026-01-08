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

// Handle Upload via API now (Legacy code removed)

// Handle Search
$search = trim($_GET['search'] ?? '');
$sql = "SELECT * FROM media";
$params = [];

if (!empty($search)) {
    $sql .= " WHERE original_filename LIKE :search OR filename LIKE :search";
    $params['search'] = "%{$search}%";
}

$sql .= " ORDER BY created_at DESC LIMIT 50";

// Fetch Media
$mediaItems = $db->fetchAll($sql, $params);

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-header" style="justify-content: space-between; display: flex; align-items: center;">
    <h1>Quản lý Media</h1>
    <form method="GET" style="display: flex; gap: 8px;">
        <input type="text" name="search" class="form-input" placeholder="Search files..." value="<?= htmlspecialchars($search) ?>" style="width: 200px;">
        <button type="submit" class="btn btn-secondary">🔍</button>
    </form>
</div>

<div class="card mb-4" id="uploadCard" style="padding: 24px; transition: all 0.2s;">
    <h3>Upload File Mới</h3>
    
    <div id="dropZone" class="drop-zone">
        <div class="drop-zone__icon">☁️</div>
        <div class="drop-zone__text">
            <strong>Kéo thả file vào đây</strong> hoặc click để chọn file
        </div>
        <small class="text-muted">Hỗ trợ: JPG, PNG, WEBP, PDF (Max 10MB)</small>
        <input type="file" id="fileInput" name="file" style="display: none;" accept="image/*,application/pdf">
    </div>

    <!-- Progress indication -->
    <div id="uploadProgress" class="upload-progress" style="display: none;">
        <div class="progress-bar">
            <div id="progressBarFill" class="progress-bar__fill" style="width: 0%"></div>
        </div>
        <span id="progressText">Đang upload...</span>
    </div>
</div>

<div class="media-grid" id="mediaGrid">
    <?php foreach ($mediaItems as $item): ?>
        <div class="media-item" data-media-id="<?= $item['id'] ?>">
            <div class="media-preview">
                <?php if (in_array($item['file_type'], ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                    <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($item['file_path']) ?>" alt="<?= htmlspecialchars($item['original_filename']) ?>">
                <?php else: ?>
                    <div class="file-icon">📄</div>
                <?php endif; ?>
                <button class="delete-btn" onclick="deleteMedia(<?= $item['id'] ?>, '<?= htmlspecialchars($item['original_filename']) ?>')" title="Xóa file">
                    🗑️
                </button>
            </div>
            <div class="media-info">
                <p class="filename" title="<?= htmlspecialchars($item['original_filename']) ?>">
                    <?= htmlspecialchars($item['original_filename']) ?>
                </p>
                <div class="media-actions">
                    <input type="text" value="<?= UPLOAD_URL . '/' . htmlspecialchars($item['file_path']) ?>" readonly onclick="this.select()" class="url-input">
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<style>
    .drop-zone {
        border: 2px dashed #ccc;
        border-radius: 8px;
        padding: 40px;
        text-align: center;
        background: #f9f9f9;
        cursor: pointer;
        transition: all 0.2s;
    }
    .drop-zone:hover, .drop-zone.drag-over {
        border-color: #2C5F4F;
        background: #e6f0ed;
    }
    .drop-zone__icon {
        font-size: 48px;
        margin-bottom: 16px;
    }
    .drop-zone__text {
        font-size: 16px;
        margin-bottom: 8px;
        color: #333;
    }
    
    .upload-progress {
        margin-top: 16px;
    }
    .progress-bar {
        height: 6px;
        background: #eee;
        border-radius: 3px;
        overflow: hidden;
        margin-bottom: 8px;
    }
    .progress-bar__fill {
        height: 100%;
        background: #2C5F4F;
        transition: width 0.3s ease;
    }

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
        position: relative;
        transition: transform 0.2s;
    }
    .media-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    /* ... reuse previous styles ... */
    .media-preview {
        height: 150px;
        background: #f9f9f9;
        display: flex;
        align-items: center;
        justify-content: center;
        border-bottom: 1px solid #eee;
        position: relative;
    }
    .media-preview img {
        max-width: 100%;
        max-height: 100%;
        object-fit: cover;
    }
    .delete-btn {
        position: absolute;
        top: 8px;
        right: 8px;
        background: rgba(255, 255, 255, 0.9);
        border: none;
        border-radius: 4px;
        width: 32px;
        height: 32px;
        font-size: 18px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        opacity: 0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .media-item:hover .delete-btn {
        opacity: 1;
    }
    .delete-btn:hover {
        background: #ef4444;
        transform: scale(1.1);
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
    .text-muted { color: #666; font-size: 0.9em; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const progressDiv = document.getElementById('uploadProgress');
    const progressBarFill = document.getElementById('progressBarFill');
    const progressText = document.getElementById('progressText');
    const mediaGrid = document.getElementById('mediaGrid');

    // Click handler
    dropZone.addEventListener('click', () => fileInput.click());

    // Drag & Drop handlers
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
        dropZone.classList.add('drag-over');
    }

    function unhighlight(e) {
        dropZone.classList.remove('drag-over');
    }

    dropZone.addEventListener('drop', handleDrop, false);
    fileInput.addEventListener('change', handleFiles, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles({ target: { files: files } });
    }

    async function handleFiles(e) {
        const file = e.target.files[0];
        if (!file) return;

        uploadFile(file);
    }

    async function uploadFile(file) {
        // Validation
        if (file.size > 10 * 1024 * 1024) {
            alert('File quá lớn! (Max 10MB)');
            return;
        }

        // UI Reset
        progressDiv.style.display = 'block';
        progressBarFill.style.width = '0%';
        progressText.textContent = `Đang upload ${file.name}...`;

        const formData = new FormData();
        formData.append('file', file);

        try {
            const res = await fetch('/api/upload-media.php', {
                method: 'POST',
                body: formData
            });

            const data = await res.json();

            if (data.success) {
                progressBarFill.style.width = '100%';
                progressText.textContent = 'Upload thành công!';
                
                prependMediaItem(data.data);
                
                setTimeout(() => {
                    progressDiv.style.display = 'none';
                }, 2000);
            } else {
                throw new Error(data.message);
            }
        } catch (err) {
            console.error(err);
            progressText.textContent = 'Lỗi: ' + err.message;
            progressText.style.color = 'red';
        }
    }

    function prependMediaItem(item) {
        const div = document.createElement('div');
        div.className = 'media-item';
        div.dataset.mediaId = item.id;
        
        // Simple template literal for new item
        const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(item.type);
        const previewHtml = isImage 
            ? `<img src="${item.url}" alt="${item.original_filename}">`
            : `<div class="file-icon">📄</div>`;

        div.innerHTML = `
            <div class="media-preview">
                ${previewHtml}
                <button class="delete-btn" onclick="deleteMedia(${item.id}, '${item.original_filename}')" title="Xóa file">🗑️</button>
            </div>
            <div class="media-info">
                <p class="filename" title="${item.original_filename}">${item.original_filename}</p>
                <div class="media-actions">
                    <input type="text" value="${item.url}" readonly onclick="this.select()" class="url-input">
                </div>
            </div>
        `;

        // Add to grid with animation
        div.style.opacity = '0';
        div.style.transform = 'translateY(-20px)';
        mediaGrid.prepend(div);
        
        // Trigger reflow
        div.offsetHeight;
        
        div.style.opacity = '1';
        div.style.transform = 'translateY(0)';
    }
});
</script>
<script>
async function deleteMedia(mediaId, filename) {
    if (!confirm(`Bạn có chắc muốn xóa file "${filename}"?\nHành động này không thể hoàn tác!`)) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('id', mediaId);
        
        const response = await fetch('/api/delete-media.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Remove from DOM
            const mediaItem = document.querySelector(`[data-media-id="${mediaId}"]`);
            if (mediaItem) {
                mediaItem.style.opacity = '0';
                mediaItem.style.transform = 'scale(0.8)';
                setTimeout(() => mediaItem.remove(), 300);
            }
            
            // Show success message
            alert(result.message || 'Đã xóa file thành công!');
        } else {
            alert('Lỗi: ' + (result.message || 'Không thể xóa file'));
        }
    } catch (error) {
        console.error('Delete error:', error);
        alert('Có lỗi xảy ra khi xóa file');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
