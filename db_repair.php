<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'Database.php';

echo "<style>
    body { font-family: sans-serif; line-height: 1.5; padding: 2rem; background: #f4f7f9; }
    .box { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; }
    .status { padding: 10px; border-radius: 8px; margin-bottom: 10px; }
    .success { background: #e6ffed; color: #22863a; border: 1px solid #34d058; }
    .info { background: #f1f8ff; color: #0366d6; border: 1px solid #c8e1ff; }
    .error { background: #ffeef0; color: #cb2431; border: 1px solid #f97583; }
</style>";

echo "<div class='box'>";
echo "<h2>🛠️ IEEE MIU Database Repair Tool (v2)</h2>";

try {
    $db = new Database();
    echo "<div class='status info'>CONNECTED to database successfully.</div>";

    // 1. Check events table category column
    echo "<h3>1. Checking 'events' table...</h3>";
    $res = $db->query("DESCRIBE events");
    $columns = $res->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('category', $columns)) {
        echo "<p>Column 'category' is MISSING. Adding it now...</p>";
        $db->query("ALTER TABLE events ADD COLUMN category VARCHAR(50) DEFAULT 'General' AFTER title");
        echo "<div class='status success'>SUCCESS: 'category' column added to 'events' table.</div>";
    } else {
        echo "<div class='status info'>OK: 'category' column already exists in 'events' table.</div>";
    }

    // 2. Check event_gallery table
    echo "<h3>2. Checking 'event_gallery' table...</h3>";
    $tableCheck = $db->query("SHOW TABLES LIKE 'event_gallery'");
    if (!$tableCheck->fetch()) {
        echo "<p>Table 'event_gallery' is MISSING. Creating it now...</p>";
        $db->query("CREATE TABLE event_gallery (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "<div class='status success'>SUCCESS: 'event_gallery' table created.</div>";
    } else {
        echo "<div class='status info'>OK: 'event_gallery' table already exists.</div>";
    }

    // 3. Check gallery_sections table
    echo "<h3>3. Checking 'gallery_sections' table...</h3>";
    $tableCheck = $db->query("SHOW TABLES LIKE 'gallery_sections'");
    if (!$tableCheck->fetch()) {
        echo "<p>Table 'gallery_sections' is MISSING. Creating it now...</p>";
        $db->query("CREATE TABLE gallery_sections (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "<div class='status success'>SUCCESS: 'gallery_sections' table created.</div>";
    } else {
        echo "<div class='status info'>OK: 'gallery_sections' table already exists.</div>";
    }

    // 4. Check gallery_photos table
    echo "<h3>4. Checking 'gallery_photos' table...</h3>";
    $tableCheck = $db->query("SHOW TABLES LIKE 'gallery_photos'");
    if (!$tableCheck->fetch()) {
        echo "<p>Table 'gallery_photos' is MISSING. Creating it now...</p>";
        $db->query("CREATE TABLE gallery_photos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            section_id INT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "<div class='status success'>SUCCESS: 'gallery_photos' table created.</div>";
    } else {
        echo "<div class='status info'>OK: 'gallery_photos' table already exists.</div>";
    }

    echo "<hr>";
    echo "<h3 style='color:blue'>✅ Repair Complete!</h3>";
    echo "<p>Please try to publish your event again in the dashboard. If it works, you can <strong>delete this db_repair.php file</strong> from your server.</p>";

} catch (Exception $e) {
    echo "<h3>❌ Repair Failed</h3>";
    echo "<div class='status error'><strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p>Double-check your <code>Database.php</code> credentials.</p>";
}

echo "</div>";
?>