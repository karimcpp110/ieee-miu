-- IEEE MIU Database Setup for MySQL (InfinityFree)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- 1. Courses Table
CREATE TABLE IF NOT EXISTS `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `instructor` varchar(255),
  `duration` varchar(100),
  `thumbnail` varchar(500) DEFAULT 'https://via.placeholder.com/300x200',
  `content` text,
  `file_path` varchar(500),
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Users Table (Admins)
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) UNIQUE NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.1 Members Table (Club Registration)
CREATE TABLE IF NOT EXISTS `members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `student_id` varchar(100),
  `department` varchar(100),
  `registered_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Students Table
CREATE TABLE IF NOT EXISTS `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) UNIQUE NOT NULL,
  `password` varchar(255) NOT NULL,
  `student_id` varchar(100),
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Enrollments Table
CREATE TABLE IF NOT EXISTS `enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11),
  `student_name` varchar(255) NOT NULL,
  `student_contact` varchar(255) NOT NULL,
  `student_account_id` int(11) DEFAULT NULL,
  `enrolled_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`course_id`),
  INDEX (`student_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Course Extras
CREATE TABLE IF NOT EXISTS `course_extras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `content` text,
  `file_path` varchar(500),
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Site Settings
CREATE TABLE IF NOT EXISTS `site_settings` (
  `key_name` varchar(100) NOT NULL,
  `value` text,
  PRIMARY KEY (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Board Members
CREATE TABLE IF NOT EXISTS `board_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `photo_url` varchar(500) DEFAULT 'https://via.placeholder.com/150',
  `committee` varchar(100) DEFAULT 'Board',
  `bio` text,
  `linkedin_url` varchar(500),
  `is_best` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Events Table
CREATE TABLE IF NOT EXISTS `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `event_date` datetime,
  `image_path` varchar(500) DEFAULT 'https://via.placeholder.com/300x200',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Forms Table
CREATE TABLE IF NOT EXISTS `forms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `fields_json` text NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Submissions Table
CREATE TABLE IF NOT EXISTS `submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `form_id` int(11),
  `data_json` text NOT NULL,
  `submitted_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`form_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Event Gallery
CREATE TABLE IF NOT EXISTS `event_gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SEED DATA
INSERT INTO `users` (`username`, `password`) VALUES ('admin', 'password123');

INSERT INTO `site_settings` (`key_name`, `value`) VALUES 
('home_about', '<h3>About IEEE MIU</h3><p>We are a student branch dedicated to advancing technology and fostering innovation among students. Join us to learn, build, and grow.</p>'),
('home_board_intro', '<h3>Meet the Board</h3><p>Our dedicated leadership team.</p>'),
('home_goals', '<h3>Our Goals</h3><ul><li>Provide high-quality technical workshops.</li><li>Build a strong community of engineers.</li><li>Participate in global complications.</li></ul>'),
('hero_badge', 'IEEE MIU Student Branch'),
('hero_title', 'Innovating the <span class="text-gradient">Future</span> Together'),
('hero_subtitle', 'Join a community of passionate engineers, creators, and innovators. Master new skills through our specialized tracks and hands-on workshops.'),
('header_events', 'Upcoming <span class="text-gradient">Events</span>'),
('header_registrations', 'Open <span class="text-gradient">Registrations</span>'),
('header_leadership', 'Club <span class="text-gradient">Leadership</span>'),
('header_join', 'Join the Club');

INSERT INTO `board_members` (`name`, `role`, `photo_url`) VALUES 
('John Sameh', 'Chairman', 'https://via.placeholder.com/150'),
('Khaled Ashraf', 'Vice Chairman', 'https://via.placeholder.com/150'),
('Seif Mohamed', 'Vice Chairman', 'https://via.placeholder.com/150'),
('Karim Wael', 'Co-Head R&D', 'https://via.placeholder.com/150');
