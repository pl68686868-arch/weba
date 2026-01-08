<?php declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

header('Content-Type: application/json');

$db = Database::getInstance();

// Fetch recent media items
$limit = (int)($_GET['limit'] ?? 50);
$mediaItems = $db->fetchAll("SELECT * FROM media ORDER BY created_at DESC LIMIT :limit", ['limit' => $limit]);

// Return only filenames - JavaScript will construct display URLs
// But store only filename in the input field
echo json_encode([
    'success' => true,
    'data' => $mediaItems
]);
