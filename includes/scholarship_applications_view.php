<?php
require_once 'includes/scholarship_files.php';

$statuses = ['Pending', 'For Verification', 'Verified', 'Submitted to Provider', 'Approved', 'Rejected', 'Withdrawn'];
$fStatus = in_array($_GET['status'] ?? '', $statuses, true) ? $_GET['status'] : '';
$fProgram = (int)($_GET['program_id'] ?? 0);
$sql = 'SELECT a.*,p.program_name,p.application_method,s.last_name,s.first_name,(SELECT e.grade_level FROM enrollments e WHERE e.student_lrn=a.student_lrn ORDER BY e.enrollment_id DESC LIMIT 1) AS grade_level FROM scholarship_applications a JOIN scholarship_programs p ON p.program_id=a.program_id JOIN students s ON s.student_lrn=a.student_lrn WHERE 1=1';
$params = [];
if ($fStatus !== '') { $sql .= ' AND a.application_status=?'; $params[] = $fStatus; }
if ($fProgram) { $sql .= ' AND a.program_id=?'; $params[] = $fProgram; }
$apps = $system->getAll($sql . ' ORDER BY a.application_id DESC LIMIT 300', $params);
$programList = $system->getAll('SELECT program_id,program_name FROM scholarship_programs ORDER BY program_name');
$badge = function ($s) { return ['Approved' => 'success', 'Verified' => 'info', 'Rejected' => 'danger', 'Withdrawn' => 'dark', 'Submitted to Provider' => 'primary'][$s] ?? 'warning'; };
?>
<form class="card p-3 mb-3" method="get"><input type="hidden" name="module" value="applications">
  <div class="row g-2 align-items-end">
    <div class="col-md-5"><label class="form-label">Scholarship</label><select class="form-select" name="program_id"><option value="">All scholarships</option><?php foreach ($programList as $pl): ?><option value="<?= (int)$pl['program_id'] ?>" <?= $fProgram === (int)$pl['program_id'] ? 'selected' : '' ?>><?= e($pl['program_name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status"><option value="">All statuses</option><?php foreach ($statuses as $st): ?><option <?= $fStatus === $st ? 'selected' : '' ?>><?= e($st) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3"><button class="btn btn-primary">Filter</button> <a class="btn btn-outline-secondary" href="?module=applications">Reset</a></div>
  </div>
</form>
<div class="card p-3">
  <h5>Student Applications <span class="badge text-bg-secondary"><?= count($apps) ?></span></h5>
  <?php if (!$apps): ?><p class="text-muted mb-0">No applications found.</p><?php endif; ?>
  <?php foreach ($apps as $a):
    $docs = $system->getAll('SELECT * FROM scholarship_application_documents WHERE application_id=? ORDER BY application_document_id', [$a['application_id']]);
    $closed = scholarshipFinalStatus($a['application_status']);
    $hasAward = (bool)$system->getOne('SELECT award_id FROM scholarship_awards WHERE application_id=?', [$a['application_id']]);
  ?>
  <details class="border rounded p-3 mb-2">
    <summary class="d-flex flex-wrap justify-content-between gap-2">
      <span><strong><?= e($a['last_name'] . ', ' . $a['first_name']) ?></strong> <span class="small-note">LRN <?= e($a['student_lrn']) ?> · <?= e($a['grade_level'] ?? 'No enrollment') ?></span><br><span class="small-note"><?= e($a['program_name']) ?> · Applied <?= e($a['application_date']) ?></span></span>
      <span><span class="badge text-bg-<?= $badge($a['application_status']) ?>"><?= e($a['application_status']) ?></span></span>
    </summary>
    <div class="mt-3">
      <h6>Supporting Documents</h6>
      <?php if (!$docs): ?><p class="text-muted">This scholarship does not require supporting documents.</p><?php endif; ?>
      <?php foreach ($docs as $d): ?>
        <form method="POST" class="row g-2 align-items-end border-bottom pb-2 mb-2">
          <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="verify_document"><input type="hidden" name="application_document_id" value="<?= (int)$d['application_document_id'] ?>">
          <div class="col-md-4"><strong><?= e($d['document_name']) ?></strong><br><?php if ($d['file_path']): ?><a target="_blank" rel="noopener" href="../shared/scholarship_file.php?id=<?= (int)$d['application_document_id'] ?>">View file</a><?php else: ?><span class="text-muted">Not submitted</span><?php endif; ?></div>
          <div class="col-md-3"><label class="form-label">Verification</label><select class="form-select" name="verification_status" <?= ($closed || !$d['file_path']) ? 'disabled' : '' ?>><?php foreach (['Pending', 'Accepted', 'Rejected'] as $vs): ?><option <?= $d['verification_status'] === $vs ? 'selected' : '' ?>><?= e($vs) ?></option><?php endforeach; ?></select></div>
          <div class="col-md-3"><label class="form-label">Remarks</label><input class="form-control" name="remarks" maxlength="255" value="<?= e($d['remarks']) ?>" <?= ($closed || !$d['file_path']) ? 'disabled' : '' ?>></div>
          <div class="col-md-2"><?php if (!$closed && $d['file_path']): ?><button class="btn btn-sm btn-outline-primary">Save</button><?php else: ?><span class="badge text-bg-secondary"><?= e($d['verification_status']) ?></span><?php endif; ?></div>
        </form>
      <?php endforeach; ?>

      <h6 class="mt-3">Decision</h6>
      <?php if ($hasAward): ?>
        <p class="mb-0 text-success">Approved on <?= e($a['decision_date']) ?><?= $a['decision_reference'] ? ' (Ref: ' . e($a['decision_reference']) . ')' : '' ?>. Continue under <a href="?module=monitoring">Scholarship Monitoring</a>.</p>
      <?php elseif ($a['application_status'] === 'Withdrawn'): ?>
        <p class="mb-0 text-muted">The student withdrew this application.</p>
      <?php else: ?>
        <form method="POST" class="row g-2">
          <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="update_application"><input type="hidden" name="application_id" value="<?= (int)$a['application_id'] ?>">
          <div class="col-md-3"><label class="form-label">Application Status</label><select class="form-select" name="application_status"><?php foreach (['Pending', 'For Verification', 'Verified', 'Submitted to Provider', 'Approved', 'Rejected'] as $st): ?><option <?= $a['application_status'] === $st ? 'selected' : '' ?>><?= e($st) ?></option><?php endforeach; ?></select></div>
          <div class="col-md-3"><label class="form-label">Decision Reference</label><input class="form-control" name="decision_reference" maxlength="100" value="<?= e($a['decision_reference']) ?>"></div>
          <div class="col-md-6"><label class="form-label">Remarks (required when rejecting)</label><input class="form-control" name="remarks" maxlength="500" value="<?= e($a['remarks']) ?>"></div>
          <div class="col-12"><button class="btn btn-primary btn-sm">Save Decision</button></div>
        </form>
      <?php endif; ?>
    </div>
  </details>
  <?php endforeach; ?>
</div>
