<?php
session_start();
require_once __DIR__ . '/../config/dbconn.php';

/*
 * Scholarship Monitoring module (Module 6).
 * Standalone for now because classes/Auth.php and authorization/login.php
 * are not implemented yet in this repo. Once login is in place, replace
 * the block below with the real session/role check, e.g.:
 *   require_once __DIR__ . '/../classes/Auth.php';
 *   requireRole('Scholarship Coordinator');
 */
if (empty($_SESSION['role'])) {
    $_SESSION['role'] = 'Scholarship Coordinator'; // TEMP placeholder until login exists
}
if ($_SESSION['role'] !== 'Scholarship Coordinator') {
    http_response_code(403);
    exit('Access restricted to the Scholarship Coordinator.');
}

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function csrfToken() { return $_SESSION['csrf_token']; }
function checkCsrf() {
    if (($_POST['csrf_token'] ?? '') !== $_SESSION['csrf_token']) {
        http_response_code(400);
        exit('Invalid request (CSRF check failed). Please go back and try again.');
    }
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    try {
        $action = $_POST['monitor_action'] ?? '';

        if ($action === 'award') {
            $stmt = $pdo->prepare(
                'INSERT INTO scholarship_awards
                    (application_id, award_start_date, award_end_date, award_status, renewal_eligibility, academic_performance_remarks, recorded_by)
                 VALUES (?,?,?,?,?,?,?)'
            );
            $stmt->execute([
                $_POST['application_id'],
                $_POST['award_start_date'] ?: null,
                $_POST['award_end_date'] ?: null,
                $_POST['award_status'],
                $_POST['renewal_eligibility'],
                $_POST['academic_performance_remarks'] ?: null,
                $_SESSION['user_id'] ?? null,
            ]);
            $message = 'Scholarship award/beneficiary record saved.';

        } elseif ($action === 'update_award') {
            $awardId = (int)($_POST['award_id'] ?? 0);
            $check = $pdo->prepare('SELECT award_id FROM scholarship_awards WHERE award_id = ?');
            $check->execute([$awardId]);
            if (!$check->fetch()) {
                throw new Exception('Scholarship award record not found.');
            }
            $stmt = $pdo->prepare(
                'UPDATE scholarship_awards
                    SET award_start_date=?, award_end_date=?, award_status=?, renewal_eligibility=?, academic_performance_remarks=?, recorded_by=?
                  WHERE award_id=?'
            );
            $stmt->execute([
                $_POST['award_start_date'] ?: null,
                $_POST['award_end_date'] ?: null,
                $_POST['award_status'],
                $_POST['renewal_eligibility'],
                $_POST['academic_performance_remarks'] ?: null,
                $_SESSION['user_id'] ?? null,
                $awardId,
            ]);
            $message = 'Beneficiary record updated.';

        } elseif ($action === 'distribution') {
            $stmt = $pdo->prepare(
                'INSERT INTO assistance_distributions
                    (award_id, distribution_date, assistance_description, quantity_or_coverage, remarks, recorded_by)
                 VALUES (?,?,?,?,?,?)'
            );
            $stmt->execute([
                $_POST['award_id'],
                $_POST['distribution_date'],
                $_POST['assistance_description'],
                $_POST['quantity_or_coverage'] ?: null,
                $_POST['distribution_remarks'] ?: null,
                $_SESSION['user_id'] ?? null,
            ]);
            $message = 'Assistance distribution recorded.';
        }
    } catch (PDOException $ex) {
        $error = 'Database error: ' . $ex->getMessage();
    } catch (Exception $ex) {
        $error = $ex->getMessage();
    }
}

// Data for display
$awards = $pdo->query(
    "SELECT aw.*, a.application_id, s.first_name, s.last_name, p.program_name
     FROM scholarship_awards aw
     JOIN scholarship_applications a ON aw.application_id = a.application_id
     JOIN scholarship_programs p ON a.program_id = p.program_id
     JOIN students s ON a.student_lrn = s.student_lrn
     ORDER BY aw.award_id DESC"
)->fetchAll();

$editAward = null;
if (!empty($_GET['edit_award'])) {
    $stmt = $pdo->prepare('SELECT * FROM scholarship_awards WHERE award_id = ?');
    $stmt->execute([(int)$_GET['edit_award']]);
    $editAward = $stmt->fetch();
}

$approvedApplications = $pdo->query(
    "SELECT a.application_id, s.student_lrn, s.first_name, s.last_name, p.program_name
     FROM scholarship_applications a
     JOIN scholarship_programs p ON a.program_id = p.program_id
     JOIN students s ON a.student_lrn = s.student_lrn
     WHERE a.application_status = 'Approved'
       AND a.application_id NOT IN (SELECT application_id FROM scholarship_awards)"
)->fetchAll();

$allAwardsForDropdown = $pdo->query(
    "SELECT aw.award_id, s.first_name, s.last_name, p.program_name
     FROM scholarship_awards aw
     JOIN scholarship_applications a ON aw.application_id = a.application_id
     JOIN scholarship_programs p ON a.program_id = p.program_id
     JOIN students s ON a.student_lrn = s.student_lrn"
)->fetchAll();

$distributions = $pdo->query(
    "SELECT d.*, s.first_name, s.last_name, p.program_name
     FROM assistance_distributions d
     JOIN scholarship_awards aw ON d.award_id = aw.award_id
     JOIN scholarship_applications a ON aw.application_id = a.application_id
     JOIN scholarship_programs p ON a.program_id = p.program_id
     JOIN students s ON a.student_lrn = s.student_lrn
     ORDER BY d.distribution_id DESC
     LIMIT 100"
)->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Scholarship Monitoring</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">

<h1>Scholarship Monitoring</h1>
<p class="text-muted">Module 6 — track scholarship beneficiaries, renewal eligibility, and assistance distribution.</p>

<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card p-3">
      <h5><?= $editAward ? 'Update Beneficiary Record #' . e($editAward['award_id']) : 'Record Approved Scholarship Award' ?></h5>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <?php if ($editAward): ?>
          <input type="hidden" name="monitor_action" value="update_award">
          <input type="hidden" name="award_id" value="<?= e($editAward['award_id']) ?>">
          <p class="small text-muted">Editing award for application #<?= e($editAward['application_id']) ?>.
            <a href="monitoring.php">Cancel / add a new award instead</a></p>
        <?php else: ?>
          <input type="hidden" name="monitor_action" value="award">
          <label class="form-label">Approved Application</label>
          <select class="form-select mb-2" name="application_id" required>
            <?php foreach ($approvedApplications as $a): ?>
              <option value="<?= e($a['application_id']) ?>">
                #<?= e($a['application_id']) ?> <?= e($a['last_name'] . ', ' . $a['first_name']) ?> — <?= e($a['program_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
        <div class="row">
          <div class="col"><label class="form-label">Start Date</label>
            <input class="form-control" type="date" name="award_start_date" value="<?= e($editAward['award_start_date'] ?? '') ?>"></div>
          <div class="col"><label class="form-label">End Date</label>
            <input class="form-control" type="date" name="award_end_date" value="<?= e($editAward['award_end_date'] ?? '') ?>"></div>
        </div>
        <label class="form-label mt-2">Award Status</label>
        <select class="form-select mb-2" name="award_status">
          <?php foreach (['Active','For Renewal','Renewed','Completed','Discontinued'] as $v): ?>
            <option <?= ($editAward['award_status'] ?? '') === $v ? 'selected' : '' ?>><?= e($v) ?></option>
          <?php endforeach; ?>
        </select>
        <label class="form-label">Renewal Eligibility</label>
        <select class="form-select mb-2" name="renewal_eligibility">
          <?php foreach (['Pending','Eligible','Not Eligible','Not Applicable'] as $v): ?>
            <option <?= ($editAward['renewal_eligibility'] ?? '') === $v ? 'selected' : '' ?>><?= e($v) ?></option>
          <?php endforeach; ?>
        </select>
        <label class="form-label">Academic Performance Remarks</label>
        <textarea class="form-control mb-2" name="academic_performance_remarks"><?= e($editAward['academic_performance_remarks'] ?? '') ?></textarea>
        <button class="btn btn-primary"><?= $editAward ? 'Save Changes' : 'Save Award' ?></button>
      </form>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card p-3">
      <h5>Record Assistance Distribution</h5>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="monitor_action" value="distribution">
        <label class="form-label">Award</label>
        <select class="form-select mb-2" name="award_id" required>
          <?php foreach ($allAwardsForDropdown as $aw): ?>
            <option value="<?= e($aw['award_id']) ?>">
              #<?= e($aw['award_id']) ?> <?= e($aw['last_name'] . ', ' . $aw['first_name']) ?> — <?= e($aw['program_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <label class="form-label">Distribution Date</label>
        <input class="form-control mb-2" type="date" name="distribution_date" value="<?= date('Y-m-d') ?>" required>
        <label class="form-label">Assistance Description</label>
        <input class="form-control mb-2" name="assistance_description" required>
        <label class="form-label">Quantity / Coverage</label>
        <input class="form-control mb-2" name="quantity_or_coverage">
        <label class="form-label">Remarks</label>
        <input class="form-control mb-3" name="distribution_remarks">
        <button class="btn btn-primary">Record Assistance</button>
      </form>
    </div>
  </div>
</div>

<div class="card p-3 mt-3">
  <h5>Scholarship Beneficiaries</h5>
  <div class="table-responsive">
    <table class="table table-sm">
      <tr><th>Award #</th><th>Student</th><th>Program</th><th>Period</th><th>Status</th><th>Renewal Eligibility</th><th></th></tr>
      <?php if (!$awards): ?><tr><td colspan="7" class="text-muted">No scholarship awards recorded yet.</td></tr><?php endif; ?>
      <?php foreach ($awards as $aw): ?>
        <tr>
          <td>#<?= e($aw['award_id']) ?></td>
          <td><?= e($aw['last_name'] . ', ' . $aw['first_name']) ?></td>
          <td><?= e($aw['program_name']) ?></td>
          <td><?= e(($aw['award_start_date'] ?? '—') . ' to ' . ($aw['award_end_date'] ?? '—')) ?></td>
          <td><?= e($aw['award_status']) ?></td>
          <td><?= e($aw['renewal_eligibility']) ?></td>
          <td><a href="monitoring.php?edit_award=<?= e($aw['award_id']) ?>">Edit</a></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>

<div class="card p-3 mt-3">
  <h5>Assistance Distribution History</h5>
  <div class="table-responsive">
    <table class="table table-sm">
      <tr><th>Award #</th><th>Student</th><th>Date</th><th>Assistance</th><th>Qty / Coverage</th><th>Remarks</th></tr>
      <?php if (!$distributions): ?><tr><td colspan="6" class="text-muted">No assistance distributions recorded yet.</td></tr><?php endif; ?>
      <?php foreach ($distributions as $d): ?>
        <tr>
          <td>#<?= e($d['award_id']) ?></td>
          <td><?= e($d['last_name'] . ', ' . $d['first_name']) ?></td>
          <td><?= e($d['distribution_date']) ?></td>
          <td><?= e($d['assistance_description']) ?></td>
          <td><?= e($d['quantity_or_coverage'] ?? '—') ?></td>
          <td><?= e($d['remarks'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>

</body>
</html>
