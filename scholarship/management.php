
```php
<?php

session_start();
require_once '../config/dbconn.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['Scholarship Coordinator', 'System Administrator'])) {
    header("Location: ../login.php"); 
    exit();
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_provider') {
        $provider_name   = trim($_POST['provider_name']);
        $provider_type   = trim($_POST['provider_type']);
        $contact_person  = trim($_POST['contact_person']);
        $contact_number  = trim($_POST['contact_number']);
        $email           = trim($_POST['email']);
        $website_url     = trim($_POST['website_url']);
        $provider_status = $_POST['provider_status'] ?? 'Active';

        if (!empty($provider_name)) {
            $stmt = $pdo->prepare("INSERT INTO scholarship_providers 
                (provider_name, provider_type, contact_person, contact_number, email, website_url, provider_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$provider_name, $provider_type, $contact_person, $contact_number, $email, $website_url, $provider_status])) {
                $message = "Scholarship provider successfully registered.";
                $message_type = "success";
            } else {
                $message = "Failed to register scholarship provider.";
                $message_type = "danger";
            }
        } else {
            $message = "Provider name is required.";
            $message_type = "warning";
        }
    }

    elseif ($action === 'toggle_provider_status') {
        $provider_id = intval($_POST['provider_id']);
        $new_status  = $_POST['new_status'];

        $stmt = $pdo->prepare("UPDATE scholarship_providers SET provider_status = ? WHERE provider_id = ?");
        if ($stmt->execute([$new_status, $provider_id])) {
            $message = "Provider status updated to {$new_status}.";
            $message_type = "info";
        }
    }

    elseif ($action === 'create_program') {
        $provider_id              = intval($_POST['provider_id']);
        $program_name             = trim($_POST['program_name']);
        $description              = trim($_POST['description']);
        $eligibility_criteria     = trim($_POST['eligibility_criteria']);
        $required_documents       = trim($_POST['required_documents']);
        $benefits                 = trim($_POST['benefits']);
        $available_slots          = intval($_POST['available_slots']);
        $application_start        = $_POST['application_start'];
        $application_deadline     = $_POST['application_deadline'];
        $application_method       = $_POST['application_method'];
        $external_application_url = trim($_POST['external_application_url']);
        $program_status           = $_POST['program_status'] ?? 'Draft';

        if (!empty($program_name) && $provider_id > 0) {
            $stmt = $pdo->prepare("INSERT INTO scholarship_programs 
                (provider_id, program_name, description, eligibility_criteria, required_documents, benefits, available_slots, application_start, application_deadline, application_method, external_application_url, program_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $params = [
                $provider_id, $program_name, $description, $eligibility_criteria, 
                $required_documents, $benefits, $available_slots, 
                !empty($application_start) ? $application_start : null, 
                !empty($application_deadline) ? $application_deadline : null, 
                $application_method, $external_application_url, $program_status
            ];
            if ($stmt->execute($params)) {
                $message = "Scholarship program created successfully.";
                $message_type = "success";
            } else {
                $message = "Error creating scholarship program.";
                $message_type = "danger";
            }
        } else {
            $message = "Please select a provider and enter a program name.";
            $message_type = "warning";
        }
    }

    elseif ($action === 'update_program_status') {
        $program_id = intval($_POST['program_id']);
        $status     = $_POST['program_status'];

        $stmt = $pdo->prepare("UPDATE scholarship_programs SET program_status = ? WHERE program_id = ?");
        if ($stmt->execute([$status, $program_id])) {
            $message = "Program status updated successfully.";
            $message_type = "info";
        }
    }

    elseif ($action === 'update_document_status') {
        $application_document_id = intval($_POST['application_document_id']);
        $verification_status     = $_POST['verification_status'];

        $stmt = $pdo->prepare("UPDATE scholarship_application_documents SET verification_status = ? WHERE application_document_id = ?");
        if ($stmt->execute([$verification_status, $application_document_id])) {
            $message = "Document verification status set to {$verification_status}.";
            $message_type = "info";
        }
    }

    elseif ($action === 'update_application_status') {
        $application_id     = intval($_POST['application_id']);
        $application_status = $_POST['application_status'];
        $decision_reference = trim($_POST['decision_reference']);
        $remarks            = trim($_POST['remarks']);
        $decision_date      = (!empty($_POST['decision_date'])) ? $_POST['decision_date'] : date('Y-m-d H:i:s');

        $stmt = $pdo->prepare("UPDATE scholarship_applications 
            SET application_status = ?, decision_reference = ?, remarks = ?, decision_date = ? 
            WHERE application_id = ?");
        if ($stmt->execute([$application_status, $decision_reference, $remarks, $decision_date, $application_id])) {
            $message = "Application record #{$application_id} updated.";
            $message_type = "success";
        } else {
            $message = "Failed to update application record.";
            $message_type = "danger";
        }
    }
}

$providers = $pdo->query("SELECT * FROM scholarship_providers ORDER BY provider_name ASC")->fetchAll(PDO::FETCH_ASSOC);

$programs  = $pdo->query("SELECT sp.*, p.provider_name 
    FROM scholarship_programs sp 
    JOIN scholarship_providers p ON sp.provider_id = p.provider_id 
    ORDER BY sp.program_id DESC")->fetchAll(PDO::FETCH_ASSOC);

$applications = $pdo->query("SELECT sa.*, sp.program_name, p.provider_name, 
    st.first_name, st.last_name, st.email AS student_email
    FROM scholarship_applications sa
    JOIN scholarship_programs sp ON sa.program_id = sp.program_id
    JOIN scholarship_providers p ON sp.provider_id = p.provider_id
    JOIN students st ON sa.student_lrn = st.student_lrn
    ORDER BY sa.application_date DESC")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 font-weight-bold text-gray-800">Scholarship Management System</h2>
        <span class="badge bg-primary fs-6">Role: <?= htmlspecialchars($_SESSION['user_role']) ?></span>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-4" id="scholarshipTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="providers-tab" data-bs-toggle="tab" data-bs-target="#providers" type="button" role="tab">Providers</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="programs-tab" data-bs-toggle="tab" data-bs-target="#programs" type="button" role="tab">Scholarship Programs</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="review-tab" data-bs-toggle="tab" data-bs-target="#review" type="button" role="tab">Application Review & Verification</button>
        </li>
    </ul>

    <div class="tab-content" id="scholarshipTabsContent">

        <div class="tab-pane fade show active" id="providers" role="tabpanel">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">Add Provider</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="management.php">
                                <input type="hidden" name="action" value="add_provider">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Provider Name</label>
                                    <input type="text" name="provider_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Provider Type</label>
                                    <input type="text" name="provider_type" class="form-control" placeholder="e.g., Government, Private, LGU, NGO">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Contact Person</label>
                                    <input type="text" name="contact_person" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Contact Number</label>
                                    <input type="text" name="contact_number" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Website URL</label>
                                    <input type="url" name="website_url" class="form-control" placeholder="https://">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="provider_status" class="form-select">
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Save Provider</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">Registered Scholarship Providers</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Provider</th>
                                            <th>Contact</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($providers)): ?>
                                            <tr><td colspan="5" class="text-center py-3 text-muted">No scholarship providers configured.</td></tr>
                                        <?php else: foreach ($providers as $prov): ?>
                                            <tr>
                                                <td><?= $prov['provider_id'] ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($prov['provider_name']) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($prov['provider_type']) ?></small>
                                                </td>
                                                <td>
                                                    <small>
                                                        Person: <?= htmlspecialchars($prov['contact_person'] ?? 'N/A') ?><br>
                                                        Email: <?= htmlspecialchars($prov['email'] ?? 'N/A') ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $prov['provider_status'] === 'Active' ? 'success' : 'secondary' ?>">
                                                        <?= $prov['provider_status'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <form method="POST" action="management.php" class="d-inline">
                                                        <input type="hidden" name="action" value="toggle_provider_status">
                                                        <input type="hidden" name="provider_id" value="<?= $prov['provider_id'] ?>">
                                                        <input type="hidden" name="new_status" value="<?= $prov['provider_status'] === 'Active' ? 'Inactive' : 'Active' ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-<?= $prov['provider_status'] === 'Active' ? 'warning' : 'success' ?>">
                                                            Mark <?= $prov['provider_status'] === 'Active' ? 'Inactive' : 'Active' ?>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="tab-pane fade" id="programs" role="tabpanel">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Create Scholarship Program</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="management.php">
                        <input type="hidden" name="action" value="create_program">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Provider</label>
                                <select name="provider_id" class="form-select" required>
                                    <option value="">-- Select Provider --</option>
                                    <?php foreach ($providers as $p): ?>
                                        <?php if ($p['provider_status'] === 'Active'): ?>
                                            <option value="<?= $p['provider_id'] ?>"><?= htmlspecialchars($p['provider_name']) ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Program Name</label>
                                <input type="text" name="program_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Eligibility Criteria</label>
                                <textarea name="eligibility_criteria" class="form-control" rows="3" placeholder="e.g., General Average >= 85%, Low-income background"></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Required Documents</label>
                                <textarea name="required_documents" class="form-control" rows="3" placeholder="e.g., Form 138, Certificate of Indigency, 2x2 Photo"></textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Benefits</label>
                                <input type="text" name="benefits" class="form-control" placeholder="e.g., Php 10,000 / Semester">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Available Slots</label>
                                <input type="number" name="available_slots" class="form-control" value="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Initial Program Status</label>
                                <select name="program_status" class="form-select">
                                    <option value="Draft">Draft</option>
                                    <option value="Published">Published</option>
                                    <option value="Closed">Closed</option>
                                    <option value="Archived">Archived</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Application Start</label>
                                <input type="date" name="application_start" class="form-control">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Application Deadline</label>
                                <input type="date" name="application_deadline" class="form-control">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Application Method</label>
                                <select name="application_method" class="form-select" id="appMethodSelect" onchange="toggleExternalUrlInput()">
                                    <option value="School Managed">School Managed</option>
                                    <option value="External Provider">External Provider</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3" id="externalUrlContainer" style="display:none;">
                                <label class="form-label">External URL</label>
                                <input type="url" name="external_application_url" class="form-control" placeholder="https://external-portal.com">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success">Create Scholarship Program</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Existing Scholarship Programs</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Program</th>
                                    <th>Provider</th>
                                    <th>Method</th>
                                    <th>Deadline</th>
                                    <th>Slots</th>
                                    <th>Status</th>
                                    <th>Update Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($programs)): ?>
                                    <tr><td colspan="7" class="text-center py-3 text-muted">No scholarship programs recorded.</td></tr>
                                <?php else: foreach ($programs as $prog): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($prog['program_name']) ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($prog['provider_name']) ?></td>
                                        <td>
                                            <span class="badge bg-outline-info text-dark border">
                                                <?= htmlspecialchars($prog['application_method']) ?>
                                            </span>
                                        </td>
                                        <td><?= $prog['application_deadline'] ? htmlspecialchars($prog['application_deadline']) : 'N/A' ?></td>
                                        <td><?= $prog['available_slots'] ?></td>
                                        <td>
                                            <?php
                                                $badge_map = [
                                                    'Draft' => 'secondary',
                                                    'Published' => 'success',
                                                    'Closed' => 'danger',
                                                    'Archived' => 'dark'
                                                ];
                                            ?>
                                            <span class="badge bg-<?= $badge_map[$prog['program_status']] ?? 'secondary' ?>">
                                                <?= $prog['program_status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" action="management.php" class="d-flex gap-1">
                                                <input type="hidden" name="action" value="update_program_status">
                                                <input type="hidden" name="program_id" value="<?= $prog['program_id'] ?>">
                                                <select name="program_status" class="form-select form-select-sm" style="width: auto;">
                                                    <?php foreach (['Draft', 'Published', 'Closed', 'Archived'] as $st): ?>
                                                        <option value="<?= $st ?>" <?= $prog['program_status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

       
        <div class="tab-pane fade" id="review" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Student Scholarship Applications</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>App #</th>
                                    <th>Student</th>
                                    <th>Program</th>
                                    <th>Applied On</th>
                                    <th>Status</th>
                                    <th>Submitted Documents</th>
                                    <th>Action / Management</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($applications)): ?>
                                    <tr><td colspan="7" class="text-center py-3 text-muted">No student applications submitted yet.</td></tr>
                                <?php else: foreach ($applications as $app): 
                                
                                    $stmt_docs = $pdo->prepare("SELECT * FROM scholarship_application_documents WHERE application_id = ?");
                                    $stmt_docs->execute([$app['application_id']]);
                                    $docs = $stmt_docs->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                    <tr>
                                        <td><strong>#<?= $app['application_id'] ?></strong></td>
                                        <td>
                                            <strong><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></strong><br>
                                            <small class="text-muted">LRN: <?= htmlspecialchars($app['student_lrn']) ?></small>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($app['program_name']) ?><br>
                                            <small class="text-muted"><?= htmlspecialchars($app['provider_name']) ?></small>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($app['application_date'])) ?></td>
                                        <td>
                                            <?php
                                                $status_colors = [
                                                    'Pending' => 'warning',
                                                    'For Verification' => 'info',
                                                    'Verified' => 'primary',
                                                    'Submitted to Provider' => 'secondary',
                                                    'Approved' => 'success',
                                                    'Rejected' => 'danger',
                                                    'Withdrawn' => 'dark'
                                                ];
                                            ?>
                                            <span class="badge bg-<?= $status_colors[$app['application_status']] ?? 'light text-dark' ?>">
                                                <?= $app['application_status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (empty($docs)): ?>
                                                <em class="text-muted small">No files attached</em>
                                            <?php else: ?>
                                                <ul class="list-unstyled mb-0 small">
                                                    <?php foreach ($docs as $d): ?>
                                                        <li class="mb-1 border-bottom pb-1">
                                                            <a href="../<?= htmlspecialchars($d['file_path']) ?>" target="_blank" class="fw-bold">
                                                                <?= htmlspecialchars($d['document_name'] ?? 'View File') ?>
                                                            </a>
                                                            <br>
                                                            Status: 
                                                            <span class="badge bg-<?= $d['verification_status'] === 'Accepted' ? 'success' : ($d['verification_status'] === 'Rejected' ? 'danger' : 'warning') ?>">
                                                                <?= $d['verification_status'] ?>
                                                            </span>
                                                            <form method="POST" action="management.php" class="d-inline-flex ms-1">
                                                                <input type="hidden" name="action" value="update_document_status">
                                                                <input type="hidden" name="application_document_id" value="<?= $d['application_document_id'] ?>">
                                                                <select name="verification_status" class="form-select form-select-sm py-0 px-1" style="font-size:0.75rem;" onchange="this.form.submit()">
                                                                    <option value="Pending" <?= $d['verification_status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                                    <option value="Accepted" <?= $d['verification_status'] === 'Accepted' ? 'selected' : '' ?>>Accepted</option>
                                                                    <option value="Rejected" <?= $d['verification_status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                                                </select>
                                                            </form>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#appModal<?= $app['application_id'] ?>">
                                                Update Status
                                            </button>

                                            <!-- Application Update Modal -->
                                            <div class="modal fade" id="appModal<?= $app['application_id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST" action="management.php">
                                                            <input type="hidden" name="action" value="update_application_status">
                                                            <input type="hidden" name="application_id" value="<?= $app['application_id'] ?>">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Application #<?= $app['application_id'] ?> — Lifecycle Update</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold">Overall Status</label>
                                                                    <select name="application_status" class="form-select">
                                                                        <?php 
                                                                        $all_statuses = ['Pending', 'For Verification', 'Verified', 'Submitted to Provider', 'Approved', 'Rejected', 'Withdrawn'];
                                                                        foreach ($all_statuses as $st_opt): 
                                                                        ?>
                                                                            <option value="<?= $st_opt ?>" <?= $app['application_status'] === $st_opt ? 'selected' : '' ?>><?= $st_opt ?></option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Decision / Reference No.</label>
                                                                    <input type="text" name="decision_reference" class="form-control" value="<?= htmlspecialchars($app['decision_reference'] ?? '') ?>" placeholder="e.g., REF-2026-091">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Decision Date</label>
                                                                    <input type="datetime-local" name="decision_date" class="form-control" value="<?= $app['decision_date'] ? date('Y-m-d\TH:i', strtotime($app['decision_date'])) : '' ?>">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Coordinator Remarks</label>
                                                                    <textarea name="remarks" class="form-control" rows="3"><?= htmlspecialchars($app['remarks'] ?? '') ?></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function toggleExternalUrlInput() {
    var method = document.getElementById('appMethodSelect').value;
    var container = document.getElementById('externalUrlContainer');
    if (method === 'External Provider') {
        container.style.display = 'block';
    } else {
        container.style.display = 'none';
    }
}
</script>

<?php include '../includes/footer.php'; ?>
