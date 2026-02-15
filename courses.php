<?php
require_once 'Course.php';
require_once 'Auth.php';

$courseModel = new Course();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::check()) {
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $thumbnailUrl = 'https://via.placeholder.com/300x200';
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/courses/';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0777, true);
            $fileName = time() . '_' . basename($_FILES['thumbnail']['name']);
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $targetPath)) {
                $thumbnailUrl = $targetPath;
            }
        } elseif (!empty($_POST['thumbnail_url'])) {
            $thumbnailUrl = $_POST['thumbnail_url'];
        }
        $data = [
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'instructor' => $_POST['instructor'],
            'duration' => $_POST['duration'],
            'thumbnail' => $thumbnailUrl
        ];
        $courseModel->create($data);
        header("Location: courses.php");
        exit;
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $courseModel->delete($_POST['id']);
        header("Location: courses.php");
        exit;
    }
}

$courses = $courseModel->getAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses - IEEE MIU</title>
    <link rel="stylesheet" href="style.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

    <?php include 'navbar.php'; ?>

    <main class="container">
        <div class="glass-panel page-header">
            <div class="header-info">
                <h1 class="text-gradient">Our Courses</h1>
                <p>Master electronics, programming, and soft skills with our dedicated tracks.</p>
            </div>
            <?php if (Auth::check()): ?>
                <button class="btn btn-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i> New Course
                </button>
            <?php endif; ?>
        </div>

        <?php if (empty($courses)): ?>
            <div class="glass-panel empty-state">
                <div class="icon-box-large"><i class="fas fa-cubes"></i></div>
                <h2>Coming Soon</h2>
                <p>We are preparing exciting new courses for you. Stay tuned!</p>
            </div>
        <?php else: ?>
            <div class="course-grid">
                <?php foreach ($courses as $course): ?>
                    <div class="course-card glass-panel">
                        <div class="card-img-container">
                            <img src="<?= htmlspecialchars($course['thumbnail']) ?>" alt="Course Thumbnail" class="card-img">
                            <?php if (Auth::check()): ?>
                                <div class="admin-card-actions">
                                    <a href="dashboard.php?tab=courses&id=<?= $course['id'] ?>" class="btn-icon-mini edit"
                                        title="Edit Content"><i class="fas fa-edit"></i></a>
                                    <form method="POST" onsubmit="return confirm('Delete this course?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $course['id'] ?>">
                                        <button type="submit" class="btn-icon-mini delete" title="Delete Course"><i
                                                class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-content">
                            <h3 class="card-title"><?= htmlspecialchars($course['title']) ?></h3>
                            <div class="instructor-info">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($course['instructor']) ?>&background=00629B&color=fff"
                                    class="mini-avatar">
                                <span><?= htmlspecialchars($course['instructor']) ?></span>
                            </div>
                            <p class="card-desc"><?= htmlspecialchars($course['description']) ?></p>

                            <div class="card-footer-flex">
                                <div class="duration-badge">
                                    <i class="far fa-clock"></i> <?= htmlspecialchars($course['duration']) ?>
                                </div>
                                <a href="course_details.php?id=<?= $course['id'] ?>" class="btn btn-primary btn-small">
                                    Explore <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Add Course Modal (Admin Only) -->
    <?php if (Auth::check()): ?>
        <div class="modal-overlay" id="addModal">
            <div class="modal glass-panel modal-wide">
                <div class="modal-header">
                    <h2 class="text-gradient">Add New Course</h2>
                    <button class="close-btn" onclick="closeModal()">&times;</button>
                </div>

                <div class="modal-content-grid">
                    <!-- Form Column -->
                    <div class="modal-form-col">
                        <form method="POST" enctype="multipart/form-data" class="styled-form">
                            <input type="hidden" name="action" value="create">
                            <div class="form-group">
                                <label class="form-label">Course Title</label>
                                <input type="text" name="title" id="inp-title" class="form-input" required
                                    placeholder="e.g. Arduino Mastery">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Instructor</label>
                                    <input type="text" name="instructor" id="inp-instr" class="form-input" required
                                        placeholder="Eng. John Doe">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Duration</label>
                                    <input type="text" name="duration" id="inp-dur" class="form-input" required
                                        placeholder="e.g. 15 Hours">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Thumbnail (File or URL)</label>
                                <div class="file-url-input">
                                    <input type="file" name="thumbnail" id="inp-thumb-file" class="form-input"
                                        accept="image/*">
                                    <span class="or-divider">OR</span>
                                    <input type="url" name="thumbnail_url" id="inp-thumb-url" class="form-input"
                                        placeholder="https://...">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" id="inp-desc" class="form-textarea" rows="4" required
                                    placeholder="Briefly describe the track..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-full">
                                Create Course <i class="fas fa-plus-circle"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Preview Column -->
                    <div class="modal-preview-col">
                        <div class="preview-label">Live Preview</div>
                        <div class="course-card glass-panel" id="preview-card"
                            style="pointer-events: none; transform: scale(0.95); transform-origin: top center;">
                            <div class="card-img-container">
                                <img src="https://via.placeholder.com/300x200" alt="Course Thumbnail" class="card-img"
                                    id="prev-img">
                            </div>
                            <div class="card-content">
                                <h3 class="card-title" id="prev-title">Course Title</h3>
                                <div class="instructor-info">
                                    <img src="https://ui-avatars.com/api/?name=Instructor&background=00629B&color=fff"
                                        class="mini-avatar" id="prev-instr-av">
                                    <span id="prev-instr">Instructor Name</span>
                                </div>
                                <p class="card-desc" id="prev-desc">Course description will appear here as you type...</p>

                                <div class="card-footer-flex">
                                    <div class="duration-badge">
                                        <i class="far fa-clock"></i> <span id="prev-dur">0 Hours</span>
                                    </div>
                                    <a href="#" class="btn btn-primary btn-small" onclick="return false;">
                                        Explore <i class="fas fa-chevron-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function openModal() {
                document.getElementById('addModal').classList.add('active');
                document.body.style.overflow = 'hidden';
            }
            function closeModal() {
                document.getElementById('addModal').classList.remove('active');
                document.body.style.overflow = 'auto';
            }
            document.getElementById('addModal').addEventListener('click', function (e) {
                if (e.target === this) { closeModal(); }
            });

            // Live Preview Logic
            const inputs = {
                title: document.getElementById('inp-title'),
                instr: document.getElementById('inp-instr'),
                dur: document.getElementById('inp-dur'),
                desc: document.getElementById('inp-desc'),
                thumbFile: document.getElementById('inp-thumb-file'),
                thumbUrl: document.getElementById('inp-thumb-url')
            };

            const previews = {
                title: document.getElementById('prev-title'),
                instr: document.getElementById('prev-instr'),
                instrAv: document.getElementById('prev-instr-av'),
                dur: document.getElementById('prev-dur'),
                desc: document.getElementById('prev-desc'),
                img: document.getElementById('prev-img')
            };

            if (inputs.title) {
                inputs.title.addEventListener('input', e => previews.title.textContent = e.target.value || "Course Title");
                inputs.instr.addEventListener('input', e => {
                    const val = e.target.value || "Instructor";
                    previews.instr.textContent = val;
                    previews.instrAv.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(val)}&background=00629B&color=fff`;
                });
                inputs.dur.addEventListener('input', e => previews.dur.textContent = e.target.value || "0 Hours");
                inputs.desc.addEventListener('input', e => previews.desc.textContent = e.target.value || "Description...");

                inputs.thumbUrl.addEventListener('input', e => {
                    if (e.target.value) previews.img.src = e.target.value;
                });

                inputs.thumbFile.addEventListener('change', e => {
                    if (e.target.files && e.target.files[0]) {
                        const reader = new FileReader();
                        reader.onload = ev => previews.img.src = ev.target.result;
                        reader.readAsDataURL(e.target.files[0]);
                    }
                });
            }
        </script>
    <?php endif; ?>


</body>

</html>