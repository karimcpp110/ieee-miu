<?php
require_once 'Auth.php';
require_once 'Database.php';
require_once 'Event.php';
require_once 'galleryModel.php';

$eventModel = new Event();
$galleryModel = new GalleryModel();

$groupedEventGallery = $eventModel->getAllGalleryGrouped();
$standaloneSections = $galleryModel->getAllSections();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visual Journey - IEEE MIU</title>
    <link rel="stylesheet" href="style.css?v=6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .gallery-section {
            margin-bottom: 5rem;
        }

        .event-title-divider {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .event-title-divider h2 {
            font-size: 1.8rem;
            white-space: nowrap;
        }

        .event-title-divider .line {
            flex-grow: 1;
            height: 1px;
            background: linear-gradient(to right, var(--primary-neon), transparent);
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .photo-item {
            position: relative;
            aspect-ratio: 4/3;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid var(--glass-border);
            cursor: pointer;
            transition: all 0.4s ease;
        }

        .photo-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .photo-item:hover {
            transform: translateY(-5px);
            border-color: var(--primary-neon);
            box-shadow: 0 10px 30px rgba(0, 98, 155, 0.3);
        }

        .photo-item:hover img {
            transform: scale(1.1);
        }

        .photo-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .photo-item:hover .photo-overlay {
            opacity: 1;
        }

        .photo-overlay i {
            color: #fff;
            font-size: 1.5rem;
        }

        /* Lightbox */
        .lb-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.9);
            z-index: 9999;
            display: none;
            padding: 2rem;
            backdrop-filter: blur(10px);
        }

        .lb-content {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .lb-content img {
            max-width: 90%;
            max-height: 80vh;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .lb-close {
            position: absolute;
            top: 2rem;
            right: 2rem;
            color: #fff;
            font-size: 2.5rem;
            cursor: pointer;
            z-index: 10000;
        }

        .lb-title {
            margin-top: 1.5rem;
            color: var(--primary-neon);
            font-weight: 600;
            font-size: 1.2rem;
        }

        .tab-btn {
            background: transparent;
            border: 2px solid var(--glass-border);
            color: var(--text-muted);
            padding: 0.8rem 2rem;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .tab-btn.active {
            border-color: var(--primary-neon);
            color: var(--primary-neon);
            box-shadow: 0 0 20px rgba(0, 98, 155, 0.2);
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <main class="container" style="padding-top: 8rem;">
        <div class="section-header" style="margin-bottom: 4rem;">
            <span class="badge">Visual Chronicles</span>
            <h1 class="text-gradient" style="font-size: 3.5rem;">Memories Gallery</h1>
            <p class="text-muted" style="max-width: 600px;">Relive the moments that defined our student branch. Every
                photo tells a story of innovation and community.</p>
        </div>

        <div style="display: flex; gap: 1rem; margin-bottom: 4rem; flex-wrap: wrap;">
            <button class="tab-btn active" onclick="switchGallery('events', this)">Event Memories</button>
            <button class="tab-btn" onclick="switchGallery('standalone', this)">Special Collections</button>
        </div>

        <!-- Event Based Gallery -->
        <div id="gallery-events" class="gallery-wrapper">
            <?php if (empty($groupedEventGallery)): ?>
                <div class="glass-panel" style="padding: 5rem; text-align: center;">
                    <i class="fas fa-images" style="font-size: 4rem; color: var(--glass-border); margin-bottom: 2rem;"></i>
                    <h2>No Event Photos Yet</h2>
                </div>
            <?php else: ?>
                <?php foreach ($groupedEventGallery as $eventTitle => $photos): ?>
                    <section class="gallery-section">
                        <div class="event-title-divider">
                            <h2 class="text-gradient"><?= htmlspecialchars($eventTitle) ?></h2>
                            <div class="line"></div>
                            <span class="text-muted" style="font-size: 0.9rem; font-weight: 700;"><?= count($photos) ?>
                                Photos</span>
                        </div>
                        <div class="gallery-grid">
                            <?php foreach ($photos as $photo): ?>
                                <div class="photo-item"
                                    onclick="openLightbox('<?= htmlspecialchars($photo['image_path']) ?>', '<?= htmlspecialchars($eventTitle) ?>')">
                                    <img src="<?= htmlspecialchars($photo['image_path']) ?>" loading="lazy">
                                    <div class="photo-overlay"><i class="fas fa-expand-alt"></i></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Standalone Collections Gallery -->
        <div id="gallery-standalone" class="gallery-wrapper" style="display:none;">
            <?php if (empty($standaloneSections)): ?>
                <div class="glass-panel" style="padding: 5rem; text-align: center;">
                    <i class="fas fa-folder-open"
                        style="font-size: 4rem; color: var(--glass-border); margin-bottom: 2rem;"></i>
                    <h2>No Collections Yet</h2>
                </div>
            <?php else: ?>
                <?php foreach ($standaloneSections as $sec): ?>
                    <?php $photos = $galleryModel->getPhotosBySection($sec['id']); ?>
                    <section class="gallery-section">
                        <div class="event-title-divider">
                            <h2 class="text-gradient"><?= htmlspecialchars($sec['title']) ?></h2>
                            <div class="line"></div>
                            <span class="text-muted" style="font-size: 0.9rem; font-weight: 700;"><?= count($photos) ?>
                                Photos</span>
                        </div>
                        <?php if (!empty($sec['description'])): ?>
                            <p class="text-muted" style="margin-top: -1.5rem; margin-bottom: 2rem; font-size: 0.9rem;">
                                <?= htmlspecialchars($sec['description']) ?>
                            </p>
                        <?php endif; ?>

                        <?php if (empty($photos)): ?>
                            <div class="glass-panel" style="padding: 3rem; text-align: center; border-style: dashed; opacity: 0.6;">
                                <i class="fas fa-camera-retro" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                <p>Collection created. Photos coming soon!</p>
                            </div>
                        <?php else: ?>
                            <div class="gallery-grid">
                                <?php foreach ($photos as $photo): ?>
                                    <div class="photo-item"
                                        onclick="openLightbox('<?= htmlspecialchars($photo['image_path']) ?>', '<?= htmlspecialchars($sec['title']) ?>')">
                                        <img src="<?= htmlspecialchars($photo['image_path']) ?>" loading="lazy">
                                        <div class="photo-overlay"><i class="fas fa-expand-alt"></i></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>

    <!-- Lightbox -->
    <div id="lightbox" class="lb-overlay" onclick="closeLightbox()">
        <span class="lb-close" onclick="closeLightbox()">&times;</span>
        <div class="lb-content">
            <img id="lb-img" src="">
            <div id="lb-title" class="lb-title"></div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        function switchGallery(id, btn) {
            document.querySelectorAll('.gallery-wrapper').forEach(w => w.style.display = 'none');
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('gallery-' + id).style.display = 'block';
            btn.classList.add('active');
        }

        function openLightbox(src, title) {
            document.getElementById('lb-img').src = src;
            document.getElementById('lb-title').innerText = title;
            document.getElementById('lightbox').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            document.getElementById('lightbox').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeLightbox();
        });
    </script>
</body>

</html>