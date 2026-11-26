
<?php

chdir(dirname(__DIR__));

session_start();

require_once 'dbconn.php';
require_once 'includes/functions.php';

requireRole(['System Administrator', 'School Registrar']);

require_once 'includes/grading_schedule.php';

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    try {
        $pdo->beginTransaction();

        $action = $_POST['action'] ?? '';
        $uid = (int) $_SESSION['user_id'];

        if ($action === 'schedule') {
            if ($_SESSION['role'] !== 'System Administrator') {
                throw new Exception('Only Admin can change schedules.');
            }

            $q = $pdo->query("
                SELECT school_year_id
                FROM school_years
                WHERE year_status = 'Current'
                FOR UPDATE
            ");

            $year = $q->fetchColumn();

            if (!$year || $q->fetchColumn()) {
                throw new Exception('Select one current school year.');
            }

            $old = gradingSchedule($pdo, $year);
            $new = [];
            $reason = trim($_POST['reason'] ?? '');

            if (!$reason || strlen($reason) > 500) {
                throw new Exception('Enter a reason.');
            }

            for ($i = 1; $i <= 3; $i++) {
                $s = $_POST['start'][$i] ?? '';
                $e = $_POST['end'][$i] ?? '';
                $closed = isset($_POST['closed'][$i]) ? 1 : 0;

                foreach ([$s, $e] as $d) {
                    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $d);

                    if (!$dt || $dt->format('Y-m-d') !== $d) {
                        throw new Exception('Invalid date.');
                    }
                }

                if ($s > $e || ($i > 1 && $s <= $new[$i - 1]['end_date'])) {
                    throw new Exception('Terms must be sequential and cannot overlap.');
                }

                if (
                    isset($old[$i])
                    && gradingState($old[$i]) === 'Closed'
                    && isset($old[$i + 1])
                    && gradingToday() >= $old[$i + 1]['start_date']
                    && (
                        $s !== $old[$i]['start_date']
                        || $e !== $old[$i]['end_date']
                        || $closed !== (int) $old[$i]['manually_closed']
                    )
                ) {
                    throw new Exception(
                        'Previous term cannot be changed after the next term starts. Use correction requests.'
                    );
                }

                $new[$i] = [
                    'start_date' => $s,
                    'end_date' => $e,
                    'manually_closed' => $closed
                ];
            }

            $q = $pdo->prepare("
                INSERT INTO grading_schedules (
                    school_year_id,
                    term,
                    start_date,
                    end_date,
                    manually_closed
                )
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    start_date = VALUES(start_date),
                    end_date = VALUES(end_date),
                    manually_closed = VALUES(manually_closed)
            ");

            foreach ($new as $i => $r) {
                $q->execute([
                    $year,
                    $i,
                    $r['start_date'],
                    $r['end_date'],
                    $r['manually_closed']
                ]);
            }

            $pdo->prepare("
                INSERT INTO grading_schedule_history (
                    school_year_id,
                    changed_by,
                    old_schedule,
                    new_schedule,
                    reason
                )
                VALUES (?, ?, ?, ?, ?)
            ")->execute([
                $year,
                $uid,
                json_encode($old),
                json_encode($new),
                $reason
            ]);

            $message = 'Schedule saved.';
        } elseif (in_array($action, ['approve', 'reject'], true)) {
            $id = (int) ($_POST['request_id'] ?? 0);

            $q = $pdo->prepare("
                SELECT
                    r.*,
                    g.grade_value,
                    g.result_status
                FROM grade_correction_requests r
                JOIN grades g
                    ON g.grade_id = r.grade_id
                WHERE r.request_id = ?
                FOR UPDATE
            ");

            $q->execute([$id]);
            $r = $q->fetch();

            if (!$r || $r['request_status'] !== 'Pending') {
                throw new Exception('Request already reviewed or missing.');
            }

            if ($action === 'approve') {
                if (
                    $r['old_grade'] !== $r['grade_value']
                    || $r['old_status'] !== $r['result_status']
                ) {
                    throw new Exception(
                        'Grade changed since request. Reject and request again.'
                    );
                }

                $pdo->prepare("
                    UPDATE grades
                    SET
                        grade_value = ?,
                        result_status = NULL
                    WHERE grade_id = ?
                ")->execute([
                    $r['proposed_grade'],
                    $r['grade_id']
                ]);
            }

            $pdo->prepare("
                UPDATE grade_correction_requests
                SET
                    request_status = ?,
                    reviewed_by = ?,
                    reviewed_at = NOW()
                WHERE request_id = ?
            ")->execute([
                $action === 'approve' ? 'Approved' : 'Rejected',
                $uid,
                $id
            ]);

            $message = 'Request reviewed.';
        } else {
            throw new Exception('Invalid action.');
        }

        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = $ex instanceof PDOException
            ? 'Unable to save.'
            : $ex->getMessage();

        error_log($ex->getMessage());
    }
}

$y = $pdo->query("
    SELECT
        school_year_id,
        school_year_name,
        open_grading_term
    FROM school_years
    WHERE year_status = 'Current'
")->fetch();

$rows = $pdo->query("
    SELECT
        r.*,
        g.grading_period,
        s.subject_name,
        st.first_name,
        st.last_name,
        sy.school_year_name
    FROM grade_correction_requests r
    JOIN grades g
        ON g.grade_id = r.grade_id
    JOIN enrollments en
        ON en.enrollment_id = g.enrollment_id
    JOIN students st
        ON st.student_lrn = en.student_lrn
    JOIN school_years sy
        ON sy.school_year_id = en.school_year_id
    JOIN section_subjects ss
        ON ss.section_subject_id = g.section_subject_id
    JOIN subjects s
        ON s.subject_id = ss.subject_id
    ORDER BY r.requested_at DESC
")->fetchAll();

require 'includes/header.php';
?>

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

<?php if ($y): ?>
    <?php $schedule = gradingSchedule($pdo, $y['school_year_id']); ?>

    <div class="card p-3 mb-3">
        <h5>
            <?= e($y['school_year_name']) ?> — Grading Schedule
        </h5>

        <?php if ($_SESSION['role'] === 'System Administrator'): ?>
            <form method="post">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrfToken()) ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="schedule"
                >
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Term</th>
                        <th>Start</th>
                        <th>End (inclusive)</th>
                        <th>Status</th>
                        <th>Manually closed</th>
                    </tr>
                </thead>

                <tbody>
                    <?php for ($i = 1; $i <= 3; $i++): ?>
                        <?php $r = $schedule[$i] ?? null; ?>

                        <tr>
                            <td>
                                Term <?= $i ?>
                            </td>

                            <?php if ($_SESSION['role'] === 'System Administrator'): ?>
                                <td>
                                    <input
                                        type="date"
                                        class="form-control"
                                        name="start[<?= $i ?>]"
                                        value="<?= e($r['start_date'] ?? '') ?>"
                                        required
                                    >
                                </td>

                                <td>
                                    <input
                                        type="date"
                                        class="form-control"
                                        name="end[<?= $i ?>]"
                                        value="<?= e($r['end_date'] ?? '') ?>"
                                        required
                                    >
                                </td>

                                <td>
                                    <?= e(gradingState($r)) ?>
                                </td>

                                <td>
                                    <input
                                        type="checkbox"
                                        name="closed[<?= $i ?>]"
                                        <?= $r && $r['manually_closed'] ? 'checked' : '' ?>
                                    >
                                </td>
                            <?php else: ?>
                                <td>
                                    <?= e($r['start_date'] ?? '') ?>
                                </td>

                                <td>
                                    <?= e($r['end_date'] ?? '') ?>
                                </td>

                                <td>
                                    <?= e(gradingState($r)) ?>
                                </td>

                                <td>
                                    <?= $r && $r['manually_closed'] ? 'Yes' : 'No' ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>

        <?php if ($_SESSION['role'] === 'System Administrator'): ?>
            <label>
                Reason for schedule change
            </label>

            <textarea
                name="reason"
                class="form-control mb-2"
                maxlength="500"
                required
            ></textarea>

            <button class="btn btn-primary">
                Save Schedule
            </button>
            </form>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="card p-3">
    <h5>Correction Requests</h5>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Student / Subject</th>
                    <th>Term</th>
                    <th>Previous</th>
                    <th>Proposed</th>
                    <th>Reason</th>
                    <th>Status / Action</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <?= e(
                                $r['last_name']
                                . ', '
                                . $r['first_name']
                                . ' / '
                                . $r['subject_name']
                                . ' / '
                                . $r['school_year_name']
                            ) ?>
                        </td>

                        <td>
                            <?= e($r['grading_period']) ?>
                        </td>

                        <td>
                            <?= e($r['old_grade'] ?? $r['old_status'] ?? '') ?>
                        </td>

                        <td>
                            <?= e($r['proposed_grade']) ?>
                        </td>

                        <td>
                            <?= e($r['reason']) ?>
                        </td>

                        <td>
                            <?= e($r['request_status']) ?>

                            <?php if ($r['request_status'] === 'Pending'): ?>
                                <form method="post" class="d-flex gap-1">
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e(csrfToken()) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="request_id"
                                        value="<?= $r['request_id'] ?>"
                                    >

                                    <button
                                        class="btn btn-sm btn-primary"
                                        name="action"
                                        value="approve"
                                    >
                                        Approve
                                    </button>

                                    <button
                                        class="btn btn-sm btn-outline-secondary"
                                        name="action"
                                        value="reject"
                                    >
                                        Reject
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
