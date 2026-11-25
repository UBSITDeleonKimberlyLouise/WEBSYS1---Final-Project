<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'includes/functions.php';

requireRole(['Teacher Adviser']);

$teacherStmt = $pdo->prepare('SELECT teacher_id FROM teachers WHERE user_id = ?');
$teacherStmt->execute([$_SESSION['user_id']]);
$teacherId = (int)$teacherStmt->fetchColumn();

$sectionsStmt = $pdo->prepare("
    SELECT cs.section_id, cs.grade_level, cs.section_name, sy.school_year_name
    FROM class_sections cs
    JOIN school_years sy ON sy.school_year_id = cs.school_year_id
    WHERE cs.adviser_teacher_id = ? AND sy.year_status = 'Current'
    ORDER BY cs.grade_level, cs.section_name
");
$sectionsStmt->execute([$teacherId]);
$sections = $sectionsStmt->fetchAll(PDO::FETCH_ASSOC);

$sectionId = (int)($_GET['section_id'] ?? $_POST['section_id'] ?? ($sections[0]['section_id'] ?? 0));
$allowed = array_column($sections, 'section_id');
if (!in_array($sectionId, array_map('intval', $allowed), true)) {
    $sectionId = 0;
}

$date = (string)($_GET['date'] ?? $_POST['date'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !checkdate((int)substr($date, 5, 2), (int)substr($date, 8, 2), (int)substr($date, 0, 4))) {
    $date = date('Y-m-d');
}

$error = '';
$success = '';
$students = [];

if ($sectionId) {
    $q = $pdo->prepare("
        SELECT e.enrollment_id, e.student_lrn, st.last_name, st.first_name, st.sex
        FROM enrollments e
        JOIN students st ON st.student_lrn = e.student_lrn
        JOIN class_sections cs ON cs.section_id = e.section_id
        WHERE e.section_id = ?
          AND e.school_year_id = cs.school_year_id
          AND e.enrollment_status = 'Officially Enrolled'
          AND e.enrollment_date <= ?
        ORDER BY st.last_name, st.first_name
    ");
    $q->execute([$sectionId, $date]);
    $students = $q->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!$sectionId) {
            throw new RuntimeException('No advisory section assigned.');
        }
        if (!hash_equals((string)($_SESSION['attendance_csrf'] ?? ''), (string)($_POST['csrf'] ?? ''))) {
            throw new RuntimeException('Session expired. Reload the page.');
        }
        if ($date > date('Y-m-d')) {
            throw new RuntimeException('Future dates cannot be marked.');
        }
        if ((int)date('N', strtotime($date)) >= 6) {
            throw new RuntimeException('Weekend attendance needs school calendar verification.');
        }
        if (!$students) {
            throw new RuntimeException('No officially enrolled students for this date.');
        }

        $valid = ['Present', 'Absent', 'Tardy', 'Excused'];
        $posted = $_POST['status'] ?? [];

        if (!is_array($posted) || count($posted) !== count($students)) {
            throw new RuntimeException('Please mark every learner before saving.');
        }

        foreach ($students as $s) {
            $id = (int)$s['enrollment_id'];
            if (!isset($posted[$id]) || !in_array($posted[$id], $valid, true)) {
                throw new RuntimeException('Please mark every learner before saving.');
            }
        }

        $pdo->beginTransaction();

        $save = $pdo->prepare('
            INSERT INTO official_daily_attendance (enrollment_id, attendance_date, attendance_status, remarks, recorded_by)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                attendance_status = VALUES(attendance_status),
                remarks = VALUES(remarks),
                recorded_by = VALUES(recorded_by)
        ');

        foreach ($students as $s) {
            $id = (int)$s['enrollment_id'];
            $save->execute([$id, $date, $posted[$id], null, (int)$_SESSION['user_id']]);
        }

        $pdo->commit();
        $success = 'Official daily attendance saved successfully.';
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Adviser attendance: ' . $ex->getMessage());
        $error = $ex instanceof PDOException
            ? 'Unable to save. Check if you imported 01_official_attendance.sql.'
            : $ex->getMessage();
    }
}

if (empty($_SESSION['attendance_csrf'])) {
    $_SESSION['attendance_csrf'] = bin2hex(random_bytes(24));
}

$existing = [];
if ($sectionId) {
    try {
        $q = $pdo->prepare('
            SELECT a.enrollment_id, a.attendance_status
            FROM official_daily_attendance a
            JOIN enrollments e ON e.enrollment_id = a.enrollment_id
            WHERE e.section_id = ? AND a.attendance_date = ?
        ');
        $q->execute([$sectionId, $date]);
        $existing = $q->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $ex) {
        $error = 'Official attendance table is missing. Import 01_official_attendance.sql first.';
    }
}

require 'includes/header.php';
?>

<div class="page-heading">
    <div>
        <h1>Official Daily Attendance</h1>
        <p>Advisory class attendance for SF2 and monthly summaries. One record per learner per school day.</p>
    </div>
    <a class="btn btn-outline-primary" href="../advisers/adviser_monthly_attendance.php?section_id=<?= $sectionId ?>">Monthly Summary</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-warning"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<div class="card p-3 mb-3">
    <form method="get" class="row g-3 align-items-end">
        <div class="col-md-7">
            <label class="form-label fw-semibold">My Advisory Section</label>
            <select class="form-select" name="section_id" required>
                <?php foreach ($sections as $s): ?>
                    <option value="<?= (int)$s['section_id'] ?>" <?= $sectionId === (int)$s['section_id'] ? 'selected' : '' ?>>
                        <?= e($s['school_year_name'] . ' · ' . $s['grade_level'] . ' · ' . $s['section_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label fw-semibold">Attendance Date</label>
            <input class="form-control" type="date" name="date" value="<?= e($date) ?>" max="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="col-md-2">
            <button class="btn btn-primary w-100">Load</button>
        </div>
    </form>
</div>

<?php if (!$sections): ?>
    <div class="alert alert-info">No current advisory section assigned.</div>
<?php elseif (!$students): ?>
    <div class="alert alert-info">No officially enrolled learners in this section for the selected date.</div>
<?php else: ?>
    <div class="card p-3">
        <form method="post" id="attendanceForm">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['attendance_csrf']) ?>">
            <input type="hidden" name="section_id" value="<?= $sectionId ?>">
            <input type="hidden" name="date" value="<?= e($date) ?>">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h5 class="mb-1">Learners <span class="text-muted">(<?= count($students) ?>)</span></h5>
                    <small class="text-muted">Choose P, A, T, or E. Unmarked learners are not saved.</small>
                </div>
                <button class="btn btn-outline-primary btn-sm" type="button" onclick="document.querySelectorAll('input[data-attendance][value=Present]').forEach(el => el.checked = true); updateCounts()">Mark All Present</button>
            </div>

            <div class="row g-2 mb-3">
                <div class="col"><div class="border rounded p-2 text-center">Present <strong id="countPresent">0</strong></div></div>
                <div class="col"><div class="border rounded p-2 text-center">Absent <strong id="countAbsent">0</strong></div></div>
                <div class="col"><div class="border rounded p-2 text-center">Unmarked <strong id="countUnmarked">0</strong></div></div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Learner</th>
                            <th>Attendance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $i => $s):
                            $id = (int)$s['enrollment_id'];
                        ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <strong><?= e($s['last_name'] . ', ' . $s['first_name']) ?></strong>
                                    <div class="text-muted small"><?= e($s['student_lrn']) ?></div>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach (['Present' => 'P', 'Absent' => 'A', 'Tardy' => 'T', 'Excused' => 'E'] as $status => $short): ?>
                                            <label class="btn btn-sm btn-outline-secondary">
                                                <input
                                                    data-attendance
                                                    type="radio"
                                                    name="status[<?= $id ?>]"
                                                    value="<?= $status ?>"
                                                    <?= ($existing[$id] ?? '') === $status ? 'checked' : '' ?>
                                                    onchange="updateCounts()"
                                                >
                                                <?= $short ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted">P = Present · A = Absent · T = Tardy · E = Excused</small>
                <button class="btn btn-primary" type="submit" <?= $error ? 'disabled' : '' ?>>Save Attendance</button>
            </div>
        </form>
    </div>

    <script>
    function updateCounts() {
        const all = document.querySelectorAll('input[data-attendance]');
        const chosen = [...all].filter(x => x.checked);
        document.getElementById('countPresent').textContent = chosen.filter(x => x.value === 'Present').length;
        document.getElementById('countAbsent').textContent = chosen.filter(x => x.value === 'Absent').length;
        document.getElementById('countUnmarked').textContent = <?= count($students) ?> - chosen.length;
    }
    updateCounts();
    </script>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>