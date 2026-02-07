<!DOCTYPE html>
<html>
<head>
    <title>Image Test</title>
</head>
<body>
    <h1>Testing Board Member Images</h1>
    
    <h2>Test 1: Direct Path</h2>
    <img src="uploads/board/1768355067_Khaled.jpeg" alt="Test 1" style="width:200px; border:2px solid red;">
    
    <h2>Test 2: Absolute Path</h2>
    <img src="/uploads/board/1768355067_Khaled.jpeg" alt="Test 2" style="width:200px; border:2px solid blue;">
    
    <h2>Test 3: Check if file exists</h2>
    <?php
    $file = 'uploads/board/1768355067_Khaled.jpeg';
    if (file_exists($file)) {
        echo "<p style='color:green'>✓ File exists at: $file</p>";
        echo "<p>File size: " . filesize($file) . " bytes</p>";
    } else {
        echo "<p style='color:red'>✗ File NOT found at: $file</p>";
    }
    ?>
    
    <h2>All uploaded images:</h2>
    <?php
    $dir = 'uploads/board/';
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo "<div style='margin:10px; padding:10px; border:1px solid #ccc;'>";
                echo "<p>File: $file</p>";
                echo "<img src='$dir$file' style='max-width:150px;' alt='$file'>";
                echo "</div>";
            }
        }
    }
    ?>
</body>
</html>
