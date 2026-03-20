<?php
require_once 'Database.php';

class Gamification
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function completeCourse($userId, $courseId)
    {
        // Mark course as completed
        $this->db->query("INSERT INTO user_course_progress (user_id, course_id, status, completed_at) 
                        VALUES (?, ?, 'completed', NOW()) 
                        ON DUPLICATE KEY UPDATE status = 'completed', completed_at = NOW()",
            [$userId, $courseId]
        );

        // Update user stats
        $this->db->query("INSERT INTO user_stats (user_id, courses_completed) 
                        VALUES (?, 1) 
                        ON DUPLICATE KEY UPDATE courses_completed = courses_completed + 1",
            [$userId]
        );

        $this->checkBadges($userId);
    }

    public function checkBadges($userId)
    {
        $stats = $this->db->query("SELECT courses_completed FROM user_stats WHERE user_id = ?", [$userId])->fetch();
        if (!$stats)
            return;

        $completedCount = $stats['courses_completed'];

        // Define badge requirements (could be moved to DB eventually)
        $badgeLevels = [
            1 => ['name' => 'First Step', 'desc' => 'Completed your first course!'],
            5 => ['name' => 'Fast Learner', 'desc' => 'Completed 5 courses!'],
            10 => ['name' => 'IEEE Scholar', 'desc' => 'The pursuit of knowledge is eternal. 10 courses done!']
        ];

        foreach ($badgeLevels as $req => $data) {
            if ($completedCount >= $req) {
                // Check if user already has it
                $exists = $this->db->query("SELECT id FROM badges WHERE name = ?", [$data['name']])->fetch();
                if (!$exists) {
                    $this->db->query(
                        "INSERT INTO badges (name, description, requirements_type, requirements_value) VALUES (?, ?, 'total_courses', ?)",
                        [$data['name'], $data['desc'], $req]
                    );
                    $badgeId = $this->db->lastInsertId();
                } else {
                    $badgeId = $exists['id'];
                }

                $this->db->query("INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES (?, ?)", [$userId, $badgeId]);
            }
        }
    }

    public function getLeaderboard($limit = 10)
    {
        $limit = (int) $limit;
        try {
            return $this->db->query("SELECT s_acc.full_name as username, stat.courses_completed, stat.events_attended 
                                    FROM user_stats stat 
                                    JOIN students s_acc ON stat.user_id = s_acc.id 
                                    ORDER BY stat.courses_completed DESC, stat.events_attended DESC 
                                    LIMIT $limit")->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function resetLeaderboard()
    {
        return $this->db->query("TRUNCATE TABLE user_stats");
    }

    public function getUserBadges($userId)
    {
        return $this->db->query("SELECT b.*, ub.awarded_at 
                                FROM user_badges ub 
                                JOIN badges b ON ub.badge_id = b.id 
                                WHERE ub.user_id = ? 
                                ORDER BY ub.awarded_at DESC", [$userId])->fetchAll();
    }
}
?>