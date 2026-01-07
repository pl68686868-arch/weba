<?php
// Simple script to check CKEditor version in posts files

echo "<h1>CKEditor Version Checker</h1>";

$files = [
    'posts-new.php' => __DIR__ . '/posts-new.php',
    'posts-edit.php' => __DIR__ . '/posts-edit.php'
];

foreach ($files as $name => $path) {
    echo "<h2>File: {$name}</h2>";
    
    if (!file_exists($path)) {
        echo "<p style='color: red;'>❌ File not found!</p>";
        continue;
    }
    
    $content = file_get_contents($path);
    
    // Find CKEditor CDN URL
    if (preg_match('/cdn\.ckeditor\.com\/([0-9.-]+)\//', $content, $matches)) {
        $version = $matches[1];
        
        if ($version === '4.25.1-lts') {
            echo "<p style='color: green; font-size: 18px;'>✅ <strong>Version: {$version}</strong> (Latest & Secure)</p>";
        } else {
            echo "<p style='color: red; font-size: 18px;'>⚠️ <strong>Version: {$version}</strong> (OLD - Needs Update!)</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Could not find CKEditor version</p>";
    }
    
    echo "<hr>";
}

echo "<h3>Summary</h3>";
echo "<p>If you see any RED warnings above, please:</p>";
echo "<ol>";
echo "<li>Download the UPDATED file from your local project</li>";
echo "<li>Upload it to <code>/home/iwzwumvq/public_html/admin/</code></li>";
echo "<li>Hard refresh your browser (Ctrl+F5 or Cmd+Shift+R)</li>";
echo "</ol>";
