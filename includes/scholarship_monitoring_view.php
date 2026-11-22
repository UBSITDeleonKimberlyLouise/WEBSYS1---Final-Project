<?php

$awards = $system->getAll("SELECT aw.*,ap.student_lrn,ap.decision_date,p.program_name,s.last_name,s.first_name,
  (SELECT e.grade_level FROM enrollments e WHERE e.student_lrn=ap.student_lrn ORDER BY e.school_year_id DESC,e.enrollment_id DESC LIMIT 1) AS grade_level,
  (SELECT e.final_result FROM enrollments e WHERE e.student_lrn=ap.student_lrn ORDER BY e.school_year_id DESC,e.enrollment_id DESC LIMIT 1) AS final_result,
  (SELECT ROUND(AVG(g.grade_value),2) FROM grades g WHERE g.enrollment_id=(SELECT e.enrollment_id FROM enrollments e WHERE e.student_lrn=ap.student_lrn ORDER BY e.school_year_id DESC,e.enrollment_id DESC LIMIT 1) AND g.grade_value IS NOT NULL) AS avg_grade
  FROM scholarship_awards aw JOIN scholarship_applications ap ON ap.application_id=aw.application_id JOIN scholarship_programs p ON p.program_id=ap.program_id JOIN students s ON s.student_lrn=ap.student_lrn ORDER BY aw.award_id DESC");
?>
<div class="card p-3">
  <h5>Scholarship Beneficiaries <span class="badge text-bg-secondary"><?= count($awards) ?></span></h5>
  <?php if (!$awards): ?><p class="text-muted mb-0">No approved scholarships yet. Approve an application to start monitoring.</p><?php endif; ?>
  <?php foreach ($awards as $aw):
    $dist = $system->getAll('SELECT * FROM assistance_distributions WHERE award_id=? ORDER BY distribution_date DESC,distribution_id DESC', [$aw['award_id']]);
  ?>
  <details class="border rounded p-3 mb-2">
    <summary class="d-flex flex-wrap justify-content-between gap-2">
      <span><strong><?= e($aw['last_name'] . ', ' . $aw['first_name']) ?></strong> <span class="small-note">LRN <?= e($aw['student_lrn']) ?> · <?= e($aw['grade_level'] ?? '') ?></span><br><span class="small-note"><?= e($aw['program_name']) ?></span></span>
      <span><span class="badge text-bg-primary"><?= e($aw['award_status']) ?></span> <span class="badge text-bg-<?= $aw['renewal_eligibility'] === 'Eligible' ? 'success' : ($aw['renewal_eligibility'] === 'Not Eligible' ? 'danger' : 'secondary') ?>">Renewal: <?= e($aw['renewal_eligibility']) ?></span></span>
    </summary>
    <div class="mt-3">
      <div class="row g-2 mb-2">
        <div class="col-md-4"><div class="small-note">Latest average grade</div><strong><?= $aw['avg_grade'] !== null ? e($aw['avg_grade']) : 'No grades recorded' ?></strong></div>
        <div class="col-md-4"><div class="small-note">Latest school-year result</div><strong><?= e($aw['final_result'] ?: 'Not yet recorded') ?></strong></div>
        <div class="col-md-4"><div class="small-note">Approved on</div><strong><?= e($aw['decision_date']) ?></strong></div>
      </div>
      <form method="POST" class="row g-2 mb-3">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="update_award"><input type="hidden" name="award_id" value="<?= (int)$aw['award_id'] ?>">
        <div class="col-md-3"><label class="form-label">Scholarship Status</label><select class="form-select" name="award_status"><?php foreach (['Active', 'For Renewal', 'Renewed', 'Completed', 'Discontinued'] as $s): ?><option <?= $aw['award_status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label class="form-label">Renewal Eligibility</label><select class="form-select" name="renewal_eligibility"><?php foreach (['Pending', 'Eligible', 'Not Eligible', 'Not Applicable'] as $s): ?><option <?= $aw['renewal_eligibility'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label class="form-label">Start Date</label><input class="form-control" type="date" name="award_start_date" value="<?= e($aw['award_start_date']) ?>"></div>
        <div class="col-md-3"><label class="form-label">End Date</label><input class="form-control" type="date" name="award_end_date" value="<?= e($aw['award_end_date']) ?>"></div>
        <div class="col-12"><label class="form-label">Academic Performance Record</label><textarea class="form-control" name="academic_performance_remarks" rows="2" maxlength="500"><?= e($aw['academic_performance_remarks']) ?></textarea></div>
        <div class="col-12"><button class="btn btn-primary btn-sm">Save Monitoring Record</button></div>
      </form>

      <h6>Assistance Distribution Records</h6>
      <?php if ($dist): ?><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Assistance</th><th>Quantity / Coverage</th><th>Remarks</th></tr></thead><tbody><?php foreach ($dist as $d): ?><tr><td><?= e($d['distribution_date']) ?></td><td><?= e($d['assistance_description']) ?></td><td><?= e($d['quantity_or_coverage']) ?></td><td><?= e($d['remarks']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="text-muted">No assistance recorded yet.</p><?php endif; ?>
      <form method="POST" class="row g-2">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="add_distribution"><input type="hidden" name="award_id" value="<?= (int)$aw['award_id'] ?>">
        <div class="col-md-2"><label class="form-label">Date</label><input class="form-control" type="date" name="distribution_date" value="<?= date('Y-m-d') ?>" required></div>
        <div class="col-md-4"><label class="form-label">Assistance</label><input class="form-control" name="assistance_description" maxlength="180" required></div>
        <div class="col-md-3"><label class="form-label">Quantity / Coverage</label><input class="form-control" name="quantity_or_coverage" maxlength="120"></div>
        <div class="col-md-3"><label class="form-label">Remarks</label><input class="form-control" name="remarks" maxlength="255"></div>
        <div class="col-12"><button class="btn btn-outline-primary btn-sm">Add Distribution Record</button></div>
      </form>
    </div>
  </details>
  <?php endforeach; ?>
</div>
