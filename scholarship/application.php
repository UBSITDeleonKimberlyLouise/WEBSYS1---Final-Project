<?php

session_start();
require_once '../config/dbconn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Student') {
    header("Location: ../login.php"); 
    exit();
}

$stmt_student = $pdo->prepare("SELECT student_lrn, first_name, last_name FROM students WHERE user_id = ?");
$stmt_student->execute([$_SESSION['user_id']]);
$student = $stmt_student->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student record not linked to active account.");
}

$student_lrn = $student['student_lrn'];
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Apply to School Managed Program

    if ($action === 'apply_school_managed') {
        $program_id = intval($_POST['program_id']);

        $stmt_check = $pdo->prepare("SELECT application_id FROM scholarship_applications WHERE program_id = ? AND student_lrn = ?");
        $stmt_check->execute([$program_id, $student_lrn]);
        
        if ($stmt_check->rowCount() > 0) {
            $message = "You have already submitted an application for this scholarship program.";
            $message_type = "warning";
        } else {
            $stmt_app = $pdo->prepare("INSERT INTO scholarship_applications (program_id, student_lrn, application_status, application_date) VALUES (?, ?, 'Pending', CURDATE())");
            if ($stmt_app->execute([$program_id, $student_lrn])) {
                $application_id = $pdo->lastInsertId();

                if (isset($_FILES['supporting_documents']) && !empty($_FILES['supporting_documents']['name'][0])) {
                    $upload_dir_fs = '../uploads/scholarships/';
                    if (!is_dir($upload_dir_fs)) {
                        mkdir($upload_dir_fs, 0755, true);
                    }

                    $total_files = count($_FILES['supporting_documents']['name']);
                    for ($i = 0; $i < $total_files; $i++) {
                        $file_name = $_FILES['supporting_documents']['name'][$i];
                        $tmp_name  = $_FILES['supporting_documents']['tmp_name'][$i];
                        $file_err  = $_FILES['supporting_documents']['error'][$i];

                        if ($file_err === UPLOAD_ERR_OK) {
                            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                            if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
                                $new_filename = 'scholarship_' . $application_id . '_' . time() . '_' . $i . '.' . $ext;
                                $target_path_fs = $upload_dir_fs . $new_filename;
                                $db_file_path   = 'uploads/scholarships/' . $new_filename;

                                if (move_uploaded_file($tmp_name, $target_path_fs)) {
                                    $stmt_doc = $pdo->prepare("INSERT INTO scholarship_application_documents 
                                        (application_id, document_name, file_path, verification_status) 
                                        VALUES (?, ?, ?, 'Pending')");
                                    $doc_label = "Supporting Document " . ($i + 1) . " (" . strtoupper($ext) . ")";
                                    $stmt_doc->execute([$application_id, $doc_label, $db_file_path]);
                                }
                            }
                        }
                    }
                }
                $message = "Application submitted successfully.";
                $message_type = "success";
            } else {
                $message = "Failed to submit application. Please try again.";
                $message_type = "danger";
            }
        }
    }

    // Record External Provider Application Intent

    elseif ($action === 'apply_external_record') {
        $program_id = intval($_POST['program_id']);

        $stmt_check = $pdo->prepare("SELECT application_id FROM scholarship_applications WHERE program_id = ? AND student_lrn = ?");
        $stmt_check->execute([$program_id, $student_lrn]);

        if ($stmt_check->rowCount() == 0) {
            $stmt_app = $pdo->prepare("INSERT INTO scholarship_applications (program_id, student_lrn, application_status, remarks, application_date) VALUES (?, ?, 'Submitted to Provider', 'Applied externally via provider link.', CURDATE())");
            $stmt_app->execute([$program_id, $student_lrn]);
            $message = "External application logged in your records.";
            $message_type = "info";
        }
    }

    // Withdraw Application

    elseif ($action === 'withdraw_application') {
        $application_id = intval($_POST['application_id']);

        $stmt = $pdo->prepare("UPDATE scholarship_applications 
            SET application_status = 'Withdrawn' 
            WHERE application_id = ? AND student_lrn = ? AND application_status IN ('Pending', 'For Verification')");
        if ($stmt->execute([$application_id, $student_lrn])) {
            $message = "Application successfully withdrawn.";
            $message_type = "info";
        }
    }
}

$published_programs = $pdo->query("SELECT sp.*, p.provider_name 
    FROM scholarship_programs sp 
    JOIN scholarship_providers p ON sp.provider_id = p.provider_id 
    WHERE sp.program_status = 'Published' 
    ORDER BY sp.application_deadline ASC")->fetchAll(PDO::FETCH_ASSOC);

$stmt_my_apps = $pdo->prepare("SELECT sa.*, sp.program_name, sp.application_method, p.provider_name 
    FROM scholarship_applications sa
    JOIN scholarship_programs sp ON sa.program_id = sp.program_id
    JOIN scholarship_providers p ON sp.provider_id = p.provider_id
    WHERE sa.student_lrn = ?
    ORDER BY sa.application_date DESC");
$stmt_my_apps->execute([$student_lrn]);
$my_applications = $stmt_my_apps->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container py-4">
    <div class="mb-4">
        <h2 class="h3 font-weight-bold text-gray-800">Scholarship Opportunities & Applications</h2>
        <p class="text-muted">LRN: <strong><?= htmlspecialchars($student_lrn) ?></strong> | Applicant: <strong><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></strong></p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-5">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Available Scholarship Opportunities</h5>
        </div>
        <div class="card-body">
            <?php if (empty($published_programs)): ?>
                <p class="text-muted text-center my-3">There are currently no published scholarship opportunities open for application.</p>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 g-4">
                    <?php foreach ($published_programs as $prog): ?>
                        <div class="col">
                            <div class="card h-100 border">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title text-primary mb-0"><?= htmlspecialchars($prog['program_name']) ?></h5>
                                        <span class="badge bg-info text-dark"><?= htmlspecialchars($prog['application_method']) ?></span>
                                    </div>
                                    <h6 class="card-subtitle mb-3 text-muted">Provided by: <?= htmlspecialchars($prog['provider_name']) ?></h6>
                                    
                                    <p class="card-text small mb-2"><strong>Description:</strong> <?= htmlspecialchars($prog['description']) ?></p>
                                    <p class="card-text small mb-2"><strong>Eligibility:</strong> <?= htmlspecialchars($prog['eligibility_criteria']) ?></p>
                                    <p class="card-text small mb-2"><strong>Benefits:</strong> <?= htmlspecialchars($prog['benefits']) ?></p>
                                    <p class="card-text small mb-2"><strong>Required Docs:</strong> <?= htmlspecialchars($prog['required_documents']) ?></p>
                                    <p class="card-text small mb-3"><strong>Deadline:</strong> <?= $prog['application_deadline'] ? htmlspecialchars($prog['application_deadline']) : 'Open' ?></p>
                                    
                                    <div class="mt-auto">
                                        <?php if ($prog['application_method'] === 'External Provider'): ?>
                                            <div class="d-grid gap-2">
                                                <a href="<?= htmlspecialchars($prog['external_application_url']) ?>" target="_blank" class="btn btn-outline-primary">
                                                    Open External Provider Portal
                                                </a>
                                                <form method="POST" action="application.php">
                                                    <input type="hidden" name="action" value="apply_external_record">
                                                    <input type="hidden" name="program_id" value="<?= $prog['program_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-link text-muted w-100">Record external submission in portal</button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#applyModal<?= $prog['program_id'] ?>">
                                                Apply Now (School Managed)
                                            </button>

                                            <div class="modal fade" id="applyModal<?= $prog['program_id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <form method="POST" action="application.php" enctype="multipart/form-data">
                                                            <input type="hidden" name="action" value="apply_school_managed">
                                                            <input type="hidden" name="program_id" value="<?= $prog['program_id'] ?>">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Apply for <?= htmlspecialchars($prog['program_name']) ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="alert alert-info py-2 small">
                                                                    <strong>Mandatory Documents:</strong> <?= htmlspecialchars($prog['required_documents']) ?>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold">Upload Supporting Files (PDF, JPG, PNG)</label>
                                                                    <input type="file" name="supporting_documents[]" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png" required>
                                                                    <div class="form-text">You may select multiple files holding shift/ctrl. Maximum size 5MB per file.</div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-success">Submit Application</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="card-title mb-0">My Scholarship Applications</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Program</th>
                            <th>Provider</th>
                            <th>Date Applied</th>
                            <th>Uploaded Credentials</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($my_applications)): ?>
                            <tr><td colspan="7" class="text-center py-3 text-muted">You have not submitted any scholarship applications.</td></tr>
                        <?php else: foreach ($my_applications as $m_app): 
                            $stmt_m_docs = $pdo->prepare("SELECT * FROM scholarship_application_documents WHERE application_id = ?");
                            $stmt_m_docs->execute([$m_app['application_id']]);
                            $m_docs = $stmt_m_docs->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($m_app['program_name']) ?></strong></td>
                                <td><?= htmlspecialchars($m_app['provider_name']) ?></td>
                                <td><?= date('M d, Y', strtotime($m_app['application_date'])) ?></td>
                                <td>
                                    <?php if (empty($m_docs)): ?>
                                        <span class="badge bg-secondary">Not Submitted</span>
                                    <?php else: ?>
                                        <ul class="list-unstyled mb-0 small">
                                            <?php foreach ($m_docs as $md): ?>
                                                <li>
                                                    <a href="../<?= htmlspecialchars($md['file_path']) ?>" target="_blank"><?= htmlspecialchars($md['document_name']) ?></a>
                                                    <span class="badge bg-<?= $md['verification_status'] === 'Accepted' ? 'success' : ($md['verification_status'] === 'Rejected' ? 'danger' : 'warning') ?> ms-1">
                                                        <?= $md['verification_status'] ?>
                                                    </span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $badge_colors = [
                                            'Pending' => 'warning',
                                            'For Verification' => 'info',
                                            'Verified' => 'primary',
                                            'Submitted to Provider' => 'secondary',
                                            'Approved' => 'success',
                                            'Rejected' => 'danger',
                                            'Withdrawn' => 'dark'
                                        ];
                                    ?>
                                    <span class="badge bg-<?= $badge_colors[$m_app['application_status']] ?? 'light text-dark' ?>">
                                        <?= $m_app['application_status'] ?>
                                    </span>
                                </td>
                                <td><small class="text-muted"><?= htmlspecialchars($m_app['remarks'] ?? 'None') ?></small></td>
                                <td>
                                    <?php if (in_array($m_app['application_status'], ['Pending', 'For Verification'])): ?>
                                        <form method="POST" action="application.php" onsubmit="return confirm('Are you sure you want to withdraw this application?');">
                                            <input type="hidden" name="action" value="withdraw_application">
                                            <input type="hidden" name="application_id" value="<?= $m_app['application_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Withdraw</button>
           
