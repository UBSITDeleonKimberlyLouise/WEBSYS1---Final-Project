
<?php

chdir(dirname(__DIR__));
session_start();

require_once 'dbconn.php';
require_once 'includes/functions.php';

requireRole(['Teacher Adviser', 'Subject Teacher']);

require_once 'includes/grading_schedule.php';

$uid = (int) $_SESSION['user_id'];

$terms = ['Term 1', 'Term 2', 'Term 3'];
$legacy = ['Prelim', 'Midterm', 'Finals'];

$q = $pdo->prepare("
    SELECT
        ss.section_subject_id,
        cs.section_id,
        cs.school_year_id,
        cs.grade_level,
        cs.section_name,
        s.subject_name,
        sy.open_grading_term
    FROM section_subjects ss
    JOIN teachers t ON t.teacher_id = ss.teacher_id
    JOIN users u ON u.user_id = t.user_id
    JOIN class_sections cs ON cs.section_id = ss.section_id
    JOIN school_years sy ON sy.school_year_id = cs.school_year_id
    JOIN subjects s ON s.subject_id = ss.subject_id
    WHERE
        t.user_id = ?
        AND t.employment_status = 'Active'
        AND u.account_status = 'Active'
        AND sy.year_status = 'Current'
    ORDER BY
        cs.grade_level,
        cs.section_name,
        s.subject_name
");

$q->execute([$uid]);

$assignments = $q->fetchAll(PDO::FETCH_ASSOC);
$map = array_column($assignments, null, 'section_subject_id');

$id = (int) (
    $_POST['assignment_id']
    ?? $_GET['assignment_id']
    ?? ($assignments[0]['section_subject_id'] ?? 0)
);

$a = $map[$id] ?? null;

$error = '';
$message = '';

function loadGradeRows($pdo, $a, $id)
{
    $q = $pdo->prepare("
        SELECT
            e.enrollment_id,
            e.enrollment_status,
            st.student_lrn,
            st.first_name,
            st.last_name,
            (
                SELECT movement_type
                FROM learner_movements m
                WHERE m.enrollment_id = e.enrollment_id
                ORDER BY movement_date DESC, movement_id DESC
                LIMIT 1
            ) movement
        FROM enrollments e
        JOIN students st ON st.student_lrn = e.student_lrn
        WHERE
            e.section_id = ?
            AND e.school_year_id = ?
            AND e.enrollment_status IN (
                'Officially Enrolled',
                'Withdrawn',
                'Completed'
            )
        ORDER BY st.last_name, st.first_name
    ");

    $q->execute([
        $a['section_id'],
        $a['school_year_id']
    ]);

    $rows = $q->fetchAll(PDO::FETCH_ASSOC);

    $q = $pdo->prepare("
        SELECT *
        FROM grades
        WHERE section_subject_id = ?
        ORDER BY FIELD(
            grading_period,
            'Prelim',
            'Midterm',
            'Finals',
            'Term 1',
            'Term 2',
            'Term 3'
        )
    ");

    $q->execute([$id]);

    $g = [];

    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $p = [
            'Prelim' => 'Term 1',
            'Midterm' => 'Term 2',
            'Finals' => 'Term 3'
        ][$r['grading_period']] ?? $r['grading_period'];

        $g[$r['enrollment_id']][$p] = $r;
    }

    return [$rows, $g];
}

function gradeNumber($v)
{
    return is_string($v)
        && preg_match('/^\d{1,3}(\.\d{1,2})?$/D', $v)
        && (float) $v <= 100;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    try {
        if (!$a) {
            throw new Exception('Select an assigned subject.');
        }

        $pdo->beginTransaction();

        $q = $pdo->prepare("
            SELECT open_grading_term
            FROM school_years
            WHERE
                school_year_id = ?
                AND year_status = 'Current'
            FOR UPDATE
        ");

        $q->execute([$a['school_year_id']]);
        $exists = $q->fetchColumn();

        if ($exists === false) {
            throw new Exception('School year is no longer current.');
        }

        $schedule = gradingSchedule($pdo, $a['school_year_id']);
        $open = gradingOpen($schedule);

        $q = $pdo->prepare("
            SELECT ss.section_subject_id
            FROM section_subjects ss
            JOIN teachers t ON t.teacher_id = ss.teacher_id
            JOIN users u ON u.user_id = t.user_id
            WHERE
                ss.section_subject_id = ?
                AND t.user_id = ?
                AND t.employment_status = 'Active'
                AND u.account_status = 'Active'
            FOR UPDATE
        ");

        $q->execute([$id, $uid]);

        if (!$q->fetchColumn()) {
            throw new Exception('Assignment changed.');
        }

        [$rows, $g] = loadGradeRows($pdo, $a, $id);
        $allowed = array_column($rows, null, 'enrollment_id');

        if (($_POST['action'] ?? '') === 'save') {
            $p = $terms[(int) $open - 1] ?? null;

            if ((int) ($_POST['term'] ?? 0) !== $open) {
                throw new Exception('Term changed. Reload before saving.');
            }

            if (!$p) {
                throw new Exception('No term is open.');
            }

            $q = $pdo->prepare("
                SELECT COUNT(*)
                FROM grade_period_locks
                WHERE
                    section_subject_id = ?
                    AND grading_period IN (?, ?)
            ");

            $q->execute([
                $id,
                $p,
                $legacy[(int) $open - 1]
            ]);

            if ($q->fetchColumn()) {
                throw new Exception('This subject term is closed.');
            }

            foreach (($_POST['grades'] ?? []) as $eid => $v) {
                if ($v === '') {
                    continue;
                }

                $r = $allowed[$eid] ?? null;
                $old = $g[$eid][$p] ?? null;

                if (
                    !$r
                    || $r['enrollment_status'] !== 'Officially Enrolled'
                    || in_array(
                        $r['movement'],
                        ['Dropped Out', 'Transferred Out'],
                        true
                    )
                ) {
                    throw new Exception('Learner is not eligible.');
                }

                if (
                    $old
                    && !in_array(
                        $old['result_status'],
                        [null, 'INC', 'Passed', 'Failed'],
                        true
                    )
                ) {
                    throw new Exception('A saved grade cannot be edited.');
                }

                if ($v !== 'INC' && !gradeNumber($v)) {
                    throw new Exception('Enter 0–100 or INC.');
                }

                $num = $v === 'INC' ? null : $v;
                $status = $v === 'INC' ? 'INC' : null;

                if ($old) {
                    $q = $pdo->prepare("
                        UPDATE grades
                        SET
                            grade_value = ?,
                            result_status = ?,
                            encoded_by = ?
                        WHERE grade_id = ?
                    ");

                    $q->execute([
                        $num,
                        $status,
                        $uid,
                        $old['grade_id']
                    ]);
                } else {
                    $q = $pdo->prepare("
                        INSERT INTO grades (
                            enrollment_id,
                            section_subject_id,
                            grading_period,
                            grade_value,
                            result_status,
                            encoded_by
                        )
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");

                    $q->execute([
                        $eid,
                        $id,
                        $p,
                        $num,
                        $status,
                        $uid
                    ]);
                }
            }

            $message = 'Grades saved.';
        } elseif (($_POST['action'] ?? '') === 'request') {
            $gid = (int) ($_POST['grade_id'] ?? 0);
            $v = $_POST['proposed_grade'] ?? '';
            $reason = trim($_POST['reason'] ?? '');
            $found = null;

            foreach ($g as $entries) {
                foreach ($entries as $r) {
                    if ((int) $r['grade_id'] === $gid) {
                        $found = $r;
                    }
                }
            }

            if (
                !$found
                || gradingState(
                    $schedule[
                        array_search(
                            [
                                'Prelim' => 'Term 1',
                                'Midterm' => 'Term 2',
                                'Finals' => 'Term 3'
                            ][$found['grading_period']] ?? $found['grading_period'],
                            $terms
                        ) + 1
                    ] ?? null
                ) !== 'Closed'
                || !isset($allowed[$found['enrollment_id']])
                || (
                    $found['grade_value'] === null
                    && $found['result_status'] !== 'INC'
                )
                || !gradeNumber($v)
                || $reason === ''
                || strlen($reason) > 500
            ) {
                throw new Exception('Invalid correction request.');
            }

            $q = $pdo->prepare("
                SELECT COUNT(*)
                FROM grade_correction_requests
                WHERE
                    grade_id = ?
                    AND request_status = 'Pending'
            ");

            $q->execute([$gid]);

            if ($q->fetchColumn()) {
                throw new Exception('A request is already pending.');
            }

            $q = $pdo->prepare("
                INSERT INTO grade_correction_requests (
                    grade_id,
                    requested_by,
                    proposed_grade,
                    reason,
                    old_grade,
                    old_status
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $q->execute([
                $gid,
                $uid,
                $v,
                $reason,
                $found['grade_value'],
                $found['result_status']
            ]);

            $message = 'Correction request submitted.';
        } else {
            throw new Exception('Invalid action.');
        }

        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = $ex instanceof PDOException
            ? 'Unable to save. Check migration and server log.'
            : $ex->getMessage();

        error_log($ex->getMessage());
    }
}

$rows = [];
$g = [];
$open = 0;
$locked = false;

if ($a) {
    [$rows, $g] = loadGradeRows($pdo, $a, $id);

    $q = $pdo->prepare("
        SELECT open_grading_term
        FROM school_years
        WHERE school_year_id = ?
    ");

    $q->execute([$a['school_year_id']]);

    $schedule = gradingSchedule($pdo, $a['school_year_id']);
    $open = gradingOpen($schedule);

    if (isset($terms[$open - 1])) {
        $q = $pdo->prepare("
            SELECT COUNT(*)
            FROM grade_period_locks
            WHERE
                section_subject_id = ?
                AND grading_period IN (?, ?)
        ");

        $q->execute([
            $id,
            $terms[$open - 1],
            $legacy[$open - 1]
        ]);

        $locked = (bool) $q->fetchColumn();
    }
}

require 'includes/header.php';
?>

<div class="page-heading">
    <div>
        <h1>Grade Encoding</h1>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <?= e($error) ?>
    </div>
<?php endif; ?>

<?php if ($message): ?>
    <div class="alert alert-success">
        <?= e($message) ?>
    </div>
<?php endif; ?>

<div class="card p-3 mb-3">
    <form method="get" class="d-flex gap-2">
        <select name="assignment_id" class="form-select">
            <?php foreach ($assignments as $x): ?>
                <option
                    value="<?= $x['section_subject_id'] ?>"
                    <?= $id == (int) $x['section_subject_id'] ? 'selected' : '' ?>
                >
                    <?= e(
                        $x['grade_level']
                        . ' / '
                        . $x['section_name']
                        . ' / '
                        . $x['subject_name']
                    ) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button class="btn btn-primary">Load</button>
    </form>
</div>

<?php if ($a): ?>
    <div class="card p-3">
        <p>
            <?= e(
                $open === 0
                    ? 'No term open'
                    : (
                        $open === 4
                            ? 'All terms closed'
                            : $terms[$open - 1]
                                . ($locked
                                    ? ' — Closed for this subject'
                                    : ' — Open')
                    )
            ) ?>
        </p>

        <form
            method="post"
            onsubmit="return confirm('Save all entered grades?')"
        >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrfToken()) ?>"
            >
            <input type="hidden" name="assignment_id" value="<?= $id ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="term" value="<?= $open ?>">

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>LRN</th>
                            <th>Student</th>

                            <?php foreach ($terms as $p): ?>
                                <th>
                                    <?= e($p) ?>
                                    <div class="small text-muted">
                                        <?= e(
                                            gradingState(
                                                $schedule[array_search($p, $terms) + 1] ?? null
                                            )
                                        ) ?>
                                    </div>
                                </th>
                            <?php endforeach; ?>

                            <th>Average</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <?php
                            $eid = $r['enrollment_id'];
                            $nums = [];
                            ?>

                            <tr>
                                <td><?= e($r['student_lrn']) ?></td>
                                <td>
                                    <?= e($r['last_name'] . ', ' . $r['first_name']) ?>
                                </td>

                                <?php foreach ($terms as $i => $p): ?>
                                    <?php
                                    $old = $g[$eid][$p] ?? null;
                                    $num = $old['grade_value'] ?? null;

                                    if ($num !== null) {
                                        $nums[] = (float) $num;
                                    }

                                    $editable =
                                        $open === $i + 1
                                        && !$locked
                                        && $r['enrollment_status'] === 'Officially Enrolled'
                                        && !in_array(
                                            $r['movement'],
                                            ['Dropped Out', 'Transferred Out'],
                                            true
                                        )
                                        && in_array(
                                            $old['result_status'] ?? null,
                                            [null, 'INC', 'Passed', 'Failed'],
                                            true
                                        );
                                    ?>

                                    <td>
                                        <?php if ($editable): ?>
                                            <input
                                                class="form-control form-control-sm"
                                                style="min-width: 85px"
                                                name="grades[<?= $eid ?>]"
                                                value="<?= e($num ?? $old['result_status'] ?? '') ?>"
                                                placeholder="Grade / INC"
                                            >
                                        <?php else: ?>
                                            <?= e(
                                                $num
                                                ?? $old['result_status']
                                                ?? (
                                                    in_array(
                                                        $r['movement'],
                                                        ['Dropped Out', 'Transferred Out'],
                                                        true
                                                    )
                                                        ? $r['movement']
                                                        : ''
                                                )
                                            ) ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>

                                <td>
                                    <?= count($nums) === 3
                                        ? number_format(array_sum($nums) / 3, 2)
                                        : '—' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($open >= 1 && $open <= 3 && !$locked): ?>
                <button class="btn btn-primary">Save All Grades</button>
            <?php endif; ?>
        </form>
    </div>

    <details class="card p-3 mt-3">
        <summary>Request Grade Correction</summary>

        <form method="post">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrfToken()) ?>"
            >
            <input type="hidden" name="assignment_id" value="<?= $id ?>">
            <input type="hidden" name="action" value="request">

            <label class="form-label">Saved grade</label>
            <select class="form-select mb-2" name="grade_id" required>
                <option value="">Select grade</option>

                <?php foreach ($rows as $r): ?>
                    <?php foreach ($g[$r['enrollment_id']] ?? [] as $p => $old): ?>
                        <?php
                        if (
                            gradingState(
                                $schedule[array_search($p, $terms) + 1] ?? null
                            ) !== 'Closed'
                            || (
                                $old['grade_value'] === null
                                && $old['result_status'] !== 'INC'
                            )
                        ) {
                            continue;
                        }
                        ?>

                        <option value="<?= $old['grade_id'] ?>">
                            <?= e(
                                $r['last_name']
                                . ', '
                                . $r['first_name']
                                . ' / '
                                . $p
                                . ' / '
                                . ($old['grade_value'] ?? 'INC')
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </select>

            <label>Corrected grade</label>
            <input
                class="form-control mb-2"
                type="number"
                min="0"
                max="100"
                step="0.01"
                name="proposed_grade"
                required
            >

            <label>Reason</label>
            <textarea
                class="form-control mb-2"
                name="reason"
                maxlength="500"
                required
            ></textarea>

            <button class="btn btn-outline-primary">Submit Request</button>
        </form>
    </details>
<?php else: ?>
    <p>No assigned subjects for the current school year.</p>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
