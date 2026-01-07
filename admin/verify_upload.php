<?php
// Check if uploaded files contain licenseKey fix

echo "<h1>Server File Verification</h1>";

$files = [
    'test_ckeditor.html' => __DIR__ . '/test_ckeditor.html',
    'posts-new.php' => __DIR__ . '/posts-new.php',
    'posts-edit.php' => __DIR__ . '/posts-edit.php'
];

foreach ($files as $name => $path) {
    echo "<h2>{$name}</h2>";
    
    if (!file_exists($path)) {
        echo "<p style='color: red;'>❌ File NOT FOUND on server!</p>";
        continue;
    }
    
    $content = file_get_contents($path);
    
    // Check if file contains the licenseKey fix
    if (strpos($content, "licenseKey: ''") !== false || strpos($content, 'licenseKey: ""') !== false) {
        echo "<p style='color: green; font-size: 18px;'>✅ <strong>UPDATED</strong> - Contains licenseKey fix</p>";
    } else {
        echo "<p style='color: red; font-size: 18px;'>❌ <strong>OLD VERSION</strong> - Missing licenseKey!</p>";
        echo "<p><strong>Action needed:</strong> Upload the NEW version of this file</p>";
    }
    
    // Show file modification time
    $modTime = filemtime($path);
    echo "<p><small>Last modified: " . date('Y-m-d H:i:s', $modTime) . "</small></p>";
    
    echo "<hr>";
}

echo "<h3>Quick Fix Instructions:</h3>";
echo "<ol>";
echo "<li>Re-download these 3 files from your LOCAL project</li>";
echo "<li>Upload to <code>/home/iwzwumvq/public_html/admin/</code></li>";
echo "<li><strong>IMPORTANT:</strong> Make sure to click 'Overwrite' when uploading</li>";
echo "<li>Hard refresh browser: <kbd>Ctrl+Shift+R</kbd> (Windows) or <kbd>Cmd+Shift+R</kbd> (Mac)</li>";
echo "</ol>";
