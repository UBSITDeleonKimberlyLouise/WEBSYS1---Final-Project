<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'includes/functions.php';
require_once 'includes/scholarship_files.php';
requireRole(['Student']);

$prep = $pdo->prepare('SELECT student_lrn, student_status, first_name, last_name FROM students WHERE user_id = ?');
$prep->execute([$_SESSION['user_id']]);
$student = $prep->fetch(PDO::FETCH_ASSOC);
if (!$student) { http_response_code(404); exit('Student profile not found.'); }
$lrn = $student['student_lrn'];
$msg = '';
$err = '';

function one(PDO $pdo, string $sql, array $p = []) { $s = $pdo->prepare($sql); $s->execute($p); return $s->fetch(PDO::FETCH_ASSOC); }
function many(PDO $pdo, string $sql, array $p = []) { $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchAll(PDO::FETCH_ASSOC); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $saved = [];
    try {
        if ($student['student_status'] !== 'Active') throw new Exception('Your account is read-only.');
        $action = $_POST['action'] ?? '';
        if ($action === 'apply') {
            $program = one($pdo, "SELECT * FROM scholarship_programs WHERE program_id=? AND program_status='Published' AND application_method='School Managed' AND (application_start IS NULL OR application_start<=CURDATE()) AND (application_deadline IS NULL OR application_deadline>=CURDATE())", [(int)($_POST['program_id'] ?? 0)]);
            if (!$program) throw new Exception('This scholarship is not open for application.');
            if (!one($pdo, "SELECT e.enrollment_id FROM enrollments e JOIN school_years sy ON sy.school_year_id=e.school_year_id WHERE e.student_lrn=? AND e.enrollment_status='Officially Enrolled' AND sy.year_status='Current' LIMIT 1", [$lrn])) throw new Exception('Only officially enrolled students can apply for a scholarship.');
            if (one($pdo, 'SELECT application_id FROM scholarship_applications WHERE program_id=? AND student_lrn=?', [$program['program_id'], $lrn])) throw new Exception('You already applied for this scholarship.');
            $docNames = scholarshipDocNames($program['required_documents']);
            try {
                foreach ($docNames as $i => $name) $saved[$i] = scholarshipUpload($lrn, [
                    'name' => $_FILES['docs']['name'][$i] ?? '', 'type' => $_FILES['docs']['type'][$i] ?? '', 'tmp_name' => $_FILES['docs']['tmp_name'][$i] ?? '',
                    'error' => $_FILES['docs']['error'][$i] ?? UPLOAD_ERR_NO_FILE, 'size' => $_FILES['docs']['size'][$i] ?? 0]);
                $pdo->beginTransaction();
                $pdo->prepare("INSERT INTO scholarship_applications (program_id,student_lrn,application_date,application_status) VALUES (?,?,CURDATE(),'Pending')")->execute([$program['program_id'], $lrn]);
                $appId = (int)$pdo->lastInsertId();
                $ins = $pdo->prepare("INSERT INTO scholarship_application_documents (application_id,document_name,file_path,verification_status) VALUES (?,?,?,'Pending')");
                foreach ($docNames as $i => $name) $ins->execute([$appId, $name, $saved[$i]]);
                $pdo->commit();
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                foreach ($saved as $path) scholarshipDeleteFile($path);
                throw $ex;
            }
            $msg = 'Application submitted. The Scholarship Coordinator will verify your documents.';
            try {
                $n = $pdo->prepare("INSERT INTO user_notifications (user_id,title,message,notification_type,related_url,is_read) VALUES (?,?,?,'Scholarship','modules.php?module=applications',0)");
                foreach ($pdo->query("SELECT user_id FROM users WHERE role='Scholarship Coordinator' AND account_status='Active'")->fetchAll(PDO::FETCH_COLUMN) as $cid) {
                    $n->execute([(int)$cid, 'New Scholarship Application', trim($student['first_name'] . ' ' . $student['last_name']) . ' (LRN: ' . $lrn . ') applied for ' . $program['program_name'] . '.']);
                }
            } catch (Throwable $ne) { error_log('Scholarship notification failed: ' . $ne->getMessage()); }
        } elseif ($action === 'replace_document') {
            $doc = one($pdo, 'SELECT d.*,a.application_status FROM scholarship_application_documents d JOIN scholarship_applications a ON a.application_id=d.application_id WHERE d.application_document_id=? AND a.student_lrn=?', [(int)($_POST['application_document_id'] ?? 0), $lrn]);
            if (!$doc) throw new Exception('Document not found.');
            if (scholarshipFinalStatus($doc['application_status']) || $doc['verification_status'] === 'Accepted') throw new Exception('This document can no longer be replaced.');
            $path = scholarshipUpload($lrn, $_FILES['document'] ?? []);
            try {
                $pdo->prepare("UPDATE scholarship_application_documents SET file_path=?,verification_status='Pending',remarks=NULL,verified_by=NULL,verified_at=NULL WHERE application_document_id=?")->execute([$path, $doc['application_document_id']]);
            } catch (Throwable $ex) { scholarshipDeleteFile($path); throw $ex; }
            scholarshipDeleteFile($doc['file_path']);
            $msg = 'Document uploaded. Waiting for verification.';
        } elseif ($action === 'withdraw') {
            $st = $pdo->prepare("UPDATE scholarship_applications SET application_status='Withdrawn' WHERE application_id=? AND student_lrn=? AND application_status IN ('Pending','For Verification','Verified','Submitted to Provider')");
            $st->execute([(int)($_POST['application_id'] ?? 0), $lrn]);
            if (!$st->rowCount()) throw new Exception('This application can no longer be withdrawn.');
            $msg = 'Application withdrawn.';
        }
    } catch (Throwable $e) {
        $err = $e instanceof PDOException ? 'Unable to save your scholarship application. Please try again.' : $e->getMessage();
    }
}

$programs = many($pdo, "SELECT p.*,pr.provider_name,(SELECT COUNT(*) FROM scholarship_awards aw JOIN scholarship_applications ap ON ap.application_id=aw.application_id WHERE ap.program_id=p.program_id) AS awarded FROM scholarship_programs p JOIN scholarship_providers pr ON pr.provider_id=p.provider_id WHERE p.program_status='Published' ORDER BY p.application_deadline IS NULL, p.application_deadline, p.program_name");
$myApps = many($pdo, 'SELECT a.*,p.program_name FROM scholarship_applications a JOIN scholarship_programs p ON p.program_id=a.program_id WHERE a.student_lrn=? ORDER BY a.application_id DESC', [$lrn]);
$applied = array_column($myApps, 'application_status', 'program_id');
$isEnrolled = (bool)one($pdo, "SELECT e.enrollment_id FROM enrollments e JOIN school_years sy ON sy.school_year_id=e.school_year_id WHERE e.student_lrn=? AND e.enrollment_status='Officially Enrolled' AND sy.year_status='Current' LIMIT 1", [$lrn]);
$today = date('Y-m-d');
$badge = function ($s) { return ['Approved' => 'success', 'Accepted' => 'success', 'Verified' => 'info', 'Rejected' => 'danger', 'Withdrawn' => 'dark', 'Submitted to Provider' => 'primary'][$s] ?? 'warning'; };
require 'includes/header.php';
?>
<div class="page-heading"><div><h1>Scholarships</h1></div></div>
<?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

<div class="card p-3 mb-3">
  <h5>My Applications</h5>
  <?php if (!$myApps): ?><p class="text-muted mb-0">You have not applied for any scholarship yet.</p><?php endif; ?>
  <?php foreach ($myApps as $a):
    $docs = many($pdo, 'SELECT * FROM scholarship_application_documents WHERE application_id=? ORDER BY application_document_id', [$a['application_id']]);
    $closed = scholarshipFinalStatus($a['application_status']);
  ?>
  <div class="border rounded p-3 mb-2">
    <div class="d-flex flex-wrap justify-content-between gap-2">
      <div><strong><?= e($a['program_name']) ?></strong><div class="small-note">Applied <?= e($a['application_date']) ?><?= $a['decision_date'] ? ' · Decision ' . e($a['decision_date']) : '' ?></div></div>
      <div><span class="badge text-bg-<?= $badge($a['application_status']) ?> fs-6"><?= e($a['application_status']) ?></span></div>
    </div>
    <?php if ($a['remarks']): ?><p class="mb-2 mt-2"><span class="small-note">Remarks:</span> <?= e($a['remarks']) ?></p><?php endif; ?>
    <?php foreach ($docs as $d): ?>
      <div class="row g-2 align-items-center border-top pt-2 mt-2">
        <div class="col-md-4"><?= e($d['document_name']) ?> <span class="badge text-bg-<?= $badge($d['verification_status']) ?>"><?= e($d['verification_status']) ?></span><?php if ($d['remarks']): ?><div class="small-note"><?= e($d['remarks']) ?></div><?php endif; ?></div>
        <div class="col-md-2"><?php if ($d['file_path']): ?><a target="_blank" rel="noopener" href="../shared/scholarship_file.php?id=<?= (int)$d['application_document_id'] ?>">View file</a><?php endif; ?></div>
        <div class="col-md-6">
          <?php if (!$closed && $d['verification_status'] !== 'Accepted' && $student['student_status'] === 'Active'): ?>
          <form method="post" enctype="multipart/form-data" class="d-flex gap-2">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="replace_document"><input type="hidden" name="application_document_id" value="<?= (int)$d['application_document_id'] ?>">
            <input class="form-control form-control-sm" type="file" name="document" accept=".jpg,.jpeg,.png,.pdf" required><button class="btn btn-sm btn-outline-primary">Replace</button>
          </form><?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (in_array($a['application_status'], ['Pending', 'For Verification', 'Verified', 'Submitted to Provider'], true)): ?>
      <form method="post" class="mt-2" onsubmit="return confirm('Withdraw this application?');"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="withdraw"><input type="hidden" name="application_id" value="<?= (int)$a['application_id'] ?>"><button class="btn btn-sm btn-outline-danger">Withdraw Application</button></form>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<h5 class="mb-2">Available Scholarships</h5>
<?php if (!$programs): ?><div class="card p-3"><p class="text-muted mb-0">No scholarships are open right now.</p></div><?php endif; ?>
<?php foreach ($programs as $p):
    $left = $p['available_slots'] === null ? null : max(0, (int)$p['available_slots'] - (int)$p['awarded']);
    $notStarted = $p['application_start'] && $p['application_start'] > $today;
    $ended = $p['application_deadline'] && $p['application_deadline'] < $today;
    $docNames = scholarshipDocNames($p['required_documents']);
?>
<div class="card p-3 mb-3">
  <div class="d-flex flex-wrap justify-content-between gap-2"><div><h5 class="mb-0"><?= e($p['program_name']) ?></h5><div class="small-note"><?= e($p['provider_name']) ?></div></div>
    <div class="small-note text-end">Slots: <?= $left === null ? 'No limit' : e($left . ' of ' . $p['available_slots'] . ' left') ?><br>Application: <?= e(($p['application_start'] ?: '—') . ' to ' . ($p['application_deadline'] ?: '—')) ?></div></div>
  <?php if ($p['description']): ?><p class="mt-2 mb-2"><?= nl2br(e($p['description'])) ?></p><?php endif; ?>
  <div class="row g-3">
    <div class="col-md-4"><div class="small-note">Eligibility requirements</div><?= nl2br(e($p['eligibility_criteria'] ?: 'Not specified')) ?></div>
    <div class="col-md-4"><div class="small-note">Scholarship benefits</div><?= nl2br(e($p['benefits'] ?: 'Not specified')) ?></div>
    <div class="col-md-4"><div class="small-note">Supporting documents</div><?= $docNames ? nl2br(e(implode("\n", $docNames))) : 'None required' ?></div>
  </div>
  <div class="mt-3">
  <?php if ($p['application_method'] === 'External Provider'): ?>
    <a class="btn btn-primary btn-sm" target="_blank" rel="noopener noreferrer" href="<?= e($p['external_application_url']) ?>">Apply on provider's website</a>
  <?php elseif (isset($applied[$p['program_id']])): ?>
    <span class="badge text-bg-<?= $badge($applied[$p['program_id']]) ?>">Your application: <?= e($applied[$p['program_id']]) ?></span>
  <?php elseif ($notStarted): ?><span class="text-muted">Applications open on <?= e($p['application_start']) ?>.</span>
  <?php elseif ($ended): ?><span class="text-muted">The application period has ended.</span>
  <?php elseif (!$isEnrolled || $student['student_status'] !== 'Active'): ?><span class="text-muted">Only officially enrolled students can apply.</span>
  <?php else: ?>
    <form method="post" enctype="multipart/form-data" class="row g-2">
      <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="apply"><input type="hidden" name="program_id" value="<?= (int)$p['program_id'] ?>">
      <?php foreach ($docNames as $i => $name): ?>
        <div class="col-md-6"><label class="form-label"><?= e($name) ?> (JPG, PNG or PDF, max 5 MB)</label><input class="form-control" type="file" name="docs[<?= (int)$i ?>]" accept=".jpg,.jpeg,.png,.pdf" required></div>
      <?php endforeach; ?>
      <div class="col-12"><button class="btn btn-primary">Submit Application</button></div>
    </form>
  <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
<?php require 'includes/footer.php'; ?>
