
<?php
chdir(dirname(__DIR__));

session_start();

require_once 'dbconn.php';
require_once 'classes/ELTNHS.php';
require_once 'includes/functions.php';

requireRole(['School Registrar']);

$system = new ELTNHS($pdo);

$enrollments = $system->getAll(
    'SELECT
        e.*,
        s.first_name,
        s.last_name,
        sy.school_year_name,
        cs.section_name
    FROM enrollments e
    JOIN students s ON e.student_lrn = s.student_lrn
    JOIN school_years sy ON e.school_year_id = sy.school_year_id
    LEFT JOIN class_sections cs ON e.section_id = cs.section_id
    ORDER BY
        e.grade_level,
        cs.section_name,
        s.last_name,
        s.first_name'
);

$documentsByEnrollment = [];
$documentsByLrn = [];

try {
    $existingDocs = $system->getAll(
        "SELECT
            enrollment_document_id,
            enrollment_id,
            document_name,
            file_path
        FROM enrollment_documents
        WHERE file_path IS NOT NULL
            AND file_path <> ''
        ORDER BY document_name"
    );

    foreach ($existingDocs as $document) {
        $documentsByEnrollment[(int) $document['enrollment_id']][] = $document;
    }
} catch (PDOException $documentError) {
    error_log('Enrollment document listing: ' . $documentError->getMessage());
}

try {
    $uploadedDocs = $system->getAll(
        "SELECT
            file_id,
            student_lrn,
            document_name,
            file_path
        FROM student_files
        WHERE file_path IS NOT NULL
            AND file_path <> ''
        ORDER BY
            student_lrn,
            document_name"
    );

    foreach ($uploadedDocs as $document) {
        $documentsByLrn[$document['student_lrn']][] = $document;
    }
} catch (PDOException $documentError) {
    error_log('Student document listing: ' . $documentError->getMessage());
}

$enrollmentGroups = [];

foreach ($enrollments as $en) {
    $enrollmentGroups[$en['grade_level']][$en['section_name'] ?? 'Unassigned'][] = $en;
}

require 'includes/header.php';
?>

<div class="page-heading">
    <div>
        <h1>Student Records</h1>
        <p>Enrollment records by grade and section</p>
    </div>
</div>

<div class="card p-3">
    <h5>Enrollment Records</h5>

    <?php if (!$enrollmentGroups): ?>
        <p class="text-muted mb-0">No enrollment records yet.</p>
    <?php else: ?>
        <?php foreach ($enrollmentGroups as $grade => $sectionGroups): ?>
            <details class="border rounded p-3 mt-3">
                <summary class="fw-bold">
                    <?= e($grade) ?>
                    (<?= array_sum(array_map('count', $sectionGroups)) ?> learners)
                </summary>

                <?php foreach ($sectionGroups as $sectionName => $learners): ?>
                    <details class="card border p-3 mb-3 mt-2">
                        <summary class="fw-semibold">
                            Section: <?= e($sectionName) ?>
                            (<?= count($learners) ?> learners)
                        </summary>

                        <div class="table-responsive mt-2">
                            <table class="table table-sm align-middle">
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
                                        <?php
                                        $fileDocs = $documentsByLrn[$en['student_lrn']] ?? [];
                                        $enrollmentDocs = $documentsByEnrollment[(int) $en['enrollment_id']] ?? [];
                                        $totalDocs = count($fileDocs) + count($enrollmentDocs);
                                        ?>

                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td><?= e($en['student_lrn']) ?></td>
                                            <td><?= e($en['last_name'] . ', ' . $en['first_name']) ?></td>
                                            <td><?= e($en['school_year_name']) ?></td>
                                            <td><?= e($en['enrollment_status']) ?></td>

                                            <td>
                                                <?php if ($totalDocs): ?>
                                                    <details>
                                                        <summary class="btn btn-sm btn-outline-primary">
                                                            View Documents (<?= $totalDocs ?>)
                                                        </summary>

                                                        <div
                                                            class="border rounded p-2 mt-2 bg-light"
                                                            style="min-width:230px;"
                                                        >
                                                            <?php foreach ($fileDocs as $doc): ?>
                                                                <div class="py-2 border-bottom">
                                                                    <a
                                                                        href="../students/student_file.php?id=<?= (int) $doc['file_id'] ?>"
                                                                        target="_blank"
                                                                        rel="noopener noreferrer"
                                                                        class="text-decoration-underline fw-semibold"
                                                                    >
                                                                        <?= e($doc['document_name']) ?>
                                                                    </a>
                                                                </div>
                                                            <?php endforeach; ?>

                                                            <?php foreach ($enrollmentDocs as $doc): ?>
                                                                <div class="py-2 border-bottom">
                                                                    <a
                                                                        href="../registrar/enrollment_file.php?id=<?= (int) $doc['enrollment_document_id'] ?>"
                                                                        target="_blank"
                                                                        rel="noopener noreferrer"
                                                                        class="text-decoration-underline fw-semibold"
                                                                    >
                                                                        <?= e($doc['document_name']) ?>
                                                                    </a>
                                                                </div>
                                                            <?php endforeach; ?>
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
                    </details>
                <?php endforeach; ?>
            </details>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>
