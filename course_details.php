<?php
require_once 'Course.php';
require_once 'Auth.php';

$id = isset($_GET['id']) ? $_GET['id'] : 0;
$courseModel = new Course();
$course = $courseModel->get($id);

if (!$course) {
    die("Course not found");
}

$db = new Database(); // Direct DB access for enrollment insert
$msg = '';

// Handle Enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll'])) {
    $sql = "INSERT INTO enrollments (course_id, student_name, student_contact) VALUES (?, ?, ?)";
    $db->query($sql, [$id, $_POST['name'], $_POST['contact']]);
    $msg = "You have successfully enrolled in " . htmlspecialchars($course['title']) . "!";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($course['title']) ?> - IEEE MIU</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <a href="courses.php" class="btn" style="margin-bottom: 2rem; background: rgba(255,255,255,0.05);"><i class="fas fa-arrow-left"></i> Back to Courses</a>

    <div class="glass-panel" style="padding: 0; overflow: hidden; margin-bottom: 2rem;">
        <div style="height: 300px; width: 100%; overflow: hidden;">
            <img src="<?= htmlspecialchars($course['thumbnail']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <div style="padding: 3rem;">
            <h1 style="font-size: 2.5rem; margin-bottom: 0.5rem; color: var(--primary-neon);"><?= htmlspecialchars($course['title']) ?></h1>
            <p style="font-size: 1.1rem; color: var(--text-muted); margin-bottom: 2rem;">
                <i class="fas fa-user-circle"></i> <?= htmlspecialchars($course['instructor']) ?> &bull; 
                <i class="fas fa-clock"></i> <?= htmlspecialchars($course['duration']) ?>
            </p>
            
            <p style="font-size: 1.1rem; line-height: 1.8; margin-bottom: 3rem;">
                <?= htmlspecialchars($course['description']) ?>
            </p>
            
            <!-- Admin Content Section -->
            <!-- Admin/Student Content Section -->
            <div style="background: rgba(0,0,0,0.2); padding: 2rem; border-radius: 12px; margin-bottom: 3rem;">
                <h3 style="color: var(--secondary-neon); margin-bottom: 1rem;"><i class="fas fa-lock"></i> Course Content</h3>
                
                <?php
                $canView = false;
                if (Auth::check()) {
                    $canView = true;
                } elseif (isset($_SESSION['student_logged_in'])) {
                    // Check enrollment
                    $stmt = $db->query("SELECT * FROM enrollments WHERE course_id = ? AND student_contact = ?", [$id, $_SESSION['student_email']]);
                    if ($stmt->fetch()) {
                        $canView = true;
                    }
                }
                ?>

                <?php if ($canView): ?>
                    <?php if (!empty($course['content'])): ?>
                        <div style="line-height: 1.6;">
                            <?= $course['content'] /* Allow HTML for content */ ?>
                        </div>
                    <?php else: ?>
                        <p style="color: var(--text-muted);">No specific content uploaded yet.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem;">
                        <i class="fas fa-lock" style="font-size: 2rem; color: #ff4444; margin-bottom: 1rem;"></i>
                        <h4 style="margin-bottom: 0.5rem;">Content Locked</h4>
                        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">You must be enrolled in this course to view its content.</p>
                        
                        <?php if (!isset($_SESSION['student_logged_in'])): ?>
                            <a href="student_login.php" class="btn btn-primary">Student Login</a>
                        <?php else: ?>
                            <p style="color: var(--secondary-neon);">Please fill the enrollment form below.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Enrollment Form -->
            <div style="border-top: 1px solid var(--glass-border); padding-top: 3rem;">
                <h2 style="margin-bottom: 1.5rem;">Enroll in this Course</h2>
                
                <?php if($msg): ?>
                    <div style="background: rgba(0, 255, 0, 0.1); color: #00ffaa; padding: 1rem; margin-bottom: 1rem; border-radius: 8px;">
                        <?= $msg ?>
                    </div>
                <?php endif; ?>

                <form method="POST" style="max-width: 500px;">
                    <input type="hidden" name="enroll" value="1">
                    <div class="form-group">
                        <label class="form-label">Your Name</label>
                        <input type="text" name="name" class="form-input" required placeholder="Jane Doe">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact (Email/Phone)</label>
                        <input type="text" name="contact" class="form-input" required placeholder="+20 100...">
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Enrollment</button>
                </form>
            </div>

        </div>
    </div>
</div>

</body>
</html>
