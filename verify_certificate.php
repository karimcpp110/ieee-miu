<?php
require_once 'CertificateGenerator.php';
require_once 'Database.php';

$studentId = isset($_GET['s']) ? (int) $_GET['s'] : 0;
$courseId = isset($_GET['c']) ? (int) $_GET['c'] : 0;

if (!$studentId || !$courseId) {
    die("Invalid Verification Link.");
}

$db = new Database();

// Verify completion & check privacy
$student = $db->query("SELECT full_name, is_public FROM students WHERE id = ?", [$studentId])->fetch();
if (!$student) {
    die("Student record not found.");
}

if (!(bool)$student['is_public']) {
    // If not public, only allow the owner or admin to view
    session_start();
    $isOwner = isset($_SESSION['student_account_id']) && $_SESSION['student_account_id'] == $studentId;
    
    require_once 'Auth.php';
    $isAdmin = Auth::isAdmin();

    if (!$isOwner && !$isAdmin) {
        die("This profile's certificates are set to private.");
    }
}

$comp = $db->query("SELECT status FROM user_course_progress WHERE user_id = ? AND course_id = ?", [$studentId, $courseId])->fetch();

if (!$comp || $comp['status'] !== 'completed') {
    die("Verification Failed: Course not completed by this student.");
}

$course = $db->query("SELECT title FROM courses WHERE id = ?", [$courseId])->fetch();

if (!$course) {
    die("Verification Failed: Course not found.");
}

// Generate the PDF and display inline
$generator = new CertificateGenerator();
$pdfData = $generator->generate($student['full_name'], $course['title']);

header('Content-Type: application/pdf');
header('Cache-Control: public, must-revalidate, max-age=0');
header('Pragma: public');
// Inline disposition so it opens in the browser instead of downloading directly
header('Content-Disposition: inline; filename="IEEE_MIU_Verified_' . str_replace(' ', '_', $course['title']) . '.pdf"');
echo $pdfData;
?>
