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
$boardMembers = $boardModel->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IEEE MIU</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hero {
            padding: 4rem 2rem;
            text-align: center;
            background: linear-gradient(rgba(15, 23, 42, 0.8), rgba(15, 23, 42, 0.9)), url('https://images.unsplash.com/photo-1517077304055-6e89abbf09b0?auto=format&fit=crop&w=1500&q=80');
            background-size: cover;
            background-position: center;
            border-radius: 20px;
            margin-bottom: 3rem;
            border: 1px solid var(--glass-border);
        }
        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
        }
        .section-title {
            font-size: 2rem;
            color: var(--primary-neon);
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--secondary-neon);
            padding-left: 1rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }
        .board-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
        }
        .board-card {
            text-align: center;
            padding: 1.5rem;
            transition: transform 0.3s;
        }
        .board-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.08);
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    
    <div class="hero">
        <h1>Welcome to IEEE MIU</h1>
        <p style="font-size: 1.2rem; color: var(--text-muted); max-width: 600px; margin: 0 auto 2rem;">
            Empowering students to build the technology of tomorrow. Join our workshops, competitions, and community.
        </p>
        <a href="courses.php" class="btn btn-primary" style="font-size: 1.1rem; padding: 1rem 2rem;">
            Explore Our Courses <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <!-- Dynamic Content Section -->
    <div class="info-grid">
        <div class="glass-panel" style="padding: 2rem;">
            <?= $about ?>
        </div>
        <div class="glass-panel" style="padding: 2rem;">
            <?= $goals ?>
        </div>
    </div>

    <!-- Events Section -->
    <?php $events = $eventModel->getAll(); ?>
    <?php if (!empty($events)): ?>
    <div style="margin-bottom: 4rem;">
        <h2 class="section-title">Coming Events</h2>
        <div class="info-grid">
            <?php foreach ($events as $event): ?>
            <div class="glass-panel" style="overflow: hidden; padding: 0;">
                <img src="<?= htmlspecialchars($event['image_path']) ?>" style="width: 100%; height: 200px; object-fit: cover;">
                <div style="padding: 1.5rem;">
                    <div style="color: var(--primary-neon); margin-bottom: 0.5rem; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;">
                        <?= date('M d, Y', strtotime($event['event_date'])) ?>
                    </div>
                    <h3 style="margin-bottom: 1rem;"><?= htmlspecialchars($event['title']) ?></h3>
                    <p style="color: var(--text-muted); margin-bottom: 1.5rem;"><?= htmlspecialchars($event['description']) ?></p>
                    <!-- Will link to dynamic form later if needed -->
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Forms Section -->
    <?php $forms = $formModel->getAll(); ?>
    <?php if (!empty($forms)): ?>
    <div style="margin-bottom: 4rem;">
        <h2 class="section-title">Open Registrations</h2>
        <div class="info-grid">
            <?php foreach ($forms as $form): ?>
            <div class="glass-panel" style="padding: 2rem; position: relative; transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: var(--secondary-neon);"></div>
                <h3 style="margin-bottom: 0.5rem;"><?= htmlspecialchars($form['title']) ?></h3>
                <p style="color: var(--text-muted); margin-bottom: 1.5rem;"><?= htmlspecialchars($form['description']) ?></p>
                <a href="view_form.php?id=<?= $form['id'] ?>" class="btn btn-primary" style="font-size: 0.9rem;">
                    Fill Form <i class="fas fa-arrow-right" style="margin-left: 0.5rem;"></i>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div style="margin-bottom: 4rem;">
        <h2 class="section-title">Club Leadership</h2>
        <div class="glass-panel" style="padding: 2rem;">
            <?= $boardIntro ?>
            
            <div class="board-grid" style="margin-top: 2rem;">
                <?php foreach ($boardMembers as $bm): ?>
                    <div class="glass-panel board-card">
                        <img src="<?= htmlspecialchars($bm['photo_url']) ?>" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem; border: 3px solid var(--primary-neon);">
                        <h3 style="margin-bottom: 0.5rem; font-size: 1.2rem;"><?= htmlspecialchars($bm['name']) ?></h3>
                        <p style="color: var(--secondary-neon);"><?= htmlspecialchars($bm['role']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Registration Form -->
    <div class="glass-panel" style="padding: 3rem; max-width: 600px; margin: 0 auto;">
        <h2 style="text-align: center; margin-bottom: 2rem; color: var(--secondary-neon);">Join the Club</h2>
        
        <?php if($msg): ?>
            <div style="background: rgba(0, 255, 0, 0.1); color: #00ffaa; padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px;">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="join_club" value="1">
            
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-input" required placeholder="John Doe">
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-input" required placeholder="john@example.com">
            </div>

            <div class="form-group">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label class="form-label">Student ID</label>
                        <input type="text" name="student_id" class="form-input" placeholder="20202020">
                    </div>
                    <div>
                        <label class="form-label">Department</label>
                        <input type="text" name="department" class="form-input" placeholder="Computer Science">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                Register Now
            </button>
        </form>
    </div>

</div>

<footer style="text-align: center; padding: 3rem; color: var(--text-muted); margin-top: 4rem;">
    <p>&copy; <?= date('Y') ?> IEEE MIU Student Branch. Created by <a href="https://www.linkedin.com/in/karim-wael-40132b360/" target="_blank" style="color: var(--secondary-neon); text-decoration: none;">Karim Wael</a>.</p>
</footer>

</body>
</html>
