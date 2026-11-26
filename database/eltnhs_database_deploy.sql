-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Nov 26, 2026 at 05:18 AM
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

-- --------------------------------------------------------

--
-- Table structure for table `grade_correction_requests`
--

CREATE TABLE `grade_correction_requests` (
  `request_id` int(11) NOT NULL,
  `grade_id` int(11) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `proposed_grade` decimal(5,2) NOT NULL,
  `reason` varchar(500) NOT NULL,
  `old_grade` decimal(5,2) DEFAULT NULL,
  `old_status` varchar(30) DEFAULT NULL,
  `request_status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `grading_schedules`
--

CREATE TABLE `grading_schedules` (
  `school_year_id` int(11) NOT NULL,
  `term` tinyint(3) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `manually_closed` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grading_schedule_history`
--

CREATE TABLE `grading_schedule_history` (
  `history_id` int(11) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `old_schedule` text NOT NULL,
  `new_schedule` text NOT NULL,
  `reason` varchar(500) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `school_years`
--

CREATE TABLE `school_years` (
  `school_year_id` int(11) NOT NULL,
  `school_year_name` varchar(20) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `year_status` enum('Current','Completed') NOT NULL DEFAULT 'Completed',
  `open_grading_term` tinyint(3) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(1, 'ADMIN-01', 'Alex', NULL, 'Rivera', NULL, NULL, NULL, 'Active', '2026-11-20 02:17:14');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'demo_admin', '$2y$12$s1zy2Q6m0CpYA2liZWPwxO24ypvWN/hfIP8cTjDGVwqKYya3Q3ccW', 'System Administrator', 'Active', 0, NULL, '2026-11-20 02:17:14');

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
-- Indexes for table `grade_correction_requests`
--
ALTER TABLE `grade_correction_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `grade_id` (`grade_id`),
  ADD KEY `requested_by` (`requested_by`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `grade_period_locks`
--
ALTER TABLE `grade_period_locks`
  ADD PRIMARY KEY (`lock_id`),
  ADD UNIQUE KEY `section_subject_id` (`section_subject_id`,`grading_period`),
  ADD KEY `locked_by` (`locked_by`);

--
-- Indexes for table `grading_schedules`
--
ALTER TABLE `grading_schedules`
  ADD PRIMARY KEY (`school_year_id`,`term`);

--
-- Indexes for table `grading_schedule_history`
--
ALTER TABLE `grading_schedule_history`
  ADD PRIMARY KEY (`history_id`);

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
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assistance_distributions`
--
ALTER TABLE `assistance_distributions`
  MODIFY `distribution_id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `clinic_visits`
--
ALTER TABLE `clinic_visits`
  MODIFY `visit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `elective_clusters`
--
ALTER TABLE `elective_clusters`
  MODIFY `cluster_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollment_documents`
--
ALTER TABLE `enrollment_documents`
  MODIFY `enrollment_document_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `grade_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grade_correction_requests`
--
ALTER TABLE `grade_correction_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grade_period_locks`
--
ALTER TABLE `grade_period_locks`
  MODIFY `lock_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grading_schedule_history`
--
ALTER TABLE `grading_schedule_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `guidance_records`
--
ALTER TABLE `guidance_records`
  MODIFY `guide_id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `grade_id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scholarship_providers`
--
ALTER TABLE `scholarship_providers`
  MODIFY `provider_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `school_years`
--
ALTER TABLE `school_years`
  MODIFY `school_year_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `section_subjects`
--
ALTER TABLE `section_subjects`
  MODIFY `section_subject_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_files`
--
ALTER TABLE `student_files`
  MODIFY `file_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_registration_applications`
--
ALTER TABLE `student_registration_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
-- Constraints for table `grade_correction_requests`
--
ALTER TABLE `grade_correction_requests`
  ADD CONSTRAINT `grade_correction_requests_ibfk_1` FOREIGN KEY (`grade_id`) REFERENCES `grades` (`grade_id`),
  ADD CONSTRAINT `grade_correction_requests_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `grade_correction_requests_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `grade_period_locks`
--
ALTER TABLE `grade_period_locks`
  ADD CONSTRAINT `grade_period_locks_ibfk_1` FOREIGN KEY (`section_subject_id`) REFERENCES `section_subjects` (`section_subject_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grade_period_locks_ibfk_2` FOREIGN KEY (`locked_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `grading_schedules`
--
ALTER TABLE `grading_schedules`
  ADD CONSTRAINT `grading_schedules_ibfk_1` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`school_year_id`);

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
