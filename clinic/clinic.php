<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'includes/functions.php';
requireRole(['Clinic Personnel']);

$msg = '';
if (isset($_POST['save'])) {
    verifyCsrf();
    $lrn = trim($_POST['lrn'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    $medicine = trim($_POST['medicine'] ?? '');
    $status = $_POST['status'] ?? '';
    $allowed = ['Stayed in Clinic', 'Returned to Class', 'Sent Home'];
    if (preg_match('/^[0-9]{12}$/', $lrn) && $reason !== '' && in_array($status, $allowed, true)) {
        try {
            $sql = "INSERT INTO clinic_visits (student_lrn, visit_date, reason, medicine, disposition, recorded_by)
                    VALUES (:lrn, NOW(), :reason, :medicine, :status, :user)";
            $prep = $pdo->prepare($sql);
            $prep->execute([
                ':lrn' => $lrn, ':reason' => $reason, ':medicine' => $medicine,
                ':status' => $status, ':user' => $_SESSION['user_id']
            ]);
            $msg = 'Clinic visit recorded.';
        } catch (Exception $e) {
            error_log($e->getMessage());
            $msg = 'Unable to save visit. Check the student LRN.';
        }
    } else {
        $msg = 'Please enter a valid LRN, reason and disposition.';
    }
}
$sql = "SELECT v.*, s.first_name, s.last_name FROM clinic_visits v
        JOIN students s ON s.student_lrn=v.student_lrn ORDER BY v.visit_date DESC LIMIT 50";
$prep = $pdo->prepare($sql);
$prep->execute();
$visits = $prep->fetchAll(PDO::FETCH_ASSOC);
$sql = "SELECT student_lrn, first_name, last_name, emergency_contact_name,
               emergency_contact_number, current_barangay, current_municipality
        FROM students ORDER BY CASE WHEN sex='Male' THEN 0 ELSE 1 END, last_name, first_name";
$prep = $pdo->prepare($sql);
$prep->execute();
$students = $prep->fetchAll(PDO::FETCH_ASSOC);
require 'includes/header.php';
?>
<div class="page-heading"><div><h1>Clinic Records</h1><p>Student visits and emergency contacts</p></div></div>
<?php if ($msg): ?><div class="alert alert-info"><?= e($msg) ?></div><?php endif; ?>
<div class="card p-3 mb-3"><h5>Add Clinic Visit</h5>
<form method="post" class="row g-2">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<div class="col-md-3"><label class="form-label">Student LRN</label><select class="form-select" name="lrn" required><option value="">Select student</option><?php foreach ($students as $s): ?><option value="<?= e($s['student_lrn']) ?>"><?= e($s['last_name'].', '.$s['first_name'].' ('.$s['student_lrn'].')') ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">Reason</label><input class="form-control" name="reason" maxlength="255" required></div>
<div class="col-md-2"><label class="form-label">Medicine / Care</label><input class="form-control" name="medicine" maxlength="255"></div>
<div class="col-md-3"><label class="form-label">Disposition</label><select name="status" class="form-select"><option>Stayed in Clinic</option><option>Returned to Class</option><option>Sent Home</option></select></div>
<div class="col-md-1 d-flex align-items-end"><button class="btn btn-primary" name="save">Save</button></div>
</form></div>
<div class="card p-3 mb-3"><h5>Recent Clinic Visits</h5><div class="table-responsive"><table class="table"><thead><tr><th>Date / Time</th><th>Student</th><th>Reason</th><th>Medicine</th><th>Disposition</th></tr></thead><tbody>
<?php foreach ($visits as $v): ?><tr><td><?= e($v['visit_date']) ?></td><td><?= e($v['last_name'].', '.$v['first_name']) ?></td><td><?= e($v['reason']) ?></td><td><?= e($v['medicine']) ?></td><td><?= e($v['disposition']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php require 'includes/footer.php'; ?>
