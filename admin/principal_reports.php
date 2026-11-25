<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'includes/functions.php';

requireLogin();
requireRole(['System Administrator', 'School Registrar']);

$form = strtoupper(trim((string)($_GET['form'] ?? 'SF1')));
if (!in_array($form, ['SF1', 'SF2', 'SF5'], true)) {
    $form = 'SF1';
}

$years = $pdo->query('SELECT school_year_id, school_year_name, year_status FROM school_years ORDER BY school_year_id DESC')
    ->fetchAll(PDO::FETCH_ASSOC);

$yearId = (int)($_GET['year_id'] ?? 0);
$validYears = array_map('intval', array_column($years, 'school_year_id'));

if (!in_array($yearId, $validYears, true)) {
    $yearId = 0;
    foreach ($years as $year) {
        if ($year['year_status'] === 'Current') {
            $yearId = (int)$year['school_year_id'];
            break;
        }
    }
    if (!$yearId && $years) {
        $yearId = (int)$years[0]['school_year_id'];
    }
}

$grade = trim((string)($_GET['grade'] ?? ''));
$validGrades = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
if (!in_array($grade, $validGrades, true)) {
    $grade = '';
}

$month = trim((string)($_GET['month'] ?? date('Y-m')));
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
    $month = date('Y-m');
}

$start = $month . '-01';
$end = date('Y-m-d', strtotime($start . ' +1 month'));

$condition = ' WHERE e.school_year_id = ?';
$params = [$yearId];

if ($grade !== '') {
    $condition .= ' AND e.grade_level = ?';
    $params[] = $grade;
}

$rows = [];

if ($form === 'SF1') {
    $sql = "
        SELECT e.enrollment_id, s.student_lrn, s.last_name, s.first_name, s.middle_name,
               s.sex, s.birth_date, e.grade_level, COALESCE(cs.section_name, 'Unassigned') AS section_name,
               e.enrollment_category, e.enrollment_date, e.enrollment_status
        FROM enrollments e
        JOIN students s ON s.student_lrn = e.student_lrn
        LEFT JOIN class_sections cs ON cs.section_id = e.section_id
        {$condition}
          AND e.enrollment_status = 'Officially Enrolled'
        ORDER BY e.grade_level, cs.section_name, s.last_name, s.first_name
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($form === 'SF2') {
    // Source attendance is per subject, so retain subject detail and never label the rows as unique daily attendance.
    $sql = "
        SELECT a.attendance_date, s.student_lrn, CONCAT(s.last_name, ', ', s.first_name) AS learner,
               e.grade_level, COALESCE(cs.section_name, 'Unassigned') AS section_name,
               sub.subject_name, a.attendance_status, a.remarks
        FROM attendance a
        JOIN enrollments e ON e.enrollment_id = a.enrollment_id
        JOIN students s ON s.student_lrn = e.student_lrn
        LEFT JOIN class_sections cs ON cs.section_id = e.section_id
        JOIN section_subjects ss ON ss.section_subject_id = a.section_subject_id
        JOIN subjects sub ON sub.subject_id = ss.subject_id
        {$condition}
          AND a.attendance_date >= ?
          AND a.attendance_date < ?
        ORDER BY a.attendance_date, e.grade_level, cs.section_name, s.last_name, sub.subject_name
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params, [$start, $end]));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $sql = "
        SELECT s.student_lrn, CONCAT(s.last_name, ', ', s.first_name) AS learner,
               e.grade_level, COALESCE(cs.section_name, 'Unassigned') AS section_name,
               e.enrollment_status, e.final_result
        FROM enrollments e
        JOIN students s ON s.student_lrn = e.student_lrn
        LEFT JOIN class_sections cs ON cs.section_id = e.section_id
        {$condition}
          AND e.enrollment_status IN ('Officially Enrolled', 'Completed')
        ORDER BY e.grade_level, cs.section_name, s.last_name, s.first_name
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$titles = [
    'SF1' => 'SF1 — School Register',
    'SF2' => 'SF2 — Daily Attendance Report of Learners',
    'SF5' => 'SF5 — Report on Promotion',
];

$selectedYearName = (string)(current(array_filter(
    $years,
    fn($y) => (int)$y['school_year_id'] === $yearId
))['school_year_name'] ?? 'Not set');

$headersByForm = [
    'SF1' => ['LRN', 'Last Name', 'First Name', 'Middle Name', 'Sex', 'Birth Date', 'Grade', 'Section', 'Category', 'Enrollment Date'],
    'SF2' => ['Date', 'LRN', 'Learner', 'Grade', 'Section', 'Subject', 'Status', 'Remarks'],
    'SF5' => ['LRN', 'Learner', 'Grade', 'Section', 'Enrollment Status', 'Final Result'],
];

$keysByForm = [
    'SF1' => ['student_lrn', 'last_name', 'first_name', 'middle_name', 'sex', 'birth_date', 'grade_level', 'section_name', 'enrollment_category', 'enrollment_date'],
    'SF2' => ['attendance_date', 'student_lrn', 'learner', 'grade_level', 'section_name', 'subject_name', 'attendance_status', 'remarks'],
    'SF5' => ['student_lrn', 'learner', 'grade_level', 'section_name', 'enrollment_status', 'final_result'],
];

require 'includes/header.php';
?>

<style>
@media print {
    .sidebar,
    .topbar,
    .page-heading .btn,
    .report-filters,
    .report-nav,
    .print-control {
        display: none !important;
    }

    .main-content {
        padding: 0 !important;
    }

    .card {
        box-shadow: none !important;
        border: 0 !important;
    }

    body {
        font-size: 11px;
    }
}
</style>

<div class="page-heading">
    <div>
        <h1><?= e($titles[$form]) ?></h1>
        <p>School-level report preview for Admin and Registrar</p>
    </div>
    <button class="btn btn-outline-primary print-control" onclick="window.print()">Print</button>
</div>

<div class="card p-3 mb-3 report-nav">
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="../shared/modules.php?module=reports">All Reports</a>
        <?php foreach ($titles as $key => $label): ?>
            <a class="btn <?= $form === $key ? 'btn-primary' : 'btn-outline-primary' ?>" href="?form=<?= e($key) ?>&year_id=<?= $yearId ?>">
                <?= e($key) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<form class="card p-3 mb-3 report-filters" method="get">
    <input type="hidden" name="form" value="<?= e($form) ?>">
    <div class="row g-2">
        <div class="col-md-4">
            <label class="form-label">School Year</label>
            <select name="year_id" class="form-select">
                <?php foreach ($years as $y): ?>
                    <option value="<?= (int)$y['school_year_id'] ?>" <?= $yearId === (int)$y['school_year_id'] ? 'selected' : '' ?>>
                        <?= e($y['school_year_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">Grade Level</label>
            <select name="grade" class="form-select">
                <option value="">All grades</option>
                <?php foreach ($validGrades as $g): ?>
                    <option value="<?= e($g) ?>" <?= $grade === $g ? 'selected' : '' ?>>
                        <?= e($g) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($form === 'SF2'): ?>
            <div class="col-md-3">
                <label class="form-label">Month</label>
                <input class="form-control" type="month" name="month" value="<?= e($month) ?>">
            </div>
        <?php endif; ?>

        <div class="col-md-1 d-flex align-items-end">
            <button class="btn btn-primary">View</button>
        </div>
    </div>
</form>

<div class="card p-3">
    <h5><?= e($titles[$form]) ?></h5>
    <p class="text-muted">
        School Year: <?= e($selectedYearName) ?>
        <?= $form === 'SF2' ? ' · Month: ' . e($month) : '' ?>
        · <?= count($rows) ?> record(s)
    </p>

    <?php if ($form === 'SF2'): ?>
        <div class="alert alert-warning">
            This is a <strong>subject-attendance detail preview</strong>, not a certified SF2. One learner may have multiple entries on the same day. The database does not yet contain a verified daily consolidated attendance status or separate dropout-reason field. Do not treat counts of these rows as official daily attendance totals.
        </div>
    <?php elseif ($form === 'SF5'): ?>
        <div class="alert alert-info">
            Promotion results below use the stored final_result only. Blank means no final result has been recorded; no automatic pass/fail decision is made.
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            School Register preview: officially enrolled learners only. Validate against the school's required SF1 template before official submission.
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <?php foreach ($headersByForm[$form] as $h): ?>
                        <th><?= e($h) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <?php foreach ($keysByForm[$form] as $k): ?>
                            <td><?= e((string)($r[$k] ?? '')) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="10" class="text-muted text-center">No records found for the selected filters.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require 'includes/footer.php'; ?>