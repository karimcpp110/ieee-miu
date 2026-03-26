<?php
require_once 'Database.php';
$db = new Database();
$f = $db->query("SELECT * FROM forms WHERE type='exam' ORDER BY id DESC LIMIT 1")->fetch();
if ($f) {
    echo "ID: " . $f['id'] . "\n";
    echo "Title: " . $f['title'] . "\n";
    echo "JSON: " . $f['fields_json'] . "\n";
} else {
    echo "No exams found.\n";
}
