
<?php
chdir(dirname(__DIR__));

session_start();

require_once 'dbconn.php';
require_once 'includes/functions.php';

requireRole(['Student']);

$q = $pdo->prepare('SELECT student_lrn FROM students WHERE user_id = ?');
$q->execute([$_SESSION['user_id']]);
$lrn = $q->fetchColumn();

if (!$lrn) {
    http_response_code(404);
    exit('Student profile not found.');
}

$q = $pdo->prepare("
    SELECT
        e.enrollment_id,
        e.section_id,
        e.grade_level,
        sy.school_year_name,
        cs.section_name
    FROM enrollments e
    JOIN school_years sy
        ON sy.school_year_id = e.school_year_id
    LEFT JOIN class_sections cs
        ON cs.section_id = e.section_id
    WHERE e.student_lrn = ?
        AND sy.year_status = 'Completed'
    ORDER BY
        sy.school_year_id DESC,
        e.enrollment_id DESC
");

$q->execute([$lrn]);
$history = $q->fetchAll(PDO::FETCH_ASSOC);

$allowed = [];

foreach ($history as $record) {
    $allowed[(int) $record['enrollment_id']] = $record;
}

$selectedId = filter_input(INPUT_GET, 'record_id', FILTER_VALIDATE_INT) ?: 0;
$selected = $allowed[$selectedId] ?? null;
$subjects = [];

if ($selected && !empty($selected['section_id'])) {
    $q = $pdo->prepare("
        SELECT
            ss.section_subject_id,
            s.subject_name,
            g.grading_period,
            g.grade_value,
            g.result_status
        FROM section_subjects ss
        JOIN subjects s
            ON s.subject_id = ss.subject_id
        LEFT JOIN grades g
            ON g.section_subject_id = ss.section_subject_id
            AND g.enrollment_id = ?
        WHERE ss.section_id = ?
        ORDER BY
            s.subject_name,
            ss.section_subject_id
    ");

    $q->execute([$selectedId, (int) $selected['section_id']]);

    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $id = (int) $r['section_subject_id'];

        if (!isset($subjects[$id])) {
            $subjects[$id] = [
                'name' => $r['subject_name'],
                'grades' => [],
                'results' => []
            ];
        }

        if ($r['grading_period'] !== null) {
            $subjects[$id]['grades'][$r['grading_period']] = $r['grade_value'];

            $subjects[$id]['results'][$r['grading_period']] = $r['result_status'];
        }
    }
}

require 'includes/header.php';
?>

<div class="page-heading">
    <div>
        <h1>Past Records</h1>
    </div>
</div>

<div class="card p-3 mb-3">
    <h5 class="mb-3">Previous School Years</h5>

    <?php if (!$history): ?>
        <p class="text-muted mb-0">No completed school-year records found.</p>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($history as $record):
                $id = (int) $record['enrollment_id'];
                $isSelected = $selected !== null && $selectedId === $id;

                $url = $isSelected
                    ? 'student_past_records.php'
                    : 'student_past_records.php?record_id=' . $id;
            ?>
                <div class="col-md-6">
                    <a
                        href="<?= e($url) ?>"
                        class="text-decoration-none d-block h-100"
                        aria-expanded="<?= $isSelected ? 'true' : 'false' ?>"
                    >
                        <div
                            class="border rounded p-3 h-100"
                            style="background:#f8f9fa; border-color:<?= $isSelected ? '#2458a6' : '#d7dde6' ?> !important; border-width:<?= $isSelected ? '2px' : '1px' ?> !important;"
                        >
                            <div class="fw-semibold text-primary">
                                <?= e($record['school_year_name']) ?>
                            </div>

                            <div class="text-muted small">
                                <?= e($record['grade_level']) ?>
                                ·
                                <?= e($record['section_name'] ?: 'Unassigned') ?>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($selected): ?>
    <div class="card p-3 mb-3" id="academic-records">
        <h5 class="mb-2">
            Academic Records —
            <?= e($selected['grade_level']) ?>
            ·
            <?= e($selected['section_name'] ?: 'Unassigned') ?>
        </h5>

        <p class="text-muted small">
            Grades recorded for <?= e($selected['school_year_name']) ?>.
            Blank means no saved grade; results are displayed as stored.
        </p>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Subject</th>
                        <th>Term 1</th>
                        <th>Term 2</th>
                        <th>Term 3</th>
                        <th>Result</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($subjects as $subject): ?>
                        <tr>
                            <td><?= e($subject['name']) ?></td>

                            <?php foreach (['Term 1'=>'Prelim', 'Term 2'=>'Midterm', 'Term 3'=>'Finals'] as $period => $legacy):
                                $grade = array_key_exists($period, $subject['grades']) ? $subject['grades'][$period] : ($subject['grades'][$legacy] ?? null);
                            ?>
                                <td>
                                    <?= $grade !== null && $grade !== ''
                                        ? e(number_format((float) $grade, 2))
                                        : '' ?>
                                </td>
                            <?php endforeach; ?>

                            <td><?= e(array_key_exists('Term 3', $subject['results']) ? ($subject['results']['Term 3'] ?? '') : ($subject['results']['Finals'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$subjects): ?>
                        <tr>
                            <td colspan="5" class="text-muted text-center py-3">
                                No saved subject records found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
