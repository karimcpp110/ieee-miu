<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>IEEE MIU Portal - Diagnostics</h1>";

$requirements = [
    'Files' => [
        'Auth.php',
        'Cache.php',
        'Database.php',
        'EmailQueue.php',
        'lib/PHPMailer.php',
        'lib/SMTP.php',
        'lib/Exception.php'
    ],
    'Directories' => [
        'lib',
        'cache'
    ]
];

echo "<h2>1. File Check</h2>";
echo "<ul>";
foreach ($requirements['Files'] as $file) {
    if (file_exists($file)) {
        echo "<li style='color:green'>✅ $file - Found</li>";
    } else {
        echo "<li style='color:red'>❌ $file - MISSING (Upload this!)</li>";
    }
}
echo "</ul>";

echo "<h2>2. Directory & Permissions Check</h2>";
echo "<ul>";
foreach ($requirements['Directories'] as $dir) {
    if (is_dir($dir)) {
        $writable = is_writable($dir) ? "<span style='color:green'>Writable</span>" : "<span style='color:red'>NOT Writable (Set to 777)</span>";
        echo "<li>✅ $dir - Directory Exists / $writable</li>";
    } else {
        echo "<li style='color:red'>❌ $dir - MISSING (Create this folder manually)</li>";
    }
}
echo "</ul>";

echo "<h2>3. Server Environment</h2>";
echo "<ul>";
echo "<li>PHP Version: " . PHP_VERSION . " (Required: 7.0+)</li>";
echo "<li>Session Status: " . (session_start() ? "<span style='color:green'>Active</span>" : "<span style='color:red'>Failed</span>") . "</li>";
echo "</ul>";

echo "<hr><p>If all items above are green but you still see a 500 error, please check your <b>.htaccess</b> file or contact support.</p>";
?>