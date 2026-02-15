<?php
try {
    $pdo = new PDO("sqlite:courses.db");
    $pdo->exec("ALTER TABLE board_members ADD COLUMN is_best INTEGER DEFAULT 0");
    echo "Column is_best added successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>