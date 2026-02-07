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

// Access logic
$canView = Auth::check(); // Admins can always view

if (!$canView && isset($_SESSION['student_logged_in'])) {
    $studentId = $_SESSION['student_account_id'];
    // Check if this student is enrolled in THIS course
    $enrollCheck = $db->query("SELECT id FROM enrollments WHERE course_id = ? AND student_account_id = ?", [$id, $studentId])->fetch();
    if ($enrollCheck) {
        $canView = true;
    }
}

// Handle Enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll'])) {
    $studentAccountId = isset($_SESSION['student_account_id']) ? $_SESSION['student_account_id'] : null;
    
    $sql = "INSERT INTO enrollments (course_id, student_name, student_contact, student_account_id) VALUES (?, ?, ?, ?)";
    $db->query($sql, [$id, $_POST['name'], $_POST['contact'], $studentAccountId]);
    
    $msg = "You have successfully enrolled in " . htmlspecialchars($course['title']) . "!";
    // Refresh canView after enrollment
    if ($studentAccountId) $canView = true;
}

$extras = $courseModel->getExtras($id);
$materials = array_filter($extras, fn($e) => $e['type'] === 'material');
$records = array_filter($extras, fn($e) => $e['type'] === 'record');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($course['title']) ?> - IEEE MIU</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="container">
    <div class="back-nav">
        <a href="courses.php" class="btn-back"><i class="fas fa-chevron-left"></i> All Courses</a>
    </div>

    <article class="course-details-card glass-panel">
        <div class="course-hero">
            <img src="<?= htmlspecialchars($course['thumbnail']) ?>" alt="<?= htmlspecialchars($course['title']) ?>" class="hero-image">
            <div class="hero-overlay"></div>
            <div class="hero-info-header">
                <span class="badge"><?= htmlspecialchars($course['duration']) ?> Track</span>
                <h1 class="text-gradient"><?= htmlspecialchars($course['title']) ?></h1>
                <div class="instructor-pill">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($course['instructor']) ?>&background=00f3ff&color=000" class="avatar-sm">
                    <span>Instructed by <strong><?= htmlspecialchars($course['instructor']) ?></strong></span>
                </div>
            </div>
        </div>

        <div class="details-body">
            <section class="description-section">
                <h2 class="section-title">About this Course</h2>
                <p class="course-purpose"><?= htmlspecialchars($course['description']) ?></p>
            </section>
            
            <section class="content-access-section glass-panel">
                <div class="access-header">
                    <h3 class="text-gradient"><i class="fas fa-play-circle"></i> Learning Material</h3>
                    <div class="access-status">
                        <?php if ($canView): ?>
                            <span class="status-pill unlocked"><i class="fas fa-lock-open"></i> Unlocked</span>
                        <?php else: ?>
                            <span class="status-pill locked"><i class="fas fa-lock"></i> Enrollment Required</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="access-content">
                    <?php if ($canView): ?>
                        <div class="content-main-wrapper">
                            <?php if (!empty($course['file_path'])): ?>
                                <div class="file-download-box glass-panel">
                                    <div class="file-type-info">
                                        <div class="file-icon-box">
                                            <?php 
                                            $ext = pathinfo($course['file_path'], PATHINFO_EXTENSION);
                                            $icon = 'fa-file-alt';
                                            if ($ext == 'pdf') $icon = 'fa-file-pdf';
                                            if (in_array($ext, ['doc', 'docx'])) $icon = 'fa-file-word';
                                            if (in_array($ext, ['ppt', 'pptx'])) $icon = 'fa-file-powerpoint';
                                            ?>
                                            <i class="fas <?= $icon ?>"></i>
                                        </div>
                                        <div class="file-meta">
                                            <h4>Course Material</h4>
                                            <p><?= strtoupper($ext) ?> Document</p>
                                        </div>
                                    </div>
                                    <a href="<?= htmlspecialchars($course['file_path']) ?>" download class="btn btn-primary">
                                        <i class="fas fa-download"></i> Download Resource
                                    </a>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($course['content'])): ?>
                                <div class="rich-content">
                                    <?= $course['content'] ?>
                                </div>
                            <?php endif; ?>

                        </div>
                    <?php else: ?>
                        <div class="locked-state">
                            <div class="locked-icon-box pulse"><i class="fas fa-shield-alt"></i></div>
                            <h4>Educational Content Restricted</h4>
                            <p>This module is part of the certified IEEE MIU curriculum. Please enroll below to gain access to videos, resources, and sessions.</p>
                            
                            <?php if (!isset($_SESSION['student_logged_in'])): ?>
                                <div class="locked-actions">
                                    <a href="student_login.php" class="btn btn-primary">Login as Student</a>
                                    <span class="or">or</span>
                                    <a href="#enroll" class="btn btn-outline">Fill Enrollment</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <?php if ($canView && (!empty($materials) || !empty($records))): ?>
                <div class="extras-grid">
                    <?php if (!empty($materials)): ?>
                        <section class="extras-section materials glass-panel">
                            <h3 class="text-gradient"><i class="fas fa-book"></i> Supplementary Materials</h3>
                            <div class="extras-list">
                                <?php foreach ($materials as $m): ?>
                                    <div class="extra-item glass-panel">
                                        <div class="extra-icon"><i class="fas fa-file-pdf"></i></div>
                                        <div class="extra-info">
                                            <h4><?= htmlspecialchars($m['title']) ?></h4>
                                            <?php if ($m['content']): ?><p><?= htmlspecialchars($m['content']) ?></p><?php endif; ?>
                                        </div>
                                        <?php if ($m['file_path']): ?>
                                            <a href="<?= htmlspecialchars($m['file_path']) ?>" download class="btn btn-sm btn-outline"><i class="fas fa-download"></i></a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($records)): ?>
                        <section class="extras-section records glass-panel">
                            <h3 class="text-gradient"><i class="fas fa-video"></i> Session Records</h3>
                            <div class="extras-list">
                                <?php foreach ($records as $r): ?>
                                    <div class="extra-item glass-panel">
                                        <div class="extra-icon record"><i class="fas fa-play"></i></div>
                                        <div class="extra-info">
                                            <h4><?= htmlspecialchars($r['title']) ?></h4>
                                            <?php if ($r['content']): ?><p><?= htmlspecialchars($r['content']) ?></p><?php endif; ?>
                                        </div>
                                        <?php if ($r['file_path']): ?>
                                            <a href="<?= htmlspecialchars($r['file_path']) ?>" target="_blank" class="btn btn-sm btn-primary"><i class="fas fa-external-link-alt"></i> View</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Enrollment Form Section -->
                <?php if (!$canView): ?>
                <div class="section-header-centered">
                    <h2 class="text-gradient">Ready to Start?</h2>
                    <p>Enroll now to join the track and start your learning journey.</p>
                    <?php if (!isset($_SESSION['student_logged_in'])): ?>
                        <p class="login-prompt">Already have an account? <a href="student_login.php" class="text-gradient">Login</a> to keep your progress.</p>
                    <?php endif; ?>
                </div>
                
                <?php if($msg): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= $msg ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="styled-form enrollment-form glass-panel">
                    <input type="hidden" name="enroll" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-input" required value="<?= isset($_SESSION['student_name']) ? htmlspecialchars($_SESSION['student_name']) : '' ?>" placeholder="Jane Doe">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Contact (Email/WhatsApp)</label>
                            <input type="text" name="contact" class="form-input" required value="<?= isset($_SESSION['student_email']) ? htmlspecialchars($_SESSION['student_email']) : '' ?>" placeholder="e.g. +20 100 123 4567">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">Submit Enrollment <i class="fas fa-arrow-right"></i></button>
                </form>
                <?php else: ?>
                <div class="section-header-centered">
                    <h2 class="text-gradient">You are Enrolled!</h2>
                    <p>Enjoy your learning journey in <?= htmlspecialchars($course['title']) ?>.</p>
                </div>
                <?php endif; ?>
            </section>
        </div>
    </article>
</main>

<style>
.back-nav { margin-bottom: 2rem; }
.btn-back { display: inline-flex; align-items: center; gap: 0.75rem; color: var(--text-muted); text-decoration: none; font-weight: 600; padding: 0.75rem 1.25rem; background: var(--glass-bg); border-radius: 12px; border: 1px solid var(--glass-border); transition: var(--transition); }
.btn-back:hover { color: var(--primary-neon); border-color: var(--primary-neon); background: var(--glass-bg-bright); }

.course-details-card { padding: 0; overflow: hidden; }
.course-hero { position: relative; height: 400px; }
.hero-image { width: 100%; height: 100%; object-fit: cover; }
.hero-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(0deg, var(--bg-color) 0%, transparent 100%); }
.hero-info-header { position: absolute; bottom: 0; left: 0; padding: 4rem; width: 100%; }
.hero-info-header h1 { font-size: 3.5rem; margin: 1rem 0; }
.hero-info-header .badge { background: var(--secondary-neon); color: white; border: none; }

.instructor-pill { display: inline-flex; align-items: center; gap: 1rem; padding: 0.6rem 1.2rem; background: rgba(0,0,0,0.5); backdrop-filter: blur(10px); border-radius: 50px; border: 1px solid var(--glass-border); }
.avatar-sm { width: 32px; height: 32px; border-radius: 50%; }

.details-body { padding: 4rem; }
.section-title { font-size: 2rem; margin-bottom: 1.5rem; }
.login-prompt { margin-top: 1rem; font-size: 0.95rem; }
.course-purpose { font-size: 1.2rem; color: var(--text-muted); line-height: 1.8; margin-bottom: 4rem; }

.content-access-section { padding: 2.5rem; margin-bottom: 5rem; border: 1px solid var(--primary-neon); box-shadow: 0 0 30px rgba(0, 243, 255, 0.1); }
.access-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--glass-border); }
.status-pill { padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.85rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem; }
.status-pill.unlocked { background: rgba(0, 255, 170, 0.1); color: #00ffaa; }
.status-pill.locked { background: rgba(255, 71, 87, 0.1); color: #ff4757; }

.locked-state { text-align: center; padding: 3rem 1rem; }
.locked-icon-box { width: 80px; height: 80px; background: rgba(255, 71, 87, 0.1); color: #ff4757; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 2rem; }
.locked-state h4 { font-size: 1.5rem; margin-bottom: 1rem; color: var(--text-main); }
.locked-state p { max-width: 500px; margin: 0 auto 2.5rem; color: var(--text-muted); }
.locked-actions { display: flex; align-items: center; justify-content: center; gap: 1.5rem; }
.locked-actions .or { color: var(--text-muted); font-size: 0.9rem; font-weight: 700; text-transform: uppercase; }

.rich-content { color: var(--text-main); }
.rich-content iframe { border-radius: 16px; border: 1px solid var(--glass-border); width: 100%; aspect-ratio: 16/9; margin: 2rem 0; }
.empty-content { text-align: center; padding: 4rem; color: var(--text-muted); opacity: 0.6; }
.empty-content i { font-size: 3rem; margin-bottom: 1.5rem; }

/* File Download Box */
.file-download-box {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 2rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--glass-border);
    border-radius: 16px;
    margin-bottom: 2.5rem;
}
.file-type-info { display: flex; align-items: center; gap: 1.5rem; }
.file-icon-box {
    width: 60px;
    height: 60px;
    background: rgba(0, 243, 255, 0.1);
    color: var(--primary-neon);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
}
.file-meta h4 { font-size: 1.1rem; margin-bottom: 0.2rem; }
.file-meta p { font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; }

.section-header-centered { text-align: center; margin-bottom: 3rem; }
.section-header-centered h2 { font-size: 2.5rem; margin-bottom: 0.5rem; }
.section-header-centered p { color: var(--text-muted); }

.enrollment-form { max-width: 700px; margin: 0 auto; padding: 3rem; border: 1px solid var(--secondary-neon); }

@media (max-width: 768px) {
    .hero-info-header { padding: 2rem; }
    .hero-info-header h1 { font-size: 2.2rem; }
    .details-body { padding: 2rem; }
    .course-hero { height: 300px; }
    .locked-actions { flex-direction: column; }
    .enrollment-form { padding: 2rem; }
}

.extras-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 5rem; }
.extras-section { padding: 2rem; }
.extras-section h3 { margin-bottom: 2rem; font-size: 1.5rem; display: flex; align-items: center; gap: 1rem; }
.extras-list { display: flex; flex-direction: column; gap: 1rem; }
.extra-item { display: flex; align-items: center; gap: 1.5rem; padding: 1rem 1.5rem; background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); transition: 0.3s; }
.extra-item:hover { transform: translateY(-3px); border-color: var(--primary-neon); background: rgba(0, 243, 255, 0.05); }
.extra-icon { width: 45px; height: 45px; background: rgba(0, 243, 255, 0.1); color: var(--primary-neon); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
.extra-icon.record { background: rgba(255, 71, 87, 0.1); color: #ff4757; }
.extra-info { flex: 1; }
.extra-info h4 { font-size: 1rem; margin-bottom: 0.2rem; }
.extra-info p { font-size: 0.8rem; color: var(--text-muted); }

@media (max-width: 992px) {
    .extras-grid { grid-template-columns: 1fr; }
}
</style>

</body>
</html>

