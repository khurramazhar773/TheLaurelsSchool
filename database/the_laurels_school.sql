-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 04, 2025 at 09:55 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `the_laurels_school`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 7, 'login', 'User logged in successfully', NULL, NULL, '2025-09-01 16:44:14'),
(2, 7, 'login', 'User logged in successfully', NULL, NULL, '2025-09-01 16:56:32'),
(3, 7, 'login', 'User logged in successfully', NULL, NULL, '2025-09-02 10:56:29'),
(4, 8, 'login', 'User logged in successfully', NULL, NULL, '2025-09-02 11:28:35'),
(5, 7, 'login', 'User logged in successfully', NULL, NULL, '2025-09-02 11:32:00'),
(6, 7, 'login', 'User logged in successfully', NULL, NULL, '2025-09-03 14:47:44'),
(7, 7, 'login', 'User logged in successfully', NULL, NULL, '2025-09-04 13:03:57'),
(8, 7, 'login', 'User logged in successfully', NULL, NULL, '2025-09-04 18:11:59'),
(9, 7, 'login', 'User logged in successfully', NULL, NULL, '2025-09-04 18:36:23');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_settings`
--

CREATE TABLE `attendance_settings` (
  `id` int(11) NOT NULL,
  `duty_start` time NOT NULL DEFAULT '08:00:00',
  `duty_end` time NOT NULL DEFAULT '14:00:00',
  `grace_minutes` int(11) NOT NULL DEFAULT 15,
  `early_grace_minutes` int(11) NOT NULL DEFAULT 15,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_settings`
--

INSERT INTO `attendance_settings` (`id`, `duty_start`, `duty_end`, `grace_minutes`, `early_grace_minutes`, `created_at`, `updated_at`) VALUES
(1, '08:00:00', '14:00:00', 15, 15, '2025-09-04 18:11:02', '2025-09-04 18:11:02');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `name`, `academic_year`, `description`, `created_at`, `updated_at`) VALUES
(1, 'asd', '1234', 'asd', '2025-09-03 14:58:32', '2025-09-03 14:58:32'),
(2, '2', '1235', 'asd', '2025-09-03 15:05:43', '2025-09-03 15:05:43'),
(3, '3', '1245', 'asd', '2025-09-03 15:20:03', '2025-09-03 15:20:03');

-- --------------------------------------------------------

--
-- Table structure for table `class_subjects`
--

CREATE TABLE `class_subjects` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_subjects`
--

INSERT INTO `class_subjects` (`id`, `class_id`, `subject_id`, `created_at`) VALUES
(1, 1, 1, '2025-09-03 15:04:51'),
(2, 1, 3, '2025-09-03 15:04:55'),
(3, 1, 2, '2025-09-03 15:04:57'),
(4, 2, 1, '2025-09-03 15:05:52'),
(5, 2, 2, '2025-09-03 15:05:55'),
(6, 3, 3, '2025-09-03 15:20:09');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `enrollment_date` date NOT NULL,
  `status` enum('active','completed','dropped') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `student_id`, `class_id`, `enrollment_date`, `status`, `created_at`, `updated_at`) VALUES
(4, 3, 3, '2025-09-04', 'active', '2025-09-04 15:20:15', '2025-09-04 15:20:15'),
(5, 2, 3, '2025-09-04', 'active', '2025-09-04 15:20:18', '2025-09-04 15:20:18');

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `exam_type` enum('assessment','term') NOT NULL,
  `name` varchar(150) NOT NULL,
  `exam_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`id`, `class_id`, `exam_type`, `name`, `exam_date`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, 'term', '1', '2025-09-03', 'asd', '2025-09-03 15:05:21', '2025-09-03 15:05:21'),
(2, 2, 'term', '2', '2025-09-13', 'asd', '2025-09-03 15:06:13', '2025-09-03 15:06:13'),
(3, 3, 'assessment', 'sdfgfhgd', '2025-09-11', 'dfgdfh', '2025-09-03 15:20:30', '2025-09-03 15:20:30');

-- --------------------------------------------------------

--
-- Table structure for table `exam_subjects`
--

CREATE TABLE `exam_subjects` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `max_marks` decimal(6,2) NOT NULL DEFAULT 100.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_subjects`
--

INSERT INTO `exam_subjects` (`id`, `exam_id`, `subject_id`, `max_marks`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 50.00, '2025-09-03 12:06:26', '2025-09-03 15:06:26'),
(2, 2, 2, 40.00, '2025-09-03 12:06:32', '2025-09-03 15:06:32'),
(3, 3, 3, 50.00, '2025-09-03 12:20:44', '2025-09-03 15:20:44'),
(4, 3, 1, 50.00, '2025-09-03 12:20:49', '2025-09-03 15:20:49');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_code` varchar(20) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `sex` enum('Male','Female') NOT NULL,
  `date_of_birth` date NOT NULL,
  `birth_place` varchar(100) NOT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `address` text NOT NULL,
  `city` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `father_name` varchar(100) NOT NULL,
  `father_occupation` varchar(100) DEFAULT NULL,
  `father_address` text DEFAULT NULL,
  `father_position` varchar(100) DEFAULT NULL,
  `father_work_phone` varchar(20) DEFAULT NULL,
  `father_cell` varchar(20) DEFAULT NULL,
  `mother_name` varchar(100) NOT NULL,
  `mother_occupation` varchar(100) DEFAULT NULL,
  `mother_address` text DEFAULT NULL,
  `mother_position` varchar(100) DEFAULT NULL,
  `mother_work_phone` varchar(20) DEFAULT NULL,
  `mother_cell` varchar(20) DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_relation` varchar(50) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `guardian_address` text DEFAULT NULL,
  `emergency_contact_name` varchar(100) NOT NULL,
  `emergency_contact_phone` varchar(20) NOT NULL,
  `last_school_attended` varchar(200) DEFAULT NULL,
  `last_school_year` varchar(10) DEFAULT NULL,
  `last_school_grade` varchar(20) DEFAULT NULL,
  `last_school_address` text DEFAULT NULL,
  `has_asthma` tinyint(1) DEFAULT 0,
  `has_allergies` tinyint(1) DEFAULT 0,
  `has_heart_disease` tinyint(1) DEFAULT 0,
  `has_convulsions` tinyint(1) DEFAULT 0,
  `has_diabetes` tinyint(1) DEFAULT 0,
  `has_cancer` tinyint(1) DEFAULT 0,
  `has_tuberculosis` tinyint(1) DEFAULT 0,
  `has_epilepsy` tinyint(1) DEFAULT 0,
  `has_hearing_problems` tinyint(1) DEFAULT 0,
  `has_speech_problems` tinyint(1) DEFAULT 0,
  `has_orthopedic_problems` tinyint(1) DEFAULT 0,
  `has_other_problems` tinyint(1) DEFAULT 0,
  `other_problems_description` text DEFAULT NULL,
  `major_operations_injuries` text DEFAULT NULL,
  `regular_medication` text DEFAULT NULL,
  `family_physician_name` varchar(100) DEFAULT NULL,
  `family_physician_phone` varchar(20) DEFAULT NULL,
  `heard_through_newspapers` tinyint(1) DEFAULT 0,
  `heard_through_advertisements` tinyint(1) DEFAULT 0,
  `heard_through_friends` tinyint(1) DEFAULT 0,
  `heard_through_relatives` tinyint(1) DEFAULT 0,
  `heard_through_other` tinyint(1) DEFAULT 0,
  `heard_through_other_description` text DEFAULT NULL,
  `application_date` date NOT NULL,
  `parent_signature` varchar(255) DEFAULT NULL,
  `passport_photo` varchar(255) DEFAULT NULL,
  `school_leaving_certificate` varchar(255) DEFAULT NULL,
  `recent_exam_results` varchar(255) DEFAULT NULL,
  `father_nic_copy` varchar(255) DEFAULT NULL,
  `mother_nic_copy` varchar(255) DEFAULT NULL,
  `guardian_nic_copy` varchar(255) DEFAULT NULL,
  `birth_certificate` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `verification_notes` text DEFAULT NULL,
  `status` enum('pending','active','inactive','withdrawn','completed','suspended','expelled','transferred','graduated','on_leave') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_code`, `first_name`, `middle_name`, `last_name`, `sex`, `date_of_birth`, `birth_place`, `religion`, `address`, `city`, `phone`, `father_name`, `father_occupation`, `father_address`, `father_position`, `father_work_phone`, `father_cell`, `mother_name`, `mother_occupation`, `mother_address`, `mother_position`, `mother_work_phone`, `mother_cell`, `guardian_name`, `guardian_relation`, `guardian_phone`, `guardian_address`, `emergency_contact_name`, `emergency_contact_phone`, `last_school_attended`, `last_school_year`, `last_school_grade`, `last_school_address`, `has_asthma`, `has_allergies`, `has_heart_disease`, `has_convulsions`, `has_diabetes`, `has_cancer`, `has_tuberculosis`, `has_epilepsy`, `has_hearing_problems`, `has_speech_problems`, `has_orthopedic_problems`, `has_other_problems`, `other_problems_description`, `major_operations_injuries`, `regular_medication`, `family_physician_name`, `family_physician_phone`, `heard_through_newspapers`, `heard_through_advertisements`, `heard_through_friends`, `heard_through_relatives`, `heard_through_other`, `heard_through_other_description`, `application_date`, `parent_signature`, `passport_photo`, `school_leaving_certificate`, `recent_exam_results`, `father_nic_copy`, `mother_nic_copy`, `guardian_nic_copy`, `birth_certificate`, `is_verified`, `verified_by`, `verified_at`, `verification_notes`, `status`, `created_at`, `updated_at`) VALUES
(2, NULL, 'Khurram', '', 'Azhar', 'Male', '2025-09-10', 'asd', 'islam', '5', 'Faisalabad', '03051251144', 'asd', 'dfds', 'dsfdsfs', 'dfsdfsdf', 'sdfsdfsd', 'sdfds', 'sdfsdf', 'sdfsdfsdf', 'sdfsdfsdf', 'sdfsdfsd', 'fsdfsdf', 'sdfsdfsdf', 'dsfsdf', 'dsfsdf', 'fdsfds', 'sdfsdfsdfsd', 'dsfsdfsdf', 'dsfsdfsd', 'dsfsdf', 'fsdf', 'sdfsd', 'dsfsdfsd', 1, 1, 0, 1, 1, 1, 1, 1, 1, 0, 1, 0, 'dsfsdf', 'dsfdsfsdf', 'sdfdsfsdf', 'dsfsdfsdfsdfsd', 'dsfsdf', 1, 1, 0, 0, 0, 'dsfsdfdsfsd', '2025-09-02', '1756812239_download__1_.jpeg', '1756812239_download__1_.jpeg', '1756812239_download__1_.jpeg', '1756812239_download__1_.jpeg', '1756812239_download__1_.jpeg', '1756812239_download.jpeg', '1756812239_download.jpeg', '1756812239_download__1_.jpeg', 1, 7, '2025-09-04 11:18:18', '', 'active', '2025-09-02 11:23:59', '2025-09-04 14:18:18'),
(3, 'LAUREL25001', 'ASd', '', 'ASD', 'Male', '2025-09-10', 'asd', '', 'asd', 'asd', '', 'asd', '', '', '', '', '', 'asd', '', '', '', '', '', '', '', '', '', 'asd', 'asd', '', '', '', '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '', '', '', '', '', 0, 0, 0, 0, 0, '', '2025-09-04', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 7, '2025-09-04 11:17:27', '', 'active', '2025-09-04 14:16:41', '2025-09-04 14:17:27');

-- --------------------------------------------------------

--
-- Table structure for table `student_attendance`
--

CREATE TABLE `student_attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('present','absent','late','excused') DEFAULT 'absent',
  `marked_by` int(11) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_habits`
--

CREATE TABLE `student_habits` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `islamic_manners` enum('A','B','C') DEFAULT NULL,
  `punctual` enum('A','B','C') DEFAULT NULL,
  `well_behaved` enum('A','B','C') DEFAULT NULL,
  `follow_instructions` enum('A','B','C') DEFAULT NULL,
  `neatness` enum('A','B','C') DEFAULT NULL,
  `health` enum('A','B','C') DEFAULT NULL,
  `homework` enum('A','B','C') DEFAULT NULL,
  `get_sign_daily` enum('A','B','C') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_habits`
--

INSERT INTO `student_habits` (`id`, `exam_id`, `student_id`, `islamic_manners`, `punctual`, `well_behaved`, `follow_instructions`, `neatness`, `health`, `homework`, `get_sign_daily`, `created_at`, `updated_at`) VALUES
(1, 3, 3, 'A', 'B', 'A', 'A', 'C', 'A', 'A', 'A', '2025-09-04 15:25:20', '2025-09-04 17:02:49'),
(2, 3, 2, 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', '2025-09-04 15:25:20', '2025-09-04 17:02:49');

-- --------------------------------------------------------

--
-- Table structure for table `student_marks`
--

CREATE TABLE `student_marks` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `obtained_marks` decimal(7,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_marks`
--

INSERT INTO `student_marks` (`id`, `exam_id`, `student_id`, `subject_id`, `obtained_marks`, `created_at`, `updated_at`) VALUES
(1, 3, 3, 1, 40.00, '2025-09-04 15:25:02', '2025-09-04 15:25:04'),
(2, 3, 3, 3, 40.00, '2025-09-04 15:25:02', '2025-09-04 15:25:04'),
(3, 3, 2, 1, 40.00, '2025-09-04 15:25:02', '2025-09-04 15:25:04'),
(4, 3, 2, 3, 40.00, '2025-09-04 15:25:02', '2025-09-04 15:25:04');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES
(1, 'E', '1', 'asd', '2025-09-03 15:04:29', '2025-09-03 15:04:29'),
(2, 'U', '2', 'ASD', '2025-09-03 15:04:35', '2025-09-03 15:04:35'),
(3, 'M', '3', 'ASD', '2025-09-03 15:04:41', '2025-09-03 15:04:41');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_attendance`
--

CREATE TABLE `teacher_attendance` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `attendance_time` time NOT NULL,
  `status` enum('present','absent','late') NOT NULL DEFAULT 'present',
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_attendance`
--

INSERT INTO `teacher_attendance` (`id`, `teacher_id`, `attendance_date`, `attendance_time`, `status`, `remarks`, `created_at`) VALUES
(1, 10, '2025-09-02', '17:05:00', 'present', NULL, '2025-09-02 15:05:51'),
(2, 10, '2025-09-04', '17:31:00', 'present', NULL, '2025-09-04 15:32:02');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `role`, `status`, `created_at`, `updated_at`) VALUES
(7, 'Admin', 'User', 'admin@laurelsschool.com', '$2y$10$J8iM1DJMcFeZLtZGTlbg7.p362OcQSiJHVi8TvaRBrWsL9HsfC3oe', 'admin', 'active', '2025-09-01 16:36:04', '2025-09-01 16:42:58'),
(8, 'faiq', 'hassan', 'faiq@gmail.com', '$2y$10$d4PPl3MaJEYGcj6uIgcFaeGSG95ZybUQBeFHkPTUTXvBxnB7Q1xiu', 'admin', 'active', '2025-09-02 11:28:19', '2025-09-02 11:28:19'),
(10, 'Asd', 'AAsd', 'asd@asd.asd', '$2y$10$P8sVjyJXfEJZMaXHie/.huTg1Ndum6Uyv9KwdBOL5SKgqjCMci8kq', 'teacher', 'active', '2025-09-02 15:05:34', '2025-09-02 15:05:34');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_logs_user` (`user_id`),
  ADD KEY `idx_activity_logs_created` (`created_at`);

--
-- Indexes for table `attendance_settings`
--
ALTER TABLE `attendance_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_class_subject` (`class_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_enrollment` (`student_id`,`class_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `exam_subjects`
--
ALTER TABLE `exam_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_exam_subject` (`exam_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_code` (`student_code`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `idx_students_name` (`first_name`,`middle_name`,`last_name`),
  ADD KEY `idx_students_status` (`status`),
  ADD KEY `idx_students_verified` (`is_verified`),
  ADD KEY `idx_students_created` (`created_at`);

--
-- Indexes for table `student_attendance`
--
ALTER TABLE `student_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_date` (`student_id`,`attendance_date`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `marked_by` (`marked_by`);

--
-- Indexes for table `student_habits`
--
ALTER TABLE `student_habits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_exam_student` (`exam_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `student_marks`
--
ALTER TABLE `student_marks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_exam_student_subject` (`exam_id`,`student_id`,`subject_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `teacher_attendance`
--
ALTER TABLE `teacher_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_teacher_date` (`teacher_id`,`attendance_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `attendance_settings`
--
ALTER TABLE `attendance_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `class_subjects`
--
ALTER TABLE `class_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `exam_subjects`
--
ALTER TABLE `exam_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student_attendance`
--
ALTER TABLE `student_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_habits`
--
ALTER TABLE `student_habits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `student_marks`
--
ALTER TABLE `student_marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `teacher_attendance`
--
ALTER TABLE `teacher_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD CONSTRAINT `class_subjects_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_subjects`
--
ALTER TABLE `exam_subjects`
  ADD CONSTRAINT `exam_subjects_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_attendance`
--
ALTER TABLE `student_attendance`
  ADD CONSTRAINT `student_attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_attendance_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_attendance_ibfk_3` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_habits`
--
ALTER TABLE `student_habits`
  ADD CONSTRAINT `student_habits_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_habits_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_marks`
--
ALTER TABLE `student_marks`
  ADD CONSTRAINT `student_marks_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_marks_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_marks_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_attendance`
--
ALTER TABLE `teacher_attendance`
  ADD CONSTRAINT `teacher_attendance_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
