-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 11, 2026 at 12:32 PM
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
-- Database: `enrollment_system_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_history`
--

CREATE TABLE `academic_history` (
  `history_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `gpa` decimal(4,2) DEFAULT NULL,
  `academic_standing` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academic_history_details`
--

CREATE TABLE `academic_history_details` (
  `detail_id` int(11) NOT NULL,
  `history_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `final_grade` decimal(5,2) NOT NULL,
  `remarks` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `posted_by` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `content` text NOT NULL,
  `category` enum('Enrollment Announcement','Scholarship Deadline','School Announcement','Class Announcement') NOT NULL,
  `target_audience` enum('All','Students','Parents','Class Section') NOT NULL,
  `date_posted` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('Present','Absent','Late') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_sections`
--

CREATE TABLE `class_sections` (
  `section_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `section_name` varchar(50) NOT NULL,
  `schedule_details` varchar(100) NOT NULL,
  `room` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `enrolled_by` int(11) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `enrollment_status` enum('Temporarily Enrolled','Officially Enrolled') DEFAULT 'Temporarily Enrolled',
  `requirements_status` enum('Complete','Incomplete/Missing') DEFAULT 'Incomplete/Missing',
  `section_assigned` varchar(50) DEFAULT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scholarship_applications`
--

CREATE TABLE `scholarship_applications` (
  `application_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `supporting_documents` text DEFAULT NULL,
  `application_status` enum('Submitted','Under Verification','Approved','Rejected') DEFAULT 'Submitted',
  `application_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scholarship_monitoring`
--

CREATE TABLE `scholarship_monitoring` (
  `monitoring_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `scholarship_status` enum('Active','Suspended','Terminated','Completed') DEFAULT 'Active',
  `renewal_eligibility` tinyint(1) DEFAULT 1,
  `assistance_distribution` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scholarship_programs`
--

CREATE TABLE `scholarship_programs` (
  `program_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `program_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `eligibility_criteria` text DEFAULT NULL,
  `slots_available` int(11) NOT NULL,
  `deadline` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scholarship_providers`
--

CREATE TABLE `scholarship_providers` (
  `provider_id` int(11) NOT NULL,
  `provider_name` varchar(100) NOT NULL,
  `contact_info` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_contact` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_classes`
--

CREATE TABLE `student_classes` (
  `student_class_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `final_grade` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_quarterly_grades`
--

CREATE TABLE `student_quarterly_grades` (
  `quarter_grade_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `quarter` int(11) NOT NULL CHECK (`quarter` between 1 and 4),
  `grade` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `units` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `teacher_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('Admin','Scholarship Coordinator','Teacher','Student','Parent') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_history`
--
ALTER TABLE `academic_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `academic_history_details`
--
ALTER TABLE `academic_history_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `history_id` (`history_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `posted_by` (`posted_by`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `class_sections`
--
ALTER TABLE `class_sections`
  ADD PRIMARY KEY (`section_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `enrolled_by` (`enrolled_by`);

--
-- Indexes for table `scholarship_applications`
--
ALTER TABLE `scholarship_applications`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `scholarship_monitoring`
--
ALTER TABLE `scholarship_monitoring`
  ADD PRIMARY KEY (`monitoring_id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `scholarship_programs`
--
ALTER TABLE `scholarship_programs`
  ADD PRIMARY KEY (`program_id`),
  ADD KEY `provider_id` (`provider_id`);

--
-- Indexes for table `scholarship_providers`
--
ALTER TABLE `scholarship_providers`
  ADD PRIMARY KEY (`provider_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `student_classes`
--
ALTER TABLE `student_classes`
  ADD PRIMARY KEY (`student_class_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `student_quarterly_grades`
--
ALTER TABLE `student_quarterly_grades`
  ADD PRIMARY KEY (`quarter_grade_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`teacher_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_history`
--
ALTER TABLE `academic_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `academic_history_details`
--
ALTER TABLE `academic_history_details`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_sections`
--
ALTER TABLE `class_sections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scholarship_applications`
--
ALTER TABLE `scholarship_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scholarship_monitoring`
--
ALTER TABLE `scholarship_monitoring`
  MODIFY `monitoring_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scholarship_programs`
--
ALTER TABLE `scholarship_programs`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scholarship_providers`
--
ALTER TABLE `scholarship_providers`
  MODIFY `provider_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_classes`
--
ALTER TABLE `student_classes`
  MODIFY `student_class_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_quarterly_grades`
--
ALTER TABLE `student_quarterly_grades`
  MODIFY `quarter_grade_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `teacher_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academic_history`
--
ALTER TABLE `academic_history`
  ADD CONSTRAINT `academic_history_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `academic_history_details`
--
ALTER TABLE `academic_history_details`
  ADD CONSTRAINT `academic_history_details_ibfk_1` FOREIGN KEY (`history_id`) REFERENCES `academic_history` (`history_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `academic_history_details_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `class_sections` (`section_id`) ON DELETE CASCADE;

--
-- Constraints for table `class_sections`
--
ALTER TABLE `class_sections`
  ADD CONSTRAINT `class_sections_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_sections_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`enrolled_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `scholarship_applications`
--
ALTER TABLE `scholarship_applications`
  ADD CONSTRAINT `scholarship_applications_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `scholarship_programs` (`program_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `scholarship_applications_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `scholarship_monitoring`
--
ALTER TABLE `scholarship_monitoring`
  ADD CONSTRAINT `scholarship_monitoring_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `scholarship_applications` (`application_id`) ON DELETE CASCADE;

--
-- Constraints for table `scholarship_programs`
--
ALTER TABLE `scholarship_programs`
  ADD CONSTRAINT `scholarship_programs_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `scholarship_providers` (`provider_id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_classes`
--
ALTER TABLE `student_classes`
  ADD CONSTRAINT `student_classes_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_classes_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `class_sections` (`section_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_quarterly_grades`
--
ALTER TABLE `student_quarterly_grades`
  ADD CONSTRAINT `student_quarterly_grades_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_quarterly_grades_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
