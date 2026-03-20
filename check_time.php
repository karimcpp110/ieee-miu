<?php
echo "PHP Time: " . date('Y-m-d H:i:s') . "\n";
require_once 'Database.php';
$db = new Database();
$res = $db->query("SELECT NOW() as db_time")->fetch();
echo "MySQL Time: " . $res['db_time'] . "\n";
?>
