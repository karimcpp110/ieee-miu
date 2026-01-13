<?php
require_once 'Auth.php';
require_once 'Database.php';
require_once 'Site.php';
require_once 'BoardMember.php';
require_once 'Event.php';
require_once 'Course.php';
require_once 'Form.php';

if (!Auth::check()) {
    header("Location: login.php");
    exit;
}

$db = new Database();
$site = new Site();
$boardModel = new BoardMember();
$eventModel = new Event();
$formModel = new Form();
$courseModel = new Course();

$message = '';
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'registrations';

// Handle Course Content Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_course_content') {
    $courseModel->updateContent($_POST['course_id'], $_POST['content']);
    $message = "Course content updated successfully!";
    $activeTab = 'courses';
}

// Handle Form Builder Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_form') {
    // Construct fields JSON from posted arrays
    $fields = [];
    if (isset($_POST['field_label'])) {
        for ($i = 0; $i < count($_POST['field_label']); $i++) {
            $options = [];
            if (!empty($_POST['field_options'][$i])) {
                $options = array_map('trim', explode(',', $_POST['field_options'][$i]));
            }
            
            $fields[] = [
                'label' => $_POST['field_label'][$i],
                'type' => $_POST['field_type'][$i],
                'options' => $options,
                'required' => isset($_POST['field_required'][$i]) ? true : false
            ];
        }
    }
    $formModel->create($_POST['title'], $_POST['description'], json_encode($fields));
    $message = "Form created successfully!";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_form') {
    $formModel->delete($_POST['id']);
    $message = "Form deleted!";
}

// Handle Event Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_event') {
    $imagePath = 'https://via.placeholder.com/300x200';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/events/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
            $imagePath = $uploadDir . $fileName;
        }
    }
    $eventModel->add($_POST['title'], $_POST['description'], $_POST['event_date'], $imagePath);
    $message = "Event added successfully!";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_event') {
    $eventModel->delete($_POST['id']);
    $message = "Event deleted!";
}

// Handle Board Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_board') {
    $photoUrl = 'https://via.placeholder.com/150'; // Default
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileName = time() . '_' . basename($_FILES['photo']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
            $photoUrl = $targetPath;
        }
    }
    
    $boardModel->add($_POST['name'], $_POST['role'], $photoUrl);
    $message = "Board member added!";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_board') {
    $boardModel->delete($_POST['id']);
    $message = "Board member removed!";
}

// Handle Site Content Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_site'])) {
    $site->set('home_about', $_POST['home_about']);
    $site->set('home_board_intro', $_POST['home_board_intro']); // Updated key
    $site->set('home_goals', $_POST['home_goals']);
    $message = "Site content updated successfully!";
}

// Fetch Data
$members = $db->query("SELECT * FROM members ORDER BY registered_at DESC")->fetchAll();
$enrollments = $db->query("SELECT e.*, c.title as course_title FROM enrollments e JOIN courses c ON e.course_id = c.id ORDER BY e.enrolled_at DESC")->fetchAll();
$boardMembers = $boardModel->getAll();

// Helper to get site content
$about = $site->get('home_about');
$boardIntro = $site->get('home_board_intro');
$goals = $site->get('home_goals');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - IEEE MIU</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <header class="glass-panel" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
        <h2>Admin Dashboard</h2>
        <span style="color: var(--primary-neon);">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
    </header>

    <?php if($message): ?>
        <div style="background: rgba(0, 255, 0, 0.1); color: #00ffaa; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid rgba(0, 255, 0, 0.2);">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
        <a href="?tab=registrations" class="btn <?= $activeTab==='registrations'?'btn-primary':'' ?>" style="background: <?= $activeTab!=='registrations'?'rgba(255,255,255,0.05)':'' ?>">
            <i class="fas fa-users"></i> Registrations
        </a>
        <a href="?tab=board" class="btn <?= $activeTab==='board'?'btn-primary':'' ?>" style="background: <?= $activeTab!=='board'?'rgba(255,255,255,0.05)':'' ?>">
            <i class="fas fa-id-badge"></i> Board Members
        </a>
        <a href="?tab=events" class="btn <?= $activeTab==='events'?'btn-primary':'' ?>" style="background: <?= $activeTab!=='events'?'rgba(255,255,255,0.05)':'' ?>">
            <i class="fas fa-calendar-alt"></i> Events
        </a>
        <a href="?tab=forms" class="btn <?= $activeTab==='forms'?'btn-primary':'' ?>" style="background: <?= $activeTab!=='forms'?'rgba(255,255,255,0.05)':'' ?>">
            <i class="fas fa-poll"></i> Forms
        </a>
        <a href="?tab=courses" class="btn <?= $activeTab==='courses'?'btn-primary':'' ?>" style="background: <?= $activeTab!=='courses'?'rgba(255,255,255,0.05)':'' ?>">
            <i class="fas fa-book-open"></i> Courses
        </a>
        <a href="?tab=content" class="btn <?= $activeTab==='content'?'btn-primary':'' ?>" style="background: <?= $activeTab!=='content'?'rgba(255,255,255,0.05)':'' ?>">
            <i class="fas fa-edit"></i> Site Content
        </a>
        <a href="courses.php" class="btn" style="background: rgba(255,255,255,0.05)">
            <i class="fas fa-book"></i> Courses
        </a>
    </div>

    <!-- Tab Content -->
    <?php if ($activeTab === 'registrations'): ?>
        
        <div class="glass-panel" style="padding: 2rem; margin-bottom: 2rem;">
            <h3 style="margin-bottom: 1.5rem; color: var(--secondary-neon);">Club Members</h3>
            <table style="width: 100%; border-collapse: collapse; color: var(--text-muted);">
                <tr style="text-align: left; border-bottom: 1px solid var(--glass-border);">
                    <th style="padding: 1rem;">Name</th>
                    <th style="padding: 1rem;">Email</th>
                    <th style="padding: 1rem;">ID</th>
                    <th style="padding: 1rem;">Dept</th>
                    <th style="padding: 1rem;">Joined</th>
                </tr>
                <?php foreach ($members as $m): ?>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.02);">
                    <td style="padding: 0.8rem 1rem; color: white;"><?= htmlspecialchars($m['full_name']) ?></td>
                    <td style="padding: 0.8rem 1rem;"><?= htmlspecialchars($m['email']) ?></td>
                    <td style="padding: 0.8rem 1rem;"><?= htmlspecialchars($m['student_id']) ?></td>
                    <td style="padding: 0.8rem 1rem;"><?= htmlspecialchars($m['department']) ?></td>
                    <td style="padding: 0.8rem 1rem;"><?= $m['registered_at'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php if(empty($members)): ?>
                <p style="padding: 1rem; text-align: center;">No members yet.</p>
            <?php endif; ?>
        </div>

        <div class="glass-panel" style="padding: 2rem;">
            <h3 style="margin-bottom: 1.5rem; color: var(--primary-neon);">Course Enrollments</h3>
            <table style="width: 100%; border-collapse: collapse; color: var(--text-muted);">
                <tr style="text-align: left; border-bottom: 1px solid var(--glass-border);">
                    <th style="padding: 1rem;">Course</th>
                    <th style="padding: 1rem;">Student</th>
                    <th style="padding: 1rem;">Contact</th>
                    <th style="padding: 1rem;">Date</th>
                </tr>
                <?php foreach ($enrollments as $e): ?>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.02);">
                    <td style="padding: 0.8rem 1rem; color: var(--primary-neon);"><?= htmlspecialchars($e['course_title']) ?></td>
                    <td style="padding: 0.8rem 1rem; color: white;"><?= htmlspecialchars($e['student_name']) ?></td>
                    <td style="padding: 0.8rem 1rem;"><?= htmlspecialchars($e['student_contact']) ?></td>
                    <td style="padding: 0.8rem 1rem;"><?= $e['enrolled_at'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php if(empty($enrollments)): ?>
                <p style="padding: 1rem; text-align: center;">No enrollments yet.</p>
            <?php endif; ?>
        </div>

    <?php elseif ($activeTab === 'board'): ?>
        
        <div class="glass-panel" style="padding: 2rem; margin-bottom: 2rem;">
            <h3 style="margin-bottom: 1.5rem; color: var(--secondary-neon);">Add New Board Member</h3>
            <form method="POST" enctype="multipart/form-data" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 1rem; align-items: end;">
                <input type="hidden" name="action" value="add_board">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-input" required placeholder="Name">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Role</label>
                    <input type="text" name="role" class="form-input" required placeholder="e.g. Chairman">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Photo</label>
                    <input type="file" name="photo" class="form-input" accept="image/*" required>
                </div>
                <button type="submit" class="btn btn-primary" style="height: 42px;">Add</button>
            </form>
        </div>

        <div class="course-grid">
            <?php foreach ($boardMembers as $bm): ?>
            <div class="glass-panel" style="padding: 1.5rem; text-align: center; position: relative;">
                <img src="<?= htmlspecialchars($bm['photo_url']) ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem; border: 2px solid var(--primary-neon);">
                <h4 style="margin-bottom: 0.5rem;"><?= htmlspecialchars($bm['name']) ?></h4>
                <p style="color: var(--secondary-neon); font-size: 0.9rem;"><?= htmlspecialchars($bm['role']) ?></p>
                
                <form method="POST" onsubmit="return confirm('Remove this member?');" style="position: absolute; top: 1rem; right: 1rem;">
                    <input type="hidden" name="action" value="delete_board">
                    <input type="hidden" name="id" value="<?= $bm['id'] ?>">
                    <button type="submit" class="btn btn-danger" style="padding: 0.2rem 0.5rem; font-size: 0.8rem;"><i class="fas fa-trash"></i></button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>

    <?php elseif ($activeTab === 'events'): ?>
        <?php $events = $eventModel->getAll(); ?>
        
        <div class="glass-panel" style="padding: 2rem; margin-bottom: 2rem;">
            <h3 style="margin-bottom: 1.5rem; color: var(--secondary-neon);">Add New Event</h3>
            <form method="POST" enctype="multipart/form-data" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <input type="hidden" name="action" value="add_event">
                
                <div class="form-group">
                    <label class="form-label">Event Title</label>
                    <input type="text" name="title" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="datetime-local" name="event_date" class="form-input" required>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" rows="3"></textarea>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Event Image</label>
                    <input type="file" name="image" class="form-input" accept="image/*">
                </div>
                <button type="submit" class="btn btn-primary" style="grid-column: span 2;">Create Event</button>
            </form>
        </div>

        <div class="course-grid">
            <?php foreach ($events as $event): ?>
            <div class="glass-panel" style="padding: 1rem; position: relative;">
                <img src="<?= htmlspecialchars($event['image_path']) ?>" style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px; margin-bottom: 1rem;">
                <h4><?= htmlspecialchars($event['title']) ?></h4>
                <p style="color: var(--secondary-neon); font-size: 0.9rem; margin-bottom: 0.5rem;">
                    <i class="far fa-calendar"></i> <?= date('M d, Y h:i A', strtotime($event['event_date'])) ?>
                </p>
                <p style="font-size: 0.9rem; color: var(--text-muted);"><?= htmlspecialchars($event['description']) ?></p>
                
                <form method="POST" onsubmit="return confirm('Delete this event?');" style="position: absolute; top: 1rem; right: 1rem;">
                    <input type="hidden" name="action" value="delete_event">
                    <input type="hidden" name="id" value="<?= $event['id'] ?>">
                    <button type="submit" class="btn btn-danger" style="padding: 0.3rem 0.6rem;"><i class="fas fa-trash"></i></button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>

    <?php elseif ($activeTab === 'forms'): ?>
        <?php $forms = $formModel->getAll(); ?>
        
        <div class="glass-panel" style="padding: 2rem; margin-bottom: 2rem;">
            <h3 style="margin-bottom: 1.5rem; color: var(--secondary-neon);">Create New Form</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create_form">
                
                <div class="form-group">
                    <label class="form-label">Form Title</label>
                    <input type="text" name="title" class="form-input" required placeholder="e.g. Workshop Registration">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" rows="2" placeholder="Form purpose..."></textarea>
                </div>
                
                <h4 style="color: var(--primary-neon); margin: 1.5rem 0 1rem;">Form Fields</h4>
                <div id="fields-container">
                    <!-- Fields will be added here -->
                </div>
                
                <button type="button" class="btn" onclick="addField()" style="margin-bottom: 1.5rem; background: rgba(255,255,255,0.1);">
                    <i class="fas fa-plus"></i> Add Field
                </button>
                
                <button type="submit" class="btn btn-primary" style="display: block; width: 100%;">Save Form</button>
            </form>
        </div>

        <div class="glass-panel">
            <h3 style="margin-bottom: 1.5rem; color: var(--secondary-neon);">Your Forms</h3>
            <table style="width: 100%; border-collapse: collapse; color: var(--text-muted);">
                <tr style="text-align: left; border-bottom: 1px solid var(--glass-border);">
                    <th style="padding: 1rem;">Title</th>
                    <th style="padding: 1rem;">Created</th>
                    <th style="padding: 1rem;">Actions</th>
                </tr>
                <?php foreach ($forms as $f): ?>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.02);">
                    <td style="padding: 0.8rem 1rem; color: white;"><?= htmlspecialchars($f['title']) ?></td>
                    <td style="padding: 0.8rem 1rem;"><?= date('M d, Y', strtotime($f['created_at'])) ?></td>
                    <td style="padding: 0.8rem 1rem;">
                        <a href="view_form.php?id=<?= $f['id'] ?>" target="_blank" class="btn" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; background: rgba(0,255,255,0.1); margin-right: 0.5rem;" title="View Form"><i class="fas fa-eye"></i></a>
                        <!-- Improve: Add View Submissions Link -->
                        <form method="POST" onsubmit="return confirm('Delete this form?');" style="display:inline;">
                            <input type="hidden" name="action" value="delete_form">
                            <input type="hidden" name="id" value="<?= $f['id'] ?>">
                            <button type="submit" class="btn btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <script>
            function addField() {
                const container = document.getElementById('fields-container');
                const div = document.createElement('div');
                div.className = 'glass-panel';
                div.style.padding = '1rem';
                div.style.marginBottom = '1rem';
                div.style.position = 'relative';
                div.innerHTML = `
                    <div style="display: grid; grid-template-columns: 2fr 1fr 2fr auto; gap: 1rem; align-items: start;">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Label</label>
                            <input type="text" name="field_label[]" class="form-input" required placeholder="e.g. Phone Number">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Type</label>
                            <select name="field_type[]" class="form-input" onchange="toggleOptions(this)">
                                <option value="text">Text</option>
                                <option value="email">Email</option>
                                <option value="number">Number</option>
                                <option value="textarea">Long Text</option>
                                <option value="date">Date</option>
                                <option value="select">Dropdown</option>
                                <option value="radio">Radio Buttons</option>
                                <option value="checkbox">Checkboxes</option>
                            </select>
                        </div>
                        <div class="form-group options-group" style="margin:0; visibility: hidden;">
                            <label class="form-label">Options (comma separated)</label>
                            <input type="text" name="field_options[]" class="form-input" placeholder="Option 1, Option 2, Option 3">
                        </div>
                        <div class="form-group" style="margin:0; text-align: center;">
                            <label class="form-label">Required?</label>
                            <input type="checkbox" name="field_required[]" value="1">
                        </div>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" style="position: absolute; top: 0.5rem; right: 0.5rem; background: none; border: none; color: #ff4444; cursor: pointer;">&times;</button>
                `;
                container.appendChild(div);
            }

            function toggleOptions(select) {
                const row = select.closest('div').parentElement; // Get the main grid container
                const optionsGroup = select.closest('div').nextElementSibling; // Get the options div
                
                if (['select', 'radio', 'checkbox'].includes(select.value)) {
                    optionsGroup.style.visibility = 'visible';
                } else {
                    optionsGroup.style.visibility = 'hidden';
                }
            }
        </script>

    <?php elseif ($activeTab === 'courses'): ?>
        <?php $courses = $courseModel->getAll(); ?>
        
        <div class="glass-panel" style="padding: 2rem;">
            <h3 style="margin-bottom: 1.5rem; color: var(--secondary-neon);">Manage Course Content</h3>
            
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
                <!-- Course List -->
                <div>
                    <h4 style="margin-bottom: 1rem; color: var(--text-muted);">Select Course</h4>
                    <?php foreach ($courses as $c): ?>
                        <div style="padding: 1rem; background: rgba(255,255,255,0.05); margin-bottom: 0.5rem; border-radius: 8px; cursor: pointer; border: 1px solid transparent;" 
                             onclick="loadCourseContent(<?= $c['id'] ?>, '<?= addslashes(htmlspecialchars($c['title'])) ?>')">
                            <?= htmlspecialchars($c['title']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Editor -->
                <div>
                    <h4 id="editor-title" style="margin-bottom: 1rem; color: var(--primary-neon);">Select a course to edit</h4>
                    <form method="POST" id="content-form" style="display: none;">
                        <input type="hidden" name="action" value="update_course_content">
                        <input type="hidden" name="course_id" id="course-id">
                        
                        <div class="form-group">
                            <label class="form-label">Course Content (HTML Supported)</label>
                            <!-- Simple textarea for now, could be replaced with TinyMCE later -->
                            <textarea name="content" id="course-content" class="form-textarea" rows="15" style="font-family: monospace;"></textarea>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem;">
                                You can use HTML tags like &lt;h1&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;a&gt;, &lt;iframe&gt; (for videos).
                            </p>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Content</button>
                    </form>
                </div>
            </div>
        </div>

        <script>
            // Store course content in a JS object to load without AJAX for simplicity
            const courseData = {
                <?php foreach ($courses as $c): ?>
                <?= $c['id'] ?>: `<?= str_replace(['`', '\\'], ['\`', '\\\\'], $c['content'] ?? '') ?>`,
                <?php endforeach; ?>
            };

            function loadCourseContent(id, title) {
                document.getElementById('editor-title').innerText = 'Editing: ' + title;
                document.getElementById('content-form').style.display = 'block';
                document.getElementById('course-id').value = id;
                document.getElementById('course-content').value = courseData[id];
            }
        </script>

    <?php elseif ($activeTab === 'content'): ?>
        
        <div class="glass-panel" style="padding: 2rem;">
            <form method="POST">
                <input type="hidden" name="update_site" value="1">
                
                <div class="form-group">
                    <label class="form-label" style="color: var(--primary-neon); font-size: 1.1rem;">About Section (HTML allowed)</label>
                    <textarea name="home_about" class="form-textarea" rows="5"><?= htmlspecialchars($about) ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" style="color: var(--secondary-neon); font-size: 1.1rem;">Board Intro Text</label>
                    <textarea name="home_board_intro" class="form-textarea" rows="3"><?= htmlspecialchars($boardIntro) ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" style="color: var(--primary-neon); font-size: 1.1rem;">Club Goals (HTML allowed)</label>
                    <textarea name="home_goals" class="form-textarea" rows="5"><?= htmlspecialchars($goals) ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Save Content</button>
            </form>
        </div>

    <?php endif; ?>

</div>

</body>
</html>
