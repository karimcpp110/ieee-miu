<?php
require_once 'Database.php';

$db = new Database();

// SQL for table creation (PostgreSQL and SQLite compatible where possible)
// Note: We use SERIAL for Postgres but SQLite handles it differently.
// For simplicity, we check the driver and adjust.

$dbType = getenv('DATABASE_URL') ? 'pgsql' : 'sqlite';

$queries = [
    // Members (Club Registration)
    "CREATE TABLE IF NOT EXISTS members (
        id " . ($dbType == 'pgsql' ? 'SERIAL' : 'INTEGER') . " PRIMARY KEY " . ($dbType == 'sqlite' ? 'AUTOINCREMENT' : '') . ",
        full_name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        student_id TEXT NOT NULL,
        track TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Students (Web Accounts)
    "CREATE TABLE IF NOT EXISTS students (
        id " . ($dbType == 'pgsql' ? 'SERIAL' : 'INTEGER') . " PRIMARY KEY " . ($dbType == 'sqlite' ? 'AUTOINCREMENT' : '') . ",
        full_name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // Courses
    "CREATE TABLE IF NOT EXISTS courses (
        id " . ($dbType == 'pgsql' ? 'SERIAL' : 'INTEGER') . " PRIMARY KEY " . ($dbType == 'sqlite' ? 'AUTOINCREMENT' : '') . ",
        title TEXT NOT NULL,
        description TEXT,
        instructor TEXT,
        duration TEXT,
        thumbnail TEXT,
        content TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // Enrollments
    "CREATE TABLE IF NOT EXISTS enrollments (
        id " . ($dbType == 'pgsql' ? 'SERIAL' : 'INTEGER') . " PRIMARY KEY " . ($dbType == 'sqlite' ? 'AUTOINCREMENT' : '') . ",
        course_id INTEGER NOT NULL,
        student_name TEXT,
        student_contact TEXT,
        enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // Course Resources
    "CREATE TABLE IF NOT EXISTS course_resources (
        id " . ($dbType == 'pgsql' ? 'SERIAL' : 'INTEGER') . " PRIMARY KEY " . ($dbType == 'sqlite' ? 'AUTOINCREMENT' : '') . ",
        course_id INTEGER NOT NULL,
        file_name TEXT NOT NULL,
        file_path TEXT NOT NULL,
        file_type TEXT NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // Events
    "CREATE TABLE IF NOT EXISTS events (
        id " . ($dbType == 'pgsql' ? 'SERIAL' : 'INTEGER') . " PRIMARY KEY " . ($dbType == 'sqlite' ? 'AUTOINCREMENT' : '') . ",
        title TEXT NOT NULL,
        description TEXT,
        event_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // Board Members
    "CREATE TABLE IF NOT EXISTS board_members (
        id " . ($dbType == 'pgsql' ? 'SERIAL' : 'INTEGER') . " PRIMARY KEY " . ($dbType == 'sqlite' ? 'AUTOINCREMENT' : '') . ",
        name TEXT NOT NULL,
        position TEXT NOT NULL,
        photo TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // Site Settings
    "CREATE TABLE IF NOT EXISTS site_settings (
        id " . ($dbType == 'pgsql' ? 'SERIAL' : 'INTEGER') . " PRIMARY KEY " . ($dbType == 'sqlite' ? 'AUTOINCREMENT' : '') . ",
        setting_key TEXT UNIQUE NOT NULL,
        setting_value TEXT
    )",

    // Forms (Dynamic Builder)
    "CREATE TABLE IF NOT EXISTS forms (
        id " . ($dbType == 'pgsql' ? 'SERIAL' : 'INTEGER') . " PRIMARY KEY " . ($dbType == 'sqlite' ? 'AUTOINCREMENT' : '') . ",
        title TEXT NOT NULL,
        description TEXT,
        fields_json TEXT, -- JSON as text
        is_active INTEGER DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // Submissions
    "CREATE TABLE IF NOT EXISTS submissions (
        id " . ($dbType == 'pgsql' ? 'SERIAL' : 'INTEGER') . " PRIMARY KEY " . ($dbType == 'sqlite' ? 'AUTOINCREMENT' : '') . ",
        form_id INTEGER NOT NULL,
        data_json TEXT, -- JSON as text
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($queries as $sql) {
    try {
        $db->query($sql);
        echo "Executed: " . substr($sql, 0, 50) . "...\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// Initial Data for Settings
$settings = [
    ['about_section', 'IEEE MIU is a student branch part of IEEE Egypt Section, representing MIU university.'],
    ['goals_section', 'Empowering students with technical skills and professional networking.'],
    ['board_intro', 'Meet the creative minds behind IEEE MIU activities this year.']
];

foreach ($settings as $s) {
    try {
        $db->query("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON CONFLICT(setting_key) DO NOTHING", $s);
    } catch (Exception $e) {
        // Ignore errors if ON CONFLICT fails/is unsupported
    }
}

echo "Database Migration Complete.\n";
