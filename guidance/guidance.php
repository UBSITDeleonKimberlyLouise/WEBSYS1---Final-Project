<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'includes/functions.php';
requireRole(['Guidance Counselor']);

$msg = '';
if (isset($_POST['save'])) {
    verifyCsrf();
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $interview = $_POST['interview'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $allowed = ['Passed', 'Failed'];

    if ($id && in_array($interview, $allowed, true) && $notes !== '' && mb_strlen($notes) <= 500) {
        $pdo->beginTransaction();
        try {
            // Confirm this is a current-year enrollment before changing it.
            $check = $pdo->prepare("SELECT e.enrollment_id, e.enrollment_status FROM enrollments e
                JOIN school_years sy ON sy.school_year_id = e.school_year_id
                WHERE e.enrollment_id = ? AND sy.year_status = 'Current' FOR UPDATE");
            $check->execute([$id]);
            if (!($locked = $check->fetch(PDO::FETCH_ASSOC))) {
                throw new RuntimeException('Enrollment not found in current school year.');
            }
            if (in_array($locked['enrollment_status'], ['Officially Enrolled', 'Withdrawn', 'Rejected', 'Completed'], true)) {
                throw new RuntimeException('This enrollment is not open for guidance reassessment.');
            }

            // Preserve historical reading status in the legacy guidance_records column.
            // Guidance no longer assesses or changes reading comprehension.
            $last = $pdo->prepare("SELECT reading_status FROM guidance_records
                WHERE enrollment_id = ? ORDER BY guide_id DESC LIMIT 1");
            $last->execute([$id]);
            $previousReading = $last->fetchColumn();
            if (!in_array($previousReading, ['Pending', 'Passed', 'Failed'], true)) {
                $previousReading = 'Pending';
            }
            $save = $pdo->prepare("INSERT INTO guidance_records
                (enrollment_id, reading_status, interview_status, notes, assessed_by)
                VALUES (?, ?, ?, ?, ?)");
            $save->execute([$id, $previousReading, $interview, $notes, $_SESSION['user_id']]);

            $approval = $interview === 'Passed' ? 'Approved'
                : ($interview === 'Failed' ? 'Not Approved' : 'Pending');
            $update = $pdo->prepare("UPDATE enrollments SET
                guidance_assessment_status = ?, guidance_approval_status = ?,
                guidance_assessment_remarks = ?, guidance_assessed_by = ?,
                guidance_assessment_date = CURDATE(),
                guidance_approved_by = ?, guidance_approved_at = ?
                WHERE enrollment_id = ?");
            $update->execute([
                $interview, $approval, $notes, $_SESSION['user_id'],
                $approval === 'Approved' ? $_SESSION['user_id'] : null,
                $approval === 'Approved' ? date('Y-m-d H:i:s') : null,
                $id
            ]);

            $notice = $pdo->prepare("INSERT INTO user_notifications
                (user_id, title, message, notification_type)
                SELECT user_id, 'Guidance Interview Updated', ?, 'System'
                FROM users WHERE role IN ('School Registrar', 'System Administrator')
                AND account_status = 'Active'");
            $notice->execute(['Guidance interview for enrollment #' . $id . ': ' . $approval . '. Verify requirements before finalizing enrollment.']);
            $pdo->commit();
            $msg = 'Interview saved. Registrar/Admin notified.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log($e->getMessage());
            $msg = 'Unable to save the interview. Please check the enrollment status.';
        }
    } else {
        $msg = 'Please enter valid assessment results.';
    }
}

$sql = "SELECT e.enrollment_id, s.*, cs.grade_level, cs.section_name,
               e.enrollment_status, e.guidance_approval_status,
               e.guidance_assessment_status, gr.notes AS guide_notes
        FROM enrollments e
        JOIN students s ON s.student_lrn = e.student_lrn
        LEFT JOIN class_sections cs ON cs.section_id = e.section_id
        JOIN school_years sy ON sy.school_year_id = e.school_year_id
        LEFT JOIN guidance_records gr ON gr.guide_id = (
            SELECT MAX(g2.guide_id) FROM guidance_records g2
            WHERE g2.enrollment_id = e.enrollment_id
        )
        WHERE sy.year_status = 'Current'
        ORDER BY cs.grade_level, cs.section_name,
                 CASE WHEN s.sex = 'Male' THEN 0 ELSE 1 END,
                 s.last_name, s.first_name";
$prep = $pdo->prepare($sql);
$prep->execute();
$rows = $prep->fetchAll(PDO::FETCH_ASSOC);
$groups = [];
foreach ($rows as $row) {
    $group = ($row['grade_level'] ?: 'Unassigned') . ' / ' . ($row['section_name'] ?: 'Unassigned');
    $groups[$group][] = $row;
}

require 'includes/header.php';
?>
<div class="page-heading"><div><h1>Guidance Assessments</h1><p>Interview, comments, and approval only</p></div></div>
<?php if ($msg): ?><div class="alert alert-info"><?= e($msg) ?></div><?php endif; ?>
<?php if (!$rows): ?>
<div class="alert alert-info">No learners with enrollment records in the current school year are available for guidance interviews. Ask the Registrar to create or assign the learner's pending enrollment first. Guidance approval does not by itself finalize enrollment; the Registrar must also verify the requirements.</div>
<?php endif; ?>
<?php foreach ($groups as $section => $students): ?>
<div class="card p-3 mb-3">
    <h5><?= e($section) ?></h5>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>LRN</th><th>Student</th><th>Interview Result</th><th>Enrollment</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($students as $row): ?>
                <tr>
                    <td><?= e($row['student_lrn']) ?></td>
                    <td><?= e($row['last_name'] . ', ' . $row['first_name']) ?></td>
                    <td><?= e($row['enrollment_status']) ?></td>
                    <td><?= e($row['guidance_assessment_status']) ?></td>
                    <td><?php if (in_array($row['enrollment_status'], ['Officially Enrolled','Withdrawn','Rejected','Completed'], true)): ?><span class="text-muted small">Read-only</span><?php else: ?><button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#guide<?= (int)$row['enrollment_id'] ?>"><?= in_array($row['guidance_assessment_status'], ['Passed','Failed'], true) ? 'Edit Result' : 'Assess' ?></button><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>
<?php foreach ($rows as $row): ?>
<div class="modal fade" id="guide<?= (int)$row['enrollment_id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Student Profile and Assessment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <h6><?= e($row['last_name'] . ', ' . $row['first_name'] . ' ' . ($row['middle_name'] ?? '')) ?></h6>
                <div class="row g-3 mb-4">
                <?php foreach (['student_lrn'=>'LRN','sex'=>'Sex','birth_date'=>'Birthdate',
                    'student_status'=>'Student Status','previous_school'=>'Previous School',
                    'current_house_no'=>'House No.','current_street_sitio'=>'Street',
                    'current_barangay'=>'Barangay','current_municipality'=>'Municipality',
                    'current_province'=>'Province','student_contact_number'=>'Contact Number',
                    'parent_guardian_name'=>'Parent / Guardian',
                    'parent_guardian_contact'=>'Guardian Contact',
                    'emergency_contact_name'=>'Emergency Contact',
                    'emergency_contact_number'=>'Emergency Phone'] as $field => $label): ?>
                    <div class="col-md-4"><small class="text-muted d-block"><?= e($label) ?></small><strong><?= e($row[$field] ?? '') ?></strong></div>
                <?php endforeach; ?>
                </div>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="id" value="<?= (int)$row['enrollment_id'] ?>">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Interview</label><select name="interview" class="form-select" required><option value="" <?= !in_array($row['guidance_assessment_status'], ['Passed','Failed'], true) ? 'selected' : '' ?> disabled>Select result</option>
                            <?php foreach (['Pending','Passed','Failed'] as $v): ?><option value="<?= e($v) ?>" <?= $v === $row['guidance_assessment_status'] ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
                        </select></div>
                        <div class="col-12"><label class="form-label">Remarks</label><textarea name="notes" class="form-control" maxlength="500" rows="3" placeholder="Enter interview findings or reassessment remarks"><?= e($row['guide_notes'] ?? '') ?></textarea></div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-3"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="save" class="btn btn-primary">Save</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php require 'includes/footer.php'; ?>
