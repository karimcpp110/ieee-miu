<?php
require_once 'Auth.php';
require_once 'Database.php';



// Check for student login
if (!isset($_SESSION['student_logged_in']) || $_SESSION['student_logged_in'] !== true) {
    header("Location: student_login.php");
    exit;
}

$db = new Database();
$studentId = $_SESSION['student_account_id'];
$studentName = $_SESSION['student_name'];
$studentEmail = $_SESSION['student_email'];

// Fetch Enrolled Courses
$sql = "SELECT e.*, c.title, c.thumbnail, c.instructor, c.description, c.duration 
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.id 
        WHERE e.student_account_id = ? 
        ORDER BY e.enrolled_at DESC";
$enrollments = $db->query($sql, [$studentId])->fetchAll();

// Fetch Student Details to get Student ID if not in session
$studentProfile = $db->query("SELECT * FROM students WHERE id = ?", [$studentId])->fetch();
$studentUniversityId = $studentProfile['student_id'] ?? 'Not set';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - IEEE MIU</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

    <?php include 'navbar.php'; ?>

    <main class="container">

        <!-- Welcome Hero -->
        <div class="glass-panel dashboard-header">
            <div class="header-content">
                <h1 class="text-gradient">Student Portal</h1>
                <p>Welcome back, <span class="user-accent">
                        <?= htmlspecialchars($studentName) ?>
                    </span></p>
            </div>
            <div class="header-status">
                <span class="status-badge"><i class="fas fa-user-graduate"></i> Active Student</span>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Sidebar Profile -->
            <aside class="dashboard-sidebar glass-panel">
                <div class="profile-card-inner">
                    <div class="profile-avatar-large">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($studentName) ?>&background=00629B&color=fff&size=128"
                            alt="Profile">
                    </div>
                    <h3 class="profile-name">
                        <?= htmlspecialchars($studentName) ?>
                    </h3>
                    <p class="profile-email">
                        <?= htmlspecialchars($studentEmail) ?>
                    </p>

                    <div class="profile-details-list">
                        <div class="detail-item">
                            <span class="label">Student ID</span>
                            <span class="value">
                                <?= htmlspecialchars($studentUniversityId) ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Joined</span>
                            <span class="value">
                                <?= date('M Y', strtotime($studentProfile['created_at'] ?? 'now')) ?>
                            </span>
                        </div>
                    </div>

                    <a href="courses.php" class="btn btn-outline btn-full" style="margin-top: 1.5rem;">
                        <i class="fas fa-search"></i> Browse Courses
                    </a>
                </div>
            </aside>

            <!-- Main Content: My Learning -->
            <section class="dashboard-main-content">
                <div class="glass-panel content-section">
                    <div class="section-header">
                        <h2 class="text-gradient">My Learning</h2>
                        <span class="count-badge">
                            <?= count($enrollments) ?> Courses
                        </span>
                    </div>

                    <?php if (empty($enrollments)): ?>
                        <div class="empty-state">
                            <i class="fas fa-book-reader icon-box-large"></i>
                            <h3>No courses yet</h3>
                            <p>You haven't enrolled in any courses yet. Explore our catalog to start learning.</p>
                            <a href="courses.php" class="btn btn-primary" style="margin-top: 1rem;">
                                Browse Catalog <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="course-grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
                            <?php foreach ($enrollments as $course): ?>
                                <div class="course-card glass-panel">
                                    <div class="card-img-container" style="height: 160px;">
                                        <img src="<?= htmlspecialchars($course['thumbnail']) ?>" alt="Thumbnail"
                                            class="card-img">
                                        <div class="admin-card-actions">
                                            <!-- Progress Badge Example -->
                                            <span class="status-badge" style="background: rgba(0,0,0,0.7);">Enrolled</span>
                                        </div>
                                    </div>
                                    <div class="card-content" style="padding: 1.5rem;">
                                        <h3 class="card-title" style="font-size: 1.2rem; margin-bottom: 0.5rem;">
                                            <?= htmlspecialchars($course['title']) ?>
                                        </h3>
                                        <div class="instructor-info" style="margin-bottom: 1rem;">
                                            <small><i class="fas fa-chalkboard-teacher"></i>
                                                <?= htmlspecialchars($course['instructor']) ?>
                                            </small>
                                        </div>

                                        <div class="card-footer-flex" style="margin-top: auto; padding-top: 1rem;">
                                            <span class="duration-badge"><i class="far fa-clock"></i>
                                                <?= htmlspecialchars($course['duration']) ?>
                                            </span>
                                            <a href="course_details.php?id=<?= $course['course_id'] ?>"
                                                class="btn btn-primary btn-sm-icon"
                                                style="width: auto; padding: 0.5rem 1rem; font-size: 0.9rem;">
                                                Continue <i class="fas fa-play"
                                                    style="margin-left: 0.5rem; font-size: 0.7rem;"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <style>
        .profile-card-inner {
            text-align: center;
        }

        .profile-avatar-large img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 3px solid var(--primary-neon);
            margin-bottom: 1rem;
            box-shadow: 0 0 20px rgba(0, 98, 155, 0.4);
        }

        .profile-name {
            color: var(--text-main);
            margin-bottom: 0.2rem;
        }

        .profile-email {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .profile-details-list {
            text-align: left;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            padding: 1rem;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .detail-item:last-child {
            margin-bottom: 0;
        }

        .detail-item .label {
            color: var(--text-muted);
        }

        .detail-item .value {
            font-weight: 600;
            color: var(--text-main);
        }

        .btn-full {
            width: 100%;
            justify-content: center;
        }
    </style>

</body>

</html>