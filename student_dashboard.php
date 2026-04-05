<?php
require_once 'Auth.php';
require_once 'Database.php';
require_once 'Gamification.php';

// Check for student login
if (!isset($_SESSION['student_logged_in']) || $_SESSION['student_logged_in'] !== true) {
    header("Location: student_login.php");
    exit;
}

$db = new Database();
$gamification = new Gamification();

$studentId = $_SESSION['student_account_id'];
$studentName = $_SESSION['student_name'];
$studentUniversityId = $_SESSION['student_university_id'] ?? 'N/A';

// Fetch gamification data
$gProfile = $gamification->getUserGamificationProfile($studentId);
$xp = $gProfile['xp'];
$masteryLevel = $gProfile['level'];
$badges = $gamification->getUserBadges($studentId);

// Fetch Enrolled Courses
$sql = "SELECT e.*, c.title, c.thumbnail, c.instructor, c.description, c.duration,
                (SELECT status FROM user_course_progress WHERE user_id = e.student_account_id AND course_id = e.course_id) as progress_status
                FROM enrollments e 
                JOIN courses c ON e.course_id = c.id 
                WHERE e.student_account_id = ?";
$enrollments = $db->query($sql, [$studentId])->fetchAll();

require_once 'Event.php';
$eventModel = new Event();
$upcomingEvents = $eventModel->getUpcoming();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - IEEE MIU</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* INLINE SYSTEM CSS - TO PREVENT LOADING ISSUES */
        :root {
            --portal-bg: #0B0E14;
            --portal-card: #161B22;
            --portal-sidebar: #0D1117;
            --portal-accent: #8B5CF6;
            --portal-accent-blue: #3B82F6;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        body {
            background-color: var(--portal-bg);
            color: var(--text-main);
            font-family: 'Inter', -apple-system, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .portal-layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* Sidebar Fix */
        .portal-sidebar {
            width: 260px;
            background: var(--portal-sidebar);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            border-right: 1px solid rgba(255,255,255,0.05);
            display: flex;
            flex-direction: column;
            z-index: 1000;
        }

        .portal-main-area {
            margin-left: 260px;
            flex-grow: 1;
            padding: 2.5rem;
            min-height: 100vh;
            width: calc(100% - 260px);
        }

        /* Shared Dashboard Components */
        .dashboard-content-v2 { padding-top: 1rem; }
        .courses-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .portal-glass-panel { background: rgba(22, 27, 34, 0.7); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 20px; padding: 1.5rem; }
        .dashboard-bottom-grid { display: grid; grid-template-columns: 1.2fr 1.2fr 0.8fr; gap: 1.5rem; }
        
        /* Typography */
        h1, h2, h3 { font-family: 'Outfit', sans-serif; margin-bottom: 0.5rem; }
        .text-gradient { background: linear-gradient(135deg, var(--portal-accent), var(--portal-accent-blue)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 800; }
        
        /* Simple Sidebar Overlays */
        .sidebar-nav ul { list-style: none; padding: 1rem; margin:0; }
        .sidebar-nav a { display: flex; align-items: center; gap: 1rem; padding: 0.8rem 1.2rem; color: var(--text-muted); text-decoration: none; border-radius: 12px; font-weight: 500; font-size: 0.95rem; }
        .sidebar-nav li.active a { background: rgba(139, 92, 246, 0.1); color: var(--portal-accent); }
        .portal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
    </style>
    <!-- External CSS Link still provided for extra styles -->
    <link rel="stylesheet" href="portal-style.css?v=<?= time() ?>">
</head>
<body class="portal-body">
    <div class="portal-layout">
        <?php include 'student_sidebar.php'; ?>

        <main class="portal-main-area">
            <?php include 'student_header.php'; ?>

            <div class="dashboard-content-v2">
                <div class="courses-row">
                    <?php if (empty($enrollments)): ?>
                        <div class="portal-glass-panel" style="text-align:center; padding:3rem;">
                            <h3>No active courses.</h3>
                            <p style="color:var(--text-muted); margin-bottom:1.5rem;">Ready to start your journey?</p>
                            <a href="courses.php" style="background:var(--portal-accent); color:white; padding:10px 25px; border-radius:10px; text-decoration:none; font-weight:700;">Explore Courses</a>
                        </div>
                    <?php else: ?>
                        <?php foreach (array_slice($enrollments, 0, 2) as $index => $course): ?>
                            <div class="portal-glass-panel" style="border-left: 5px solid <?= $index % 2 == 0 ? 'var(--portal-accent-blue)' : 'var(--portal-accent)' ?>;">
                                <div style="display:flex; justify-content:space-between; margin-bottom:1rem;">
                                    <div style="font-size:1.2rem; font-weight:800;"><?= htmlspecialchars($course['title']) ?></div>
                                    <i class="fas fa-ellipsis-v" style="color:var(--text-muted);"></i>
                                </div>
                                <p style="font-size:0.85rem; color:var(--text-muted);"><?= htmlspecialchars($course['instructor']) ?></p>
                                <div style="margin-top:1.5rem;">
                                    <div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:0.5rem; color:var(--text-muted);">
                                        <span>Course Progress</span>
                                        <span>75%</span>
                                    </div>
                                    <div style="height:6px; background:rgba(255,255,255,0.05); border-radius:10px;">
                                        <div style="width:75%; height:100%; background:<?= $index % 2 == 0 ? 'var(--portal-accent-blue)' : 'var(--portal-accent)' ?>; border-radius:10px; box-shadow:0 0 10px <?= $index % 2 == 0 ? 'var(--portal-accent-blue)' : 'var(--portal-accent)' ?>;"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="dashboard-bottom-grid">
                    <div class="portal-glass-panel">
                        <h3>Member Stats</h3>
                        <div style="display:flex; justify-content:space-around; align-items:center; margin-top:1.5rem;">
                            <div style="text-align:center;">
                                <div style="font-size:2rem; font-weight:800; color:var(--portal-accent);"><?= count($badges) ?></div>
                                <p style="font-size:0.75rem; color:var(--text-muted);">Badges</p>
                            </div>
                            <div style="text-align:center;">
                                <div style="font-size:2rem; font-weight:800; color:var(--portal-accent-blue);"><?= $xp ?></div>
                                <p style="font-size:0.75rem; color:var(--text-muted);">Total XP</p>
                            </div>
                        </div>
                    </div>

                    <div class="portal-glass-panel">
                        <h3>Upcoming Events</h3>
                        <div style="margin-top:1rem;">
                            <?php foreach (array_slice($upcomingEvents, 0, 3) as $event): ?>
                                <div style="display:flex; justify-content:space-between; padding:0.8rem 0; border-bottom:1px solid rgba(255,255,255,0.03);">
                                    <span style="font-weight:600; font-size:0.9rem;"><?= htmlspecialchars($event['title']) ?></span>
                                    <span style="font-size:0.75rem; color:var(--portal-accent-blue);"><?= date('M d', strtotime($event['event_date'])) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="portal-glass-panel" style="text-align:center;">
                        <div style="font-weight:800; margin-bottom:1rem;"><?= date('F Y') ?></div>
                        <div style="display:grid; grid-template-columns:repeat(7,1fr); gap:5px; font-size:0.7rem;">
                             <?php for($i=1; $i<=date('t'); $i++) echo "<span ".($i==date('j')?"style='background:var(--portal-accent-blue); color:white; border-radius:50%; width:20px; height:20px; line-height:20px; margin:0 auto;'":"").">$i</span>"; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <?php include 'student_scripts.php'; ?>
</body>
</html>
