USE eltnhs_database_deploy;
CREATE TABLE IF NOT EXISTS student_files (
 file_id INT AUTO_INCREMENT PRIMARY KEY,
 student_lrn VARCHAR(15) NOT NULL,
 document_name VARCHAR(120) NOT NULL,
 file_path VARCHAR(255) NOT NULL,
 verification_status ENUM('Pending','Accepted','Rejected') NOT NULL DEFAULT 'Pending',
 remarks VARCHAR(255),
 uploaded_by INT NULL,
 verified_by INT NULL,
 verified_at DATETIME NULL,
 uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY unique_student_document (student_lrn, document_name),
 INDEX idx_student_files_lrn (student_lrn)
) ENGINE=InnoDB;
