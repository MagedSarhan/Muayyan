-- =====================================================
-- AALMAS – Academic Assessment Load & Performance Analysis System
-- Database Schema + Demo Data
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

DROP DATABASE IF EXISTS `aalmas_db`;
CREATE DATABASE `aalmas_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `aalmas_db`;

-- =====================================================
-- TABLE: users
-- =====================================================
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` VARCHAR(20) NOT NULL UNIQUE COMMENT 'University ID',
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','faculty','advisor','student') NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_role` (`role`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: password_resets
-- =====================================================
CREATE TABLE `password_resets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_token` (`token`)
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: courses
-- =====================================================
CREATE TABLE `courses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `credit_hours` INT NOT NULL DEFAULT 3,
  `department` VARCHAR(100) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: sections
-- =====================================================
CREATE TABLE `sections` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT NOT NULL,
  `section_number` VARCHAR(10) NOT NULL,
  `faculty_id` INT NOT NULL,
  `semester` VARCHAR(20) NOT NULL COMMENT 'e.g. Fall 2025',
  `academic_year` VARCHAR(9) NOT NULL COMMENT 'e.g. 2025-2026',
  `max_students` INT DEFAULT 40,
  `schedule` VARCHAR(100) DEFAULT NULL COMMENT 'e.g. Sun-Tue 10:00-11:30',
  `room` VARCHAR(50) DEFAULT NULL,
  `status` ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`faculty_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_semester` (`semester`),
  INDEX `idx_faculty` (`faculty_id`)
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: section_students
-- =====================================================
CREATE TABLE `section_students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `section_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `enrolled_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_enrollment` (`section_id`, `student_id`)
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: assessments
-- =====================================================
CREATE TABLE `assessments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `section_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `type` ENUM('quiz','midterm','final','project','assignment','presentation','lab','participation') NOT NULL,
  `max_score` DECIMAL(5,2) NOT NULL,
  `weight_percentage` DECIMAL(5,2) NOT NULL COMMENT 'Weight in final grade',
  `due_date` DATE NOT NULL,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('upcoming','active','graded','cancelled') NOT NULL DEFAULT 'upcoming',
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
  INDEX `idx_due_date` (`due_date`),
  INDEX `idx_type` (`type`)
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: grades
-- =====================================================
CREATE TABLE `grades` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `assessment_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `score` DECIMAL(5,2) DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `entered_by` INT NOT NULL,
  `entered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`assessment_id`) REFERENCES `assessments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`entered_by`) REFERENCES `users`(`id`),
  UNIQUE KEY `unique_grade` (`assessment_id`, `student_id`)
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: advisor_assignments
-- =====================================================
CREATE TABLE `advisor_assignments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `advisor_id` INT NOT NULL,
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`advisor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_assignment` (`student_id`, `advisor_id`)
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: contact_requests
-- =====================================================
CREATE TABLE `contact_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `advisor_id` INT NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `priority` ENUM('normal','urgent') NOT NULL DEFAULT 'normal',
  `status` ENUM('sent','under_review','replied','closed') NOT NULL DEFAULT 'sent',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`advisor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: request_attachments
-- =====================================================
CREATE TABLE `request_attachments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `request_id` INT NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_size` INT DEFAULT NULL,
  `file_type` VARCHAR(100) DEFAULT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`request_id`) REFERENCES `contact_requests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: request_replies
-- =====================================================
CREATE TABLE `request_replies` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `request_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`request_id`) REFERENCES `contact_requests`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: notifications
-- =====================================================
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `type` ENUM('alert','grade','request','system','reminder') NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `link` VARCHAR(500) DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_read` (`user_id`, `is_read`)
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: academic_notes
-- =====================================================
CREATE TABLE `academic_notes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `author_id` INT NOT NULL,
  `note_type` ENUM('general','warning','recommendation','follow_up') NOT NULL DEFAULT 'general',
  `content` TEXT NOT NULL,
  `is_private` TINYINT(1) DEFAULT 0 COMMENT 'Visible only to faculty/advisors',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: academic_alerts
-- =====================================================
CREATE TABLE `academic_alerts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `section_id` INT DEFAULT NULL,
  `alert_type` ENUM('low_grade','high_workload','declining_trend','absence_risk','overdue_assessment') NOT NULL,
  `severity` ENUM('info','warning','danger','critical') NOT NULL DEFAULT 'info',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `is_resolved` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`) ON DELETE SET NULL,
  INDEX `idx_severity` (`severity`),
  INDEX `idx_student` (`student_id`)
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: system_settings
-- =====================================================
CREATE TABLE `system_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT DEFAULT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: activity_log
-- =====================================================
CREATE TABLE `activity_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_action` (`action`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB;


-- =====================================================
-- DEMO DATA
-- =====================================================

-- Password for all demo users: password123
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `role`, `phone`, `department`, `status`) VALUES
('ADMIN001', 'Dr. Ahmad Al-Rashid', 'admin@aalmas.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '0501234567', 'Information Technology', 'active'),
('FAC001', 'Dr. Sara Al-Mohsen', 'sara.faculty@aalmas.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty', '0509876543', 'Computer Science', 'active'),
('FAC002', 'Dr. Khalid Al-Tamimi', 'khalid.faculty@aalmas.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty', '0507654321', 'Information Technology', 'active'),
('ADV001', 'Dr. Nora Al-Harbi', 'nora.advisor@aalmas.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'advisor', '0503456789', 'Computer Science', 'active'),
('ADV002', 'Dr. Fahad Al-Otaibi', 'fahad.advisor@aalmas.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'advisor', '0502345678', 'Information Technology', 'active'),
('STU001', 'Mohammed Al-Saeed', 'mohammed.stu@aalmas.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '0551234567', 'Computer Science', 'active'),
('STU002', 'Fatimah Al-Zahrani', 'fatimah.stu@aalmas.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '0559876543', 'Computer Science', 'active'),
('STU003', 'Omar Al-Ghamdi', 'omar.stu@aalmas.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '0557654321', 'Information Technology', 'active'),
('STU004', 'Lina Al-Shammari', 'lina.stu@aalmas.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '0553456789', 'Information Technology', 'active'),
('STU005', 'Youssef Al-Mutairi', 'youssef.stu@aalmas.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '0552345678', 'Computer Science', 'active');

-- Courses
INSERT INTO `courses` (`code`, `name`, `credit_hours`, `department`, `description`) VALUES
('CS101', 'Introduction to Programming', 3, 'Computer Science', 'Fundamentals of programming using Python including variables, control structures, functions, and basic data structures.'),
('CS201', 'Data Structures & Algorithms', 3, 'Computer Science', 'Study of fundamental data structures including arrays, linked lists, trees, and graphs with their associated algorithms.'),
('IT210', 'Database Systems', 3, 'Information Technology', 'Relational database design, SQL, normalization, and database management systems.'),
('CS301', 'Artificial Intelligence', 3, 'Computer Science', 'Introduction to AI concepts including search algorithms, knowledge representation, and machine learning basics.'),
('IT310', 'Computer Networks', 3, 'Information Technology', 'Network architectures, protocols, TCP/IP model, routing, and network security fundamentals.'),
('IT320', 'Web Development', 3, 'Information Technology', 'Full-stack web development including HTML, CSS, JavaScript, PHP, and MySQL.'),
('CS250', 'Software Engineering', 3, 'Computer Science', 'Software development lifecycle, requirements engineering, design patterns, testing, and project management.'),
('IT200', 'Operating Systems', 3, 'Information Technology', 'Process management, memory management, file systems, and operating system design principles.');

-- Sections (Spring 2026 semester)
INSERT INTO `sections` (`course_id`, `section_number`, `faculty_id`, `semester`, `academic_year`, `max_students`, `schedule`, `room`, `status`) VALUES
(1, '101', 2, 'Spring 2026', '2025-2026', 35, 'Sun-Tue 08:00-09:30', 'Room A101', 'active'),
(2, '201', 2, 'Spring 2026', '2025-2026', 30, 'Mon-Wed 10:00-11:30', 'Room B205', 'active'),
(3, '210', 3, 'Spring 2026', '2025-2026', 35, 'Sun-Tue 11:00-12:30', 'Lab C301', 'active'),
(4, '301', 2, 'Spring 2026', '2025-2026', 25, 'Mon-Wed 13:00-14:30', 'Room A203', 'active'),
(5, '310', 3, 'Spring 2026', '2025-2026', 30, 'Sun-Tue 14:00-15:30', 'Lab D102', 'active'),
(6, '320', 3, 'Spring 2026', '2025-2026', 35, 'Mon-Wed 08:00-09:30', 'Lab C302', 'active'),
(7, '250', 2, 'Spring 2026', '2025-2026', 30, 'Sun-Tue 10:00-11:30', 'Room B301', 'active'),
(8, '200', 3, 'Spring 2026', '2025-2026', 35, 'Mon-Wed 11:00-12:30', 'Room A105', 'active');

-- Enrollments (Students in sections)
-- STU001 (Mohammed) - 5 courses (heavy load)
INSERT INTO `section_students` (`section_id`, `student_id`) VALUES
(1, 6), (2, 6), (3, 6), (4, 6), (7, 6);

-- STU002 (Fatimah) - 4 courses (moderate load)  
INSERT INTO `section_students` (`section_id`, `student_id`) VALUES
(1, 7), (2, 7), (4, 7), (7, 7);

-- STU003 (Omar) - 5 courses (heavy load)
INSERT INTO `section_students` (`section_id`, `student_id`) VALUES
(3, 8), (5, 8), (6, 8), (8, 8), (1, 8);

-- STU004 (Lina) - 4 courses
INSERT INTO `section_students` (`section_id`, `student_id`) VALUES
(3, 9), (5, 9), (6, 9), (8, 9);

-- STU005 (Youssef) - 4 courses
INSERT INTO `section_students` (`section_id`, `student_id`) VALUES
(1, 10), (2, 10), (4, 10), (7, 10);

-- Assessments for Section 1 (CS101 - Intro Programming)
INSERT INTO `assessments` (`section_id`, `title`, `type`, `max_score`, `weight_percentage`, `due_date`, `description`, `status`, `created_by`) VALUES
(1, 'Quiz 1: Variables & Data Types', 'quiz', 10, 5, '2026-02-08', 'Covers chapters 1-2', 'graded', 2),
(1, 'Quiz 2: Control Structures', 'quiz', 10, 5, '2026-02-22', 'Covers chapters 3-4', 'graded', 2),
(1, 'Assignment 1: Basic Programs', 'assignment', 20, 10, '2026-03-01', 'Write 5 programs demonstrating loops and conditionals', 'graded', 2),
(1, 'Midterm Exam', 'midterm', 40, 25, '2026-03-15', 'Comprehensive midterm covering chapters 1-6', 'graded', 2),
(1, 'Quiz 3: Functions', 'quiz', 10, 5, '2026-03-29', 'Covers chapter 7', 'active', 2),
(1, 'Project: Calculator App', 'project', 30, 15, '2026-04-20', 'Build a calculator application using Python', 'upcoming', 2),
(1, 'Final Exam', 'final', 50, 35, '2026-05-15', 'Comprehensive final exam', 'upcoming', 2);

-- Assessments for Section 2 (CS201 - Data Structures)
INSERT INTO `assessments` (`section_id`, `title`, `type`, `max_score`, `weight_percentage`, `due_date`, `description`, `status`, `created_by`) VALUES
(2, 'Quiz 1: Arrays & Linked Lists', 'quiz', 10, 5, '2026-02-10', NULL, 'graded', 2),
(2, 'Lab 1: Implementing Stacks', 'lab', 15, 5, '2026-02-17', 'Implement stack using arrays and linked lists', 'graded', 2),
(2, 'Quiz 2: Trees', 'quiz', 10, 5, '2026-03-03', NULL, 'graded', 2),
(2, 'Midterm Exam', 'midterm', 40, 25, '2026-03-16', NULL, 'graded', 2),
(2, 'Assignment: Graph Algorithms', 'assignment', 25, 15, '2026-04-05', 'Implement BFS and DFS', 'upcoming', 2),
(2, 'Final Exam', 'final', 50, 35, '2026-05-18', NULL, 'upcoming', 2),
(2, 'Participation', 'participation', 10, 10, '2026-05-18', NULL, 'active', 2);

-- Assessments for Section 3 (IT210 - Database Systems)
INSERT INTO `assessments` (`section_id`, `title`, `type`, `max_score`, `weight_percentage`, `due_date`, `description`, `status`, `created_by`) VALUES
(3, 'Quiz 1: ER Diagrams', 'quiz', 10, 5, '2026-02-09', NULL, 'graded', 3),
(3, 'Assignment 1: Database Design', 'assignment', 20, 10, '2026-02-23', 'Design a database for a library system', 'graded', 3),
(3, 'Lab 1: SQL Basics', 'lab', 15, 5, '2026-03-02', NULL, 'graded', 3),
(3, 'Midterm Exam', 'midterm', 40, 25, '2026-03-14', NULL, 'graded', 3),
(3, 'Quiz 2: Normalization', 'quiz', 10, 5, '2026-03-28', NULL, 'active', 3),
(3, 'Project: Full Database App', 'project', 30, 15, '2026-04-25', 'Design and implement a complete database application', 'upcoming', 3),
(3, 'Final Exam', 'final', 50, 35, '2026-05-16', NULL, 'upcoming', 3);

-- Assessments for Section 4 (CS301 - AI)
INSERT INTO `assessments` (`section_id`, `title`, `type`, `max_score`, `weight_percentage`, `due_date`, `description`, `status`, `created_by`) VALUES
(4, 'Quiz 1: Search Algorithms', 'quiz', 10, 5, '2026-02-12', NULL, 'graded', 2),
(4, 'Assignment 1: A* Implementation', 'assignment', 25, 10, '2026-03-01', NULL, 'graded', 2),
(4, 'Midterm Exam', 'midterm', 40, 25, '2026-03-17', NULL, 'graded', 2),
(4, 'Presentation: AI Ethics', 'presentation', 20, 10, '2026-04-10', NULL, 'upcoming', 2),
(4, 'Project: ML Classifier', 'project', 30, 15, '2026-04-28', NULL, 'upcoming', 2),
(4, 'Final Exam', 'final', 50, 35, '2026-05-20', NULL, 'upcoming', 2);

-- Assessments for Section 5 (IT310 - Networks)
INSERT INTO `assessments` (`section_id`, `title`, `type`, `max_score`, `weight_percentage`, `due_date`, `description`, `status`, `created_by`) VALUES
(5, 'Quiz 1: OSI Model', 'quiz', 10, 5, '2026-02-11', NULL, 'graded', 3),
(5, 'Lab 1: Packet Analysis', 'lab', 15, 5, '2026-02-25', NULL, 'graded', 3),
(5, 'Midterm Exam', 'midterm', 40, 25, '2026-03-13', NULL, 'graded', 3),
(5, 'Quiz 2: Routing Protocols', 'quiz', 10, 5, '2026-03-30', NULL, 'active', 3),
(5, 'Project: Network Design', 'project', 30, 15, '2026-04-22', NULL, 'upcoming', 3),
(5, 'Final Exam', 'final', 50, 35, '2026-05-17', NULL, 'upcoming', 3);

-- Assessments for Section 6 (IT320 - Web Dev)
INSERT INTO `assessments` (`section_id`, `title`, `type`, `max_score`, `weight_percentage`, `due_date`, `description`, `status`, `created_by`) VALUES
(6, 'Quiz 1: HTML & CSS', 'quiz', 10, 5, '2026-02-10', NULL, 'graded', 3),
(6, 'Assignment 1: Responsive Page', 'assignment', 20, 10, '2026-02-24', NULL, 'graded', 3),
(6, 'Quiz 2: JavaScript', 'quiz', 10, 5, '2026-03-10', NULL, 'graded', 3),
(6, 'Midterm Exam', 'midterm', 40, 25, '2026-03-15', NULL, 'graded', 3),
(6, 'Project: Full Website', 'project', 30, 15, '2026-04-30', NULL, 'upcoming', 3),
(6, 'Final Exam', 'final', 50, 35, '2026-05-19', NULL, 'upcoming', 3);

-- Assessments for Section 7 (CS250 - Software Eng)
INSERT INTO `assessments` (`section_id`, `title`, `type`, `max_score`, `weight_percentage`, `due_date`, `description`, `status`, `created_by`) VALUES
(7, 'Quiz 1: SDLC Models', 'quiz', 10, 5, '2026-02-13', NULL, 'graded', 2),
(7, 'Assignment: Requirements Doc', 'assignment', 20, 10, '2026-03-01', NULL, 'graded', 2),
(7, 'Midterm Exam', 'midterm', 40, 25, '2026-03-18', NULL, 'graded', 2),
(7, 'Presentation: Design Patterns', 'presentation', 20, 10, '2026-04-08', NULL, 'upcoming', 2),
(7, 'Project: Software Prototype', 'project', 30, 15, '2026-04-30', NULL, 'upcoming', 2),
(7, 'Final Exam', 'final', 50, 35, '2026-05-21', NULL, 'upcoming', 2);

-- Grades for STU001 (Mohammed) - Good student, slight decline recently
INSERT INTO `grades` (`assessment_id`, `student_id`, `score`, `entered_by`) VALUES
(1, 6, 9.0, 2), (2, 6, 8.5, 2), (3, 6, 17.0, 2), (4, 6, 33.0, 2),
(8, 6, 8.0, 2), (9, 6, 13.0, 2), (10, 6, 7.5, 2), (11, 6, 30.0, 2),
(15, 6, 8.0, 3), (16, 6, 16.0, 3), (17, 6, 12.0, 3), (18, 6, 28.0, 3),
(22, 6, 7.0, 2), (23, 6, 18.0, 2), (24, 6, 25.0, 2),
(40, 6, 8.0, 2), (41, 6, 15.0, 2), (42, 6, 28.0, 2);

-- Grades for STU002 (Fatimah) - Excellent student
INSERT INTO `grades` (`assessment_id`, `student_id`, `score`, `entered_by`) VALUES
(1, 7, 10.0, 2), (2, 7, 9.5, 2), (3, 7, 19.0, 2), (4, 7, 38.0, 2),
(8, 7, 9.5, 2), (9, 7, 14.0, 2), (10, 7, 9.0, 2), (11, 7, 36.0, 2),
(22, 7, 9.0, 2), (23, 7, 23.0, 2), (24, 7, 35.0, 2),
(40, 7, 9.5, 2), (41, 7, 18.0, 2), (42, 7, 35.0, 2);

-- Grades for STU003 (Omar) - Struggling student (at risk)
INSERT INTO `grades` (`assessment_id`, `student_id`, `score`, `entered_by`) VALUES
(1, 8, 5.0, 2), (2, 8, 4.0, 2), (3, 8, 10.0, 2), (4, 8, 18.0, 2),
(15, 8, 4.0, 3), (16, 8, 8.0, 3), (17, 8, 6.0, 3), (18, 8, 15.0, 3),
(28, 8, 5.0, 3), (29, 8, 7.0, 3), (30, 8, 16.0, 3),
(34, 8, 6.0, 3), (35, 8, 10.0, 3), (36, 8, 4.0, 3), (37, 8, 18.0, 3);

-- Grades for STU004 (Lina) - Average student, needs monitoring
INSERT INTO `grades` (`assessment_id`, `student_id`, `score`, `entered_by`) VALUES
(15, 9, 6.0, 3), (16, 9, 12.0, 3), (17, 9, 9.0, 3), (18, 9, 22.0, 3),
(28, 9, 7.0, 3), (29, 9, 10.0, 3), (30, 9, 24.0, 3),
(34, 9, 7.0, 3), (35, 9, 14.0, 3), (36, 9, 6.0, 3), (37, 9, 26.0, 3);

-- Grades for STU005 (Youssef) - Good student with declining trend
INSERT INTO `grades` (`assessment_id`, `student_id`, `score`, `entered_by`) VALUES
(1, 10, 9.5, 2), (2, 10, 7.0, 2), (3, 10, 14.0, 2), (4, 10, 26.0, 2),
(8, 10, 8.5, 2), (9, 10, 11.0, 2), (10, 10, 6.0, 2), (11, 10, 24.0, 2),
(22, 10, 8.0, 2), (23, 10, 16.0, 2), (24, 10, 22.0, 2),
(40, 10, 7.0, 2), (41, 10, 12.0, 2), (42, 10, 22.0, 2);

-- Advisor Assignments
INSERT INTO `advisor_assignments` (`student_id`, `advisor_id`) VALUES
(6, 4), -- Mohammed -> Dr. Nora
(7, 4), -- Fatimah -> Dr. Nora
(10, 4), -- Youssef -> Dr. Nora
(8, 5), -- Omar -> Dr. Fahad
(9, 5); -- Lina -> Dr. Fahad

-- Contact Requests
INSERT INTO `contact_requests` (`student_id`, `advisor_id`, `subject`, `message`, `priority`, `status`, `created_at`) VALUES
(8, 5, 'Struggling with Database Course', 'Dear Dr. Fahad, I am having significant difficulty understanding normalization concepts in IT210. My grades have been declining and I would appreciate guidance on how to improve. I have been attending all lectures but still struggling with the practical applications.', 'urgent', 'under_review', '2026-03-20 10:30:00'),
(6, 4, 'Course Load Concern', 'Dear Dr. Nora, I am enrolled in 5 courses this semester and finding it challenging to manage all assessments. Could we discuss possible strategies or if I should consider dropping a course?', 'normal', 'replied', '2026-03-15 14:20:00'),
(9, 5, 'Request for Extra Help Sessions', 'Dear Dr. Fahad, I would like to request additional tutoring or help sessions for Computer Networks. The lab work is particularly challenging.', 'normal', 'sent', '2026-03-25 09:15:00'),
(10, 4, 'Declining Performance Discussion', 'Dear Dr. Nora, I have noticed my grades dropping compared to earlier in the semester. I believe the increased workload is affecting my performance. Can we schedule a meeting?', 'urgent', 'replied', '2026-03-18 16:45:00'),
(7, 4, 'Graduate Studies Inquiry', 'Dear Dr. Nora, I am maintaining strong grades and would like to discuss the possibility of pursuing graduate studies. Could you provide guidance on the application process?', 'normal', 'closed', '2026-03-10 11:00:00');

-- Request Replies
INSERT INTO `request_replies` (`request_id`, `user_id`, `message`, `created_at`) VALUES
(2, 4, 'Dear Mohammed, thank you for reaching out. I understand the challenge of managing 5 courses. Let us schedule a meeting this week to review your course load and academic plan. In the meantime, I recommend prioritizing your assessments by due date.', '2026-03-16 10:00:00'),
(4, 4, 'Dear Youssef, I have reviewed your recent performance and noticed the declining trend. I have scheduled a meeting for next Sunday at 10 AM. Please bring your course materials so we can identify specific areas of difficulty.', '2026-03-19 09:30:00'),
(5, 4, 'Dear Fatimah, congratulations on your excellent performance! I would be happy to guide you on graduate studies. I have sent you some resources via email. Feel free to visit during office hours for a detailed discussion.', '2026-03-11 14:00:00'),
(5, 7, 'Thank you Dr. Nora! I will review the resources and visit during your office hours this week.', '2026-03-12 08:30:00');

-- Notifications
INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(6, 'grade', 'New Grade Posted', 'Your grade for Midterm Exam in CS101 has been posted.', '/student/grades.php', 1, '2026-03-15 15:00:00'),
(6, 'alert', 'High Workload Warning', 'You have 3 assessments due next week. Plan accordingly.', '/student/workload.php', 0, '2026-03-22 08:00:00'),
(7, 'grade', 'New Grade Posted', 'Your grade for Midterm Exam in CS201 has been posted.', '/student/grades.php', 1, '2026-03-16 16:00:00'),
(7, 'system', 'Request Closed', 'Your contact request about Graduate Studies has been closed.', '/student/contact.php', 1, '2026-03-12 14:30:00'),
(8, 'alert', 'Academic Risk Alert', 'Your performance in IT210 Database Systems has been flagged as At Risk.', '/student/alerts.php', 0, '2026-03-20 09:00:00'),
(8, 'alert', 'Low Grade Warning', 'Your midterm score in IT320 Web Development is below passing threshold.', '/student/grades.php', 0, '2026-03-16 10:00:00'),
(9, 'grade', 'New Grade Posted', 'Your grade for Midterm Exam in IT310 has been posted.', '/student/grades.php', 0, '2026-03-14 14:00:00'),
(10, 'alert', 'Declining Performance', 'Your grades show a declining trend in CS201 Data Structures.', '/student/grades.php', 0, '2026-03-20 11:00:00'),
(4, 'request', 'New Contact Request', 'Mohammed Al-Saeed has sent a new contact request regarding course load.', '/advisor/requests.php', 1, '2026-03-15 14:25:00'),
(4, 'request', 'New Contact Request', 'Youssef Al-Mutairi has sent an urgent contact request.', '/advisor/requests.php', 1, '2026-03-18 16:50:00'),
(5, 'request', 'New Contact Request', 'Omar Al-Ghamdi has sent an urgent contact request.', '/advisor/requests.php', 0, '2026-03-20 10:35:00'),
(5, 'request', 'New Contact Request', 'Lina Al-Shammari has sent a new contact request.', '/advisor/requests.php', 0, '2026-03-25 09:20:00'),
(2, 'system', 'Assessment Reminder', 'Quiz 3: Functions in CS101 is due on March 29.', '/faculty/assessments.php', 0, '2026-03-27 08:00:00'),
(3, 'system', 'Grade Entry Pending', 'Quiz 2: Normalization in IT210 needs grade entry.', '/faculty/grades.php', 0, '2026-03-28 08:00:00');

-- Academic Alerts
INSERT INTO `academic_alerts` (`student_id`, `section_id`, `alert_type`, `severity`, `title`, `message`, `is_read`, `created_at`) VALUES
(8, 1, 'low_grade', 'danger', 'Low Performance in CS101', 'Omar Al-Ghamdi scored 18/40 (45%) on the Midterm Exam in Introduction to Programming, significantly below the class average.', 0, '2026-03-15 16:00:00'),
(8, 3, 'low_grade', 'critical', 'Critical Performance in IT210', 'Omar Al-Ghamdi scored 15/40 (37.5%) on the Midterm Exam in Database Systems. Immediate intervention recommended.', 0, '2026-03-14 17:00:00'),
(8, 6, 'declining_trend', 'danger', 'Declining Grades in IT320', 'Omar Al-Ghamdi shows a consistent declining trend in Web Development assessments over the last 4 evaluations.', 0, '2026-03-16 10:30:00'),
(8, NULL, 'high_workload', 'warning', 'High Course Load', 'Omar Al-Ghamdi is enrolled in 5 courses with 4 assessments in the upcoming two weeks.', 0, '2026-03-22 08:00:00'),
(10, 2, 'declining_trend', 'warning', 'Declining Grades in CS201', 'Youssef Al-Mutairi shows declining performance in Data Structures: Quiz scores dropped from 85% to 60%.', 0, '2026-03-17 09:00:00'),
(10, 1, 'declining_trend', 'warning', 'Grade Drop in CS101', 'Youssef Al-Mutairi midterm score (65%) is lower than quiz average (82.5%), suggesting possible exam difficulties.', 0, '2026-03-16 11:00:00'),
(6, NULL, 'high_workload', 'info', 'Heavy Assessment Week', 'Mohammed Al-Saeed has 3 assessments due between March 28-April 5 across different courses.', 1, '2026-03-22 08:00:00'),
(9, 5, 'low_grade', 'warning', 'Below Average in IT310', 'Lina Al-Shammari scored 24/40 (60%) on the Midterm Exam in Computer Networks, near the passing threshold.', 0, '2026-03-14 15:00:00'),
(9, 3, 'low_grade', 'warning', 'Below Average in IT210', 'Lina Al-Shammari scored 22/40 (55%) on the Midterm Exam in Database Systems.', 0, '2026-03-15 11:00:00');

-- Academic Notes
INSERT INTO `academic_notes` (`student_id`, `author_id`, `note_type`, `content`, `is_private`) VALUES
(8, 5, 'warning', 'Omar is showing signs of academic struggle across multiple courses. Recommend scheduling a comprehensive academic review meeting.', 0),
(8, 3, 'general', 'Omar attended office hours but seems to have fundamental gaps in database concepts. May benefit from peer tutoring.', 1),
(10, 4, 'follow_up', 'Youssef mentioned personal issues affecting his study time. Will follow up in 2 weeks to reassess.', 1),
(6, 4, 'recommendation', 'Mohammed is performing well but heavy course load may lead to burnout. Discussed time management strategies.', 0),
(7, 4, 'general', 'Fatimah is an outstanding student. Recommended for departmental honors list and graduate studies.', 0),
(9, 5, 'follow_up', 'Lina requested additional tutoring. Connecting her with a peer tutor for Networks and Database courses.', 0);

-- System Settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('site_name', 'AALMAS', 'System display name'),
('current_semester', 'Spring 2026', 'Active semester'),
('academic_year', '2025-2026', 'Current academic year'),
('risk_threshold_low', '60', 'Grade percentage below which student is flagged as at risk'),
('risk_threshold_monitor', '70', 'Grade percentage below which student needs monitoring'),
('workload_threshold', '3', 'Number of assessments per week to trigger workload warning'),
('max_upload_size', '5242880', 'Maximum file upload size in bytes (5MB)'),
('allowed_file_types', 'pdf,doc,docx,jpg,png,zip', 'Allowed file extensions for uploads');

-- Activity Log
INSERT INTO `activity_log` (`user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 'login', 'Admin logged in successfully', '127.0.0.1', '2026-03-30 08:00:00'),
(2, 'grade_entry', 'Entered midterm grades for CS101 Section 101', '127.0.0.1', '2026-03-15 14:30:00'),
(3, 'grade_entry', 'Entered midterm grades for IT210 Section 210', '127.0.0.1', '2026-03-14 16:00:00'),
(2, 'assessment_create', 'Created Quiz 3: Functions for CS101', '127.0.0.1', '2026-03-20 09:00:00'),
(4, 'request_reply', 'Replied to Mohammed Al-Saeed contact request', '127.0.0.1', '2026-03-16 10:05:00'),
(5, 'note_add', 'Added academic note for Omar Al-Ghamdi', '127.0.0.1', '2026-03-20 11:30:00'),
(1, 'user_create', 'Created new student account: STU005', '127.0.0.1', '2026-03-01 09:00:00');

COMMIT;
