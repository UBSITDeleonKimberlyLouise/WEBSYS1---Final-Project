<?php
chdir(dirname(__DIR__));

session_start();

require_once 'dbconn.php';
require_once 'classes/ELTNHS.php';
require_once 'includes/functions.php';
require_once 'includes/announcement_audience.php';

requireLogin();

$system = new ELTNHS($pdo);
$module = $_GET['module'] ?? 'enrollments';
$role = $_SESSION['role'];
if (in_array($module, ['programs','applications','monitoring'], true)) {
    if ($role === 'Student') { header('Location: ../scholarship/student_scholarships.php'); exit; }
    if ($role === 'Scholarship Coordinator') { header('Location: ../scholarship/scholarship.php?view=' . $module); exit; }
}


$permissions = [
    'enrollments' => ['System Administrator', 'School Registrar'],
    'students' => ['System Administrator', 'School Registrar'],
    'records' => ['School Registrar', 'Teacher Adviser', 'Subject Teacher'],
    'announcements' => [
        'System Administrator',
        'School Registrar',
        'Guidance Counselor',
        'Student',
        'Subject Teacher',
        'Teacher Adviser',
        'Clinic Personnel'
    ],
    'reports' => [
        'System Administrator',
        'School Registrar',
        'Subject Teacher',
        'Teacher Adviser'
    ]
];

if (!isset($permissions[$module]) || !in_array($role, $permissions[$module], true)) {
    http_response_code(403);
    exit('You do not have permission to access this module.');
}

verifyCsrf();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lookup_student_lrn'])) {
    $lookup = trim($_POST['lookup_student_lrn']);

    if (preg_match('/^[0-9]{12}$/', $lookup)) {
        $_SESSION['registrar_lookup_lrn'] = $lookup;
    } else {
        $error = 'Enter a valid 12-digit LRN.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['lookup_student_lrn'])) {
    try {
        if (
            $module === 'students' &&
            in_array($role, ['School Registrar', 'System Administrator'], true)
        ) {
            $lrn = trim($_POST['student_lrn'] ?? '');

            if (!preg_match('/^[0-9]{12}$/', $lrn)) {
                throw new Exception('LRN must contain exactly 12 numbers.');
            }

            if (
                trim($_POST['last_name'] ?? '') === '' ||
                trim($_POST['first_name'] ?? '') === '' ||
                empty($_POST['birth_date']) ||
                !in_array($_POST['sex'] ?? '', ['Male', 'Female'], true)
            ) {
                throw new Exception('Please complete the required student name, birth date, and sex fields.');
            }

            $sql = "
                INSERT INTO students (
                    student_lrn,
                    psa_birth_certificate_no,
                    last_name,
                    first_name,
                    middle_name,
                    extension_name,
                    birth_date,
                    sex,
                    place_of_birth,
                    religion,
                    mother_tongue,
                    is_ip_member,
                    is_4ps_beneficiary,
                    current_house_no,
                    current_street_sitio,
                    current_barangay,
                    current_municipality,
                    current_province,
                    current_country,
                    current_zip_code,
                    father_last_name,
                    father_first_name,
                    father_middle_name,
                    father_contact_number,
                    mother_maiden_last_name,
                    mother_maiden_first_name,
                    mother_maiden_middle_name,
                    mother_contact_number,
                    guardian_last_name,
                    guardian_first_name,
                    guardian_middle_name,
                    guardian_contact_number
                )
                VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
                ON DUPLICATE KEY UPDATE
                    psa_birth_certificate_no = VALUES(psa_birth_certificate_no),
                    last_name = VALUES(last_name),
                    first_name = VALUES(first_name),
                    middle_name = VALUES(middle_name),
                    extension_name = VALUES(extension_name),
                    birth_date = VALUES(birth_date),
                    sex = VALUES(sex),
                    place_of_birth = VALUES(place_of_birth),
                    religion = VALUES(religion),
                    mother_tongue = VALUES(mother_tongue),
                    is_ip_member = VALUES(is_ip_member),
                    is_4ps_beneficiary = VALUES(is_4ps_beneficiary),
                    current_house_no = VALUES(current_house_no),
                    current_street_sitio = VALUES(current_street_sitio),
                    current_barangay = VALUES(current_barangay),
                    current_municipality = VALUES(current_municipality),
                    current_province = VALUES(current_province),
                    current_country = VALUES(current_country),
                    current_zip_code = VALUES(current_zip_code),
                    father_last_name = VALUES(father_last_name),
                    father_first_name = VALUES(father_first_name),
                    father_middle_name = VALUES(father_middle_name),
                    father_contact_number = VALUES(father_contact_number),
                    mother_maiden_last_name = VALUES(mother_maiden_last_name),
                    mother_maiden_first_name = VALUES(mother_maiden_first_name),
                    mother_maiden_middle_name = VALUES(mother_maiden_middle_name),
                    mother_contact_number = VALUES(mother_contact_number),
                    guardian_last_name = VALUES(guardian_last_name),
                    guardian_first_name = VALUES(guardian_first_name),
                    guardian_middle_name = VALUES(guardian_middle_name),
                    guardian_contact_number = VALUES(guardian_contact_number)
            ";

            $system->save($sql, [
                $lrn,
                $_POST['psa_birth_certificate_no'] ?: null,
                trim($_POST['last_name']),
                trim($_POST['first_name']),
                $_POST['middle_name'] ?: null,
                $_POST['extension_name'] ?: null,
                $_POST['birth_date'],
                $_POST['sex'],
                $_POST['place_of_birth'] ?: null,
                $_POST['religion'] ?: null,
                $_POST['mother_tongue'] ?: null,
                isset($_POST['is_ip_member']) ? 1 : 0,
                isset($_POST['is_4ps_beneficiary']) ? 1 : 0,
                $_POST['current_house_no'] ?: null,
                $_POST['current_street_sitio'] ?: null,
                $_POST['current_barangay'] ?: null,
                $_POST['current_municipality'] ?: null,
                $_POST['current_province'] ?: null,
                'Philippines',
                $_POST['current_zip_code'] ?: null,
                $_POST['father_last_name'] ?: null,
                $_POST['father_first_name'] ?: null,
                $_POST['father_middle_name'] ?: null,
                $_POST['father_contact_number'] ?: null,
                $_POST['mother_maiden_last_name'] ?: null,
                $_POST['mother_maiden_first_name'] ?: null,
                $_POST['mother_maiden_middle_name'] ?: null,
                $_POST['mother_contact_number'] ?: null,
                $_POST['guardian_last_name'] ?: null,
                $_POST['guardian_first_name'] ?: null,
                $_POST['guardian_middle_name'] ?: null,
                $_POST['guardian_contact_number'] ?: null
            ]);

            $_SESSION['message'] = 'Step 1 complete: student profile saved. Continue to Step 2 — Enrollment.';
            $_SESSION['next_lrn'] = trim($_POST['student_lrn']);
        } elseif (
            $module === 'enrollments' &&
            in_array($role, ['School Registrar', 'System Administrator'], true) &&
            in_array($_POST['setup_action'] ?? '', ['section', 'edit_section'], true)
        ) {
            $pdo->beginTransaction();
            $currentYear = $system->getOne("SELECT school_year_id FROM school_years WHERE year_status = 'Current' LIMIT 1 FOR UPDATE");
            if (!$currentYear) throw new Exception('No Current school year.');
            $yearId = (int)$currentYear['school_year_id'];
            $id = ($_POST['setup_action'] === 'edit_section') ? (int)($_POST['edit_section_id'] ?? 0) : 0;
            $old = $id ? $system->getOne('SELECT * FROM class_sections WHERE section_id=? AND school_year_id=? FOR UPDATE', [$id,$yearId]) : null;
            if ($_POST['setup_action'] === 'edit_section' && !$old) throw new Exception('Current section not found.');
            $grade = $old ? $old['grade_level'] : ($_POST['section_grade'] ?? '');
            $name = trim($_POST['section_name'] ?? '');
            $capacity = filter_var($_POST['section_capacity'] ?? '', FILTER_VALIDATE_INT);
            if (!in_array($grade, ['Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12'], true) || $name === '' || mb_strlen($name)>50 || $capacity === false || $capacity<1 || $capacity>65535) throw new Exception('Invalid section name, grade or capacity.');
            $shsTrack = $old ? $old['shs_track'] : (in_array($grade,['Grade 11','Grade 12'],true) ? ($_POST['section_shs_track'] ?? '') : null);
            if ($shsTrack !== null && !in_array($shsTrack,['Academic','TechPro'],true)) throw new Exception('Choose a valid SHS track.');
            if ($system->getOne('SELECT section_id FROM class_sections WHERE school_year_id=? AND grade_level=? AND section_name=? AND section_id<>?',[$yearId,$grade,$name,$id])) throw new Exception('Section name already exists for this grade and school year.');
            $adviser = (int)($_POST['adviser_teacher_id'] ?? 0) ?: null;
            if ($adviser) {
                if (!$system->getOne("SELECT t.teacher_id FROM teachers t JOIN users u ON u.user_id=t.user_id WHERE t.teacher_id=? AND t.employment_status='Active' AND u.account_status='Active' AND u.role='Teacher Adviser'",[$adviser])) throw new Exception('Select an active Teacher Adviser.');
                if ($system->getOne('SELECT section_id FROM class_sections WHERE school_year_id=? AND adviser_teacher_id=? AND section_id<>?',[$yearId,$adviser,$id])) throw new Exception('This adviser already has a section this school year.');
            }
            if ($old) {
                $occupied = (int)$system->getOne("SELECT COUNT(*) AS total FROM enrollments WHERE section_id=? AND enrollment_status IN ('Officially Enrolled','Pending','Temporarily Enrolled')",[$id])['total'];
                if ($capacity<$occupied) throw new Exception('Capacity cannot be lower than occupied slots.');
                $status = ($_POST['section_status'] ?? '') === 'Closed' ? 'Closed' : 'Open';
                $system->save('UPDATE class_sections SET section_name=?, capacity=?, adviser_teacher_id=?, section_status=? WHERE section_id=?',[$name,$capacity,$adviser,$status,$id]);
            } else {
                $system->save('INSERT INTO class_sections (school_year_id,grade_level,shs_track,section_name,capacity,adviser_teacher_id) VALUES (?,?,?,?,?,?)',[$yearId,$grade,$shsTrack,$name,$capacity,$adviser]);
            }
            $pdo->commit();
            $_SESSION['message'] = $old ? 'Section updated.' : 'Section created.';
        } elseif (
            $module === 'enrollments' &&
            in_array($role, ['School Registrar', 'System Administrator'], true) &&
            ($_POST['enrollment_action'] ?? '') === 'transfer_section'
        ) {
            $pdo->beginTransaction();
            $rawIds = $_POST['enrollment_ids'] ?? [];
            if (!is_array($rawIds) && !empty($_POST['enrollment_id'])) {
                $rawIds = [$_POST['enrollment_id']];
            }
            $enrollmentIds = array_values(array_unique(array_filter(
                array_map('intval', is_array($rawIds) ? $rawIds : []),
                static function ($val) {
                    return $val > 0;
                }
            )));
            $newSectionId = (int)($_POST['new_section_id'] ?? 0);

            if (!$enrollmentIds) {
                throw new Exception('Select at least one student to transfer.');
            }

            if ($newSectionId <= 0) {
                throw new Exception('Select an available section to transfer to.');
            }

            $placeholders = implode(',', array_fill(0, count($enrollmentIds), '?'));
            $enRows = $system->getAll(
                "SELECT e.*, sy.year_status
                 FROM enrollments e
                 JOIN school_years sy ON sy.school_year_id = e.school_year_id
                 WHERE e.enrollment_id IN ($placeholders)
                 FOR UPDATE",
                $enrollmentIds
            );

            if (count($enRows) !== count($enrollmentIds)) {
                throw new Exception('One or more selected enrollment records were not found.');
            }

            $firstRow = $enRows[0];
            foreach ($enRows as $row) {
                if ((int)$row['school_year_id'] !== (int)$firstRow['school_year_id'] || $row['grade_level'] !== $firstRow['grade_level']) {
                    throw new Exception('Selected students must belong to the same school year and grade level.');
                }
                if (($row['shs_track'] ?? '') !== ($firstRow['shs_track'] ?? '')) {
                    throw new Exception('Selected students must belong to the same SHS track.');
                }
                if ((int)($row['section_id'] ?? 0) === $newSectionId) {
                    throw new Exception('One or more selected students are already assigned to the chosen section.');
                }
            }

            $targetSec = $system->getOne(
                'SELECT *
                 FROM class_sections
                 WHERE section_id = ?
                   AND school_year_id = ?
                   AND grade_level = ?
                 FOR UPDATE',
                [$newSectionId, (int)$firstRow['school_year_id'], $firstRow['grade_level']]
            );

            if (!$targetSec || $targetSec['section_status'] === 'Closed') {
                throw new Exception('Target section is closed, full, or does not match the students\' grade level.');
            }

            if (!empty($firstRow['shs_track']) && $targetSec['shs_track'] !== $firstRow['shs_track']) {
                throw new Exception('Target section does not match the students\' SHS track.');
            }

            $occupied = (int)$system->getOne(
                "SELECT COUNT(*) AS total
                 FROM enrollments
                 WHERE section_id = ?
                   AND enrollment_id NOT IN ($placeholders)
                   AND enrollment_status IN ('Officially Enrolled', 'Pending', 'Temporarily Enrolled')",
                array_merge([$newSectionId], $enrollmentIds)
            )['total'];

            if (($occupied + count($enrollmentIds)) > (int)$targetSec['capacity']) {
                throw new Exception('Target section does not have enough available slots for all selected students.');
            }

            $system->save(
                "UPDATE enrollments SET section_id = ? WHERE enrollment_id IN ($placeholders)",
                array_merge([$newSectionId], $enrollmentIds)
            );

            $pdo->commit();
            $_SESSION['message'] = count($enrollmentIds) . ' student(s) transferred to ' . $targetSec['section_name'] . '.';
        } elseif (
            $module === 'enrollments' &&
            in_array($role, ['School Registrar', 'System Administrator'], true)
        ) {
            $pdo->beginTransaction();
            $currentYear = $system->getOne("
                SELECT school_year_id
                FROM school_years
                WHERE year_status = 'Current'
                LIMIT 1 FOR UPDATE
            ");

            if (!$currentYear) {
                throw new Exception('No Current school year. Ask the System Administrator to set one first.');
            }

            $yearId = (int)$currentYear['school_year_id'];
            $studentLrn = trim($_POST['student_lrn'] ?? '');

            if (!preg_match('/^[0-9]{12}$/', $studentLrn)) {
                throw new Exception('LRN must contain exactly 12 numbers.');
            }

            $registeredStudent = $system->getOne(
                'SELECT student_lrn FROM students WHERE student_lrn = ?',
                [$studentLrn]
            );

            if (!$registeredStudent) {
                throw new Exception('This LRN has no approved student profile yet. Review and approve the parent registration first.');
            }

            $grade = $_POST['grade_level'] ?? '';

            if (!in_array($grade, ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'], true)) {
                throw new Exception('Invalid grade level.');
            }

            $isJhs = in_array($grade, ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'], true);
            $shsTrack = $isJhs ? null : ($_POST['shs_track'] ?? '');

            if (!$isJhs && !in_array($shsTrack, ['Academic', 'TechPro'], true)) {
                throw new Exception('Choose Academic or TechPro for SHS.');
            }

            $existing = $system->getOne(
                'SELECT enrollment_id FROM enrollments WHERE student_lrn = ? AND school_year_id = ? LIMIT 1',
                [$studentLrn, $yearId]
            );

            if ($existing) {
                throw new Exception('This learner already has an enrollment for the Current school year. Open the existing record instead.');
            }

            $section = $_POST['section_id'] !== '' ? (int)$_POST['section_id'] : null;

            if ($section) {
                $check = $system->getOne(
                    'SELECT * FROM class_sections WHERE section_id = ? AND school_year_id = ? AND grade_level = ? FOR UPDATE',
                    [$section, $yearId, $grade]
                );

                if (!$check || $check['section_status'] === 'Closed') {
                    throw new Exception('Selected section is closed or invalid. Selected section does not match the school year and grade level.');
                }

                if (!$isJhs && $check['shs_track'] !== $shsTrack) {
                    throw new Exception('Selected section does not match the SHS track.');
                }

                $count = (int)$system->getOne(
                    "SELECT COUNT(*) AS total FROM enrollments WHERE section_id = ? AND enrollment_status IN ('Officially Enrolled', 'Pending', 'Temporarily Enrolled')",
                    [$section]
                )['total'];

                if ($count >= (int)$check['capacity']) {
                    throw new Exception('This section has reached its capacity.');
                }
            }

            $initialStatus = 'Pending';

            $system->save(
                'INSERT INTO enrollments (student_lrn, school_year_id, grade_level, enrollment_category, shs_track, section_id, enrollment_date, enrollment_status, processed_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $studentLrn,
                    $yearId,
                    $grade,
                    $_POST['enrollment_category'],
                    $shsTrack,
                    $section,
                    $_POST['enrollment_date'],
                    $initialStatus,
                    $_SESSION['user_id']
                ]
            );

            $newEnrollmentId = (int)$pdo->lastInsertId();

            $defaultDocs = [
                ['PSA Birth Certificate', 1],
                ['SF9 / Report Card', 1],
                ['Certificate of Good Moral Character', 1],
                ['Certificate of Transfer / Form 137', ($_POST['enrollment_category'] === 'Transferee' ? 1 : 0)],
                ['2x2 ID Picture', 0]
            ];

            $docInsert = $pdo->prepare(
                'INSERT INTO enrollment_documents (enrollment_id, document_name, is_required) VALUES (?, ?, ?)'
            );

            foreach ($defaultDocs as [$docName, $required]) {
                $docInsert->execute([$newEnrollmentId, $docName, $required]);
            }

            $pdo->commit();
            $_SESSION['message'] = 'Step 2 complete: enrollment record created with a pre-filled requirements checklist. Continue to Step 3 — check the submitted papers and create the student account.';
            $_SESSION['next_lrn'] = $studentLrn;
        } elseif ($module === 'records' && $role === 'School Registrar') {
            $action = $_POST['record_action'] ?? '';

            if (in_array($action, ['teacher', 'subject', 'class_subject'], true)) {
                throw new Exception('Subject creation and teacher assignment are handled by the assigned Adviser under My Advisory Setup.');
            }

            if ($action === 'teacher') {
                throw new Exception('Teacher and Adviser accounts are created by the System Administrator.');
            } elseif ($action === 'subject') {
                $system->save(
                    'INSERT INTO subjects (subject_code, subject_name, grade_level) VALUES (?, ?, ?)',
                    [
                        $_POST['subject_code'],
                        $_POST['subject_name'],
                        $_POST['subject_grade']
                    ]
                );

                $_SESSION['message'] = 'Subject added.';
            } elseif ($action === 'class_subject') {
                $system->save(
                    'INSERT INTO section_subjects (section_id, subject_id, teacher_id, schedule_days, start_time, end_time, room) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE teacher_id = VALUES(teacher_id), schedule_days = VALUES(schedule_days), start_time = VALUES(start_time), end_time = VALUES(end_time), room = VALUES(room)',
                    [
                        $_POST['section_id'],
                        $_POST['subject_id'],
                        $_POST['teacher_id'],
                        $_POST['schedule_days'] ?: null,
                        $_POST['start_time'] ?: null,
                        $_POST['end_time'] ?: null,
                        $_POST['room'] ?: null
                    ]
                );

                $_SESSION['message'] = 'Subject teacher and schedule saved. The adviser will still be able to view every subject in the advisory section.';
            } elseif ($action === 'grade' || $action === 'attendance') {
                if (!in_array($role, ['Subject Teacher', 'Teacher Adviser'], true)) {
                    throw new Exception('Only an assigned teacher can encode subject grades and attendance.');
                }

                $enrollment = $system->getOne(
                    'SELECT * FROM enrollments WHERE enrollment_id = ?',
                    [(int)$_POST['enrollment_id']]
                );

                $class = $system->getOne(
                    'SELECT ss.*, cs.grade_level AS section_grade FROM section_subjects ss JOIN class_sections cs ON ss.section_id = cs.section_id WHERE ss.section_subject_id = ?',
                    [(int)$_POST['section_subject_id']]
                );

                if (
                    !$enrollment ||
                    !$class ||
                    (int)$enrollment['section_id'] !== (int)$class['section_id'] ||
                    $enrollment['grade_level'] !== $class['section_grade']
                ) {
                    throw new Exception('The enrollment and subject assignment do not belong to the same section.');
                }

                if (in_array($role, ['Subject Teacher', 'Teacher Adviser'], true)) {
                    $teacher = $system->getOne(
                        'SELECT teacher_id FROM teachers WHERE user_id = ?',
                        [$_SESSION['user_id']]
                    );

                    if (!$teacher || (int)$class['teacher_id'] !== (int)$teacher['teacher_id']) {
                        throw new Exception('You can only encode grades and attendance for your assigned subjects.');
                    }
                }

                if ($action === 'grade') {
                    throw new Exception('Use the assigned subjects Grade Encoding page.');
                } else {
                    $system->save(
                        'INSERT INTO attendance (enrollment_id, section_subject_id, attendance_date, attendance_status, remarks, recorded_by) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE attendance_status = VALUES(attendance_status), remarks = VALUES(remarks), recorded_by = VALUES(recorded_by)',
                        [
                            $enrollment['enrollment_id'],
                            $class['section_subject_id'],
                            $_POST['attendance_date'],
                            $_POST['attendance_status'],
                            $_POST['attendance_remarks'] ?: null,
                            $_SESSION['user_id']
                        ]
                    );

                    $_SESSION['message'] = 'Subject attendance saved.';
                }
            }
        } elseif (
            $module === 'announcements' &&
            in_array($role, ['School Registrar', 'System Administrator'], true)
        ) {
            $visibility = $_POST['visibility'] ?? '';
            if (!in_array($visibility, ['Public', 'Internal'], true)) throw new Exception('Choose a valid visibility.');
            if ($visibility === 'Public' && $_POST['target_audience'] !== 'All') throw new Exception('Public announcements must target All.');
            if (!empty($_POST['expiry_date']) && $_POST['expiry_date'] < $_POST['publish_date']) throw new Exception('Expiry must not precede publication.');
            $system->save(
                'INSERT INTO announcements (title, content, announcement_category, target_audience, publish_date, expiry_date, announcement_status, visibility, posted_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $_POST['title'],
                    $_POST['content'],
                    $_POST['announcement_category'],
                    $_POST['target_audience'],
                    str_replace('T', ' ', $_POST['publish_date']),
                    ($_POST['expiry_date'] ?? '') ? str_replace('T', ' ', $_POST['expiry_date']) : null,
                    $_POST['announcement_status'],
                    $visibility,
                    $_SESSION['user_id']
                ]
            );

            $_SESSION['message'] = 'Announcement saved.';
        }

        if (empty($_SESSION['message'])) {
            throw new Exception('The requested action is not available for this account.');
        }

        if ($module === 'students' && !empty($_SESSION['next_lrn'])) {
            $_SESSION['enrollment_lrn'] = $_SESSION['next_lrn'];
            unset($_SESSION['next_lrn']);
            redirectModule('enrollments');
        }

        if ($module === 'enrollments' && !empty($_SESSION['next_lrn'])) {
            $_SESSION['enrollment_lrn'] = $_SESSION['next_lrn'];
            unset($_SESSION['next_lrn']);
            header('Location: ../registrar/registrar_requirements.php');
            exit;
        }

        redirectModule($module);
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = 'Database error: ' . ($ex->errorInfo[2] ?? $ex->getMessage());
    } catch (Exception $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $ex->getMessage();
    }
}

require 'includes/header.php';

$schoolYears = $system->getAll("
    SELECT *
    FROM school_years
    WHERE year_status = 'Current'
    ORDER BY school_year_id DESC
");

$currentSchoolYear = $schoolYears[0] ?? null;

$students = $module === 'students'
    ? $system->getAll('SELECT student_lrn, last_name, first_name, birth_date FROM students ORDER BY last_name, first_name LIMIT 500')
    : [];

$sections = $module === 'enrollments'
    ? $system->getAll('SELECT cs.*, sy.school_year_name, (SELECT COUNT(*) FROM enrollments en WHERE en.section_id=cs.section_id AND en.enrollment_status IN (\'Officially Enrolled\',\'Pending\',\'Temporarily Enrolled\')) AS occupied FROM class_sections cs JOIN school_years sy ON sy.school_year_id = cs.school_year_id ORDER BY cs.grade_level, cs.section_name')
    : [];

$lrnHistoryMap = [];
if ($module === 'enrollments') {
    $pastEnrollments = $system->getAll(
        'SELECT e.student_lrn, e.grade_level, e.enrollment_category, e.shs_track
         FROM enrollments e
         JOIN school_years sy ON sy.school_year_id = e.school_year_id
         ORDER BY sy.school_year_id ASC, e.enrollment_id ASC'
    );
    foreach ($pastEnrollments as $pe) {
        $lrnHistoryMap[$pe['student_lrn']] = [
            'last_grade' => $pe['grade_level'],
            'last_category' => $pe['enrollment_category'],
            'last_track' => $pe['shs_track'] ?? ''
        ];
    }

    $appRows = $system->getAll(
        'SELECT student_lrn, enrollment_category, application_data
         FROM student_registration_applications
         ORDER BY application_id ASC'
    );
    foreach ($appRows as $ar) {
        $decoded = json_decode($ar['application_data'] ?? '', true) ?: [];
        $appGrade = $decoded['grade_level'] ?? ($decoded['last_grade_completed'] ?? '');
        $appTrack = $decoded['shs_track'] ?? '';
        if (!isset($lrnHistoryMap[$ar['student_lrn']])) {
            $lrnHistoryMap[$ar['student_lrn']] = [
                'last_grade' => $appGrade,
                'last_category' => $ar['enrollment_category'] ?? 'Incoming',
                'last_track' => $appTrack,
                'from_application' => true
            ];
        } else {
            if (!empty($ar['enrollment_category'])) {
                $lrnHistoryMap[$ar['student_lrn']]['last_category'] = $ar['enrollment_category'];
            }
            if (!empty($appTrack)) {
                $lrnHistoryMap[$ar['student_lrn']]['last_track'] = $appTrack;
            }
        }
    }
}

$teachers = $system->getAll("
    SELECT t.teacher_id, t.first_name, t.last_name
    FROM teachers t
    JOIN users u ON u.user_id = t.user_id
    WHERE t.employment_status = 'Active'
      AND u.role IN ('Subject Teacher', 'Teacher Adviser')
    ORDER BY t.last_name
");

$advisers = $system->getAll("
    SELECT t.teacher_id, t.first_name, t.last_name
    FROM teachers t
    JOIN users u ON u.user_id = t.user_id
    WHERE t.employment_status = 'Active'
      AND u.role = 'Teacher Adviser' AND u.account_status = 'Active'
    ORDER BY t.last_name
");

if (in_array($role, ['Subject Teacher', 'Teacher Adviser'], true)) {
    $enrollments = $system->getAll(
        'SELECT DISTINCT e.*, s.first_name, s.last_name, sy.school_year_name
         FROM enrollments e
         JOIN students s ON e.student_lrn = s.student_lrn
         JOIN school_years sy ON e.school_year_id = sy.school_year_id
         JOIN class_sections cs ON cs.section_id = e.section_id
         LEFT JOIN section_subjects ss ON ss.section_id = cs.section_id
         LEFT JOIN teachers t ON (ss.teacher_id = t.teacher_id OR cs.adviser_teacher_id = t.teacher_id)
         WHERE t.user_id = ?
         ORDER BY e.enrollment_id DESC
         LIMIT 100',
        [$_SESSION['user_id']]
    );

    $sectionSubjects = $system->getAll(
        'SELECT ss.section_subject_id, s.subject_name, cs.section_name
         FROM section_subjects ss
         JOIN subjects s ON ss.subject_id = s.subject_id
         JOIN class_sections cs ON ss.section_id = cs.section_id
         JOIN teachers t ON ss.teacher_id = t.teacher_id
         WHERE t.user_id = ?',
        [$_SESSION['user_id']]
    );
} else {
    $enrollments = $system->getAll("
        SELECT e.*, s.first_name, s.last_name, sy.school_year_name, cs.section_name
        FROM enrollments e
        JOIN students s ON e.student_lrn = s.student_lrn
        JOIN school_years sy ON e.school_year_id = sy.school_year_id
        LEFT JOIN class_sections cs ON cs.section_id = e.section_id
        WHERE sy.year_status = 'Current'
        ORDER BY e.grade_level, cs.section_name, s.last_name, s.first_name
        LIMIT 500
    ");

    $sectionSubjects = $system->getAll("
        SELECT ss.section_subject_id, s.subject_name, cs.section_name
        FROM section_subjects ss
        JOIN subjects s ON ss.subject_id = s.subject_id
        JOIN class_sections cs ON ss.section_id = cs.section_id
    ");
}

$lookupLrn = trim($_SESSION['registrar_lookup_lrn'] ?? '');

$editStudent = ($module === 'students' && $lookupLrn !== '')
    ? $system->getOne('SELECT * FROM students WHERE student_lrn = ?', [$lookupLrn])
    : null;

$stepLrn = trim($_SESSION['enrollment_lrn'] ?? '');

$stepStudent = ($module === 'enrollments' && $stepLrn !== '')
    ? $system->getOne('SELECT student_lrn, user_id FROM students WHERE student_lrn = ?', [$stepLrn])
    : null;

$stepStudentHasLogin = !empty($stepStudent['user_id']);

$programs = [];
$applications = [];
?>

<style>
    .workflow-step.active {
        border: 2px solid #0d6efd !important;
        background-color: #f0f6ff !important;
        box-shadow: 0 2px 8px rgba(13, 110, 253, 0.14);
    }

    .workflow-step.active .workflow-number {
        background-color: #0d6efd !important;
        color: #ffffff !important;
    }

    .workflow-step.active strong {
        color: #0a58ca;
    }

    .workflow-step.completed .workflow-number {
        background-color: #198754 !important;
        color: #ffffff !important;
    }

    .section-summary-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
</style>

<div class="page-heading">
    <div>
        <h1>
            <?= e([
                "students" => "Student Registration",
                "enrollments" => "Enrollment Management",
                "records" => "Student Records",
                "programs" => "Scholarship Programs",
                "applications" => "Scholarship Applications",
                "monitoring" => "Scholarship Monitoring",
                "announcements" => "",
                "reports" => ""
            ][$module] ?? "Module") ?>
        </h1>
    </div>

    <?php if ($module === 'reports'): ?>
        <button
            type="button"
            class="btn btn-outline-primary print-action"
            onclick="if (typeof printSelectedRegistrarReport === 'function' && printSelectedRegistrarReport()) return; window.print()"
            aria-label="Print this page"
            title="Print this page"
        >
            🖨️ Print
        </button>
    <?php endif; ?>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($module === 'students'): ?>

    <div class="card p-3 mb-3">
        <form method="POST" class="row g-2 align-items-end">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <div class="col-md-6">
                <label class="form-label">Search existing student by LRN</label>
                <input
                    class="form-control"
                    name="lookup_student_lrn"
                    maxlength="12"
                    minlength="12"
                    pattern="[0-9]{12}"
                    inputmode="numeric"
                    value="<?= e($lookupLrn) ?>"
                    placeholder="Enter student LRN"
                    required
                >
            </div>

            <div class="col-md-auto">
                <button class="btn btn-outline-primary">Search Profile</button>
            </div>

            <?php if ($editStudent): ?>
                <div class="col-md-auto small text-success">
                    Existing profile found. Details are loaded below.
                </div>
            <?php elseif ($lookupLrn !== ''): ?>
                <div class="col-md-auto small text-muted">
                    No profile found. You can create a new one below.
                </div>
            <?php endif; ?>
        </form>
    </div>

    <div class="workflow-steps">
        <div class="workflow-step active" aria-current="step">
            <span class="workflow-number">1</span>
            <span>
                <strong>Student Profile</strong>
                <small>Search or create the student's permanent record</small>
            </span>
        </div>

        <div class="workflow-step">
            <span class="workflow-number">2</span>
            <span>
                <strong>Enrollment</strong>
                <small>Next, process this school year's enrollment</small>
            </span>
        </div>

        <div class="workflow-step">
            <span class="workflow-number">3</span>
            <span>
                <strong>Requirements & Account</strong>
                <small>Registrar checks paper documents and creates the student login</small>
            </span>
        </div>
    </div>

    <div class="alert alert-info">
        Enter the student’s 12-digit numeric LRN. For returning students, search the existing LRN and update that profile instead of creating another student. No student self-registration or online document upload is used.
    </div>

    <div class="card p-3 mb-4">
        <h5>Step 1 — Student Profile</h5>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <div class="row g-2">
                <?php
                foreach ([
                    'student_lrn' => '12-Digit LRN',
                    'psa_birth_certificate_no' => 'PSA Birth Certificate No.',
                    'last_name' => 'Last Name',
                    'first_name' => 'First Name',
                    'middle_name' => 'Middle Name',
                    'extension_name' => 'Extension Name',
                    'birth_date' => 'Birth Date',
                    'place_of_birth' => 'Place of Birth',
                    'religion' => 'Religion',
                    'mother_tongue' => 'Mother Tongue',
                    'current_house_no' => 'House No.',
                    'current_street_sitio' => 'Street/Sitio',
                    'current_barangay' => 'Barangay',
                    'current_municipality' => 'Municipality/City',
                    'current_province' => 'Province',
                    'current_zip_code' => 'ZIP Code',
                    'father_last_name' => 'Father Last Name',
                    'father_first_name' => 'Father First Name',
                    'father_middle_name' => 'Father Middle Name',
                    'father_contact_number' => 'Father Contact',
                    'mother_maiden_last_name' => 'Mother Maiden Last Name',
                    'mother_maiden_first_name' => 'Mother First Name',
                    'mother_maiden_middle_name' => 'Mother Middle Name',
                    'mother_contact_number' => 'Mother Contact',
                    'guardian_last_name' => 'Guardian Last Name',
                    'guardian_first_name' => 'Guardian First Name',
                    'guardian_middle_name' => 'Guardian Middle Name',
                    'guardian_contact_number' => 'Guardian Contact'
                ] as $name => $label):
                ?>
                    <div class="col-md-3">
                        <label class="form-label"><?= e($label) ?></label>
                        <input
                            class="form-control"
                            name="<?= e($name) ?>"
                            value="<?= $name === 'student_lrn' ? e($lookupLrn) : e($editStudent[$name] ?? '') ?>"
                            <?= in_array($name, ['student_lrn', 'last_name', 'first_name', 'birth_date']) ? 'required' : '' ?>
                            type="<?= $name === 'birth_date' ? 'date' : 'text' ?>"
                            <?= $name === 'student_lrn' ? 'maxlength="12" minlength="12" pattern="[0-9]{12}" inputmode="numeric"' : '' ?>
                        >
                    </div>
                <?php endforeach; ?>

                <div class="col-md-3">
                    <label class="form-label">Sex</label>
                    <select class="form-select" name="sex" required>
                        <option value="Male" <?= (($editStudent['sex'] ?? 'Male') === 'Male') ? 'selected' : '' ?>>
                            Male
                        </option>
                        <option value="Female" <?= (($editStudent['sex'] ?? '') === 'Female') ? 'selected' : '' ?>>
                            Female
                        </option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">IP Member</label>
                    <div>
                        <input
                            type="checkbox"
                            name="is_ip_member"
                            value="1"
                            <?= !empty($editStudent['is_ip_member']) ? 'checked' : '' ?>
                        >
                        Yes
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">4Ps Beneficiary</label>
                    <div>
                        <input
                            type="checkbox"
                            name="is_4ps_beneficiary"
                            value="1"
                            <?= !empty($editStudent['is_4ps_beneficiary']) ? 'checked' : '' ?>
                        >
                        Yes
                    </div>
                </div>
            </div>

            <button class="btn btn-primary mt-3">Save Student</button>
        </form>
    </div>

    <div class="card p-3">
        <h5>Registered Students</h5>

        <div class="table-responsive">
            <table class="table table-sm">
                <tr>
                    <th>LRN</th>
                    <th>Name</th>
                    <th>Birth Date</th>
                    <th>Status</th>
                </tr>

                <?php foreach ($students as $s): ?>
                    <tr>
                        <td><?= e($s['student_lrn']) ?></td>
                        <td><?= e($s['last_name'] . ', ' . $s['first_name']) ?></td>
                        <td><?= e($s['birth_date'] ?? '') ?></td>
                        <td>Registered</td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

<?php elseif ($module === 'enrollments'): ?>

    <div class="workflow-steps">
        <div class="workflow-step completed">
            <span class="workflow-number">1</span>
            <span>
                <strong>Student Profile</strong>
                <small>Saved in Step 1</small>
            </span>
        </div>

        <div class="workflow-step active" aria-current="step">
            <span class="workflow-number">2</span>
            <span>
                <strong>Enrollment</strong>
                <small>Current school year is assigned automatically; select grade, category, and section</small>
            </span>
        </div>

        <div class="workflow-step">
            <span class="workflow-number">3</span>
            <span>
                <strong>Requirements & Account</strong>
                <small>Registrar checks submitted paper documents and creates the login</small>
            </span>
        </div>
    </div>

    <details class="card p-3 mb-3">
        <summary class="fw-bold">Manage Grade &amp; Sections</summary>

        <div class="mt-3">
            <?php if (!$currentSchoolYear): ?>
                <div class="alert alert-warning">
                    No Current school year. Please ask the System Administrator to create or activate one.
                </div>
            <?php else: ?>
                <p class="text-muted">
                    Current School Year:
                    <strong><?= e($currentSchoolYear['school_year_name']) ?></strong>
                    (automatically assigned)
                </p>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="setup_action" value="section">

                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Grade Level</label>
                            <select class="form-select" name="section_grade" required>
                                <?php foreach (['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'] as $grade): ?>
                                    <option><?= e($grade) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Section Name</label>
                            <input class="form-control" name="section_name" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Capacity</label>
                            <input class="form-control" type="number" name="section_capacity" min="1" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">SHS Track (Grades 11–12)</label>
                            <select class="form-select" name="section_shs_track">
                                <option value="">Not applicable (JHS)</option>
                                <option value="Academic">Academic</option>
                                <option value="TechPro">Technical-Professional (TechPro)</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Adviser (optional)</label>
                            <select class="form-select" name="adviser_teacher_id">
                                <option value="">Not assigned</option>
                                <?php foreach ($advisers as $teacher): ?>
                                    <option value="<?= e($teacher['teacher_id']) ?>">
                                        <?= e($teacher['last_name'] . ', ' . $teacher['first_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button class="btn btn-primary mt-3">Create Section</button>
                </form>
                <hr><h6>Current sections</h6>
                <?php foreach ($sections as $sec): if ((int)$sec['school_year_id'] !== (int)$currentSchoolYear['school_year_id']) continue; ?>
                <details class="border rounded p-2 mb-2">
                    <summary><?= e($sec['grade_level'].' / '.$sec['section_name']) ?> — <?= e($sec['occupied'].'/'.$sec['capacity']) ?> — <?= e($sec['section_status']==='Closed'?'Closed':($sec['occupied'] >= $sec['capacity']?'Full':'Open')) ?></summary>
                    <form method="POST" class="row g-2 mt-2">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="setup_action" value="edit_section">
                        <input type="hidden" name="edit_section_id" value="<?= e($sec['section_id']) ?>">
                        <div class="col-md-3"><label>Section name</label><input class="form-control" name="section_name" maxlength="50" required value="<?= e($sec['section_name']) ?>"></div>
                        <div class="col-md-2"><label>Capacity</label><input class="form-control" type="number" name="section_capacity" min="1" max="65535" required value="<?= e($sec['capacity']) ?>"></div>
                        <div class="col-md-3"><label>Adviser</label><select class="form-select" name="adviser_teacher_id"><option value="">Not assigned</option><?php foreach ($advisers as $t): ?><option value="<?= e($t['teacher_id']) ?>" <?= $t['teacher_id']==$sec['adviser_teacher_id']?'selected':'' ?>><?= e($t['last_name'].', '.$t['first_name']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-2"><label>Status</label><select class="form-select" name="section_status"><option value="Open" <?= $sec['section_status']!=='Closed'?'selected':'' ?>>Open</option><option value="Closed" <?= $sec['section_status']==='Closed'?'selected':'' ?>>Closed</option></select></div>
                        <div class="col-md-2 align-self-end"><button class="btn btn-primary">Save</button></div>
                    </form>
                </details>
                <?php endforeach; ?>

            <?php endif; ?>
        </div>
    </details>

    <details class="card p-3 mb-4" open>
        <summary class="fw-bold">Create Enrollment Record</summary>

        <div class="mt-3">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Student LRN</label>
                        <input
                            class="form-control"
                            type="text"
                            name="student_lrn"
                            id="createEnrollStudentLrn"
                            maxlength="12"
                            minlength="12"
                            pattern="[0-9]{12}"
                            inputmode="numeric"
                            value="<?= e($_GET['lrn'] ?? ($_SESSION['enrollment_lrn'] ?? '')) ?>"
                            placeholder="Enter registered LRN"
                            required
                        >
                        <small class="text-muted" id="createEnrollLrnHint">
                            Enter the 12-digit LRN from the saved Student Profile.
                        </small>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">School Year</label>
                        <input
                            class="form-control"
                            value="<?= e($currentSchoolYear['school_year_name'] ?? 'No Current school year') ?>"
                            disabled
                        >
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Grade Level</label>
                        <select class="form-select" name="grade_level" id="createEnrollGradeLevel" required>
                            <?php foreach (['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'] as $g): ?>
                                <option><?= e($g) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="enrollment_category" id="createEnrollCategory">
                            <option>Incoming</option>
                            <option>Returning</option>
                            <option>Transferee</option>
                            <option>Balik-Aral</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Section</label>
                        <select class="form-select" name="section_id" id="createEnrollSectionSelect">
                            <option value="" data-grade="" data-track="">Not assigned</option>
                            <?php foreach ($sections as $s): ?>
                                <?php if (!$currentSchoolYear || (int)$s['school_year_id'] !== (int)$currentSchoolYear['school_year_id'] || $s['section_status']==='Closed' || $s['occupied'] >= $s['capacity']) continue; ?>
                                <option
                                    value="<?= e($s['section_id']) ?>"
                                    data-grade="<?= e($s['grade_level']) ?>"
                                    data-track="<?= e($s['shs_track'] ?? '') ?>"
                                >
                                    <?= e($s['school_year_name'] . ' / ' . $s['grade_level'] . ' / ' . $s['section_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">SHS Track (Grades 11–12)</label>
                        <select class="form-select" name="shs_track" id="createEnrollShsTrack">
                            <option value="">Not applicable (JHS)</option>
                            <option value="Academic">Academic</option>
                            <option value="TechPro">Technical-Professional (TechPro)</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Enrollment Date</label>
                        <input
                            class="form-control"
                            type="date"
                            name="enrollment_date"
                            value="<?= date('Y-m-d') ?>"
                            required
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Initial Status</label>
                        <input
                            class="form-control"
                            value="Pending (physical documents for verification)"
                            disabled
                        >
                    </div>
                </div>

                <button class="btn btn-primary mt-3" <?= $currentSchoolYear ? '' : 'disabled' ?>>
                    Save Enrollment
                </button>
            </form>
        </div>
    </details>
    <script>
    (function() {
        var lrnEl = document.getElementById('createEnrollStudentLrn');
        var hintEl = document.getElementById('createEnrollLrnHint');
        var gradeEl = document.getElementById('createEnrollGradeLevel');
        var catEl = document.getElementById('createEnrollCategory');
        var trackEl = document.getElementById('createEnrollShsTrack');
        var secEl = document.getElementById('createEnrollSectionSelect');
        if (!gradeEl || !secEl) return;

        var lrnHistory = <?= json_encode($lrnHistoryMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        var gradeOrder = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];

        var allSectionOptions = Array.prototype.slice.call(secEl.options).map(function(opt) {
            return {
                value: opt.value,
                text: opt.text,
                grade: opt.getAttribute('data-grade') || '',
                track: opt.getAttribute('data-track') || ''
            };
        });

        function filterCreateEnrollmentSections() {
            var selectedGrade = gradeEl.value;
            var isShs = (selectedGrade === 'Grade 11' || selectedGrade === 'Grade 12');
            var selectedTrack = (trackEl && isShs) ? trackEl.value : '';

            if (trackEl && !isShs) {
                trackEl.value = '';
            }

            var currentVal = secEl.value;
            secEl.innerHTML = '';

            allSectionOptions.forEach(function(item) {
                if (item.value === '') {
                    var blankOpt = document.createElement('option');
                    blankOpt.value = '';
                    blankOpt.text = item.text;
                    secEl.appendChild(blankOpt);
                    return;
                }

                var matchesGrade = (item.grade === selectedGrade);
                var matchesTrack = !isShs || !selectedTrack || (item.track === selectedTrack);

                if (matchesGrade && matchesTrack) {
                    var opt = document.createElement('option');
                    opt.value = item.value;
                    opt.text = item.text;
                    secEl.appendChild(opt);
                }
            });

            var stillValid = Array.prototype.some.call(secEl.options, function(o) {
                return o.value === currentVal;
            });
            secEl.value = stillValid ? currentVal : '';
        }

        function applyLrnAutoDefaults() {
            if (!lrnEl) {
                filterCreateEnrollmentSections();
                return;
            }

            var lrn = lrnEl.value.trim();
            if (!/^[0-9]{12}$/.test(lrn)) {
                if (hintEl) {
                    hintEl.textContent = 'Enter the 12-digit LRN from the saved Student Profile.';
                }
                filterCreateEnrollmentSections();
                return;
            }

            var rec = lrnHistory[lrn];
            if (!rec) {
                if (catEl) catEl.value = 'Incoming';
                if (hintEl) {
                    hintEl.textContent = 'New LRN: defaulted category to Incoming.';
                }
                filterCreateEnrollmentSections();
                return;
            }

            var category = rec.last_category || 'Returning';
            if (rec.from_application) {
                category = rec.last_category || 'Incoming';
            } else if (!['Transferee', 'Balik-Aral'].includes(category)) {
                category = 'Returning';
            }

            var idx = gradeOrder.indexOf(rec.last_grade);
            var targetGrade = rec.last_grade || 'Grade 7';
            if (!rec.from_application && idx !== -1) {
                if (category === 'Balik-Aral') {
                    targetGrade = gradeOrder[idx];
                } else if (idx < gradeOrder.length - 1) {
                    targetGrade = gradeOrder[idx + 1];
                } else {
                    targetGrade = gradeOrder[idx];
                }
            }

            if (gradeOrder.includes(targetGrade)) {
                gradeEl.value = targetGrade;
            }
            if (catEl) {
                catEl.value = category;
            }
            if (trackEl && (targetGrade === 'Grade 11' || targetGrade === 'Grade 12') && rec.last_track) {
                trackEl.value = rec.last_track;
            }
            if (hintEl) {
                hintEl.textContent = 'Matched LRN history: ' + category + ' • ' + targetGrade + '.';
            }

            filterCreateEnrollmentSections();
        }

        if (lrnEl) {
            lrnEl.addEventListener('input', applyLrnAutoDefaults);
            lrnEl.addEventListener('change', applyLrnAutoDefaults);
        }
        gradeEl.addEventListener('change', filterCreateEnrollmentSections);
        if (trackEl) {
            trackEl.addEventListener('change', filterCreateEnrollmentSections);
        }
        applyLrnAutoDefaults();
    })();
    </script>

    <?php
    $documentsByLrn = [];

    if (in_array($role, ['School Registrar', 'System Administrator'], true)) {
        $documentRows = $system->getAll("
            SELECT file_id, student_lrn, document_name, verification_status, remarks
            FROM student_files
            ORDER BY student_lrn, document_name
        ");

        foreach ($documentRows as $documentRow) {
            $documentsByLrn[$documentRow['student_lrn']][] = $documentRow;
        }
    }

    $enrollmentGroups = [];

    foreach ($enrollments as $en) {
        $enrollmentGroups[$en['grade_level']][$en['section_name'] ?? 'Unassigned'][] = $en;
    }

    $availableSectionsByGrade = [];
    foreach ($sections as $sec) {
        if (!$currentSchoolYear || (int)$sec['school_year_id'] !== (int)$currentSchoolYear['school_year_id']) continue;
        if ($sec['section_status'] === 'Closed' || (int)$sec['occupied'] >= (int)$sec['capacity']) continue;
        $availableSectionsByGrade[$sec['grade_level']][] = $sec;
    }

    $canManageTransfers = in_array($role, ['School Registrar', 'System Administrator'], true);
    $secBlockCounter = 0;
    ?>

    <?php if ($role === 'System Administrator'): ?>
        <div class="mb-3">
            <a class="btn btn-outline-primary" href="../registrar/registrar_requirements.php">
                Open Enrollment Verification
            </a>
        </div>
    <?php endif; ?>

    <div class="card p-3">
        <h5>Enrollment Records</h5>

        <?php if (!$enrollmentGroups): ?>
            <p class="text-muted mb-0">No enrollment records yet.</p>
        <?php else: ?>
            <?php foreach ($enrollmentGroups as $grade => $sectionGroups): ?>
                <details class="border rounded p-3 mt-3" open>
                    <summary class="fw-bold">
                        <?= e($grade) ?>
                        (<?= array_sum(array_map('count', $sectionGroups)) ?> learners)
                    </summary>

                    <?php foreach ($sectionGroups as $sectionName => $learners): ?>
                        <?php
                        $secBlockCounter++;
                        $secBlockId = 'secManageBlock_' . $secBlockCounter;
                        $currentSecId = (int)($learners[0]['section_id'] ?? 0);
                        $currentSecTrack = $learners[0]['shs_track'] ?? '';
                        $gradeAvailableSections = $availableSectionsByGrade[$grade] ?? [];
                        if ($currentSecTrack !== '') {
                            $gradeAvailableSections = array_values(array_filter(
                                $gradeAvailableSections,
                                static function ($s) use ($currentSecTrack) {
                                    return ($s['shs_track'] ?? '') === $currentSecTrack;
                                }
                            ));
                        }
                        $gradeAvailableSections = array_values(array_filter(
                            $gradeAvailableSections,
                            static function ($s) use ($currentSecId) {
                                return (int)$s['section_id'] !== $currentSecId;
                            }
                        ));
                        ?>
                        <details class="card border p-3 mb-3 mt-2" open>
                            <summary class="fw-semibold">
                                <div class="section-summary-row w-100">
                                    <span>
                                        Section: <?= e($sectionName) ?>
                                        (<?= count($learners) ?> learners)
                                    </span>

                                    <?php if ($canManageTransfers): ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            onclick="event.preventDefault(); event.stopPropagation(); toggleSectionTransferMode('<?= e($secBlockId) ?>');"
                                        >
                                            Manage
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </summary>

                            <div id="<?= e($secBlockId) ?>" class="mt-2" data-transfer-active="0">
                                <?php if ($canManageTransfers): ?>
                                    <form method="POST" id="transferForm_<?= e($secBlockId) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="enrollment_action" value="transfer_section">

                                        <div class="transfer-toolbar bg-light border rounded p-3 mb-3" style="display:none;">
                                            <div class="row g-2 align-items-end">
                                                <div class="col-md-5">
                                                    <label class="form-label mb-1">
                                                        Transfer selected <strong><?= e($grade) ?></strong> students to section
                                                    </label>
                                                    <select class="form-select form-select-sm" name="new_section_id" required>
                                                        <option value="">-- Select available section --</option>
                                                        <?php foreach ($gradeAvailableSections as $avail): ?>
                                                            <option value="<?= (int)$avail['section_id'] ?>">
                                                                <?= e($avail['section_name'] . ' (' . $avail['occupied'] . '/' . $avail['capacity'] . ' slots)') ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="col-auto">
                                                    <button type="submit" class="btn btn-sm btn-primary">
                                                        Transfer Selected
                                                    </button>
                                                </div>

                                                <div class="col-auto">
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        onclick="toggleSectionTransferMode('<?= e($secBlockId) ?>', false);"
                                                    >
                                                        Cancel
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th class="transfer-select-col" style="display:none; width: 36px;">
                                                            <input
                                                                type="checkbox"
                                                                class="form-check-input select-all-section-students"
                                                                aria-label="Select all students in section"
                                                                onchange="toggleAllSectionStudents('<?= e($secBlockId) ?>', this.checked);"
                                                            >
                                                        </th>
                                                        <th>#</th>
                                                        <th>LRN</th>
                                                        <th>Student</th>
                                                        <th>School Year</th>
                                                        <th>Enrollment Status</th>
                                                        <th>Documents</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    <?php foreach ($learners as $i => $en): ?>
                                                        <tr>
                                                            <td class="transfer-select-col" style="display:none;">
                                                                <input
                                                                    type="checkbox"
                                                                    class="form-check-input section-student-checkbox"
                                                                    name="enrollment_ids[]"
                                                                    value="<?= (int)$en['enrollment_id'] ?>"
                                                                    aria-label="Select <?= e($en['last_name'] . ', ' . $en['first_name']) ?>"
                                                                >
                                                            </td>
                                                            <td><?= $i + 1 ?></td>
                                                            <td><?= e($en['student_lrn']) ?></td>
                                                            <td><?= e($en['last_name'] . ', ' . $en['first_name']) ?></td>
                                                            <td><?= e($en['school_year_name']) ?></td>
                                                            <td><?= e($en['enrollment_status']) ?></td>
                                                            <td>
                                                                <?php $recordDocs = $documentsByLrn[$en['student_lrn']] ?? []; ?>

                                                                <?php if ($recordDocs): ?>
                                                                    <details>
                                                                        <summary class="btn btn-sm btn-outline-primary">
                                                                            View Documents (<?= count($recordDocs) ?>)
                                                                        </summary>

                                                                        <div class="mt-2">
                                                                            <table class="table table-bordered table-sm mb-0">
                                                                                <thead>
                                                                                    <tr>
                                                                                        <th>Document</th>
                                                                                        <th>Status</th>
                                                                                        <th>Remarks</th>
                                                                                        <th>Preview</th>
                                                                                    </tr>
                                                                                </thead>

                                                                                <tbody>
                                                                                    <?php foreach ($recordDocs as $document): ?>
                                                                                        <tr>
                                                                                            <td><?= e($document['document_name']) ?></td>
                                                                                            <td><?= e($document['verification_status']) ?></td>
                                                                                            <td><?= e($document['remarks'] ?? '') ?></td>
                                                                                            <td>
                                                                                                <a
                                                                                                    class="btn btn-sm btn-outline-secondary"
                                                                                                    href="../students/student_file.php?id=<?= (int)$document['file_id'] ?>"
                                                                                                    target="_blank"
                                                                                                    rel="noopener"
                                                                                                >
                                                                                                    Preview
                                                                                                </a>
                                                                                            </td>
                                                                                        </tr>
                                                                                    <?php endforeach; ?>
                                                                                </tbody>
                                                                            </table>
                                                                        </div>
                                                                    </details>
                                                                <?php else: ?>
                                                                    <span class="text-muted">No documents uploaded</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>LRN</th>
                                                    <th>Student</th>
                                                    <th>School Year</th>
                                                    <th>Enrollment Status</th>
                                                    <th>Documents</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <?php foreach ($learners as $i => $en): ?>
                                                    <tr>
                                                        <td><?= $i + 1 ?></td>
                                                        <td><?= e($en['student_lrn']) ?></td>
                                                        <td><?= e($en['last_name'] . ', ' . $en['first_name']) ?></td>
                                                        <td><?= e($en['school_year_name']) ?></td>
                                                        <td><?= e($en['enrollment_status']) ?></td>
                                                        <td>
                                                            <?php $recordDocs = $documentsByLrn[$en['student_lrn']] ?? []; ?>

                                                            <?php if ($recordDocs): ?>
                                                                <details>
                                                                    <summary class="btn btn-sm btn-outline-primary">
                                                                        View Documents (<?= count($recordDocs) ?>)
                                                                    </summary>

                                                                    <div class="mt-2">
                                                                        <table class="table table-bordered table-sm mb-0">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th>Document</th>
                                                                                    <th>Status</th>
                                                                                    <th>Remarks</th>
                                                                                    <th>Preview</th>
                                                                                </tr>
                                                                            </thead>

                                                                            <tbody>
                                                                                <?php foreach ($recordDocs as $document): ?>
                                                                                    <tr>
                                                                                        <td><?= e($document['document_name']) ?></td>
                                                                                        <td><?= e($document['verification_status']) ?></td>
                                                                                        <td><?= e($document['remarks'] ?? '') ?></td>
                                                                                        <td>
                                                                                            <a
                                                                                                class="btn btn-sm btn-outline-secondary"
                                                                                                href="../students/student_file.php?id=<?= (int)$document['file_id'] ?>"
                                                                                                target="_blank"
                                                                                                rel="noopener"
                                                                                            >
                                                                                                Preview
                                                                                            </a>
                                                                                        </td>
                                                                                    </tr>
                                                                                <?php endforeach; ?>
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </details>
                                                            <?php else: ?>
                                                                <span class="text-muted">No documents uploaded</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </details>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
    function toggleSectionTransferMode(blockId, forceState) {
        var block = document.getElementById(blockId);
        if (!block) return;

        var isActive = block.getAttribute('data-transfer-active') === '1';
        var nextActive = (typeof forceState === 'boolean') ? forceState : !isActive;
        block.setAttribute('data-transfer-active', nextActive ? '1' : '0');

        var toolbar = block.querySelector('.transfer-toolbar');
        if (toolbar) {
            toolbar.style.display = nextActive ? 'block' : 'none';
        }

        var selectCols = block.querySelectorAll('.transfer-select-col');
        selectCols.forEach(function(cell) {
            cell.style.display = nextActive ? '' : 'none';
        });

        if (!nextActive) {
            block.querySelectorAll('.section-student-checkbox, .select-all-section-students').forEach(function(cb) {
                cb.checked = false;
            });
        }
    }

    function toggleAllSectionStudents(blockId, checked) {
        var block = document.getElementById(blockId);
        if (!block) return;
        block.querySelectorAll('.section-student-checkbox').forEach(function(cb) {
            cb.checked = checked;
        });
    }
    </script>

<?php elseif ($module === 'records'): ?>

    <?php if ($role === 'School Registrar'): ?>
        <?php
        $studentDocumentRecords = $system->getAll("
            SELECT
                s.student_lrn,
                s.last_name,
                s.first_name,
                COUNT(sf.file_id) AS document_count
            FROM students s
            LEFT JOIN student_files sf ON sf.student_lrn = s.student_lrn
            GROUP BY s.student_lrn, s.last_name, s.first_name
            ORDER BY s.last_name, s.first_name, s.student_lrn
        ");

        $documentRows = $system->getAll("
            SELECT
                sf.file_id,
                sf.student_lrn,
                sf.document_name,
                sf.verification_status,
                sf.remarks,
                sf.uploaded_at
            FROM student_files sf
            JOIN students s ON s.student_lrn = sf.student_lrn
            ORDER BY sf.student_lrn, sf.document_name
        ");

        $documentsByLrn = [];

        foreach ($documentRows as $documentRow) {
            $documentsByLrn[$documentRow['student_lrn']][] = $documentRow;
        }
        ?>

        <div class="card p-3 mb-3">
            <h5>Student Records — Submitted Documents</h5>

            <p class="text-muted small">
                Click View Documents to see the uploaded files and open a preview. Only students with uploaded files have document links.
            </p>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>LRN</th>
                            <th>Student Name</th>
                            <th>Documents</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($studentDocumentRecords as $record): ?>
                            <tr>
                                <td><?= e($record['student_lrn']) ?></td>
                                <td><?= e($record['last_name'] . ', ' . $record['first_name']) ?></td>
                                <td>
                                    <?php $recordDocs = $documentsByLrn[$record['student_lrn']] ?? []; ?>

                                    <?php if ($recordDocs): ?>
                                        <details>
                                            <summary class="btn btn-sm btn-outline-primary">
                                                View Documents (<?= count($recordDocs) ?>)
                                            </summary>

                                            <div class="mt-2">
                                                <table class="table table-bordered table-sm mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Document</th>
                                                            <th>Status</th>
                                                            <th>Remarks</th>
                                                            <th>Preview</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        <?php foreach ($recordDocs as $document): ?>
                                                            <tr>
                                                                <td><?= e($document['document_name']) ?></td>
                                                                <td><?= e($document['verification_status']) ?></td>
                                                                <td><?= e($document['remarks'] ?? '') ?></td>
                                                                <td>
                                                                    <a
                                                                        class="btn btn-sm btn-outline-secondary"
                                                                        href="../students/student_file.php?id=<?= (int)$document['file_id'] ?>"
                                                                        target="_blank"
                                                                        rel="noopener"
                                                                    >
                                                                        View / Preview
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </details>
                                    <?php else: ?>
                                        <span class="text-muted">No documents uploaded</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (!$studentDocumentRecords): ?>
                            <tr>
                                <td colspan="3">No student records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <?php
    if ($role === 'Student'):
        $my = $system->getOne(
            'SELECT student_lrn FROM students WHERE user_id = ?',
            [$_SESSION['user_id']]
        );

        $myLrn = $my['student_lrn'] ?? '';

        $myGrades = $system->getAll(
            'SELECT sy.school_year_name, e.grade_level, s.subject_name, g.grading_period, g.grade_value
             FROM grades g
             JOIN enrollments e ON g.enrollment_id = e.enrollment_id
             JOIN school_years sy ON e.school_year_id = sy.school_year_id
             JOIN section_subjects ss ON g.section_subject_id = ss.section_subject_id
             JOIN subjects s ON ss.subject_id = s.subject_id
             WHERE e.student_lrn = ?
             ORDER BY sy.school_year_name DESC, s.subject_name',
            [$myLrn]
        );

        $myAttendance = $system->getAll(
            'SELECT a.attendance_date, s.subject_name, a.attendance_status
             FROM attendance a
             JOIN enrollments e ON a.enrollment_id = e.enrollment_id
             JOIN section_subjects ss ON a.section_subject_id = ss.section_subject_id
             JOIN subjects s ON ss.subject_id = s.subject_id
             WHERE e.student_lrn = ?
             ORDER BY a.attendance_date DESC
             LIMIT 100',
            [$myLrn]
        );
    ?>

        <div class="card p-3 mb-3">
            <h5>My Grades</h5>

            <table class="table table-sm">
                <tr>
                    <th>School Year</th>
                    <th>Grade</th>
                    <th>Subject</th>
                    <th>Period</th>
                    <th>Grade</th>
                </tr>

                <?php foreach ($myGrades as $g): ?>
                    <tr>
                        <?php foreach (['school_year_name', 'grade_level', 'subject_name', 'grading_period', 'grade_value'] as $c): ?>
                            <td><?= e($g[$c]) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="card p-3">
            <h5>My Subject Attendance</h5>

            <table class="table table-sm">
                <tr>
                    <th>Date</th>
                    <th>Subject</th>
                    <th>Status</th>
                </tr>

                <?php foreach ($myAttendance as $a): ?>
                    <tr>
                        <td><?= e($a['attendance_date']) ?></td>
                        <td><?= e($a['subject_name']) ?></td>
                        <td><?= e($a['attendance_status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

    <?php else: ?>

        <div class="alert alert-info">
            Subject setup is managed by the assigned Adviser. Open <strong>My Advisory Setup</strong> to add subjects, edit a subject marked TBA, assign an active teacher, and maintain the class schedule. The Registrar only creates the school year and advisory sections.
        </div>

    <?php endif; ?>

<?php elseif ($module === 'announcements'): ?>

    <?php if (in_array($role, ['School Registrar', 'System Administrator'], true)): ?>
        <details class="card p-3 mb-3">
            <summary class="fw-bold">Create Announcement</summary>

            <div class="mt-3">
                <h5>Create Announcement</h5>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                    <label class="form-label">Title</label>
                    <input class="form-control mb-2" name="title" required>

                    <label class="form-label">Content</label>
                    <textarea class="form-control mb-2" name="content" rows="4" required></textarea>

                    <div class="row">
                        <div class="col">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="announcement_category">
                                <?php foreach (['Enrollment', 'Scholarship', 'School', 'General'] as $v): ?>
                                    <option><?= e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col">
                            <label class="form-label">Audience</label>
                            <select class="form-select" name="target_audience">
                                <?php foreach (['All', 'Students', 'Teachers', 'Registrar', 'Subject Teachers', 'Teacher Advisers'] as $v): ?>
                                    <option><?= e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col">
                            <label class="form-label">Publish Date</label>
                            <input
                                class="form-control"
                                type="datetime-local"
                                name="publish_date"
                                value="<?= date('Y-m-d\TH:i') ?>"
                                required
                            >
                        </div>

                        <div class="col">
                            <label class="form-label">Expiry Date</label>
                            <input class="form-control" type="datetime-local" name="expiry_date">
                        </div>
                    </div>

                    <label class="form-label mt-2">Visibility</label>
                    <select class="form-select mb-2" name="visibility" required><option value="Internal">Internal (signed-in users)</option><option value="Public">Public (login page, All audience)</option></select>
                    <label class="form-label mt-2">Status</label>
                    <select class="form-select mb-3" name="announcement_status">
                        <option>Draft</option>
                        <option>Published</option>
                        <option>Archived</option>
                    </select>

                    <button class="btn btn-primary">Save Announcement</button>
                </form>
            </div>
        </details>
    <?php endif; ?>

    <div class="card p-3">
        <h5>Announcements</h5>

        <?php
        $manageAnnouncements = in_array($role, ['School Registrar', 'System Administrator'], true);
        $ann = $manageAnnouncements ? $pdo->query("SELECT a.*, CASE WHEN a.expiry_date < NOW() AND a.announcement_status = 'Published' THEN 'Expired' WHEN a.publish_date > NOW() AND a.announcement_status = 'Published' THEN 'Scheduled' ELSE a.announcement_status END AS display_status FROM announcements a ORDER BY a.publish_date DESC, a.announcement_id DESC")->fetchAll() : visibleAnnouncements($pdo, $role);

        foreach ($ann as $a):
        ?>
            <div class="border-bottom py-3">
                <h5><?= e($a['title']) ?></h5>
                <p><?= nl2br(e($a['content'])) ?></p>
                <span class="small-note">
                    <?= e($a['announcement_category'] . ' | ' . $a['publish_date']) ?>
                    <?php if ($manageAnnouncements): ?> | <?= e($a['display_status'] . ' | ' . $a['visibility'] . ' | ' . $a['target_audience']) ?><?php endif; ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>

<?php elseif ($module === 'reports'): ?>

    <?php if (in_array($role, ['System Administrator', 'School Registrar'], true)): ?>
        <div class="card p-3 mb-3 registrar-report-picker">
            <h5>
                <?= $role === 'System Administrator' ? 'Principal Reports' : 'Registrar Reports' ?>
            </h5>

            <p class="text-muted mb-3">
            Select a form to display.
            </p>

            <div class="d-flex flex-wrap gap-2">
                <button
                    type="button"
                    class="btn btn-outline-primary registrar-form-btn"
                    data-form="SF1"
                    onclick="showRegistrarForm('SF1', this)"
                >
                    SF1 — School Register
                </button>

                <button
                    type="button"
                    class="btn btn-outline-primary registrar-form-btn"
                    data-form="SF2"
                    onclick="showRegistrarForm('SF2', this)"
                >
                    SF2 — Attendance
                </button>

                <button
                    type="button"
                    class="btn btn-outline-primary registrar-form-btn"
                    data-form="SF5"
                    onclick="showRegistrarForm('SF5', this)"
                >
                    SF5 — Promotion
                </button>
            </div>
        </div>

        <div
            id="registrar-report-container"
            class="card p-2 mb-3"
            style="display:none"
        >
            <iframe
                id="registrar-report-frame"
                title="Selected Registrar School Form"
                style="width:100%;min-height:750px;border:0;display:block"
            ></iframe>
        </div>

        <style>
            @media print {
                .registrar-report-picker {
                    display: none !important;
                }

                #registrar-report-container {
                    border: 0 !important;
                    box-shadow: none !important;
                    padding: 0 !important;
                }
            }
        </style>

        <script>
            function printSelectedRegistrarReport() {
                var frame = document.getElementById('registrar-report-frame');
                var container = document.getElementById('registrar-report-container');

                if (!frame || !container || container.style.display === 'none' || !frame.contentWindow) {
                    return false;
                }

                frame.contentWindow.focus();
                frame.contentWindow.print();

                return true;
            }

            function showRegistrarForm(form, button) {
                if (!['SF1', 'SF2', 'SF5'].includes(form)) {
                    return;
                }

                document.querySelectorAll('.registrar-form-btn').forEach(function(btn) {
                    btn.classList.toggle('btn-primary', btn === button);
                    btn.classList.toggle('btn-outline-primary', btn !== button);
                });

                var container = document.getElementById('registrar-report-container');
                var frame = document.getElementById('registrar-report-frame');

                container.style.display = 'block';
                frame.onload = prepareRegistrarReportFrame;
                frame.src = '../admin/principal_reports.php?form=' + encodeURIComponent(form);
            }

            function prepareRegistrarReportFrame() {
                var frame = document.getElementById('registrar-report-frame');

                try {
                    var doc = frame.contentDocument;

                    if (!doc || !doc.body) {
                        return;
                    }

                    var style = doc.createElement('style');

                    style.textContent = '.topbar,.sidebar,.page-heading,.report-nav,.print-control{display:none!important}.main-content{margin:0!important;width:100%!important;max-width:none!important;padding:12px!important}.app-layout{display:block!important}body{background:white!important}';

                    doc.head.appendChild(style);

                    function resize() {
                        frame.style.height = Math.max(
                            750,
                            doc.documentElement.scrollHeight + 30,
                            doc.body.scrollHeight + 30
                        ) + 'px';
                    }

                    resize();

                    if (typeof ResizeObserver !== 'undefined') {
                        if (frame._registrarResizeObserver) {
                            frame._registrarResizeObserver.disconnect();
                        }

                        frame._registrarResizeObserver = new ResizeObserver(resize);
                        frame._registrarResizeObserver.observe(doc.body);
                    }
                } catch (err) {
                }
            }
        </script>
    <?php endif; ?>

    <?php if ($role === 'Teacher Adviser'): ?>
        <div class="card p-3">
            <h5>My Assigned Classes</h5>

            <table class="table">
                <tr>
                    <th>Section</th>
                    <th>Subject</th>
                </tr>

                <?php
                foreach (
                    $system->getAll(
                        'SELECT cs.section_name, s.subject_name
                         FROM section_subjects ss
                         JOIN subjects s ON ss.subject_id = s.subject_id
                         JOIN teachers t ON ss.teacher_id = t.teacher_id
                         WHERE t.user_id = ?',
                        [$_SESSION['user_id']]
                    ) as $r
                ):
                ?>
                    <tr>
                        <td><?= e($r['section_name']) ?></td>
                        <td><?= e($r['subject_name']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php require 'includes/footer.php'; ?>