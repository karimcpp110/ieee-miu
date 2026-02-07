<?php
require_once 'Course.php';
require_once 'Auth.php';

$courseModel = new Course();
$message = '';

// Handle Admin Actions (Create/Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::check()) {
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $thumbnailUrl = 'https://via.placeholder.com/300x200'; // Default
        
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/courses/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
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
    <link rel="stylesheet" href="style.css">
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
                    <?php if (Auth::check()): ?>
                    <div class="admin-card-actions">
                        <a href="dashboard.php?tab=courses&id=<?= $course['id'] ?>" class="btn-icon-mini edit" title="Edit Content"><i class="fas fa-edit"></i></a>
                        <form method="POST" onsubmit="return confirm('Delete this course?');" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $course['id'] ?>">
                            <button type="submit" class="btn-icon-mini delete" title="Delete Course"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                    <?php endif; ?>
                    <div class="card-img-container">
                        <img src="<?= htmlspecialchars($course['thumbnail']) ?>" alt="Course Thumbnail" class="card-img">
                    </div>
                    <div class="card-content">
                        <h3 class="card-title"><?= htmlspecialchars($course['title']) ?></h3>
                        <div class="instructor-info">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($course['instructor']) ?>&background=00f3ff&color=000" class="mini-avatar">
                            <span><?= htmlspecialchars($course['instructor']) ?></span>
                        </div>
                        <p class="card-desc"><?= htmlspecialchars($course['description']) ?></p>
                        
                        <div class="card-meta">
                            <span class="meta-item"><i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($course['duration']) ?></span>
                            
                            <div class="card-actions">
                                <a href="course_details.php?id=<?= $course['id'] ?>" class="btn btn-outline btn-small">
                                    Details
                                </a>

                                <?php if (Auth::check()): ?>
                                    <!-- Admin actions moved to floating toolbar -->
                                <?php endif; ?>
                            </div>
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
    <div class="modal glass-panel">
        <div class="modal-header">
            <h2 class="text-gradient">Add New Course</h2>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="styled-form">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label class="form-label">Course Title</label>
                <input type="text" name="title" class="form-input" required placeholder="e.g. Arduino Mastery">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Instructor</label>
                    <input type="text" name="instructor" class="form-input" required placeholder="Eng. John Doe">
                </div>
                <div class="form-group">
                    <label class="form-label">Duration</label>
                    <input type="text" name="duration" class="form-input" required placeholder="e.g. 15 Hours">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Thumbnail (File or URL)</label>
                <div class="file-url-input">
                    <input type="file" name="thumbnail" class="form-input" accept="image/*">
                    <span class="or-divider">OR</span>
                    <input type="url" name="thumbnail_url" class="form-input" placeholder="https://...">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-textarea" rows="3" required placeholder="Briefly describe the track..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                Create Course <i class="fas fa-plus-circle"></i>
            </button>
        </form>
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
    document.getElementById('addModal').addEventListener('click', function(e) {
        if (e.target === this) { closeModal(); }
    });
</script>
<?php endif; ?>

<style>
.page-header {
    padding: 3rem;
    margin-bottom: 3rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-header h1 { margin-bottom: 0.5rem; }
.page-header p { color: var(--text-muted); }

.instructor-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
    color: var(--text-muted);
}

.mini-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
}

.card-title { margin-bottom: 1rem; }
.card-desc {
    color: var(--text-muted);
    font-size: 0.95rem;
    margin-bottom: 1.5rem;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.card-actions { display: flex; gap: 0.5rem; }
.btn-small { padding: 0.4rem 1.2rem; font-size: 0.85rem; }
.btn-icon { width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; }
.btn-danger { background: rgba(255, 71, 87, 0.1); color: #ff4757; border: 1px solid rgba(255, 71, 87, 0.2); }
.btn-danger:hover { background: #ff4757; color: #fff; }

/* Admin Floating Actions */
.admin-card-actions {
    position: absolute;
    top: 1rem;
    left: 1rem;
    z-index: 10;
    display: flex;
    gap: 0.5rem;
    opacity: 0;
    transform: translateY(-10px);
    transition: var(--transition);
}

.course-card:hover .admin-card-actions {
    opacity: 1;
    transform: translateY(0);
}

.btn-icon-mini {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(11, 15, 26, 0.8);
    backdrop-filter: blur(10px);
    border: 1px solid var(--glass-border);
    color: white;
    text-decoration: none;
    cursor: pointer;
    transition: var(--transition);
}

.btn-icon-mini:hover { transform: scale(1.1); }
.btn-icon-mini.edit:hover { color: var(--primary-neon); border-color: var(--primary-neon); }
.btn-icon-mini.delete:hover { color: #ff4757; border-color: #ff4757; }
.btn-icon-mini i { font-size: 0.85rem; }

.empty-state { text-align: center; padding: 6rem; }
.icon-box-large { font-size: 4rem; color: var(--primary-neon); margin-bottom: 2rem; opacity: 0.5; }

/* Modal Design Modernization */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    opacity: 0;
    visibility: hidden;
    transition: var(--transition);
}

.modal-overlay.active { opacity: 1; visibility: visible; }

.modal {
    width: 100%;
    max-width: 600px;
    padding: 3rem;
    position: relative;
    transform: translateY(30px);
    transition: var(--transition);
}

.modal-overlay.active .modal { transform: translateY(0); }

.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; }
.close-btn { background: none; border: none; font-size: 2rem; color: var(--text-muted); cursor: pointer; transition: var(--transition); }
.close-btn:hover { color: #ff4757; transform: rotate(90deg); }

.file-url-input { display: flex; flex-direction: column; gap: 0.75rem; }
.or-divider { text-align: center; font-size: 0.8rem; color: var(--text-muted); font-weight: 700; }

@media (max-width: 768px) {
    .page-header { flex-direction: column; text-align: center; gap: 2rem; padding: 2rem; }
    .modal { padding: 2rem; width: 95%; }
}
</style>

</body>
</html>

