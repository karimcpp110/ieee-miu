<?php
require_once 'CertificateGenerator.php';
require_once 'Auth.php';
require_once 'Database.php';

session_start();

$courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

// Check student login
if (!isset($_SESSION['student_logged_in']) || !isset($_SESSION['student_account_id'])) {
    die("Access denied. Please login as a student.");
}

$studentId = $_SESSION['student_account_id'];
$db = new Database();

// Verify completion
$comp = $db->query("SELECT status FROM user_course_progress WHERE user_id = ? AND course_id = ?", [$studentId, $courseId])->fetch();

if (!$comp || $comp['status'] !== 'completed') {
    die("Course not completed yet.");
}

// Fetch details for certificate - Fixed table names to match production schema
$student = $db->query("SELECT full_name FROM students WHERE id = ?", [$studentId])->fetch();
$course = $db->query("SELECT title FROM courses WHERE id = ?", [$courseId])->fetch();

if (!$student || !$course) {
    die("Data error: Student or Course record not found.");
}

$generator = new CertificateGenerator();
$pdfData = $generator->generate($student['full_name'], $course['title']);

// Force standard PDF headers
header('Content-Type: application/pdf');
header('Cache-Control: public, must-revalidate, max-age=0');
header('Pragma: public');
header('Content-Disposition: attachment; filename="IEEE_MIU_Certificate_' . str_replace(' ', '_', $course['title']) . '.pdf"');
echo $pdfData;
?>