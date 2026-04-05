<?php
require_once 'Database.php';

class Notification
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function create($title, $message, $type = 'info', $userId = null, $studentId = null)
    {
        return $this->db->query(
            "INSERT INTO notifications (user_id, student_id, type, title, message) VALUES (?, ?, ?, ?, ?)",
            [$userId, $studentId, $type, $title, $message]
        );
    }

    public function getUnread($id, $isStudent = false)
    {
        $col = $isStudent ? 'student_id' : 'user_id';
        return $this->db->query(
            "SELECT * FROM notifications WHERE $col = ? AND is_read = 0 ORDER BY created_at DESC",
            [$id]
        )->fetchAll();
    }

    public function markAsRead($id)
    {
        return $this->db->query("UPDATE notifications SET is_read = 1 WHERE id = ?", [$id]);
    }

    public function markAllRead($userId, $isStudent = false)
    {
        $col = $isStudent ? 'student_id' : 'user_id';
        return $this->db->query(
            "UPDATE notifications SET is_read = 1 WHERE $col = ? AND is_read = 0",
            [$userId]
        );
    }

    public function createForStudent($studentId, $title, $message, $type = 'info')
    {
        return $this->create($title, $message, $type, null, $studentId);
    }

    public function getAll($id, $isStudent = false, $limit = 20)
    {
        $limit = (int) $limit;
        $col = $isStudent ? 'student_id' : 'user_id';
        return $this->db->query(
            "SELECT * FROM notifications WHERE $col = ? ORDER BY created_at DESC LIMIT $limit",
            [$id]
        )->fetchAll();
    }
}
?>