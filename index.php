<?php
require_once 'Auth.php';
require_once 'Database.php';
require_once 'Site.php';
require_once 'BoardMember.php';
require_once 'Event.php';
require_once 'Form.php';

// Safe include for new Gallery model
if (file_exists('galleryModel.php')) {
    require_once 'galleryModel.php';
}

$db = new Database();
$site = new Site();
$boardModel = new BoardMember();
$eventModel = new Event();
$formModel = new Form();
$galleryModel = (class_exists('GalleryModel')) ? new GalleryModel() : null;
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
$bestMembers = $boardModel->getFeatured();

// Fetch Recent Session Records
$recentRecords = $db->query("SELECT ce.*, c.title as course_title FROM course_extras ce JOIN courses c ON ce.course_id =
c.id WHERE ce.type = 'record' ORDER BY ce.created_at DESC LIMIT 6")->fetchAll();

// Fetch Shuffled Gallery for Slideshow
$shuffleGallery = ($galleryModel) ? $galleryModel->getGlobalShuffle(20) : [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IEEE MIU - Empowering Innovation</title>

    <!-- SEO & Social Meta Tags -->
    <meta name="description"
        content="IEEE MIU Student Branch: Empowering innovation, fostering technical excellence, and building a community of future leaders at Misr International University.">
    <meta name="keywords"
        content="IEEE, MIU, Student Branch, Engineering, Technology, Innovation, Misr International University">
    <meta name="author" content="IEEE MIU Student Branch">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://ieeemiu.org/">
    <meta property="og:title" content="IEEE MIU - Empowering Innovation">
    <meta property="og:description"
        content="Join the leading technical community at MIU. Workshops, events, and a network of passionate innovators.">
    <meta property="og:image" content="https://ieeemiu.org/assets/og-image.jpg">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://ieeemiu.org/">
    <meta name="twitter:title" content="IEEE MIU - Empowering Innovation">
    <meta name="twitter:description"
        content="Join the leading technical community at MIU. Workshops, events, and a network of passionate innovators.">
    <meta name="twitter:image" content="https://ieeemiu.org/assets/og-image.jpg">

    <link rel="stylesheet" href="style.css?v=6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Living Mosaic Styles */
        .mosaic-container {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            grid-template-rows: 1fr 1fr;
            gap: 1rem;
            width: 100%;
            height: 480px;
            perspective: 1000px;
        }

        .mosaic-item {
            position: relative;
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            border: 1px solid var(--glass-border);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transition: all 0.6s cubic-bezier(0.23, 1, 0.32, 1);
            cursor: pointer;
        }

        .mosaic-item:hover {
            transform: scale(1.03) translateZ(20px);
            z-index: 10;
            border-color: var(--primary-neon);
            box-shadow: 0 20px 50px rgba(0, 98, 155, 0.4);
        }

        .mosaic-item.large {
            grid-row: span 2;
        }

        .mosaic-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(0.8);
            transition: all 0.8s ease;
        }

        .mosaic-item:hover img {
            transform: scale(1.1);
            filter: brightness(1);
        }

        .mosaic-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1rem;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
            color: #fff;
            font-size: 0.8rem;
            font-weight: 600;
            opacity: 0;
            transform: translateY(10px);
            transition: 0.4s;
        }

        .mosaic-item:hover .mosaic-overlay {
            opacity: 1;
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .mosaic-container {
                height: 350px;
                gap: 0.5rem;
            }
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <main class="container">

        <!-- Hero Section -->
        <section class="hero-section glass-panel">
            <div class="hero-content">
                <span class="badge"><?= $heroBadge ?></span>
                <h1><?= $heroTitle ?></h1>
                <p class="hero-subtitle">
                    <?= $heroSubtitle ?>
                </p>
                <div class="hero-btns">
                    <a href="courses.php" class="btn btn-primary">
                        Explore Our Courses <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="#about" class="btn btn-outline">Learn More</a>
                </div>
            </div>
            <div class="hero-image">
                <?php if (count($shuffleGallery) >= 3): ?>
                    <div class="mosaic-container" id="living-mosaic">
                        <div class="mosaic-item large">
                            <img src="<?= htmlspecialchars($shuffleGallery[0]['image_path']) ?>" loading="lazy">
                            <div class="mosaic-overlay">Focus on Technical Excellence</div>
                        </div>
                        <div class="mosaic-item">
                            <img src="<?= htmlspecialchars($shuffleGallery[1]['image_path']) ?>" loading="lazy">
                            <div class="mosaic-overlay">Community Vibrancy</div>
                        </div>
                        <div class="mosaic-item">
                            <img src="<?= htmlspecialchars($shuffleGallery[2]['image_path']) ?>" loading="lazy">
                            <div class="mosaic-overlay">Innovating Together</div>
                        </div>
                    </div>
                <?php elseif (!empty($shuffleGallery)): ?>
                    <div class="mosaic-item active"
                        style="width: 100%; height: 100%; border-radius: var(--border-radius-lg); overflow: hidden;">
                        <img src="<?= htmlspecialchars($shuffleGallery[0]['image_path']) ?>"
                            style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                <?php else: ?>
                    <div class="hero-blob"></div>
                <?php endif; ?>
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
                                <img src="<?= htmlspecialchars($event['image_path']) ?>"
                                    alt="<?= htmlspecialchars($event['title']) ?>">
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
                    <h2 class="section-title"><i class="fas fa-play-circle" style="color: #ff4757;"></i> Recent Session
                        Records</h2>
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
                                <span><i class="far fa-clock"></i>
                                    <?= date('M d, Y', strtotime($record['created_at'])) ?></span>
                                <a href="course_details.php?id=<?= $record['course_id'] ?>" class="btn-text">View Session <i
                                        class="fas fa-external-link-alt"></i></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Best Board Members Section -->
        <?php if (!empty($bestMembers)): ?>
            <section class="section-container">
                <div class="section-header text-center">
                    <h2 class="text-gradient">Featured Leadership</h2>
                    <p class="section-subtitle">The exceptional minds leading our community this season.</p>
                </div>

                <div class="board-grid featured-grid">
                    <?php foreach ($bestMembers as $bm): ?>
                        <div class="board-card glass-panel featured-card">
                            <div class="member-photo-wrapper">
                                <div class="member-glow"></div>
                                <div class="member-photo">
                                    <img src="<?= htmlspecialchars($bm['photo_url']) ?>"
                                        alt="<?= htmlspecialchars($bm['name']) ?>">
                                </div>
                            </div>
                            <div class="member-info">
                                <h3 class="member-name text-gradient"><?= htmlspecialchars($bm['name']) ?></h3>
                                <div class="role-badge-container">
                                    <span class="role-badge"><?= htmlspecialchars($bm['role']) ?></span>
                                </div>
                                <?php if (!empty($bm['bio'])): ?>
                                    <p class="member-bio"><?= htmlspecialchars($bm['bio']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($bm['linkedin_url'])): ?>
                                    <div class="member-social">
                                        <a href="<?= htmlspecialchars($bm['linkedin_url']) ?>" target="_blank" class="linkedin-link"
                                            title="Connect on LinkedIn">
                                            <i class="fab fa-linkedin"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="text-center" style="margin-top: 3rem;">
                    <a href="board.php" class="btn btn-outline">Meet the Entire Board <i class="fas fa-arrow-right"></i></a>
                </div>
            </section>
        <?php else: ?>
            <!-- Fallback if no best members selected -->
            <section class="section-container">
                <div class="glass-panel info-card" style="text-align: center; padding: 4rem 2rem;">
                    <h2 class="text-gradient" style="margin-bottom: 1rem;">Meet Our Leadership</h2>
                    <p style="color: var(--text-muted); margin-bottom: 2.5rem; max-width: 600px; margin-inline: auto;">
                        <?= htmlspecialchars($boardIntro) ?>
                    </p>
                    <a href="board.php" class="btn btn-primary">
                        Explore Our Full Board <i class="fas fa-users-viewfinder"></i>
                    </a>
                </div>
            </section>
        <?php endif; ?>

        <!-- Join Us Section -->
        <section class="join-section">
            <div class="glass-panel registration-form-box">
                <div class="form-header">
                    <h2 class="text-gradient"><?= $headJoin ?></h2>
                    <p>Register today to start your journey with IEEE MIU.</p>
                </div>

                <?php if ($msg): ?>
                    <div class="alert <?= strpos($msg, 'Error') !== false ? 'alert-danger' : 'alert-success' ?>">
                        <i
                            class="fas <?= strpos($msg, 'Error') !== false ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
                        <?= $msg ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="styled-form">
                    <input type="hidden" name="join_club" value="1">

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-input" required
                                placeholder="Your full name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-input" required
                                placeholder="email@example.com">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Student ID</label>
                            <input type="text" name="student_id" class="form-input" placeholder="e.g. 20240001">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" class="form-input" placeholder="e.g. Computer Science">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full" style="margin-top: 2rem;">
                        Register Now <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </section>

    </main>

    <?php include 'footer.php'; ?>

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
            padding: 0.5rem 1.2rem;
            background: rgba(0, 98, 155, 0.08);
            color: var(--primary-neon);
            border-radius: 100px;
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(0, 98, 155, 0.15);
            letter-spacing: 0.5px;
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
            from {
                transform: scale(0.8) translate(0, 0);
            }

            to {
                transform: scale(1.2) translate(10px, 10px);
            }
        }

        .section-container {
            margin-bottom: 6rem;
        }

        .section-header {
            margin-bottom: 3rem;
        }

        .section-subtitle {
            color: var(--text-muted);
            margin-top: 0.5rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 6rem;
        }

        .info-card {
            padding: 3rem;
        }

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

        .event-card {
            overflow: hidden;
            padding: 0;
        }

        .event-image {
            height: 200px;
            position: relative;
        }

        .event-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

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

        .event-body {
            padding: 2rem;
        }

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

        .registration-card:hover i {
            transform: translateX(5px);
        }

        .leadership-box {
            padding: 4rem 2rem;
            background: transparent;
            border: none;
            box-shadow: none;
        }

        .committee-section {
            margin-bottom: 5rem;
        }

        .committee-title {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 2.5rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .committee-title::after {
            content: '';
            height: 1px;
            flex: 1;
            background: linear-gradient(to right, var(--glass-border), transparent);
        }

        .board-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 3rem;
        }

        .board-card {
            padding: 0;
            text-align: center;
            border-radius: var(--border-radius-lg);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.04) 0%, rgba(255, 255, 255, 0.01) 100%);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(15px);
        }

        .board-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(800px circle at var(--mouse-x) var(--mouse-y), rgba(255, 255, 255, 0.06), transparent 40%);
            opacity: 0;
            transition: opacity 0.5s;
        }

        .board-card:hover::before {
            opacity: 1;
        }

        .board-card:hover {
            transform: translateY(-15px) scale(1.02);
            border-color: var(--primary-neon);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 0 20px rgba(0, 98, 155, 0.1);
        }

        .featured-card {
            border: 2px solid rgba(255, 181, 0, 0.15);
            /* Thicker but softer */
            background: linear-gradient(135deg, rgba(255, 181, 0, 0.04) 0%, rgba(255, 255, 255, 0.01) 100%);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .featured-card:hover {
            border-color: rgba(255, 181, 0, 0.4);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5), 0 0 30px rgba(255, 181, 0, 0.1);
            transform: translateY(-10px) scale(1.02);
        }

        .featured-grid {
            justify-content: center;
        }

        .member-bio {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 1.25rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .member-social {
            margin-top: 1.5rem;
            display: flex;
            justify-content: center;
        }

        .linkedin-link {
            color: var(--text-muted);
            font-size: 1.5rem;
            transition: var(--transition);
        }

        .linkedin-link:hover {
            color: #0077B5;
            transform: scale(1.1);
        }

        .member-photo-wrapper {
            padding-top: 3rem;
            margin-bottom: 2rem;
            position: relative;
        }

        .member-photo {
            width: 160px;
            height: 160px;
            margin: 0 auto;
            border-radius: 50%;
            position: relative;
            z-index: 2;
            background: var(--bg-color);
        }

        .member-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            padding: 6px;
            background: var(--bg-color);
            position: relative;
            z-index: 2;
        }

        .member-photo::before {
            content: '';
            position: absolute;
            inset: -3px;
            background: linear-gradient(45deg, var(--primary-neon), var(--secondary-neon));
            border-radius: 50%;
            z-index: 1;
            animation: rotateGlow 4s linear infinite;
        }

        @keyframes rotateGlow {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .member-glow {
            position: absolute;
            inset: 5px;
            background: var(--primary-neon);
            filter: blur(25px);
            opacity: 0;
            transition: var(--transition);
            border-radius: 50%;
        }

        .board-card:hover .member-glow {
            opacity: 0.3;
        }

        .member-info {
            padding: 0 2rem 3rem;
        }

        .member-name {
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
        }

        .role-badge-container {
            display: flex;
            justify-content: center;
        }

        .role-badge {
            padding: 0.5rem 1.5rem;
            background: rgba(0, 181, 226, 0.1);
            /* Cyber Blue base */
            color: var(--secondary-neon);
            border: 1px solid rgba(0, 181, 226, 0.2);
            border-radius: 100px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .join-section {
            margin-bottom: 6rem;
        }

        .registration-form-box {
            max-width: 800px;
            margin: 0 auto;
            padding: 4rem;
        }

        .form-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .form-header h2 {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .btn-full {
            width: 100%;
            justify-content: center;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(0, 255, 170, 0.1);
            color: #00ffaa;
            border: 1px solid rgba(0, 255, 170, 0.2);
        }

        .alert-danger {
            background: rgba(255, 71, 87, 0.1);
            color: #ff4757;
            border: 1px solid rgba(255, 71, 87, 0.2);
        }

        .main-footer {
            padding: 4rem 2rem;
            border-top: 1px solid var(--glass-border);
            margin-top: 4rem;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .footer-content a {
            text-decoration: none;
            font-weight: 600;
        }

        /* Record Cards */
        .record-card {
            padding: 2.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            position: relative;
        }

        .record-badge {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--text-muted);
            background: rgba(255, 255, 255, 0.05);
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
        }

        .record-main {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .record-play-icon {
            width: 52px;
            height: 52px;
            background: rgba(0, 181, 226, 0.1);
            color: var(--secondary-neon);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
            border: 1px solid rgba(0, 181, 226, 0.2);
            box-shadow: 0 0 15px rgba(0, 181, 226, 0.1);
        }

        .record-card h3 {
            font-size: 1.2rem;
        }

        .record-card p {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-left: 4.2rem;
        }

        .record-footer {
            margin-top: auto;
            padding-top: 1.5rem;
            border-top: 1px solid var(--glass-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .btn-text {
            color: var(--primary-neon);
            text-decoration: none;
            font-weight: 700;
            transition: 0.3s;
        }

        .btn-text:hover {
            color: var(--secondary-neon);
        }

        @media (max-width: 992px) {
            .hero-section {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 2rem;
            }

            .hero-subtitle {
                margin: 0 auto 2.5rem;
            }

            .hero-btns {
                justify-content: center;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        @media (max-width: 768px) {
            .leadership-box {
                padding: 2rem;
            }

            .registration-form-box {
                padding: 2rem;
            }

            .footer-content {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
        }
    </style>


    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const mosaicItems = document.querySelectorAll('.mosaic-item img');
            const gallery = <?= json_encode(array_column($shuffleGallery, 'image_path')) ?>;

            if (gallery.length > 3) {
                setInterval(() => {
                    // Randomly pick one of the 3 mosaic images to change
                    const randomIndex = Math.floor(Math.random() * 3);
                    const item = mosaicItems[randomIndex];

                    // Pick a random image from the pool that isn't currently shown
                    const pool = gallery.filter(src => src !== item.src);
                    const newSrc = pool[Math.floor(Math.random() * pool.length)];

                    // Smooth transition
                    item.style.opacity = '0';
                    setTimeout(() => {
                        item.src = newSrc;
                        item.style.opacity = '1';
                    }, 600);
                }, 4000);
            }

            // Mouse interaction
            document.getElementById('board-container')?.addEventListener('mousemove', e => {
                for (const card of document.getElementsByClassName('board-card')) {
                    const rect = card.getBoundingClientRect(),
                        x = e.clientX - rect.left,
                        y = e.clientY - rect.top;

                    card.style.setProperty('--mouse-x', `${x}px`);
                    card.style.setProperty('--mouse-y', `${y}px`);
                }
            });
        });
    </script>
</body>

</html>