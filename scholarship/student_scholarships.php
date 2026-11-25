<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'includes/functions.php';
require_once 'scholarship/scholarship_files.php';
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
            if ($program['available_slots'] !== null) {
                $slotCount = one($pdo, "SELECT COUNT(*) AS total FROM scholarship_awards aw JOIN scholarship_applications ap ON ap.application_id = aw.application_id WHERE ap.program_id = ?", [(int)$program['program_id']]);
                if ((int)$slotCount['total'] >= (int)$program['available_slots']) {
                    throw new Exception('This scholarship has no available slots.');
                }
            }
            if (!one($pdo, "SELECT e.enrollment_id FROM enrollments e JOIN school_years sy ON sy.school_year_id=e.school_year_id WHERE e.student_lrn=? AND e.enrollment_status='Officially Enrolled' AND sy.year_status='Current' LIMIT 1", [$lrn])) throw new Exception('Only officially enrolled students can apply for a scholarship.');
            if (one($pdo, 'SELECT application_id FROM scholarship_applications WHERE program_id=? AND student_lrn=?', [$program['program_id'], $lrn])) throw new Exception('You already applied for this scholarship.');
            $docNames = scholarshipDocNames($program['required_documents']);
            try {
                $uploads = scholarshipMatchUploads($docNames, $_FILES['docs'] ?? []);
                foreach ($uploads as $i => $file) $saved[$i] = scholarshipUpload($lrn, $file);
                $pdo->beginTransaction();
                $lock = $pdo->prepare('SELECT program_id FROM scholarship_programs WHERE program_id=? FOR UPDATE'); $lock->execute([$program['program_id']]);
                if (one($pdo, 'SELECT application_id FROM scholarship_applications WHERE program_id=? AND student_lrn=?', [$program['program_id'], $lrn])) throw new Exception('You already applied for this scholarship.');
                $pdo->prepare("INSERT INTO scholarship_applications (program_id,student_lrn,application_date,application_status) VALUES (?,?,CURDATE(),'Pending')")->execute([$program['program_id'], $lrn]);
                $appId = (int)$pdo->lastInsertId();
                $ins = $pdo->prepare("INSERT INTO scholarship_application_documents (application_id,document_name,file_path,verification_status) VALUES (?,?,?,'Pending')");
                foreach ($uploads as $i => $file) $ins->execute([$appId, mb_substr(basename(str_replace(chr(92), "/", $file['name'])), 0, 120), $saved[$i]]);
                $pdo->commit();
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                foreach ($saved as $path) scholarshipDeleteFile($path);
                throw $ex;
            }
            $msg = 'Application submitted. The Scholarship Coordinator will verify your documents.';
            try {
                $n = $pdo->prepare("INSERT INTO user_notifications (user_id,title,message,notification_type,related_url,is_read) VALUES (?,?,?,'Scholarship','../scholarship/scholarship.php?view=applications',0)");
                foreach ($pdo->query("SELECT user_id FROM users WHERE role='Scholarship Coordinator' AND account_status='Active'")->fetchAll(PDO::FETCH_COLUMN) as $cid) {
                    $n->execute([(int)$cid, 'New Scholarship Application', trim($student['first_name'] . ' ' . $student['last_name']) . ' (LRN: ' . $lrn . ') applied for ' . $program['program_name'] . '.']);
                }
            } catch (Throwable $ne) { error_log('Scholarship notification failed: ' . $ne->getMessage()); }
        } elseif ($action === 'replace_document') {
            $doc = one($pdo, 'SELECT d.*,a.application_status FROM scholarship_application_documents d JOIN scholarship_applications a ON a.application_id=d.application_id WHERE d.application_document_id=? AND a.student_lrn=?', [(int)($_POST['application_document_id'] ?? 0), $lrn]);
            if (!$doc) throw new Exception('Document not found.');
            if (scholarshipFinalStatus($doc['application_status']) || ($doc['file_path'] && $doc['verification_status'] !== 'Rejected') || $doc['verification_status'] === 'Accepted') throw new Exception('This document can no longer be replaced.');
            $path = scholarshipUpload($lrn, $_FILES['document'] ?? []);
            try {
                $update = $pdo->prepare("UPDATE scholarship_application_documents SET file_path=?,verification_status='Pending',remarks=NULL,verified_by=NULL,verified_at=NULL WHERE application_document_id=? AND (verification_status='Rejected' OR ((file_path IS NULL OR file_path='') AND verification_status<>'Accepted'))");
                $update->execute([$path, $doc['application_document_id']]);
                if (!$update->rowCount()) throw new Exception('This document can no longer be replaced.');
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
$programs = array_values(array_filter($programs, static function ($p) use ($applied) { return !isset($applied[$p['program_id']]); }));
$isEnrolled = (bool)one($pdo, "SELECT e.enrollment_id FROM enrollments e JOIN school_years sy ON sy.school_year_id=e.school_year_id WHERE e.student_lrn=? AND e.enrollment_status='Officially Enrolled' AND sy.year_status='Current' LIMIT 1", [$lrn]);
$today = date('Y-m-d');
$badge = function ($s) { return ['Approved' => 'success', 'Accepted' => 'success', 'Verified' => 'info', 'Rejected' => 'danger', 'Withdrawn' => 'dark', 'Submitted to Provider' => 'primary'][$s] ?? 'warning'; };
require 'includes/header.php';
?>
<?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

<style>
.sch-page {max-width:1100px;margin:0 auto;}
.sch-page .sch-card {padding:24px;margin-bottom:18px;}
.sch-page .sch-head {display:flex;justify-content:space-between;align-items:flex-start;gap:20px;flex-wrap:wrap;}
.sch-page .sch-head h5 {margin:0 0 5px;overflow-wrap:anywhere;}
.sch-page .sch-meta {font-size:13px;color:#64748b;line-height:1.7;}
.sch-page summary {cursor:pointer;color:#194581;padding:8px 0;}
.sch-page .sch-details {display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px;margin:20px 0;}
.sch-page .sch-detail {background:#f5f7fa;border:1px solid #e4e9ef;border-radius:8px;padding:16px;line-height:1.65;overflow-wrap:anywhere;}
.sch-page .sch-detail h6 {font-size:13px;font-weight:700;color:#42536a;margin:0 0 10px;}
.sch-page .sch-doc {display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:9px 0;}
.sch-page .sch-badge {font-size:11px;font-weight:600;padding:3px 7px;line-height:1.2;border-radius:4px;vertical-align:middle;}
.sch-page .sch-action-btn {font-size:12px;padding:5px 10px;line-height:1.4;border-radius:5px;}
.sch-page .sch-status {font-size:12px;color:#64748b;font-weight:400;}
.sch-page .sch-doc a {font-size:13px;text-decoration:none;margin-left:8px;}
.sch-page .sch-doc form {margin-top:6px;}
.sch-page .sch-upload {border-top:1px solid #e4e9ef;padding-top:20px;margin-top:20px;}
.sch-page .sch-reminder {background:#dce8f8;border-radius:6px;padding:12px 16px;font-size:13px;color:#1e3a5f;margin:12px 0;}
@media(max-width:850px){.sch-page .sch-details{grid-template-columns:1fr;gap:12px}.sch-page .sch-card{padding:16px}}
</style>
<div class="sch-page"><h2 class="mb-4">Scholarships</h2>
<?php if (!$isEnrolled): ?><div class="alert alert-warning">No official enrollment in the current school year. Please contact the Registrar.</div><?php endif; ?>
<details class="card sch-card" open><summary class="fw-bold mb-2">My Applications (<?=count($myApps)?>)</summary>
  <?php if (!$myApps): ?><p class="text-muted mb-0">You have not applied for any scholarship yet.</p><?php endif; ?>
  <?php foreach ($myApps as $a):
    $docs = many($pdo, 'SELECT * FROM scholarship_application_documents WHERE application_id=? ORDER BY application_document_id', [$a['application_id']]);
    $closed = scholarshipFinalStatus($a['application_status']);
  ?>
  <div class="border-bottom pb-3 mb-3">
    <div class="d-flex flex-wrap justify-content-between gap-2">
      <div><strong><?= e($a['program_name']) ?></strong><div class="small-note">Applied <?= e($a['application_date']) ?><?= $a['decision_date'] ? ' · Decision ' . e($a['decision_date']) : '' ?></div></div>
      <div><span class="badge sch-badge text-bg-<?= $badge($a['application_status']) ?>"><?= e($a['application_status']) ?></span></div>
    </div>
    <?php if ($a['remarks']): ?><p class="mb-2 mt-2"><span class="small-note">Remarks:</span> <?= e($a['remarks']) ?></p><?php endif; ?>
    <?php $uploaded = array_filter($docs, static function($d) { return !empty($d['file_path']); }); ?>

    <?php foreach ($docs as $d): ?>
      <?php if ($d['file_path'] && $d['verification_status'] !== 'Rejected') continue; ?>
      <div class="sch-doc">
        <div><?= e($d['document_name']) ?><?php if (!$d['file_path'] || $d['verification_status'] === 'Rejected'): ?> <span class="badge sch-badge text-bg-<?= $badge($d['verification_status']) ?>"><?= e($d['verification_status']) ?></span><?php endif; ?><?php if ($d['remarks']): ?><div class="small-note"><?= e($d['remarks']) ?></div><?php endif; ?></div>
        <div>
          <?php if (!$closed && $d['verification_status'] !== 'Accepted' && (!$d['file_path'] || $d['verification_status'] === 'Rejected') && $student['student_status'] === 'Active'): ?>
          <form method="post" enctype="multipart/form-data" class="d-flex gap-2">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="replace_document"><input type="hidden" name="application_document_id" value="<?= (int)$d['application_document_id'] ?>">
            <input class="form-control form-control-sm" type="file" name="document" accept=".jpg,.jpeg,.png,.pdf" required><button class="btn btn-sm btn-outline-primary">Upload document</button>
          </form><?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
    <div class="d-flex align-items-center flex-wrap gap-2 mt-3">
    <?php if ($uploaded): ?><div class="d-flex align-items-center gap-2 flex-wrap"><a class="btn btn-sm btn-outline-primary sch-action-btn" target="_blank" rel="noopener" href="scholarship_document.php?application_id=<?= (int)$a['application_id'] ?>">View Documents</a> <span class="sch-status"><?= count($uploaded) ?> uploaded</span></div><?php endif; ?>
    <?php if (in_array($a['application_status'], ['Pending', 'For Verification', 'Verified', 'Submitted to Provider'], true)): ?>
      <form method="post" class="m-0 ms-auto" onsubmit="return confirm('Withdraw this application?');"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="withdraw"><input type="hidden" name="application_id" value="<?= (int)$a['application_id'] ?>"><button class="btn btn-sm btn-outline-danger sch-action-btn">Withdraw</button></form>
    <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</details>

<h5 class="mb-2">Available Scholarships</h5>
<?php if (!$programs): ?><div class="card p-3"><p class="text-muted mb-0">No scholarships are open right now.</p></div><?php endif; ?>
<?php foreach ($programs as $p):
    $left = $p['available_slots'] === null ? null : max(0, (int)$p['available_slots'] - (int)$p['awarded']);
    $notStarted = $p['application_start'] && $p['application_start'] > $today;
    $ended = $p['application_deadline'] && $p['application_deadline'] < $today;
    $docNames = scholarshipDocNames($p['required_documents']);
?>
<div class="card sch-card">
  <div class="sch-head"><div><h5 class="mb-0"><?= e($p['program_name']) ?></h5><div class="small-note"><?= e($p['provider_name']) ?></div></div>
    <div class="sch-meta">Slots: <?= $left === null ? 'No limit' : e($left . ' of ' . $p['available_slots'] . ' left') ?><br>Application: <?= e(($p['application_start'] ?: '—') . ' to ' . ($p['application_deadline'] ?: '—')) ?></div></div>
  <details class="mt-2"><summary style="cursor:pointer;color:#194581">View details &amp; apply</summary>
  <?php if ($p['description']): ?><p class="mt-2 mb-2"><?= nl2br(e($p['description'])) ?></p><?php endif; ?>
  <div class="sch-details">
    <div class="sch-detail"><h6>Eligibility requirements</h6><?= nl2br(e($p['eligibility_criteria'] ?: 'Not specified')) ?></div>
    <div class="sch-detail"><h6>Scholarship benefits</h6><?= nl2br(e($p['benefits'] ?: 'Not specified')) ?></div>
    <div class="sch-detail"><h6>Supporting documents</h6><?= $docNames ? nl2br(e(implode("\n", $docNames))) : 'None required' ?></div>
  </div>
  <div class="mt-3">
  <?php if ($p['application_method'] === 'External Provider'): ?>
    <a class="btn btn-primary btn-sm" target="_blank" rel="noopener noreferrer" href="<?= e($p['external_application_url']) ?>">Apply on provider's website</a>
  <?php elseif (isset($applied[$p['program_id']])): ?>
    <span class="badge sch-badge text-bg-<?= $badge($applied[$p['program_id']]) ?>">Your application: <?= e($applied[$p['program_id']]) ?></span>
  <?php elseif ($left !== null && $left === 0): ?><span class="text-muted">No available scholarship slots remain.</span>
  <?php elseif ($notStarted): ?><span class="text-muted">Applications open on <?= e($p['application_start']) ?>.</span>
  <?php elseif ($ended): ?><span class="text-muted">The application period has ended.</span>
  <?php elseif (!$isEnrolled || $student['student_status'] !== 'Active'): ?><span class="text-muted">Application unavailable.</span>
  <?php else: ?>
    <form method="post" enctype="multipart/form-data" class="row g-3 sch-upload">
      <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="apply"><input type="hidden" name="program_id" value="<?= (int)$p['program_id'] ?>">
      <?php if ($docNames): ?>
        <div class="col-12"><label class="form-label fw-bold">Upload Required Documents</label>
        <input class="form-control" type="file" name="docs[]" accept=".jpg,.jpeg,.png,.pdf" multiple required>
        <div class="form-text">Select all required files together. Any order is accepted.</div>
        <div class="sch-reminder"><strong>Reminder:</strong> Please rename your files according to the required document names for easier verification.<br>
        Accepted formats: JPG, JPEG, PNG, or PDF (maximum 5 MB per file).<br>
        Make sure all required documents are included.</div></div>
      <?php endif; ?>
      <div class="col-12"><button class="btn btn-primary">Submit Application</button></div>
    </form>
  <?php endif; ?>
  </div>
  </details>
</div>
<?php endforeach; ?>
</div>
<?php require 'includes/footer.php'; ?>
