<?php
require_once 'Database.php';
require_once 'BoardMember.php';

$boardModel = new BoardMember();
$members = $boardModel->getAll();

echo "=== BOARD MEMBERS DEBUG ===\n\n";

foreach ($members as $member) {
    echo "ID: " . $member['id'] . "\n";
    echo "Name: " . $member['name'] . "\n";
    echo "Photo URL: " . $member['photo_url'] . "\n";
    
    // Check if file exists
    if (file_exists($member['photo_url'])) {
        echo "✓ File exists\n";
        echo "File size: " . filesize($member['photo_url']) . " bytes\n";
    } else {
        echo "✗ File NOT found at: " . $member['photo_url'] . "\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}
?>
