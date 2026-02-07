<?php
require_once 'Database.php';

$db = new Database();
// Direct PDO access to run ALTER TABLE
$pdo = new PDO("sqlite:" . __DIR__ . '/courses.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $pdo->query("SELECT content FROM courses LIMIT 1");
    echo "Column 'content' already exists.\n";
} catch (PDOException $e) {
    echo "Column 'content' missing. Adding it...\n";
    $pdo->exec("ALTER TABLE courses ADD COLUMN content TEXT");
    echo "Column 'content' added successfully.\n";
}

try {
    $pdo->query("SELECT file_path FROM courses LIMIT 1");
    echo "Column 'file_path' already exists.\n";
} catch (PDOException $e) {
    echo "Column 'file_path' missing. Adding it...\n";
    $pdo->exec("ALTER TABLE courses ADD COLUMN file_path TEXT DEFAULT NULL");
    echo "Column 'file_path' added successfully.\n";
}
