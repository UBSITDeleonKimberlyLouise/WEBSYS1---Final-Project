<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'includes/functions.php';

requireRole(['Teacher Adviser']);

$q = $pdo->prepare('SELECT teacher_id FROM teachers WHERE user_id = ?');
$q->execute([$_SESSION['user_id']]);
$tid = (int)$q->fetchColumn();

$q = $pdo->prepare("
    SELECT cs.section_id, cs.grade_level, cs.section_name, sy.school_year_name
    FROM class_sections cs
    JOIN school_years sy ON sy.school_year_id = cs.school_year_id
    WHERE cs.adviser_teacher_id = ? AND sy.year_status = 'Current'
    ORDER BY sy.school_year_name DESC, cs.grade_level, cs.section_name
");
$q->execute([$tid]);
$sections = $q->fetchAll();

$id = (int)($_POST['section_id'] ?? ($sections[0]['section_id'] ?? 0));
$valid = false;
foreach ($sections as $s) {
    if ((int)$s['section_id'] === $id) {
        $valid = true;
    }
}
if (!$valid) {
    $id = 0;
}

$students = [];
$grades = [];

if ($id) {
    $q = $pdo->prepare("
        SELECT e.enrollment_id, e.student_lrn,
               CONCAT_WS(' ', st.first_name, st.middle_name, st.last_name) AS student_name
        FROM enrollments e
        JOIN students st ON st.student_lrn = e.student_lrn
        WHERE e.section_id = ? AND e.enrollment_status = 'Officially Enrolled'
        ORDER BY st.last_name, st.first_name
    ");
    $q->execute([$id]);
    $students = $q->fetchAll();

    $eid = (int)($_POST['student'] ?? 0);
    $selected = null;
    foreach ($students as $st) {
        if ((int)$st['enrollment_id'] === $eid) {
            $selected = $st;
        }
    }

    if ($selected) {
        $q = $pdo->prepare("
            SELECT s.subject_name, ss.section_subject_id,
                   CONCAT_WS(' ', t.first_name, t.last_name) AS teacher_name,
                   CASE g.grading_period
                       WHEN 'Prelim' THEN 'Term 1'
                       WHEN 'Midterm' THEN 'Term 2'
                       WHEN 'Finals' THEN 'Term 3'
                       ELSE g.grading_period
                   END AS grading_period,
                   g.grade_value, g.result_status
            FROM section_subjects ss
            JOIN subjects s ON s.subject_id = ss.subject_id
            LEFT JOIN teachers t ON t.teacher_id = ss.teacher_id
            LEFT JOIN grades g ON g.section_subject_id = ss.section_subject_id AND g.enrollment_id = ?
            WHERE ss.section_id = ? 
            ORDER BY s.subject_name,
                     FIELD(g.grading_period, 'Prelim', 'Term 1', 'Midterm', 'Term 2', 'Finals', 'Term 3')
        ");
        $q->execute([$eid, $id]);

        foreach ($q->fetchAll() as $r) {
            $key = $r['section_subject_id'];
            $grades[$key]['subject_name'] = $r['subject_name'];
            $grades[$key]['teacher_name'] = $r['teacher_name'] ?: 'Not assigned';
            $grades[$key][$r['grading_period'] ?? 'none'] = $r;
        }
    }
}

require 'includes/header.php';
?>

<div class="page-heading">
    <div>
        <h1>My Advisory Class</h1>
        <p>Click a student to view all subjects and grades together.</p>
    </div>
    <button type="button" class="btn btn-outline-primary print-action" onclick="window.print()" aria-label="Print this page" title="Print this page">🖨️ Print</button>
</div>

<div class="card p-3 mb-3">
    <form method="post">
        <label class="form-label">School Year / Grade / Section</label>
        <select class="form-select" name="section_id" onchange="this.form.submit()">
            <option value="">Choose section</option>
            <?php foreach ($sections as $s): ?>
                <option value="<?= $s['section_id'] ?>" <?= $id === (int)$s['section_id'] ? 'selected' : '' ?>>
                    <?= e($s['school_year_name'] . ' / ' . $s['grade_level'] . ' / ' . $s['section_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if (!$sections): ?>
    <div class="alert alert-info">No advisory section is assigned to your account for the current school year.</div>
<?php endif; ?>
<?php if ($id): ?>
    <div class="card p-3 mb-3">
        <h5>Students</h5>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>LRN</th>
                        <th>Student</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $i => $st): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($st['student_lrn']) ?></td>
                            <td><?= e($st['student_name']) ?></td>
                            <td>
                                <form method="post" action="advisory_class.php#student-grades">
                                    <input type="hidden" name="section_id" value="<?= $id ?>">
                                    <input type="hidden" name="student" value="<?= (int)$st['enrollment_id'] ?>">
                                    <button class="btn btn-sm btn-outline-primary">Grades</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (isset($selected) && $selected): ?>
        <div class="card p-3" id="student-grades">
            <h5><?= e($selected['student_name']) ?> — All Subjects</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Subject</th>
                            <th>Assigned Teacher</th>
                            <th>Term 1</th>
                            <th>Term 2</th>
                            <th>Term 3</th>
                            <th>Academic Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 0;
                        if (!$grades) echo '<tr><td colspan="7">No subjects have been configured for this section. Open Class Setup to add subjects.</td></tr>';
                        foreach ($grades as $subjectId => $g):
                            $i++;
                            $final = $g['Term 3'] ?? [];
                            $status = $final['result_status'] ?? null;
                            if (isset($final['grade_value']) && $final['grade_value'] !== null) {
                                $status = $final['grade_value'] >= 75 ? 'Passed' : 'Failed';
                            }
                        ?>
                            <tr>
                                <td><?= $i ?></td>
                                <td><?= e($g['subject_name']) ?></td>
                                <td><?= e($g['teacher_name']) ?></td>
                                <?php foreach (['Term 1', 'Term 2', 'Term 3'] as $period):
                                    $v = $g[$period] ?? [];
                                ?>
                                    <td><?= e($v['grade_value'] ?? ($v['result_status'] ?? '—')) ?></td>
                                <?php endforeach; ?>
                                <td><?= e($status ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="small text-muted">Grades are read-only here. Grades are encoded by the teacher assigned to each subject. Locked-grade corrections require Registrar authorization.</p>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>