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

<div class="admin-page">
    <div class="admin-page__header">
        <div>
            <h1>Thư viện Media</h1>
            <p>Quản lý hình ảnh và tài liệu tải lên hệ thống.</p>
        </div>
        <div class="admin-page__actions">
            <form method="GET" class="search-form-header">
                <div class="form-group mb-0">
                    <input type="text" name="search" class="form-control mr-2" placeholder="Tìm theo tên file..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <button type="submit" class="btn btn-secondary btn-icon-only">
                    🔍
                </button>
                <?php if (!empty($search)): ?>
                    <a href="/admin/media.php" class="btn btn-secondary btn-icon-only ml-2" title="Xóa tìm kiếm">↺</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Upload Card -->
    <div class="card upload-drop-card" id="uploadCard">
        <div id="dropZone" class="drop-zone">
            <div class="drop-icon">☁️</div>
            <h3 class="drop-title">Tải tệp tin mới lên</h3>
            <p class="drop-subtitle">
                <strong>Kéo thả tệp vào đây</strong> hoặc nhấp để chọn từ máy tính
            </p>
            <p class="drop-hint">
                Hỗ trợ: JPG, PNG, WEBP, PDF (Tối đa 10MB)
            </p>
            <input type="file" id="fileInput" name="file" class="hidden" accept="image/*,application/pdf">
        </div>

        <!-- Progress indication -->
        <div id="uploadProgress" class="upload-progress-container" style="display: none;">
            <div class="progress-info">
                <span id="progressText" class="progress-label">Đang tải lên...</span>
                <span id="percentText" class="progress-percent">0%</span>
            </div>
            <div class="progress-track">
                <div id="progressBarFill" class="progress-bar-fill"></div>
            </div>
        </div>
    </div>

    <!-- Media Grid -->
    <div class="media-grid" id="mediaGrid">
        <?php foreach ($mediaItems as $item): ?>
            <div class="card media-card p-0" data-media-id="<?= $item['id'] ?>">
                <div class="media-preview-container">
                    <?php if (in_array($item['file_type'], ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                        <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($item['file_path']) ?>" alt="<?= htmlspecialchars($item['original_filename']) ?>" class="media-img">
                    <?php else: ?>
                        <div class="file-placeholder">
                            <div class="file-icon">📄</div>
                            <span class="file-ext"><?= strtoupper($item['file_type']) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <button class="media-delete-btn" onclick="deleteMedia(<?= $item['id'] ?>, '<?= htmlspecialchars($item['original_filename']) ?>')" title="Xóa">
                        ✕
                    </button>
                    
                    <div class="media-action-overlay">
                        <input type="text" value="<?= UPLOAD_URL . '/' . htmlspecialchars($item['file_path']) ?>" readonly onclick="this.select(); event.stopPropagation();" class="media-url-input" title="Nhấn để sao chép link">
                    </div>
                </div>
                <div class="media-details">
                    <p class="media-filename" title="<?= htmlspecialchars($item['original_filename']) ?>">
                        <?= htmlspecialchars($item['original_filename']) ?>
                    </p>
                    <div class="media-meta">
                         <span><?= date('d/m/Y', strtotime($item['created_at'])) ?></span>
                         <span><?= isset($item['file_size']) ? (string)round($item['file_size'] / 1024, 1) . ' KB' : '' ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    .mb-0 { margin-bottom: 0; }
    .mr-2 { margin-right: 0.5rem; }
    .ml-2 { margin-left: 0.5rem; }
    .p-0 { padding: 0; }
    .hidden { display: none; }
    
    .search-form-header { display: flex; align-items: center; }
    .btn-icon-only { width: 44px; height: 44px; padding: 0; display: flex; align-items: center; justify-content: center; }
    
    .upload-drop-card { border: 2px dashed var(--border-color); background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(8px); margin-bottom: 40px; transition: var(--transition); }
    .upload-drop-card.drag-over { border-color: var(--color-primary); background: rgba(44, 95, 79, 0.05); transform: scale(1.005); }
    
    .drop-zone { text-align: center; cursor: pointer; padding: 24px; }
    .drop-icon { font-size: 3.5rem; margin-bottom: 16px; }
    .drop-title { margin-bottom: 8px; font-weight: 700; }
    .drop-subtitle { color: var(--text-muted); font-size: 1rem; }
    .drop-hint { font-size: 0.75rem; color: var(--text-muted); margin-top: 16px; opacity: 0.7; }
    
    .upload-progress-container { margin-top: 32px; padding: 0 40px 24px; }
    .progress-info { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.875rem; font-weight: 600; }
    .progress-track { height: 8px; background: rgba(0, 0, 0, 0.05); border-radius: 4px; overflow: hidden; }
    .progress-bar-fill { width: 0%; height: 100%; background: var(--color-primary); transition: width 0.3s ease; }
    
    .media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 32px; }
    .media-card { overflow: hidden; position: relative; border-radius: 12px; transform: translateZ(0); }
    .media-card:hover { transform: translateY(-8px); }
    
    .media-preview-container { aspect-ratio: 1/1; background: var(--bg-body); display: flex; align-items: center; justify-content: center; position: relative; cursor: zoom-in; }
    .media-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
    .media-card:hover .media-img { transform: scale(1.1); }
    
    .file-placeholder { display: flex; flex-direction: column; align-items: center; gap: 12px; }
    .file-icon { font-size: 3rem; }
    .file-ext { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.1em; }
    
    .media-delete-btn { position: absolute; top: 12px; right: 12px; width: 36px; height: 36px; border-radius: 10px; border: none; background: rgba(255, 255, 255, 0.95); box-shadow: var(--shadow-sm); display: flex; align-items: center; justify-content: center; color: #DC2626; opacity: 0; transform: scale(0.8); transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275); cursor: pointer; z-index: 2; }
    .media-card:hover .media-delete-btn { opacity: 1; transform: scale(1); }
    .media-delete-btn:hover { background: #DC2626; color: white; transform: scale(1.1); }
    
    .media-action-overlay { position: absolute; bottom: 0; left: 0; right: 0; padding: 16px; background: linear-gradient(to top, rgba(0,0,0,0.8), transparent); opacity: 0; transition: all 0.3s ease; z-index: 1; }
    .media-card:hover .media-action-overlay { opacity: 1; }
    .media-url-input { width: 100%; font-size: 0.7rem; padding: 8px 12px; background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(8px); color: white; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px; cursor: pointer; }
    .media-url-input:focus { border-color: rgba(255, 255, 255, 0.5); }
    
    .media-details { padding: 16px; background: var(--bg-card); }
    .media-filename { font-size: 0.875rem; font-weight: 600; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-main); }
    .media-meta { font-size: 0.75rem; color: var(--text-muted); margin-top: 6px; display: flex; justify-content: space-between; font-weight: 500; }
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
