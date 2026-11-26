<?php

chdir(dirname(__DIR__));

session_start();

require_once 'dbconn.php';
require_once 'includes/functions.php';

requireLogin();
requireRole(['System Administrator', 'School Registrar']);

$search = trim((string)($_GET['q'] ?? ''));

$currentYear = $pdo->query(
    "SELECT school_year_id, school_year_name
     FROM school_years
     WHERE year_status = 'Current'
     ORDER BY school_year_id DESC
     LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

$yearId = (int)($currentYear['school_year_id'] ?? 0);

$students = [];
$maxResults = 30;

if ($search !== '') {
    $stmt = $pdo->prepare(
        "SELECT *
         FROM students
         WHERE student_lrn = ?
            OR student_lrn LIKE ?
            OR first_name LIKE ?
            OR last_name LIKE ?
            OR CONCAT(last_name, ' ', first_name) LIKE ?
            OR CONCAT(first_name, ' ', last_name) LIKE ?
         ORDER BY
            CASE WHEN student_lrn = ? THEN 0 ELSE 1 END,
            last_name,
            first_name
         LIMIT $maxResults"
    );

    $term = '%' . $search . '%';

    $stmt->execute([
        $search,
        $term,
        $term,
        $term,
        $term,
        $term,
        $search
    ]);

    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$enrollmentStmt = $pdo->prepare(
    'SELECT
        e.grade_level,
        e.enrollment_status,
        cs.section_name,
        sy.school_year_name
     FROM enrollments e
     JOIN school_years sy
        ON sy.school_year_id = e.school_year_id
     LEFT JOIN class_sections cs
        ON cs.section_id = e.section_id
     WHERE e.student_lrn = ?
       AND e.school_year_id = ?
     ORDER BY e.enrollment_id DESC'
);

$studentFilesStmt = $pdo->prepare(
    "SELECT
        file_id,
        document_name,
        verification_status
     FROM student_files
     WHERE student_lrn = ?
       AND file_path IS NOT NULL
       AND file_path <> ''
     ORDER BY uploaded_at DESC"
);

$enrollmentFilesStmt = $pdo->prepare(
    "SELECT
        ed.enrollment_document_id,
        ed.document_name,
        ed.verification_status,
        sy.school_year_name
     FROM enrollment_documents ed
     JOIN enrollments e
        ON e.enrollment_id = ed.enrollment_id
     JOIN school_years sy
        ON sy.school_year_id = e.school_year_id
     WHERE e.student_lrn = ?
       AND ed.file_path IS NOT NULL
       AND ed.file_path <> ''
     ORDER BY
        sy.school_year_id DESC,
        ed.enrollment_document_id DESC"
);

function masterLabel($key)
{
    return ucwords(str_replace('_', ' ', $key));
}

require 'includes/header.php';

?>

<div class="page-heading">
    <div>
        <h1>Student Masterlist</h1>
    </div>
</div>

<div class="card p-3 mb-3">
    <form
        method="get"
        action="../registrar/student_masterlist.php"
        class="d-flex gap-2"
    >
        <input
            class="form-control"
            name="q"
            value="<?= e($search) ?>"
            placeholder="Enter student LRN or name"
            aria-label="Search student by LRN or name"
            required
            autofocus
        >

        <button class="btn btn-primary" type="submit">
            Search
        </button>
    </form>

    <p class="small text-muted mt-2 mb-0">
        Press Enter to show the student details and documents below.
        No View button needed.
    </p>
</div>

<?php if ($search === ''): ?>

    <div class="card p-3 text-muted">
        Enter a student name or LRN and press Enter to search.
    </div>

<?php elseif (!$students): ?>

    <div class="alert alert-info">
        No matching students found.
    </div>

<?php else: ?>

    <p class="small text-muted">
        Showing <?= count($students) ?>
        matching student<?= count($students) === 1 ? '' : 's' ?>

        <?= count($students) === $maxResults
            ? ' (maximum ' . $maxResults . ' results; refine your search if needed)'
            : '' ?>.
    </p>

    <?php foreach ($students as $student): ?>

        <?php
        $lrn = (string)$student['student_lrn'];

        $enrollmentStmt->execute([$lrn, $yearId]);
        $currentEnrollments = $enrollmentStmt->fetchAll(PDO::FETCH_ASSOC);

        $studentFilesStmt->execute([$lrn]);
        $studentFiles = $studentFilesStmt->fetchAll(PDO::FETCH_ASSOC);

        $enrollmentFilesStmt->execute([$lrn]);
        $enrollmentFiles = $enrollmentFilesStmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <div class="card p-3 mb-3">

            <h5 class="mb-1">
                <?= e(
                    $student['last_name'] . ', ' .
                    $student['first_name'] . ' ' .
                    ($student['middle_name'] ?? '')
                ) ?>
            </h5>

            <p class="text-muted mb-3">
                LRN: <?= e($lrn) ?>
            </p>

            <h6>Current Enrollment</h6>

            <?php if ($currentEnrollments): ?>

                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>School Year</th>
                                <th>Grade</th>
                                <th>Section</th>
                                <th>Enrollment Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($currentEnrollments as $en): ?>
                                <tr>
                                    <td>
                                        <?= e($en['school_year_name']) ?>
                                    </td>

                                    <td>
                                        <?= e($en['grade_level']) ?>
                                    </td>

                                    <td>
                                        <?= e($en['section_name'] ?? 'Unassigned') ?>
                                    </td>

                                    <td>
                                        <?= e($en['enrollment_status']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>

                <p class="text-muted">
                    Not yet enrolled in the current school year
                    <?= $currentYear
                        ? ' (' . e($currentYear['school_year_name']) . ')'
                        : '' ?>.
                </p>

            <?php endif; ?>

            <hr>

            <h6>Student and Guardian Information</h6>

            <div class="row g-3">

                <?php foreach ($student as $key => $value): ?>

                    <?php
                    if (
                        in_array($key, ['user_id', 'created_at', 'updated_at'], true) ||
                        $value === null ||
                        $value === ''
                    ) {
                        continue;
                    }
                    ?>

                    <div class="col-md-4">
                        <div class="small text-muted">
                            <?= e(masterLabel($key)) ?>
                        </div>

                        <div>
                            <?= e((string)$value) ?>
                        </div>
                    </div>

                <?php endforeach; ?>

            </div>

            <hr>

            <h6>Uploaded Documents</h6>

            <?php if (!$studentFiles && !$enrollmentFiles): ?>

                <p class="text-muted mb-0">
                    No uploaded documents found.
                </p>

            <?php else: ?>

                <div class="list-group list-group-flush">

                    <?php foreach ($studentFiles as $f): ?>

                        <div class="list-group-item d-flex justify-content-between align-items-center gap-2">

                            <div>
                                <strong>
                                    <?= e($f['document_name']) ?>
                                </strong>

                                <div class="small text-muted">
                                    <?= e($f['verification_status']) ?>
                                </div>
                            </div>

                            <a
                                class="btn btn-sm btn-outline-primary"
                                href="../students/student_file.php?id=<?= (int)$f['file_id'] ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                View Document
                            </a>

                        </div>

                    <?php endforeach; ?>

                    <?php foreach ($enrollmentFiles as $f): ?>

                        <div class="list-group-item d-flex justify-content-between align-items-center gap-2">

                            <div>
                                <strong>
                                    <?= e($f['document_name']) ?>
                                </strong>

                                <div class="small text-muted">
                                    <?= e(
                                        $f['school_year_name'] .
                                        ' · ' .
                                        $f['verification_status']
                                    ) ?>
                                </div>
                            </div>

                            <a
                                class="btn btn-sm btn-outline-primary"
                                href="../registrar/enrollment_file.php?id=<?= (int)$f['enrollment_document_id'] ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                View Document
                            </a>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

<?php require 'includes/footer.php'; ?>
