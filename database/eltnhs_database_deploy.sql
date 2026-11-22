-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 22, 2026 at 07:53 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `eltnhs_database_deploy`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(160) NOT NULL,
  `content` text NOT NULL,
  `announcement_category` enum('Enrollment','Scholarship','School','General') NOT NULL DEFAULT 'General',
  `target_audience` enum('All','Students','Teachers','Registrar','Subject Teachers','Teacher Advisers') NOT NULL DEFAULT 'All',
  `visibility` enum('Public','Internal') NOT NULL DEFAULT 'Internal',
  `publish_date` datetime NOT NULL,
  `expiry_date` datetime DEFAULT NULL,
  `announcement_status` enum('Draft','Published','Archived') NOT NULL DEFAULT 'Draft',
  `posted_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `title`, `content`, `announcement_category`, `target_audience`, `visibility`, `publish_date`, `expiry_date`, `announcement_status`, `posted_by`, `created_at`) VALUES
(1, 'DEMO: Enrollment Reminder', 'Please complete your requirements before guidance approval.', 'Enrollment', 'All', 'Internal', '2026-11-01 08:00:00', NULL, 'Published', 2, '2026-11-20 02:17:14');

-- --------------------------------------------------------

--
-- Table structure for table `assistance_distributions`
--

CREATE TABLE `assistance_distributions` (
  `distribution_id` int(11) NOT NULL,
  `award_id` int(11) NOT NULL,
  `distribution_date` date NOT NULL,
  `assistance_description` varchar(180) NOT NULL,
  `quantity_or_coverage` varchar(120) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `section_subject_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `attendance_status` enum('Present','Absent','Late','Excused') NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `enrollment_id`, `section_subject_id`, `attendance_date`, `attendance_status`, `remarks`, `recorded_by`, `recorded_at`) VALUES
(1, 1, 1, '2026-11-16', 'Present', NULL, 6, '2026-11-20 02:17:14'),
(2, 2, 1, '2026-11-16', 'Present', NULL, 6, '2026-11-20 02:17:14'),
(3, 3, 1, '2026-11-16', 'Absent', NULL, 6, '2026-11-20 02:17:14'),
(4, 4, 1, '2026-11-16', 'Present', NULL, 6, '2026-11-20 02:17:14'),
(5, 5, 1, '2026-11-16', 'Present', NULL, 6, '2026-11-20 02:17:14'),
(6, 1, 3, '2026-11-16', 'Present', NULL, 8, '2026-11-20 02:17:14'),
(7, 2, 3, '2026-11-16', 'Present', NULL, 8, '2026-11-20 02:17:14'),
(8, 3, 3, '2026-11-16', 'Absent', NULL, 8, '2026-11-20 02:17:14'),
(9, 4, 3, '2026-11-16', 'Present', NULL, 8, '2026-11-20 02:17:14'),
(10, 5, 3, '2026-11-16', 'Present', NULL, 8, '2026-11-20 02:17:14'),
(11, 1, 5, '2026-11-16', 'Present', NULL, 8, '2026-11-20 02:17:14'),
(12, 2, 5, '2026-11-16', 'Present', NULL, 8, '2026-11-20 02:17:14'),
(13, 3, 5, '2026-11-16', 'Absent', NULL, 8, '2026-11-20 02:17:14'),
(14, 4, 5, '2026-11-16', 'Present', NULL, 8, '2026-11-20 02:17:14'),
(15, 5, 5, '2026-11-16', 'Present', NULL, 8, '2026-11-20 02:17:14'),
(16, 1, 7, '2026-11-16', 'Present', NULL, 9, '2026-11-20 02:17:14'),
(17, 2, 7, '2026-11-16', 'Present', NULL, 9, '2026-11-20 02:17:14'),
(18, 3, 7, '2026-11-16', 'Absent', NULL, 9, '2026-11-20 02:17:14'),
(19, 4, 7, '2026-11-16', 'Present', NULL, 9, '2026-11-20 02:17:14'),
(20, 5, 7, '2026-11-16', 'Present', NULL, 9, '2026-11-20 02:17:14'),
(21, 1, 9, '2026-11-16', 'Present', NULL, 10, '2026-11-20 02:17:14'),
(22, 2, 9, '2026-11-16', 'Present', NULL, 10, '2026-11-20 02:17:14'),
(23, 3, 9, '2026-11-16', 'Absent', NULL, 10, '2026-11-20 02:17:14'),
(24, 4, 9, '2026-11-16', 'Present', NULL, 10, '2026-11-20 02:17:14'),
(25, 5, 9, '2026-11-16', 'Present', NULL, 10, '2026-11-20 02:17:14'),
(26, 6, 11, '2026-11-16', 'Present', NULL, 11, '2026-11-20 02:17:14'),
(27, 7, 11, '2026-11-16', 'Present', NULL, 11, '2026-11-20 02:17:14'),
(28, 8, 11, '2026-11-16', 'Present', NULL, 11, '2026-11-20 02:17:14'),
(29, 9, 11, '2026-11-16', 'Present', NULL, 11, '2026-11-20 02:17:14'),
(30, 10, 11, '2026-11-16', 'Present', NULL, 11, '2026-11-20 02:17:14'),
(31, 6, 13, '2026-11-16', 'Present', NULL, 7, '2026-11-20 02:17:14'),
(32, 7, 13, '2026-11-16', 'Present', NULL, 7, '2026-11-20 02:17:14'),
(33, 8, 13, '2026-11-16', 'Present', NULL, 7, '2026-11-20 02:17:14'),
(34, 9, 13, '2026-11-16', 'Present', NULL, 7, '2026-11-20 02:17:14'),
(35, 10, 13, '2026-11-16', 'Present', NULL, 7, '2026-11-20 02:17:14'),
(36, 6, 15, '2026-11-16', 'Present', NULL, 13, '2026-11-20 02:17:14'),
(37, 7, 15, '2026-11-16', 'Present', NULL, 13, '2026-11-20 02:17:14'),
(38, 8, 15, '2026-11-16', 'Present', NULL, 13, '2026-11-20 02:17:14'),
(39, 9, 15, '2026-11-16', 'Present', NULL, 13, '2026-11-20 02:17:14'),
(40, 10, 15, '2026-11-16', 'Present', NULL, 13, '2026-11-20 02:17:14'),
(41, 6, 17, '2026-11-16', 'Present', NULL, 14, '2026-11-20 02:17:14'),
(42, 7, 17, '2026-11-16', 'Present', NULL, 14, '2026-11-20 02:17:14'),
(43, 8, 17, '2026-11-16', 'Present', NULL, 14, '2026-11-20 02:17:14'),
(44, 9, 17, '2026-11-16', 'Present', NULL, 14, '2026-11-20 02:17:14'),
(45, 10, 17, '2026-11-16', 'Present', NULL, 14, '2026-11-20 02:17:14'),
(46, 6, 19, '2026-11-16', 'Present', NULL, 15, '2026-11-20 02:17:14'),
(47, 7, 19, '2026-11-16', 'Present', NULL, 15, '2026-11-20 02:17:14'),
(48, 8, 19, '2026-11-16', 'Present', NULL, 15, '2026-11-20 02:17:14'),
(49, 9, 19, '2026-11-16', 'Present', NULL, 15, '2026-11-20 02:17:14'),
(50, 10, 19, '2026-11-16', 'Present', NULL, 15, '2026-11-20 02:17:14');

-- --------------------------------------------------------

--
-- Table structure for table `class_sections`
--

CREATE TABLE `class_sections` (
  `section_id` int(11) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `grade_level` enum('Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12') NOT NULL,
  `shs_track` enum('Academic','TechPro') DEFAULT NULL,
  `section_name` varchar(50) NOT NULL,
  `capacity` smallint(5) UNSIGNED NOT NULL,
  `adviser_teacher_id` int(11) DEFAULT NULL,
  `section_status` enum('Open','Full','Closed') NOT NULL DEFAULT 'Open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `class_sections`
--

INSERT INTO `class_sections` (`section_id`, `school_year_id`, `grade_level`, `shs_track`, `section_name`, `capacity`, `adviser_teacher_id`, `section_status`) VALUES
(1, 2, 'Grade 7', NULL, 'Section A', 40, 1, 'Open'),
(2, 2, 'Grade 8', NULL, 'Section B', 40, 2, 'Open'),
(3, 3, 'Grade 11', 'Academic', 'ABM', 12, 1, 'Open');

-- --------------------------------------------------------

--
-- Table structure for table `clinic_visits`
--

CREATE TABLE `clinic_visits` (
  `visit_id` int(11) NOT NULL,
  `student_lrn` varchar(15) NOT NULL,
  `visit_date` datetime NOT NULL,
  `reason` varchar(255) NOT NULL,
  `medicine` varchar(255) DEFAULT NULL,
  `disposition` enum('Stayed in Clinic','Returned to Class','Sent Home') NOT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `recorded_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clinic_visits`
--

INSERT INTO `clinic_visits` (`visit_id`, `student_lrn`, `visit_date`, `reason`, `medicine`, `disposition`, `notes`, `recorded_by`) VALUES
(1, '202600000002', '2026-11-18 10:30:00', 'Headache', 'Water and rest', 'Returned to Class', NULL, 4),
(2, '202600000004', '2026-11-18 10:30:00', 'Headache', 'Water and rest', 'Returned to Class', NULL, 4),
(3, '202600000007', '2026-11-18 10:30:00', 'Headache', 'Water and rest', 'Returned to Class', NULL, 4);

-- --------------------------------------------------------

--
-- Table structure for table `elective_clusters`
--

CREATE TABLE `elective_clusters` (
  `cluster_id` int(11) NOT NULL,
  `cluster_name` varchar(120) NOT NULL,
  `track` enum('Academic','TechPro') NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `student_lrn` varchar(15) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `grade_level` enum('Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12') NOT NULL,
  `enrollment_category` enum('Incoming','Returning','Transferee','Balik-Aral') NOT NULL,
  `shs_track` enum('Academic','TechPro') DEFAULT NULL,
  `last_grade_level_completed` varchar(20) DEFAULT NULL,
  `last_school_attended` varchar(180) DEFAULT NULL,
  `last_school_year_completed` varchar(20) DEFAULT NULL,
  `last_school_id` varchar(30) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `enrollment_date` date NOT NULL,
  `enrollment_status` enum('Pending','Incomplete Requirements','For Verification','Temporarily Enrolled','For Guidance Approval','Officially Enrolled','Rejected','Withdrawn','Completed') NOT NULL DEFAULT 'Pending',
  `final_result` enum('Passed','Failed','INC','DRP/Dropped') DEFAULT NULL,
  `reading_comprehension_status` enum('Not Required','Pending','Passed','Failed') NOT NULL DEFAULT 'Pending',
  `guidance_assessment_status` enum('Not Required','Pending','Passed','Failed') NOT NULL DEFAULT 'Pending',
  `guidance_assessment_date` date DEFAULT NULL,
  `guidance_assessment_remarks` varchar(500) DEFAULT NULL,
  `guidance_assessed_by` int(11) DEFAULT NULL,
  `guidance_approval_status` enum('Not Required','Pending','Approved','Not Approved') NOT NULL DEFAULT 'Pending',
  `guidance_approved_by` int(11) DEFAULT NULL,
  `guidance_approved_at` datetime DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `remarks` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`enrollment_id`, `student_lrn`, `school_year_id`, `grade_level`, `enrollment_category`, `shs_track`, `last_grade_level_completed`, `last_school_attended`, `last_school_year_completed`, `last_school_id`, `section_id`, `enrollment_date`, `enrollment_status`, `final_result`, `reading_comprehension_status`, `guidance_assessment_status`, `guidance_assessment_date`, `guidance_assessment_remarks`, `guidance_assessed_by`, `guidance_approval_status`, `guidance_approved_by`, `guidance_approved_at`, `processed_by`, `remarks`, `created_at`, `updated_at`) VALUES
(1, '202600000001', 2, 'Grade 7', 'Incoming', NULL, NULL, NULL, NULL, NULL, 1, '2026-06-01', 'Officially Enrolled', NULL, 'Passed', 'Passed', NULL, NULL, NULL, 'Approved', NULL, NULL, NULL, NULL, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(2, '202600000002', 2, 'Grade 7', 'Incoming', NULL, NULL, NULL, NULL, NULL, 1, '2026-06-01', 'Officially Enrolled', NULL, 'Passed', 'Passed', NULL, NULL, NULL, 'Approved', NULL, NULL, NULL, NULL, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(3, '202600000003', 2, 'Grade 7', 'Incoming', NULL, NULL, NULL, NULL, NULL, 1, '2026-06-01', 'Officially Enrolled', NULL, 'Passed', 'Passed', NULL, NULL, NULL, 'Approved', NULL, NULL, NULL, NULL, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(4, '202600000004', 2, 'Grade 7', 'Incoming', NULL, NULL, NULL, NULL, NULL, 1, '2026-06-01', 'Officially Enrolled', NULL, 'Passed', 'Passed', NULL, NULL, NULL, 'Approved', NULL, NULL, NULL, NULL, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(5, '202600000005', 2, 'Grade 7', 'Incoming', NULL, NULL, NULL, NULL, NULL, 1, '2026-06-01', 'Officially Enrolled', NULL, 'Passed', 'Passed', NULL, NULL, NULL, 'Approved', NULL, NULL, NULL, NULL, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(6, '202600000006', 2, 'Grade 8', 'Incoming', NULL, NULL, NULL, NULL, NULL, 2, '2026-06-01', 'Officially Enrolled', NULL, 'Passed', 'Passed', NULL, NULL, NULL, 'Approved', NULL, NULL, NULL, NULL, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(7, '202600000007', 2, 'Grade 8', 'Incoming', NULL, NULL, NULL, NULL, NULL, 2, '2026-06-01', 'Officially Enrolled', NULL, 'Passed', 'Passed', NULL, NULL, NULL, 'Approved', NULL, NULL, NULL, NULL, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(8, '202600000008', 2, 'Grade 8', 'Incoming', NULL, NULL, NULL, NULL, NULL, 2, '2026-06-01', 'Officially Enrolled', NULL, 'Passed', 'Passed', NULL, NULL, NULL, 'Approved', NULL, NULL, NULL, NULL, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(9, '202600000009', 2, 'Grade 8', 'Incoming', NULL, NULL, NULL, NULL, NULL, 2, '2026-06-01', 'For Guidance Approval', NULL, 'Pending', 'Pending', NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, NULL, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(10, '202600000010', 2, 'Grade 8', 'Incoming', NULL, NULL, NULL, NULL, NULL, 2, '2026-06-01', 'For Guidance Approval', NULL, 'Pending', 'Pending', NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, NULL, '2026-11-20 02:17:14', '2026-11-20 02:17:14');

-- --------------------------------------------------------

--
-- Table structure for table `enrollment_documents`
--

CREATE TABLE `enrollment_documents` (
  `enrollment_document_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `document_name` varchar(120) NOT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `file_path` varchar(255) DEFAULT NULL,
  `verification_status` enum('Not Submitted','Pending','Accepted','Rejected','Not Required') NOT NULL DEFAULT 'Not Submitted',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollment_documents`
--

INSERT INTO `enrollment_documents` (`enrollment_document_id`, `enrollment_id`, `document_name`, `is_required`, `file_path`, `verification_status`, `verified_by`, `verified_at`, `remarks`) VALUES
(1, 1, 'PSA Birth Certificate', 1, 'storage/student_documents/202600000001/sample_document.png', 'Accepted', NULL, NULL, NULL),
(2, 1, 'Report Card', 1, 'storage/student_documents/202600000001/sample_document.png', 'Accepted', NULL, NULL, NULL),
(3, 1, 'Good Moral Certificate', 1, 'storage/student_documents/202600000001/sample_document.png', 'Accepted', NULL, NULL, NULL),
(4, 2, 'PSA Birth Certificate', 1, 'storage/student_documents/202600000002/sample_document.png', 'Accepted', NULL, NULL, NULL),
(5, 2, 'Report Card', 1, 'storage/student_documents/202600000002/sample_document.png', 'Accepted', NULL, NULL, NULL),
(6, 2, 'Good Moral Certificate', 1, 'storage/student_documents/202600000002/sample_document.png', 'Accepted', NULL, NULL, NULL),
(7, 3, 'PSA Birth Certificate', 1, 'storage/student_documents/202600000003/sample_document.png', 'Accepted', NULL, NULL, NULL),
(8, 3, 'Report Card', 1, 'storage/student_documents/202600000003/sample_document.png', 'Accepted', NULL, NULL, NULL),
(9, 3, 'Good Moral Certificate', 1, 'storage/student_documents/202600000003/sample_document.png', 'Accepted', NULL, NULL, NULL),
(10, 4, 'PSA Birth Certificate', 1, 'storage/student_documents/202600000004/sample_document.png', 'Accepted', NULL, NULL, NULL),
(11, 4, 'Report Card', 1, 'storage/student_documents/202600000004/sample_document.png', 'Accepted', NULL, NULL, NULL),
(12, 4, 'Good Moral Certificate', 1, 'storage/student_documents/202600000004/sample_document.png', 'Accepted', NULL, NULL, NULL),
(13, 5, 'PSA Birth Certificate', 1, 'storage/student_documents/202600000005/sample_document.png', 'Accepted', NULL, NULL, NULL),
(14, 5, 'Report Card', 1, 'storage/student_documents/202600000005/sample_document.png', 'Accepted', NULL, NULL, NULL),
(15, 5, 'Good Moral Certificate', 1, 'storage/student_documents/202600000005/sample_document.png', 'Accepted', NULL, NULL, NULL),
(16, 6, 'PSA Birth Certificate', 1, 'storage/student_documents/202600000006/sample_document.png', 'Accepted', NULL, NULL, NULL),
(17, 6, 'Report Card', 1, 'storage/student_documents/202600000006/sample_document.png', 'Accepted', NULL, NULL, NULL),
(18, 6, 'Good Moral Certificate', 1, 'storage/student_documents/202600000006/sample_document.png', 'Accepted', NULL, NULL, NULL),
(19, 7, 'PSA Birth Certificate', 1, 'storage/student_documents/202600000007/sample_document.png', 'Accepted', NULL, NULL, NULL),
(20, 7, 'Report Card', 1, 'storage/student_documents/202600000007/sample_document.png', 'Accepted', NULL, NULL, NULL),
(21, 7, 'Good Moral Certificate', 1, 'storage/student_documents/202600000007/sample_document.png', 'Accepted', NULL, NULL, NULL),
(22, 8, 'PSA Birth Certificate', 1, 'storage/student_documents/202600000008/sample_document.png', 'Accepted', NULL, NULL, NULL),
(23, 8, 'Report Card', 1, 'storage/student_documents/202600000008/sample_document.png', 'Accepted', NULL, NULL, NULL),
(24, 8, 'Good Moral Certificate', 1, 'storage/student_documents/202600000008/sample_document.png', 'Accepted', NULL, NULL, NULL),
(25, 9, 'PSA Birth Certificate', 1, 'storage/student_documents/202600000009/sample_document.png', 'Accepted', NULL, NULL, NULL),
(26, 9, 'Report Card', 1, 'storage/student_documents/202600000009/sample_document.png', 'Accepted', NULL, NULL, NULL),
(27, 9, 'Good Moral Certificate', 1, 'storage/student_documents/202600000009/sample_document.png', 'Accepted', NULL, NULL, NULL),
(28, 10, 'PSA Birth Certificate', 1, 'storage/student_documents/202600000010/sample_document.png', 'Accepted', NULL, NULL, NULL),
(29, 10, 'Report Card', 1, 'storage/student_documents/202600000010/sample_document.png', 'Accepted', NULL, NULL, NULL),
(30, 10, 'Good Moral Certificate', 1, 'storage/student_documents/202600000010/sample_document.png', 'Accepted', NULL, NULL, NULL),
(31, 10, 'SF9 / Report Card', 1, NULL, 'Not Submitted', NULL, NULL, NULL),
(32, 10, 'Certificate of Good Moral Character', 1, NULL, 'Not Submitted', NULL, NULL, NULL),
(33, 10, 'Certificate of Transfer / Form 137', 0, NULL, 'Not Submitted', NULL, NULL, NULL),
(34, 10, '2x2 ID Picture', 0, NULL, 'Not Submitted', NULL, NULL, NULL),
(35, 7, 'SF9 / Report Card', 1, NULL, 'Not Submitted', NULL, NULL, NULL),
(36, 7, 'Certificate of Good Moral Character', 1, NULL, 'Not Submitted', NULL, NULL, NULL),
(37, 7, 'Certificate of Transfer / Form 137', 0, NULL, 'Not Submitted', NULL, NULL, NULL),
(38, 7, '2x2 ID Picture', 0, NULL, 'Not Submitted', NULL, NULL, NULL),
(39, 8, 'SF9 / Report Card', 1, NULL, 'Not Submitted', NULL, NULL, NULL),
(40, 8, 'Certificate of Good Moral Character', 1, NULL, 'Not Submitted', NULL, NULL, NULL),
(41, 8, 'Certificate of Transfer / Form 137', 0, NULL, 'Not Submitted', NULL, NULL, NULL),
(42, 8, '2x2 ID Picture', 0, NULL, 'Not Submitted', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `grade_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `section_subject_id` int(11) NOT NULL,
  `grading_period` enum('Prelim','Midterm','Finals','Term 1','Term 2','Term 3') NOT NULL,
  `grade_value` decimal(5,2) DEFAULT NULL,
  `remarks` varchar(100) DEFAULT NULL,
  `result_status` enum('Passed','Failed','INC','DRP/Dropped') DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `encoded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`grade_id`, `enrollment_id`, `section_subject_id`, `grading_period`, `grade_value`, `remarks`, `result_status`, `encoded_by`, `encoded_at`, `updated_at`) VALUES
(1, 1, 1, 'Term 1', 84.00, NULL, NULL, 6, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(2, 1, 1, 'Term 2', 82.00, NULL, NULL, 6, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(3, 1, 1, 'Term 3', 78.00, NULL, 'Passed', 6, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(4, 2, 1, 'Term 1', 85.00, NULL, NULL, 6, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(5, 2, 1, 'Term 2', 83.00, NULL, NULL, 6, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(6, 2, 1, 'Term 3', 79.00, NULL, 'Passed', 6, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(7, 3, 1, 'Term 1', 86.00, NULL, NULL, 6, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(8, 3, 1, 'Term 2', 84.00, NULL, NULL, 6, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(9, 3, 1, 'Term 3', 80.00, NULL, 'Passed', 6, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(10, 4, 1, 'Term 1', 87.00, NULL, NULL, 6, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(11, 4, 1, 'Term 2', 85.00, NULL, NULL, 6, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(12, 4, 1, 'Term 3', 81.00, NULL, 'Passed', 6, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(13, 5, 1, 'Term 1', 88.00, NULL, NULL, 6, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(14, 5, 1, 'Term 2', 86.00, NULL, NULL, 6, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(15, 5, 1, 'Term 3', 82.00, NULL, 'Passed', 6, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(16, 1, 3, 'Term 1', 85.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(17, 1, 3, 'Term 2', 83.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(18, 1, 3, 'Term 3', 79.00, NULL, 'Passed', 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(19, 2, 3, 'Term 1', 86.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(20, 2, 3, 'Term 2', 84.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(21, 2, 3, 'Term 3', 80.00, NULL, 'Passed', 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(22, 3, 3, 'Term 1', 87.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(23, 3, 3, 'Term 2', 85.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(24, 3, 3, 'Term 3', 81.00, NULL, 'Passed', 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(25, 4, 3, 'Term 1', 88.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(26, 4, 3, 'Term 2', 86.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(27, 4, 3, 'Term 3', 82.00, NULL, 'Passed', 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(28, 5, 3, 'Term 1', 89.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(29, 5, 3, 'Term 2', 87.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(30, 5, 3, 'Term 3', 83.00, NULL, 'Passed', 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(31, 1, 5, 'Term 1', 86.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(32, 1, 5, 'Term 2', 84.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(33, 1, 5, 'Term 3', 80.00, NULL, 'Passed', 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(34, 2, 5, 'Term 1', 87.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(35, 2, 5, 'Term 2', 85.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(36, 2, 5, 'Term 3', 81.00, NULL, 'Passed', 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(37, 3, 5, 'Term 1', 88.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(38, 3, 5, 'Term 2', 86.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(39, 3, 5, 'Term 3', 82.00, NULL, 'Passed', 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(40, 4, 5, 'Term 1', 89.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(41, 4, 5, 'Term 2', 87.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(42, 4, 5, 'Term 3', 83.00, NULL, 'Passed', 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(43, 5, 5, 'Term 1', 90.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(44, 5, 5, 'Term 2', 88.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(45, 5, 5, 'Term 3', 84.00, NULL, 'Passed', 8, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(46, 1, 7, 'Term 1', 87.00, NULL, NULL, 9, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(47, 1, 7, 'Term 2', 85.00, NULL, NULL, 9, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(48, 1, 7, 'Term 3', 81.00, NULL, 'Passed', 9, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(49, 2, 7, 'Term 1', 88.00, NULL, NULL, 9, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(50, 2, 7, 'Term 2', 86.00, NULL, NULL, 9, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(51, 2, 7, 'Term 3', 82.00, NULL, 'Passed', 9, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(52, 3, 7, 'Term 1', 89.00, NULL, NULL, 9, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(53, 3, 7, 'Term 2', 87.00, NULL, NULL, 9, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(54, 3, 7, 'Term 3', 83.00, NULL, 'Passed', 9, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(55, 4, 7, 'Term 1', 90.00, NULL, NULL, 9, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(56, 4, 7, 'Term 2', 88.00, NULL, NULL, 9, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(57, 4, 7, 'Term 3', 84.00, NULL, 'Passed', 9, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(58, 5, 7, 'Term 1', 91.00, NULL, NULL, 9, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(59, 5, 7, 'Term 2', 89.00, NULL, NULL, 9, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(60, 5, 7, 'Term 3', 85.00, NULL, 'Passed', 9, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(61, 1, 9, 'Term 1', 88.00, NULL, NULL, 10, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(62, 1, 9, 'Term 2', 86.00, NULL, NULL, 10, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(63, 1, 9, 'Term 3', 82.00, NULL, 'Passed', 10, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(64, 2, 9, 'Term 1', 89.00, NULL, NULL, 10, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(65, 2, 9, 'Term 2', 87.00, NULL, NULL, 10, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(66, 2, 9, 'Term 3', 83.00, NULL, 'Passed', 10, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(67, 3, 9, 'Term 1', 90.00, NULL, NULL, 10, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(68, 3, 9, 'Term 2', 88.00, NULL, NULL, 10, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(69, 3, 9, 'Term 3', 84.00, NULL, 'Passed', 10, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(70, 4, 9, 'Term 1', 91.00, NULL, NULL, 10, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(71, 4, 9, 'Term 2', 89.00, NULL, NULL, 10, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(72, 4, 9, 'Term 3', 85.00, NULL, 'Passed', 10, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(73, 5, 9, 'Term 1', 92.00, NULL, NULL, 10, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(74, 5, 9, 'Term 2', 90.00, NULL, NULL, 10, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(75, 5, 9, 'Term 3', 86.00, NULL, 'Passed', 10, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(76, 6, 11, 'Term 1', 89.00, NULL, NULL, 11, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(77, 6, 11, 'Term 2', 87.00, NULL, NULL, 11, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(78, 6, 11, 'Term 3', 83.00, NULL, 'Passed', 11, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(79, 7, 11, 'Term 1', 90.00, NULL, NULL, 11, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(80, 7, 11, 'Term 2', 88.00, NULL, NULL, 11, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(81, 7, 11, 'Term 3', 84.00, NULL, 'Passed', 11, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(82, 8, 11, 'Term 1', 91.00, NULL, NULL, 11, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(83, 8, 11, 'Term 2', 89.00, NULL, NULL, 11, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(84, 8, 11, 'Term 3', 85.00, NULL, 'Passed', 11, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(85, 9, 11, 'Term 1', 92.00, NULL, NULL, 11, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(86, 9, 11, 'Term 2', 90.00, NULL, NULL, 11, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(87, 9, 11, 'Term 3', 86.00, NULL, 'Passed', 11, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(88, 10, 11, 'Term 1', 84.00, NULL, NULL, 11, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(89, 10, 11, 'Term 2', 91.00, NULL, NULL, 11, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(90, 10, 11, 'Term 3', 87.00, NULL, 'Passed', 11, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(91, 6, 13, 'Term 1', 90.00, NULL, NULL, 7, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(92, 6, 13, 'Term 2', 88.00, NULL, NULL, 7, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(93, 6, 13, 'Term 3', 84.00, NULL, 'Passed', 7, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(94, 7, 13, 'Term 1', 91.00, NULL, NULL, 7, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(95, 7, 13, 'Term 2', 89.00, NULL, NULL, 7, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(96, 7, 13, 'Term 3', 85.00, NULL, 'Passed', 7, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(97, 8, 13, 'Term 1', 92.00, NULL, NULL, 7, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(98, 8, 13, 'Term 2', 90.00, NULL, NULL, 7, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(99, 8, 13, 'Term 3', 86.00, NULL, 'Passed', 7, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(100, 9, 13, 'Term 1', 84.00, NULL, NULL, 7, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(101, 9, 13, 'Term 2', 91.00, NULL, NULL, 7, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(102, 9, 13, 'Term 3', 87.00, NULL, 'Passed', 7, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(103, 10, 13, 'Term 1', 85.00, NULL, NULL, 7, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(104, 10, 13, 'Term 2', 92.00, NULL, NULL, 7, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(105, 10, 13, 'Term 3', 88.00, NULL, 'Passed', 7, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(106, 6, 15, 'Term 1', 91.00, NULL, NULL, 13, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(107, 6, 15, 'Term 2', 89.00, NULL, NULL, 13, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(108, 6, 15, 'Term 3', 85.00, NULL, 'Passed', 13, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(109, 7, 15, 'Term 1', 92.00, NULL, NULL, 13, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(110, 7, 15, 'Term 2', 90.00, NULL, NULL, 13, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(111, 7, 15, 'Term 3', 86.00, NULL, 'Passed', 13, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(112, 8, 15, 'Term 1', 84.00, NULL, NULL, 13, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(113, 8, 15, 'Term 2', 91.00, NULL, NULL, 13, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(114, 8, 15, 'Term 3', 87.00, NULL, 'Passed', 13, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(115, 9, 15, 'Term 1', 85.00, NULL, NULL, 13, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(116, 9, 15, 'Term 2', 92.00, NULL, NULL, 13, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(117, 9, 15, 'Term 3', 88.00, NULL, 'Passed', 13, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(118, 10, 15, 'Term 1', 86.00, NULL, NULL, 13, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(119, 10, 15, 'Term 2', 82.00, NULL, NULL, 13, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(120, 10, 15, 'Term 3', 89.00, NULL, 'Passed', 13, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(121, 6, 17, 'Term 1', 92.00, NULL, NULL, 14, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(122, 6, 17, 'Term 2', 90.00, NULL, NULL, 14, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(123, 6, 17, 'Term 3', 86.00, NULL, 'Passed', 14, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(124, 7, 17, 'Term 1', 84.00, NULL, NULL, 14, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(125, 7, 17, 'Term 2', 91.00, NULL, NULL, 14, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(126, 7, 17, 'Term 3', 87.00, NULL, 'Passed', 14, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(127, 8, 17, 'Term 1', 85.00, NULL, NULL, 14, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(128, 8, 17, 'Term 2', 92.00, NULL, NULL, 14, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(129, 8, 17, 'Term 3', 88.00, NULL, 'Passed', 14, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(130, 9, 17, 'Term 1', 86.00, NULL, NULL, 14, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(131, 9, 17, 'Term 2', 82.00, NULL, NULL, 14, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(132, 9, 17, 'Term 3', 89.00, NULL, 'Passed', 14, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(133, 10, 17, 'Term 1', 87.00, NULL, NULL, 14, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(134, 10, 17, 'Term 2', 83.00, NULL, NULL, 14, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(135, 10, 17, 'Term 3', 90.00, NULL, 'Passed', 14, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(136, 6, 19, 'Term 1', 84.00, NULL, NULL, 15, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(137, 6, 19, 'Term 2', 91.00, NULL, NULL, 15, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(138, 6, 19, 'Term 3', 87.00, NULL, 'Passed', 15, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(139, 7, 19, 'Term 1', 85.00, NULL, NULL, 15, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(140, 7, 19, 'Term 2', 92.00, NULL, NULL, 15, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(141, 7, 19, 'Term 3', 88.00, NULL, 'Passed', 15, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(142, 8, 19, 'Term 1', 86.00, NULL, NULL, 15, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(143, 8, 19, 'Term 2', 82.00, NULL, NULL, 15, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(144, 8, 19, 'Term 3', 89.00, NULL, 'Passed', 15, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(145, 9, 19, 'Term 1', 87.00, NULL, NULL, 15, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(146, 9, 19, 'Term 2', 83.00, NULL, NULL, 15, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(147, 9, 19, 'Term 3', 90.00, NULL, 'Passed', 15, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(148, 10, 19, 'Term 1', 88.00, NULL, NULL, 15, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(149, 10, 19, 'Term 2', 84.00, NULL, NULL, 15, '2026-11-20 02:17:14', '2026-11-22 11:17:13'),
(150, 10, 19, 'Term 3', 91.00, NULL, 'Passed', 15, '2026-11-20 02:17:14', '2026-11-22 11:17:13');

-- --------------------------------------------------------

--
-- Table structure for table `grade_period_locks`
--

CREATE TABLE `grade_period_locks` (
  `lock_id` int(11) NOT NULL,
  `section_subject_id` int(11) NOT NULL,
  `grading_period` enum('Prelim','Midterm','Finals','Term 1','Term 2','Term 3') NOT NULL,
  `locked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `locked_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `guidance_records`
--

CREATE TABLE `guidance_records` (
  `guide_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `reading_status` enum('Pending','Passed','Failed') NOT NULL DEFAULT 'Pending',
  `interview_status` enum('Pending','Passed','Failed') NOT NULL DEFAULT 'Pending',
  `notes` varchar(500) DEFAULT NULL,
  `assessed_by` int(11) NOT NULL,
  `assessed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `guidance_records`
--

INSERT INTO `guidance_records` (`guide_id`, `enrollment_id`, `reading_status`, `interview_status`, `notes`, `assessed_by`, `assessed_at`) VALUES
(1, 1, 'Passed', 'Passed', 'Demo assessment completed', 3, '2026-11-20 10:17:14'),
(2, 2, 'Passed', 'Passed', 'Demo assessment completed', 3, '2026-11-20 10:17:14'),
(3, 3, 'Passed', 'Passed', 'Demo assessment completed', 3, '2026-11-20 10:17:14'),
(4, 4, 'Passed', 'Passed', 'Demo assessment completed', 3, '2026-11-20 10:17:14'),
(5, 5, 'Passed', 'Passed', 'Demo assessment completed', 3, '2026-11-20 10:17:14'),
(6, 6, 'Passed', 'Passed', 'Demo assessment completed', 3, '2026-11-20 10:17:14'),
(7, 7, 'Passed', 'Passed', 'Demo assessment completed', 3, '2026-11-20 10:17:14'),
(8, 8, 'Passed', 'Passed', 'Demo assessment completed', 3, '2026-11-20 10:17:14');

-- --------------------------------------------------------

--
-- Table structure for table `learner_assessments`
--

CREATE TABLE `learner_assessments` (
  `assessment_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `assessment_type` enum('Reading','Numeracy') NOT NULL,
  `assessment_status` enum('Pending','Passed','Failed') NOT NULL DEFAULT 'Pending',
  `assessed_by` int(11) NOT NULL,
  `assessed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `notes` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `learner_movements`
--

CREATE TABLE `learner_movements` (
  `movement_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `movement_type` enum('Transferred In','Transferred Out','Dropped Out') NOT NULL,
  `movement_date` date NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migration_grades_before_terms`
--

CREATE TABLE `migration_grades_before_terms` (
  `grade_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `section_subject_id` int(11) NOT NULL,
  `grading_period` enum('Prelim','Midterm','Finals','Term 1','Term 2','Term 3') NOT NULL,
  `grade_value` decimal(5,2) DEFAULT NULL,
  `remarks` varchar(100) DEFAULT NULL,
  `result_status` enum('Passed','Failed','INC','DRP/Dropped') DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `encoded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migration_grades_before_terms`
--

INSERT INTO `migration_grades_before_terms` (`grade_id`, `enrollment_id`, `section_subject_id`, `grading_period`, `grade_value`, `remarks`, `result_status`, `encoded_by`, `encoded_at`, `updated_at`) VALUES
(1, 1, 1, 'Prelim', 84.00, NULL, NULL, 6, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(2, 1, 1, 'Midterm', 82.00, NULL, NULL, 6, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(3, 1, 1, 'Finals', 78.00, NULL, 'Passed', 6, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(4, 2, 1, 'Prelim', 85.00, NULL, NULL, 6, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(5, 2, 1, 'Midterm', 83.00, NULL, NULL, 6, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(6, 2, 1, 'Finals', 79.00, NULL, 'Passed', 6, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(7, 3, 1, 'Prelim', 86.00, NULL, NULL, 6, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(8, 3, 1, 'Midterm', 84.00, NULL, NULL, 6, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(9, 3, 1, 'Finals', 80.00, NULL, 'Passed', 6, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(10, 4, 1, 'Prelim', 87.00, NULL, NULL, 6, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(11, 4, 1, 'Midterm', 85.00, NULL, NULL, 6, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(12, 4, 1, 'Finals', 81.00, NULL, 'Passed', 6, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(13, 5, 1, 'Prelim', 88.00, NULL, NULL, 6, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(14, 5, 1, 'Midterm', 86.00, NULL, NULL, 6, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(15, 5, 1, 'Finals', 82.00, NULL, 'Passed', 6, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(16, 1, 3, 'Prelim', 85.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(17, 1, 3, 'Midterm', 83.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(18, 1, 3, 'Finals', 79.00, NULL, 'Passed', 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(19, 2, 3, 'Prelim', 86.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(20, 2, 3, 'Midterm', 84.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(21, 2, 3, 'Finals', 80.00, NULL, 'Passed', 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(22, 3, 3, 'Prelim', 87.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(23, 3, 3, 'Midterm', 85.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(24, 3, 3, 'Finals', 81.00, NULL, 'Passed', 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(25, 4, 3, 'Prelim', 88.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(26, 4, 3, 'Midterm', 86.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(27, 4, 3, 'Finals', 82.00, NULL, 'Passed', 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(28, 5, 3, 'Prelim', 89.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(29, 5, 3, 'Midterm', 87.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(30, 5, 3, 'Finals', 83.00, NULL, 'Passed', 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(31, 1, 5, 'Prelim', 86.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(32, 1, 5, 'Midterm', 84.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(33, 1, 5, 'Finals', 80.00, NULL, 'Passed', 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(34, 2, 5, 'Prelim', 87.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(35, 2, 5, 'Midterm', 85.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(36, 2, 5, 'Finals', 81.00, NULL, 'Passed', 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(37, 3, 5, 'Prelim', 88.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(38, 3, 5, 'Midterm', 86.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(39, 3, 5, 'Finals', 82.00, NULL, 'Passed', 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(40, 4, 5, 'Prelim', 89.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(41, 4, 5, 'Midterm', 87.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(42, 4, 5, 'Finals', 83.00, NULL, 'Passed', 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(43, 5, 5, 'Prelim', 90.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(44, 5, 5, 'Midterm', 88.00, NULL, NULL, 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(45, 5, 5, 'Finals', 84.00, NULL, 'Passed', 8, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(46, 1, 7, 'Prelim', 87.00, NULL, NULL, 9, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(47, 1, 7, 'Midterm', 85.00, NULL, NULL, 9, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(48, 1, 7, 'Finals', 81.00, NULL, 'Passed', 9, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(49, 2, 7, 'Prelim', 88.00, NULL, NULL, 9, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(50, 2, 7, 'Midterm', 86.00, NULL, NULL, 9, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(51, 2, 7, 'Finals', 82.00, NULL, 'Passed', 9, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(52, 3, 7, 'Prelim', 89.00, NULL, NULL, 9, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(53, 3, 7, 'Midterm', 87.00, NULL, NULL, 9, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(54, 3, 7, 'Finals', 83.00, NULL, 'Passed', 9, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(55, 4, 7, 'Prelim', 90.00, NULL, NULL, 9, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(56, 4, 7, 'Midterm', 88.00, NULL, NULL, 9, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(57, 4, 7, 'Finals', 84.00, NULL, 'Passed', 9, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(58, 5, 7, 'Prelim', 91.00, NULL, NULL, 9, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(59, 5, 7, 'Midterm', 89.00, NULL, NULL, 9, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(60, 5, 7, 'Finals', 85.00, NULL, 'Passed', 9, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(61, 1, 9, 'Prelim', 88.00, NULL, NULL, 10, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(62, 1, 9, 'Midterm', 86.00, NULL, NULL, 10, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(63, 1, 9, 'Finals', 82.00, NULL, 'Passed', 10, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(64, 2, 9, 'Prelim', 89.00, NULL, NULL, 10, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(65, 2, 9, 'Midterm', 87.00, NULL, NULL, 10, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(66, 2, 9, 'Finals', 83.00, NULL, 'Passed', 10, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(67, 3, 9, 'Prelim', 90.00, NULL, NULL, 10, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(68, 3, 9, 'Midterm', 88.00, NULL, NULL, 10, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(69, 3, 9, 'Finals', 84.00, NULL, 'Passed', 10, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(70, 4, 9, 'Prelim', 91.00, NULL, NULL, 10, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(71, 4, 9, 'Midterm', 89.00, NULL, NULL, 10, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(72, 4, 9, 'Finals', 85.00, NULL, 'Passed', 10, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(73, 5, 9, 'Prelim', 92.00, NULL, NULL, 10, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(74, 5, 9, 'Midterm', 90.00, NULL, NULL, 10, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(75, 5, 9, 'Finals', 86.00, NULL, 'Passed', 10, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(76, 6, 11, 'Prelim', 89.00, NULL, NULL, 11, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(77, 6, 11, 'Midterm', 87.00, NULL, NULL, 11, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(78, 6, 11, 'Finals', 83.00, NULL, 'Passed', 11, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(79, 7, 11, 'Prelim', 90.00, NULL, NULL, 11, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(80, 7, 11, 'Midterm', 88.00, NULL, NULL, 11, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(81, 7, 11, 'Finals', 84.00, NULL, 'Passed', 11, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(82, 8, 11, 'Prelim', 91.00, NULL, NULL, 11, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(83, 8, 11, 'Midterm', 89.00, NULL, NULL, 11, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(84, 8, 11, 'Finals', 85.00, NULL, 'Passed', 11, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(85, 9, 11, 'Prelim', 92.00, NULL, NULL, 11, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(86, 9, 11, 'Midterm', 90.00, NULL, NULL, 11, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(87, 9, 11, 'Finals', 86.00, NULL, 'Passed', 11, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(88, 10, 11, 'Prelim', 84.00, NULL, NULL, 11, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(89, 10, 11, 'Midterm', 91.00, NULL, NULL, 11, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(90, 10, 11, 'Finals', 87.00, NULL, 'Passed', 11, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(91, 6, 13, 'Prelim', 90.00, NULL, NULL, 7, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(92, 6, 13, 'Midterm', 88.00, NULL, NULL, 7, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(93, 6, 13, 'Finals', 84.00, NULL, 'Passed', 7, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(94, 7, 13, 'Prelim', 91.00, NULL, NULL, 7, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(95, 7, 13, 'Midterm', 89.00, NULL, NULL, 7, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(96, 7, 13, 'Finals', 85.00, NULL, 'Passed', 7, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(97, 8, 13, 'Prelim', 92.00, NULL, NULL, 7, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(98, 8, 13, 'Midterm', 90.00, NULL, NULL, 7, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(99, 8, 13, 'Finals', 86.00, NULL, 'Passed', 7, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(100, 9, 13, 'Prelim', 84.00, NULL, NULL, 7, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(101, 9, 13, 'Midterm', 91.00, NULL, NULL, 7, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(102, 9, 13, 'Finals', 87.00, NULL, 'Passed', 7, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(103, 10, 13, 'Prelim', 85.00, NULL, NULL, 7, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(104, 10, 13, 'Midterm', 92.00, NULL, NULL, 7, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(105, 10, 13, 'Finals', 88.00, NULL, 'Passed', 7, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(106, 6, 15, 'Prelim', 91.00, NULL, NULL, 13, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(107, 6, 15, 'Midterm', 89.00, NULL, NULL, 13, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(108, 6, 15, 'Finals', 85.00, NULL, 'Passed', 13, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(109, 7, 15, 'Prelim', 92.00, NULL, NULL, 13, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(110, 7, 15, 'Midterm', 90.00, NULL, NULL, 13, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(111, 7, 15, 'Finals', 86.00, NULL, 'Passed', 13, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(112, 8, 15, 'Prelim', 84.00, NULL, NULL, 13, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(113, 8, 15, 'Midterm', 91.00, NULL, NULL, 13, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(114, 8, 15, 'Finals', 87.00, NULL, 'Passed', 13, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(115, 9, 15, 'Prelim', 85.00, NULL, NULL, 13, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(116, 9, 15, 'Midterm', 92.00, NULL, NULL, 13, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(117, 9, 15, 'Finals', 88.00, NULL, 'Passed', 13, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(118, 10, 15, 'Prelim', 86.00, NULL, NULL, 13, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(119, 10, 15, 'Midterm', 82.00, NULL, NULL, 13, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(120, 10, 15, 'Finals', 89.00, NULL, 'Passed', 13, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(121, 6, 17, 'Prelim', 92.00, NULL, NULL, 14, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(122, 6, 17, 'Midterm', 90.00, NULL, NULL, 14, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(123, 6, 17, 'Finals', 86.00, NULL, 'Passed', 14, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(124, 7, 17, 'Prelim', 84.00, NULL, NULL, 14, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(125, 7, 17, 'Midterm', 91.00, NULL, NULL, 14, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(126, 7, 17, 'Finals', 87.00, NULL, 'Passed', 14, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(127, 8, 17, 'Prelim', 85.00, NULL, NULL, 14, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(128, 8, 17, 'Midterm', 92.00, NULL, NULL, 14, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(129, 8, 17, 'Finals', 88.00, NULL, 'Passed', 14, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(130, 9, 17, 'Prelim', 86.00, NULL, NULL, 14, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(131, 9, 17, 'Midterm', 82.00, NULL, NULL, 14, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(132, 9, 17, 'Finals', 89.00, NULL, 'Passed', 14, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(133, 10, 17, 'Prelim', 87.00, NULL, NULL, 14, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(134, 10, 17, 'Midterm', 83.00, NULL, NULL, 14, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(135, 10, 17, 'Finals', 90.00, NULL, 'Passed', 14, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(136, 6, 19, 'Prelim', 84.00, NULL, NULL, 15, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(137, 6, 19, 'Midterm', 91.00, NULL, NULL, 15, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(138, 6, 19, 'Finals', 87.00, NULL, 'Passed', 15, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(139, 7, 19, 'Prelim', 85.00, NULL, NULL, 15, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(140, 7, 19, 'Midterm', 92.00, NULL, NULL, 15, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(141, 7, 19, 'Finals', 88.00, NULL, 'Passed', 15, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(142, 8, 19, 'Prelim', 86.00, NULL, NULL, 15, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(143, 8, 19, 'Midterm', 82.00, NULL, NULL, 15, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(144, 8, 19, 'Finals', 89.00, NULL, 'Passed', 15, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(145, 9, 19, 'Prelim', 87.00, NULL, NULL, 15, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(146, 9, 19, 'Midterm', 83.00, NULL, NULL, 15, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(147, 9, 19, 'Finals', 90.00, NULL, 'Passed', 15, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(148, 10, 19, 'Prelim', 88.00, NULL, NULL, 15, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(149, 10, 19, 'Midterm', 84.00, NULL, NULL, 15, '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
(150, 10, 19, 'Finals', 91.00, NULL, 'Passed', 15, '2026-11-20 02:17:14', '2026-11-20 02:17:14');

-- --------------------------------------------------------

--
-- Table structure for table `official_daily_attendance`
--

CREATE TABLE `official_daily_attendance` (
  `attendance_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `attendance_status` enum('Present','Absent','Tardy','Excused') NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_requests`
--

CREATE TABLE `password_reset_requests` (
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_status` enum('Pending','Completed','Rejected') NOT NULL DEFAULT 'Pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `registrar_note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reenrollment_requests`
--

CREATE TABLE `reenrollment_requests` (
  `request_id` int(11) NOT NULL,
  `student_lrn` varchar(15) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `request_status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reenrollment_requests`
--

INSERT INTO `reenrollment_requests` (`request_id`, `student_lrn`, `school_year_id`, `requested_by`, `request_status`, `reviewed_by`, `reviewed_at`, `review_notes`, `created_at`) VALUES
(1, '202600000001', 4, 2, 'Pending', NULL, NULL, NULL, '2026-11-22 18:25:12');

-- --------------------------------------------------------

--
-- Table structure for table `scholarship_applications`
--

CREATE TABLE `scholarship_applications` (
  `application_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `student_lrn` varchar(15) NOT NULL,
  `application_date` date NOT NULL,
  `application_status` enum('Pending','For Verification','Verified','Submitted to Provider','Approved','Rejected','Withdrawn') NOT NULL DEFAULT 'Pending',
  `decision_date` date DEFAULT NULL,
  `decision_reference` varchar(100) DEFAULT NULL,
  `remarks` varchar(500) DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scholarship_application_documents`
--

CREATE TABLE `scholarship_application_documents` (
  `application_document_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `document_name` varchar(120) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `verification_status` enum('Not Submitted','Pending','Accepted','Rejected','Not Required') NOT NULL DEFAULT 'Not Submitted',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scholarship_awards`
--

CREATE TABLE `scholarship_awards` (
  `award_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `award_start_date` date DEFAULT NULL,
  `award_end_date` date DEFAULT NULL,
  `award_status` enum('Active','For Renewal','Renewed','Completed','Discontinued') NOT NULL DEFAULT 'Active',
  `renewal_eligibility` enum('Pending','Eligible','Not Eligible','Not Applicable') NOT NULL DEFAULT 'Pending',
  `academic_performance_remarks` varchar(500) DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scholarship_programs`
--

CREATE TABLE `scholarship_programs` (
  `program_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `program_name` varchar(160) NOT NULL,
  `description` text DEFAULT NULL,
  `eligibility_criteria` text DEFAULT NULL,
  `required_documents` text DEFAULT NULL,
  `benefits` text DEFAULT NULL,
  `available_slots` smallint(5) UNSIGNED DEFAULT NULL,
  `application_start` date DEFAULT NULL,
  `application_deadline` date DEFAULT NULL,
  `application_method` enum('School Managed','External Provider') NOT NULL,
  `external_application_url` varchar(255) DEFAULT NULL,
  `program_status` enum('Draft','Published','Closed','Archived') NOT NULL DEFAULT 'Draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `scholarship_programs`
--

INSERT INTO `scholarship_programs` (`program_id`, `provider_id`, `program_name`, `description`, `eligibility_criteria`, `required_documents`, `benefits`, `available_slots`, `application_start`, `application_deadline`, `application_method`, `external_application_url`, `program_status`, `created_by`, `created_at`) VALUES
(1, 1, 'DEMO: Learner Assistance', 'Fictional program for demonstration', 'Enrolled students', NULL, 'School supply support', 20, '2026-11-01', '2027-03-01', 'School Managed', NULL, 'Published', 5, '2026-11-20 02:17:14');

-- --------------------------------------------------------

--
-- Table structure for table `scholarship_providers`
--

CREATE TABLE `scholarship_providers` (
  `provider_id` int(11) NOT NULL,
  `provider_name` varchar(160) NOT NULL,
  `provider_type` varchar(80) DEFAULT NULL,
  `contact_person` varchar(120) DEFAULT NULL,
  `contact_number` varchar(30) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `provider_status` enum('Active','Inactive','Resigned') NOT NULL DEFAULT 'Active',
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `scholarship_providers`
--

INSERT INTO `scholarship_providers` (`provider_id`, `provider_name`, `provider_type`, `contact_person`, `contact_number`, `email`, `website_url`, `provider_status`, `created_by`) VALUES
(1, 'Demo Local Scholarship', 'Community', NULL, NULL, NULL, NULL, 'Active', 5);

-- --------------------------------------------------------

--
-- Table structure for table `school_years`
--

CREATE TABLE `school_years` (
  `school_year_id` int(11) NOT NULL,
  `school_year_name` varchar(20) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `year_status` enum('Current','Completed') NOT NULL DEFAULT 'Completed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `school_years`
--

INSERT INTO `school_years` (`school_year_id`, `school_year_name`, `start_date`, `end_date`, `year_status`) VALUES
(1, '2025-2026', '2025-06-02', '2026-03-31', 'Completed'),
(2, '2026-2027', '2026-06-01', '2027-03-31', 'Completed'),
(3, '2027-2028', NULL, NULL, 'Completed'),
(4, '2028-2029', NULL, NULL, 'Current');

-- --------------------------------------------------------

--
-- Table structure for table `section_subjects`
--

CREATE TABLE `section_subjects` (
  `section_subject_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `schedule_days` varchar(40) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `room` varchar(40) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `section_subjects`
--

INSERT INTO `section_subjects` (`section_subject_id`, `section_id`, `subject_id`, `teacher_id`, `schedule_days`, `start_time`, `end_time`, `room`) VALUES
(1, 1, 1, 1, 'Mon-Fri', '08:00:00', '09:00:00', 'Room 701'),
(3, 1, 2, 3, 'Mon-Fri', '09:00:00', '10:00:00', 'Room 701'),
(5, 1, 3, 3, 'Mon-Fri', '10:00:00', '11:00:00', 'Room 701'),
(7, 1, 4, 4, 'Mon-Fri', '11:00:00', '12:00:00', 'Room 701'),
(9, 1, 5, 5, 'Mon-Fri', '12:00:00', '13:00:00', 'Room 701'),
(11, 2, 6, 6, 'Mon-Fri', '08:00:00', '09:00:00', 'Room 801'),
(13, 2, 7, 2, 'Mon-Fri', '09:00:00', '10:00:00', 'Room 801'),
(15, 2, 8, 8, 'Mon-Fri', '10:00:00', '11:00:00', 'Room 801'),
(17, 2, 9, 9, 'Mon-Fri', '11:00:00', '12:00:00', 'Room 801'),
(19, 2, 10, 10, 'Mon-Fri', '12:00:00', '13:00:00', 'Room 801');

-- --------------------------------------------------------

--
-- Table structure for table `staff_profiles`
--

CREATE TABLE `staff_profiles` (
  `user_id` int(11) NOT NULL,
  `employee_number` varchar(30) NOT NULL,
  `first_name` varchar(60) NOT NULL,
  `middle_name` varchar(60) DEFAULT NULL,
  `last_name` varchar(60) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `employment_status` enum('Active','Inactive','Resigned') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_profiles`
--

INSERT INTO `staff_profiles` (`user_id`, `employee_number`, `first_name`, `middle_name`, `last_name`, `address`, `contact_number`, `email`, `employment_status`, `created_at`) VALUES
(1, 'ADMIN-01', 'Alex', NULL, 'Rivera', NULL, NULL, NULL, 'Active', '2026-11-20 02:17:14'),
(2, 'REG-01', 'Maria', NULL, 'Santos', NULL, NULL, NULL, 'Active', '2026-11-20 02:17:14'),
(3, 'GUID-01', 'Carla', NULL, 'Mendoza', NULL, NULL, NULL, 'Active', '2026-11-20 02:17:14'),
(4, 'CLINIC-01', 'Rina', NULL, 'Flores', NULL, NULL, NULL, 'Active', '2026-11-20 02:17:14'),
(5, 'SCH-01', 'Lina', NULL, 'Garcia', NULL, NULL, NULL, 'Active', '2026-11-20 02:17:14'),
(6, 'T-001', 'Grace', NULL, 'Cruz', NULL, NULL, NULL, 'Active', '2026-11-20 02:17:14'),
(7, 'T-002', 'Paolo', NULL, 'Reyes', NULL, NULL, NULL, 'Active', '2026-11-20 02:17:14'),
(8, 'T-003', 'Nina', NULL, 'Lim', NULL, NULL, NULL, 'Active', '2026-11-20 02:17:14'),
(9, 'T-004', 'Marco', NULL, 'Lopez', NULL, NULL, NULL, 'Active', '2026-11-20 02:17:14'),
(10, 'T-005', 'Ella', NULL, 'Tan', NULL, NULL, NULL, 'Active', '2026-11-20 02:17:14'),
(11, 'T-006', 'Ramon', NULL, 'Castro', NULL, NULL, NULL, 'Active', '2026-11-20 02:17:14'),
(12, 'T-007', 'Jessa', NULL, 'Ramos', NULL, NULL, NULL, 'Active', '2026-11-20 02:17:14'),
(13, 'T-008', 'Victor', NULL, 'Dela Cruz', NULL, NULL, NULL, 'Active', '2026-11-20 02:17:14'),
(14, 'T-009', 'Clara', NULL, 'Torres', NULL, NULL, NULL, 'Active', '2026-11-20 02:17:14'),
(15, 'T-010', 'Dino', NULL, 'Aquino', NULL, NULL, NULL, 'Active', '2026-11-20 02:17:14');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_lrn` varchar(15) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `psa_birth_certificate_no` varchar(40) DEFAULT NULL,
  `last_name` varchar(60) NOT NULL,
  `first_name` varchar(60) NOT NULL,
  `middle_name` varchar(60) DEFAULT NULL,
  `extension_name` varchar(15) DEFAULT NULL,
  `birth_date` date NOT NULL,
  `sex` enum('Male','Female') NOT NULL,
  `place_of_birth` varchar(120) DEFAULT NULL,
  `religion` varchar(80) DEFAULT NULL,
  `mother_tongue` varchar(80) DEFAULT NULL,
  `is_ip_member` tinyint(1) NOT NULL DEFAULT 0,
  `is_4ps_beneficiary` tinyint(1) NOT NULL DEFAULT 0,
  `current_house_no` varchar(80) DEFAULT NULL,
  `current_street_sitio` varchar(120) DEFAULT NULL,
  `current_barangay` varchar(100) DEFAULT NULL,
  `current_municipality` varchar(100) DEFAULT NULL,
  `current_province` varchar(100) DEFAULT NULL,
  `current_country` varchar(80) DEFAULT 'Philippines',
  `current_zip_code` varchar(10) DEFAULT NULL,
  `permanent_same_as_current` tinyint(1) NOT NULL DEFAULT 1,
  `permanent_house_no` varchar(80) DEFAULT NULL,
  `permanent_street_sitio` varchar(120) DEFAULT NULL,
  `permanent_barangay` varchar(100) DEFAULT NULL,
  `permanent_municipality` varchar(100) DEFAULT NULL,
  `permanent_province` varchar(100) DEFAULT NULL,
  `permanent_country` varchar(80) DEFAULT NULL,
  `permanent_zip_code` varchar(10) DEFAULT NULL,
  `father_last_name` varchar(60) DEFAULT NULL,
  `father_first_name` varchar(60) DEFAULT NULL,
  `father_middle_name` varchar(60) DEFAULT NULL,
  `father_contact_number` varchar(20) DEFAULT NULL,
  `mother_maiden_last_name` varchar(60) DEFAULT NULL,
  `mother_maiden_first_name` varchar(60) DEFAULT NULL,
  `mother_maiden_middle_name` varchar(60) DEFAULT NULL,
  `mother_contact_number` varchar(20) DEFAULT NULL,
  `guardian_last_name` varchar(60) DEFAULT NULL,
  `guardian_first_name` varchar(60) DEFAULT NULL,
  `guardian_middle_name` varchar(60) DEFAULT NULL,
  `guardian_contact_number` varchar(20) DEFAULT NULL,
  `student_email` varchar(120) DEFAULT NULL,
  `student_contact_number` varchar(20) DEFAULT NULL,
  `parent_guardian_name` varchar(160) DEFAULT NULL,
  `parent_guardian_relationship` varchar(80) DEFAULT NULL,
  `parent_guardian_contact` varchar(20) DEFAULT NULL,
  `parent_guardian_email` varchar(120) DEFAULT NULL,
  `previous_school` varchar(180) DEFAULT NULL,
  `last_grade_completed` varchar(30) DEFAULT NULL,
  `last_school_year_attended` varchar(20) DEFAULT NULL,
  `health_conditions` varchar(500) DEFAULT NULL,
  `allergies` varchar(500) DEFAULT NULL,
  `current_medications` varchar(500) DEFAULT NULL,
  `special_medical_concerns` varchar(500) DEFAULT NULL,
  `emergency_contact_name` varchar(120) DEFAULT NULL,
  `emergency_contact_relationship` varchar(60) DEFAULT NULL,
  `emergency_contact_number` varchar(20) DEFAULT NULL,
  `student_status` enum('Active','Transferred','Dropped','Graduated','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_lrn`, `user_id`, `psa_birth_certificate_no`, `last_name`, `first_name`, `middle_name`, `extension_name`, `birth_date`, `sex`, `place_of_birth`, `religion`, `mother_tongue`, `is_ip_member`, `is_4ps_beneficiary`, `current_house_no`, `current_street_sitio`, `current_barangay`, `current_municipality`, `current_province`, `current_country`, `current_zip_code`, `permanent_same_as_current`, `permanent_house_no`, `permanent_street_sitio`, `permanent_barangay`, `permanent_municipality`, `permanent_province`, `permanent_country`, `permanent_zip_code`, `father_last_name`, `father_first_name`, `father_middle_name`, `father_contact_number`, `mother_maiden_last_name`, `mother_maiden_first_name`, `mother_maiden_middle_name`, `mother_contact_number`, `guardian_last_name`, `guardian_first_name`, `guardian_middle_name`, `guardian_contact_number`, `student_email`, `student_contact_number`, `parent_guardian_name`, `parent_guardian_relationship`, `parent_guardian_contact`, `parent_guardian_email`, `previous_school`, `last_grade_completed`, `last_school_year_attended`, `health_conditions`, `allergies`, `current_medications`, `special_medical_concerns`, `emergency_contact_name`, `emergency_contact_relationship`, `emergency_contact_number`, `student_status`, `created_at`, `updated_at`) VALUES
('202600000001', 16, NULL, 'Santos', 'Ben', NULL, NULL, '2012-05-15', 'Male', NULL, NULL, NULL, 0, 0, NULL, NULL, 'Poblacion', 'La Trinidad', 'Benguet', 'Philippines', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Guardian', 'Demo', NULL, '09170000000', NULL, NULL, NULL, NULL, NULL, NULL, 'Demo Elementary School', NULL, NULL, NULL, NULL, NULL, NULL, 'Demo Guardian', NULL, '09170000000', 'Active', '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
('202600000002', 17, NULL, 'Cruz', 'Ana', NULL, NULL, '2012-05-15', 'Female', NULL, NULL, NULL, 0, 0, NULL, NULL, 'Poblacion', 'La Trinidad', 'Benguet', 'Philippines', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Guardian', 'Demo', NULL, '09170000000', NULL, NULL, NULL, NULL, NULL, NULL, 'Demo Elementary School', NULL, NULL, NULL, NULL, NULL, NULL, 'Demo Guardian', NULL, '09170000000', 'Active', '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
('202600000003', 18, NULL, 'Reyes', 'Mark', NULL, NULL, '2012-05-15', 'Male', NULL, NULL, NULL, 0, 0, NULL, NULL, 'Poblacion', 'La Trinidad', 'Benguet', 'Philippines', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Guardian', 'Demo', NULL, '09170000000', NULL, NULL, NULL, NULL, NULL, NULL, 'Demo Elementary School', NULL, NULL, NULL, NULL, NULL, NULL, 'Demo Guardian', NULL, '09170000000', 'Active', '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
('202600000004', 19, NULL, 'Lim', 'Jade', NULL, NULL, '2012-05-15', 'Female', NULL, NULL, NULL, 0, 0, NULL, NULL, 'Poblacion', 'La Trinidad', 'Benguet', 'Philippines', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Guardian', 'Demo', NULL, '09170000000', NULL, NULL, NULL, NULL, NULL, NULL, 'Demo Elementary School', NULL, NULL, NULL, NULL, NULL, NULL, 'Demo Guardian', NULL, '09170000000', 'Active', '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
('202600000005', 20, NULL, 'Lopez', 'Carlo', NULL, NULL, '2012-05-15', 'Male', NULL, NULL, NULL, 0, 0, NULL, NULL, 'Poblacion', 'La Trinidad', 'Benguet', 'Philippines', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Guardian', 'Demo', NULL, '09170000000', NULL, NULL, NULL, NULL, NULL, NULL, 'Demo Elementary School', NULL, NULL, NULL, NULL, NULL, NULL, 'Demo Guardian', NULL, '09170000000', 'Active', '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
('202600000006', 21, NULL, 'Ramos', 'Mika', NULL, NULL, '2012-05-15', 'Female', NULL, NULL, NULL, 0, 0, NULL, NULL, 'Poblacion', 'La Trinidad', 'Benguet', 'Philippines', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Guardian', 'Demo', NULL, '09170000000', NULL, NULL, NULL, NULL, NULL, NULL, 'Demo Elementary School', NULL, NULL, NULL, NULL, NULL, NULL, 'Demo Guardian', NULL, '09170000000', 'Active', '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
('202600000007', 22, NULL, 'Tan', 'Leo', NULL, NULL, '2012-05-15', 'Male', NULL, NULL, NULL, 0, 0, NULL, NULL, 'Poblacion', 'La Trinidad', 'Benguet', 'Philippines', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Guardian', 'Demo', NULL, '09170000000', NULL, NULL, NULL, NULL, NULL, NULL, 'Demo Elementary School', NULL, NULL, NULL, NULL, NULL, NULL, 'Demo Guardian', NULL, '09170000000', 'Active', '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
('202600000008', 23, NULL, 'Garcia', 'Sofia', NULL, NULL, '2012-05-15', 'Female', NULL, NULL, NULL, 0, 0, NULL, NULL, 'Poblacion', 'La Trinidad', 'Benguet', 'Philippines', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Guardian', 'Demo', NULL, '09170000000', NULL, NULL, NULL, NULL, NULL, NULL, 'Demo Elementary School', NULL, NULL, NULL, NULL, NULL, NULL, 'Demo Guardian', NULL, '09170000000', 'Active', '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
('202600000009', 24, NULL, 'Torres', 'Nico', NULL, NULL, '2012-05-15', 'Male', NULL, NULL, NULL, 0, 0, NULL, NULL, 'Poblacion', 'La Trinidad', 'Benguet', 'Philippines', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Guardian', 'Demo', NULL, '09170000000', NULL, NULL, NULL, NULL, NULL, NULL, 'Demo Elementary School', NULL, NULL, NULL, NULL, NULL, NULL, 'Demo Guardian', NULL, '09170000000', 'Active', '2026-11-20 02:17:14', '2026-11-20 02:17:14'),
('202600000010', 25, NULL, 'Aquino', 'Lara', NULL, NULL, '2012-05-15', 'Female', NULL, NULL, NULL, 0, 0, NULL, NULL, 'Poblacion', 'La Trinidad', 'Benguet', 'Philippines', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Guardian', 'Demo', NULL, '09170000000', NULL, NULL, NULL, NULL, NULL, NULL, 'Demo Elementary School', NULL, NULL, NULL, NULL, NULL, NULL, 'Demo Guardian', NULL, '09170000000', 'Active', '2026-11-20 02:17:14', '2026-11-20 02:17:14');

-- --------------------------------------------------------

--
-- Table structure for table `student_electives`
--

CREATE TABLE `student_electives` (
  `enrollment_id` int(11) NOT NULL,
  `section_subject_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_files`
--

CREATE TABLE `student_files` (
  `file_id` int(11) NOT NULL,
  `student_lrn` varchar(15) NOT NULL,
  `document_name` varchar(120) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `verification_status` enum('Pending','Accepted','Rejected') NOT NULL DEFAULT 'Pending',
  `remarks` varchar(255) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_files`
--

INSERT INTO `student_files` (`file_id`, `student_lrn`, `document_name`, `file_path`, `verification_status`, `remarks`, `uploaded_by`, `verified_by`, `verified_at`, `uploaded_at`) VALUES
(1, '202600000001', 'PSA Birth Certificate', 'storage/student_documents/202600000001/40b4b02915ba400a09286709b293626f.jpg', 'Pending', NULL, 16, NULL, NULL, '2026-11-20 07:16:02');

-- --------------------------------------------------------

--
-- Table structure for table `student_registration_applications`
--

CREATE TABLE `student_registration_applications` (
  `application_id` int(11) NOT NULL,
  `student_lrn` varchar(15) NOT NULL,
  `enrollment_category` enum('Incoming','Returning','Transferee','Balik-Aral') NOT NULL,
  `application_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`application_data`)),
  `application_status` enum('For Verification','Approved','Rejected') NOT NULL DEFAULT 'For Verification',
  `registrar_notes` varchar(500) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `subject_name` varchar(120) NOT NULL,
  `grade_level` enum('Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12') NOT NULL,
  `subject_status` enum('Active','Inactive','Resigned') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `subject_code`, `subject_name`, `grade_level`, `subject_status`) VALUES
(1, 'DEMO-ENG7', 'English', 'Grade 7', 'Active'),
(2, 'DEMO-MATH7', 'Mathematics', 'Grade 7', 'Active'),
(3, 'DEMO-SCI7', 'Science', 'Grade 7', 'Active'),
(4, 'DEMO-FIL7', 'Filipino', 'Grade 7', 'Active'),
(5, 'DEMO-AP7', 'Araling Panlipunan', 'Grade 7', 'Active'),
(6, 'DEMO-ENG8', 'English', 'Grade 8', 'Active'),
(7, 'DEMO-MATH8', 'Mathematics', 'Grade 8', 'Active'),
(8, 'DEMO-SCI8', 'Science', 'Grade 8', 'Active'),
(9, 'DEMO-FIL8', 'Filipino', 'Grade 8', 'Active'),
(10, 'DEMO-AP8', 'Araling Panlipunan', 'Grade 8', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `subject_elective_clusters`
--

CREATE TABLE `subject_elective_clusters` (
  `subject_id` int(11) NOT NULL,
  `cluster_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `teacher_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `employee_number` varchar(30) NOT NULL,
  `first_name` varchar(60) NOT NULL,
  `middle_name` varchar(60) DEFAULT NULL,
  `last_name` varchar(60) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `employment_status` enum('Active','Inactive','Resigned') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`teacher_id`, `user_id`, `employee_number`, `first_name`, `middle_name`, `last_name`, `contact_number`, `email`, `employment_status`) VALUES
(1, 6, 'T-001', 'Grace', NULL, 'Cruz', NULL, NULL, 'Active'),
(2, 7, 'T-002', 'Paolo', NULL, 'Reyes', NULL, NULL, 'Active'),
(3, 8, 'T-003', 'Nina', NULL, 'Lim', NULL, NULL, 'Active'),
(4, 9, 'T-004', 'Marco', NULL, 'Lopez', NULL, NULL, 'Active'),
(5, 10, 'T-005', 'Ella', NULL, 'Tan', NULL, NULL, 'Active'),
(6, 11, 'T-006', 'Ramon', NULL, 'Castro', NULL, NULL, 'Active'),
(7, 12, 'T-007', 'Jessa', NULL, 'Ramos', NULL, NULL, 'Active'),
(8, 13, 'T-008', 'Victor', NULL, 'Dela Cruz', NULL, NULL, 'Active'),
(9, 14, 'T-009', 'Clara', NULL, 'Torres', NULL, NULL, 'Active'),
(10, 15, 'T-010', 'Dino', NULL, 'Aquino', NULL, NULL, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `tle_components`
--

CREATE TABLE `tle_components` (
  `component_id` int(11) NOT NULL,
  `component_name` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tle_groups`
--

CREATE TABLE `tle_groups` (
  `group_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `group_name` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tle_group_members`
--

CREATE TABLE `tle_group_members` (
  `enrollment_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tle_group_rotations`
--

CREATE TABLE `tle_group_rotations` (
  `rotation_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `term_no` tinyint(3) UNSIGNED NOT NULL,
  `component_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tle_term_grades`
--

CREATE TABLE `tle_term_grades` (
  `enrollment_id` int(11) NOT NULL,
  `rotation_id` int(11) NOT NULL,
  `grade_value` decimal(5,2) DEFAULT NULL,
  `encoded_by` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('System Administrator','School Registrar','Subject Teacher','Teacher Adviser','Scholarship Coordinator','Guidance Counselor','Clinic Personnel','Student') NOT NULL,
  `account_status` enum('Active','Inactive','Locked','Alumni') NOT NULL DEFAULT 'Active',
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `role`, `account_status`, `must_change_password`, `created_by`, `created_at`) VALUES
(1, 'demo_admin', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'System Administrator', 'Active', 0, NULL, '2026-11-20 02:17:14'),
(2, 'demo_registrar', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'School Registrar', 'Active', 0, NULL, '2026-11-20 02:17:14'),
(3, 'demo_guidance', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'Guidance Counselor', 'Active', 0, NULL, '2026-11-20 02:17:14'),
(4, 'demo_clinic', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'Clinic Personnel', 'Active', 0, NULL, '2026-11-20 02:17:14'),
(5, 'demo_scholarship', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'Scholarship Coordinator', 'Active', 0, NULL, '2026-11-20 02:17:14'),
(6, 'demo_teacher1', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'Teacher Adviser', 'Active', 0, NULL, '2026-11-20 02:17:14'),
(7, 'demo_teacher2', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'Teacher Adviser', 'Active', 0, NULL, '2026-11-20 02:17:14'),
(8, 'demo_teacher3', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'Subject Teacher', 'Active', 0, NULL, '2026-11-20 02:17:14'),
(9, 'demo_teacher4', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'Subject Teacher', 'Active', 0, NULL, '2026-11-20 02:17:14'),
(10, 'demo_teacher5', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'Subject Teacher', 'Active', 0, NULL, '2026-11-20 02:17:14'),
(11, 'demo_teacher6', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'Subject Teacher', 'Active', 0, NULL, '2026-11-20 02:17:14'),
(12, 'demo_teacher7', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'Subject Teacher', 'Active', 0, NULL, '2026-11-20 02:17:14'),
(13, 'demo_teacher8', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'Subject Teacher', 'Active', 0, NULL, '2026-11-20 02:17:14'),
(14, 'demo_teacher9', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'Subject Teacher', 'Active', 0, NULL, '2026-11-20 02:17:14'),
(15, 'demo_teacher10', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'Subject Teacher', 'Active', 0, NULL, '2026-11-20 02:17:14'),
(16, '202600000001', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'Student', 'Active', 0, NULL, '2026-11-20 02:17:14'),
(17, '202600000002', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'Student', 'Active', 0, NULL, '2026-11-20 02:17:14'),
(18, '202600000003', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'Student', 'Active', 0, NULL, '2026-11-20 02:17:14'),
(19, '202600000004', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'Student', 'Active', 0, NULL, '2026-11-20 02:17:14'),
(20, '202600000005', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'Student', 'Active', 0, NULL, '2026-11-20 02:17:14'),
(21, '202600000006', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'Student', 'Active', 0, NULL, '2026-11-20 02:17:14'),
(22, '202600000007', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'Student', 'Active', 0, NULL, '2026-11-20 02:17:14'),
(23, '202600000008', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'Student', 'Active', 0, NULL, '2026-11-20 02:17:14'),
(24, '202600000009', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'Student', 'Inactive', 0, NULL, '2026-11-20 02:17:14'),
(25, '202600000010', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'Student', 'Active', 0, NULL, '2026-11-20 02:17:14');

-- --------------------------------------------------------

--
-- Table structure for table `user_notifications`
--

CREATE TABLE `user_notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(160) NOT NULL,
  `message` varchar(500) NOT NULL,
  `notification_type` enum('Announcement','Scholarship','Password Reset','System') NOT NULL DEFAULT 'System',
  `related_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `posted_by` (`posted_by`);

--
-- Indexes for table `assistance_distributions`
--
ALTER TABLE `assistance_distributions`
  ADD PRIMARY KEY (`distribution_id`),
  ADD KEY `award_id` (`award_id`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `enrollment_id` (`enrollment_id`,`section_subject_id`,`attendance_date`),
  ADD KEY `section_subject_id` (`section_subject_id`),
  ADD KEY `recorded_by` (`recorded_by`),
  ADD KEY `idx_attendance_date` (`attendance_date`);

--
-- Indexes for table `class_sections`
--
ALTER TABLE `class_sections`
  ADD PRIMARY KEY (`section_id`),
  ADD UNIQUE KEY `uq_section_year_grade_name` (`school_year_id`,`grade_level`,`section_name`),
  ADD KEY `adviser_teacher_id` (`adviser_teacher_id`);

--
-- Indexes for table `clinic_visits`
--
ALTER TABLE `clinic_visits`
  ADD PRIMARY KEY (`visit_id`),
  ADD KEY `student_lrn` (`student_lrn`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `elective_clusters`
--
ALTER TABLE `elective_clusters`
  ADD PRIMARY KEY (`cluster_id`),
  ADD UNIQUE KEY `uq_track_cluster` (`track`,`cluster_name`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD UNIQUE KEY `uq_enrollment_student_year` (`student_lrn`,`school_year_id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `guidance_assessed_by` (`guidance_assessed_by`),
  ADD KEY `guidance_approved_by` (`guidance_approved_by`),
  ADD KEY `idx_enrollments_year_grade` (`school_year_id`,`grade_level`),
  ADD KEY `idx_enrollments_status` (`enrollment_status`);

--
-- Indexes for table `enrollment_documents`
--
ALTER TABLE `enrollment_documents`
  ADD PRIMARY KEY (`enrollment_document_id`),
  ADD KEY `enrollment_id` (`enrollment_id`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`grade_id`),
  ADD UNIQUE KEY `enrollment_id` (`enrollment_id`,`section_subject_id`,`grading_period`),
  ADD KEY `section_subject_id` (`section_subject_id`),
  ADD KEY `encoded_by` (`encoded_by`),
  ADD KEY `idx_grades_period` (`grading_period`);

--
-- Indexes for table `grade_period_locks`
--
ALTER TABLE `grade_period_locks`
  ADD PRIMARY KEY (`lock_id`),
  ADD UNIQUE KEY `section_subject_id` (`section_subject_id`,`grading_period`),
  ADD KEY `locked_by` (`locked_by`);

--
-- Indexes for table `guidance_records`
--
ALTER TABLE `guidance_records`
  ADD PRIMARY KEY (`guide_id`),
  ADD KEY `enrollment_id` (`enrollment_id`),
  ADD KEY `assessed_by` (`assessed_by`);

--
-- Indexes for table `learner_assessments`
--
ALTER TABLE `learner_assessments`
  ADD PRIMARY KEY (`assessment_id`),
  ADD UNIQUE KEY `uq_learner_assessment` (`enrollment_id`,`assessment_type`),
  ADD KEY `idx_assessed_by` (`assessed_by`);

--
-- Indexes for table `learner_movements`
--
ALTER TABLE `learner_movements`
  ADD PRIMARY KEY (`movement_id`),
  ADD KEY `idx_movement_date` (`movement_date`,`movement_type`),
  ADD KEY `idx_movement_enrollment` (`enrollment_id`);

--
-- Indexes for table `migration_grades_before_terms`
--
ALTER TABLE `migration_grades_before_terms`
  ADD PRIMARY KEY (`grade_id`),
  ADD UNIQUE KEY `enrollment_id` (`enrollment_id`,`section_subject_id`,`grading_period`),
  ADD KEY `section_subject_id` (`section_subject_id`),
  ADD KEY `encoded_by` (`encoded_by`),
  ADD KEY `idx_grades_period` (`grading_period`);

--
-- Indexes for table `official_daily_attendance`
--
ALTER TABLE `official_daily_attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `uq_official_enrollment_date` (`enrollment_id`,`attendance_date`),
  ADD KEY `idx_official_date` (`attendance_date`);

--
-- Indexes for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `idx_reset_status` (`request_status`,`requested_at`);

--
-- Indexes for table `reenrollment_requests`
--
ALTER TABLE `reenrollment_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD UNIQUE KEY `uq_reenrollment_student_year` (`student_lrn`,`school_year_id`),
  ADD KEY `fk_reenroll_year` (`school_year_id`),
  ADD KEY `fk_reenroll_requester` (`requested_by`);

--
-- Indexes for table `scholarship_applications`
--
ALTER TABLE `scholarship_applications`
  ADD PRIMARY KEY (`application_id`),
  ADD UNIQUE KEY `program_id` (`program_id`,`student_lrn`),
  ADD KEY `student_lrn` (`student_lrn`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_scholarship_app_status` (`application_status`);

--
-- Indexes for table `scholarship_application_documents`
--
ALTER TABLE `scholarship_application_documents`
  ADD PRIMARY KEY (`application_document_id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indexes for table `scholarship_awards`
--
ALTER TABLE `scholarship_awards`
  ADD PRIMARY KEY (`award_id`),
  ADD UNIQUE KEY `application_id` (`application_id`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `scholarship_programs`
--
ALTER TABLE `scholarship_programs`
  ADD PRIMARY KEY (`program_id`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `scholarship_providers`
--
ALTER TABLE `scholarship_providers`
  ADD PRIMARY KEY (`provider_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `school_years`
--
ALTER TABLE `school_years`
  ADD PRIMARY KEY (`school_year_id`),
  ADD UNIQUE KEY `school_year_name` (`school_year_name`);

--
-- Indexes for table `section_subjects`
--
ALTER TABLE `section_subjects`
  ADD PRIMARY KEY (`section_subject_id`),
  ADD UNIQUE KEY `uq_section_subject` (`section_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `employee_number` (`employee_number`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_lrn`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_students_name` (`last_name`,`first_name`);

--
-- Indexes for table `student_electives`
--
ALTER TABLE `student_electives`
  ADD PRIMARY KEY (`enrollment_id`,`section_subject_id`),
  ADD KEY `section_subject_id` (`section_subject_id`);

--
-- Indexes for table `student_files`
--
ALTER TABLE `student_files`
  ADD PRIMARY KEY (`file_id`),
  ADD UNIQUE KEY `unique_student_document` (`student_lrn`,`document_name`),
  ADD KEY `idx_student_files_lrn` (`student_lrn`);

--
-- Indexes for table `student_registration_applications`
--
ALTER TABLE `student_registration_applications`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `idx_registration_lrn_status` (`student_lrn`,`application_status`),
  ADD KEY `idx_registration_status_date` (`application_status`,`submitted_at`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`);

--
-- Indexes for table `subject_elective_clusters`
--
ALTER TABLE `subject_elective_clusters`
  ADD PRIMARY KEY (`subject_id`,`cluster_id`),
  ADD KEY `cluster_id` (`cluster_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`teacher_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `employee_number` (`employee_number`);

--
-- Indexes for table `tle_components`
--
ALTER TABLE `tle_components`
  ADD PRIMARY KEY (`component_id`),
  ADD UNIQUE KEY `component_name` (`component_name`);

--
-- Indexes for table `tle_groups`
--
ALTER TABLE `tle_groups`
  ADD PRIMARY KEY (`group_id`),
  ADD UNIQUE KEY `uq_section_group` (`section_id`,`group_name`);

--
-- Indexes for table `tle_group_members`
--
ALTER TABLE `tle_group_members`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `tle_group_rotations`
--
ALTER TABLE `tle_group_rotations`
  ADD PRIMARY KEY (`rotation_id`),
  ADD UNIQUE KEY `uq_group_term` (`group_id`,`term_no`),
  ADD KEY `component_id` (`component_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `tle_term_grades`
--
ALTER TABLE `tle_term_grades`
  ADD PRIMARY KEY (`enrollment_id`,`rotation_id`),
  ADD KEY `rotation_id` (`rotation_id`),
  ADD KEY `encoded_by` (`encoded_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_user_notifications` (`user_id`,`is_read`,`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `assistance_distributions`
--
ALTER TABLE `assistance_distributions`
  MODIFY `distribution_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `class_sections`
--
ALTER TABLE `class_sections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `clinic_visits`
--
ALTER TABLE `clinic_visits`
  MODIFY `visit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `elective_clusters`
--
ALTER TABLE `elective_clusters`
  MODIFY `cluster_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `enrollment_documents`
--
ALTER TABLE `enrollment_documents`
  MODIFY `enrollment_document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `grade_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=151;

--
-- AUTO_INCREMENT for table `grade_period_locks`
--
ALTER TABLE `grade_period_locks`
  MODIFY `lock_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `guidance_records`
--
ALTER TABLE `guidance_records`
  MODIFY `guide_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `learner_assessments`
--
ALTER TABLE `learner_assessments`
  MODIFY `assessment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `learner_movements`
--
ALTER TABLE `learner_movements`
  MODIFY `movement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migration_grades_before_terms`
--
ALTER TABLE `migration_grades_before_terms`
  MODIFY `grade_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=151;

--
-- AUTO_INCREMENT for table `official_daily_attendance`
--
ALTER TABLE `official_daily_attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reenrollment_requests`
--
ALTER TABLE `reenrollment_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `scholarship_applications`
--
ALTER TABLE `scholarship_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scholarship_application_documents`
--
ALTER TABLE `scholarship_application_documents`
  MODIFY `application_document_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scholarship_awards`
--
ALTER TABLE `scholarship_awards`
  MODIFY `award_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scholarship_programs`
--
ALTER TABLE `scholarship_programs`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `scholarship_providers`
--
ALTER TABLE `scholarship_providers`
  MODIFY `provider_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `school_years`
--
ALTER TABLE `school_years`
  MODIFY `school_year_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `section_subjects`
--
ALTER TABLE `section_subjects`
  MODIFY `section_subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `student_files`
--
ALTER TABLE `student_files`
  MODIFY `file_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `student_registration_applications`
--
ALTER TABLE `student_registration_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `teacher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tle_components`
--
ALTER TABLE `tle_components`
  MODIFY `component_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tle_groups`
--
ALTER TABLE `tle_groups`
  MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tle_group_rotations`
--
ALTER TABLE `tle_group_rotations`
  MODIFY `rotation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `user_notifications`
--
ALTER TABLE `user_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `assistance_distributions`
--
ALTER TABLE `assistance_distributions`
  ADD CONSTRAINT `assistance_distributions_ibfk_1` FOREIGN KEY (`award_id`) REFERENCES `scholarship_awards` (`award_id`),
  ADD CONSTRAINT `assistance_distributions_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`enrollment_id`),
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`section_subject_id`) REFERENCES `section_subjects` (`section_subject_id`),
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `class_sections`
--
ALTER TABLE `class_sections`
  ADD CONSTRAINT `class_sections_ibfk_1` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`school_year_id`),
  ADD CONSTRAINT `class_sections_ibfk_2` FOREIGN KEY (`adviser_teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE SET NULL;

--
-- Constraints for table `clinic_visits`
--
ALTER TABLE `clinic_visits`
  ADD CONSTRAINT `clinic_visits_ibfk_1` FOREIGN KEY (`student_lrn`) REFERENCES `students` (`student_lrn`),
  ADD CONSTRAINT `clinic_visits_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_lrn`) REFERENCES `students` (`student_lrn`),
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`school_year_id`),
  ADD CONSTRAINT `enrollments_ibfk_3` FOREIGN KEY (`section_id`) REFERENCES `class_sections` (`section_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `enrollments_ibfk_4` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `enrollments_ibfk_5` FOREIGN KEY (`guidance_assessed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `enrollments_ibfk_6` FOREIGN KEY (`guidance_approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `enrollment_documents`
--
ALTER TABLE `enrollment_documents`
  ADD CONSTRAINT `enrollment_documents_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`enrollment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollment_documents_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`enrollment_id`),
  ADD CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`section_subject_id`) REFERENCES `section_subjects` (`section_subject_id`),
  ADD CONSTRAINT `grades_ibfk_3` FOREIGN KEY (`encoded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `grade_period_locks`
--
ALTER TABLE `grade_period_locks`
  ADD CONSTRAINT `grade_period_locks_ibfk_1` FOREIGN KEY (`section_subject_id`) REFERENCES `section_subjects` (`section_subject_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grade_period_locks_ibfk_2` FOREIGN KEY (`locked_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `guidance_records`
--
ALTER TABLE `guidance_records`
  ADD CONSTRAINT `guidance_records_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`enrollment_id`),
  ADD CONSTRAINT `guidance_records_ibfk_2` FOREIGN KEY (`assessed_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `learner_assessments`
--
ALTER TABLE `learner_assessments`
  ADD CONSTRAINT `fk_la_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`enrollment_id`),
  ADD CONSTRAINT `fk_la_user` FOREIGN KEY (`assessed_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `learner_movements`
--
ALTER TABLE `learner_movements`
  ADD CONSTRAINT `fk_movement_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`enrollment_id`);

--
-- Constraints for table `official_daily_attendance`
--
ALTER TABLE `official_daily_attendance`
  ADD CONSTRAINT `fk_official_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`enrollment_id`);

--
-- Constraints for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD CONSTRAINT `password_reset_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `password_reset_requests_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `reenrollment_requests`
--
ALTER TABLE `reenrollment_requests`
  ADD CONSTRAINT `fk_reenroll_requester` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_reenroll_student` FOREIGN KEY (`student_lrn`) REFERENCES `students` (`student_lrn`),
  ADD CONSTRAINT `fk_reenroll_year` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`school_year_id`);

--
-- Constraints for table `scholarship_applications`
--
ALTER TABLE `scholarship_applications`
  ADD CONSTRAINT `scholarship_applications_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `scholarship_programs` (`program_id`),
  ADD CONSTRAINT `scholarship_applications_ibfk_2` FOREIGN KEY (`student_lrn`) REFERENCES `students` (`student_lrn`),
  ADD CONSTRAINT `scholarship_applications_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `scholarship_application_documents`
--
ALTER TABLE `scholarship_application_documents`
  ADD CONSTRAINT `scholarship_application_documents_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `scholarship_applications` (`application_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `scholarship_application_documents_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `scholarship_awards`
--
ALTER TABLE `scholarship_awards`
  ADD CONSTRAINT `scholarship_awards_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `scholarship_applications` (`application_id`),
  ADD CONSTRAINT `scholarship_awards_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `scholarship_programs`
--
ALTER TABLE `scholarship_programs`
  ADD CONSTRAINT `scholarship_programs_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `scholarship_providers` (`provider_id`),
  ADD CONSTRAINT `scholarship_programs_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `scholarship_providers`
--
ALTER TABLE `scholarship_providers`
  ADD CONSTRAINT `scholarship_providers_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `section_subjects`
--
ALTER TABLE `section_subjects`
  ADD CONSTRAINT `section_subjects_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `class_sections` (`section_id`),
  ADD CONSTRAINT `section_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`),
  ADD CONSTRAINT `section_subjects_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE SET NULL;

--
-- Constraints for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  ADD CONSTRAINT `staff_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `student_electives`
--
ALTER TABLE `student_electives`
  ADD CONSTRAINT `student_electives_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`enrollment_id`),
  ADD CONSTRAINT `student_electives_ibfk_2` FOREIGN KEY (`section_subject_id`) REFERENCES `section_subjects` (`section_subject_id`);

--
-- Constraints for table `student_registration_applications`
--
ALTER TABLE `student_registration_applications`
  ADD CONSTRAINT `student_registration_applications_ibfk_1` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `subject_elective_clusters`
--
ALTER TABLE `subject_elective_clusters`
  ADD CONSTRAINT `subject_elective_clusters_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`),
  ADD CONSTRAINT `subject_elective_clusters_ibfk_2` FOREIGN KEY (`cluster_id`) REFERENCES `elective_clusters` (`cluster_id`);

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `tle_groups`
--
ALTER TABLE `tle_groups`
  ADD CONSTRAINT `tle_groups_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `class_sections` (`section_id`);

--
-- Constraints for table `tle_group_members`
--
ALTER TABLE `tle_group_members`
  ADD CONSTRAINT `tle_group_members_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`enrollment_id`),
  ADD CONSTRAINT `tle_group_members_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `tle_groups` (`group_id`);

--
-- Constraints for table `tle_group_rotations`
--
ALTER TABLE `tle_group_rotations`
  ADD CONSTRAINT `tle_group_rotations_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `tle_groups` (`group_id`),
  ADD CONSTRAINT `tle_group_rotations_ibfk_2` FOREIGN KEY (`component_id`) REFERENCES `tle_components` (`component_id`),
  ADD CONSTRAINT `tle_group_rotations_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`);

--
-- Constraints for table `tle_term_grades`
--
ALTER TABLE `tle_term_grades`
  ADD CONSTRAINT `tle_term_grades_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`enrollment_id`),
  ADD CONSTRAINT `tle_term_grades_ibfk_2` FOREIGN KEY (`rotation_id`) REFERENCES `tle_group_rotations` (`rotation_id`),
  ADD CONSTRAINT `tle_term_grades_ibfk_3` FOREIGN KEY (`encoded_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD CONSTRAINT `user_notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
