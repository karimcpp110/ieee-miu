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

<div class="container">
    <header class="glass-panel" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
        <h2>Available Courses</h2>
        <?php if (Auth::check()): ?>
        <button class="btn btn-primary" onclick="openModal()">
            <i class="fas fa-plus"></i> Add New Course
        </button>
        <?php endif; ?>
    </header>

    <?php if (empty($courses)): ?>
        <div class="glass-panel empty-state">
            <i class="fas fa-layer-group" style="font-size: 3rem; margin-bottom: 1rem; color: var(--primary-neon);"></i>
            <h2>No courses found</h2>
        </div>
    <?php else: ?>
        <div class="course-grid">
            <?php foreach ($courses as $course): ?>
                <div class="course-card glass-panel">
                    <div class="card-img-container">
                        <img src="<?= htmlspecialchars($course['thumbnail']) ?>" alt="Course Thumbnail" class="card-img">
                    </div>
                    <div class="card-content">
                        <h3 class="card-title"><?= htmlspecialchars($course['title']) ?></h3>
                        <div class="card-instructor">
                            <i class="fas fa-user-circle"></i> <?= htmlspecialchars($course['instructor']) ?>
                        </div>
                        <p class="card-desc"><?= htmlspecialchars($course['description']) ?></p>
                        
                        <div class="card-meta">
                            <span><i class="fas fa-clock"></i> <?= htmlspecialchars($course['duration']) ?></span>
                            
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <a href="course_details.php?id=<?= $course['id'] ?>" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: rgba(255,255,255,0.1);">
                                    Details
                                </a>

                                <?php if (Auth::check()): ?>
                                    <form method="POST" onsubmit="return confirm('Are you sure?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $course['id'] ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Add Course Modal (Admin Only) -->
<?php if (Auth::check()): ?>
<div class="modal-overlay" id="addModal">
    <div class="modal glass-panel">
        <button class="close-btn" onclick="closeModal()">&times;</button>
        <h2 style="margin-bottom: 1.5rem; color: var(--primary-neon);">New Course</h2>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label class="form-label">Course Title</label>
                <input type="text" name="title" class="form-input" required placeholder="e.g. Advanced AI Development">
            </div>
            
            <div class="form-group">
                <label class="form-label">Instructor</label>
                <input type="text" name="instructor" class="form-input" required placeholder="e.g. Dr. Sarah Smith">
            </div>
            
            <div class="form-group">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label class="form-label">Duration</label>
                        <input type="text" name="duration" class="form-input" required placeholder="e.g. 12 Hours">
                    </div>
                    <div>
                        <label class="form-label">Thumbnail (File or URL)</label>
                        <input type="file" name="thumbnail" class="form-input" accept="image/*" style="margin-bottom: 0.5rem;">
                        <input type="url" name="thumbnail_url" class="form-input" placeholder="OR paste URL https://...">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-textarea" rows="4" required placeholder="Course summary..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Create Course
            </button>
        </form>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('addModal').classList.add('active');
    }
    function closeModal() {
        document.getElementById('addModal').classList.remove('active');
    }
    document.getElementById('addModal').addEventListener('click', function(e) {
        if (e.target === this) { closeModal(); }
    });
</script>
<?php endif; ?>

</body>
</html>
