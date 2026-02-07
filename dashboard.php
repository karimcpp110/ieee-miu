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
    $courseId = $_POST['course_id'];
    $courseModel->updateContent($courseId, $_POST['content']);
    
    if (isset($_FILES['course_file']) && $_FILES['course_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/courses/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = time() . '_' . basename($_FILES['course_file']['name']);
        if (move_uploaded_file($_FILES['course_file']['tmp_name'], $uploadDir . $fileName)) {
            $courseModel->updateFile($courseId, $uploadDir . $fileName);
        }
    }
    
    $message = "Course content updated!";
    $activeTab = 'courses';
}

// Handle Add Course Extra
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_course_extra') {
    $courseId = $_POST['course_id'];
    $filePath = '';
    
    // Handle file upload
    if (isset($_FILES['extra_file']) && $_FILES['extra_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/extras/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = time() . '_' . basename($_FILES['extra_file']['name']);
        if (move_uploaded_file($_FILES['extra_file']['tmp_name'], $uploadDir . $fileName)) {
            $filePath = $uploadDir . $fileName;
        }
    }
    
    // If no file, use direct link
    if (empty($filePath) && !empty($_POST['extra_link'])) {
        $filePath = $_POST['extra_link'];
    }
    
    $courseModel->addExtra($courseId, $_POST['extra_title'], $_POST['extra_type'], $_POST['extra_content'], $filePath);
    $message = "New " . $_POST['extra_type'] . " added successfully!";
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
    $photoUrl = 'https://via.placeholder.com/150';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/board/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = time() . '_' . basename($_FILES['photo']['name']);
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $fileName)) {
            $photoUrl = $uploadDir . $fileName;
        }
    }
    $boardModel->add($_POST['name'], $_POST['role'], $photoUrl, $_POST['committee']);
    $message = "Board member added!";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_board') {
    $photoUrl = $_POST['existing_photo'];
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/board/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = time() . '_' . basename($_FILES['photo']['name']);
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $fileName)) {
            $photoUrl = $uploadDir . $fileName;
        }
    }
    $boardModel->update($_POST['id'], $_POST['name'], $_POST['role'], $photoUrl, $_POST['committee']);
    $message = "Board member updated!";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $redirectTab = $activeTab;
    $success = true;

    if ($action === 'delete_board_member') {
        $boardModel->delete($_POST['id']);
        $redirectTab = 'board';
    } elseif ($action === 'delete_member') {
        $db->query("DELETE FROM members WHERE id = ?", [$_POST['id']]);
        $redirectTab = 'registrations';
    } elseif ($action === 'delete_enrollment') {
        $db->query("DELETE FROM enrollments WHERE id = ?", [$_POST['id']]);
        $redirectTab = 'registrations';
    } elseif ($action === 'delete_event') {
        $eventModel->delete($_POST['id']);
        $redirectTab = 'events';
    } elseif ($action === 'delete_form') {
        $formModel->delete($_POST['id']);
        $redirectTab = 'forms';
    } elseif ($action === 'delete_course') {
        $courseModel->delete($_POST['id']);
        $redirectTab = 'courses';
    } else {
        $success = false;
    }

    if ($success) {
        header("Location: dashboard.php?tab=$redirectTab&status=deleted");
        exit;
    }
}

if (isset($_GET['status']) && $_GET['status'] === 'deleted') {
    $message = "Record removed successfully!";
}

// Handle Site Content Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_site'])) {
    $site->set('home_about', $_POST['home_about']);
    $site->set('home_board_intro', $_POST['home_board_intro']);
    $site->set('home_goals', $_POST['home_goals']);
    $site->set('hero_badge', $_POST['hero_badge']);
    $site->set('hero_title', $_POST['hero_title']);
    $site->set('hero_subtitle', $_POST['hero_subtitle']);
    $site->set('header_events', $_POST['header_events']);
    $site->set('header_registrations', $_POST['header_registrations']);
    $site->set('header_leadership', $_POST['header_leadership']);
    $site->set('header_join', $_POST['header_join']);
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
$heroBadge = $site->get('hero_badge');
$heroTitle = $site->get('hero_title');
$heroSubtitle = $site->get('hero_subtitle');
$headEvents = $site->get('header_events');
$headRegs = $site->get('header_registrations');
$headLead = $site->get('header_leadership');
$headJoin = $site->get('header_join');

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

<main class="container">
    <div class="glass-panel dashboard-header">
        <div class="header-content">
            <h1 class="text-gradient">Admin Dashboard</h1>
            <p>Welcome back, <span class="user-accent"><?= htmlspecialchars($_SESSION['username']) ?></span></p>
        </div>
        <div class="header-status">
            <span class="status-badge"><i class="fas fa-circle"></i> System Online</span>
        </div>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= $message ?>
        </div>
    <?php endif; ?>

    <!-- Dashboard Layout -->
    <div class="dashboard-grid">
        <!-- Sidebar Navigation -->
        <aside class="dashboard-sidebar glass-panel">
            <nav class="side-nav">
                <a href="?tab=registrations" class="side-link <?= $activeTab==='registrations'?'active':'' ?>">
                    <i class="fas fa-users"></i> Registrations
                </a>
                <a href="?tab=board" class="side-link <?= $activeTab==='board'?'active':'' ?>">
                    <i class="fas fa-id-badge"></i> Board Members
                </a>
                <a href="?tab=events" class="side-link <?= $activeTab==='events'?'active':'' ?>">
                    <i class="fas fa-calendar-alt"></i> Events
                </a>
                <a href="?tab=forms" class="side-link <?= $activeTab==='forms'?'active':'' ?>">
                    <i class="fas fa-poll"></i> Dynamic Forms
                </a>
                <a href="?tab=courses" class="side-link <?= $activeTab==='courses'?'active':'' ?>">
                    <i class="fas fa-book-open"></i> LMS Management
                </a>
                <a href="?tab=content" class="side-link <?= $activeTab==='content'?'active':'' ?>">
                    <i class="fas fa-edit"></i> Site Content
                </a>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <section class="dashboard-main-content">

            <?php if ($activeTab === 'registrations'): ?>
                
                <div class="glass-panel content-section">
                    <div class="section-header">
                        <h2 class="text-gradient">Club Members</h2>
                        <div class="header-actions">
                            <div class="search-box-mini">
                                <i class="fas fa-search"></i>
                                <input type="text" id="memberSearch" placeholder="Search members..." onkeyup="filterMembers()">
                            </div>
                            <span class="count-badge"><?= count($members) ?> Total</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>ID</th>
                                    <th>Email</th>
                                    <th>Dept</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="membersTableBody">
                                <?php foreach ($members as $m): ?>
                                <tr>
                                    <td class="primary-td"><?= htmlspecialchars($m['full_name']) ?></td>
                                    <td><code><?= htmlspecialchars($m['student_id']) ?></code></td>
                                    <td><?= htmlspecialchars($m['email']) ?></td>
                                    <td><span class="dept-tag"><?= htmlspecialchars($m['department']) ?></span></td>
                                    <td class="date-td"><?= date('M d, Y', strtotime($m['registered_at'])) ?></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Remove this member?');" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_member">
                                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm-icon"><i class="fas fa-user-minus"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if(empty($members)): ?>
                        <p style="padding: 2rem; text-align: center; color: var(--text-muted);">No members registered yet.</p>
                    <?php endif; ?>
                </div>

                <div class="glass-panel content-section">
                    <div class="section-header">
                        <h2 class="text-gradient">Course Enrollments</h2>
                        <span class="count-badge"><?= count($enrollments) ?> Total</span>
                    </div>
                    <div class="table-responsive">
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Student</th>
                                    <th>Contact</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($enrollments as $e): ?>
                                <tr>
                                    <td class="course-td"><?= htmlspecialchars($e['course_title']) ?></td>
                                    <td class="primary-td"><?= htmlspecialchars($e['student_name']) ?></td>
                                    <td><?= htmlspecialchars($e['student_contact']) ?></td>
                                    <td class="date-td"><?= date('M d, Y', strtotime($e['enrolled_at'])) ?></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Remove this enrollment?');" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_enrollment">
                                            <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm-icon"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if(empty($enrollments)): ?>
                        <p style="padding: 2rem; text-align: center; color: var(--text-muted);">No enrollments recorded yet.</p>
                    <?php endif; ?>
                </div>

            <?php elseif ($activeTab === 'board'): ?>
                    <div class="section-header">
                        <h2 class="text-gradient">Board Members</h2>
                        <div class="header-actions">
                            <div class="search-box-mini">
                                <i class="fas fa-search"></i>
                                <input type="text" id="boardSearch" placeholder="Search by name..." onkeyup="filterBoard()">
                            </div>
                            <select id="committeeFilter" class="form-input-mini" onchange="filterBoard()">
                                <option value="all">All Committees</option>
                                <option value="Board">Board / Executives</option>
                                <option value="PR">PR Committee</option>
                                <option value="HR">HR Committee</option>
                                <option value="multi media">Multimedia</option>
                                <option value="R&D">R&D</option>
                                <option value="technical">Technical</option>
                                <option value="event planning">Event Planning</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="glass-panel content-section">
                        <div class="section-header">
                            <h3 class="text-gradient">Add Board Member</h3>
                        </div>
                    <form method="POST" enctype="multipart/form-data" class="styled-form inline-form">
                        <input type="hidden" name="action" value="add_board">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <input type="text" name="role" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label>Committee</label>
                            <select name="committee" class="form-input" required>
                                <option value="Board">Board / Executives</option>
                                <option value="PR">PR Committee</option>
                                <option value="HR">HR Committee</option>
                                <option value="multi media">Multimedia Committee</option>
                                <option value="R&D">R&D Committee</option>
                                <option value="technical">Technical Committee</option>
                                <option value="event planning">Event Planning Committee</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Photo</label>
                            <input type="file" name="photo" class="form-input" accept="image/*" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Member</button>
                    </form>
                </div>

                <div class="board-sections-stack" id="boardStack">
                    <?php 
                    $currentCommittee = '';
                    foreach ($boardMembers as $bm): 
                        if ($currentCommittee !== $bm['committee']): 
                            $currentCommittee = $bm['committee'];
                    ?>
                        <div class="committee-divider">
                            <h3 class="text-gradient"><?= ucwords($currentCommittee) ?></h3>
                        </div>
                    <?php endif; ?>
                    <div class="glass-panel board-item-admin">
                        <img src="<?= htmlspecialchars($bm['photo_url']) ?>" class="board-img-admin">
                        <div class="board-info-admin">
                            <h4><?= htmlspecialchars($bm['name']) ?></h4>
                            <p><?= htmlspecialchars($bm['role']) ?></p>
                            <span class="committee-tag"><?= htmlspecialchars($bm['committee']) ?></span>
                        </div>
                        <div class="board-actions-abs">
                            <button type="button" class="btn btn-icon btn-edit" onclick="openEditBoardModal(<?= $bm['id'] ?>, '<?= addslashes(htmlspecialchars($bm['name'])) ?>', '<?= addslashes(htmlspecialchars($bm['role'])) ?>', '<?= $bm['photo_url'] ?>', '<?= $bm['committee'] ?>')" title="Edit Member"><i class="fas fa-edit"></i></button>
                            <form method="POST" onsubmit="return confirm('Permanently remove this member?');" style="display:inline;">
                                <input type="hidden" name="action" value="delete_board_member">
                                <input type="hidden" name="id" value="<?= $bm['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-icon" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

            <?php elseif ($activeTab === 'events'): ?>
                <?php $events = $eventModel->getAll(); ?>
                
                <div class="glass-panel content-section">
                    <div class="section-header">
                        <h2 class="text-gradient">Create New Event</h2>
                    </div>
                    <form method="POST" enctype="multipart/form-data" class="styled-form">
                        <input type="hidden" name="action" value="add_event">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Event Title</label>
                                <input type="text" name="title" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label>Event Date & Time</label>
                                <input type="datetime-local" name="event_date" class="form-input" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-textarea" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Event Cover Image</label>
                            <input type="file" name="image" class="form-input" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-primary btn-full">Publish Event</button>
                    </form>
                </div>

                <div class="events-grid-admin">
                    <?php foreach ($events as $event): ?>
                    <div class="glass-panel event-card-admin">
                        <img src="<?= htmlspecialchars($event['image_path']) ?>" class="event-img-admin">
                        <div class="event-content-admin">
                            <span class="event-date-badge"><i class="far fa-calendar"></i> <?= date('M d, Y', strtotime($event['event_date'])) ?></span>
                            <h4><?= htmlspecialchars($event['title']) ?></h4>
                            <p><?= htmlspecialchars($event['description']) ?></p>
                        </div>
                        <form method="POST" onsubmit="return confirm('Delete this event?');" class="delete-form-abs">
                            <input type="hidden" name="action" value="delete_event">
                            <input type="hidden" name="id" value="<?= $event['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-icon"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>

            <?php elseif ($activeTab === 'forms'): ?>
                <?php $forms = $formModel->getAll(); ?>
                
                <div class="glass-panel content-section">
                    <div class="section-header">
                        <h2 class="text-gradient">Dynamic Form Builder</h2>
                    </div>
                    <form method="POST" class="styled-form">
                        <input type="hidden" name="action" value="create_form">
                        
                        <div class="form-group">
                            <label>Form Title</label>
                            <input type="text" name="title" class="form-input" required placeholder="Workshop Registration, Membership, etc.">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <input type="text" name="description" class="form-input" placeholder="Briefly describe the purpose of this form">
                        </div>
                        
                        <div class="builder-meta">
                            <h3 class="builder-title"><i class="fas fa-layer-group"></i> Form Fields</h3>
                            <button type="button" class="btn btn-outline btn-sm" onclick="addField()">
                                <i class="fas fa-plus-circle"></i> Add Field
                            </button>
                        </div>

                        <div id="fields-container" class="fields-stack">
                            <!-- Dynamic fields injected here -->
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-full" style="margin-top: 2rem;">Save & Generate Form</button>
                    </form>
                </div>

                <div class="glass-panel content-section">
                    <div class="section-header">
                        <h2 class="text-gradient">Active Forms</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>Form Title</th>
                                    <th>Created Date</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($forms as $f): ?>
                                <tr>
                                    <td class="primary-td"><?= htmlspecialchars($f['title']) ?></td>
                                    <td class="date-td"><?= date('M d, Y', strtotime($f['created_at'])) ?></td>
                                    <td class="text-right">
                                        <div class="actions-group">
                                            <a href="form_responses.php?id=<?= $f['id'] ?>" class="btn btn-icon btn-edit" title="View Responses"><i class="fas fa-list-alt"></i></a>
                                            <a href="view_form.php?id=<?= $f['id'] ?>" target="_blank" class="btn btn-icon btn-view" title="View Public Form"><i class="fas fa-external-link-alt"></i></a>
                                            <form method="POST" onsubmit="return confirm('Permanently delete this form?');" style="display:inline;">
                                                <input type="hidden" name="action" value="delete_form">
                                                <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                                <button type="submit" class="btn btn-icon btn-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

        <script>
            function addField() {
                const container = document.getElementById('fields-container');
                const div = document.createElement('div');
                div.className = 'glass-panel field-builder-item';
                div.innerHTML = `
                    <div class="field-controls-grid">
                        <div class="form-group">
                            <label>Field Name / Label</label>
                            <input type="text" name="field_label[]" class="form-input" required placeholder="e.g. Phone Number">
                        </div>
                        <div class="form-group">
                            <label>Field Type</label>
                            <select name="field_type[]" class="form-input" onchange="toggleOptions(this)">
                                <option value="text">Short Text</option>
                                <option value="email">Email Address</option>
                                <option value="number">Number Input</option>
                                <option value="textarea">Long Text / Bio</option>
                                <option value="date">Date Picker</option>
                                <option value="select">Dropdown Menu</option>
                                <option value="radio">Radio Options</option>
                                <option value="checkbox">Checkbox List</option>
                            </select>
                        </div>
                        <div class="form-group options-group" style="display: none;">
                            <label>Options (comma separated)</label>
                            <input type="text" name="field_options[]" class="form-input" placeholder="Option A, Option B, Option C">
                        </div>
                        <div class="form-group check-group">
                            <label>Required?</label>
                            <input type="checkbox" name="field_required[]" value="1">
                        </div>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="remove-field" title="Remove Field">&times;</button>
                `;
                container.appendChild(div);
            }

            function toggleOptions(select) {
                const optionsGroup = select.closest('.field-controls-grid').querySelector('.options-group');
                if (['select', 'radio', 'checkbox'].includes(select.value)) {
                    optionsGroup.style.display = 'block';
                } else {
                    optionsGroup.style.display = 'none';
                }
            }
        </script>

            <?php elseif ($activeTab === 'courses'): ?>
                <?php $courses = $courseModel->getAll(); ?>
                
                <div class="glass-panel content-section">
                    <div class="section-header">
                        <h2 class="text-gradient">LMS - Course Content Manager</h2>
                    </div>
                    
                    <div class="lms-split-layout">
                        <!-- Course Selection -->
                        <div class="course-list-sidebar">
                            <h3 class="sidebar-title">Tracks</h3>
                            <div class="course-nav-stack">
                                <?php foreach ($courses as $c): ?>
                                    <div class="course-nav-item" onclick="loadCourseContent(<?= $c['id'] ?>, '<?= addslashes(htmlspecialchars($c['title'])) ?>', this)">
                                        <div class="nav-item-main">
                                            <div class="nav-item-icon"><i class="fas fa-graduation-cap"></i></div>
                                            <span><?= htmlspecialchars($c['title']) ?></span>
                                        </div>
                                        <form method="POST" onsubmit="event.stopPropagation(); return confirm('Delete this course and all its enrollments?');" class="inline-delete">
                                            <input type="hidden" name="action" value="delete_course">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <button type="submit" class="btn-clear text-danger" onclick="event.stopPropagation()"><i class="fas fa-times-circle"></i></button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Content Editor -->
                        <div class="course-editor-area">
                            <div id="editor-placeholder" class="editor-empty-state">
                                <i class="fas fa-edit"></i>
                                <p>Select a academic track from the sidebar to modify its learning materials.</p>
                            </div>

                            <form method="POST" id="content-form" class="styled-form" style="display: none;" enctype="multipart/form-data">
                                <div class="editor-header">
                                    <h3 id="editor-title" class="text-gradient">Editing Track</h3>
                                    <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                                </div>
                                
                                <input type="hidden" name="action" value="update_course_content">
                                <input type="hidden" name="course_id" id="course-id">
                                
                                <div class="form-group">
                                    <label class="form-label-fancy primary"><i class="fas fa-file-upload"></i> Upload Course Material (PDF, Word, PPT)</label>
                                    <input type="file" name="course_file" class="form-input" accept=".pdf,.doc,.docx,.ppt,.pptx">
                                    <div id="file-status" class="editor-tips" style="margin-top: 0.5rem; color: var(--secondary-neon);"></div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Content Payload (HTML & Embedded Media Supported)</label>
                                    <textarea name="content" id="course-content" class="form-textarea editor-textarea" rows="20"></textarea>
                                    <div class="editor-tips">
                                        <span><i class="fas fa-info-circle"></i> Supports <code>&lt;iframe&gt;</code> for YouTube/Vimeo.</span>
                                        <span><i class="fab fa-markdown"></i> HTML standard tags allowed.</span>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

        <script>
            const courseData = {
                <?php foreach ($courses as $c): ?>
                <?= $c['id'] ?>: `<?= str_replace(['`', '\\'], ['\`', '\\\\'], $c['content'] ?? '') ?>`,
                <?php endforeach; ?>
            };

            function loadCourseContent(id, title, el) {
                // UI feedback
                document.querySelectorAll('.course-nav-item').forEach(item => item.classList.remove('active'));
                el.classList.add('active');
                
                document.getElementById('editor-placeholder').style.display = 'none';
                document.getElementById('content-form').style.display = 'block';
                document.getElementById('editor-title').innerText = 'Editing: ' + title;
                document.getElementById('course-id').value = id;
                document.getElementById('course-content').value = courseData[id];
                
                // Show current file status if any
                const fileStatus = document.getElementById('file-status');
                const courseFilePath = courseFiles[id];
                if (courseFilePath) {
                    const fileName = courseFilePath.split('/').pop();
                    fileStatus.innerHTML = `<i class="fas fa-paperclip"></i> Current file: <strong>${fileName}</strong>`;
                } else {
                    fileStatus.innerHTML = `<i class="fas fa-info-circle"></i> No document uploaded yet.`;
                }

                // Load extras list
                loadExtras(id);
            }

            function loadExtras(courseId) {
                const container = document.getElementById('extras-list-container');
                container.innerHTML = '<p>Loading items...</p>';
                
                fetch('get_course_extras.php?course_id=' + courseId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length === 0) {
                            container.innerHTML = '<p class="text-muted">No additional materials or records yet.</p>';
                            return;
                        }
                        
                        let html = '<table class="styled-table mini-table"><thead><tr><th>Title</th><th>Type</th><th>Action</th></tr></thead><tbody>';
                        data.forEach(item => {
                            html += `<tr>
                                <td>${item.title}</td>
                                <td><span class="type-tag ${item.type}">${item.type}</span></td>
                                <td>
                                    <form onsubmit="deleteExtra(${item.id}, event); return false;" style="display:inline;">
                                        <button type="submit" class="btn-clear text-danger"><i class="fas fa-trash-alt"></i></button>
                                    </form>
                                </td>
                            </tr>`;
                        });
                        html += '</tbody></table>';
                        container.innerHTML = html;
                    });
            }

            const courseFiles = {
                <?php foreach ($courses as $c): ?>
                <?= $c['id'] ?>: '<?= $c['file_path'] ?? '' ?>',
                <?php endforeach; ?>
            };
        </script>

        <div id="extra-manager-section" style="display:none; margin-top: 3rem;">
            <div class="glass-panel content-section">
                <div class="section-header">
                    <h2 class="text-gradient">Course Materials & Records</h2>
                </div>
                
                <div class="extras-management-flex">
                    <div class="extras-list-area">
                        <h3>Existing Items</h3>
                        <div id="extras-list-container">
                            <!-- Loaded via JS -->
                        </div>
                    </div>
                    
                    <div class="extras-add-area">
                        <h3>Add New Item</h3>
                        <form method="POST" enctype="multipart/form-data" class="styled-form mini-form">
                            <input type="hidden" name="action" value="add_course_extra">
                            <input type="hidden" name="course_id" id="extra-course-id">
                            
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="extra_title" class="form-input" required placeholder="e.g. Session 1 Slides">
                            </div>
                            
                            <div class="form-group">
                                <label>Type</label>
                                <select name="extra_type" class="form-input" required>
                                    <option value="material">Learning Material (PDF/Doc)</option>
                                    <option value="record">Session Record (Video Link)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>File Upload (Optional if using link)</label>
                                <input type="file" name="extra_file" class="form-input">
                            </div>

                            <div class="form-group">
                                <label>Direct Link (Optional for Records)</label>
                                <input type="text" name="extra_link" class="form-input" placeholder="https://youtube.com/...">
                            </div>

                            <div class="form-group">
                                <label>Notes / Description</label>
                                <textarea name="extra_content" class="form-textarea" rows="2"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary btn-full">Add Extra Content</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>


        <script>
            // AJAX delete function for course extras
            function deleteExtra(id, event) {
                event.preventDefault();
                if (!confirm('Remove this item?')) return;
                
                const formData = new FormData();
                formData.append('id', id);
                
                fetch('delete_extra.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Refresh the extras list
                        const courseId = document.getElementById('extra-course-id').value;
                        loadExtras(courseId);
                    } else {
                        alert('Error: ' + (data.error || 'Failed to delete item'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to delete item. Please try again.');
                });
            }

            // Update loadCourseContent to show the extra manager
            function updateEditorAfterLoad(id) {
                document.getElementById('extra-manager-section').style.display = 'block';
                document.getElementById('extra-course-id').value = id;
            }

            // Search and Filter Functions
            function filterMembers() {
                const query = document.getElementById('memberSearch').value.toLowerCase();
                const rows = document.querySelectorAll('#membersTableBody tr');
                rows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(query) ? '' : 'none';
                });
            }

            function filterBoard() {
                const query = document.getElementById('boardSearch').value.toLowerCase();
                const committee = document.getElementById('committeeFilter').value;
                const items = document.querySelectorAll('.board-item-admin');
                const dividers = document.querySelectorAll('.committee-divider');

                items.forEach(item => {
                    const name = item.querySelector('h4').innerText.toLowerCase();
                    const itemComm = item.querySelector('.committee-tag').innerText;
                    const matchesSearch = name.includes(query);
                    const matchesComm = (committee === 'all' || itemComm.toLowerCase() === committee.toLowerCase());
                    
                    item.style.display = (matchesSearch && matchesComm) ? 'flex' : 'none';
                });

                // Hide empty dividers
                dividers.forEach(div => {
                    const nextItems = [];
                    let next = div.nextElementSibling;
                    while (next && !next.classList.contains('committee-divider') && next.classList.contains('board-item-admin')) {
                        if (next.style.display !== 'none') nextItems.push(next);
                        next = next.nextElementSibling;
                    }
                    div.style.display = nextItems.length > 0 ? '' : 'none';
                });
            }

            // HEIC Support Script
            window.addEventListener('DOMContentLoaded', () => {
                const script = document.createElement('script');
                script.src = "https://cdn.jsdelivr.net/npm/heic2any@0.0.3/dist/heic2any.min.js";
                script.onload = handleHeicImages;
                document.head.appendChild(script);
            });

            async function handleHeicImages() {
                const images = document.querySelectorAll('img');
                for (const img of images) {
                    if (img.src.toLowerCase().endsWith('.heic') || img.src.toLowerCase().endsWith('.heif')) {
                        try {
                            const response = await fetch(img.src);
                            const blob = await response.blob();
                            const conversionResult = await heic2any({
                                blob: blob,
                                toType: "image/jpeg",
                                quality: 0.8
                            });
                            img.src = URL.createObjectURL(conversionResult);
                        } catch (e) {
                            console.error("HEIC conversion failed for:", img.src, e);
                        }
                    }
                }
            }

            // Auto-load course if ID is in URL
            window.addEventListener('load', () => {
                const urlParams = new URLSearchParams(window.location.search);
                const courseId = urlParams.get('id');
                if (courseId && "<?= $activeTab ?>" === "courses") {
                    const items = document.querySelectorAll('.course-nav-item');
                    items.forEach(item => {
                        if (item.getAttribute('onclick').includes(courseId)) {
                            item.click();
                        }
                    });
                }
            });
            const originalLoad = loadCourseContent;
            loadCourseContent = function(id, title, el) {
                originalLoad(id, title, el);
                document.getElementById('extra-manager-section').style.display = 'block';
                document.getElementById('extra-course-id').value = id;
            }
        </script>

            <?php elseif ($activeTab === 'content'): ?>
                
                    <div class="form-header">
                        <h2 class="text-gradient">Global Site Content</h2>
                        <p>Modify core branding and section content across the site.</p>
                    </div>
                    <form method="POST" class="styled-form">
                        <input type="hidden" name="update_site" value="1">
                        
                        <div class="form-section-builder">
                            <h3 class="builder-title secondary"><i class="fas fa-rocket"></i> Hero Section</h3>
                            <div class="form-group">
                                <label class="form-label-fancy primary">Hero Badge</label>
                                <input type="text" name="hero_badge" class="form-input" value="<?= htmlspecialchars($heroBadge) ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label-fancy primary">Hero Main Title (HTML allowed)</label>
                                <input type="text" name="hero_title" class="form-input" value="<?= htmlspecialchars($heroTitle) ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label-fancy primary">Hero Subtitle</label>
                                <textarea name="hero_subtitle" class="form-textarea" rows="3"><?= htmlspecialchars($heroSubtitle) ?></textarea>
                            </div>
                        </div>

                        <div class="form-section-builder">
                            <h3 class="builder-title secondary"><i class="fas fa-heading"></i> Section Headers (HTML allowed)</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label-fancy primary">Events Header</label>
                                    <input type="text" name="header_events" class="form-input" value="<?= htmlspecialchars($headEvents) ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label-fancy primary">Registrations Header</label>
                                    <input type="text" name="header_registrations" class="form-input" value="<?= htmlspecialchars($headRegs) ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label-fancy primary">Leadership Header</label>
                                    <input type="text" name="header_leadership" class="form-input" value="<?= htmlspecialchars($headLead) ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label-fancy primary">Join Section Header</label>
                                    <input type="text" name="header_join" class="form-input" value="<?= htmlspecialchars($headJoin) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label-fancy primary"><i class="fas fa-info-circle"></i> About Section (HTML allowed)</label>
                            <textarea name="home_about" class="form-textarea" rows="6"><?= htmlspecialchars($about) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label-fancy secondary"><i class="fas fa-id-badge"></i> Board Intro Text</label>
                            <textarea name="home_board_intro" class="form-textarea" rows="4"><?= htmlspecialchars($boardIntro) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label-fancy primary"><i class="fas fa-bullseye"></i> Club Goals (HTML allowed)</label>
                            <textarea name="home_goals" class="form-textarea" rows="6"><?= htmlspecialchars($goals) ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-full">Update Global Assets</button>
                    </form>
                </div>

            <?php endif; ?>
        </section>
    </div>
</main>

<!-- Edit Board Member Modal -->
<div id="editBoardModal" class="modal-overlay" style="display: none;">
    <div class="glass-panel modal-content">
        <div class="modal-header">
            <h2 class="text-gradient">Edit Board Member</h2>
            <button onclick="closeEditBoardModal()" class="close-btn">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="styled-form">
            <input type="hidden" name="action" value="update_board">
            <input type="hidden" name="id" id="edit-board-id">
            <input type="hidden" name="existing_photo" id="edit-board-existing-photo">
            
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" id="edit-board-name" class="form-input" required>
            </div>
            <div class="form-group">
                <label>Role</label>
                <input type="text" name="role" id="edit-board-role" class="form-input" required>
            </div>
            <div class="form-group">
                <label>Committee</label>
                <select name="committee" id="edit-board-committee" class="form-input" required>
                    <option value="Board">Board / Executives</option>
                    <option value="PR">PR Committee</option>
                    <option value="HR">HR Committee</option>
                    <option value="multi media">Multimedia Committee</option>
                    <option value="R&D">R&D Committee</option>
                    <option value="technical">Technical Committee</option>
                    <option value="event planning">Event Planning Committee</option>
                </select>
            </div>
            <div class="form-group">
                <label>Photo (Leave blank to keep current)</label>
                <input type="file" name="photo" class="form-input" accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary btn-full">Save Changes</button>
        </form>
    </div>
</div>

<script>
function openEditBoardModal(id, name, role, photo, committee) {
    document.getElementById('editBoardModal').style.display = 'flex';
    document.getElementById('edit-board-id').value = id;
    document.getElementById('edit-board-name').value = name;
    document.getElementById('edit-board-role').value = role;
    document.getElementById('edit-board-existing-photo').value = photo;
    document.getElementById('edit-board-committee').value = committee;
}
function closeEditBoardModal() {
    document.getElementById('editBoardModal').style.display = 'none';
}
</script>

<style>
/* Dashboard Layout */
.dashboard-grid {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 2rem;
    margin-top: 2rem;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 2.5rem;
    margin-bottom: 2rem;
}

.user-accent { color: var(--primary-neon); font-weight: 700; }
.status-badge {
    padding: 0.5rem 1rem;
    background: rgba(0, 255, 170, 0.1);
    color: #00ffaa;
    border-radius: 100px;
    font-size: 0.8rem;
    font-weight: 600;
}
.status-badge i { font-size: 0.6rem; margin-right: 0.5rem; }

/* Sidebar */
.dashboard-sidebar { padding: 1.5rem; height: fit-content; sticky; top: 6rem; }
.side-nav { display: flex; flex-direction: column; gap: 0.5rem; }
.side-link {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.5rem;
    color: var(--text-muted);
    text-decoration: none;
    border-radius: 12px;
    font-weight: 500;
    transition: var(--transition);
}
.side-link i { width: 20px; text-align: center; }
.side-link:hover, .side-link.active {
    background: var(--glass-bg-bright);
    color: var(--primary-neon);
}
.side-link.active { border: 1px solid rgba(0, 243, 255, 0.2); }

/* Content Sections */
.content-section { padding: 2.5rem; margin-bottom: 2rem; }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
.count-badge { padding: 0.3rem 0.8rem; background: var(--glass-bg-bright); border-radius: 6px; font-size: 0.8rem; color: var(--text-muted); }

/* Tables */
.table-responsive { overflow-x: auto; }
.styled-table { width: 100%; border-collapse: collapse; text-align: left; }
.styled-table th { padding: 1.2rem; color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; border-bottom: 1px solid var(--glass-border); }
.styled-table td { padding: 1.2rem; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
.primary-td { font-weight: 600; color: var(--text-main); }
.dept-tag { padding: 0.2rem 0.6rem; background: rgba(188, 19, 254, 0.1); color: var(--secondary-neon); border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
.date-td { color: var(--text-muted); font-size: 0.85rem; }

/* Board Grid Management */
.board-grid-admin { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
.board-item-admin { position: relative; display: flex; align-items: center; gap: 1.5rem; padding: 1.5rem; }
.board-img-admin { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-neon); }
.board-info-admin h4 { margin-bottom: 0.2rem; }
.board-info-admin p { color: var(--secondary-neon); font-size: 0.85rem; font-weight: 600; }
.board-actions-abs { position: absolute; top: 1rem; right: 1rem; display: flex; gap: 0.5rem; }
.btn-edit { background: rgba(0, 243, 255, 0.1); color: var(--primary-neon); }
.btn-edit:hover { background: var(--primary-neon); color: #000; }

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.modal-content {
    max-width: 500px;
    width: 100%;
    padding: 2.5rem;
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}
/* Board Management Enhancements */
.board-sections-stack { display: flex; flex-direction: column; gap: 2rem; }
.committee-divider { margin-top: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--glass-border); }
.committee-divider h3 { font-size: 1.2rem; text-transform: uppercase; letter-spacing: 1px; }
.committee-tag { padding: 0.2rem 0.6rem; background: rgba(0, 243, 255, 0.1); color: var(--primary-neon); border-radius: 4px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; margin-top: 0.5rem; display: inline-block; }
.btn-sm-icon { width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; }
.close-btn {
    background: none;
    border: none;
    color: var(--text-muted);
    font-size: 2rem;
    cursor: pointer;
    transition: var(--transition);
}
.close-btn:hover { color: #ff4757; }

/* Event Grid Management */
.events-grid-admin { display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 2rem; margin-top: 2rem; }
.event-card-admin { position: relative; overflow: hidden; padding: 0; }
.event-img-admin { width: 100%; height: 200px; object-fit: cover; border-bottom: 1px solid var(--glass-border); }
.event-content-admin { padding: 1.5rem; }
.event-date-badge { display: block; font-size: 0.75rem; color: var(--primary-neon); margin-bottom: 0.5rem; font-weight: 700; }

/* Dynamic Form Builder UI */
.builder-meta { display: flex; justify-content: space-between; align-items: baseline; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 1rem; }
.builder-title { font-size: 1.1rem; color: var(--text-main); }
.fields-stack { display: flex; flex-direction: column; gap: 1rem; }
.field-builder-item { position: relative; padding: 1.5rem; border-left: 4px solid var(--primary-neon); }
.field-controls-grid { display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 1.5rem; align-items: end; }
.remove-field { position: absolute; top: 0.5rem; right: 0.5rem; background: none; border: none; color: #ff4757; font-size: 1.5rem; cursor: pointer; }

/* LMS Management UI */
.lms-split-layout { display: grid; grid-template-columns: 300px 1fr; gap: 2rem; min-height: 600px; }
.course-list-sidebar { border-right: 1px solid var(--glass-border); padding-right: 1.5rem; }
.course-nav-stack { display: flex; flex-direction: column; gap: 0.75rem; }
.course-nav-item { display: flex; align-items: center; gap: 1rem; padding: 1rem; border-radius: 12px; cursor: pointer; transition: var(--transition); background: rgba(255, 255, 255, 0.02); }
.course-nav-item:hover, .course-nav-item.active { background: var(--glass-bg-bright); }
.course-nav-item.active { border: 1px solid var(--primary-neon); }
.nav-item-icon { width: 35px; height: 35px; border-radius: 8px; background: rgba(0, 243, 255, 0.1); color: var(--primary-neon); display: flex; align-items: center; justify-content: center; }
.editor-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
.editor-textarea { min-height: 500px; font-family: 'Consolas', monospace; font-size: 0.95rem; line-height: 1.5; background: #000; border-radius: 8px; }
.editor-tips { display: flex; gap: 2rem; margin-top: 1rem; color: var(--text-muted); font-size: 0.8rem; }
.editor-tips code { color: var(--primary-neon); }

/* Content Tab specific */
.form-section-builder { background: rgba(255, 255, 255, 0.02); padding: 2rem; border-radius: var(--border-radius-md); border: 1px solid var(--glass-border); margin-bottom: 2.5rem; }
.builder-title.secondary { border: none; padding: 0; margin-bottom: 1.5rem; font-size: 1.2rem; }
.form-label-fancy { font-weight: 700; text-transform: uppercase; letter-spacing: 1px; font-size: 0.75rem; margin-bottom: 0.75rem; display: block; }
.form-label-fancy.primary { color: var(--primary-neon); }
.form-label-fancy.secondary { color: var(--secondary-neon); }

@media (max-width: 1200px) {
    .dashboard-grid { grid-template-columns: 1fr; }
    .dashboard-sidebar { sticky; top: 0; z-index: 100; }
    .side-nav { flex-direction: row; overflow-x: auto; padding-bottom: 1rem; }
    .side-link { white-space: nowrap; }
    .lms-split-layout { grid-template-columns: 1fr; }
    .course-list-sidebar { border-right: none; border-bottom: 1px solid var(--glass-border); padding-right: 0; padding-bottom: 1.5rem; }
}
.course-nav-item { display: flex; justify-content: space-between; align-items: center; padding: 0.8rem 1rem; }
.nav-item-main { display: flex; align-items: center; gap: 0.8rem; flex: 1; cursor: pointer; }
.btn-clear { background: none; border: none; padding: 4px; cursor: pointer; opacity: 0.5; transition: 0.3s; }
.btn-clear:hover { opacity: 1; }
.inline-delete { display: flex; align-items: center; }
.extras-management-flex { display: flex; gap: 3rem; margin-top: 1rem; }
.extras-list-area { flex: 1.5; }
.extras-add-area { flex: 1; border-left: 1px solid var(--glass-border); padding-left: 3rem; }
.mini-table { font-size: 0.9rem; }
.type-tag { padding: 0.1rem 0.4rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
.type-tag.material { background: rgba(0, 243, 255, 0.1); color: var(--primary-neon); }
.type-tag.record { background: rgba(255, 71, 87, 0.1); color: #ff4757; }
</style>

</body>
</html>
