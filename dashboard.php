<?php
require_once 'Auth.php';
$csrf_token = Auth::generateCSRFToken();
require_once 'Database.php';
require_once 'Site.php';
require_once 'Logger.php';
require_once 'Analytics.php';
require_once 'Gamification.php';
require_once 'EmailQueue.php';

$analytics = new Analytics();
$gamification = new Gamification();
$emailQueue = new EmailQueue();
$analytics->trackView('Dashboard');

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
    // Courses Completion Safety
    $res = $db->query("DESCRIBE courses");
    $cols = $res->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('allow_completion', $cols)) {
        $db->query("ALTER TABLE courses ADD COLUMN allow_completion TINYINT DEFAULT 0");
    }
    if (!in_array('is_hero', $cols)) {
        $db->query("ALTER TABLE courses ADD COLUMN is_hero TINYINT DEFAULT 0");
    }
    if (!in_array('exam_id', $cols)) {
        $db->query("ALTER TABLE courses ADD COLUMN exam_id INT DEFAULT NULL");
    }
    if (!in_array('passing_score', $cols)) {
        $db->query("ALTER TABLE courses ADD COLUMN passing_score INT DEFAULT 60");
    }

    // Forms Automation Safety
    $res = $db->query("DESCRIBE forms");
    $cols = $res->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('automation_email_subject', $cols)) {
        $db->query("ALTER TABLE forms ADD COLUMN automation_email_subject VARCHAR(255) DEFAULT NULL");
        $db->query("ALTER TABLE forms ADD COLUMN automation_email_template TEXT DEFAULT NULL");
    }
    // Advanced Forms Automation
    if (!in_array('automation_delay_hours', $cols)) {
        $db->query("ALTER TABLE forms ADD COLUMN automation_delay_hours INT DEFAULT 0");
    }
    if (!in_array('automation_conditions', $cols)) {
        $db->query("ALTER TABLE forms ADD COLUMN automation_conditions TEXT DEFAULT NULL");
    }
    if (!in_array('type', $cols)) {
        $db->query("ALTER TABLE forms ADD COLUMN type VARCHAR(50) DEFAULT 'general'");
    }
    if (!in_array('is_hero', $cols)) {
        $db->query("ALTER TABLE forms ADD COLUMN is_hero TINYINT DEFAULT 0");
    }
    if (!in_array('type', $cols)) {
        $db->query("ALTER TABLE forms ADD COLUMN type VARCHAR(50) DEFAULT 'general'");
    }

    // Email Templates Table
    $db->query("CREATE TABLE IF NOT EXISTS email_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Seed default templates if empty
    $count = $db->query("SELECT COUNT(*) FROM email_templates")->fetchColumn();
    if ($count == 0 || $count < 4) {
        $templates = [
            [
                'name' => 'Standard Auto-Reply',
                'subject' => 'Thank you for your submission to [Form Title]',
                'body' => '<div style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;">
    <h2 style="color: #00629b;">Submission Received!</h2>
    <p>Hi,</p>
    <p>Thank you for submitting your data for <strong>[Form Title]</strong> on [Date].</p>
    <p>We have successfully received your information.</p>
    <div style="background: #f1f1f1; padding: 15px; border-radius: 5px; margin: 15px 0;">
        <strong>Result:</strong> [Status]<br>
        <strong>Score:</strong> [Score] ([Percentage])
    </div>
    <br>
    <hr style="border: 0; border-top: 1px solid #eee;">
    <p style="font-size: 0.9rem; color: #777;">Best regards,<br>IEEE MIU Team</p>
</div>'
            ],
            [
                'name' => 'Congratulations: Course Completed',
                'subject' => '🎓 Congratulations on mastering [Course Title]!',
                'body' => '<div style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #00ffaa; border-radius: 10px; background: #f0fff9;">
    <h2 style="color: #00629b;">You Did It! 🏆</h2>
    <p>Dear Student,</p>
    <p>We are thrilled to announce that you have successfully passed the final assessment and completed the track: <strong>[Course Title]</strong>.</p>
    <div style="background: white; padding: 20px; border-radius: 8px; text-align: center; border: 1px solid #eee; margin: 20px 0;">
        <span style="font-size: 1.2rem; color: #00629b; font-weight: bold;">Course Certificate Ready</span>
    </div>
    <p>You can now log in to your dashboard to download your official IEEE MIU certificate of completion.</p>
    <a href="https://ieeemiu-portal.rf.gd/dashboard.php" style="display: inline-block; padding: 12px 24px; background: #00629b; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">Claim Your Certificate</a>
    <br><br>
    <p style="font-size: 0.9rem; color: #777;">Best regards,<br>IEEE MIU Certification Board</p>
</div>'
            ],
            [
                'name' => 'Announcement',
                'subject' => 'Important Announcement: [Topic]',
                'body' => '<div style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius: 10px; background: #f9f9f9;">
    <h2 style="color: #00629b;">📢 New Announcement</h2>
    <p>Dear Community,</p>
    <p>We are excited to share some important news with you regarding our latest activities.</p>
    <div style="background: white; padding: 15px; border-radius: 5px; border-left: 4px solid #00629b;">
        [Announcement Details]
    </div>
    <p>Stay tuned for more updates!</p>
    <br>
    <p style="font-size: 0.9rem; color: #777;">Best regards,<br>IEEE MIU Board</p>
</div>'
            ],
            [
                'name' => 'Course Update',
                'subject' => 'New Content Available: [Course Title]',
                'body' => '<div style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;">
    <h2 style="color: #00629b;">🎓 Course Progress Update</h2>
    <p>Hi there,</p>
    <p>We have just added new materials or exams to the course: <strong>[Course Title]</strong>.</p>
    <p>Log in to the portal now to continue your learning journey and get one step closer to your certificate!</p>
    <a href="https://ieeemiu-portal.rf.gd/login.php" style="display: inline-block; padding: 10px 20px; background: #00629b; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px;">Go to Course</a>
    <br><br>
    <p style="font-size: 0.9rem; color: #777;">Best regards,<br>IEEE MIU Education Team</p>
</div>'
            ]
        ];

        foreach ($templates as $tpl) {
            $check = $db->query("SELECT id FROM email_templates WHERE name = ?", [$tpl['name']])->fetch();
            if (!$check) {
                $db->query("INSERT INTO email_templates (name, subject, body) VALUES (?, ?, ?)", [$tpl['name'], $tpl['subject'], $tpl['body']]);
            }
        }
    }

    // Email Queue Table
    $db->query("CREATE TABLE IF NOT EXISTS email_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        recipient_email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
        attempts INT DEFAULT 0,
        last_error TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        sent_at TIMESTAMP NULL
    )");

    // Heal email_queue if it exists but is old
    $res = $db->query("DESCRIBE email_queue");
    $cols = $res->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('attempts', $cols)) {
        $db->query("ALTER TABLE email_queue ADD COLUMN attempts INT DEFAULT 0 AFTER status");
        $db->query("ALTER TABLE email_queue ADD COLUMN last_error TEXT AFTER attempts");
    }
    if (!in_array('send_after', $cols)) {
        $db->query("ALTER TABLE email_queue ADD COLUMN send_after TIMESTAMP NULL AFTER created_at");
    }

    // Add form_id to course_extras for exams
    $res = $db->query("DESCRIBE course_extras");
    $cols = $res->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('form_id', $cols)) {
        $db->query("ALTER TABLE course_extras ADD COLUMN form_id INT DEFAULT NULL");
    }

    // Update course_extras type enum check (common for some DB setups, but usually fine to just add logically)

    // --- NEW FEATURES HEALING ---
    // Audit Logs
    $db->query("CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        username VARCHAR(255),
        action VARCHAR(255),
        details TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Login Attempts (Brute Force Protection)
    $db->query("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        username VARCHAR(255) NOT NULL,
        attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (ip_address, attempt_time)
    )");

    // RBAC System
    $db->query("CREATE TABLE IF NOT EXISTS roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) UNIQUE,
        description TEXT
    )");
    $db->query("CREATE TABLE IF NOT EXISTS permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) UNIQUE,
        description TEXT
    )");
    $db->query("CREATE TABLE IF NOT EXISTS role_permissions (
        role_id INT,
        permission_id INT,
        PRIMARY KEY(role_id, permission_id)
    )");

    // Seeding default roles/perms if empty
    $roleCount = $db->query("SELECT COUNT(*) FROM roles")->fetchColumn();
    if ($roleCount == 0) {
        $db->query("INSERT INTO roles (name, description) VALUES 
            ('Admin', 'Full system access'),
            ('HR', 'Manage members and forms'),
            ('Instructor', 'Manage course content'),
            ('Student', 'Learning access')");

        $db->query("INSERT INTO permissions (name, description) VALUES 
            ('manage_registrations', 'Can view/edit community members'),
            ('manage_board', 'Can manage board members'),
            ('manage_events', 'Can create and delete events'),
            ('manage_forms', 'Can build and view dynamic forms'),
            ('manage_courses', 'Can update LMS content'),
            ('view_analytics', 'Can see platform statistics'),
            ('view_logs', 'Can see system audit trail')");
    }

    // Analytics & Gamification
    $db->query("CREATE TABLE IF NOT EXISTS user_stats (
        user_id INT PRIMARY KEY,
        courses_completed INT DEFAULT 0,
        events_attended INT DEFAULT 0,
        last_login TIMESTAMP NULL
    )");
    $db->query("CREATE TABLE IF NOT EXISTS badges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) UNIQUE,
        description TEXT,
        requirements_type VARCHAR(50),
        requirements_value INT
    )");
    $db->query("CREATE TABLE IF NOT EXISTS user_badges (
        user_id INT,
        badge_id INT,
        awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(user_id, badge_id)
    )");
    $db->query("CREATE TABLE IF NOT EXISTS user_course_progress (
        user_id INT,
        course_id INT,
        status VARCHAR(20),
        completed_at TIMESTAMP NULL,
        PRIMARY KEY(user_id, course_id)
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

// --- ACCESS CONTROL BY ROLE ---
if (Auth::isHR() && !Auth::isAdmin() && !in_array($activeTab, ['forms', 'registrations'])) {
    $activeTab = 'forms';
}
if (Auth::isInstructor() && !Auth::isAdmin() && !in_array($activeTab, ['courses'])) {
    $activeTab = 'courses';
}
// -----------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_broadcast') {
    $templateId = $_POST['template_id'] ?? 0;
    $emailsRaw = $_POST['recipient_emails'] ?? '';
    
    $template = $db->query("SELECT * FROM email_templates WHERE id = ?", [$templateId])->fetch();
    if ($template) {
        $emails = array_unique(array_filter(array_map('trim', explode(',', $emailsRaw))));
        $queued = 0;
        foreach ($emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emailQueue->queueEmail($email, $template['subject'], $template['body']);
                $queued++;
            }
        }
        $message = "Successfully queued $queued broadcast emails! Please visit Analytics to process the queue.";
        Logger::log("Email Broadcast", "Queued $queued emails using template: " . $template['name']);
    } else {
        $message = "Error: Invalid template selected.";
    }
}

// --- EXPORT LOGS ---
if (isset($_GET['export_logs']) && $_GET['export_logs'] == '1' && Auth::isAdmin()) {
    $allLogs = $db->query("SELECT * FROM activity_logs ORDER BY created_at DESC")->fetchAll();

    $filename = 'audit_logs_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM
    fputcsv($output, ['User', 'Action', 'Details', 'IP Address', 'Date']);

    foreach ($allLogs as $log) {
        fputcsv($output, [
            $log['username'],
            $log['action'],
            $log['details'],
            $log['ip_address'],
            $log['created_at']
        ]);
    }
    fclose($output);
    exit;
}

// Handle Course Content Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_course_content') {
    $courseId = $_POST['course_id'];
    $allowCompletion = isset($_POST['allow_completion']) ? 1 : 0;
    $isHero = isset($_POST['is_hero']) ? 1 : 0;

    // Update content, completion, and hero status
    $examId = !empty($_POST['exam_id']) ? (int)$_POST['exam_id'] : null;
    $passingScore = !empty($_POST['passing_score']) ? (int)$_POST['passing_score'] : 60;
    $courseModel->updateContent($courseId, $_POST['content']);
    $db->query("UPDATE courses SET allow_completion = ?, is_hero = ?, exam_id = ?, passing_score = ? WHERE id = ?", [$allowCompletion, $isHero, $examId, $passingScore, $courseId]);

    if (isset($_FILES['course_file']) && $_FILES['course_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/courses/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);
        $fileName = time() . '_' . basename($_FILES['course_file']['name']);
        if (move_uploaded_file($_FILES['course_file']['tmp_name'], $uploadDir . $fileName)) {
            $courseModel->updateFile($courseId, $uploadDir . $fileName);
        }
    }

    $message = "Course content and safety settings updated!";
    Logger::log("Updated Course", "Updated content/safety for course ID: $courseId");
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

    // If it's an exam and a form is selected, set the file path to the form URL
    if ($_POST['extra_type'] === 'exam' && !empty($_POST['extra_form_id'])) {
        $filePath = 'view_form.php?id=' . (int) $_POST['extra_form_id'];
    }

    $formId = (!empty($_POST['extra_form_id'])) ? (int) $_POST['extra_form_id'] : null;

    $courseModel->addExtra($courseId, $_POST['extra_title'], $_POST['extra_type'], $_POST['extra_content'], $filePath, $formId);
    $message = "New " . $_POST['extra_type'] . " added successfully!";
    Logger::log("Added Course Extra", "Added " . $_POST['extra_type'] . " to course ID: $courseId");
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
    $isHero = isset($_POST['is_hero']) ? 1 : 0;
    $type = isset($_POST['type']) ? $_POST['type'] : 'general';
    $finalJson = isset($_POST['fields_json']) ? $_POST['fields_json'] : json_encode($fields);
    $formModel->create($_POST['title'], $_POST['description'], $finalJson, $type);

    // Using direct query to update is_hero since Form.php create might not have it yet or we want to be explicit
    $lastId = $db->query("SELECT LAST_INSERT_ID()")->fetchColumn();
    $db->query("UPDATE forms SET is_hero = ? WHERE id = ?", [$isHero, $lastId]);

    if (isset($_POST['is_ajax'])) {
        echo json_encode(['success' => true, 'id' => $lastId]);
        exit;
    }

    $message = "Form created successfully!";
    Logger::log("Created Form", "Created form: " . $_POST['title']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_form') {
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
    $isHero = isset($_POST['is_hero']) ? 1 : 0;
    $finalJson = isset($_POST['fields_json']) ? $_POST['fields_json'] : json_encode($fields);
    $formModel->update($_POST['id'], $_POST['title'], $_POST['description'], $finalJson);
    $db->query("UPDATE forms SET is_hero = ? WHERE id = ?", [$isHero, $_POST['id']]);

    $message = "Form updated successfully!";
    Logger::log("Updated Form", "Updated form ID: " . $_POST['id']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_form_automation') {
    // Collect conditional logic from dynamic inputs
    $conditions = [];
    if (isset($_POST['cond_field'])) {
        for ($i = 0; $i < count($_POST['cond_field']); $i++) {
            if (!empty($_POST['cond_field'][$i]) && !empty($_POST['cond_value'][$i])) {
                $conditions[] = [
                    'field' => $_POST['cond_field'][$i],
                    'operator' => $_POST['cond_operator'][$i],
                    'value' => $_POST['cond_value'][$i],
                    'template_id' => $_POST['cond_template'][$i]
                ];
            }
        }
    }

    $formModel->updateAutomation(
        $_POST['id'],
        $_POST['automation_email_subject'],
        $_POST['automation_email_template'],
        $_POST['automation_delay_hours'],
        json_encode($conditions)
    );
    $message = "Form automation settings updated!";
    Logger::log("Updated Automations", "For form ID: " . $_POST['id']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_form') {
    $formModel->delete($_POST['id']);
    $message = "Form deleted!";
    Logger::log("Deleted Form", "Deleted form ID: " . $_POST['id']);
}

// Handle Template Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_template') {
    $db->query("INSERT INTO email_templates (name, subject, body) VALUES (?, ?, ?)", [$_POST['name'], $_POST['subject'], $_POST['body']]);
    $message = "Email template created successfully!";
    $activeTab = 'templates';
    Logger::log("Created Template", "Template: " . $_POST['name']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_template') {
    $db->query("UPDATE email_templates SET name = ?, subject = ?, body = ? WHERE id = ?", [$_POST['name'], $_POST['subject'], $_POST['body'], $_POST['id']]);
    $message = "Email template updated successfully!";
    $activeTab = 'templates';
    Logger::log("Updated Template", "Template ID: " . $_POST['id']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_template') {
    $db->query("DELETE FROM email_templates WHERE id = ?", [$_POST['id']]);
    $message = "Email template deleted!";
    $activeTab = 'templates';
    Logger::log("Deleted Template", "Template ID: " . $_POST['id']);
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
    Logger::log("Created Event", "Event: " . $_POST['title']);
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
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_leaderboard') {
    $gamification->resetLeaderboard();
    $message = "Hall of Fame cleared successfully!";
    Logger::log("Reset Leaderboard", "Admin cleared the Hall of Fame stats.");
    $activeTab = 'analytics';
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
    $boardModel->add($_POST['name'], $_POST['role'], $photoUrl, $_POST['committee'], isset($_POST['bio']) ? $_POST['bio'] : '', isset($_POST['linkedin_url']) ? $_POST['linkedin_url'] : '');
    $message = "Board member added!";
    Logger::log("Created Board Member", "Name: " . $_POST['name']);
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
    $boardModel->update($_POST['id'], $_POST['name'], $_POST['role'], $photoUrl, $_POST['committee'], isset($_POST['bio']) ? $_POST['bio'] : '', isset($_POST['linkedin_url']) ? $_POST['linkedin_url'] : '');
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
        Logger::log("Deleted Board Member", "ID: " . $_POST['id']);
        if ($isAjax) {
            echo json_encode(['success' => true]);
            exit;
        }
        $redirectTab = 'board';
    } elseif ($action === 'toggle_best') {
        $boardModel->setBest($_POST['id'], $_POST['is_best']);
        Logger::log("Toggled Featured", "Member ID: " . $_POST['id'] . " to " . $_POST['is_best']);
        if ($isAjax) {
            echo json_encode(['success' => true]);
            exit;
        }
        $redirectTab = 'board';
    } elseif ($action === 'delete_member') {
        $db->query("DELETE FROM members WHERE id = ?", [$_POST['id']]);
        Logger::log("Deleted Member", "ID: " . $_POST['id']);
        $redirectTab = 'registrations';
    } elseif ($action === 'delete_enrollment') {
        $db->query("DELETE FROM enrollments WHERE id = ?", [$_POST['id']]);
        Logger::log("Deleted Enrollment", "ID: " . $_POST['id']);
        $redirectTab = 'registrations';
    } elseif ($action === 'delete_event') {
        $eventModel->delete($_POST['id']);
        Logger::log("Deleted Event", "ID: " . $_POST['id']);
        if ($isAjax) {
            echo json_encode(['success' => true]);
            exit;
        }
        $redirectTab = 'events';
    } elseif ($action === 'delete_form') {
        $formModel->delete($_POST['id']);
        Logger::log("Deleted Form", "ID: " . $_POST['id']);
        $redirectTab = 'forms';
    } elseif ($action === 'delete_course') {
        $courseModel->delete($_POST['id']);
        Logger::log("Deleted Course", "ID: " . $_POST['id']);
        $redirectTab = 'courses';
    } elseif ($action === 'update_rbac' && Auth::isAdmin()) {
        $roleId = $_POST['role_id'];
        $db->query("DELETE FROM role_permissions WHERE role_id = ?", [$roleId]);
        if (isset($_POST['perms'])) {
            foreach ($_POST['perms'] as $permId) {
                $db->query("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)", [$roleId, $permId]);
            }
        }
        Logger::log("Updated RBAC Matrix", "Role ID: $roleId");
        $message = "Permissions updated successfully!";
        $redirectTab = 'rbac';
    } elseif ($action === 'process_queue') {
        // Fix stuck emails: If any are 'pending' but have a specific timestamp from the past 'PHP version'
        // that is technically in the 'future' for DB, reset them to NULL for immediate processing.
        $db->query("UPDATE email_queue SET send_after = NULL WHERE status = 'pending' AND send_after IS NOT NULL AND send_after < DATE_ADD(NOW(), INTERVAL 1 DAY)");

        $processed = $emailQueue->process(10); // Process 10 at a time
        $message = "Successfully processed $processed emails!";
        $redirectTab = 'system';
    } else {
        $success = false;
    }

    if (isset($redirectTab)) {
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
    <link rel="stylesheet" href="style.css?v=10">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Switch Toggle */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.1);
            transition: .4s;
            border: 1px solid var(--glass-border);
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
        }

        input:checked+.slider {
            background-color: var(--secondary-neon);
        }

        input:checked+.slider:before {
            transform: translateX(26px);
        }

        .slider.round {
            border-radius: 24px;
        }

        .slider.round:before {
            border-radius: 50%;
        }

        .type-tag.badge-exam {
            background: linear-gradient(135deg, #ff0055, #ffaa00);
            color: white;
            border: none;
            font-weight: bold;
            box-shadow: 0 0 10px rgba(255, 0, 85, 0.3);
        }

        /* Exam Builder Modal Styles */
        .exam-builder-body {
            max-height: 70vh;
            overflow-y: auto;
            padding-right: 1rem;
        }

        .question-item {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .question-header h4 {
            margin: 0;
            font-size: 1rem;
            color: var(--secondary-neon);
        }

        .options-list {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .option-item {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .option-radio {
            width: 18px;
            height: 18px;
            accent-color: var(--primary-neon);
            cursor: pointer;
        }

        .option-input {
            flex: 1;
        }

        .btn-add-q {
            border: 2px dashed var(--glass-border);
            background: transparent;
            color: var(--text-muted);
            padding: 1rem;
            border-radius: 10px;
            width: 100%;
            cursor: pointer;
            transition: 0.3s;
            margin-bottom: 1.5rem;
        }

        .btn-add-q:hover {
            border-color: var(--secondary-neon);
            color: var(--secondary-neon);
            background: rgba(0, 243, 255, 0.05);
        }
    </style>
</head>

<body class="dashboard-page">

    <div class="sidebar-layout">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="logo.png" alt="IEEE MIU" class="sidebar-logo" width="32" height="32"
                    style="width: 32px; height: 32px; flex-shrink: 0;">
                <h2 class="text-gradient" style="font-size: 1.2rem; margin: 0;">Portal</h2>
            </div>

            <nav class="sidebar-nav">
                <?php if (Auth::hasPermission('manage_registrations') || Auth::isHR()): ?>
                    <a href="?tab=registrations" class="nav-link <?= $activeTab === 'registrations' ? 'active' : '' ?>">
                        <i class="fas fa-users"></i> Registrations
                    </a>
                <?php endif; ?>

                <?php if (Auth::hasPermission('manage_board')): ?>
                    <a href="?tab=board" class="nav-link <?= $activeTab === 'board' ? 'active' : '' ?>">
                        <i class="fas fa-id-badge"></i> Board Members
                    </a>
                <?php endif; ?>

                <?php if (Auth::hasPermission('manage_events')): ?>
                    <a href="?tab=events" class="nav-link <?= $activeTab === 'events' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-alt"></i> Events
                    </a>
                <?php endif; ?>

                <?php if (Auth::hasPermission('manage_forms') || Auth::isHR()): ?>
                    <a href="?tab=forms" class="nav-link <?= $activeTab === 'forms' ? 'active' : '' ?>">
                        <i class="fas fa-poll"></i> Dynamic Forms
                    </a>

                    <a href="?tab=exams" class="nav-link <?= $activeTab === 'exams' ? 'active' : '' ?>">
                        <i class="fas fa-file-signature"></i> Exams & Assessments
                    </a>

                    <a href="?tab=templates" class="nav-link <?= $activeTab === 'templates' ? 'active' : '' ?>">
                        <i class="fas fa-envelope-open-text"></i> Email Templates
                    </a>
                <?php endif; ?>

                <?php if (Auth::hasPermission('manage_courses')): ?>
                    <a href="?tab=courses" class="nav-link <?= $activeTab === 'courses' ? 'active' : '' ?>">
                        <i class="fas fa-book-open"></i> LMS Management
                    </a>
                <?php endif; ?>

                <?php if (Auth::hasPermission('manage_content')): ?>
                    <a href="?tab=gallery" class="nav-link <?= $activeTab === 'gallery' ? 'active' : '' ?>">
                        <i class="fas fa-images"></i> Gallery Sections
                    </a>
                    <a href="?tab=content" class="nav-link <?= $activeTab === 'content' ? 'active' : '' ?>">
                        <i class="fas fa-edit"></i> Site Content
                    </a>
                <?php endif; ?>

                <?php if (Auth::isAdmin()): ?>
                    <a href="?tab=rbac" class="nav-link <?= $activeTab === 'rbac' ? 'active' : '' ?>">
                        <i class="fas fa-shield-alt"></i> Access Matrix
                    </a>
                    <a href="?tab=logs" class="nav-link <?= $activeTab === 'logs' ? 'active' : '' ?>">
                        <i class="fas fa-terminal"></i> Audit Logs
                    </a>
                    <a href="?tab=analytics" class="nav-link <?= $activeTab === 'analytics' ? 'active' : '' ?>">
                        <i class="fas fa-chart-line"></i> Analytics
                    </a>
                    <a href="?tab=system" class="nav-link <?= $activeTab === 'system' ? 'active' : '' ?>">
                        <i class="fas fa-cog"></i> System
                    </a>
                <?php endif; ?>

                <div style="margin-top: auto; padding-top: 2rem;">
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-home"></i> Back to Site
                    </a>
                    <a href="logout.php" class="nav-link" style="color: #ff4757;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <header class="dashboard-card glass-panel"
                style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 class="text-gradient" style="font-size: 2rem; margin: 0;">Dashboard</h1>
                    <p style="color: var(--text-muted); margin-top: 0.5rem;">Welcome back, <span
                            style="color: var(--secondary-neon); font-weight: 600;"><?= htmlspecialchars($_SESSION['username']) ?></span>
                    </p>
                </div>
                <div class="header-status">
                    <span class="status-badge"><i class="fas fa-circle" style="font-size: 0.6rem; color: #00ffaa;"></i>
                        System Online</span>
                </div>
            </header>

            <?php if ($message): ?>
                <div class="alert alert-success glass-panel"
                    style="padding: 1.5rem; margin-bottom: 2.5rem; border-left: 4px solid #00ffaa; display: flex; align-items: center; gap: 1rem;">
                    <i class="fas fa-check-circle" style="color: #00ffaa; font-size: 1.2rem;"></i>
                    <span style="font-weight: 500;"><?= $message ?></span>
                </div>
            <?php endif; ?>

            <?php if ($activeTab === 'system'): ?>
                <?php
                $pendingEmails = $db->query("SELECT COUNT(*) FROM email_queue WHERE status = 'pending'")->fetchColumn();
                $sentEmails = $db->query("SELECT COUNT(*) FROM email_queue WHERE status = 'sent'")->fetchColumn();

                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_queue') {
                    if (Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                        $processed = $emailQueue->process(10); // Process 10 at a time
                        $message = "Successfully processed $processed emails!";
                        $pendingEmails -= $processed;
                    } else {
                        $error = "CSRF Verification Failed. Please reload the page.";
                    }
                }
                ?>
                <div class="glass-panel content-section">
                    <div class="section-header">
                        <h2 class="text-gradient">System & Communications</h2>
                    </div>

                    <div class="analytics-grid">
                        <div class="stat-card glass-panel">
                            <i class="fas fa-envelope-open-text" style="color: var(--secondary-neon);"></i>
                            <div class="stat-info">
                                <h3><?= $pendingEmails ?></h3>
                                <p>Pending Emails</p>
                            </div>
                        </div>
                        <div class="stat-card glass-panel">
                            <i class="fas fa-paper-plane" style="color: var(--primary-neon);"></i>
                            <div class="stat-info">
                                <h3><?= $sentEmails ?></h3>
                                <p>Total Sent</p>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 2rem;">
                        <form method="POST">
                            <input type="hidden" name="action" value="process_queue">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <button type="submit" class="btn btn-primary" <?= $pendingEmails == 0 ? 'disabled' : '' ?>>
                                <i class="fas fa-sync-alt"></i> Process Email Queue (Next 10)
                            </button>
                        </form>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 1rem;">
                            <i class="fas fa-info-circle"></i> Emails are processed in batches to avoid hosting server
                            limits.
                        </p>
                    </div>

                    <div style="margin-top: 3rem;">
                        <h3 class="text-gradient">Database Maintenance</h3>
                        <p style="margin: 1rem 0;">Ensure all tables and columns are up to date with the latest platform
                            features.</p>
                        <a href="db_repair.php" target="_blank" class="btn btn-outline">
                            <i class="fas fa-tools"></i> Open Database Repair Tool
                        </a>
                    </div>
                </div>

            <?php elseif ($activeTab === 'logs' && Auth::isAdmin()): ?>
                <?php
                $logs = $db->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 200")->fetchAll();
                ?>
                <div class="glass-panel content-section">
                    <div class="section-header">
                        <h2 class="text-gradient">System Audit Logs</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>IP Address</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center" style="padding: 2rem; color: var(--text-muted);">No
                                            activity logs found.</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td style="color: var(--text-muted); font-size: 0.85rem;">
                                            <?= htmlspecialchars(date('M d, Y H:i:s', strtotime($log['created_at']))) ?>
                                        </td>
                                        <td class="primary-td">
                                            <?= htmlspecialchars($log['username'] ?? 'Anonymous') ?>
                                            <span style="display:block; font-size:0.75rem; color: var(--text-muted);">
                                                ID: <?= htmlspecialchars($log['user_id'] ?? 'N/A') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="role-badge"
                                                style="font-size: 0.75rem; padding: 0.3rem 0.8rem; border-radius: 999px;">
                                                <?= htmlspecialchars($log['action']) ?>
                                            </span>
                                        </td>
                                        <td style="font-family: monospace; color: var(--secondary-neon);">
                                            <?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?>
                                        </td>
                                        <td style="font-size: 0.85rem;">
                                            <?= htmlspecialchars($log['details']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($activeTab === 'registrations' && (Auth::hasPermission('manage_registrations') || Auth::isHR())): ?>

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
                                        <input type="text" name="title" class="form-input" required id="prev-event-title">
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
                                    <p id="preview-event-desc-text" style="font-size: 0.9rem; color: var(--text-muted);">
                                        Event description will
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
                                    <input type="file" name="gallery_images[]" class="form-input" multiple accept="image/*"
                                        required>
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

                <script>                 function openGalleryManager(id, title) { const manager = document.getElementById('gallery-manager'); manager.style.display = 'block'; manager.scrollIntoView({ behavior: 'smooth' }); document.getElementById('gallery-title').innerText = 'Gallery: ' + title; document.getElementById('gallery-event-id').value = id; loadGalleryPhotos(id); }
                    function loadGalleryPhotos(eventId) {
                        const container = document.getElementById('gallery-photos-container'); container.innerHTML = '<p>Loading...</p>';
                        fetch('get_gallery.php?event_id=' + eventId).then(res => res.json()).then(data => {
                            if (data.length === 0) { container.innerHTML = '<p class="text-muted">No photos in this gallery yet.</p>'; return; } let html = ''; data.forEach(img => {
                                html += `
                                            <div class="admin-gallery-item">
                                                <img src="${img.image_path}">
                                                <button type="button" class="remove-gallery-img" onclick="deleteGalleryPhoto(${img.id}, ${eventId})">
                                                    &times;
                                                </button>
                                            </div>`;
                            }); container.innerHTML = html;
                        });
                    }
                    function deleteGalleryPhoto(id, eventId) {
                        if (!confirm('Delete this photo from gallery?')) return; const fd = new FormData(); fd.append('action', 'delete_gallery_image'); fd.append('id', id); fd.append('is_ajax', '1');
                        fetch('dashboard.php', { method: 'POST', body: fd }).then(res => res.json()).then(data => { if (data.success) loadGalleryPhotos(eventId); });
                    }
                </script>

            <?php elseif ($activeTab === 'forms'): ?>
                <?php $forms = $formModel->getAll('general'); ?>

                <div class="section-header">
                    <h2 class="text-gradient">Dynamic Form Builder</h2>
                    <p style="color: var(--text-muted);">Create general registration forms and surveys.</p>
                </div>
                <div class="glass-panel content-section">
                    <div class="form-actions-top" style="display:none; justify-content: flex-end; margin-bottom: 1rem;"
                        id="form-cancel-edit">
                        <button type="button" class="btn btn-outline btn-sm" onclick="cancelEditForm()"><i
                                class="fas fa-times"></i> Cancel Edit</button>
                    </div>
                    <form method="POST" class="styled-form" id="form-builder-form">
                        <input type="hidden" name="action" id="form-action" value="create_form">
                        <input type="hidden" name="id" id="form-id" value="">

                        <div class="form-group">
                            <label>Form Title</label>
                            <input type="text" name="title" id="form-title" class="form-input" required
                                placeholder="Workshop Registration, Membership, etc.">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <input type="text" name="description" id="form-desc" class="form-input"
                                placeholder="Briefly describe the purpose of this form">
                        </div>

                        <div class="form-group glass-panel"
                            style="padding: 1rem; border: 1px solid var(--primary-neon); margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h4 style="margin:0; color:var(--primary-neon); font-size: 0.95rem;"><i
                                        class="fas fa-star"></i> Visible on Hero Page</h4>
                                <p style="margin:0.2rem 0 0; font-size: 0.8rem; color: var(--text-muted);">Featured on
                                    landing page as active announcement/registration.</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="is_hero" id="form-is-hero">
                                <span class="slider round"></span>
                            </label>
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

                        <button type="submit" id="form-submit-btn" class="btn btn-primary btn-full"
                            style="margin-top: 2rem;">Save & Generate Form</button>
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
                                                <a href="form_responses.php?id=<?= $f['id'] ?>" class="btn btn-icon btn-edit"
                                                    title="View Responses"><i class="fas fa-list-alt"></i></a>
                                                <button type="button" class="btn btn-icon btn-view" title="Edit Form"
                                                    onclick='editForm(<?= $f['id'] ?>, <?= json_encode($f['title']) ?>, <?= json_encode($f['description']) ?>, <?= json_encode($f['fields_json']) ?>, <?= $f['is_hero'] ?? 0 ?>)'>
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <button type="button" class="btn btn-icon btn-secondary"
                                                    title="Auto-Reply Settings"
                                                    onclick='openAutomationModal(<?= $f['id'] ?>, <?= json_encode($f['automation_email_subject']) ?>, <?= json_encode($f['automation_email_template']) ?>)'>
                                                    <i class="fas fa-robot"></i>
                                                </button>
                                                <a href="view_form.php?id=<?= $f['id'] ?>" target="_blank"
                                                    class="btn btn-icon btn-view" title="View Public Form"><i
                                                        class="fas fa-external-link-alt"></i></a>
                                                <form method="POST" onsubmit="return confirm('Permanently delete this form?');"
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

                <?php if (isset($_GET['edit_id'])):
                    $editId = (int) $_GET['edit_id'];
                    $editForm = $db->query("SELECT * FROM forms WHERE id = ?", [$editId])->fetch();
                    if ($editForm): ?>
                        <script>
                            document.addEventListener('DOMContentLoaded', () => {
                                editForm(
                                    <?= $editForm['id'] ?>,
                                    <?= json_encode($editForm['title']) ?>,
                                    <?= json_encode($editForm['description']) ?>,
                                    <?= json_encode($editForm['fields_json']) ?>,
                                    <?= $editForm['is_hero'] ?? 0 ?>
                                );
                            });
                        </script>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Automation Modal -->
                <div id="automationModal" class="modal-overlay"
                    style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; align-items:center; justify-content:center;">
                    <div class="modal-content glass-panel"
                        style="max-width: 800px; width:100%; max-height: 90vh; overflow-y: auto;">
                        <div class="modal-header"
                            style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                            <h3 class="text-gradient" style="margin:0;"><i class="fas fa-robot"></i> Form Auto-Reply
                                Settings</h3>
                            <button type="button" class="btn-clear" onclick="closeAutomationModal()"
                                style="color:var(--text-muted); font-size:1.5rem;">&times;</button>
                        </div>
                        <form method="POST" class="styled-form">
                            <input type="hidden" name="action" value="update_form_automation">
                            <input type="hidden" name="id" id="auto-form-id">

                            <div class="form-group glass-panel" style="padding: 1rem; margin-bottom: 1.5rem;">
                                <h4 style="margin-top:0; color:var(--secondary-neon);"><i class="fas fa-clock"></i> Delivery
                                    Delay</h4>
                                <label>Delay sending the email by (hours)</label>
                                <input type="number" name="automation_delay_hours" id="auto-delay" class="form-input"
                                    min="0" value="0" style="max-width: 200px;">
                                <p class="text-muted" style="font-size: 0.85rem; margin-bottom: 0;">Set to 0 to send
                                    immediately upon submission.</p>
                            </div>

                            <div class="form-group">
                                <h4 style="margin-bottom:0.5rem; color:var(--primary-color);"><i
                                        class="fas fa-envelope"></i> Default Auto-Reply</h4>
                                <p class="text-muted" style="font-size: 0.85rem;">This email is sent by default if an email
                                    field is detected and no specific conditional templates are matched.</p>
                            </div>
                            <div class="form-group grid-2"
                                style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div>
                                    <label>Email Subject</label>
                                    <input type="text" name="automation_email_subject" id="auto-subject" class="form-input"
                                        placeholder="Thanks for your submission!">
                                </div>
                                <div>
                                    <label>Or use a Saved Template</label>
                                    <select class="form-input"
                                        onchange="if(this.value) { document.getElementById('auto-subject').value = this.options[this.selectedIndex].getAttribute('data-sub'); document.getElementById('auto-template').value = this.options[this.selectedIndex].getAttribute('data-body'); }">
                                        <option value="">-- Load a template --</option>
                                        <?php
                                        $tpls = $db->query("SELECT * FROM email_templates")->fetchAll();
                                        foreach ($tpls as $t): ?>
                                            <option value="<?= $t['id'] ?>" data-sub="<?= htmlspecialchars($t['subject']) ?>"
                                                data-body="<?= htmlspecialchars($t['body']) ?>">
                                                <?= htmlspecialchars($t['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Email Body (HTML allowed, use [Field Name] for placeholders)</label>
                                <textarea name="automation_email_template" id="auto-template" class="form-textarea" rows="6"
                                    placeholder="Dear [First Name], we received your submission..."></textarea>
                            </div>

                            <div class="form-group glass-panel"
                                style="padding: 1rem; margin-bottom: 1.5rem; border-top: 2px solid var(--accent-color);">
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                    <h4 style="margin:0; color:var(--accent-color);"><i class="fas fa-code-branch"></i>
                                        Conditional Logic</h4>
                                    <button type="button" class="btn btn-outline btn-sm" onclick="addCondition()"><i
                                            class="fas fa-plus"></i> Add Rule</button>
                                </div>
                                <p class="text-muted" style="font-size: 0.85rem;">Send a specific template based on the
                                    respondent's answers.</p>

                                <div id="conditions-container">
                                    <!-- Dynamic conditions injected here -->
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-full">Save Automation Settings</button>
                        </form>
                    </div>
                </div>

                <!-- Shared scripts moved to global footer -->


            <?php elseif ($activeTab === 'exams'): ?>
                <?php $exams = $formModel->getAll('exam'); ?>

                <div class="section-header">
                    <h2 class="text-gradient">Exams & Assessments</h2>
                    <p style="color: var(--text-muted);">Manage MCQ exams and training assessments.</p>
                </div>

                <div class="glass-panel content-section">
                    <div class="section-header">
                        <h3 class="text-gradient">Existing Exams</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>Exam Title</th>
                                    <th>Created Date</th>
                                    <th>Responses</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($exams as $e): ?>
                                    <tr>
                                        <td class="primary-td"><?= htmlspecialchars($e['title']) ?></td>
                                        <td class="date-td"><?= date('M d, Y', strtotime($e['created_at'])) ?></td>
                                        <td>
                                            <a href="form_responses.php?id=<?= $e['id'] ?>" class="badge-exam"
                                                style="text-decoration: none;">
                                                View Results
                                            </a>
                                        </td>
                                        <td class="text-right">
                                            <div class="actions-group">
                                                <button type="button" class="btn btn-icon btn-view" title="Edit Exam"
                                                    onclick='editExamCall(<?= $e['id'] ?>, <?= json_encode($e['title']) ?>, <?= json_encode($e['description']) ?>, <?= json_encode($e['fields_json']) ?>)'>
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <a href="view_form.php?id=<?= $e['id'] ?>" target="_blank"
                                                    class="btn btn-icon btn-view" title="Preview Exam"><i
                                                        class="fas fa-external-link-alt"></i></a>
                                                <form method="POST" onsubmit="return confirm('Permanently delete this exam?');"
                                                    style="display:inline;">
                                                    <input type="hidden" name="action" value="delete_form">
                                                    <input type="hidden" name="id" value="<?= $e['id'] ?>">
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
                    <?php if (empty($exams)): ?>
                        <p style="padding: 2rem; text-align: center; color: var(--text-muted);">No exams created yet. Use the
                            LMS Management tab to create one.</p>
                    <?php endif; ?>
                </div>

            <?php elseif ($activeTab === 'templates' && (Auth::hasPermission('manage_forms') || Auth::isHR())): ?>
                <?php $templates = $db->query("SELECT * FROM email_templates ORDER BY created_at DESC")->fetchAll(); ?>

                <div class="glass-panel content-section">
                    <div class="section-header">
                        <h2 class="text-gradient">Email Template Manager</h2>
                        <div style="display:flex; gap:10px; align-items:center;">
                            <button type="button" class="btn btn-outline mb-0" onclick="openBroadcastModal()"><i class="fas fa-bullhorn"></i> Send Broadcast</button>
                        <button type="button" class="btn btn-primary" onclick="openTemplateModal()"><i
                                class="fas fa-plus"></i> Create Template</button>
                    </div>

                    <div class="table-responsive">
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>Template Name</th>
                                    <th>Subject Line</th>
                                    <th>Last Modified</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($templates as $t): ?>
                                    <tr>
                                        <td class="primary-td"><?= htmlspecialchars($t['name']) ?></td>
                                        <td><?= htmlspecialchars($t['subject']) ?></td>
                                        <td class="date-td"><?= date('M d, Y H:i', strtotime($t['created_at'])) ?></td>
                                        <td class="text-right">
                                            <div class="actions-group">
                                                <button type="button" class="btn btn-icon btn-view" title="Edit Template"
                                                    onclick='openTemplateModal(<?= $t['id'] ?>, <?= json_encode($t['name']) ?>, <?= json_encode($t['subject']) ?>, <?= json_encode($t['body']) ?>)'>
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <form method="POST"
                                                    onsubmit="return confirm('Permanently delete this template?');"
                                                    style="display:inline;">
                                                    <input type="hidden" name="action" value="delete_template">
                                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                                    <button type="submit" class="btn btn-icon btn-danger"><i
                                                            class="fas fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (empty($templates)): ?>
                            <p style="text-align: center; color: var(--text-muted); padding: 2rem;">No email templates created
                                yet. Get started by creating one!</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Template Modal -->
                <div id="templateModal" class="modal-overlay"
                    style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; align-items:center; justify-content:center;">
                    <div class="modal-content glass-panel"
                        style="max-width: 700px; width:100%; max-height: 90vh; overflow-y: auto;">
                        <div class="modal-header"
                            style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                            <h3 class="text-gradient" style="margin:0;" id="template-modal-title"><i
                                    class="fas fa-envelope-open-text"></i> Create Template</h3>
                            <button type="button" class="btn-clear" onclick="closeTemplateModal()"
                                style="color:var(--text-muted); font-size:1.5rem;">&times;</button>
                        </div>
                        <form method="POST" class="styled-form">
                            <input type="hidden" name="action" id="template-action" value="create_template">
                            <input type="hidden" name="id" id="template-id">

                            <div class="form-group">
                                <label>Internal Name (e.g., 'Welcome Beginner', 'Rejection Followup')</label>
                                <input type="text" name="name" id="template-name" class="form-input" required>
                            </div>

                            <div class="form-group">
                                <label>Email Subject</label>
                                <input type="text" name="subject" id="template-subject" class="form-input" required
                                    placeholder="You can use placeholders like [First Name]">
                            </div>

                            <div class="form-group">
                                <label style="display: flex; justify-content: space-between;">
                                    <span>Email Body (HTML)</span>
                                    <span style="font-size: 0.8rem; color: var(--secondary-neon);"><i
                                            class="fas fa-info-circle"></i> Use exact form field labels inside brackets,
                                        e.g., <code>[Phone Number]</code></span>
                                </label>
                                <textarea name="body" id="template-body" class="form-textarea" rows="12" required
                                    placeholder="Dear [First Name]..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary btn-full" id="template-submit-btn">Save
                                Template</button>
                        </form>
                    </div>
                </div>

                <script>
                    function openTemplateModal(id = '', name = '', subject = '', body = '') {
                        document.getElementById('template-id').value = id;
                        document.getElementById('template-name').value = name;
                        document.getElementById('template-subject').value = subject;
                        document.getElementById('template-body').value = body;

                        if (id) {
                            document.getElementById('template-action').value = 'update_template';
                            document.getElementById('template-modal-title').innerHTML = '<i class="fas fa-edit"></i> Edit Template';
                            document.getElementById('template-submit-btn').innerText = 'Save Changes';
                        } else {
                            document.getElementById('template-action').value = 'create_template';
                            document.getElementById('template-modal-title').innerHTML = '<i class="fas fa-plus"></i> Create Template';
                            document.getElementById('template-submit-btn').innerText = 'Create Template';
                        }
                        document.getElementById('templateModal').style.display = 'flex';
                    }
                    function closeTemplateModal() {
                        document.getElementById('templateModal').style.display = 'none';
                    }
                </script>

                <!-- Broadcast Modal -->
                <div id="broadcastModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; align-items:center; justify-content:center;">
                    <div class="modal-content glass-panel" style="max-width: 600px; width:100%; max-height: 90vh; overflow-y: auto;">
                        <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                            <h3 class="text-gradient" style="margin:0;"><i class="fas fa-bullhorn"></i> Send Email Broadcast</h3>
                            <button type="button" class="btn-clear" onclick="closeBroadcastModal()" style="color:var(--text-muted); font-size:1.5rem;">&times;</button>
                        </div>
                        <form method="POST" class="styled-form">
                            <input type="hidden" name="action" value="send_broadcast">
                            <div class="form-group">
                                <label>Select Template</label>
                                <select name="template_id" class="form-input" required>
                                    <option value="">-- Choose Template --</option>
                                    <?php foreach ($templates as $t): ?>
                                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['subject']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label style="display: flex; justify-content: space-between;">
                                    <span>Recipient Emails</span>
                                    <span style="font-size: 0.8rem; color: var(--text-muted);">Comma-separated list</span>
                                </label>
                                <textarea name="recipient_emails" class="form-textarea" rows="6" required placeholder="user1@example.com, user2@example.com, ..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-full"><i class="fas fa-paper-plane"></i> Queue Broadcast</button>
                        </form>
                    </div>
                </div>
                <script>
                    function openBroadcastModal() { document.getElementById('broadcastModal').style.display = 'flex'; }
                    function closeBroadcastModal() { document.getElementById('broadcastModal').style.display = 'none'; }
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
                                                onclick="event.stopPropagation()"><i class="fas fa-times-circle"></i></button>
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

                                <div class="form-group glass-panel"
                                    style="padding: 1.5rem; border: 1px solid var(--secondary-neon); margin-bottom: 2rem; display: flex; flex-direction: column; gap: 1.5rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <h4 style="margin: 0; color: var(--secondary-neon);"><i
                                                    class="fas fa-certificate"></i> Graduation Safety</h4>
                                            <p
                                                style="margin: 0.5rem 0 0; font-size: 0.85rem; color: var(--text-muted); line-height: 1.4;">
                                                When enabled, students <b>must submit all linked Exams</b> before they can
                                                claim their certificate.
                                            </p>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="allow_completion" id="allow-completion">
                                            <span class="slider round"></span>
                                        </label>
                                    </div>
                                    <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 0;">
                                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                                        <h4 style="margin: 0; color: var(--secondary-neon);"><i class="fas fa-file-signature"></i> Final Assessment Connection</h4>
                                        <div class="form-row" style="display: flex; gap: 1rem;">
                                            <div class="form-group" style="flex: 2; margin-bottom: 0;">
                                                <label style="font-size: 0.85rem; color: var(--text-muted);">Select Linked Exam</label>
                                                <select name="exam_id" id="course-exam-id" class="form-input">
                                                    <option value="">-- No Exam Linked --</option>
                                                    <?php 
                                                    $examForms = $formModel->getAll('exam');
                                                    foreach($examForms as $ef): ?>
                                                        <option value="<?= $ef['id'] ?>"><?= htmlspecialchars($ef['title']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                                <label style="font-size: 0.85rem; color: var(--text-muted);">Passing Score (%)</label>
                                                <input type="number" name="passing_score" id="course-passing-score" class="form-input" min="0" max="100" value="60">
                                            </div>
                                        </div>
                                        <p style="margin: 0; font-size: 0.8rem; color: var(--text-muted);">Students must achieve this percentage to unlock their certificate.</p>
                                    </div>
                                    <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 0;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <h4 style="margin: 0; color: var(--primary-neon);"><i class="fas fa-star"></i>
                                                Featured on Hero Page</h4>
                                            <p
                                                style="margin: 0.5rem 0 0; font-size: 0.85rem; color: var(--text-muted); line-height: 1.4;">
                                                Show this track/course as a highlight on the main landing page.
                                            </p>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="is_hero" id="is-hero">
                                            <span class="slider round"></span>
                                        </label>
                                    </div>
                                </div>

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
                    const courseFiles = {
                        <?php foreach ($courses as $c): ?>
                                    <?= $c['id'] ?>: '<?= $c['file_path'] ?? '' ?>',
                        <?php endforeach; ?>
                    };
                    const completionStatus = {
                        <?php foreach ($courses as $c): ?>
                                    <?= $c['id'] ?>: <?= $c['allow_completion'] ?? 0 ?>,
                        <?php endforeach; ?>
                    };
                    const heroStatus = {
                        <?php foreach ($courses as $c): ?>
                                    <?= $c['id'] ?>: <?= $c['is_hero'] ?? 0 ?>,
                        <?php endforeach; ?>
                    };
                    const examLinks = {
                        <?php foreach ($courses as $c): ?>
                                    <?= $c['id'] ?>: <?= json_encode($c['exam_id'] ?? '') ?>,
                        <?php endforeach; ?>
                    };
                    const examPassingScores = {
                        <?php foreach ($courses as $c): ?>
                                    <?= $c['id'] ?>: <?= $c['passing_score'] ?? 60 ?>,
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
                        // Set completion toggle
                        document.getElementById('allow-completion').checked = completionStatus[id] == 1;
                        document.getElementById('is-hero').checked = heroStatus[id] == 1;
                        document.getElementById('course-exam-id').value = examLinks[id];
                        document.getElementById('course-passing-score').value = examPassingScores[id];

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
                                    let badgeClass = item.type === 'exam' ? 'badge-exam' : item.type;
                                    let typeLabel = item.type === 'exam' ? 'GRADUATION EXAM' : item.type.toUpperCase();
                                    html += `<tr>
                                    <td>${item.title}</td>
                                    <td><span class="type-tag ${badgeClass}">${typeLabel}</span></td>
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
                </script>

                <div id="extra-manager-section" style="display:none; margin-top: 3rem;" class="glass-panel content-section">
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
                                    <select name="extra_type" class="form-input" required
                                        onchange="toggleExtraInputs(this.value)">
                                        <option value="material">Learning Material (PDF/Doc)</option>
                                        <option value="record">Session Record (Video Link)</option>
                                        <option value="exam">Exam / Assessment (Form)</option>
                                    </select>
                                </div>
                                <script>
                                    function toggleExtraInputs(type) {
                                        const fileGrp = document.getElementById('file-upload-group');
                                        const linkGrp = document.getElementById('direct-link-group');
                                        const formGrp = document.getElementById('exam-form-group');

                                        if (type === 'exam') {
                                            fileGrp.style.display = 'none';
                                            linkGrp.style.display = 'none';
                                            formGrp.style.display = 'block';
                                        } else {
                                            fileGrp.style.display = 'block';
                                            linkGrp.style.display = 'block';
                                            formGrp.style.display = 'none';
                                        }
                                    }
                                </script>

                                <div class="form-group" id="file-upload-group">
                                    <label>File Upload (Optional if using link)</label>
                                    <input type="file" name="extra_file" class="form-input">
                                </div>

                                <div class="form-group" id="direct-link-group">
                                    <label>Direct Link (Optional for Records)</label>
                                    <input type="text" name="extra_link" class="form-input"
                                        placeholder="https://youtube.com/...">
                                </div>
                                <div class="form-group" id="exam-form-group" style="display:none;">
                                    <div
                                        style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 0.5rem;">
                                        <label class="form-label">Select Examination Form</label>
                                        <button type="button" class="btn btn-sm btn-outline" onclick="openExamBuilder()"
                                            style="padding: 0.2rem 0.5rem; font-size: 0.75rem;">
                                            <i class="fas fa-plus"></i> Create New Exam
                                        </button>
                                    </div>
                                    <select name="extra_form_id" class="form-input" id="extra-form-id">
                                        <option value="">-- Choose an Exam --</option>
                                        <?php
                                        $examForms = $formModel->getAll('exam');
                                        foreach ($examForms as $f): ?>
                                            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['title']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                        </div>

                        <div class="form-group">
                            <label>Notes / Description</label>
                            <textarea name="extra_content" class="form-textarea" rows="2"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-full" onclick="return validateExtra(this.form)">Add
                            Extra Content</button>
                        </form>
                    </div>
                </div>
        </div>

        <script>
            function validateExtra(form) {
                const type = form.extra_type.value;
                const file = form.extra_file.value;
                const link = form.extra_link.value;
                const formId = form.extra_form_id.value;

                if (type === 'material' && !file && !link) {
                    alert('⚠️ Please upload a file or provide a direct link for Learning Materials.');
                    return false;
                }
                if (type === 'exam' && !formId) {
                    alert('⚠️ Please select a Dynamic Form to use as the Examination.');
                    return false;
                }
                return true;
            }
        </script>

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
                    <div class="glass-panel" style="padding: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
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
                                <button type="submit" class="btn btn-danger btn-icon"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Standalone Photo Manager -->
        <div id="standalone-manager" class="glass-panel content-section" style="display:none; margin-top: 2rem;">
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
                            <input type="file" name="section_images[]" class="form-input" multiple accept="image/*"
                                required>
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
                    <input type="text" name="hero_badge" class="form-input" value="<?= htmlspecialchars($heroBadge) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label-fancy primary">Hero Main Title (HTML allowed)</label>
                    <input type="text" name="hero_title" class="form-input" value="<?= htmlspecialchars($heroTitle) ?>">
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
                        <input type="text" name="header_join" class="form-input" value="<?= htmlspecialchars($headJoin) ?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label-fancy primary"><i class="fas fa-info-circle"></i> About Section (HTML
                    allowed)</label>
                <textarea name="home_about" class="form-textarea" rows="6"><?= htmlspecialchars($about) ?></textarea>
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
                <textarea name="home_goals" class="form-textarea" rows="6"><?= htmlspecialchars($goals) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-full">Update Global Assets</button>
        </form>
        </div>

    <?php elseif ($activeTab === 'analytics' && Auth::isAdmin()): ?>
        <?php $stats = $analytics->getStats(); ?>
        <div class="analytics-dashboard">
            <div class="stats-grid">
                <div class="glass-panel stat-card premium-entry">
                    <div class="stat-icon primary"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3><?= $stats['total_members'] ?></h3>
                        <p>Total Community Members</p>
                    </div>
                </div>
                <div class="glass-panel stat-card premium-entry" style="animation-delay: 0.1s;">
                    <div class="stat-icon secondary"><i class="fas fa-graduation-cap"></i></div>
                    <div class="stat-info">
                        <h3><?= $stats['total_enrollments'] ?></h3>
                        <p>Course Enrollments</p>
                    </div>
                </div>
                <div class="glass-panel stat-card premium-entry" style="animation-delay: 0.2s;">
                    <div class="stat-icon accent"><i class="fas fa-user-check"></i></div>
                    <div class="stat-info">
                        <h3><?= $stats['active_students'] ?></h3>
                        <p>Weekly Active Students</p>
                    </div>
                </div>
            </div>

            <div class="info-grid" style="margin-top: 2rem;">
                <!-- Top Courses -->
                <div class="glass-panel content-section premium-entry" style="animation-delay: 0.3s;">
                    <div class="section-header">
                        <h2 class="text-gradient">Top Resources</h2>
                    </div>
                    <div class="resource-list">
                        <?php foreach ($stats['top_courses'] as $course): ?>
                            <div class="resource-item">
                                <div class="res-info">
                                    <span class="res-title"><?= htmlspecialchars($course['title']) ?></span>
                                    <span class="res-count"><?= $course['downloads'] ?> downloads</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= min(100, $course['downloads'] * 10) ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($stats['top_courses'])): ?>
                            <p style="color: var(--text-muted); text-align: center; padding: 2rem;">No download data yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="glass-panel content-section premium-entry" style="animation-delay: 0.4s;">
                    <div class="section-header">
                        <h2 class="text-gradient">User Activity</h2>
                    </div>
                    <?php
                    $recentLogins = $db->query("SELECT u.username, s.last_login FROM user_stats s JOIN users u ON s.user_id = u.id ORDER BY s.last_login DESC LIMIT 5")->fetchAll();
                    ?>
                    <div class="activity-feed">
                        <?php foreach ($recentLogins as $login): ?>
                            <div class="activity-item">
                                <div class="activity-dot"></div>
                                <div class="activity-text">
                                    <strong><?= htmlspecialchars($login['username']) ?></strong> logged in
                                    <span class="activity-time"><?= date('M d, H:i', strtotime($login['last_login'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Danger Zone / Management -->
                <div class="glass-panel content-section premium-entry"
                    style="animation-delay: 0.5s; border: 1px solid rgba(255, 71, 87, 0.2);">
                    <div class="section-header">
                        <h2 style="color: #ff4757;"><i class="fas fa-exclamation-triangle"></i> Hall of Fame Management</h2>
                    </div>
                    <div style="padding: 1rem;">
                        <p style="color: var(--text-muted); margin-bottom: 1.5rem; font-size: 0.9rem;">
                            Clearing the Hall of Fame will reset all student rankings, course completion counts, and event
                            attendance stats. This action cannot be undone.
                        </p>
                        <form method="POST"
                            onsubmit="return confirm('ARE YOU ABSOLUTELY SURE? This will permanently wipe all student stats and reset the leaderboard.');">
                            <input type="hidden" name="action" value="reset_leaderboard">
                            <button type="submit" class="btn btn-danger btn-full">
                                <i class="fas fa-trash-alt"></i> Wipe & Reset Leaderboard
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($activeTab === 'rbac' && Auth::isAdmin()): ?>
        <?php
        $roles = $db->query("SELECT * FROM roles")->fetchAll();
        $allPerms = $db->query("SELECT * FROM permissions")->fetchAll();
        ?>
        <div class="glass-panel content-section">
            <div class="section-header">
                <h2 class="text-gradient">Access Control Matrix</h2>
                <p style="color: var(--text-muted);">Manage granular permissions for each platform role.</p>
            </div>

            <div class="rbac-container">
                <?php foreach ($roles as $role): ?>
                    <?php if ($role['name'] === 'Admin')
                        continue; // Admin is always full access ?>
                    <form method="POST" class="rbac-role-card glass-panel">
                        <input type="hidden" name="action" value="update_rbac">
                        <input type="hidden" name="role_id" value="<?= $role['id'] ?>">

                        <div class="role-header">
                            <h3><?= htmlspecialchars($role['name']) ?></h3>
                            <p><?= htmlspecialchars($role['description']) ?></p>
                        </div>

                        <div class="permission-matrix">
                            <?php
                            $currPerms = $db->query("SELECT permission_id FROM role_permissions WHERE role_id = ?", [$role['id']])->fetchAll(PDO::FETCH_COLUMN);
                            ?>
                            <?php foreach ($allPerms as $perm): ?>
                                <label class="matrix-checkbox">
                                    <input type="checkbox" name="perms[]" value="<?= $perm['id'] ?>" <?= in_array($perm['id'], $currPerms) ? 'checked' : '' ?>>
                                    <span class="chk-label"><?= htmlspecialchars($perm['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm" style="margin-top: 1.5rem;">Save
                            <?= $role['name'] ?> Permissions</button>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>

    <?php endif; ?>
    </section> <!-- End Sections Area -->
    </main> <!-- End Main Content -->
    </div> <!-- End Sidebar Layout -->

    <script>
        // Tab Persistence & Mobile Optimizations
        document.addEventListener('DOMContentLoaded', () => {
            // Smooth transitions for sections
            const sections = document.querySelectorAll('.content-section');
            sections.forEach(s => s.classList.add('premium-entry'));
        });

        // Form Builder & Automation Shared Functions
        function addField(fieldData = null) {
            const container = document.getElementById('fields-container');
            if (!container) return;
            const div = document.createElement('div');
            div.className = 'glass-panel field-builder-item';

            let label = typeof fieldData === 'string' ? fieldData : (fieldData ? fieldData.label : '');
            let type = typeof arguments[1] === 'string' ? arguments[1] : (fieldData ? fieldData.type : 'text');
            let optionsStr = typeof arguments[2] === 'string' ? arguments[2] : (fieldData && fieldData.options ? fieldData.options.join(', ') : '');
            let required = typeof arguments[3] === 'boolean' || arguments[3] == 1 ? 'checked' : (fieldData && fieldData.required ? 'checked' : '');

            let displayOptions = ['select', 'radio', 'checkbox'].includes(type) ? 'block' : 'none';

            div.innerHTML = `
            <div class="field-controls-grid">
                <div class="form-group">
                    <label>Field Name / Label</label>
                    <input type="text" name="field_label[]" class="form-input" required placeholder="e.g. Phone Number" value="${label.replace(/"/g, '&quot;')}">
                </div>
                <div class="form-group">
                    <label>Field Type</label>
                    <select name="field_type[]" class="form-input" onchange="toggleOptions(this)">
                        <option value="text" ${type === 'text' ? 'selected' : ''}>Short Text</option>
                        <option value="email" ${type === 'email' ? 'selected' : ''}>Email Address</option>
                        <option value="number" ${type === 'number' ? 'selected' : ''}>Number Input</option>
                        <option value="textarea" ${type === 'textarea' ? 'selected' : ''}>Long Text / Bio</option>
                        <option value="date" ${type === 'date' ? 'selected' : ''}>Date Picker</option>
                        <option value="select" ${type === 'select' ? 'selected' : ''}>Dropdown Menu</option>
                        <option value="radio" ${type === 'radio' ? 'selected' : ''}>Radio Options</option>
                        <option value="checkbox" ${type === 'checkbox' ? 'selected' : ''}>Checkbox List</option>
                    </select>
                </div>
                <div class="form-group options-group" style="display: ${displayOptions};">
                    <label>Options (comma separated)</label>
                    <input type="text" name="field_options[]" class="form-input" placeholder="Option A, Option B, Option C" value="${optionsStr.replace(/"/g, '&quot;')}">
                </div>
                <div class="form-group check-group">
                    <label>Required?</label>
                    <input type="checkbox" name="field_required[]" value="1" ${required}>
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

        function editForm(id, title, desc, fields, isHero) {
            // Check if we are on the Dynamic Forms tab, if not, we can't edit visually here
            // but we can at least try to redirect or show a message. 
            // However, to fix the "Edit" button in Exams tab, we need the form builder to be visible or redirect to Forms tab.
            const builder = document.getElementById('form-builder-form');
            if (!builder) {
                // If builder isn't on current page, redirect with edit params
                window.location.href = `?tab=forms&edit_id=${id}`;
                return;
            }

            const titleEl = document.getElementById('form-builder-title');
            if (titleEl) titleEl.innerText = "Edit Form: " + title;

            document.getElementById('form-action').value = "update_form";
            document.getElementById('form-id').value = id;
            document.getElementById('form-title').value = title;
            document.getElementById('form-desc').value = desc;

            const heroCheck = document.getElementById('form-is-hero');
            if (heroCheck) heroCheck.checked = (isHero == 1);

            const cancelBtn = document.getElementById('form-cancel-edit');
            if (cancelBtn) cancelBtn.style.display = 'flex';

            const submitBtn = document.getElementById('form-submit-btn');
            if (submitBtn) submitBtn.innerText = 'Update Form';

            document.getElementById('fields-container').innerHTML = '';
            try {
                const fieldsArr = typeof fields === 'string' ? JSON.parse(fields) : fields;
                fieldsArr.forEach(f => addField(f.label, f.type, f.options.join(', '), f.required));
            } catch (e) { console.error("Error parsing fields", e); }

            builder.scrollIntoView({ behavior: 'smooth' });
        }

        function cancelEditForm() {
            const titleEl = document.getElementById('form-builder-title');
            if (titleEl) titleEl.innerText = 'Dynamic Form Builder';
            document.getElementById('form-action').value = 'create_form';
            document.getElementById('form-id').value = '';
            document.getElementById('form-title').value = '';
            document.getElementById('form-desc').value = '';
            const heroCheck = document.getElementById('form-is-hero');
            if (heroCheck) heroCheck.checked = false;
            const submitBtn = document.getElementById('form-submit-btn');
            if (submitBtn) submitBtn.innerText = 'Save & Generate Form';
            const cancelBtn = document.getElementById('form-cancel-edit');
            if (cancelBtn) cancelBtn.style.display = 'none';
            document.getElementById('fields-container').innerHTML = '';
        }

        function openAutomationModal(id, subject, template, delay = 0, conditionsJson = '[]') {
            const modal = document.getElementById('automationModal');
            if (!modal) return;
            document.getElementById('auto-form-id').value = id;
            document.getElementById('auto-subject').value = subject || '';
            document.getElementById('auto-template').value = template || '';
            document.getElementById('auto-delay').value = delay || 0;

            document.getElementById('conditions-container').innerHTML = '';
            try {
                const conditions = typeof conditionsJson === 'string' ? JSON.parse(conditionsJson || '[]') : (conditionsJson || []);
                conditions.forEach(c => addCondition(c));
            } catch (e) { console.error("Could not parse conditions", e); }

            modal.style.display = 'flex';
        }

        function addCondition(data = null) {
            const container = document.getElementById('conditions-container');
            if (!container) return;
            const div = document.createElement('div');
            div.style.cssText = "display: grid; grid-template-columns: 2fr 1fr 2fr 2fr auto; gap: 0.5rem; align-items: end; margin-bottom: 0.5rem; padding-bottom: 0.5rem; border-bottom: 1px dashed rgba(255,255,255,0.1);";

            const f = data ? data.field : '';
            const o = data ? data.operator : '==';
            const v = data ? data.value : '';
            const t = data ? data.template_id : '';

            // Note: templatesHtml needs to be refreshed or passed differently if it's dynamic
            // For now we assume the templates dropdown is populated correctly via the existing PHP loop if present

            div.innerHTML = `
                <div><label style="font-size:0.75rem;">If Field</label><input type="text" name="cond_field[]" class="form-input form-input-sm" required placeholder="e.g. Experience" value="${f.replace(/"/g, '&quot;')}"></div>
                <div>
                    <label style="font-size:0.75rem;">Operator</label>
                    <select name="cond_operator[]" class="form-input form-input-sm">
                        <option value="==" ${o === '==' ? 'selected' : ''}>Equals</option>
                        <option value="!=" ${o === '!=' ? 'selected' : ''}>Not Equals</option>
                    </select>
                </div>
                <div><label style="font-size:0.75rem;">Value</label><input type="text" name="cond_value[]" class="form-input form-input-sm" required placeholder="e.g. Beginner" value="${v.replace(/"/g, '&quot;')}"></div>
                <div>
                    <label style="font-size:0.75rem;">Send Template</label>
                    <select name="cond_template[]" class="form-input form-input-sm" required>
                        <option value="">-- Select --</option>
                        <!-- Options will be handled by caller or manual sync -->
                    </select>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="btn-clear text-danger" style="margin-bottom: 0.5rem;"><i class="fas fa-times"></i></button>
            `;

            if (t) {
                setTimeout(() => {
                    const sel = div.querySelector('select[name="cond_template[]"]');
                    if (sel) sel.value = t;
                }, 50);
            }
            container.appendChild(div);
        }

        function closeAutomationModal() {
            const modal = document.getElementById('automationModal');
            if (modal) modal.style.display = 'none';
        }
    </script>

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


    <!-- MCQ Exam Builder Modal -->
    <div id="examBuilderModal" class="modal-overlay" style="display: none;">
        <div class="glass-panel modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2 class="text-gradient"><i class="fas fa-tasks"></i> Exam & Assessment Builder</h2>
                <button onclick="closeExamBuilder()" class="close-btn">&times;</button>
            </div>
            <div class="exam-builder-body">
                <div class="form-group">
                    <label>Exam Title</label>
                    <input type="text" id="builder-exam-title" class="form-input"
                        placeholder="e.g. Final Certification Test">
                </div>
                <div class="form-group">
                    <label>Description / Instructions</label>
                    <textarea id="builder-exam-desc" class="form-textarea" rows="2"
                        placeholder="e.g. Choose the best answer for each question."></textarea>
                </div>

                <div id="questions-container">
                    <!-- Questions will be added here -->
                </div>

                <button type="button" class="btn-add-q" onclick="addCustomQuestion()">
                    <i class="fas fa-plus-circle"></i> Add New Question
                </button>
            </div>
            <div class="modal-footer" style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 1rem;">
                <button type="button" class="btn btn-outline" onclick="closeExamBuilder()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveExam()">
                    <i class="fas fa-save"></i> Save & Use Exam
                </button>
            </div>
        </div>
    </div>

    <style>
        .q-main-input { display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem; }
        .img-upload-wrapper { margin-top: 0.5rem; margin-bottom: 0.5rem; }
        .builder-img-preview { max-width: 200px; max-height: 120px; border-radius: 8px; margin-top: 0.5rem; border: 1px solid var(--glass-border); }
        .btn-icon-sm { padding: 0.4rem 0.6rem; font-size: 0.85rem; min-width: unset; }
        .sm-icon { font-size: 0.8rem; padding: 0.3rem; cursor: pointer; color: var(--text-muted); transition: var(--transition); }
        .sm-icon:hover { color: var(--primary-color, #00ffa3); }
        .sm-select { font-size: 0.85rem; padding: 0.35rem 0.5rem; }
        .option-item { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; flex-wrap: wrap; }
        .option-item .form-input.option-input { flex: 1; }
        .matching-pairs .pair-item { display: flex; gap: 0.5rem; margin-bottom: 0.5rem; align-items: center; }
        .type-container { margin-top: 1rem; }
        .file-upload-label { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.3rem 0.7rem; background: rgba(255,255,255,0.05); border: 1px dashed var(--glass-border); border-radius: 8px; cursor: pointer; font-size: 0.8rem; color: var(--text-muted); transition: var(--transition); }
        .file-upload-label:hover { border-color: var(--primary-color, #00ffa3); color: var(--primary-color, #00ffa3); }
        .file-upload-label input[type="file"] { display: none; }
        .upload-status { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem; }
        .upload-status.success { color: #2ecc71; }
        .ordering-items .order-item { display: flex; gap: 0.5rem; margin-bottom: 0.5rem; align-items: center; }
        .tf-options { display: flex; gap: 1rem; margin-top: 0.5rem; }
        .tf-options label { display: flex; align-items: center; gap: 0.4rem; cursor: pointer; padding: 0.5rem 1rem; border-radius: 8px; background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); transition: var(--transition); }
        .tf-options label:hover { border-color: var(--primary-color, #00ffa3); }
    </style>


    <script>
        let questionCount = 0;

        function openExamBuilder() {
            document.getElementById('examBuilderModal').style.display = 'flex';
            if (questionCount === 0) addCustomQuestion();
        }
        function closeExamBuilder() { document.getElementById('examBuilderModal').style.display = 'none'; }

        async function uploadImage(fileInput, previewId, hiddenId) {
            const file = fileInput.files[0]; if (!file) return;
            const statusEl = fileInput.closest('.img-upload-wrapper').querySelector('.upload-status');
            statusEl.textContent = 'Uploading...'; statusEl.className = 'upload-status';
            const fd = new FormData(); fd.append('image', file);
            try {
                const res = await fetch('upload_exam_image.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    document.getElementById(hiddenId).value = data.url;
                    if (previewId) { const img = document.getElementById(previewId); img.src = data.url; img.style.display = 'block'; }
                    statusEl.textContent = '✓ Uploaded'; statusEl.className = 'upload-status success';
                } else { statusEl.textContent = '✗ ' + data.error; }
            } catch (err) { statusEl.textContent = '✗ Upload failed'; }
        }

        function getMCQHTML(qId) {
            return `<div class="mcq-options">${[0,1,2,3].map(i => `<div class="option-item"><input type="radio" name="correct-${qId}" class="option-radio" ${i===0?'checked':''}><input type="text" class="form-input option-input" placeholder="Option ${i+1}"><button type="button" class="btn-clear sm-icon" onclick="toggleUpload(${qId},'opt-${i}')"><i class="fas fa-image"></i></button><div id="opt-upload-${qId}-${i}" class="img-upload-wrapper" style="display:none;width:100%;"><input type="hidden" class="option-image" id="opt-img-val-${qId}-${i}"><label class="file-upload-label"><i class="fas fa-cloud-upload-alt"></i> Option Image<input type="file" accept="image/*" onchange="uploadImage(this,null,'opt-img-val-${qId}-${i}')"></label><div class="upload-status"></div></div></div>`).join('')}</div>`;
        }

        function addCustomQuestion() {
            questionCount++;
            const container = document.getElementById('questions-container');
            const qDiv = document.createElement('div');
            qDiv.className = 'question-item'; qDiv.id = `q-item-${questionCount}`;
            qDiv.innerHTML = `<div class="question-header"><div style="display:flex;gap:1rem;align-items:center;"><h4 style="margin:0;">Q${questionCount}</h4><select class="form-input sm-select q-type" onchange="toggleQuestionType(${questionCount},this.value)" style="width:180px;"><option value="mcq">MCQ</option><option value="true_false">True / False</option><option value="short_answer">Short Answer</option><option value="matching">Matching (Drag/Drop)</option><option value="ordering">Ordering (Sequence)</option></select></div><button type="button" class="btn-clear text-danger" onclick="removeQuestion(${questionCount})"><i class="fas fa-trash"></i></button></div><div class="q-main-input"><input type="text" class="form-input q-text" placeholder="Enter your question here..." style="flex:1;"><button type="button" class="btn btn-outline btn-icon-sm" title="Add Image" onclick="toggleUpload(${questionCount},'q')"><i class="fas fa-image"></i></button></div><div id="q-upload-${questionCount}" class="img-upload-wrapper" style="display:none;"><input type="hidden" class="q-image" id="q-img-val-${questionCount}"><label class="file-upload-label"><i class="fas fa-cloud-upload-alt"></i> Choose Image<input type="file" accept="image/*" onchange="uploadImage(this,'q-preview-${questionCount}','q-img-val-${questionCount}')"></label><div class="upload-status"></div><img id="q-preview-${questionCount}" src="" class="builder-img-preview" style="display:none;"></div><div id="type-container-${questionCount}" class="type-container">${getMCQHTML(questionCount)}</div>`;
            container.appendChild(qDiv);
            qDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function toggleQuestionType(qId, type) {
            const container = document.getElementById(`type-container-${qId}`);
            if (type === 'mcq') { container.innerHTML = getMCQHTML(qId); }
            else if (type === 'true_false') {
                container.innerHTML = `<div class="tf-options"><label><input type="radio" name="tf-${qId}" class="tf-radio" value="true" checked> ✅ True</label><label><input type="radio" name="tf-${qId}" class="tf-radio" value="false"> ❌ False</label></div><p class="text-muted" style="font-size:0.8rem;margin-top:0.5rem;">Select the correct answer.</p>`;
            } else if (type === 'short_answer') {
                container.innerHTML = `<div style="margin-top:0.5rem;"><input type="text" class="form-input correct-answer" placeholder="Expected correct answer (for auto-grading)"><p class="text-muted" style="font-size:0.8rem;margin-top:0.3rem;">Students type their answer. Leave blank for manual grading.</p></div>`;
            } else if (type === 'matching') {
                container.innerHTML = `<p class="text-muted" style="font-size:0.8rem;margin-bottom:0.5rem;">Add matching pairs. Students drag targets to correct prompts.</p><div class="matching-pairs" id="matching-pairs-${qId}">${[0,1].map(()=>`<div class="pair-item"><input type="text" class="form-input pair-prompt" placeholder="Prompt" style="flex:1;"><i class="fas fa-arrows-alt-h" style="padding-top:10px;"></i><input type="text" class="form-input pair-target" placeholder="Target" style="flex:1;"></div>`).join('')}</div><button type="button" class="btn btn-outline btn-sm" onclick="addMatchingPair(${qId})"><i class="fas fa-plus"></i> Add Pair</button>`;
            } else if (type === 'ordering') {
                container.innerHTML = `<p class="text-muted" style="font-size:0.8rem;margin-bottom:0.5rem;">Enter items in correct order. Students will reorder them.</p><div class="ordering-items" id="ordering-items-${qId}">${[1,2,3].map(i=>`<div class="order-item"><span style="color:var(--text-muted);">${i}.</span><input type="text" class="form-input order-text" placeholder="Item ${i}" style="flex:1;"><button type="button" class="btn-clear text-danger" onclick="this.parentElement.remove()">&times;</button></div>`).join('')}</div><button type="button" class="btn btn-outline btn-sm" onclick="addOrderItem(${qId})"><i class="fas fa-plus"></i> Add Item</button>`;
            }
        }

        function addMatchingPair(qId) {
            const c = document.getElementById(`matching-pairs-${qId}`);
            const d = document.createElement('div'); d.className = 'pair-item';
            d.innerHTML = `<input type="text" class="form-input pair-prompt" placeholder="Prompt" style="flex:1;"><i class="fas fa-arrows-alt-h" style="padding-top:10px;"></i><input type="text" class="form-input pair-target" placeholder="Target" style="flex:1;"><button type="button" class="btn-clear text-danger" onclick="this.parentElement.remove()">&times;</button>`;
            c.appendChild(d);
        }

        function addOrderItem(qId) {
            const c = document.getElementById(`ordering-items-${qId}`);
            const n = c.children.length + 1;
            const d = document.createElement('div'); d.className = 'order-item';
            d.innerHTML = `<span style="color:var(--text-muted);">${n}.</span><input type="text" class="form-input order-text" placeholder="Item ${n}" style="flex:1;"><button type="button" class="btn-clear text-danger" onclick="this.parentElement.remove()">&times;</button>`;
            c.appendChild(d);
        }

        function toggleUpload(qId, suffix) {
            const id = suffix === 'q' ? `q-upload-${qId}` : `opt-upload-${qId}-${suffix.split('-')[1]}`;
            const el = document.getElementById(id);
            el.style.display = el.style.display === 'none' ? 'block' : 'none';
        }

        function removeQuestion(id) { const item = document.getElementById(`q-item-${id}`); if (item) item.remove(); }

        async function saveExam() {
            const title = document.getElementById('builder-exam-title').value;
            const desc = document.getElementById('builder-exam-desc').value;
            if (!title) { alert('Please enter an exam title.'); return; }

            const questions = [];
            document.querySelectorAll('.question-item').forEach(item => {
                const type = item.querySelector('.q-type').value;
                const qText = item.querySelector('.q-text').value;
                const qImgEl = item.querySelector('.q-image');
                const qImg = qImgEl ? qImgEl.value : '';

                if (type === 'mcq') {
                    const options = []; let correctIdx = 0;
                    item.querySelectorAll('.option-item').forEach((opt, idx) => {
                        const val = opt.querySelector('.option-input').value;
                        const optImgEl = opt.querySelector('.option-image');
                        const optImg = optImgEl ? optImgEl.value : '';
                        const isCorrect = opt.querySelector('.option-radio').checked;
                        if (val || optImg) { options.push({ text: val, image: optImg }); if (isCorrect) correctIdx = options.length - 1; }
                    });
                    if (qText && options.length > 0) questions.push({ label: qText, image: qImg, type: 'mcq', options, correct: correctIdx });
                } else if (type === 'true_false') {
                    const checked = item.querySelector('.tf-radio:checked');
                    if (qText) questions.push({ label: qText, image: qImg, type: 'true_false', correct: checked ? checked.value : 'true' });
                } else if (type === 'short_answer') {
                    const el = item.querySelector('.correct-answer');
                    if (qText) questions.push({ label: qText, image: qImg, type: 'short_answer', correct: el ? el.value : '' });
                } else if (type === 'matching') {
                    const pairs = [];
                    item.querySelectorAll('.pair-item').forEach(p => {
                        const pr = p.querySelector('.pair-prompt').value, ta = p.querySelector('.pair-target').value;
                        if (pr && ta) pairs.push({ prompt: pr, target: ta });
                    });
                    if (qText && pairs.length > 0) questions.push({ label: qText, image: qImg, type: 'matching', pairs });
                } else if (type === 'ordering') {
                    const items = [];
                    item.querySelectorAll('.order-text').forEach(el => { if (el.value) items.push(el.value); });
                    if (qText && items.length > 0) questions.push({ label: qText, image: qImg, type: 'ordering', items });
                }
            });

            if (questions.length === 0) { alert('Please add at least one question.'); return; }

            const fd = new FormData();
            if (window.editingExamId) {
                fd.append('action', 'update_form');
                fd.append('id', window.editingExamId);
            } else {
                fd.append('action', 'create_form');
            }
            fd.append('title', title);
            fd.append('description', desc);
            fd.append('fields_json', JSON.stringify(questions));
            fd.append('type', 'exam');
            fd.append('is_ajax', '1');

            try {
                const res = await fetch('dashboard.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    if (!window.editingExamId) {
                        const sel = document.getElementById('extra-form-id');
                        if (sel) {
                            const opt = document.createElement('option');
                            opt.value = data.id; opt.text = title; opt.selected = true; sel.add(opt);
                        }
                    }
                    alert(window.editingExamId ? 'Exam updated successfully!' : 'Exam created and selected successfully!');
                    closeExamBuilder();
                    if(window.editingExamId) window.location.reload();
                } else { alert('Error: ' + data.error); }
            } catch (err) { console.error(err); alert('Failed to save exam.'); }
        }

        function editExamCall(id, title, desc, fields_json) {
            window.editingExamId = id;
            document.getElementById('examBuilderModal').style.display = 'flex';
            document.getElementById('builder-exam-title').value = title || '';
            document.getElementById('builder-exam-desc').value = desc || '';
            document.getElementById('questions-container').innerHTML = '';
            questionCount = 0;

            if (fields_json && Array.isArray(fields_json) && fields_json.length > 0) {
                fields_json.forEach(q => {
                    addCustomQuestion();
                    const qItem = document.getElementById(`q-item-${questionCount}`);
                    qItem.querySelector('.q-type').value = q.type;
                    qItem.querySelector('.q-text').value = q.label || '';
                    if (q.image) {
                        qItem.querySelector('.q-image').value = q.image;
                        const preview = document.getElementById(`q-preview-${questionCount}`);
                        preview.src = q.image; preview.style.display = 'block';
                        document.getElementById(`q-upload-${questionCount}`).style.display = 'block';
                    }
                    toggleQuestionType(questionCount, q.type); // Trigger UI rebuild for type

                    setTimeout(() => {
                        const container = document.getElementById(`type-container-${questionCount}`);
                        if (q.type === 'mcq' && q.options) {
                            q.options.forEach((opt, idx) => {
                                if (idx > 3) return; // UI only supports 4 right now easily
                                const optItem = container.querySelectorAll('.option-item')[idx];
                                if(optItem) {
                                    optItem.querySelector('.option-input').value = opt.text || '';
                                    if (opt.image) {
                                        optItem.querySelector('.option-image').value = opt.image;
                                        optItem.querySelector('.upload-status').textContent = 'Loaded';
                                        document.getElementById(`opt-upload-${questionCount}-${idx}`).style.display = 'block';
                                    }
                                    if (q.correct === idx) optItem.querySelector('.option-radio').checked = true;
                                }
                            });
                        } else if (q.type === 'true_false') {
                            const radios = container.querySelectorAll('.tf-radio');
                            radios.forEach(r => { if(r.value === q.correct) r.checked = true; });
                        } else if (q.type === 'short_answer') {
                            container.querySelector('.correct-answer').value = q.correct || '';
                        } else if (q.type === 'matching' && q.pairs) {
                            container.querySelector('.matching-pairs').innerHTML = ''; // clear default 2
                            q.pairs.forEach(p => {
                                addMatchingPair(questionCount);
                                const rows = container.querySelectorAll('.pair-item');
                                const lastRow = rows[rows.length - 1];
                                lastRow.querySelector('.pair-prompt').value = p.prompt || '';
                                lastRow.querySelector('.pair-target').value = p.target || '';
                            });
                        } else if (q.type === 'ordering' && q.items) {
                            container.querySelector('.ordering-items').innerHTML = ''; // clear default 3
                            q.items.forEach(itm => {
                                addOrderItem(questionCount);
                                const rows = container.querySelectorAll('.order-item');
                                const lastRow = rows[rows.length - 1];
                                lastRow.querySelector('.order-text').value = itm || '';
                            });
                        }
                    }, 50);
                });
            } else {
                addCustomQuestion();
            }
        }
    </script>

</body>

</html>
