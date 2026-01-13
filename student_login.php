<?php
require_once 'Database.php';

session_start();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $studentId = $_POST['student_id'];
    
    $db = new Database();
    // Check if member exists in 'members' table (Club Registration)
    // In a real LMS, we'd have a 'students' table with passwords.
    // For "Little Moodle", we verify against club members using Email + Student ID as auth.
    
    $stmt = $db->query("SELECT * FROM members WHERE email = ? AND student_id = ?", [$email, $studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        $_SESSION['student_logged_in'] = true;
        $_SESSION['student_id'] = $student['student_id'];
        $_SESSION['student_name'] = $student['full_name'];
        $_SESSION['student_email'] = $student['email'];
        
        header("Location: courses.php"); // Redirect to courses
        exit;
    } else {
        $msg = "Invalid credentials. Please register for the club first.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Login - IEEE MIU</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #0f172a;">

<div class="glass-panel" style="padding: 3rem; width: 100%; max-width: 400px; text-align: center;">
    <h2 style="margin-bottom: 2rem; color: var(--primary-neon);">Student Portal</h2>
    
    <?php if($msg): ?>
        <p style="color: #ff4444; margin-bottom: 1rem;"><?= $msg ?></p>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group" style="text-align: left;">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-input" required placeholder="student@example.com">
        </div>
        
        <div class="form-group" style="text-align: left;">
            <label class="form-label">Student ID</label>
            <input type="text" name="student_id" class="form-input" required placeholder="2020xxxx">
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">Access Courses</button>
    </form>
    
    <p style="margin-top: 1.5rem; color: var(--text-muted); font-size: 0.9rem;">
        Not a member? <a href="index.php" style="color: var(--secondary-neon);">Join IEEE MIU</a>
    </p>
    <p style="margin-top: 0.5rem;">
        <a href="index.php" style="color: var(--text-muted); font-size: 0.9rem;">Back to Home</a>
    </p>
</div>

</body>
</html>
