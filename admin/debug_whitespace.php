<?php
// Force error display
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>Whitespace Diagnostic Tool</h1>";
echo "<p>Checking for premature output in core files...</p>";

// Function to check headers
function check_headers($filename) {
    if (headers_sent($file, $line)) {
        echo "<div style='color:white; background:red; padding:20px; border-radius:8px; margin:20px 0;'>";
        echo "<h2>❌ CRITICAL ERROR DETECTED</h2>";
        echo "<p><strong>Output started prematurely by:</strong> $file</p>";
        echo "<p><strong>Line number:</strong> $line</p>";
        echo "<p><strong>Caused when loading:</strong> $filename</p>";
        echo "<p>This is what causes the 500 Error. Please remove any whitespace/newlines before <code>&lt;?php</code> or after <code>?&gt;</code> in that file.</p>";
        echo "</div>";
        exit;
    } else {
        echo "<div style='color:green; border:1px solid green; padding:10px; margin:10px 0;'>";
        echo "✅ Loaded <strong>$filename</strong> successfully. No whitespace detected.";
        echo "</div>";
    }
}

// 1. Check Config
echo "<h3>1. Loading Config...</h3>";
require_once __DIR__ . '/../config/config.php';
check_headers('config.php');

// 2. Load Database
echo "<h3>2. Loading Database...</h3>";
require_once __DIR__ . '/../includes/Database.php';
check_headers('Database.php');

// 3. Load Functions
echo "<h3>3. Loading Functions...</h3>";
require_once __DIR__ . '/../includes/functions.php';
check_headers('functions.php');

// 4. Load Auth
echo "<h3>4. Loading Auth...</h3>";
require_once __DIR__ . '/../includes/Auth.php';
check_headers('Auth.php');

echo "<div style='color:white; background:green; padding:20px; border-radius:8px; margin:20px 0;'>";
echo "<h2>✅ ALL CORE FILES CLEAN</h2>";
echo "<p>No whitespace detected in the checked files.</p>";
echo "<p>If you still get 500 Error, the issue might be in <strong>admin/login.php</strong> itself or server configuration (permission/htaccess).</p>";
echo "</div>";
?>
