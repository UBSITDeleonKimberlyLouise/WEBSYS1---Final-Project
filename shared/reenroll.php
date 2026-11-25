
<?php
chdir(dirname(__DIR__));

session_start();

require_once 'dbconn.php';
require_once 'includes/functions.php';

requireRole(['Student', 'School Registrar', 'System Administrator']);

$staff = in_array($_SESSION['role'], ['School Registrar', 'System Administrator'], true);
$msg = '';
$err = '';

$year = $pdo->query("
    SELECT school_year_id, school_year_name
    FROM school_years
    WHERE year_status='Current'
    ORDER BY school_year_id DESC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

$yearId = (int)($year['school_year_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    try {
        if (!$yearId) {
            throw new Exception('No current school year is configured.');
        }

        $action = (string)($_POST['action'] ?? '');

        if ($action === 'request') {
            if ($staff) {
                $lrn = trim((string)($_POST['student_lrn'] ?? ''));
            } else {
                $q = $pdo->prepare('SELECT student_lrn FROM students WHERE user_id=?');
                $q->execute([$_SESSION['user_id']]);
                $lrn = (string)$q->fetchColumn();
            }

            if (!preg_match('/^[0-9]{12}$/', $lrn)) {
                throw new Exception('Invalid LRN.');
            }

            $pdo->beginTransaction();

            $q = $pdo->prepare('SELECT student_status FROM students WHERE student_lrn=? FOR UPDATE');
            $q->execute([$lrn]);
            $status = $q->fetchColumn();

            if ($status === false) {
                throw new Exception('Student record not found.');
            }

            if ($status !== 'Active') {
                throw new Exception('Student is not active. Contact the Registrar.');
            }

            $q = $pdo->prepare('SELECT enrollment_id FROM enrollments WHERE student_lrn=? AND school_year_id=?');
            $q->execute([$lrn, $yearId]);

            if ($q->fetchColumn()) {
                throw new Exception('Already enrolled for the current school year.');
            }

            $q = $pdo->prepare('SELECT enrollment_id FROM enrollments WHERE student_lrn=? AND school_year_id<>? ORDER BY school_year_id DESC LIMIT 1');
            $q->execute([$lrn, $yearId]);

            if (!$q->fetchColumn()) {
                throw new Exception('No previous enrollment found. Use first-time enrollment instead.');
            }

            $q = $pdo->prepare('
                INSERT INTO reenrollment_requests(student_lrn, school_year_id, requested_by)
                VALUES(?,?,?)
            ');
            $q->execute([$lrn, $yearId, (int)$_SESSION['user_id']]);

            $pdo->commit();

            $msg = 'Re-enrollment request submitted. No documents or registration form required.';
        } elseif ($action === 'review' && $staff) {
            $id = (int)($_POST['request_id'] ?? 0);
            $decision = (string)($_POST['decision'] ?? '');

            if (!in_array($decision, ['Approved', 'Rejected'], true)) {
                throw new Exception('Invalid review decision.');
            }

            $pdo->beginTransaction();

            $q = $pdo->prepare("
                SELECT *
                FROM reenrollment_requests
                WHERE request_id=?
                AND school_year_id=?
                AND request_status='Pending'
                FOR UPDATE
            ");
            $q->execute([$id, $yearId]);
            $req = $q->fetch(PDO::FETCH_ASSOC);

            if (!$req) {
                throw new Exception('Pending request not found.');
            }

            $lrn = (string)$req['student_lrn'];

            if ($decision === 'Approved') {
                $q = $pdo->prepare('SELECT enrollment_id FROM enrollments WHERE student_lrn=? AND school_year_id=?');
                $q->execute([$lrn, $yearId]);

                if ($q->fetchColumn()) {
                    throw new Exception('This student is already enrolled for this school year.');
                }

                $q = $pdo->prepare('
                    SELECT e.grade_level, e.shs_track, e.final_result
                    FROM enrollments e
                    WHERE e.student_lrn=?
                    AND e.school_year_id<>?
                    ORDER BY e.school_year_id DESC, e.enrollment_id DESC
                    LIMIT 1
                ');
                $q->execute([$lrn, $yearId]);
                $previous = $q->fetch(PDO::FETCH_ASSOC);

                if (!$previous) {
                    throw new Exception('No previous enrollment found.');
                }

                $grade = (string)($_POST['grade_level'] ?? '');
                $section = (int)($_POST['section_id'] ?? 0);

                if (!in_array($grade, ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'], true)) {
                    throw new Exception('Choose a valid grade level.');
                }

                if (!$section) {
                    throw new Exception('Select a section before approval.');
                }

                $q = $pdo->prepare('
                    SELECT *
                    FROM class_sections
                    WHERE section_id=?
                    AND school_year_id=?
                    AND grade_level=?
                    FOR UPDATE
                ');
                $q->execute([$section, $yearId, $grade]);
                $class = $q->fetch(PDO::FETCH_ASSOC);

                if (!$class || $class['section_status'] === 'Closed') {
                    throw new Exception('Section does not match the current year and grade or is closed.');
                }

                $q = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM enrollments
                    WHERE section_id=?
                    AND enrollment_status NOT IN ('Rejected','Withdrawn')
                ");
                $q->execute([$section]);

                if ((int)$q->fetchColumn() >= (int)$class['capacity']) {
                    throw new Exception('Section is already full.');
                }

                $isShs = in_array($grade, ['Grade 11', 'Grade 12'], true);
                $track = $isShs ? $class['shs_track'] : null;

                if ($isShs && !in_array($track, ['Academic', 'TechPro'], true)) {
                    throw new Exception('Selected SHS section has no valid track.');
                }

                $q = $pdo->prepare("
                    INSERT INTO enrollments(
                        student_lrn,
                        school_year_id,
                        grade_level,
                        enrollment_category,
                        shs_track,
                        section_id,
                        enrollment_date,
                        enrollment_status,
                        processed_by
                    )
                    VALUES(?,?,?,'Returning',?,?,CURDATE(),'Officially Enrolled',?)
                ");

                $q = $pdo->prepare("
                    INSERT INTO enrollments(
                        student_lrn,
                        school_year_id,
                        grade_level,
                        enrollment_category,
                        shs_track,
                        section_id,
                        enrollment_date,
                        enrollment_status,
                        processed_by
                    )
                    VALUES(?,?,?,'Returning',?,?,CURDATE(),'Officially Enrolled',?)
                ");

                $q->execute([
                    $lrn,
                    $yearId,
                    $grade,
                    $track,
                    $section,
                    (int)$_SESSION['user_id']
                ]);
            }

            $q = $pdo->prepare('
                UPDATE reenrollment_requests
                SET request_status=?,
                    reviewed_by=?,
                    reviewed_at=NOW(),
                    review_notes=?
                WHERE request_id=?
            ');
            $q->execute([
                $decision,
                (int)$_SESSION['user_id'],
                trim((string)($_POST['review_notes'] ?? '')) ?: null,
                $id
            ]);

            $pdo->commit();

            $msg = 'Request ' . $decision . '.';
        } else {
            throw new Exception('Action not allowed.');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $err = $e instanceof PDOException
            ? 'Unable to process request. Check the database migration and record constraints.'
            : $e->getMessage();

        error_log('Re-enrollment: ' . $e->getMessage());
    }
}

if ($staff) {
    $q = $pdo->prepare("
        SELECT
            r.*,
            s.first_name,
            s.last_name,
            e.grade_level AS previous_grade,
            e.final_result AS previous_result
        FROM reenrollment_requests r
        JOIN students s ON s.student_lrn=r.student_lrn
        LEFT JOIN enrollments e ON e.enrollment_id=(
            SELECT e2.enrollment_id
            FROM enrollments e2
            WHERE e2.student_lrn=r.student_lrn
            AND e2.school_year_id<>r.school_year_id
            ORDER BY e2.school_year_id DESC, e2.enrollment_id DESC
            LIMIT 1
        )
        WHERE r.school_year_id=?
        ORDER BY r.created_at DESC
    ");
    $q->execute([$yearId]);
    $requests = $q->fetchAll(PDO::FETCH_ASSOC);

    $q = $pdo->prepare("
        SELECT section_id, grade_level, section_name, shs_track
        FROM class_sections
        WHERE school_year_id=?
        AND section_status<>'Closed'
        ORDER BY grade_level, section_name
    ");
    $q->execute([$yearId]);
    $sections = $q->fetchAll(PDO::FETCH_ASSOC);
} else {
    $q = $pdo->prepare('SELECT student_lrn FROM students WHERE user_id=?');
    $q->execute([$_SESSION['user_id']]);
    $myLrn = (string)$q->fetchColumn();

    $q = $pdo->prepare('SELECT request_status, created_at FROM reenrollment_requests WHERE student_lrn=? AND school_year_id=?');
    $q->execute([$myLrn, $yearId]);
    $myRequest = $q->fetch(PDO::FETCH_ASSOC);

    $q = $pdo->prepare('SELECT enrollment_id FROM enrollments WHERE student_lrn=? AND school_year_id=?');
    $q->execute([$myLrn, $yearId]);
    $alreadyEnrolled = (bool)$q->fetchColumn();
}

require 'includes/header.php';
?>

<div class="page-heading">
    <div>
        <h1>Re-enrollment</h1>
        <p>Use existing student records for the current school year.</p>
    </div>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>

<?php if ($err): ?>
    <div class="alert alert-danger"><?= e($err) ?></div>
<?php endif; ?>

<div class="card p-4 mb-3">
    <h5>School Year: <?= e($year['school_year_name'] ?? 'Not configured') ?></h5>
    <p class="text-muted">Returning students do not need to complete a new registration form or reupload accepted documents.</p>

    <?php if ($staff): ?>
        <form method="post" class="row g-2">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="action" value="request">

            <div class="col-md-8">
                <label class="form-label">Returning student LRN</label>
                <input
                    class="form-control"
                    name="student_lrn"
                    pattern="[0-9]{12}"
                    maxlength="12"
                    required
                >
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-primary w-100">Start Re-enrollment</button>
            </div>
        </form>
    <?php elseif ($alreadyEnrolled): ?>
        <div class="alert alert-success">You already have an enrollment for this school year.</div>
    <?php elseif ($myRequest): ?>
        <div class="alert alert-info">
            Request status: <?= e($myRequest['request_status']) ?>
        </div>
    <?php else: ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="action" value="request">
            <button class="btn btn-primary">Request Re-enrollment</button>
        </form>
    <?php endif; ?>
</div>

<?php if ($staff): ?>
    <div class="card p-4">
        <h5>Re-enrollment Requests</h5>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Previous Grade / Result</th>
                        <th>Status</th>
                        <th>Review</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td>
                                <?= e($r['student_lrn']) ?>
                                <div><?= e($r['last_name'] . ', ' . $r['first_name']) ?></div>
                            </td>

                            <td>
                                <?= e($r['previous_grade'] ?? '—') ?>
                                /
                                <?= e($r['previous_result'] ?? 'Not finalized') ?>
                            </td>

                            <td><?= e($r['request_status']) ?></td>

                            <td>
                                <?php if ($r['request_status'] === 'Pending'): ?>
                                    <form method="post" class="d-grid gap-2">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="review">
                                        <input type="hidden" name="request_id" value="<?= (int)$r['request_id'] ?>">

                                        <label>Grade level</label>
                                        <select name="grade_level" class="form-select" required>
                                            <option value="">Choose grade</option>
                                            <?php foreach (['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'] as $grade): ?>
                                                <option><?= e($grade) ?></option>
                                            <?php endforeach; ?>
                                        </select>

                                        <label>Section</label>
                                        <select name="section_id" class="form-select" required>
                                            <option value="">Choose section</option>
                                            <?php foreach ($sections as $s): ?>
                                                <option value="<?= (int)$s['section_id'] ?>">
                                                    <?= e($s['grade_level'] . ' — ' . $s['section_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <input
                                            class="form-control"
                                            name="review_notes"
                                            placeholder="Notes (optional)"
                                        >

                                        <div class="d-flex gap-2">
                                            <button class="btn btn-success btn-sm" name="decision" value="Approved">
                                                Approve
                                            </button>

                                            <button
                                                class="btn btn-outline-danger btn-sm"
                                                name="decision"
                                                value="Rejected"
                                                formnovalidate
                                            >
                                                Reject
                                            </button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    Reviewed
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$requests): ?>
                        <tr>
                            <td colspan="4" class="text-muted">No requests yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
