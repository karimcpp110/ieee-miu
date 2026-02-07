<?php

class Database {
    private $pdo;

    public function __construct() {
        // 1. Check for DATABASE_URL (for modern hosts)
        $dbUrl = getenv('DATABASE_URL');
        
        // 2. Manual Config (EASY FOR INFINITYFREE)
        // If you are on InfinityFree, fill these in and the site will use them!
        $manual_host = ''; // 'fdb1033.awardspace.net'; // Commented out for local run
        $manual_user = '4726137_db';
        $manual_pass = 'gTQi92@bXqaPwV7'; // USER NEEDS TO FILL THIS
        $manual_db   = '4726137_db';

        if ($manual_host) {
            $dsn = "mysql:host=$manual_host;dbname=$manual_db;charset=utf8mb4";
            try {
                $this->pdo = new PDO($dsn, $manual_user, $manual_pass);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                die("Manual MySQL Connection failed: " . $e->getMessage());
            }
        } elseif ($dbUrl) {
            $url = parse_url($dbUrl);
            $scheme = $url["scheme"] ?? 'pgsql';
            
            $host = $url["host"];
            $port = $url["port"] ?? ($scheme === 'pgsql' ? 5432 : 3306);
            $user = $url["user"];
            $pass = $url["pass"];
            $path = ltrim($url["path"], "/");

            if ($scheme === 'pgsql' || $scheme === 'postgres') {
                $dsn = "pgsql:host=$host;port=$port;dbname=$path";
            } else {
                $dsn = "mysql:host=$host;port=$port;dbname=$path;charset=utf8mb4";
            }

            try {
                $this->pdo = new PDO($dsn, $user, $pass);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                die("Database Connection failed ($scheme): " . $e->getMessage());
            }
        } else {
            // Local SQLite fallback
            $dbPath = __DIR__ . '/courses.db';
            try {
                $this->pdo = new PDO("sqlite:" . $dbPath);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                die("SQLite Connection failed: " . $e->getMessage());
            }
        }
        $this->initialize();
    }

    private function initialize() {
        // Courses Table
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS courses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT,
            instructor TEXT,
            duration TEXT,
            thumbnail TEXT DEFAULT 'https://via.placeholder.com/300x200',
            content TEXT, -- Instruction override
            file_path TEXT, -- Link to PDF/Doc
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Users Table (Admins)
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL
        )");

        // Members Table (Club Registration)
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS members (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            email TEXT NOT NULL,
            student_id TEXT,
            department TEXT,
            registered_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Students Table (Student Accounts)
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS students (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            student_id TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Enrollments Table (Course Signups)
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS enrollments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            course_id INTEGER,
            student_name TEXT NOT NULL,
            student_contact TEXT NOT NULL,
            student_account_id INTEGER DEFAULT NULL,
            enrolled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(course_id) REFERENCES courses(id),
            FOREIGN KEY(student_account_id) REFERENCES students(id)
        )");

        // Course Extras (Multiple Materials/Records)
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS course_extras (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            course_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            type TEXT NOT NULL, -- 'material' or 'record'
            content TEXT,
            file_path TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(course_id) REFERENCES courses(id)
        )");

        // Site Settings Table (Dynamic Content)
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
            key TEXT PRIMARY KEY,
            value TEXT
        )");

        // Board Members Table
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS board_members (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            role TEXT NOT NULL,
            photo_url TEXT DEFAULT 'https://via.placeholder.com/150',
            committee TEXT DEFAULT 'Board'
        )");

        // Events Table
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT,
            event_date DATETIME,
            image_path TEXT DEFAULT 'https://via.placeholder.com/300x200',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Forms Table (Dynamic Forms)
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS forms (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT,
            fields_json TEXT NOT NULL, -- JSON array of field definitions
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Submissions Table
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS submissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            form_id INTEGER,
            data_json TEXT NOT NULL, -- JSON object of submitted data
            submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(form_id) REFERENCES forms(id)
        )");

        // Seed Board Members
        $boardCount = $this->pdo->query("SELECT COUNT(*) FROM board_members")->fetchColumn();
        if ($boardCount == 0) {
            $members = [
                ['John Sameh', 'Chairman', 'https://via.placeholder.com/150'],
                ['Khaled Ashraf', 'Vice Chairman', 'https://via.placeholder.com/150'],
                ['Seif Mohamed', 'Vice Chairman', 'https://via.placeholder.com/150'],
                ['Karim Wael', 'Co-Head R&D', 'https://via.placeholder.com/150']
            ];
            $stmt = $this->pdo->prepare("INSERT INTO board_members (name, role, photo_url) VALUES (?, ?, ?)");
            foreach ($members as $m) {
                $stmt->execute($m);
            }
        }

        // Seed Site Content
        $defaults = [
            'home_about' => '<h3>About IEEE MIU</h3><p>We are a student branch dedicated to advancing technology and fostering innovation among students. Join us to learn, build, and grow.</p>',
            'home_board_intro' => '<h3>Meet the Board</h3><p>Our dedicated leadership team.</p>',
            'home_goals' => '<h3>Our Goals</h3><ul><li>Provide high-quality technical workshops.</li><li>Build a strong community of engineers.</li><li>Participate in global complications.</li></ul>',
            'hero_badge' => 'IEEE MIU Student Branch',
            'hero_title' => 'Innovating the <span class="text-gradient">Future</span> Together',
            'hero_subtitle' => 'Join a community of passionate engineers, creators, and innovators. Master new skills through our specialized tracks and hands-on workshops.',
            'header_events' => 'Upcoming <span class="text-gradient">Events</span>',
            'header_registrations' => 'Open <span class="text-gradient">Registrations</span>',
            'header_leadership' => 'Club <span class="text-gradient">Leadership</span>',
            'header_join' => 'Join the Club'
        ];
        
        $stmt = $this->pdo->prepare("INSERT OR IGNORE INTO site_settings (key, value) VALUES (?, ?)");
        foreach ($defaults as $k => $v) {
            $stmt->execute([$k, $v]);
        }

        // Seed Admin Account (admin / password123)
        $adminCount = $this->pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'")->fetchColumn();
        if ($adminCount == 0) {
            // Using simple hash for demo purposes; use password_hash() in production
            $stmt = $this->pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->execute(['admin', 'password123']); 
        }

        // Seed default courses
        $count = $this->pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
        if ($count == 0) {
            $courses = [
                [
                    'Arduino Mastery', 
                    'Master the basics of electronics and coding with Arduino.',
                    'Eng. Ahmed Ali',
                    '15 Hours',
                    'https://images.unsplash.com/photo-1555662703-9d10e0604245?auto=format&fit=crop&w=400&q=80',
                    '<h2>Welcome to Arduino Mastery</h2><p>Here you will find all the resources needed for this track.</p><ul><li>Lecture 1 Slides</li><li>Datasheets</li></ul>'
                ],
                [
                    'Python for Everyone', 
                    'From Hello World to Data Science.',
                    'Dr. Sarah Smith',
                    '20 Hours',
                    'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?auto=format&fit=crop&w=400&q=80',
                    '<h2>Python Track Resources</h2><p>Please download Python 3.9+ before the first session.</p>'
                ]
            ];

            $insert = "INSERT INTO courses (title, description, instructor, duration, thumbnail, content) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($insert);
            foreach ($courses as $c) {
                $stmt->execute($c);
            }
        }
    }

    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
}
