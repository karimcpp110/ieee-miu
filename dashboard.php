<?php
require_once 'Auth.php';
require_once 'Database.php';
require_once 'Site.php';

$db = new Database();

// --- AUTOMATIC DATABASE HEALING ---
// This ensures new features work even if the user didn't run the repair script
try {
    $res = $db->query("DESCRIBE events");
    $cols = $res->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('category', $cols)) {
        $db->query("ALTER TABLE events ADD COLUMN category VARCHAR(50) DEFAULT 'General' AFTER title");
    }
    $db->query("CREATE TABLE IF NOT EXISTS event_gallery (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Standalone Gallery Support
    $db->query("CREATE TABLE IF NOT EXISTS gallery_sections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $db->query("CREATE TABLE IF NOT EXISTS gallery_photos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        section_id INT NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {
}
// ----------------------------------

require_once 'BoardMember.php';
require_once 'Event.php';
require_once 'Course.php';
require_once 'Form.php';
require_once 'galleryModel.php';

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
$galleryModel = new GalleryModel();

$message = '';
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'registrations';

// Handle Course Content Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_course_content') {
    $courseId = $_POST['course_id'];
    $courseModel->updateContent($courseId, $_POST['content']);

    if (isset($_FILES['course_file']) && $_FILES['course_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/courses/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);
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
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);
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
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
            $imagePath = $uploadDir . $fileName;
        }
    }
    $eventModel->add($_POST['title'], $_POST['category'], $_POST['description'], $_POST['event_date'], $imagePath);
    $message = "Event added successfully!";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_upload_gallery') {
    $eventId = $_POST['event_id'];
    if (isset($_FILES['gallery_images'])) {
        $uploadDir = 'uploads/gallery/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);

        foreach ($_FILES['gallery_images']['name'] as $key => $val) {
            if ($_FILES['gallery_images']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName = time() . '_' . rand(100, 999) . '_' . basename($_FILES['gallery_images']['name'][$key]);
                if (move_uploaded_file($_FILES['gallery_images']['tmp_name'][$key], $uploadDir . $fileName)) {
                    $eventModel->addGalleryImage($eventId, $uploadDir . $fileName);
                }
            }
        }
    }
    $message = "Gallery updated successfully!";
    $activeTab = 'events';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_gallery_image') {
    $eventModel->deleteGalleryImage($_POST['id']);
    if (isset($_POST['is_ajax'])) {
        echo json_encode(['success' => true]);
        exit;
    }
    $message = "Image removed from gallery!";
    $activeTab = 'events';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_event') {
    $eventModel->delete($_POST['id']);
    $message = "Event deleted!";
}

// Handle Standalone Gallery Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_gallery_section') {
    $galleryModel->createSection($_POST['title'], $_POST['description']);
    $message = "New Gallery section created!";
    $activeTab = 'gallery';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_upload_standalone') {
    $sectionId = $_POST['section_id'];
    if (isset($_FILES['section_images'])) {
        $uploadDir = 'uploads/sections/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);
        foreach ($_FILES['section_images']['name'] as $key => $val) {
            if ($_FILES['section_images']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName = time() . '_' . rand(100, 999) . '_' . basename($_FILES['section_images']['name'][$key]);
                if (move_uploaded_file($_FILES['section_images']['tmp_name'][$key], $uploadDir . $fileName)) {
                    $galleryModel->addPhoto($sectionId, $uploadDir . $fileName);
                }
            }
        }
    }
    $message = "Photos uploaded successfully!";
    $activeTab = 'gallery';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_section') {
    $galleryModel->deleteSection($_POST['id']);
    $message = "Gallery section deleted!";
    $activeTab = 'gallery';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_photo') {
    $galleryModel->deletePhoto($_POST['id']);
    if (isset($_POST['is_ajax'])) {
        echo json_encode(['success' => true]);
        exit;
    }
    $message = "Photo removed!";
    $activeTab = 'gallery';
}

// Handle Board Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_board') {
    $photoUrl = 'https://via.placeholder.com/150';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/board/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);
        $fileName = time() . '_' . basename($_FILES['photo']['name']);
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $fileName)) {
            $photoUrl = $uploadDir . $fileName;
        }
    }
    $boardModel->add($_POST['name'], $_POST['role'], $photoUrl, $_POST['committee'], $_POST['bio'] ?? '', $_POST['linkedin_url'] ?? '');
    $message = "Board member added!";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_board') {
    $photoUrl = $_POST['existing_photo'];
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/board/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);
        $fileName = time() . '_' . basename($_FILES['photo']['name']);
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $fileName)) {
            $photoUrl = $uploadDir . $fileName;
        }
    }
    $boardModel->update($_POST['id'], $_POST['name'], $_POST['role'], $photoUrl, $_POST['committee'], $_POST['bio'] ?? '', $_POST['linkedin_url'] ?? '');
    if (isset($_POST['is_ajax'])) {
        echo json_encode(['success' => true, 'message' => "Update success!"]);
        exit;
    }
    $message = "Board member updated!";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $isAjax = isset($_POST['is_ajax']);

    if ($action === 'delete_board_member') {
        $boardModel->delete($_POST['id']);
        if ($isAjax) {
            echo json_encode(['success' => true]);
            exit;
        }
        $redirectTab = 'board';
    } elseif ($action === 'toggle_best') {
        $boardModel->setBest($_POST['id'], $_POST['is_best']);
        if ($isAjax) {
            echo json_encode(['success' => true]);
            exit;
        }
        $redirectTab = 'board';
    } elseif ($action === 'delete_member') {
        $db->query("DELETE FROM members WHERE id = ?", [$_POST['id']]);
        $redirectTab = 'registrations';
    } elseif ($action === 'delete_enrollment') {
        $db->query("DELETE FROM enrollments WHERE id = ?", [$_POST['id']]);
        $redirectTab = 'registrations';
    } elseif ($action === 'delete_event') {
        $eventModel->delete($_POST['id']);
        if ($isAjax) {
            echo json_encode(['success' => true]);
            exit;
        }
        $redirectTab = 'events';
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
            <div class="header-content" style="display: flex; align-items: center; gap: 2rem;">
                <img src="logo.png?v=1" alt="Logo" style="height: 60px; width: auto;">
                <div>
                    <h1 class="text-gradient">Admin Dashboard</h1>
                    <p>Welcome back, <span class="user-accent"><?= htmlspecialchars($_SESSION['username']) ?></span></p>
                </div>
            </div>
            <div class="header-status">
                <span class="status-badge"><i class="fas fa-circle"></i> System Online</span>
            </div>
        </div>

        <?php if ($message): ?>
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
                    <a href="?tab=registrations"
                        class="side-link <?= $activeTab === 'registrations' ? 'active' : '' ?>">
                        <i class="fas fa-users"></i> Registrations
                    </a>
                    <a href="?tab=board" class="side-link <?= $activeTab === 'board' ? 'active' : '' ?>">
                        <i class="fas fa-id-badge"></i> Board Members
                    </a>
                    <a href="?tab=events" class="side-link <?= $activeTab === 'events' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-alt"></i> Events
                    </a>
                    <a href="?tab=forms" class="side-link <?= $activeTab === 'forms' ? 'active' : '' ?>">
                        <i class="fas fa-poll"></i> Dynamic Forms
                    </a>
                    <a href="?tab=courses" class="side-link <?= $activeTab === 'courses' ? 'active' : '' ?>">
                        <i class="fas fa-book-open"></i> LMS Management
                    </a>
                    <a href="?tab=gallery" class="side-link <?= $activeTab === 'gallery' ? 'active' : '' ?>">
                        <i class="fas fa-images"></i> Gallery Sections
                    </a>
                    <a href="?tab=content" class="side-link <?= $activeTab === 'content' ? 'active' : '' ?>">
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
                                    <input type="text" id="memberSearch" placeholder="Search members..."
                                        onkeyup="filterMembers()">
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
                                                <form method="POST" onsubmit="return confirm('Remove this member?');"
                                                    style="display:inline;">
                                                    <input type="hidden" name="action" value="delete_member">
                                                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm-icon"><i
                                                            class="fas fa-user-minus"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (empty($members)): ?>
                            <p style="padding: 2rem; text-align: center; color: var(--text-muted);">No members registered yet.
                            </p>
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
                                                <form method="POST" onsubmit="return confirm('Remove this enrollment?');"
                                                    style="display:inline;">
                                                    <input type="hidden" name="action" value="delete_enrollment">
                                                    <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm-icon"><i
                                                            class="fas fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (empty($enrollments)): ?>
                            <p style="padding: 2rem; text-align: center; color: var(--text-muted);">No enrollments recorded yet.
                            </p>
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
                            <h3 class="text-gradient">Manage Board Members</h3>
                        </div>
                        <div class="board-management-layout">
                            <div class="management-form">
                                <form method="POST" enctype="multipart/form-data" class="styled-form" id="addBoardForm">
                                    <input type="hidden" name="action" value="add_board">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Name</label>
                                            <input type="text" name="name" class="form-input" required id="prev-name">
                                        </div>
                                        <div class="form-group">
                                            <label>Role</label>
                                            <input type="text" name="role" class="form-input" required id="prev-role">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Committee</label>
                                        <select name="committee" class="form-input" required id="prev-committee">
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
                                        <label>Bio / Brief (Optional)</label>
                                        <textarea name="bio" class="form-input" rows="2"
                                            placeholder="Tell us about this member..." id="prev-bio"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>LinkedIn URL (Optional)</label>
                                        <input type="url" name="linkedin_url" class="form-input"
                                            placeholder="https://linkedin.com/in/..." id="prev-linkedin">
                                    </div>
                                    <div class="form-group">
                                        <label>Photo</label>
                                        <input type="file" name="photo" class="form-input" accept="image/*" required
                                            id="prev-photo-input">
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-full">Add Member</button>
                                </form>
                            </div>

                            <div class="management-preview">
                                <div class="preview-label">Live Preview</div>
                                <div class="board-card glass-panel" id="live-preview-card">
                                    <div class="member-photo-wrapper">
                                        <div class="member-glow"></div>
                                        <div class="member-photo">
                                            <img src="https://via.placeholder.com/150" id="preview-img">
                                        </div>
                                    </div>
                                    <div class="member-info">
                                        <h3 class="member-name text-gradient" id="preview-name-text">Member Name</h3>
                                        <div class="role-badge-container">
                                            <span class="role-badge" id="preview-role-text">Role</span>
                                        </div>
                                        <p class="member-bio" id="preview-bio-text"
                                            style="margin-top: 1rem; color: var(--text-muted); font-size: 0.9rem; line-height: 1.4; opacity: 0.7;">
                                        </p>
                                        <div id="preview-linkedin-icon" style="margin-top:1rem; display:none;">
                                            <i class="fab fa-linkedin" style="color:#0077b5; font-size:1.2rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                                    <button type="button"
                                        class="btn btn-icon <?= $bm['is_best'] ? 'btn-featured' : 'btn-not-featured' ?>"
                                        onclick="toggleFeatured(this, <?= $bm['id'] ?>, <?= $bm['is_best'] ? 'true' : 'false' ?>)"
                                        title="<?= $bm['is_best'] ? 'Unmark as best' : 'Mark as one of the best' ?>">
                                        <i class="fas fa-star"></i>
                                    </button>
                                    <button type="button" class="btn btn-icon btn-edit"
                                        onclick="openEditBoardModal(<?= $bm['id'] ?>, '<?= addslashes(htmlspecialchars($bm['name'])) ?>', '<?= addslashes(htmlspecialchars($bm['role'])) ?>', '<?= $bm['photo_url'] ?>', '<?= $bm['committee'] ?>', '<?= addslashes(htmlspecialchars($bm['bio'] ?? '')) ?>', '<?= addslashes(htmlspecialchars($bm['linkedin_url'] ?? '')) ?>')"
                                        title="Edit Member"><i class="fas fa-edit"></i></button>
                                    <button type="button" class="btn btn-danger btn-icon" title="Delete"
                                        onclick="deleteBoardMember(<?= $bm['id'] ?>, this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($activeTab === 'events'): ?>
                    <?php $events = $eventModel->getAll(); ?>

                    <div class="glass-panel content-section">
                        <div class="section-header">
                            <h2 class="text-gradient">Manage Events / News</h2>
                        </div>
                        <div class="board-management-layout">
                            <div class="management-form">
                                <form method="POST" enctype="multipart/form-data" class="styled-form" id="addEventForm">
                                    <input type="hidden" name="action" value="add_event">

                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Event Title</label>
                                            <input type="text" name="title" class="form-input" required
                                                id="prev-event-title">
                                        </div>
                                        <div class="form-group">
                                            <label>Category</label>
                                            <select name="category" class="form-input" required>
                                                <option value="Workshop">Workshop</option>
                                                <option value="Session">Session</option>
                                                <option value="Competition">Competition</option>
                                                <option value="Social">Social</option>
                                                <option value="Conference">Conference</option>
                                                <option value="General">General</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Event Date & Time</label>
                                            <input type="datetime-local" name="event_date" class="form-input" required
                                                id="prev-event-date">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea name="description" class="form-textarea" rows="3"
                                            id="prev-event-desc"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Event Cover Image</label>
                                        <input type="file" name="image" class="form-input" accept="image/*"
                                            id="prev-event-img-input">
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-full">Publish Event</button>
                                </form>
                            </div>

                            <div class="management-preview">
                                <div class="preview-label">Live Preview</div>
                                <div class="glass-panel event-card-admin" id="live-event-preview"
                                    style="transform: scale(0.9); transform-origin: top center;">
                                    <img src="https://via.placeholder.com/300x200" class="event-img-admin"
                                        id="preview-event-img">
                                    <div class="event-content-admin">
                                        <span class="event-date-badge"><i class="far fa-calendar"></i>
                                            <span id="preview-event-date-text">Oct 25, 2023</span></span>
                                        <h4 id="preview-event-title-text" style="font-size: 1.1rem; margin-bottom: 0.5rem;">
                                            Event Title</h4>
                                        <p id="preview-event-desc-text"
                                            style="font-size: 0.9rem; color: var(--text-muted);">Event description will
                                            appear here...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="events-grid-admin">
                        <?php foreach ($events as $event): ?>
                            <div class="glass-panel event-card-admin">
                                <img src="<?= htmlspecialchars($event['image_path']) ?>" class="event-img-admin">
                                <div class="event-content-admin">
                                    <span class="event-date-badge"><i class="far fa-calendar"></i>
                                        <?= date('M d, Y', strtotime($event['event_date'])) ?></span>
                                    <h4><?= htmlspecialchars($event['title']) ?></h4>
                                    <p><?= htmlspecialchars($event['description']) ?></p>
                                </div>
                                <div class="event-actions-flex">
                                    <button type="button" class="btn btn-outline btn-sm"
                                        onclick="openGalleryManager(<?= $event['id'] ?>, '<?= addslashes(htmlspecialchars($event['title'])) ?>')">
                                        <i class="fas fa-images"></i> Manage Gallery
                                    </button>
                                    <button type="button" class="btn btn-danger btn-icon" title="Delete Event"
                                        onclick="deleteEvent(<?= $event['id'] ?>, this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Gallery Manager Interface (Hidden by default) -->
                    <div id="gallery-manager" class="glass-panel content-section" style="display:none; margin-top: 2rem;">
                        <div class="section-header">
                            <h3 id="gallery-title" class="text-gradient">Gallery Management</h3>
                        </div>
                        <div class="gallery-management-grid">
                            <div class="upload-zone">
                                <h4>Add Photos</h4>
                                <form method="POST" enctype="multipart/form-data" class="styled-form mini-form">
                                    <input type="hidden" name="action" value="bulk_upload_gallery">
                                    <input type="hidden" name="event_id" id="gallery-event-id">
                                    <div class="form-group">
                                        <label>Select Multiple Images</label>
                                        <input type="file" name="gallery_images[]" class="form-input" multiple
                                            accept="image/*" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-full">Upload Photos</button>
                                </form>
                            </div>
                            <div class="existing-photos">
                                <h4>Current Photos</h4>
                                <div id="gallery-photos-container" class="admin-gallery-preview">
                                    <!-- Loaded via AJAX -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        function openGalleryManager(id, title) {
                            const manager = document.getElementById('gallery-manager');
                            manager.style.display = 'block';
                            manager.scrollIntoView({ behavior: 'smooth' });
                            document.getElementById('gallery-title').innerText = 'Gallery: ' + title;
                            document.getElementById('gallery-event-id').value = id;
                            loadGalleryPhotos(id);
                        }

                        function loadGalleryPhotos(eventId) {
                            const container = document.getElementById('gallery-photos-container');
                            container.innerHTML = '<p>Loading...</p>';

                            fetch('get_gallery.php?event_id=' + eventId)
                                .then(res => res.json())
                                .then(data => {
                                    if (data.length === 0) {
                                        container.innerHTML = '<p class="text-muted">No photos in this gallery yet.</p>';
                                        return;
                                    }
                                    let html = '';
                                    data.forEach(img => {
                                        html += `
                                            <div class="admin-gallery-item">
                                                <img src="${img.image_path}">
                                                <button type="button" class="remove-gallery-img" onclick="deleteGalleryPhoto(${img.id}, ${eventId})">
                                                    &times;
                                                </button>
                                            </div>`;
                                    });
                                    container.innerHTML = html;
                                });
                        }

                        function deleteGalleryPhoto(id, eventId) {
                            if (!confirm('Delete this photo from gallery?')) return;
                            const fd = new FormData();
                            fd.append('action', 'delete_gallery_image');
                            fd.append('id', id);
                            fd.append('is_ajax', '1');

                            fetch('dashboard.php', { method: 'POST', body: fd })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) loadGalleryPhotos(eventId);
                                });
                        }
                    </script>

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
                                <input type="text" name="title" class="form-input" required
                                    placeholder="Workshop Registration, Membership, etc.">
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <input type="text" name="description" class="form-input"
                                    placeholder="Briefly describe the purpose of this form">
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

                            <button type="submit" class="btn btn-primary btn-full" style="margin-top: 2rem;">Save & Generate
                                Form</button>
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
                                                    <a href="form_responses.php?id=<?= $f['id'] ?>"
                                                        class="btn btn-icon btn-edit" title="View Responses"><i
                                                            class="fas fa-list-alt"></i></a>
                                                    <a href="view_form.php?id=<?= $f['id'] ?>" target="_blank"
                                                        class="btn btn-icon btn-view" title="View Public Form"><i
                                                            class="fas fa-external-link-alt"></i></a>
                                                    <form method="POST"
                                                        onsubmit="return confirm('Permanently delete this form?');"
                                                        style="display:inline;">
                                                        <input type="hidden" name="action" value="delete_form">
                                                        <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                                        <button type="submit" class="btn btn-icon btn-danger"><i
                                                                class="fas fa-trash"></i></button>
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
                                        <div class="course-nav-item"
                                            onclick="loadCourseContent(<?= $c['id'] ?>, '<?= addslashes(htmlspecialchars($c['title'])) ?>', this)">
                                            <div class="nav-item-main">
                                                <div class="nav-item-icon"><i class="fas fa-graduation-cap"></i></div>
                                                <span><?= htmlspecialchars($c['title']) ?></span>
                                            </div>
                                            <form method="POST"
                                                onsubmit="event.stopPropagation(); return confirm('Delete this course and all its enrollments?');"
                                                class="inline-delete">
                                                <input type="hidden" name="action" value="delete_course">
                                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                <button type="submit" class="btn-clear text-danger"
                                                    onclick="event.stopPropagation()"><i
                                                        class="fas fa-times-circle"></i></button>
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

                                <form method="POST" id="content-form" class="styled-form" style="display: none;"
                                    enctype="multipart/form-data">
                                    <div class="editor-header">
                                        <h3 id="editor-title" class="text-gradient">Editing Track</h3>
                                        <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                                    </div>

                                    <input type="hidden" name="action" value="update_course_content">
                                    <input type="hidden" name="course_id" id="course-id">

                                    <div class="form-group">
                                        <label class="form-label-fancy primary"><i class="fas fa-file-upload"></i> Upload
                                            Course Material (PDF, Word, PPT)</label>
                                        <input type="file" name="course_file" class="form-input"
                                            accept=".pdf,.doc,.docx,.ppt,.pptx">
                                        <div id="file-status" class="editor-tips"
                                            style="margin-top: 0.5rem; color: var(--secondary-neon);"></div>
                                    </div>

                                    <div class="form-group">
                                        <label>Content Payload (HTML & Embedded Media Supported)</label>
                                        <textarea name="content" id="course-content" class="form-textarea editor-textarea"
                                            rows="20"></textarea>
                                        <div class="editor-tips">
                                            <span><i class="fas fa-info-circle"></i> Supports <code>&lt;iframe&gt;</code>
                                                for YouTube/Vimeo.</span>
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
                            document.getElementById('extra-manager-section').style.display = 'block';
                            document.getElementById('extra-course-id').value = id;
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

                        const courseFiles = {
                            <?php foreach ($courses as $c): ?>
                                                                                                                                                                                                                                                <?= $c['id'] ?>: '<?= $c['file_path'] ?? '' ?>',
                            <?php endforeach; ?>
                        };
                    </script>

                    <div id="extra-manager-section" style="display:none; margin-top: 3rem;"
                        class="glass-panel content-section">
                        <div class="section-header">
                            <h2 class="text-gradient">Course Supplemental Materials</h2>
                        </div>
                        <div class="gallery-management-grid">
                            <div class="existing-photos">
                                <h3>Existing Items</h3>
                                <div id="extras-list-container">
                                    <!-- Loaded via JS -->
                                </div>
                            </div>
                            <div class="upload-zone">
                                <h3>Add New Item</h3>
                                <form method="POST" enctype="multipart/form-data" class="styled-form mini-form">
                                    <input type="hidden" name="action" value="add_course_extra">
                                    <input type="hidden" name="course_id" id="extra-course-id">

                                    <div class="form-group">
                                        <label>Title</label>
                                        <input type="text" name="extra_title" class="form-input" required
                                            placeholder="e.g. Session 1 Slides">
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
                                        <input type="text" name="extra_link" class="form-input"
                                            placeholder="https://youtube.com/...">
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

                <?php elseif ($activeTab === 'gallery'): ?>
                    <?php $sections = $galleryModel->getAllSections(); ?>

                    <div class="glass-panel content-section">
                        <div class="section-header">
                            <h2 class="text-gradient">Gallery Collections (Non-Event)</h2>
                        </div>
                        <div class="board-management-layout">
                            <div class="management-form">
                                <form method="POST" class="styled-form">
                                    <input type="hidden" name="action" value="create_gallery_section">
                                    <div class="form-group">
                                        <label>Collection Title</label>
                                        <input type="text" name="title" class="form-input" required
                                            placeholder="e.g., Student Life, Campus Moments">
                                    </div>
                                    <div class="form-group">
                                        <label>Brief Description</label>
                                        <textarea name="description" class="form-textarea" rows="2"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-full">Create Collection</button>
                                </form>
                            </div>
                            <div class="management-preview">
                                <div class="preview-label">Gallery Management Tips</div>
                                <div class="glass-panel"
                                    style="padding: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
                                    <p><i class="fas fa-info-circle"></i> Use this for photos that aren't tied to a specific
                                        workshop or session.</p>
                                    <p style="margin-top: 1rem;"><i class="fas fa-layer-group"></i> Collections will appear
                                        as separate sections on the public Gallery page.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="events-grid-admin">
                        <?php foreach ($sections as $sec): ?>
                            <div class="glass-panel event-card-admin">
                                <div style="padding: 1.5rem;">
                                    <h4 class="text-gradient"><?= htmlspecialchars($sec['title']) ?></h4>
                                    <p style="font-size: 0.85rem; color: var(--text-muted); min-height: 40px;">
                                        <?= htmlspecialchars($sec['description'] ?: 'No description provided.') ?>
                                    </p>
                                    <div class="event-actions-flex" style="margin-top: 1.5rem;">
                                        <button type="button" class="btn btn-outline btn-sm"
                                            onclick="openStandaloneGallery(<?= $sec['id'] ?>, '<?= addslashes(htmlspecialchars($sec['title'])) ?>')">
                                            <i class="fas fa-plus"></i> Add Photos
                                        </button>
                                        <form method="POST" style="display:inline;"
                                            onsubmit="return confirm('Delete this entire collection and all its photos?');">
                                            <input type="hidden" name="action" value="delete_section">
                                            <input type="hidden" name="id" value="<?= $sec['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-icon"><i
                                                    class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Standalone Photo Manager -->
                    <div id="standalone-manager" class="glass-panel content-section"
                        style="display:none; margin-top: 2rem;">
                        <div class="section-header">
                            <h3 id="standalone-title" class="text-gradient">Manage Collection</h3>
                        </div>
                        <div class="gallery-management-grid">
                            <div class="upload-zone">
                                <h4>Bulk Upload Photos</h4>
                                <form method="POST" enctype="multipart/form-data" class="styled-form mini-form">
                                    <input type="hidden" name="action" value="bulk_upload_standalone">
                                    <input type="hidden" name="section_id" id="standalone-section-id">
                                    <div class="form-group">
                                        <label>Select Multiple Images</label>
                                        <input type="file" name="section_images[]" class="form-input" multiple
                                            accept="image/*" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-full">Upload to Section</button>
                                </form>
                            </div>
                            <div class="existing-photos">
                                <h4>Current Section Photos</h4>
                                <div id="standalone-photos-container" class="admin-gallery-preview">
                                    <!-- Loaded via AJAX -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        function openStandaloneGallery(id, title) {
                            const manager = document.getElementById('standalone-manager');
                            manager.style.display = 'block';
                            manager.scrollIntoView({ behavior: 'smooth' });
                            document.getElementById('standalone-title').innerText = 'Collection: ' + title;
                            document.getElementById('standalone-section-id').value = id;
                            loadStandalonePhotos(id);
                        }

                        function loadStandalonePhotos(sectionId) {
                            const container = document.getElementById('standalone-photos-container');
                            container.innerHTML = '<p>Loading...</p>';

                            fetch('get_standalone_gallery.php?section_id=' + sectionId)
                                .then(res => res.json())
                                .then(data => {
                                    if (data.length === 0) {
                                        container.innerHTML = '<p class="text-muted">Empty collection.</p>';
                                        return;
                                    }
                                    let html = '';
                                    data.forEach(img => {
                                        html += `
                                            <div class="admin-gallery-item">
                                                <img src="${img.image_path}">
                                                <button type="button" class="remove-gallery-img" onclick="deleteStandalonePhoto(${img.id}, ${sectionId})">
                                                    &times;
                                                </button>
                                            </div>`;
                                    });
                                    container.innerHTML = html;
                                });
                        }

                        function deleteStandalonePhoto(id, sectionId) {
                            if (!confirm('Permanent deletion. Continue?')) return;
                            const fd = new FormData();
                            fd.append('action', 'delete_photo');
                            fd.append('id', id);
                            fd.append('is_ajax', '1');

                            fetch('dashboard.php', { method: 'POST', body: fd })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) loadStandalonePhotos(sectionId);
                                });
                        }
                    </script>

                    <script>
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
                                while (next && !next.classList.contains('committee-divider') &&
                                    next.classList.contains('board-item-admin')) {
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
                                <input type="text" name="hero_badge" class="form-input"
                                    value="<?= htmlspecialchars($heroBadge) ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label-fancy primary">Hero Main Title (HTML allowed)</label>
                                <input type="text" name="hero_title" class="form-input"
                                    value="<?= htmlspecialchars($heroTitle) ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label-fancy primary">Hero Subtitle</label>
                                <textarea name="hero_subtitle" class="form-textarea"
                                    rows="3"><?= htmlspecialchars($heroSubtitle) ?></textarea>
                            </div>
                        </div>

                        <div class="form-section-builder">
                            <h3 class="builder-title secondary"><i class="fas fa-heading"></i> Section Headers (HTML
                                allowed)</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label-fancy primary">Events Header</label>
                                    <input type="text" name="header_events" class="form-input"
                                        value="<?= htmlspecialchars($headEvents) ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label-fancy primary">Registrations Header</label>
                                    <input type="text" name="header_registrations" class="form-input"
                                        value="<?= htmlspecialchars($headRegs) ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label-fancy primary">Leadership Header</label>
                                    <input type="text" name="header_leadership" class="form-input"
                                        value="<?= htmlspecialchars($headLead) ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label-fancy primary">Join Section Header</label>
                                    <input type="text" name="header_join" class="form-input"
                                        value="<?= htmlspecialchars($headJoin) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label-fancy primary"><i class="fas fa-info-circle"></i> About Section (HTML
                                allowed)</label>
                            <textarea name="home_about" class="form-textarea"
                                rows="6"><?= htmlspecialchars($about) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label-fancy secondary"><i class="fas fa-id-badge"></i> Board Intro
                                Text</label>
                            <textarea name="home_board_intro" class="form-textarea"
                                rows="4"><?= htmlspecialchars($boardIntro) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label-fancy primary"><i class="fas fa-bullseye"></i> Club Goals (HTML
                                allowed)</label>
                            <textarea name="home_goals" class="form-textarea"
                                rows="6"><?= htmlspecialchars($goals) ?></textarea>
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
                    <label>Bio / Brief (Optional)</label>
                    <textarea name="bio" id="edit-board-bio" class="form-input" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>LinkedIn URL (Optional)</label>
                    <input type="url" name="linkedin_url" id="edit-board-linkedin" class="form-input">
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
        function openEditBoardModal(id, name, role, photo, committee, bio, linkedin) {
            document.getElementById('editBoardModal').style.display = 'flex';
            document.getElementById('edit-board-id').value = id;
            document.getElementById('edit-board-name').value = name;
            document.getElementById('edit-board-role').value = role;
            document.getElementById('edit-board-existing-photo').value = photo;
            document.getElementById('edit-board-committee').value = committee;
            document.getElementById('edit-board-bio').value = bio;
            document.getElementById('edit-board-linkedin').value = linkedin;
        }
        function closeEditBoardModal() {
            document.getElementById('editBoardModal').style.display = 'none';
        }

        // --- DYNAMIC BOARD FEATURES ---

        // 1. Live Card Preview for Add Form
        const previewInputs = {
            name: document.getElementById('prev-name'),
            role: document.getElementById('prev-role'),
            committee: document.getElementById('prev-committee'),
            bio: document.getElementById('prev-bio'),
            linkedin: document.getElementById('prev-linkedin'),
            photo: document.getElementById('prev-photo-input')
        };

        const previewTexts = {
            name: document.getElementById('preview-name-text'),
            role: document.getElementById('preview-role-text'),
            bio: document.getElementById('preview-bio-text'),
            linkedin: document.getElementById('preview-linkedin-icon'),
            img: document.getElementById('preview-img')
        };

        if (previewInputs.name) {
            previewInputs.name.addEventListener('input', e => previewTexts.name.textContent = e.target.value || "Member Name");
            previewInputs.role.addEventListener('input', e => previewTexts.role.textContent = e.target.value || "Role");
            previewInputs.bio.addEventListener('input', e => previewTexts.bio.textContent = e.target.value);
            previewInputs.linkedin.addEventListener('input', e => {
                previewTexts.linkedin.style.display = e.target.value ? 'block' : 'none';
            });
            previewInputs.photo.addEventListener('change', e => {
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = ev => previewTexts.img.src = ev.target.result;
                    reader.readAsDataURL(e.target.files[0]);
                }
            });
        }

        // 2. AJAX Featured Toggle
        async function toggleFeatured(btn, id, currentStatus) {
            const formData = new FormData();
            formData.append('action', 'toggle_best');
            formData.append('id', id);
            formData.append('is_best', currentStatus ? 0 : 1);
            formData.append('is_ajax', 1);

            btn.style.opacity = '0.5';
            btn.style.pointerEvents = 'none';

            try {
                const response = await fetch('dashboard.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    const isNowBest = !currentStatus;
                    btn.className = `btn btn-icon ${isNowBest ? 'btn-featured' : 'btn-not-featured'}`;
                    btn.title = isNowBest ? 'Unmark as best' : 'Mark as one of the best';
                    btn.onclick = () => toggleFeatured(btn, id, isNowBest);
                    btn.closest('.board-item-admin').classList.add('ajax-success-pulse');
                    setTimeout(() => btn.closest('.board-item-admin').classList.remove('ajax-success-pulse'), 500);
                }
            } catch (err) { console.error("Toggle failed", err); } finally {
                btn.style.opacity = '1';
                btn.style.pointerEvents = 'auto';
            }
        }

        // 3. AJAX Delete Member
        async function deleteBoardMember(id, btn) {
            if (!confirm('Permanently remove this member?')) return;

            const card = btn.closest('.board-item-admin');
            const formData = new FormData();
            formData.append('action', 'delete_board_member');
            formData.append('id', id);
            formData.append('is_ajax', 1);

            try {
                const response = await fetch('dashboard.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    card.classList.add('deleting-item');
                    setTimeout(() => card.remove(), 400);
                }
            } catch (err) { console.error("Delete failed", err); }
        }

        // 4. Enhanced Search & Filter
        function filterBoard() {
            const query = document.getElementById('boardSearch').value.toLowerCase();
            const committee = document.getElementById('committeeFilter').value;
            const items = document.querySelectorAll('.board-item-admin');
            const dividers = document.querySelectorAll('.committee-divider');

            items.forEach(item => {
                const name = item.querySelector('h4').textContent.toLowerCase();
                const itemComm = item.querySelector('.committee-tag').textContent;
                const matchesSearch = name.includes(query);
                const matchesComm = (committee === 'all' || itemComm === committee);

                if (matchesSearch && matchesComm) {
                    item.style.display = 'flex';
                    item.style.opacity = '1';
                    item.style.transform = 'scale(1)';
                } else {
                    item.style.display = 'none';
                }
            });

            // Hide empty committee dividers
            dividers.forEach(div => {
                const commType = div.querySelector('h3').textContent.toLowerCase();
                let hasVisible = false;
                items.forEach(item => {
                    const itemComm = item.querySelector('.committee-tag').textContent.toLowerCase();
                    if (itemComm === commType && item.style.display !== 'none') hasVisible = true;
                });
                div.style.display = hasVisible ? 'block' : 'none';
            });
        }

        // 5. Events Live Preview
        const prevEventInputs = {
            title: document.getElementById('prev-event-title'),
            date: document.getElementById('prev-event-date'),
            desc: document.getElementById('prev-event-desc'),
            img: document.getElementById('prev-event-img-input')
        };
        const prevEventTexts = {
            title: document.getElementById('preview-event-title-text'),
            date: document.getElementById('preview-event-date-text'),
            desc: document.getElementById('preview-event-desc-text'),
            img: document.getElementById('preview-event-img')
        };

        if (prevEventInputs.title) {
            prevEventInputs.title.addEventListener('input', e => prevEventTexts.title.textContent = e.target.value || "Event Title");
            prevEventInputs.date.addEventListener('input', e => {
                const d = new Date(e.target.value);
                prevEventTexts.date.textContent = isNaN(d) ? "Date" : d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            });
            prevEventInputs.desc.addEventListener('input', e => prevEventTexts.desc.textContent = e.target.value || "Description...");
            prevEventInputs.img.addEventListener('change', e => {
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = ev => prevEventTexts.img.src = ev.target.result;
                    reader.readAsDataURL(e.target.files[0]);
                }
            });
        }

        // 6. AJAX Delete Event
        async function deleteEvent(id, btn) {
            if (!confirm('Permanently delete this event?')) return;

            const card = btn.closest('.event-card-admin');
            const formData = new FormData();
            formData.append('action', 'delete_event');
            formData.append('id', id);
            formData.append('is_ajax', 1);

            try {
                const response = await fetch('dashboard.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    card.classList.add('deleting-item');
                    setTimeout(() => card.remove(), 400);
                }
            } catch (err) { console.error("Delete event failed", err); }
        }
    </script>


</body>

</html>