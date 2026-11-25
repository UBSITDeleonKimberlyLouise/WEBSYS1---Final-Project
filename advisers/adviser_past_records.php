<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'includes/functions.php';

requireRole(['Teacher Adviser']);

$teacherQuery = $pdo->prepare('SELECT teacher_id FROM teachers WHERE user_id = ?');
$teacherQuery->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$teacherQuery->fetchColumn();

$tab = ($_GET['tab'] ?? 'advisory') === 'teaching' ? 'teaching' : 'advisory';
$sectionId = (int)($_GET['section_id'] ?? 0);
$assignmentId = (int)($_GET['assignment_id'] ?? 0);
$studentId = (int)($_GET['student'] ?? 0);

$error = '';
$advisory = [];
$teaching = [];
$students = [];
$subjectGrades = [];
$selectedStudent = null;
$selectedSection = null;
$selectedAssignment = null;

try {
    if ($teacherId) {
        $q = $pdo->prepare("
            SELECT cs.section_id, cs.grade_level, cs.section_name, sy.school_year_name
            FROM class_sections cs
            JOIN school_years sy ON sy.school_year_id = cs.school_year_id
            WHERE cs.adviser_teacher_id = ? AND sy.year_status = 'Completed'
            ORDER BY sy.school_year_name DESC, cs.grade_level, cs.section_name
        ");
        $q->execute([$teacherId]);
        $advisory = $q->fetchAll(PDO::FETCH_ASSOC);

        $q = $pdo->prepare("
            SELECT ss.section_subject_id, cs.section_id, cs.grade_level, cs.section_name,
                   sy.school_year_name, s.subject_name
            FROM section_subjects ss
            JOIN class_sections cs ON cs.section_id = ss.section_id
            JOIN school_years sy ON sy.school_year_id = cs.school_year_id
            JOIN subjects s ON s.subject_id = ss.subject_id
            WHERE ss.teacher_id = ? AND sy.year_status = 'Completed'
            ORDER BY sy.school_year_name DESC, cs.grade_level, cs.section_name, s.subject_name
        ");
        $q->execute([$teacherId]);
        $teaching = $q->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($tab === 'advisory') {
        foreach ($advisory as $row) {
            if ((int)$row['section_id'] === $sectionId) {
                $selectedSection = $row;
                break;
            }
        }

        if ($selectedSection) {
            $q = $pdo->prepare("
                SELECT e.enrollment_id, e.student_lrn,
                       CONCAT_WS(' ', st.first_name, st.middle_name, st.last_name) AS student_name,
                       e.final_result
                FROM enrollments e
                JOIN students st ON st.student_lrn = e.student_lrn
                JOIN class_sections cs ON cs.section_id = e.section_id AND cs.school_year_id = e.school_year_id
                WHERE cs.section_id = ?
                ORDER BY st.last_name, st.first_name, st.student_lrn
            ");
            $q->execute([$sectionId]);
            $students = $q->fetchAll(PDO::FETCH_ASSOC);

            foreach ($students as $row) {
                if ((int)$row['enrollment_id'] === $studentId) {
                    $selectedStudent = $row;
                    break;
                }
            }

            if ($selectedStudent) {
                $q = $pdo->prepare("
                    SELECT ss.section_subject_id, s.subject_name,
                           COALESCE(NULLIF(CONCAT_WS(' ', t.first_name, t.last_name), ''), 'Not assigned') AS teacher_name,
                           g.grading_period, g.grade_value, g.result_status
                    FROM section_subjects ss
                    JOIN subjects s ON s.subject_id = ss.subject_id
                    LEFT JOIN teachers t ON t.teacher_id = ss.teacher_id
                    LEFT JOIN grades g ON g.section_subject_id = ss.section_subject_id AND g.enrollment_id = ?
                    WHERE ss.section_id = ?
                    ORDER BY s.subject_name, FIELD(g.grading_period, 'Prelim', 'Term 1', 'Midterm', 'Term 2', 'Finals', 'Term 3')
                ");
                $q->execute([$studentId, $sectionId]);

                foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $key = (int)$row['section_subject_id'];
                    if (!isset($subjectGrades[$key])) {
                        $subjectGrades[$key] = [
                            'subject' => $row['subject_name'],
                            'teacher' => $row['teacher_name'],
                            'terms' => [],
                        ];
                    }
                    if ($row['grading_period'] !== null) {
                        $subjectGrades[$key]['terms'][$row['grading_period']] = $row;
                    }
                }
            }
        }
    } else {
        foreach ($teaching as $row) {
            if ((int)$row['section_subject_id'] === $assignmentId) {
                $selectedAssignment = $row;
                break;
            }
        }

        if ($selectedAssignment) {
            $q = $pdo->prepare("
                SELECT e.enrollment_id, e.student_lrn,
                       CONCAT_WS(' ', st.first_name, st.middle_name, st.last_name) AS student_name,
                       g.grading_period, g.grade_value, g.result_status
                FROM section_subjects ss
                JOIN class_sections cs ON cs.section_id = ss.section_id
                JOIN enrollments e ON e.section_id = cs.section_id AND e.school_year_id = cs.school_year_id
                JOIN students st ON st.student_lrn = e.student_lrn
                LEFT JOIN grades g ON g.enrollment_id = e.enrollment_id AND g.section_subject_id = ss.section_subject_id
                WHERE ss.section_subject_id = ? AND ss.teacher_id = ?
                ORDER BY st.last_name, st.first_name, FIELD(g.grading_period, 'Prelim', 'Term 1', 'Midterm', 'Term 2', 'Finals', 'Term 3')
            ");
            $q->execute([$assignmentId, $teacherId]);

            $grouped = [];
            foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $key = (int)$row['enrollment_id'];
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'lrn' => $row['student_lrn'],
                        'name' => $row['student_name'],
                        'terms' => [],
                    ];
                }
                if ($row['grading_period'] !== null) {
                    $grouped[$key]['terms'][$row['grading_period']] = $row;
                }
            }
            $students = array_values($grouped);
        }
    }
} catch (PDOException $ex) {
    error_log('Adviser past records: ' . $ex->getMessage());
    $error = 'Unable to load past records. Please check the PHP error log.';
}

function pastGradeText(array $terms, string $period): string
{
    $legacy = ['Term 1'=>'Prelim', 'Term 2'=>'Midterm', 'Term 3'=>'Finals'];
    $g = $terms[$period] ?? $terms[$legacy[$period] ?? ''] ?? null;
    if (!$g) {
        return '—';
    }
    if ($g['grade_value'] !== null && $g['grade_value'] !== '') {
        return (string)$g['grade_value'];
    }
    return ($g['result_status'] ?? '') !== '' ? (string)$g['result_status'] : '—';
}

function pastSubjectResult(array $terms): string
{
    $final = $terms['Term 3'] ?? $terms['Finals'] ?? null;
    if (!$final) {
        return '—';
    }
    $status = trim((string)($final['result_status'] ?? ''));
    if ($status !== '') {
        return $status;
    }
    if ($final['grade_value'] !== null && $final['grade_value'] !== '') {
        return (float)$final['grade_value'] >= 75 ? 'Passed' : 'Failed';
    }
    return '—';
}

require 'includes/header.php';
?>

<div class="page-heading">
    <div>
        <h1>Past Records</h1>
        <p>Previous advisory classes and teaching subjects · Read-only</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-warning"><?= e($error) ?></div>
<?php endif; ?>

<div class="card p-3 mb-3">
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn <?= $tab === 'advisory' ? 'btn-primary' : 'btn-outline-primary' ?>" href="../advisers/adviser_past_records.php?tab=advisory">Past Advisory Classes</a>
        <a class="btn <?= $tab === 'teaching' ? 'btn-primary' : 'btn-outline-primary' ?>" href="../advisers/adviser_past_records.php?tab=teaching">Past Teaching Subjects</a>
    </div>
</div>

<?php if (!$teacherId): ?>
    <div class="alert alert-warning">No teacher record is linked to your account.</div>

<?php elseif ($tab === 'advisory'): ?>
    <div class="card p-3 mb-3">
        <h5 class="mb-3">Previous Advisory Classes</h5>
        <?php if (!$advisory): ?>
            <p class="text-muted mb-0">No completed advisory class records are linked to your account.</p>
        <?php endif; ?>
        <div class="row g-3">
            <?php foreach ($advisory as $s): ?>
                <div class="col-md-6 col-lg-4">
                    <a class="d-block border rounded p-3 text-decoration-none <?= (int)$s['section_id'] === $sectionId ? 'border-primary bg-light' : '' ?>" href="?tab=advisory&amp;section_id=<?= (int)$s['section_id'] ?>">
                        <strong><?= e($s['grade_level'] . ' · ' . $s['section_name']) ?></strong>
                        <div class="small text-muted"><?= e($s['school_year_name']) ?></div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($selectedSection): ?>
        <div class="card p-3 mb-3">
            <h5>Students — <?= e($selectedSection['grade_level'] . ' · ' . $selectedSection['section_name']) ?></h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>LRN</th>
                            <th>Student</th>
                            <th>Final Result</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $i => $st): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= e($st['student_lrn']) ?></td>
                                <td><?= e($st['student_name']) ?></td>
                                <td><?= e($st['final_result'] ?: '—') ?></td>
                                <td><a class="btn btn-sm btn-outline-primary" href="?tab=advisory&amp;section_id=<?= $sectionId ?>&amp;student=<?= (int)$st['enrollment_id'] ?>">View Grades</a></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$students): ?>
                            <tr>
                                <td colspan="5" class="text-muted">No saved students for this section.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($selectedStudent): ?>
            <div class="card p-3">
                <h5><?= e($selectedStudent['student_name']) ?> — All Subjects</h5>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Assigned Teacher</th>
                                <th>Term 1</th>
                                <th>Term 2</th>
                                <th>Term 3</th>
                                <th>Subject Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subjectGrades as $g): ?>
                                <tr>
                                    <td><?= e($g['subject']) ?></td>
                                    <td><?= e($g['teacher']) ?></td>
                                    <td><?= e(pastGradeText($g['terms'], 'Term 1')) ?></td>
                                    <td><?= e(pastGradeText($g['terms'], 'Term 2')) ?></td>
                                    <td><?= e(pastGradeText($g['terms'], 'Term 3')) ?></td>
                                    <td><?= e(pastSubjectResult($g['terms'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$subjectGrades): ?>
                                <tr>
                                    <td colspan="6" class="text-muted">No subject records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted mb-0">Subject results are separate from the learner's final promotion result. Historical records are read-only.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

<?php else: ?>
    <div class="card p-3 mb-3">
        <h5 class="mb-3">Previous Teaching Subjects</h5>
        <?php if (!$teaching): ?>
            <p class="text-muted mb-0">No completed teaching subject records are linked to your account.</p>
        <?php endif; ?>
        <div class="row g-3">
            <?php foreach ($teaching as $a): ?>
                <div class="col-md-6 col-lg-4">
                    <a class="d-block border rounded p-3 text-decoration-none <?= (int)$a['section_subject_id'] === $assignmentId ? 'border-primary bg-light' : '' ?>" href="?tab=teaching&amp;assignment_id=<?= (int)$a['section_subject_id'] ?>">
                        <strong><?= e($a['subject_name']) ?></strong>
                        <div class="small text-muted"><?= e($a['grade_level'] . ' · ' . $a['section_name'] . ' · ' . $a['school_year_name']) ?></div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($selectedAssignment): ?>
        <div class="card p-3">
            <h5><?= e($selectedAssignment['subject_name']) ?> — Student Grades</h5>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>LRN</th>
                            <th>Student</th>
                            <th>Term 1</th>
                            <th>Term 2</th>
                            <th>Term 3</th>
                            <th>Subject Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $st): ?>
                            <tr>
                                <td><?= e($st['lrn']) ?></td>
                                <td><?= e($st['name']) ?></td>
                                <td><?= e(pastGradeText($st['terms'], 'Term 1')) ?></td>
                                <td><?= e(pastGradeText($st['terms'], 'Term 2')) ?></td>
                                <td><?= e(pastGradeText($st['terms'], 'Term 3')) ?></td>
                                <td><?= e(pastSubjectResult($st['terms'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$students): ?>
                            <tr>
                                <td colspan="6" class="text-muted">No student records found for this subject.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <p class="small text-muted mb-0">Read-only records for completed school years.</p>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>