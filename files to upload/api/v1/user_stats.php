<?php
require_once 'api_header.php';
require_once '../../Auth.php';

session_start();

if (!isset($_SESSION['student_logged_in'])) {
    apiResponse([], 401, "Unauthorized. Student login required.");
}

$studentId = $_SESSION['student_account_id'];
$stats = $db->query("SELECT * FROM user_stats WHERE user_id = ?", [$studentId])->fetch();

if (!$stats) {
    apiResponse(['user_id' => $studentId, 'courses_completed' => 0, 'events_attended' => 0], 200, "Stats initialized.");
}

apiResponse($stats, 200, "Student stats retrieved.");
?>