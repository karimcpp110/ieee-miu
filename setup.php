<?php
require_once 'Database.php';
$db = new Database();
echo "<h1>Site Setup</h1>";
try {
    echo "Starting initialization...<br>";
    $db->initialize();
    echo "<p style='color:green'>✅ Database Tables Checked/Created Successfully!</p>";
    echo "<a href='index.php'>Go to Home</a>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Setup Error: " . $e->getMessage() . "</p>";
}
?>