<?php
require_once 'Database.php';

$db = new Database();
// Direct PDO access to run ALTER TABLE
$pdo = new PDO("sqlite:" . __DIR__ . '/courses.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    // Try to select content column to see if it exists
    $pdo->query("SELECT content FROM courses LIMIT 1");
    echo "Column 'content' already exists.\n";
} catch (PDOException $e) {
    // Column doesn't exist, add it
    echo "Column 'content' missing. Adding it...\n";
    $pdo->exec("ALTER TABLE courses ADD COLUMN content TEXT");
    echo "Column 'content' added successfully.\n";
}
