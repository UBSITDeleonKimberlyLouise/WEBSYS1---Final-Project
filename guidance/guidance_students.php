
<?php
chdir(dirname(__DIR__));

session_start();

require_once 'dbconn.php';
require_once 'includes/functions.php';

requireRole(['Guidance Counselor']);

$search = trim((string) ($_GET['q'] ?? ''));

$students = [];
$error = '';

if ($search !== '') {
    if (mb_strlen($search) < 2) {
        $error = 'Enter at least 2 characters.';
    } else {
        $stmt = $pdo->prepare("
            SELECT *
            FROM students
            WHERE student_lrn LIKE ?
                OR CONCAT_WS(' ', first_name, middle_name, last_name) LIKE ?
                OR CONCAT_WS(' ', last_name, first_name, middle_name) LIKE ?
            ORDER BY last_name, first_name
            LIMIT 30
        ");

        $like = '%' . $search . '%';

        $stmt->execute([$like, $like, $like]);

        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$historyStmt = $pdo->prepare("
    SELECT
        e.enrollment_status,
        e.guidance_assessment_status,
        e.guidance_assessment_remarks,
        sy.school_year_name,
        sy.year_status,
        cs.grade_level,
        cs.section_name
    FROM enrollments e
    JOIN school_years sy
        ON sy.school_year_id = e.school_year_id
    LEFT JOIN class_sections cs
        ON cs.section_id = e.section_id
    WHERE e.student_lrn = ?
    ORDER BY sy.school_year_id DESC, e.enrollment_id DESC
");

$info = [
    'student_lrn' => 'LRN',
    'last_name' => 'Last Name',
    'first_name' => 'First Name',
    'middle_name' => 'Middle Name',
    'sex' => 'Sex',
    'birth_date' => 'Birthdate',
    'student_contact_number' => 'Contact Number',
    'current_barangay' => 'Barangay',
    'current_municipality' => 'Municipality',
    'current_province' => 'Province',
    'parent_guardian_name' => 'Parent / Guardian',
    'parent_guardian_contact' => 'Guardian Contact'
];

if ($students) {
    $recent = $_SESSION['guidance_recent_views'] ?? [];

    foreach (array_reverse($students) as $row) {
        $lrn = (string) $row['student_lrn'];

        $recent = array_values(array_filter(
            $recent,
            static fn($r) => ($r['lrn'] ?? '') !== $lrn
        ));

        array_unshift($recent, [
            'lrn' => $lrn,
            'name' => trim($row['last_name'] . ', ' . $row['first_name']),
            'at' => date('Y-m-d H:i')
        ]);
    }

    $_SESSION['guidance_recent_views'] = array_slice($recent, 0, 10);
}

require 'includes/header.php';
?>

<div class="page-heading">
    <div>
        <h1>Student Masterlist</h1>
        <p>Search once to display student profiles and enrollment history below.</p>
    </div>
</div>

<div class="card p-3 mb-3">
    <form
        method="get"
        action="../guidance/guidance_students.php"
        class="row g-2"
    >
        <div class="col-md-9">
            <input
                name="q"
                class="form-control"
                value="<?= e($search) ?>"
                placeholder="Enter student LRN or name"
                maxlength="100"
                required
                autofocus
            >
        </div>

        <div class="col-md-3">
            <button class="btn btn-primary w-100" type="submit">
                Search
            </button>
        </div>
    </form>
</div>

<?php if ($error): ?>
    <div class="alert alert-warning">
        <?= e($error) ?>
    </div>
<?php elseif ($search !== '' && !$students): ?>
    <div class="alert alert-info">
        No matching students found.
    </div>
<?php endif; ?>

<?php if ($students): ?>
    <p class="text-muted small">
        Showing <?= count($students) ?> matching student(s). No additional View button required.
    </p>
<?php endif; ?>

<?php foreach ($students as $student): ?>
    <div class="card p-3 mb-3">
        <h5 class="mb-1">
            <?= e($student['last_name'] . ', ' . $student['first_name'] . ' ' . ($student['middle_name'] ?? '')) ?>
        </h5>

        <p class="text-muted small">
            LRN: <?= e($student['student_lrn']) ?>
        </p>

        <h6>Student Information</h6>

        <div class="row g-3 mb-3">
            <?php foreach ($info as $field => $label): ?>
                <?php if (!array_key_exists($field, $student)) continue; ?>

                <div class="col-md-4">
                    <div class="small text-muted">
                        <?= e($label) ?>
                    </div>

                    <strong>
                        <?= e((string) ($student[$field] ?? '')) ?>
                    </strong>
                </div>
            <?php endforeach; ?>
        </div>

        <h6>Enrollment and Guidance History</h6>

        <?php
        $historyStmt->execute([$student['student_lrn']]);
        $history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <?php if (!$history): ?>
            <p class="text-muted small">
                No enrollment records yet.
            </p>
        <?php endif; ?>

        <?php foreach ($history as $rec): ?>
            <div class="border rounded p-3 mb-2">
                <strong>
                    <?= e($rec['school_year_name']) ?>
                </strong>

                · <?= e(($rec['grade_level'] ?? 'Unassigned') . ' / ' . ($rec['section_name'] ?? 'Unassigned')) ?>

                <div class="small text-muted">
                    Enrollment: <?= e($rec['enrollment_status']) ?>
                    · Interview: <?= e($rec['guidance_assessment_status'] ?? 'Pending') ?>
                </div>

                <?php if (trim((string) ($rec['guidance_assessment_remarks'] ?? '')) !== ''): ?>
                    <div class="small">
                        Remarks: <?= e($rec['guidance_assessment_remarks']) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<?php require 'includes/footer.php'; ?>
