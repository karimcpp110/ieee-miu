<?php
require_once 'Auth.php';
require_once 'Database.php';
require_once 'Site.php';
require_once 'BoardMember.php';

$site = new Site();
$boardModel = new BoardMember();

$boardIntro = $site->get('home_board_intro');
$headLead = $site->get('header_leadership');
$boardMembers = $boardModel->getAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Leadership - IEEE MIU</title>
    <link rel="stylesheet" href="style.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

    <?php include 'navbar.php'; ?>

    <main class="container">
        <div class="glass-panel page-header">
            <div class="header-info">
                <h1 class="text-gradient">Club Leadership</h1>
                <p><?= $boardIntro ?></p>
            </div>
        </div>

        <!-- Leadership Section -->
        <div class="leadership-committees" id="board-container">
            <?php
            $currentCommittee = '';
            foreach ($boardMembers as $bm):
                if ($currentCommittee !== $bm['committee']):
                    if ($currentCommittee !== '')
                        echo '</div></div>'; // Close previous grid and section
                    $currentCommittee = $bm['committee'];
                    ?>
                    <div class="committee-section">
                        <h3 class="committee-title text-gradient">
                            <span class="title-text"><?= ucwords($currentCommittee) ?> Committee</span>
                        </h3>
                        <div class="board-grid">
                        <?php endif; ?>
                        <div class="board-card glass-panel">
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
                                        <a href="<?= htmlspecialchars($bm['linkedin_url']) ?>" target="_blank"
                                            class="linkedin-link" title="Connect on LinkedIn">
                                            <i class="fab fa-linkedin"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($currentCommittee !== '')
                        echo '</div></div>'; ?>
                </div>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        document.getElementById('board-container')?.addEventListener('mousemove', e => {
            if (window.innerWidth < 768) return; // Disable hover effects on mobile for performance
            for (const card of document.getElementsByClassName('board-card')) {
                const rect = card.getBoundingClientRect(),
                    x = e.clientX - rect.left,
                    y = e.clientY - rect.top;

                card.style.setProperty('--mouse-x', `${x}px`);
                card.style.setProperty('--mouse-y', `${y}px`);
            }
        });
    </script>

    <style>
        /* Page Specific Styles for Board */
        .page-header {
            padding: 4rem 2rem;
            margin-bottom: 3rem;
            text-align: center;
            background: linear-gradient(135deg, rgba(0, 98, 155, 0.1) 0%, transparent 100%);
            border-bottom: 2px solid var(--primary-neon);
        }

        .leadership-committees {
            padding-bottom: 4rem;
        }

        .committee-section {
            margin-bottom: 4rem;
        }

        .committee-title {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 2.5rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .committee-title::after {
            content: '';
            height: 1px;
            flex: 1;
            background: linear-gradient(to right, var(--glass-border), transparent);
        }

        .board-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2.5rem;
        }

        .board-card {
            padding: 0;
            text-align: center;
            border-radius: var(--border-radius-lg);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.01) 100%);
            position: relative;
            overflow: hidden;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.4s ease;
            border: 1px solid var(--glass-border);
        }

        .board-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(800px circle at var(--mouse-x) var(--mouse-y), rgba(0, 98, 155, 0.15), transparent 40%);
            opacity: 0;
            transition: opacity 0.5s;
            pointer-events: none;
        }

        .board-card:hover::before {
            opacity: 1;
        }

        .board-card:hover {
            transform: translateY(-10px);
            border-color: var(--primary-neon);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 0 20px rgba(0, 98, 155, 0.1);
        }

        .member-photo-wrapper {
            padding-top: 3rem;
            margin-bottom: 2rem;
            position: relative;
            display: flex;
            justify-content: center;
        }

        .member-photo {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            position: relative;
            z-index: 2;
            background: var(--bg-color);
            padding: 4px;
        }

        .member-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            background: var(--bg-color);
            position: relative;
            z-index: 2;
        }

        .member-photo::after {
            content: '';
            position: absolute;
            inset: -2px;
            background: linear-gradient(45deg, var(--primary-neon), var(--secondary-neon));
            border-radius: 50%;
            z-index: 1;
            animation: rotate 4s linear infinite;
        }

        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .member-glow {
            position: absolute;
            width: 200px;
            height: 200px;
            background: var(--primary-neon);
            filter: blur(40px);
            opacity: 0;
            transition: var(--transition);
            border-radius: 50%;
            top: 2rem;
            left: 50%;
            transform: translateX(-50%);
        }

        .board-card:hover .member-glow {
            opacity: 0.15;
        }

        .member-info {
            padding: 0 2rem 3rem;
        }

        .member-name {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
        }

        .role-badge {
            padding: 0.5rem 1.5rem;
            background: rgba(0, 98, 155, 0.1);
            color: var(--primary-neon);
            border: 1px solid rgba(0, 98, 155, 0.2);
            border-radius: 50px;
            letter-spacing: 1.5px;
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

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .page-header {
                padding: 3rem 1rem;
                margin-bottom: 2rem;
            }

            .page-header h1 {
                font-size: 2.2rem;
            }

            .committee-title {
                font-size: 1.2rem;
                margin-bottom: 1.5rem;
            }

            .board-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .board-card {
                padding: 0.5rem;
            }

            .member-photo {
                width: 150px;
                height: 150px;
            }

            .member-photo-wrapper {
                padding-top: 2rem;
            }

            .member-name {
                font-size: 1.3rem;
            }

            .member-info {
                padding-bottom: 2rem;
            }
        }
    </style>

</body>

</html>