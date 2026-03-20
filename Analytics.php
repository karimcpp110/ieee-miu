<?php
require_once 'Database.php';

class Analytics
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function trackView($pageName)
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $this->db->query("INSERT INTO page_views (page_name, user_id) VALUES (?, ?)", [$pageName, $userId]);
    }

    public function trackDownload($courseId, $resourceId)
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $this->db->query("INSERT INTO downloads_log (course_id, resource_id, user_id) VALUES (?, ?, ?)", [$courseId, $resourceId, $userId]);
    }

    public function updateLogin($userId)
    {
        $this->db->query("INSERT INTO user_stats (user_id, last_login) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE last_login = NOW()", [$userId]);
    }

    public function getActiveUsersWeekly()
    {
        return $this->db->query("SELECT COUNT(DISTINCT user_id) as count FROM page_views WHERE viewed_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch()['count'];
    }

    public function getStats()
    {
        return [
            'total_members' => $this->db->query("SELECT COUNT(*) as count FROM members")->fetch()['count'],
            'total_enrollments' => $this->db->query("SELECT COUNT(*) as count FROM enrollments")->fetch()['count'],
            'active_students' => $this->getActiveUsersWeekly(),
            'top_courses' => $this->db->query("SELECT c.title, COUNT(dl.id) as downloads FROM courses c JOIN downloads_log dl ON c.id = dl.course_id GROUP BY c.id ORDER BY downloads DESC LIMIT 5")->fetchAll()
        ];
    }
}
?>