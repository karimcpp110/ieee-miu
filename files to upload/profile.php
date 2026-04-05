<?php
require_once 'Database.php';
require_once 'Gamification.php';

$studentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$studentId) {
    die("Invalid Profile ID.");
}

$db = new Database();

// Auto-migrate if needed (adding is_public to students)
try {
    $columns = $db->query("DESCRIBE students")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('is_public', $columns)) {
        $db->query("ALTER TABLE students ADD COLUMN is_public TINYINT(1) DEFAULT 1");
    }
} catch (Exception $e) {}

// Fetch student profile
$student = $db->query("SELECT id, full_name, student_id, created_at, is_public FROM students WHERE id = ?", [$studentId])->fetch();

if (!$student) {
    die("Profile not found.");
}

if (!(bool)$student['is_public']) {
    // If the currently logged in student is viewing their own profile, let them view it
    session_start();
    $isOwner = isset($_SESSION['student_account_id']) && $_SESSION['student_account_id'] == $studentId;
    
    // Admins can also view private profiles
    require_once 'Auth.php';
    $isAdmin = Auth::isAdmin();

    if (!$isOwner && !$isAdmin) {
        // Locked Profile View
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Private Profile - IEEE MIU</title>
            <link rel="stylesheet" href="style.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        </head>
        <body class="dashboard-page" style="display:flex; justify-content:center; align-items:center; height:100vh;">
            <div class="glass-panel" style="padding: 4rem; text-align:center; max-width: 500px;">
                <i class="fas fa-lock" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 1.5rem;"></i>
                <h2 class="text-gradient">This Profile is Private</h2>
                <p class="text-muted" style="margin-top: 1rem;">The student has chosen not to share their achievements publicly.</p>
                <a href="index.php" class="btn btn-primary" style="margin-top: 2rem;">Return Home</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

$gamification = new Gamification();
$badges = $gamification->getUserBadges($studentId);
$gProfile = $gamification->getUserGamificationProfile($studentId);

// Fetch stats to get "Events Attended" which is not part of Gamification profile yet
$stats = $db->query("SELECT events_attended FROM user_stats WHERE user_id = ?", [$studentId])->fetch();
$eventsAttended = $stats ? (int)$stats['events_attended'] : 0;

$coursesCompleted = $gProfile['courses_completed'];
$xp = $gProfile['xp'];
$masteryLevel = $gProfile['level'];

// Fetch completed courses for certificates
$completedCourses = $db->query("
    SELECT c.id, c.title, c.thumbnail, c.instructor, c.duration, p.completed_at 
    FROM user_course_progress p
    JOIN courses c ON p.course_id = c.id
    WHERE p.user_id = ? AND p.status = 'completed'
    ORDER BY p.completed_at DESC
", [$studentId])->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($student['full_name']) ?> - Student Profile | IEEE MIU</title>
    <link rel="stylesheet" href="style.css?v=21">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-hero {
            position: relative;
            padding: 4rem 2rem;
            text-align: center;
            border-bottom: 1px solid var(--glass-border);
            overflow: hidden;
            margin-bottom: 3rem;
        }

        .hero-bg-glow {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(0, 243, 255, 0.08) 0%, rgba(0,0,0,0) 70%);
            z-index: 0;
            pointer-events: none;
        }

        .profile-avatar-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 2rem;
            z-index: 1;
        }

        .profile-avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 4px solid var(--primary-neon);
            box-shadow: 0 0 30px rgba(0, 243, 255, 0.4);
            object-fit: cover;
        }

        .mastery-badge {
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, var(--primary-neon), var(--secondary-neon));
            color: #000;
            padding: 0.3rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            box-shadow: 0 5px 15px rgba(0,0,0,0.5);
            white-space: nowrap;
        }

        .profile-name {
            font-size: 3rem;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .profile-meta {
            color: var(--text-muted);
            font-size: 1.1rem;
            position: relative;
            z-index: 1;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 1.5rem;
            max-width: 800px;
            margin: 3rem auto 0;
            position: relative;
            z-index: 1;
        }

        .stat-box {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--glass-border);
            padding: 1.5rem;
            border-radius: 12px;
            transition: var(--transition);
        }

        .stat-box:hover {
            border-color: var(--primary-neon);
            transform: translateY(-5px);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary-neon);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
        }

        /* Achievements Section */
        .section-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 2rem;
            font-size: 1.8rem;
        }

        .section-title i {
            color: var(--secondary-neon);
        }

        .badges-container {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 4rem;
        }

        .badge-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--glass-border);
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            width: calc(25% - 1.125rem);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .badge-card:hover {
            border-color: var(--warn-gold, #ffa502);
            box-shadow: 0 0 20px rgba(255, 165, 2, 0.1);
        }

        .badge-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, #ffa502, #ff7b00);
            opacity: 0;
            transition: var(--transition);
        }

        .badge-card:hover::before { opacity: 1; }

        .badge-icon {
            font-size: 3rem;
            color: #ffa502;
            margin-bottom: 1rem;
            filter: drop-shadow(0 0 10px rgba(255, 165, 2, 0.3));
        }

        /* Certificates Section */
        .cert-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }

        .cert-card {
            display: flex;
            flex-direction: column;
            height: 100%;
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            overflow: hidden;
            transition: var(--transition);
        }

        .cert-card:hover {
            border-color: var(--primary-neon);
            box-shadow: 0 10px 30px rgba(0, 243, 255, 0.1);
        }

        .cert-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-bottom: 1px solid var(--glass-border);
        }

        .cert-content {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .cert-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .cert-meta {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
        }

        .cert-actions {
            margin-top: auto;
            display: flex;
            gap: 10px;
        }

        .btn-linkedin {
            background: #0077b5;
            color: white;
            border: none;
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            padding: 0.8rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn-linkedin:hover {
            background: #005885;
        }

        .btn-view-cert {
            flex: 1;
            background: transparent;
            color: var(--primary-neon);
            border: 1px solid var(--primary-neon);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            padding: 0.8rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-view-cert:hover {
            background: rgba(0, 243, 255, 0.1);
            color: #fff;
        }

        @media (max-width: 768px) {
            .badge-card { width: calc(50% - 0.75rem); }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 1rem; }
            .cert-actions { flex-direction: column; }
        }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <main class="container" style="max-width: 1200px;">
        
        <div class="profile-hero">
            <div class="hero-bg-glow"></div>
            
            <div class="profile-avatar-container">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($student['full_name']) ?>&background=00629B&color=fff&size=200&bold=true" class="profile-avatar" alt="Profile">
                <div class="mastery-badge"><?= $masteryLevel ?></div>
            </div>

            <h1 class="profile-name text-gradient"><?= htmlspecialchars($student['full_name']) ?></h1>
            <p class="profile-meta">
                ID: <?= htmlspecialchars($student['student_id'] ?? 'N/A') ?> • 
                Member since <?= date('F Y', strtotime($student['created_at'])) ?>
            </p>

            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value"><?= $coursesCompleted ?></div>
                    <div class="stat-label">Certifications</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?= count($badges) ?></div>
                    <div class="stat-label">Badges Earned</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?= $xp ?></div>
                    <div class="stat-label">Total XP</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?= $eventsAttended ?></div>
                    <div class="stat-label">Events Attended</div>
                </div>
            </div>
        </div>

        <?php if (!empty($badges)): ?>
        <section>
            <h2 class="section-title"><i class="fas fa-shield-alt"></i> Verified Badges</h2>
            <div class="badges-container">
                <?php foreach ($badges as $badge): ?>
                <div class="badge-card">
                    <i class="fas fa-medal badge-icon"></i>
                    <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem;"><?= htmlspecialchars($badge['name']) ?></h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted);"><?= htmlspecialchars($badge['description']) ?></p>
                    <div style="font-size: 0.7rem; color: var(--primary-neon); margin-top: 1rem;">
                        Awarded <?= date('M d, Y', strtotime($badge['awarded_at'])) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <section>
            <h2 class="section-title"><i class="fas fa-certificate"></i> Accomplishments & Certifications</h2>
            
            <?php if (empty($completedCourses)): ?>
                <div class="glass-panel" style="padding: 3rem; text-align: center; border-radius: 12px;">
                    <i class="fas fa-book-reader" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                    <h3 style="color: var(--text-main);">Learning Journey Beginning</h3>
                    <p style="color: var(--text-muted);">This student is currently working towards their first certification.</p>
                </div>
            <?php else: ?>
                <div class="cert-grid">
                    <?php foreach ($completedCourses as $cert): 
                        // Generate LinkedIn "Add to Profile" URL
                        $certName = urlencode($cert['title']);
                        $orgName = urlencode("IEEE MIU Student Branch");
                        $issueYear = date('Y', strtotime($cert['completed_at']));
                        $issueMonth = date('n', strtotime($cert['completed_at']));
                        // Public verification URL
                        $certUrl = urlencode("https://" . $_SERVER['HTTP_HOST'] . "/verify_certificate.php?s=" . $studentId . "&c=" . $cert['id']);
                        $linkedinUrl = "https://www.linkedin.com/profile/add?startTask=CERTIFICATION_NAME&name={$certName}&organizationName={$orgName}&issueYear={$issueYear}&issueMonth={$issueMonth}&certUrl={$certUrl}";
                    ?>
                    <div class="cert-card">
                        <img src="<?= htmlspecialchars($cert['thumbnail']) ?>" alt="Course Thumbnail" class="cert-img">
                        <div class="cert-content">
                            <h3 class="cert-title"><?= htmlspecialchars($cert['title']) ?></h3>
                            <div class="cert-meta">
                                <div><i class="fas fa-calendar-check"></i> Completed on <?= date('M d, Y', strtotime($cert['completed_at'])) ?></div>
                                <div style="margin-top: 5px;"><i class="fas fa-chalkboard-teacher"></i> Instructor: <?= htmlspecialchars($cert['instructor']) ?></div>
                            </div>
                            <div class="cert-actions">
                                <a href="verify_certificate.php?s=<?= $studentId ?>&c=<?= $cert['id'] ?>" target="_blank" class="btn-view-cert" title="Verify & View File">
                                    <i class="fas fa-external-link-alt"></i> View
                                </a>
                                <a href="<?= $linkedinUrl ?>" target="_blank" class="btn-linkedin">
                                    <i class="fab fa-linkedin"></i> Add to Profile
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <?php include 'footer.php'; ?>

</body>
</html>
