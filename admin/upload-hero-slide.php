<?php
@ini_set('display_errors', '0');
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    die('{"success":false,"message":"Unauthorized"}');
}

if (!isset($_FILES['file'])) {
    die('{"success":false,"message":"No file"}');
}

$file = $_FILES['file'];
$dir = __DIR__ . '/../assets/uploads/';

if (!file_exists($dir)) {
    @mkdir($dir, 0755, true);
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
    die('{"success":false,"message":"Invalid type"}');
}

$name = uniqid() . '.' . $ext;
$path = $dir . $name;

if (move_uploaded_file($file['tmp_name'], $path)) {
    echo '{"success":true,"data":{"filename":"' . $name . '","url":"' . UPLOAD_URL . '/' . $name . '"}}';
} else {
    die('{"success":false,"message":"Failed"}');
}
