<?php
chdir(dirname(__DIR__));

session_start();

require_once 'dbconn.php';
require_once 'includes/functions.php';

requireRole(['Teacher Adviser', 'Subject Teacher']);

$q = $pdo->prepare(
    'SELECT teacher_id FROM teachers WHERE user_id = ?'
);

$q->execute([(int) $_SESSION['user_id']]);

$teacherId = (int) $q->fetchColumn();

$classes = [];
$selected = null;
$learners = [];
$attendance = [];

if ($teacherId) {
    $q = $pdo->prepare("
        SELECT
            ss.section_subject_id,
            sy.school_year_name,
            sy.school_year_id,
            cs.section_id,
            cs.grade_level,
            cs.section_name,
            s.subject_name
        FROM section_subjects ss
        JOIN class_sections cs
            ON cs.section_id = ss.section_id
        JOIN school_years sy
            ON sy.school_year_id = cs.school_year_id
        JOIN subjects s
            ON s.subject_id = ss.subject_id
        WHERE ss.teacher_id = ?
            AND sy.year_status = 'Completed'
        ORDER BY
            sy.school_year_name DESC,
            cs.grade_level,
            cs.section_name,
            s.subject_name
    ");

    $q->execute([$teacherId]);

    $classes = $q->fetchAll(PDO::FETCH_ASSOC);
}

$chosen = (int) ($_GET['class_id'] ?? 0);

foreach ($classes as $c) {
    if ((int) $c['section_subject_id'] === $chosen) {
        $selected = $c;
        break;
    }
}

if ($selected) {
    $q = $pdo->prepare("
        SELECT
            e.enrollment_id,
            st.student_lrn,
            CONCAT_WS(
                ' ',
                st.first_name,
                st.middle_name,
                st.last_name
            ) AS student_name,
            MAX(
                CASE
                    WHEN g.grading_period IN ('Term 1', 'Prelim')
                    THEN g.grade_value
                END
            ) AS term1,
            MAX(
                CASE
                    WHEN g.grading_period IN ('Term 2', 'Midterm')
                    THEN g.grade_value
                END
            ) AS term2,
            MAX(
                CASE
                    WHEN g.grading_period IN ('Term 3', 'Finals')
                    THEN g.grade_value
                END
            ) AS term3,
            MAX(
                CASE
                    WHEN g.grading_period IN ('Term 3', 'Finals')
                    THEN g.result_status
                END
            ) AS result_status
        FROM enrollments e
        JOIN students st
            ON st.student_lrn = e.student_lrn
        LEFT JOIN grades g
            ON g.enrollment_id = e.enrollment_id
            AND g.section_subject_id = ?
        WHERE e.section_id = ?
            AND e.school_year_id = ?
        GROUP BY
            e.enrollment_id,
            st.student_lrn,
            st.first_name,
            st.middle_name,
            st.last_name
        ORDER BY
            st.last_name,
            st.first_name
    ");

    $q->execute([
        $chosen,
        $selected['section_id'],
        $selected['school_year_id']
    ]);

    $learners = $q->fetchAll(PDO::FETCH_ASSOC);

    $q = $pdo->prepare("
        SELECT
            COUNT(*) AS entries,
            SUM(attendance_status = 'Present') AS present_count,
            SUM(attendance_status = 'Absent') AS absent_count,
            SUM(attendance_status = 'Late') AS late_count,
            SUM(attendance_status = 'Excused') AS excused_count
        FROM attendance
        WHERE section_subject_id = ?
    ");

    $q->execute([$chosen]);

    $attendance = $q->fetch(PDO::FETCH_ASSOC) ?: [];
}

require 'includes/header.php';
?>

<div class="page-heading">
    <div>
        <h1>Past Class Records</h1>
    </div>
</div>

<div class="card p-3 mb-3">
    <h5 class="mb-3">Previous Classes</h5>

    <?php if (!$classes): ?>
        <p class="text-muted mb-0">
            No completed school-year teaching assignments found.
        </p>
    <?php else: ?>
        <div class="row g-2">
            <?php foreach ($classes as $c): ?>
                <div class="col-md-6">
                    <a
                        class="d-block border rounded p-3 text-decoration-none <?= $chosen === (int) $c['section_subject_id'] ? 'border-primary bg-light' : '' ?>"
                        href="?class_id=<?= (int) $c['section_subject_id'] ?>"
                    >
                        <strong>
                            <?= e($c['subject_name']) ?>
                        </strong>

                        <div class="small text-muted">
                            <?= e(
                                $c['grade_level'] . ' · ' .
                                $c['section_name'] . ' · ' .
                                $c['school_year_name']
                            ) ?>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($selected): ?>
    <div class="card p-3 mb-3">
        <h5>
            <?= e(
                $selected['subject_name'] . ' — ' .
                $selected['grade_level'] . ' · ' .
                $selected['section_name']
            ) ?>
        </h5>

        <p class="small text-muted">
            Grades recorded in this subject. Blank means no saved grade;
            results are displayed as stored.
        </p>

        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>LRN</th>
                        <th>Learner</th>
                        <th>Term 1</th>
                        <th>Term 2</th>
                        <th>Term 3</th>
                        <th>Result</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($learners as $l): ?>
                        <tr>
                            <td>
                                <?= e($l['student_lrn']) ?>
                            </td>

                            <td>
                                <?= e($l['student_name']) ?>
                            </td>

                            <?php foreach (
                                ['term1', 'term2', 'term3'] as $term
                            ): ?>
                                <td>
                                    <?= $l[$term] === null
                                        ? '—'
                                        : e((string) $l[$term]) ?>
                                </td>
                            <?php endforeach; ?>

                            <td>
                                <?= e($l['result_status'] ?? '—') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$learners): ?>
                        <tr>
                            <td colspan="6" class="text-muted">
                                No learners found for this class.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card p-3">
        <h5>Subject Attendance History</h5>

        <p class="small text-muted">
            Saved subject attendance entries, not official daily attendance
            for SF2.
        </p>

        <div class="row g-2">
            <div class="col">
                Present:
                <?= (int) ($attendance['present_count'] ?? 0) ?>
            </div>

            <div class="col">
                Absent:
                <?= (int) ($attendance['absent_count'] ?? 0) ?>
            </div>

            <div class="col">
                Late:
                <?= (int) ($attendance['late_count'] ?? 0) ?>
            </div>

            <div class="col">
                Excused:
                <?= (int) ($attendance['excused_count'] ?? 0) ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>