<?php
require_once 'Auth.php';
require_once 'Database.php';
require_once 'Site.php';
require_once 'BoardMember.php';
require_once 'Event.php';
require_once 'Form.php';

$db = new Database();
$site = new Site();
$boardModel = new BoardMember();
$eventModel = new Event();
$formModel = new Form();
$msg = '';

// Handle Club Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_club'])) {
    $sql = "INSERT INTO members (full_name, email, student_id, department) VALUES (?, ?, ?, ?)";
    try {
        $db->query($sql, [
            $_POST['full_name'],
            $_POST['email'],
            $_POST['student_id'],
            $_POST['department']
        ]);
        $msg = "Thanks for registering, please wait for the HR to contact you.";
    } catch (Exception $e) {
        $msg = "Error registering: " . $e->getMessage();
    }
}

// Fetch Dynamic Content
$about = $site->get('home_about');
$boardIntro = $site->get('home_board_intro');
$goals = $site->get('home_goals');
$heroBadge = $site->get('hero_badge');
$heroTitle = $site->get('hero_title');
$heroSubtitle = $site->get('hero_subtitle');
$headEvents = $site->get('header_events');
$headRegs = $site->get('header_registrations');
$headLead = $site->get('header_leadership');
$headJoin = $site->get('header_join');
$boardMembers = $boardModel->getAll();

// Fetch Recent Session Records
$recentRecords = $db->query("SELECT ce.*, c.title as course_title FROM course_extras ce JOIN courses c ON ce.course_id = c.id WHERE ce.type = 'record' ORDER BY ce.created_at DESC LIMIT 6")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IEEE MIU - Empowering Innovation</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="container">
    
    <!-- Hero Section -->
    <section class="hero-section glass-panel">
        <div class="hero-content">
            <span class="badge"><?= htmlspecialchars($heroBadge) ?></span>
            <h1><?= $heroTitle ?></h1>
            <p class="hero-subtitle">
                <?= htmlspecialchars($heroSubtitle) ?>
            </p>
            <div class="hero-btns">
                <a href="courses.php" class="btn btn-primary">
                    Explore Our Courses <i class="fas fa-arrow-right"></i>
                </a>
                <a href="#about" class="btn btn-outline">Learn More</a>
            </div>
        </div>
        <div class="hero-image">
            <!-- Decorative elements or a specific image could go here -->
             <div class="hero-blob"></div>
        </div>
    </section>

    <!-- Dynamic About/Goals Section -->
    <section id="about" class="info-grid">
        <div class="glass-panel info-card">
            <div class="icon-box"><i class="fas fa-microchip"></i></div>
            <?= $about ?>
        </div>
        <div class="glass-panel info-card">
            <div class="icon-box"><i class="fas fa-bullseye"></i></div>
            <?= $goals ?>
        </div>
    </section>

    <!-- Events Section -->
    <?php $events = $eventModel->getAll(); ?>
    <?php if (!empty($events)): ?>
    <section class="section-container">
        <div class="section-header">
            <h2 class="section-title"><?= $headEvents ?></h2>
            <p class="section-subtitle">Don't miss out on our latest workshops and gatherings.</p>
        </div>
        <div class="info-grid">
            <?php foreach ($events as $event): ?>
            <div class="glass-panel event-card">
                <div class="event-image">
                    <img src="<?= htmlspecialchars($event['image_path']) ?>" alt="<?= htmlspecialchars($event['title']) ?>">
                    <div class="event-date">
                        <?= date('d M', strtotime($event['event_date'])) ?>
                    </div>
                </div>
                <div class="event-body">
                    <h3><?= htmlspecialchars($event['title']) ?></h3>
                    <p><?= htmlspecialchars($event['description']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Open Registrations -->
    <?php $forms = $formModel->getAll(); ?>
    <?php if (!empty($forms)): ?>
    <section class="section-container">
        <div class="section-header">
            <h2 class="section-title"><?= $headRegs ?></h2>
        </div>
        <div class="info-grid">
            <?php foreach ($forms as $form): ?>
            <a href="view_form.php?id=<?= $form['id'] ?>" class="glass-panel registration-card">
                <div class="reg-accent"></div>
                <div>
                    <h3><?= htmlspecialchars($form['title']) ?></h3>
                    <p><?= htmlspecialchars($form['description']) ?></p>
                </div>
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Recent Session Records -->
    <?php if (!empty($recentRecords)): ?>
    <section class="section-container">
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-play-circle" style="color: #ff4757;"></i> Recent Session Records</h2>
            <p class="section-subtitle">Catch up on the latest technical sessions and track lectures.</p>
        </div>
        <div class="info-grid">
            <?php foreach ($recentRecords as $record): ?>
            <div class="glass-panel record-card">
                <div class="record-badge"><?= htmlspecialchars($record['course_title']) ?></div>
                <div class="record-main">
                    <div class="record-play-icon"><i class="fas fa-play"></i></div>
                    <h3><?= htmlspecialchars($record['title']) ?></h3>
                </div>
                <p><?= htmlspecialchars($record['content']) ?></p>
                <div class="record-footer">
                    <span><i class="far fa-clock"></i> <?= date('M d, Y', strtotime($record['created_at'])) ?></span>
                    <a href="course_details.php?id=<?= $record['course_id'] ?>" class="btn-text">View Session <i class="fas fa-external-link-alt"></i></a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Leadership Section -->
    <section class="section-container">
        <div class="glass-panel leadership-box">
            <div class="section-header">
                <h2 class="section-title"><?= $headLead ?></h2>
                <div class="leader-intro"><?= $boardIntro ?></div>
            </div>
            
            <div class="leadership-committees">
                <?php 
                $currentCommittee = '';
                foreach ($boardMembers as $bm): 
                    if ($currentCommittee !== $bm['committee']): 
                        if ($currentCommittee !== '') echo '</div></div>'; // Close previous grid and section
                        $currentCommittee = $bm['committee'];
                ?>
                    <div class="committee-section">
                        <h3 class="committee-title text-gradient"><?= ucwords($currentCommittee) ?> Committee</h3>
                        <div class="board-grid">
                <?php endif; ?>
                    <div class="board-card glass-panel">
                        <div class="member-photo">
                            <img src="<?= htmlspecialchars($bm['photo_url']) ?>" alt="<?= htmlspecialchars($bm['name']) ?>">
                        </div>
                        <h3><?= htmlspecialchars($bm['name']) ?></h3>
                        <p class="member-role"><?= htmlspecialchars($bm['role']) ?></p>
                    </div>
                <?php endforeach; ?>
                <?php if ($currentCommittee !== '') echo '</div></div>'; ?>
            </div>
        </div>
    </section>

    <!-- Join Us Section -->
    <section class="join-section">
        <div class="glass-panel registration-form-box">
            <div class="form-header">
                <h2 class="text-gradient"><?= $headJoin ?></h2>
                <p>Register today to start your journey with IEEE MIU.</p>
            </div>
            
            <?php if($msg): ?>
                <div class="alert <?= strpos($msg, 'Error') !== false ? 'alert-danger' : 'alert-success' ?>">
                    <i class="fas <?= strpos($msg, 'Error') !== false ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
                    <?= $msg ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="styled-form">
                <input type="hidden" name="join_club" value="1">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-input" required placeholder="John Doe">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-input" required placeholder="john@example.com">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Student ID</label>
                        <input type="text" name="student_id" class="form-input" placeholder="20202020">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" class="form-input" placeholder="Computer Science">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-full">
                    Register Now <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </section>

</main>

<footer class="main-footer">
    <div class="container footer-content">
        <p>&copy; <?= date('Y') ?> IEEE MIU Student Branch.</p>
        <p>Created with <i class="fas fa-heart" style="color: #ff4757;"></i> by <a href="https://www.linkedin.com/in/karim-wael-40132b360/" target="_blank" class="text-gradient">Karim Wael</a></p>
    </div>
</footer>

<style>
/* Page Specific Styles */
.hero-section {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 4rem;
    padding: clamp(2rem, 8vw, 6rem);
    margin-bottom: 4rem;
    align-items: center;
    position: relative;
    overflow: hidden;
}

.badge {
    display: inline-block;
    padding: 0.4rem 1rem;
    background: rgba(0, 243, 255, 0.1);
    color: var(--primary-neon);
    border-radius: 100px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    border: 1px solid rgba(0, 243, 255, 0.2);
}

.hero-subtitle {
    font-size: 1.2rem;
    color: var(--text-muted);
    margin-bottom: 2.5rem;
    max-width: 600px;
}

.hero-btns {
    display: flex;
    gap: 1.5rem;
}

.hero-image {
    position: relative;
    height: 100%;
}

.hero-blob {
    width: 100%;
    aspect-ratio: 1;
    background: linear-gradient(135deg, var(--primary-neon), var(--secondary-neon));
    filter: blur(80px);
    opacity: 0.2;
    border-radius: 50%;
    animation: pulse 8s infinite alternate;
}

@keyframes pulse {
    from { transform: scale(0.8) translate(0, 0); }
    to { transform: scale(1.2) translate(10px, 10px); }
}

.section-container { margin-bottom: 6rem; }
.section-header { margin-bottom: 3rem; }
.section-subtitle { color: var(--text-muted); margin-top: 0.5rem; }

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
    margin-bottom: 6rem;
}

.info-card { padding: 3rem; }
.icon-box {
    width: 60px;
    height: 60px;
    background: var(--glass-bg-bright);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--primary-neon);
    margin-bottom: 1.5rem;
}

.event-card { overflow: hidden; padding: 0; }
.event-image { height: 200px; position: relative; }
.event-image img { width: 100%; height: 100%; object-fit: cover; }
.event-date {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: rgba(11, 15, 26, 0.8);
    backdrop-filter: blur(10px);
    padding: 0.5rem 1rem;
    border-radius: 10px;
    font-weight: 700;
    color: var(--primary-neon);
    border: 1px solid var(--glass-border);
}
.event-body { padding: 2rem; }

.registration-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 2rem;
    text-decoration: none;
    color: inherit;
    position: relative;
    overflow: hidden;
}

.reg-accent {
    position: absolute;
    left: 0;
    top: 0;
    width: 5px;
    height: 100%;
    background: var(--secondary-neon);
}

.registration-card i {
    color: var(--secondary-neon);
    font-size: 1.2rem;
    transition: var(--transition);
}

.registration-card:hover i { transform: translateX(5px); }

.leadership-box { padding: 4rem; }
.board-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 2rem;
}

.board-card { padding: 2rem; text-align: center; }
.board-card:hover { transform: translateY(-10px); }
.member-photo {
    width: 120px;
    height: 120px;
    margin: 0 auto 1.5rem;
    border-radius: 50%;
    padding: 5px;
    border: 3px solid var(--primary-neon);
    overflow: hidden;
}
.member-photo img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
.member-role { color: var(--secondary-neon); font-weight: 600; margin-top: 0.5rem; }

.join-section { margin-bottom: 6rem; }
.registration-form-box { max-width: 800px; margin: 0 auto; padding: 4rem; }
.form-header { text-align: center; margin-bottom: 3rem; }
.form-header h2 { font-size: 3rem; margin-bottom: 0.5rem; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
.btn-full { width: 100%; justify-content: center; }

.alert {
    padding: 1rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    font-weight: 500;
}
.alert-success { background: rgba(0, 255, 170, 0.1); color: #00ffaa; border: 1px solid rgba(0, 255, 170, 0.2); }
.alert-danger { background: rgba(255, 71, 87, 0.1); color: #ff4757; border: 1px solid rgba(255, 71, 87, 0.2); }

.main-footer { padding: 4rem 2rem; border-top: 1px solid var(--glass-border); margin-top: 4rem; }
.footer-content { display: flex; justify-content: space-between; color: var(--text-muted); font-size: 0.95rem; }
.footer-content a { text-decoration: none; font-weight: 600; }

/* Record Cards */
.record-card { padding: 2.5rem; display: flex; flex-direction: column; gap: 1rem; position: relative; }
.record-badge { position: absolute; top: 1.5rem; right: 1.5rem; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); background: rgba(255,255,255,0.05); padding: 0.3rem 0.6rem; border-radius: 4px; }
.record-main { display: flex; align-items: center; gap: 1.5rem; }
.record-play-icon { width: 50px; height: 50px; background: rgba(255, 71, 87, 0.1); color: #ff4757; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; border: 1px solid rgba(255, 71, 87, 0.2); }
.record-card h3 { font-size: 1.2rem; }
.record-card p { color: var(--text-muted); font-size: 0.9rem; margin-left: 4.2rem; }
.record-footer { margin-top: auto; padding-top: 1.5rem; border-top: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; color: var(--text-muted); }
.btn-text { color: var(--primary-neon); text-decoration: none; font-weight: 700; transition: 0.3s; }
.btn-text:hover { color: var(--secondary-neon); }

@media (max-width: 992px) {
    .hero-section { grid-template-columns: 1fr; text-align: center; gap: 2rem; }
    .hero-subtitle { margin: 0 auto 2.5rem; }
    .hero-btns { justify-content: center; }
    .info-grid { grid-template-columns: 1fr; }
    .form-row { grid-template-columns: 1fr; gap: 1rem; }
}

@media (max-width: 768px) {
    .leadership-box { padding: 2rem; }
    .registration-form-box { padding: 2rem; }
    .footer-content { flex-direction: column; text-align: center; gap: 1rem; }
}
</style>

</body>
</html>

