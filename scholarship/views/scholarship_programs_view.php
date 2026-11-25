<?php

$providers = $system->getAll('SELECT * FROM scholarship_providers ORDER BY provider_name');
$programs = $system->getAll('SELECT p.*,pr.provider_name,pr.provider_type,(SELECT COUNT(*) FROM scholarship_awards aw JOIN scholarship_applications ap ON ap.application_id=aw.application_id WHERE ap.program_id=p.program_id) AS awarded FROM scholarship_programs p JOIN scholarship_providers pr ON pr.provider_id=p.provider_id ORDER BY p.program_id DESC');
$editProvider = isset($_GET['edit_provider']) ? $system->getOne('SELECT * FROM scholarship_providers WHERE provider_id=?', [(int)$_GET['edit_provider']]) : null;
$editProgram = isset($_GET['edit_program']) ? $system->getOne('SELECT * FROM scholarship_programs WHERE program_id=?', [(int)$_GET['edit_program']]) : null;
$ep = $editProgram ?: [];
$pv = $editProvider ?: [];
$val = function ($arr, $key, $default = '') { return e($arr[$key] ?? $default); };
?>
<div class="card p-3 mb-3">
  <h5>Scholarship Programs</h5>
  <div class="table-responsive"><table class="table table-sm align-middle">
    <thead><tr><th>Scholarship</th><th>Provider</th><th>Eligibility</th><th>Benefits</th><th>Slots (left / total)</th><th>Application</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($programs as $p): ?>
      <tr>
        <td><strong><?= e($p['program_name']) ?></strong><div class="small-note"><?= e($p['description']) ?></div></td>
        <td><?= e($p['provider_name']) ?><div class="small-note"><?= e($p['provider_type']) ?></div></td>
        <td><?= nl2br(e($p['eligibility_criteria'])) ?></td>
        <td><?= nl2br(e($p['benefits'])) ?></td>
        <td><?= $p['available_slots'] === null ? 'No limit' : e(max(0, (int)$p['available_slots'] - (int)$p['awarded']) . ' / ' . $p['available_slots']) ?></td>
        <td><?= e($p['application_method']) ?><div class="small-note"><?= e(trim(($p['application_start'] ?: '—') . ' to ' . ($p['application_deadline'] ?: '—'))) ?></div></td>
        <td><span class="badge text-bg-<?= $p['program_status'] === 'Published' ? 'success' : ($p['program_status'] === 'Draft' ? 'secondary' : 'dark') ?>"><?= e($p['program_status']) ?></span></td>
        <td><a class="btn btn-sm btn-outline-primary" href="?module=programs&edit_program=<?= (int)$p['program_id'] ?>#program-form">Edit</a></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$programs): ?><tr><td colspan="8" class="text-muted text-center">No scholarship programs yet.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</div>

<div class="card p-3 mb-3" id="program-form">
  <h5><?= $editProgram ? 'Edit Scholarship Program' : 'Add Scholarship Program' ?></h5>
  <?php if (!$providers): ?><p class="text-muted mb-0">Add a scholarship provider below first.</p><?php else: ?>
  <form method="POST" class="row g-2">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="save_program">
    <?php if ($editProgram): ?><input type="hidden" name="program_id" value="<?= (int)$ep['program_id'] ?>"><?php endif; ?>
    <div class="col-md-6"><label class="form-label">Scholarship Name</label><input class="form-control" name="program_name" maxlength="160" value="<?= $val($ep, 'program_name') ?>" required></div>
    <div class="col-md-6"><label class="form-label">Provider</label><select class="form-select" name="provider_id" required><?php foreach ($providers as $pr): ?><option value="<?= (int)$pr['provider_id'] ?>" <?= (int)($ep['provider_id'] ?? 0) === (int)$pr['provider_id'] ? 'selected' : '' ?>><?= e($pr['provider_name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2"><?= $val($ep, 'description') ?></textarea></div>
    <div class="col-md-6"><label class="form-label">Eligibility Requirements</label><textarea class="form-control" name="eligibility_criteria" rows="3"><?= $val($ep, 'eligibility_criteria') ?></textarea></div>
    <div class="col-md-6"><label class="form-label">Scholarship Benefits</label><textarea class="form-control" name="benefits" rows="3"><?= $val($ep, 'benefits') ?></textarea></div>
    <div class="col-md-6"><label class="form-label">Supporting Documents Required (one per line)</label><textarea class="form-control" name="required_documents" rows="3"><?= $val($ep, 'required_documents') ?></textarea></div>
    <div class="col-md-2"><label class="form-label">Available Slots</label><input class="form-control" type="number" name="available_slots" min="0" max="65535" value="<?= $val($ep, 'available_slots') ?>"></div>
    <div class="col-md-2"><label class="form-label">Application Start</label><input class="form-control" type="date" name="application_start" value="<?= $val($ep, 'application_start') ?>"></div>
    <div class="col-md-2"><label class="form-label">Deadline</label><input class="form-control" type="date" name="application_deadline" value="<?= $val($ep, 'application_deadline') ?>"></div>
    <div class="col-md-4"><label class="form-label">Application Method</label><select class="form-select" name="application_method"><?php foreach (['School Managed', 'External Provider'] as $m): ?><option <?= ($ep['application_method'] ?? 'School Managed') === $m ? 'selected' : '' ?>><?= e($m) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-5"><label class="form-label">External Application Link (External Provider only)</label><input class="form-control" name="external_application_url" maxlength="255" placeholder="https://" value="<?= $val($ep, 'external_application_url') ?>"></div>
    <div class="col-md-3"><label class="form-label">Status</label><select class="form-select" name="program_status"><?php foreach (['Draft', 'Published', 'Closed', 'Archived'] as $s): ?><option <?= ($ep['program_status'] ?? 'Draft') === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
    <div class="col-12"><button class="btn btn-primary"><?= $editProgram ? 'Save Changes' : 'Add Program' ?></button><?php if ($editProgram): ?> <a class="btn btn-outline-secondary" href="?module=programs">Cancel</a><?php endif; ?></div>
  </form>
  <?php endif; ?>
</div>

<div class="card p-3 mb-3">
  <h5>Scholarship Providers</h5>
  <div class="table-responsive"><table class="table table-sm align-middle">
    <thead><tr><th>Provider</th><th>Type</th><th>Contact</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($providers as $pr): ?>
      <tr><td><?= e($pr['provider_name']) ?></td><td><?= e($pr['provider_type']) ?></td><td><?= e(trim(($pr['contact_person'] ?? '') . ' ' . ($pr['contact_number'] ?? '') . ' ' . ($pr['email'] ?? ''))) ?></td><td><?= e($pr['provider_status']) ?></td><td><a class="btn btn-sm btn-outline-primary" href="?module=programs&edit_provider=<?= (int)$pr['provider_id'] ?>#provider-form">Edit</a></td></tr>
    <?php endforeach; ?>
    <?php if (!$providers): ?><tr><td colspan="5" class="text-muted text-center">No providers yet.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
  <h6 class="mt-3" id="provider-form"><?= $editProvider ? 'Edit Provider' : 'Add Provider' ?></h6>
  <form method="POST" class="row g-2">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="save_provider">
    <?php if ($editProvider): ?><input type="hidden" name="provider_id" value="<?= (int)$pv['provider_id'] ?>"><?php endif; ?>
    <div class="col-md-5"><label class="form-label">Provider Name</label><input class="form-control" name="provider_name" maxlength="160" value="<?= $val($pv, 'provider_name') ?>" required></div>
    <div class="col-md-3"><label class="form-label">Type</label><input class="form-control" name="provider_type" maxlength="80" placeholder="e.g. Government, Private, Community" value="<?= $val($pv, 'provider_type') ?>"></div>
    <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="provider_status"><?php foreach (['Active', 'Inactive', 'Resigned'] as $s): ?><option <?= ($pv['provider_status'] ?? 'Active') === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3"><label class="form-label">Contact Person</label><input class="form-control" name="contact_person" maxlength="120" value="<?= $val($pv, 'contact_person') ?>"></div>
    <div class="col-md-3"><label class="form-label">Contact Number</label><input class="form-control" name="contact_number" maxlength="30" value="<?= $val($pv, 'contact_number') ?>"></div>
    <div class="col-md-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" maxlength="120" value="<?= $val($pv, 'email') ?>"></div>
    <div class="col-md-3"><label class="form-label">Website</label><input class="form-control" name="website_url" maxlength="255" placeholder="https://" value="<?= $val($pv, 'website_url') ?>"></div>
    <div class="col-12"><button class="btn btn-primary"><?= $editProvider ? 'Save Provider' : 'Add Provider' ?></button><?php if ($editProvider): ?> <a class="btn btn-outline-secondary" href="?module=programs">Cancel</a><?php endif; ?></div>
  </form>
</div>
