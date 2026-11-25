<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'includes/functions.php';

requireRole(['Teacher Adviser']);

$userId = (int)$_SESSION['user_id'];
$q = $pdo->prepare('SELECT teacher_id FROM teachers WHERE user_id = ?');
$q->execute([$userId]);
$teacherId = (int)$q->fetchColumn();

if (!$teacherId) {
    http_response_code(403);
    exit('Your staff profile is not linked to an Adviser record. Ask the System Administrator to check your account.');
}

$hasLegacySemester = (bool)$pdo->query("SHOW COLUMNS FROM section_subjects LIKE 'semester'")->fetch();
$message = '';
$error = '';

$sectionsQ = $pdo->prepare("
    SELECT cs.section_id, cs.grade_level, cs.section_name, sy.school_year_name
    FROM class_sections cs
    JOIN school_years sy ON sy.school_year_id = cs.school_year_id
    WHERE cs.adviser_teacher_id = ? AND sy.year_status = 'Current'
    ORDER BY sy.school_year_name DESC, cs.grade_level, cs.section_name
");
$sectionsQ->execute([$teacherId]);
$sections = $sectionsQ->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    try {
        $sectionId = (int)($_POST['section_id'] ?? 0);
        $check = $pdo->prepare('SELECT cs.* FROM class_sections cs JOIN school_years sy ON sy.school_year_id=cs.school_year_id WHERE cs.section_id = ? AND cs.adviser_teacher_id = ? AND sy.year_status = \'Current\'');
        $check->execute([$sectionId, $teacherId]);
        $section = $check->fetch();

        if (!$section) {
            throw new Exception('You can only manage subjects under your own advisory section.');
        }

        $action = $_POST['action'] ?? '';

        if ($action === 'add_subject') {
            $code = strtoupper(trim($_POST['subject_code'] ?? ''));
            $name = trim($_POST['subject_name'] ?? '');
            $teacherChoice = trim($_POST['teacher_id'] ?? '');

            if ($code === '' || $name === '') {
                throw new Exception('Enter both subject code and subject name.');
            }

            $pdo->beginTransaction();

            $find = $pdo->prepare('SELECT subject_id FROM subjects WHERE subject_code = ?');
            $find->execute([$code]);
            $subjectId = $find->fetchColumn();

            if ($subjectId) {
                $sub = $pdo->prepare('SELECT grade_level FROM subjects WHERE subject_id = ?');
                $sub->execute([$subjectId]);
                if ($sub->fetchColumn() !== $section['grade_level']) {
                    throw new Exception('That subject code is already used for another grade level.');
                }
                $pdo->prepare("UPDATE subjects SET subject_name = ?, subject_status = 'Active' WHERE subject_id = ?")
                    ->execute([$name, $subjectId]);
            } else {
                $pdo->prepare('INSERT INTO subjects(subject_code, subject_name, grade_level) VALUES(?, ?, ?)')
                    ->execute([$code, $name, $section['grade_level']]);
                $subjectId = (int)$pdo->lastInsertId();
            }

            $assignedTeacher = null;
            if ($teacherChoice !== '') {
                $t = $pdo->prepare("
                    SELECT t.teacher_id
                    FROM teachers t
                    JOIN users u ON u.user_id = t.user_id
                    WHERE t.teacher_id = ?
                      AND t.employment_status = 'Active'
                      AND u.account_status = 'Active'
                      AND u.role IN ('Subject Teacher', 'Teacher Adviser')
                ");
                $t->execute([(int)$teacherChoice]);
                $assignedTeacher = $t->fetchColumn();
                if (!$assignedTeacher) {
                    throw new Exception('Select an active Subject Teacher or Teacher Adviser account.');
                }
            }

            $existing = $pdo->prepare("SELECT section_subject_id FROM section_subjects WHERE section_id = ? AND subject_id = ?  LIMIT 1");
            $existing->execute([$sectionId, $subjectId]);
            $existingId = $existing->fetchColumn();

            if ($existingId) {
                $pdo->prepare('UPDATE section_subjects SET teacher_id = ? WHERE section_subject_id = ?')
                    ->execute([$assignedTeacher, $existingId]);
            } else {
                $pdo->prepare($hasLegacySemester
                    ? "INSERT INTO section_subjects(section_id, subject_id, teacher_id, semester) VALUES(?, ?, ?, 'Full Year')"
                    : "INSERT INTO section_subjects(section_id, subject_id, teacher_id) VALUES(?, ?, ?)")
                    ->execute([$sectionId, $subjectId, $assignedTeacher]);
            }

            $pdo->commit();
            $message = 'Subject saved. If the teacher is still TBA, you can assign one later from this page.';

        } elseif ($action === 'assign_teacher') {
            $rowId = (int)($_POST['section_subject_id'] ?? 0);
            $choice = trim($_POST['teacher_id'] ?? '');
            $teacherChoice = $choice === '' ? null : (int)$choice;

            $row = $pdo->prepare('SELECT ss.section_subject_id FROM section_subjects ss WHERE ss.section_subject_id = ? AND ss.section_id = ?');
            $row->execute([$rowId, $sectionId]);
            if (!$row->fetch()) {
                throw new Exception('Subject assignment not found in your advisory section.');
            }

            if ($teacherChoice !== null) {
                $t = $pdo->prepare("
                    SELECT t.teacher_id
                    FROM teachers t
                    JOIN users u ON u.user_id = t.user_id
                    WHERE t.teacher_id = ?
                      AND t.employment_status = 'Active'
                      AND u.account_status = 'Active'
                      AND u.role IN ('Subject Teacher', 'Teacher Adviser')
                ");
                $t->execute([$teacherChoice]);
                if (!$t->fetchColumn()) {
                    throw new Exception('Select an active Subject Teacher or Teacher Adviser account.');
                }
            }

            $pdo->prepare('UPDATE section_subjects SET teacher_id = ? WHERE section_subject_id = ?')
                ->execute([$teacherChoice, $rowId]);
            $message = $teacherChoice ? 'Subject teacher assignment updated.' : 'Subject teacher set to TBA.';

        } elseif ($action === 'save_schedule') {
            $rowId = (int)($_POST['section_subject_id'] ?? 0);
            $row = $pdo->prepare('SELECT section_subject_id FROM section_subjects WHERE section_subject_id = ? AND section_id = ?');
            $row->execute([$rowId, $sectionId]);
            if (!$row->fetch()) {
                throw new Exception('Subject not found in your advisory section.');
            }

            $start = trim($_POST['start_time'] ?? '');
            $end = trim($_POST['end_time'] ?? '');
            if ($start !== '' && $end !== '' && $start >= $end) {
                throw new Exception('End time must be later than start time.');
            }

            $pdo->prepare('UPDATE section_subjects SET schedule_days = ?, start_time = ?, end_time = ?, room = ? WHERE section_subject_id = ?')
                ->execute([
                    trim($_POST['schedule_days'] ?? '') ?: null,
                    $start ?: null,
                    $end ?: null,
                    trim($_POST['room'] ?? '') ?: null,
                    $rowId,
                ]);
            $message = 'Subject schedule updated.';

        } elseif ($action === 'bulk_update') {
            $pdo->beginTransaction();

            foreach (($_POST['rows'] ?? []) as $rowId => $rowData) {
                $rowId = (int)$rowId;
                $check = $pdo->prepare('SELECT ss.subject_id FROM section_subjects ss WHERE ss.section_subject_id = ? AND ss.section_id = ?');
                $check->execute([$rowId, $sectionId]);
                $subjectId = $check->fetchColumn();

                if (!$subjectId) {
                    throw new Exception('A subject row does not belong to this advisory section.');
                }

                $code = strtoupper(trim($rowData['subject_code'] ?? ''));
                $name = trim($rowData['subject_name'] ?? '');
                $teacherChoice = trim($rowData['teacher_id'] ?? '');
                $days = trim($rowData['schedule_days'] ?? '');
                $start = trim($rowData['start_time'] ?? '');
                $end = trim($rowData['end_time'] ?? '');
                $room = trim($rowData['room'] ?? '');

                if ($code === '' || $name === '') {
                    throw new Exception('Every subject needs a subject code and name.');
                }
                if ($start !== '' && $end !== '' && $start >= $end) {
                    throw new Exception('End time must be later than start time.');
                }

                $teacherIdChoice = null;
                if ($teacherChoice !== '') {
                    $t = $pdo->prepare("
                        SELECT t.teacher_id
                        FROM teachers t
                        JOIN users u ON u.user_id = t.user_id
                        WHERE t.teacher_id = ?
                          AND t.employment_status = 'Active'
                          AND u.account_status = 'Active'
                          AND u.role IN ('Subject Teacher', 'Teacher Adviser')
                    ");
                    $t->execute([(int)$teacherChoice]);
                    $teacherIdChoice = $t->fetchColumn();
                    if (!$teacherIdChoice) {
                        throw new Exception('Select a valid active teaching account.');
                    }
                }

                $pdo->prepare('UPDATE subjects SET subject_code = ?, subject_name = ? WHERE subject_id = ?')
                    ->execute([$code, $name, $subjectId]);
                $pdo->prepare('UPDATE section_subjects SET teacher_id = ?, schedule_days = ?, start_time = ?, end_time = ?, room = ? WHERE section_subject_id = ?')
                    ->execute([$teacherIdChoice, $days ?: null, $start ?: null, $end ?: null, $room ?: null, $rowId]);
            }

            $pdo->commit();
            $message = 'All subject details, teacher assignments, and schedules were updated together.';

        } elseif ($action === 'update_subject') {
            $rowId = (int)($_POST['section_subject_id'] ?? 0);
            $code = strtoupper(trim($_POST['subject_code'] ?? ''));
            $name = trim($_POST['subject_name'] ?? '');

            $row = $pdo->prepare('SELECT ss.subject_id FROM section_subjects ss WHERE ss.section_subject_id = ? AND ss.section_id = ?');
            $row->execute([$rowId, $sectionId]);
            $subjectId = $row->fetchColumn();

            if (!$subjectId) {
                throw new Exception('Subject not found in your advisory section.');
            }
            if ($code === '' || $name === '') {
                throw new Exception('Subject code and name are required.');
            }

            $pdo->prepare('UPDATE subjects SET subject_code = ?, subject_name = ? WHERE subject_id = ?')
                ->execute([$code, $name, $subjectId]);
            $message = 'Subject details updated.';

        } else {
            throw new Exception('Unknown action.');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e instanceof PDOException
            ? 'Unable to save. Check for a duplicate subject code or assignment. Details: ' . ($e->errorInfo[2] ?? $e->getMessage())
            : $e->getMessage();
    }
}

$teachers = $pdo->query("
    SELECT t.teacher_id, t.first_name, t.middle_name, t.last_name
    FROM teachers t
    JOIN users u ON u.user_id = t.user_id
    WHERE t.employment_status = 'Active'
      AND u.account_status = 'Active'
      AND u.role IN ('Subject Teacher', 'Teacher Adviser')
    ORDER BY t.last_name, t.first_name
")->fetchAll();

$selectedSection = (int)($_GET['section_id'] ?? $_POST['section_id'] ?? ($sections[0]['section_id'] ?? 0));
$validIds = array_map(fn($s) => (int)$s['section_id'], $sections);
if (!in_array($selectedSection, $validIds, true)) {
    $selectedSection = 0;
}

$assignments = [];
if ($selectedSection) {
    $q = $pdo->prepare("
        SELECT ss.section_subject_id, ss.schedule_days, ss.start_time, ss.end_time, ss.room,
               s.subject_code, s.subject_name, t.teacher_id,
               CONCAT_WS(' ', t.first_name, t.middle_name, t.last_name) AS teacher_name
        FROM section_subjects ss
        JOIN subjects s ON s.subject_id = ss.subject_id
        LEFT JOIN teachers t ON t.teacher_id = ss.teacher_id
        WHERE ss.section_id = ? 
        ORDER BY s.subject_name
    ");
    $q->execute([$selectedSection]);
    $assignments = $q->fetchAll();
}

require 'includes/header.php';
?>

<div class="page-heading">
    <div>
        <h1>Class Setup</h1>
        <p>Create subjects for your advisory section and assign active subject teachers. You can leave a teacher as TBA and assign one later.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<?php if (!$sections): ?>
    <div class="card p-4">No advisory section is assigned to you yet. Ask the Registrar to assign you as an Adviser.</div>
<?php else: ?>
    <div class="card p-3 mb-3">
        <label class="form-label">Choose your advisory section</label>
        <form method="get">
            <select class="form-select" name="section_id" onchange="this.form.submit()">
                <?php foreach ($sections as $s): ?>
                    <option value="<?= $s['section_id'] ?>" <?= $selectedSection === (int)$s['section_id'] ? 'selected' : '' ?>>
                        <?= e($s['grade_level'] . ' - ' . $s['section_name'] . ' · ' . $s['school_year_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if ($selectedSection): ?>
        <details class="card p-3 mb-3">
            <summary class="fw-bold">+ Create Subject</summary>
            <div class="mt-3">
                <h5>Add a Subject</h5>
                <form method="post" class="row g-2">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="action" value="add_subject">
                    <input type="hidden" name="section_id" value="<?= $selectedSection ?>">

                    <div class="col-md-2">
                        <label class="form-label">Subject Code</label>
                        <input class="form-control" name="subject_code" placeholder="e.g. ENG8" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Subject Name</label>
                        <input class="form-control" name="subject_name" placeholder="e.g. English 8" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">School Year</label>
                        <input class="form-control" value="Full Year" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Subject Teacher</label>
                        <select class="form-select" name="teacher_id">
                            <option value="">TBA - assign later</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?= $t['teacher_id'] ?>">
                                    <?= e($t['last_name'] . ', ' . $t['first_name'] . ' ' . ($t['middle_name'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button class="btn btn-primary w-100">Add</button>
                    </div>
                </form>
            </div>
        </details>

        <div class="card p-3">
            <h5>Subjects in My Advisory Section</h5>
            <p class="small text-muted">Edit the subject, assigned teacher, and schedule in the table, then save everything once.</p>

            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="action" value="bulk_update">
                <input type="hidden" name="section_id" value="<?= $selectedSection ?>">

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Subject</th>
                                <th>Assigned Teacher</th>
                                <th>Schedule</th>
                                <th>Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $i => $a): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <input class="form-control form-control-sm mb-1" name="rows[<?= $a['section_subject_id'] ?>][subject_code]" value="<?= e($a['subject_code']) ?>" aria-label="Subject code">
                                        <input class="form-control form-control-sm" name="rows[<?= $a['section_subject_id'] ?>][subject_name]" value="<?= e($a['subject_name']) ?>" aria-label="Subject name">
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm" name="rows[<?= $a['section_subject_id'] ?>][teacher_id]">
                                            <option value="">TBA</option>
                                            <?php foreach ($teachers as $t): ?>
                                                <option value="<?= $t['teacher_id'] ?>" <?= $a['teacher_id'] == $t['teacher_id'] ? 'selected' : '' ?>>
                                                    <?= e($t['last_name'] . ', ' . $t['first_name'] . ' ' . ($t['middle_name'] ?? '')) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input class="form-control form-control-sm mb-1" name="rows[<?= $a['section_subject_id'] ?>][schedule_days]" value="<?= e($a['schedule_days'] ?? '') ?>" placeholder="Days">
                                        <div class="d-flex gap-1">
                                            <input class="form-control form-control-sm" type="time" name="rows[<?= $a['section_subject_id'] ?>][start_time]" value="<?= e($a['start_time'] ?? '') ?>">
                                            <input class="form-control form-control-sm" type="time" name="rows[<?= $a['section_subject_id'] ?>][end_time]" value="<?= e($a['end_time'] ?? '') ?>">
                                        </div>
                                        <input class="form-control form-control-sm mt-1" name="rows[<?= $a['section_subject_id'] ?>][room]" value="<?= e($a['room'] ?? '') ?>" placeholder="Room">
                                    </td>
                                    <td><span class="text-muted small">Included in Save All</span></td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (!$assignments): ?>
                                <tr>
                                    <td colspan="5" class="text-muted">No subjects have been added yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($assignments): ?>
                    <button class="btn btn-primary">Save All Updates</button>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>