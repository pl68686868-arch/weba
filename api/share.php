<?php
declare(strict_types=1);

/**
 * Share API - Track social sharing
 * 
 * Track when users share posts on social media
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$postId = (int)($input['post_id'] ?? 0);
$platform = $input['platform'] ?? '';

if ($postId > 0 && !empty($platform)) {
    try {
        $db = Database::getInstance();
        
        $db->insert('social_shares', [
            'post_id' => $postId,
            'platform' => $platform,
            'ip_address' => getClientIP()
        ]);
        
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        error_log('Share tracking error: ' . $e->getMessage());
    }
}

jsonResponse(['success' => false], 400);
