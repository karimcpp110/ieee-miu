<?php
require_once 'Database.php';
$db = new Database();

echo "<h1>Event Category Migration</h1>";

try {
    // Add category column to events table
    $sql = "ALTER TABLE events ADD COLUMN category VARCHAR(100) DEFAULT 'General' AFTER title";
    $db->query($sql);
    echo "<p style='color:green'>✅ Added 'category' column to 'events' table.</p>";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "<p style='color:orange'>⚠️ Column 'category' already exists.</p>";
    } else {
        echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    }
}

echo "<br><br><b>NEXT STEP:</b> Delete this file after running it!<br>";
echo "<br><a href='events.php'>Go to Events Page</a>";
?>
