<?php
require_once 'Database.php';

class Logger
{
    public static function log($action, $details = '')
    {
        $db = new Database();

        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Anonymous';
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';

        $db->query(
            "INSERT INTO activity_logs (user_id, username, action, details, ip_address) VALUES (?, ?, ?, ?, ?)",
            [$userId, $username, $action, $details, $ip]
        );
    }

    public static function getRecent($limit = 50)
    {
        $db = new Database();
        return $db->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT ?", [$limit])->fetchAll();
    }
}
?>