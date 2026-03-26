<?php
require_once 'Database.php';

try {
    $db = new Database();
    $columns = $db->query("DESCRIBE students")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('is_public', $columns)) {
        $db->query("ALTER TABLE students ADD COLUMN is_public TINYINT(1) DEFAULT 1");
        echo "SUCCESS: Added is_public";
    } else {
        echo "INFO: is_public already exists";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
