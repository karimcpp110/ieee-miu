<?php
require_once 'Auth.php';
require_once 'Database.php';
require_once 'Site.php';
require_once 'Event.php';

$db = new Database();
$site = new Site();
$eventModel = new Event();

$upcomingEvents = $eventModel->getUpcoming();
$pastEvents = $eventModel->getPast();
$allGallery = $eventModel->getAllPastGallery();
$nextEvent = $eventModel->getNearestUpcoming();

$headEvents = $site->get('header_events');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Future Timeline - IEEE MIU</title>
    <link rel="stylesheet" href="style.css?v=5">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #050a15;
            color: #fff;
            overflow-x: hidden;
        }

        /* Hero Hologram */
        .hologram-section {
            padding: 10rem 2rem 6rem;
            text-align: center;
            position: relative;
        }

        .scanline {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: rgba(0, 243, 255, 0.1);
            z-index: 100;
            pointer-events: none;
            animation: scan 8s linear infinite;
        }

        @keyframes scan {
            0% {
                top: -4px;
            }

            100% {
                top: 100%;
            }
        }

        /* Circuit Line Background */
        .circuit-line {
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, transparent, var(--neon-blue), transparent);
            opacity: 0.1;
            z-index: -1;
        }

        /* 3D Vortex Styling Extras */
        .vortex-container {
            user-select: none;
        }

        .vortex-card:hover {
            border-color: #fff;
            box-shadow: 0 0 50px var(--neon-blue);
            z-index: 100;
        }

        /* Card Label */
        .event-label {
            position: absolute;
            bottom: 2rem;
            left: 2rem;
            right: 2rem;
            z-index: 10;
        }

        /* Section Headings */
        .terminal-header {
            margin-bottom: 3rem;
            font-family: 'Courier New', Courier, monospace;
            border-bottom: 1px solid rgba(0, 243, 255, 0.2);
            padding-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .terminal-dot {
            width: 10px;
            height: 10px;
            background: var(--neon-blue);
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
            box-shadow: 0 0 10px var(--neon-blue);
        }
    </style>
</head>

<body class="bg-dark tech-scan">
    <?php include 'navbar.php'; ?>

    <!-- HERO SECTION -->
    <header class="events-hero"
        style="padding: 10rem 2rem 6rem; text-align: center; background: radial-gradient(circle at 50% 50%, rgba(0, 98, 155, 0.08) 0%, transparent 70%);">
        <div class="container">
            <h1 class="text-gradient" style="font-size: clamp(2.5rem, 8vw, 4.5rem); margin-bottom: 1rem;">
                Powering <span class="text-white">Future</span> Tech
            </h1>
            <p class="text-muted" style="max-width: 600px; margin: 0 auto 3rem; font-size: 1.1rem;">
                Explore our legacy and join our next breakthrough.
            </p>

            <?php if ($nextEvent): ?>
                <div class="glass-panel"
                    style="display:inline-block; padding: 2.5rem 5rem; border-radius: 40px; box-shadow: 0 20px 50px rgba(0,0,0,0.5);">
                    <p
                        style="text-transform: uppercase; letter-spacing: 4px; font-weight: 700; color: var(--neon-blue); margin-bottom: 1.5rem;">
                        Next Breakthrough In
                    </p>
                    <div id="countdown" class="countdown-container" data-date="<?= $nextEvent['event_date'] ?>"
                        style="display: flex; gap: 2rem; justify-content: center;">
                        <div class="countdown-box"><span class="countdown-num" id="days"
                                style="font-size: 3rem; color: #fff;">00</span><br><span class="countdown-label">Days</span>
                        </div>
                        <div class="countdown-box"><span class="countdown-num" id="hours"
                                style="font-size: 3rem; color: #fff;">00</span><br><span class="countdown-label">Hrs</span>
                        </div>
                        <div class="countdown-box"><span class="countdown-num" id="minutes"
                                style="font-size: 3rem; color: #fff;">00</span><br><span class="countdown-label">Min</span>
                        </div>
                    </div>
                    <div style="margin-top: 2rem;">
                        <span
                            style="font-size: 1.4rem; font-weight: 700; color: var(--neon-blue);"><?= htmlspecialchars($nextEvent['title']) ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <main class="container">
        <!-- HIGHLIGHTS GALLERY -->
        <?php if (!empty($allGallery)): ?>
            <section id="highlights" style="padding: 6rem 0;">
                <div style="margin-bottom: 3rem; border-left: 4px solid var(--neon-blue); padding-left: 1.5rem;">
                    <h2 class="text-gradient">Event Highlights</h2>
                    <p class="text-muted">Capturing moments of innovation.</p>
                </div>

                <div class="gallery-grid"
                    style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
                    <?php foreach ($allGallery as $img): ?>
                        <div class="glass-panel"
                            style="border-radius: 15px; overflow: hidden; cursor: pointer; transition: 0.3s;"
                            onclick="openPremiumLB('<?= htmlspecialchars($img['image_path']) ?>', '<?= htmlspecialchars($img['event_title']) ?>')">
                            <img src="<?= htmlspecialchars($img['image_path']) ?>"
                                style="width: 100%; height: 250px; object-fit: cover;" loading="lazy">
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- ALL EVENTS FEED -->
        <section id="timeline" style="padding: 4rem 0;">
            <div style="margin-bottom: 3rem; border-left: 4px solid var(--neon-purple); padding-left: 1.5rem;">
                <h2 class="text-gradient" style="background: linear-gradient(90deg, var(--neon-purple), #fff);">Full
                    Chronicle</h2>
            </div>

            <div class="courses-grid"
                style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 2rem;">
                <?php foreach (array_merge($upcomingEvents, $pastEvents) as $e): ?>
                    <?php
                    $isPast = strtotime($e['event_date']) < time();
                    ?>
                    <div class="glass-panel"
                        style="overflow: hidden; height: 100%; display: flex; flex-direction: column; border: 1px solid rgba(255,255,255,0.06); transition: 0.4s;">
                        <div style="height: 220px; position: relative;">
                            <img src="<?= htmlspecialchars($e['image_path']) ?>"
                                style="width: 100%; height: 100%; object-fit: cover;">
                            <span class="category-tag"
                                style="position: absolute; top: 1rem; right: 1rem; border-radius: 30px; font-size: 0.7rem; background: rgba(0,0,0,0.8); border: 1px solid var(--neon-blue); padding: 5px 12px; font-weight: 700;"><?= htmlspecialchars($e['category'] ?? 'Event') ?></span>
                            <?php if ($isPast): ?>
                                <span class="past-tag"
                                    style="position: absolute; top: 1rem; left: 1rem; background: rgba(255,255,255,0.1); backdrop-filter: blur(5px); padding: 5px 12px; border-radius: 30px; font-size: 0.7rem; font-weight: 700; color: rgba(255,255,255,0.6);">PAST
                                    EVENT</span>
                            <?php endif; ?>
                        </div>
                        <div style="padding: 2rem; flex-grow: 1;">
                            <div
                                style="color: var(--neon-blue); font-size: 0.9rem; margin-bottom: 0.8rem; font-weight: 700;">
                                <i class="far fa-calendar"></i> <?= date('F d, Y', strtotime($e['event_date'])) ?>
                            </div>
                            <h3 style="margin-bottom: 1.25rem; font-size: 1.4rem; line-height: 1.3;">
                                <?= htmlspecialchars($e['title']) ?></h3>

                            <div class="event-brief"
                                style="margin-bottom: 2rem; border-left: 2px solid var(--neon-blue); padding-left: 1rem;">
                                <p class="text-muted" style="font-size: 0.95rem; line-height: 1.6;">
                                    <?= htmlspecialchars($e['description']) ?>
                                </p>
                            </div>

                            <div style="margin-top: auto;">
                                <?php if ($isPast): ?>
                                    <a href="gallery.php" class="btn btn-outline btn-full" style="font-size: 0.85rem;">
                                        <i class="fas fa-images"></i> View Event Gallery
                                    </a>
                                <?php else: ?>
                                    <a href="index.php#join" class="btn btn-primary btn-full" style="font-size: 0.85rem;">
                                        Join Event <i class="fas fa-arrow-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <!-- LIGHTBOX -->
    <div id="lb" class="lb-overlay"
        style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.95); z-index:10000; backdrop-filter:blur(10px); cursor:pointer;"
        onclick="this.style.display='none'">
        <div
            style="height:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:2rem;">
            <img id="lb-img"
                style="max-width:90%; max-height:80vh; border-radius:12px; box-shadow:0 0 50px rgba(0, 98, 155, 0.3);">
            <h3 id="lb-title" style="margin-top:2rem; color:var(--neon-blue);"></h3>
            <span style="position:absolute; top:2rem; right:2rem; font-size:2.5rem; color:#fff;">&times;</span>
        </div>
    </div>

    <!-- PREMIUM LIGHTBOX -->
    <div id="lb" class="lb-overlay">
        <span class="lb-close" onclick="closeLB()">&times;</span>
        <div class="lb-content">
            <img id="lb-img" class="lb-main-img">
            <div class="lb-info">
                <h3 id="lb-title" style="margin-bottom: 0.5rem; color: var(--neon-blue);"></h3>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Countdown
            const countdownEl = document.getElementById('countdown');
            if (countdownEl) {
                const targetDate = new Date(countdownEl.dataset.date).getTime();
                setInterval(() => {
                    const now = new Date().getTime();
                    const diff = targetDate - now;
                    if (diff < 0) return;
                    document.getElementById('days').innerText = String(Math.floor(diff / (1000 * 60 * 60 * 24))).padStart(2, '0');
                    document.getElementById('hours').innerText = String(Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))).padStart(2, '0');
                    document.getElementById('minutes').innerText = String(Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0');
                    document.getElementById('seconds').innerText = String(Math.floor((diff % (1000 * 60)) / 1000)).padStart(2, '0');
                }, 1000);
            }

            // Lightbox
            window.openPremiumLB = (src, title) => {
                document.getElementById('lb-img').src = src;
                document.getElementById('lb-title').innerText = title;
                document.getElementById('lb').style.display = 'block';
            };

            window.closeLB = () => {
                document.getElementById('lb').style.display = 'none';
            };

            // Subtle mouse parallax for hero
            document.addEventListener('mousemove', (e) => {
                const card = document.querySelector('.hologram-card');
                if (!card) return;
                const xAxis = (window.innerWidth / 2 - e.pageX) / 50;
                const yAxis = (window.innerHeight / 2 - e.pageY) / 50;
                card.style.transform = `perspective(1000px) rotateY(${xAxis}deg) rotateX(${yAxis}deg)`;
            });
        });
    </script>
</body>

</html>