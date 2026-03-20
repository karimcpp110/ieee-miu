<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'Database.php';

echo "<style>
    body { font-family: sans-serif; line-height: 1.5; padding: 2rem; background: #f4f7f9; }
    .box { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; }
    .status { padding: 10px; border-radius: 8px; margin-bottom: 10px; }
    .success { background: #e6ffed; color: #22863a; border: 1px solid #34d058; }
    .info { background: #f1f8ff; color: #0366d6; border: 1px solid #c8e1ff; }
    .error { background: #ffeef0; color: #cb2431; border: 1px solid #f97583; }
</style>";

echo "<div class='box'>";
echo "<h2>🛠️ IEEE MIU Database Repair Tool (v2)</h2>";

try {
    $db = new Database();
    echo "<div class='status info'>CONNECTED to database successfully.</div>";

    // 0. Check foundational tables (students, enrollments)
    echo "<h3>0. Checking foundational tables...</h3>";

    $tableCheck = $db->query("SHOW TABLES LIKE 'students'");
    if (!$tableCheck->fetch()) {
        echo "<p>Table 'students' is MISSING. Creating it now...</p>";
        $db->query("CREATE TABLE students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            student_id VARCHAR(100),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        echo "<div class='status success'>SUCCESS: 'students' table created.</div>";
    }

    $tableCheck = $db->query("SHOW TABLES LIKE 'enrollments'");
    if (!$tableCheck->fetch()) {
        echo "<p>Table 'enrollments' is MISSING. Creating it now...</p>";
        $db->query("CREATE TABLE enrollments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT,
            student_name VARCHAR(255) NOT NULL,
            student_contact VARCHAR(255) NOT NULL,
            student_account_id INT DEFAULT NULL,
            enrolled_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        echo "<div class='status success'>SUCCESS: 'enrollments' table created.</div>";
    }

    // 1. Check events table category column
    echo "<h3>1. Checking 'events' table...</h3>";
    $res = $db->query("DESCRIBE events");
    $columns = $res->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('category', $columns)) {
        echo "<p>Column 'category' is MISSING. Adding it now...</p>";
        $db->query("ALTER TABLE events ADD COLUMN category VARCHAR(50) DEFAULT 'General' AFTER title");
        echo "<div class='status success'>SUCCESS: 'category' column added to 'events' table.</div>";
    } else {
        echo "<div class='status info'>OK: 'category' column already exists in 'events' table.</div>";
    }

    // 2. Check event_gallery table
    echo "<h3>2. Checking 'event_gallery' table...</h3>";
    $tableCheck = $db->query("SHOW TABLES LIKE 'event_gallery'");
    if (!$tableCheck->fetch()) {
        echo "<p>Table 'event_gallery' is MISSING. Creating it now...</p>";
        $db->query("CREATE TABLE event_gallery (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "<div class='status success'>SUCCESS: 'event_gallery' table created.</div>";
    } else {
        echo "<div class='status info'>OK: 'event_gallery' table already exists.</div>";
    }

    // 3. Check gallery_sections table
    echo "<h3>3. Checking 'gallery_sections' table...</h3>";
    $tableCheck = $db->query("SHOW TABLES LIKE 'gallery_sections'");
    if (!$tableCheck->fetch()) {
        echo "<p>Table 'gallery_sections' is MISSING. Creating it now...</p>";
        $db->query("CREATE TABLE gallery_sections (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "<div class='status success'>SUCCESS: 'gallery_sections' table created.</div>";
    } else {
        echo "<div class='status info'>OK: 'gallery_sections' table already exists.</div>";
    }

    // 4. Check gallery_photos table
    echo "<h3>4. Checking 'gallery_photos' table...</h3>";
    $tableCheck = $db->query("SHOW TABLES LIKE 'gallery_photos'");
    if (!$tableCheck->fetch()) {
        echo "<p>Table 'gallery_photos' is MISSING. Creating it now...</p>";
        $db->query("CREATE TABLE gallery_photos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            section_id INT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "<div class='status success'>SUCCESS: 'gallery_photos' table created.</div>";
    } else {
        echo "<div class='status info'>OK: 'gallery_photos' table already exists.</div>";
    }

    // 5. Check submissions table extras
    echo "<h3>5. Checking 'submissions' table for new features...</h3>";
    $res = $db->query("DESCRIBE submissions");
    $columns = $res->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('status', $columns)) {
        echo "<p>Column 'status' is MISSING. Adding it now...</p>";
        $db->query("ALTER TABLE submissions ADD COLUMN status VARCHAR(50) DEFAULT 'Pending' AFTER submitted_at");
        echo "<div class='status success'>SUCCESS: 'status' column added.</div>";
    } else {
        echo "<div class='status info'>OK: 'status' column already exists.</div>";
    }

    if (!in_array('admin_notes', $columns)) {
        echo "<p>Column 'admin_notes' is MISSING. Adding it now...</p>";
        $db->query("ALTER TABLE submissions ADD COLUMN admin_notes TEXT AFTER status");
        echo "<div class='status success'>SUCCESS: 'admin_notes' column added.</div>";
    } else {
        echo "<div class='status info'>OK: 'admin_notes' column already exists.</div>";
    }

    if (!in_array('student_id', $columns)) {
        echo "<p>Column 'student_id' is MISSING in submissions. Adding it now...</p>";
        $db->query("ALTER TABLE submissions ADD COLUMN student_id INT DEFAULT NULL AFTER admin_notes");
        echo "<div class='status success'>SUCCESS: 'student_id' column added.</div>";
    }

    // 6. Check users table for RBAC and Password Reset
    echo "<h3>6. Checking 'users' table for RBAC & Security...</h3>";
    $res = $db->query("DESCRIBE users");
    $userCols = $res->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('role', $userCols)) {
        echo "<p>Column 'role' is MISSING. Adding it now...</p>";
        $db->query("ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'Admin' AFTER password");
        echo "<div class='status success'>SUCCESS: 'role' column added.</div>";
    } else {
        echo "<div class='status info'>OK: 'role' column already exists.</div>";
    }

    if (!in_array('email', $userCols)) {
        echo "<p>Column 'email' is MISSING. Adding it now...</p>";
        $db->query("ALTER TABLE users ADD COLUMN email VARCHAR(255) DEFAULT NULL AFTER role");
        echo "<div class='status success'>SUCCESS: 'email' column added.</div>";
    } else {
        echo "<div class='status info'>OK: 'email' column already exists.</div>";
    }

    if (!in_array('reset_token', $userCols)) {
        echo "<p>Column 'reset_token' is MISSING. Adding it now...</p>";
        $db->query("ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) DEFAULT NULL, ADD COLUMN reset_expires DATETIME DEFAULT NULL");
        echo "<div class='status success'>SUCCESS: Security columns added.</div>";
    } else {
        echo "<div class='status info'>OK: 'security' columns already exist.</div>";
    }

    // 7. Check courses table
    echo "<h3>7. Checking 'courses' table...</h3>";
    $tableCheck = $db->query("SHOW TABLES LIKE 'courses'");
    if (!$tableCheck->fetch()) {
        echo "<p>Table 'courses' is MISSING. Creating it now...</p>";
        $db->query("CREATE TABLE courses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            instructor VARCHAR(255),
            duration VARCHAR(50),
            thumbnail VARCHAR(255),
            content LONGTEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "<div class='status success'>SUCCESS: 'courses' table created.</div>";
    } else {
        $res = $db->query("DESCRIBE courses");
        $cols = $res->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('content', $cols)) {
            echo "<p>Column 'content' is MISSING in 'courses'. Adding it now...</p>";
            $db->query("ALTER TABLE courses ADD COLUMN content LONGTEXT AFTER thumbnail");
            echo "<div class='status success'>SUCCESS: 'content' column added.</div>";
        }
        echo "<div class='status info'>OK: 'courses' table is ready.</div>";
    }

    // 8. Check course_extras table
    echo "<h3>8. Checking 'course_extras' table...</h3>";
    $tableCheck = $db->query("SHOW TABLES LIKE 'course_extras'");
    if (!$tableCheck->fetch()) {
        echo "<p>Table 'course_extras' is MISSING. Creating it now...</p>";
        $db->query("CREATE TABLE course_extras (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT NOT NULL,
            title VARCHAR(255),
            type VARCHAR(50),
            content TEXT,
            file_path VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "<div class='status success'>SUCCESS: 'course_extras' table created.</div>";
    } else {
        echo "<div class='status info'>OK: 'course_extras' table already exists.</div>";
    }

    echo "<hr>";
    echo "<h3 style='color:blue'>✅ Repair Complete!</h3>";
    echo "<p>Please try to access your dashboard. If it works, you can <strong>delete this db_repair.php file</strong> from your server.</p>";

} catch (Exception $e) {
    // Create Activity Logs table
    $db->query("CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        username VARCHAR(255),
        action VARCHAR(255) NOT NULL,
        details TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $db->query("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        username VARCHAR(255) NOT NULL,
        attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (ip_address, attempt_time)
    )");

    // Create Analytics tables
    $db->query("CREATE TABLE IF NOT EXISTS page_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page_name VARCHAR(255),
        user_id INT,
        viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $db->query("CREATE TABLE IF NOT EXISTS downloads_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT,
        resource_id INT,
        user_id INT,
        downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $db->query("CREATE TABLE IF NOT EXISTS user_stats (
        user_id INT PRIMARY KEY,
        courses_completed INT DEFAULT 0,
        events_attended INT DEFAULT 0,
        last_login TIMESTAMP NULL
    )");

    // Create RBAC tables
    $db->query("CREATE TABLE IF NOT EXISTS roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) UNIQUE NOT NULL,
        description TEXT
    )");

    $db->query("CREATE TABLE IF NOT EXISTS permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(50) UNIQUE NOT NULL,
        name VARCHAR(100) NOT NULL
    )");

    $db->query("CREATE TABLE IF NOT EXISTS role_permissions (
        role_id INT,
        permission_id INT,
        PRIMARY KEY (role_id, permission_id)
    )");

    // Seed default roles if they don't exist
    $db->query("INSERT IGNORE INTO roles (name, description) VALUES 
        ('Admin', 'Full system access'),
        ('HR', 'Human Resources & Registrations Management'),
        ('Instructor', 'Course & LMS Management')");

    // Seed permissions
    $permissions = [
        ['manage_registrations', 'Manage Registrations'],
        ['manage_board', 'Manage Board Members'],
        ['manage_events', 'Manage Events'],
        ['manage_forms', 'Manage Dynamic Forms'],
        ['manage_courses', 'Manage LMS / Courses'],
        ['manage_content', 'Manage Site Content'],
        ['manage_audit_logs', 'View Audit Logs'],
        ['view_analytics', 'View Analytics Dashboard']
    ];

    foreach ($permissions as $p) {
        $db->query("INSERT IGNORE INTO permissions (slug, name) VALUES (?, ?)", [$p[0], $p[1]]);
    }

    // Link Admin to all
    $db->query("INSERT IGNORE INTO role_permissions (role_id, permission_id) 
                SELECT (SELECT id FROM roles WHERE name='Admin'), id FROM permissions");

    // Link HR to registrations and forms
    $db->query("INSERT IGNORE INTO role_permissions (role_id, permission_id) 
                SELECT (SELECT id FROM roles WHERE name='HR'), id FROM permissions 
                WHERE slug IN ('manage_registrations', 'manage_forms')");

    // Link Instructor to courses
    $db->query("INSERT IGNORE INTO role_permissions (role_id, permission_id) 
                SELECT (SELECT id FROM roles WHERE name='Instructor'), id FROM permissions 
                WHERE slug IN ('manage_courses')");

    // Create Gamification tables
    $db->query("CREATE TABLE IF NOT EXISTS badges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        icon VARCHAR(255),
        requirements_type VARCHAR(50), -- e.g. 'course_id', 'total_courses'
        requirements_value INT
    )");

    $db->query("CREATE TABLE IF NOT EXISTS user_badges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        badge_id INT,
        awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_badge (user_id, badge_id)
    )");

    $db->query("CREATE TABLE IF NOT EXISTS user_course_progress (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        course_id INT,
        status ENUM('enrolled', 'completed') DEFAULT 'enrolled',
        completed_at TIMESTAMP NULL,
        UNIQUE KEY user_course (user_id, course_id)
    )");

    // Phase 4: Communication Tables
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

    $db->query("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL, -- NULL for global notifications
        student_id INT NULL,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    echo "<h3>Check Complete!</h3>";
    echo "<div class='status error'><strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p>Double-check your <code>Database.php</code> credentials.</p>";
}

echo "</div>";
?>