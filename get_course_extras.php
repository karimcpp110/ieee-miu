<?php
require_once 'Auth.php';
require_once 'Course.php';

if (!Auth::check()) {
    http_response_code(403);
    exit;
}

$courseModel = new Course();
$courseId = $_GET['course_id'] ?? 0;
$extras = $courseModel->getExtras($courseId);

header('Content-Type: application/json');
echo json_encode($extras);
?>
