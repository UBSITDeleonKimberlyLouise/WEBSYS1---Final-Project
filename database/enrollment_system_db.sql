CREATE DATABASE IF NOT EXISTS eltnhs_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE eltnhs_management;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS grade_period_locks, user_notifications, password_reset_requests, student_registration_applications, assistance_distributions, scholarship_awards, scholarship_application_documents, scholarship_applications, scholarship_programs, scholarship_providers, announcements, attendance, grades, section_subjects, subjects, enrollment_documents, enrollments, class_sections, school_years, staff_profiles, students, teachers, users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
 user_id INT AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR(50) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('System Administrator','School Registrar','Subject Teacher','Teacher Adviser','Scholarship Coordinator','Student') NOT NULL,
 account_status ENUM('Active','Inactive','Locked') NOT NULL DEFAULT 'Active',
 must_change_password TINYINT(1) NOT NULL DEFAULT 0,
 created_by INT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE teachers (
 teacher_id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL UNIQUE,
 employee_number VARCHAR(30) NOT NULL UNIQUE,
 first_name VARCHAR(60) NOT NULL,
 middle_name VARCHAR(60),
 last_name VARCHAR(60) NOT NULL,
 contact_number VARCHAR(20),
 email VARCHAR(120),
 employment_status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
 FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE students (
 student_lrn VARCHAR(15) PRIMARY KEY,
 user_id INT NULL UNIQUE,
 psa_birth_certificate_no VARCHAR(40),
 last_name VARCHAR(60) NOT NULL,
 first_name VARCHAR(60) NOT NULL,
 middle_name VARCHAR(60),
 extension_name VARCHAR(15),
 birth_date DATE NOT NULL,
 sex ENUM('Male','Female') NOT NULL,
 place_of_birth VARCHAR(120),
 religion VARCHAR(80),
 mother_tongue VARCHAR(80),
 is_ip_member TINYINT(1) NOT NULL DEFAULT 0,
 is_4ps_beneficiary TINYINT(1) NOT NULL DEFAULT 0,
 current_house_no VARCHAR(80),
 current_street_sitio VARCHAR(120),
 current_barangay VARCHAR(100),
 current_municipality VARCHAR(100),
 current_province VARCHAR(100),
 current_country VARCHAR(80) DEFAULT 'Philippines',
 current_zip_code VARCHAR(10),
 permanent_same_as_current TINYINT(1) NOT NULL DEFAULT 1,
 permanent_house_no VARCHAR(80),
 permanent_street_sitio VARCHAR(120),
 permanent_barangay VARCHAR(100),
 permanent_municipality VARCHAR(100),
 permanent_province VARCHAR(100),
 permanent_country VARCHAR(80),
 permanent_zip_code VARCHAR(10),
 father_last_name VARCHAR(60),
 father_first_name VARCHAR(60),
 father_middle_name VARCHAR(60),
 father_contact_number VARCHAR(20),
 mother_maiden_last_name VARCHAR(60),
 mother_maiden_first_name VARCHAR(60),
 mother_maiden_middle_name VARCHAR(60),
 mother_contact_number VARCHAR(20),
 guardian_last_name VARCHAR(60),
 guardian_first_name VARCHAR(60),
 guardian_middle_name VARCHAR(60),
 guardian_contact_number VARCHAR(20),
 student_email VARCHAR(120), student_contact_number VARCHAR(20), parent_guardian_name VARCHAR(160), parent_guardian_relationship VARCHAR(80), parent_guardian_contact VARCHAR(20), parent_guardian_email VARCHAR(120), previous_school VARCHAR(180), last_grade_completed VARCHAR(30), last_school_year_attended VARCHAR(20), health_conditions VARCHAR(500), current_medications VARCHAR(500), special_medical_concerns VARCHAR(500), emergency_contact_name VARCHAR(120), emergency_contact_relationship VARCHAR(60), emergency_contact_number VARCHAR(20),
 student_status ENUM('Active','Transferred','Graduated','Inactive') NOT NULL DEFAULT 'Active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Public parent/guardian registration applications. Approval is handled by the Registrar.
CREATE TABLE student_registration_applications (
 application_id INT AUTO_INCREMENT PRIMARY KEY,
 student_lrn VARCHAR(15) NOT NULL,
 enrollment_category ENUM('Incoming','Returning','Transferee','Balik-Aral') NOT NULL,
 application_data JSON NOT NULL,
 application_status ENUM('For Verification','Approved','Rejected') NOT NULL DEFAULT 'For Verification',
 registrar_notes VARCHAR(500),
 submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 reviewed_by INT NULL,
 reviewed_at DATETIME NULL,
 INDEX idx_registration_lrn_status (student_lrn, application_status),
 INDEX idx_registration_status_date (application_status, submitted_at),
 FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE staff_profiles (
 user_id INT PRIMARY KEY,
 employee_number VARCHAR(30) NOT NULL UNIQUE,
 first_name VARCHAR(60) NOT NULL,
 middle_name VARCHAR(60),
 last_name VARCHAR(60) NOT NULL,
 address VARCHAR(255),
 contact_number VARCHAR(20),
 email VARCHAR(120),
 employment_status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE school_years (
 school_year_id INT AUTO_INCREMENT PRIMARY KEY,
 school_year_name VARCHAR(20) NOT NULL UNIQUE,
 start_date DATE,
 end_date DATE,
 year_status ENUM('Upcoming','Current','Completed') NOT NULL DEFAULT 'Upcoming'
) ENGINE=InnoDB;

CREATE TABLE class_sections (
 section_id INT AUTO_INCREMENT PRIMARY KEY,
 school_year_id INT NOT NULL,
 grade_level ENUM('Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12') NOT NULL,
 track VARCHAR(80),
 strand VARCHAR(100),
 section_name VARCHAR(50) NOT NULL,
 capacity SMALLINT UNSIGNED NOT NULL,
 adviser_teacher_id INT NULL,
 section_status ENUM('Open','Full','Closed') NOT NULL DEFAULT 'Open',
 UNIQUE (school_year_id, grade_level, strand, section_name),
 FOREIGN KEY (school_year_id) REFERENCES school_years(school_year_id),
 FOREIGN KEY (adviser_teacher_id) REFERENCES teachers(teacher_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- JHS: one Full Year enrollment per school year.
-- SHS: separate First and Second semester enrollment records.
CREATE TABLE enrollments (
 enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
 student_lrn VARCHAR(15) NOT NULL,
 school_year_id INT NOT NULL,
 grade_level ENUM('Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12') NOT NULL,
 enrollment_category ENUM('Incoming','Returning','Transferee','Balik-Aral') NOT NULL,
 semester ENUM('Full Year','First','Second') NOT NULL DEFAULT 'Full Year',
 track VARCHAR(80),
 strand VARCHAR(100),
 last_grade_level_completed VARCHAR(20),
 last_school_attended VARCHAR(180),
 last_school_year_completed VARCHAR(20),
 last_school_id VARCHAR(30),
 section_id INT NULL,
 enrollment_date DATE NOT NULL,
 enrollment_status ENUM('Pending','Incomplete Requirements','For Verification','Temporarily Enrolled','Officially Enrolled','Rejected','Withdrawn','Completed') NOT NULL DEFAULT 'Pending',
 final_result ENUM('Passed','Failed','INC','DRP') NULL,
 reading_comprehension_status ENUM('Not Required','Pending','Passed','Failed') NOT NULL DEFAULT 'Pending',
 guidance_assessment_status ENUM('Not Required','Pending','Passed','Failed') NOT NULL DEFAULT 'Pending',
 guidance_assessment_date DATE NULL,
 guidance_assessment_remarks VARCHAR(500),
 guidance_assessed_by INT NULL,
 guidance_approval_status ENUM('Not Required','Pending','Approved','Not Approved') NOT NULL DEFAULT 'Pending',
 guidance_approved_by INT NULL,
 guidance_approved_at DATETIME NULL,
 processed_by INT NULL,
 remarks VARCHAR(500),
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE (student_lrn, school_year_id, semester),
 CONSTRAINT chk_enrollment_semester_by_grade CHECK (
   (grade_level IN ('Grade 7','Grade 8','Grade 9','Grade 10') AND semester = 'Full Year')
   OR
   (grade_level IN ('Grade 11','Grade 12') AND semester IN ('First','Second'))
 ),
 FOREIGN KEY (student_lrn) REFERENCES students(student_lrn),
 FOREIGN KEY (school_year_id) REFERENCES school_years(school_year_id),
 FOREIGN KEY (section_id) REFERENCES class_sections(section_id) ON DELETE SET NULL,
 FOREIGN KEY (processed_by) REFERENCES users(user_id) ON DELETE SET NULL,
 FOREIGN KEY (guidance_assessed_by) REFERENCES users(user_id) ON DELETE SET NULL,
 FOREIGN KEY (guidance_approved_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE enrollment_documents (
 enrollment_document_id INT AUTO_INCREMENT PRIMARY KEY,
 enrollment_id INT NOT NULL,
 document_name VARCHAR(120) NOT NULL,
 is_required TINYINT(1) NOT NULL DEFAULT 1,
 file_path VARCHAR(255),
 verification_status ENUM('Not Submitted','Pending','Accepted','Rejected','Not Required') NOT NULL DEFAULT 'Not Submitted',
 verified_by INT NULL,
 verified_at DATETIME,
 remarks VARCHAR(255),
 FOREIGN KEY (enrollment_id) REFERENCES enrollments(enrollment_id) ON DELETE CASCADE,
 FOREIGN KEY (verified_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE subjects (
 subject_id INT AUTO_INCREMENT PRIMARY KEY,
 subject_code VARCHAR(20) NOT NULL UNIQUE,
 subject_name VARCHAR(120) NOT NULL,
 grade_level ENUM('Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12') NOT NULL,
 subject_status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB;

CREATE TABLE section_subjects (
 section_subject_id INT AUTO_INCREMENT PRIMARY KEY,
 section_id INT NOT NULL,
 subject_id INT NOT NULL,
 teacher_id INT NULL,
 semester ENUM('First','Second') NOT NULL,
 schedule_days VARCHAR(40),
 start_time TIME,
 end_time TIME,
 room VARCHAR(40),
 UNIQUE (section_id, subject_id, semester),
 FOREIGN KEY (section_id) REFERENCES class_sections(section_id),
 FOREIGN KEY (subject_id) REFERENCES subjects(subject_id),
 FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE grades (
 grade_id INT AUTO_INCREMENT PRIMARY KEY,
 enrollment_id INT NOT NULL,
 section_subject_id INT NOT NULL,
 grading_period ENUM('Prelim','Midterm','Finals') NOT NULL,
 grade_value DECIMAL(5,2),
 remarks VARCHAR(100),
 result_status ENUM('Passed','Failed','INC','DRP') NULL,
 encoded_by INT NULL,
 encoded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE (enrollment_id, section_subject_id, grading_period),
 FOREIGN KEY (enrollment_id) REFERENCES enrollments(enrollment_id),
 FOREIGN KEY (section_subject_id) REFERENCES section_subjects(section_subject_id),
 FOREIGN KEY (encoded_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Attendance is recorded for each student, subject, and date.
-- The enrollment and section_subject must belong to the same student/section context;
-- PHP must validate that relationship before inserting an attendance row.
CREATE TABLE attendance (
 attendance_id INT AUTO_INCREMENT PRIMARY KEY,
 enrollment_id INT NOT NULL,
 section_subject_id INT NOT NULL,
 attendance_date DATE NOT NULL,
 attendance_status ENUM('Present','Absent','Late','Excused') NOT NULL,
 remarks VARCHAR(255),
 recorded_by INT NULL,
 recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE (enrollment_id, section_subject_id, attendance_date),
 FOREIGN KEY (enrollment_id) REFERENCES enrollments(enrollment_id),
 FOREIGN KEY (section_subject_id) REFERENCES section_subjects(section_subject_id),
 FOREIGN KEY (recorded_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE scholarship_providers (
 provider_id INT AUTO_INCREMENT PRIMARY KEY,
 provider_name VARCHAR(160) NOT NULL,
 provider_type VARCHAR(80),
 contact_person VARCHAR(120),
 contact_number VARCHAR(30),
 email VARCHAR(120),
 website_url VARCHAR(255),
 provider_status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
 created_by INT NULL,
 FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE scholarship_programs (
 program_id INT AUTO_INCREMENT PRIMARY KEY,
 provider_id INT NOT NULL,
 program_name VARCHAR(160) NOT NULL,
 description TEXT,
 eligibility_criteria TEXT,
 required_documents TEXT,
 benefits TEXT,
 available_slots SMALLINT UNSIGNED,
 application_start DATE,
 application_deadline DATE,
 application_method ENUM('School Managed','External Provider') NOT NULL,
 external_application_url VARCHAR(255),
 program_status ENUM('Draft','Published','Closed','Archived') NOT NULL DEFAULT 'Draft',
 created_by INT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (provider_id) REFERENCES scholarship_providers(provider_id),
 FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE scholarship_applications (
 application_id INT AUTO_INCREMENT PRIMARY KEY,
 program_id INT NOT NULL,
 student_lrn VARCHAR(15) NOT NULL,
 application_date DATE NOT NULL,
 application_status ENUM('Pending','For Verification','Verified','Submitted to Provider','Approved','Rejected','Withdrawn') NOT NULL DEFAULT 'Pending',
 decision_date DATE,
 decision_reference VARCHAR(100),
 remarks VARCHAR(500),
 processed_by INT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE (program_id, student_lrn),
 FOREIGN KEY (program_id) REFERENCES scholarship_programs(program_id),
 FOREIGN KEY (student_lrn) REFERENCES students(student_lrn),
 FOREIGN KEY (processed_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE scholarship_application_documents (
 application_document_id INT AUTO_INCREMENT PRIMARY KEY,
 application_id INT NOT NULL,
 document_name VARCHAR(120) NOT NULL,
 file_path VARCHAR(255),
 verification_status ENUM('Not Submitted','Pending','Accepted','Rejected','Not Required') NOT NULL DEFAULT 'Not Submitted',
 verified_by INT NULL,
 verified_at DATETIME,
 remarks VARCHAR(255),
 FOREIGN KEY (application_id) REFERENCES scholarship_applications(application_id) ON DELETE CASCADE,
 FOREIGN KEY (verified_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE scholarship_awards (
 award_id INT AUTO_INCREMENT PRIMARY KEY,
 application_id INT NOT NULL UNIQUE,
 award_start_date DATE,
 award_end_date DATE,
 award_status ENUM('Active','For Renewal','Renewed','Completed','Discontinued') NOT NULL DEFAULT 'Active',
 renewal_eligibility ENUM('Pending','Eligible','Not Eligible','Not Applicable') NOT NULL DEFAULT 'Pending',
 academic_performance_remarks VARCHAR(500),
 recorded_by INT NULL,
 FOREIGN KEY (application_id) REFERENCES scholarship_applications(application_id),
 FOREIGN KEY (recorded_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE assistance_distributions (
 distribution_id INT AUTO_INCREMENT PRIMARY KEY,
 award_id INT NOT NULL,
 distribution_date DATE NOT NULL,
 assistance_description VARCHAR(180) NOT NULL,
 quantity_or_coverage VARCHAR(120),
 remarks VARCHAR(255),
 recorded_by INT NULL,
 FOREIGN KEY (award_id) REFERENCES scholarship_awards(award_id),
 FOREIGN KEY (recorded_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE announcements (
 announcement_id INT AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(160) NOT NULL,
 content TEXT NOT NULL,
 announcement_category ENUM('Enrollment','Scholarship','School','General') NOT NULL DEFAULT 'General',
 target_audience ENUM('All','Students','Teachers','Registrar','Subject Teachers','Teacher Advisers') NOT NULL DEFAULT 'All',
 publish_date DATETIME NOT NULL,
 expiry_date DATETIME,
 announcement_status ENUM('Draft','Published','Archived') NOT NULL DEFAULT 'Draft',
 posted_by INT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (posted_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE password_reset_requests (
 request_id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, request_status ENUM('Pending','Completed','Rejected') NOT NULL DEFAULT 'Pending', requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, reviewed_by INT NULL, reviewed_at DATETIME NULL, registrar_note VARCHAR(255), FOREIGN KEY(user_id) REFERENCES users(user_id) ON DELETE CASCADE, FOREIGN KEY(reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL, INDEX idx_reset_status(request_status,requested_at)
) ENGINE=InnoDB;

CREATE TABLE user_notifications (
 notification_id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, title VARCHAR(160) NOT NULL, message VARCHAR(500) NOT NULL, notification_type ENUM('Announcement','Scholarship','Password Reset','System') NOT NULL DEFAULT 'System', related_url VARCHAR(255), is_read TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES users(user_id) ON DELETE CASCADE, INDEX idx_user_notifications(user_id,is_read,created_at)
) ENGINE=InnoDB;

CREATE TABLE grade_period_locks (
 lock_id INT AUTO_INCREMENT PRIMARY KEY, section_subject_id INT NOT NULL, grading_period ENUM('Prelim','Midterm','Finals') NOT NULL, locked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, locked_by INT NULL, UNIQUE(section_subject_id,grading_period), FOREIGN KEY(section_subject_id) REFERENCES section_subjects(section_subject_id) ON DELETE CASCADE, FOREIGN KEY(locked_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_students_name ON students(last_name, first_name);
CREATE INDEX idx_enrollments_year_grade ON enrollments(school_year_id, grade_level);
CREATE INDEX idx_enrollments_status ON enrollments(enrollment_status);
CREATE INDEX idx_grades_period ON grades(grading_period);
CREATE INDEX idx_attendance_date ON attendance(attendance_date);
CREATE INDEX idx_scholarship_app_status ON scholarship_applications(application_status);




