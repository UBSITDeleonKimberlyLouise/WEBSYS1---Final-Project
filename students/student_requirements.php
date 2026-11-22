<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'includes/functions.php';
require_once 'classes/StudentFiles.php';
requireRole(['Student']);

$prep = $pdo->prepare('SELECT student_lrn, student_status, first_name, last_name FROM students WHERE user_id = ?');
$prep->execute([$_SESSION['user_id']]);
$student = $prep->fetch(PDO::FETCH_ASSOC);
if (!$student) {
    http_response_code(404);
    exit('Student profile not found.');
}

$lrn = $student['student_lrn'];
$files = new StudentFiles($pdo);
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    try {
        if (in_array($student['student_status'], ['Graduated', 'Transferred', 'Dropped'], true)) {
            throw new Exception('Your records are read-only.');
        }

        $documentName = trim($_POST['document_name'] ?? '');
        $files->save_file($lrn, $documentName, $_FILES['document'] ?? [], $_SESSION['user_id']);
        $msg = 'Document uploaded. Waiting for Registrar verification.';

        // A notification is created only after the upload is successfully saved.
        // A notification failure must not incorrectly report that the saved upload failed.
        try {
            $registrars = $pdo->prepare("SELECT user_id FROM users WHERE role = ?");
            $registrars->execute(['School Registrar']);
            $recipientIds = $registrars->fetchAll(PDO::FETCH_COLUMN);

            $notify = $pdo->prepare(
                'INSERT INTO user_notifications (user_id, title, message, notification_type, related_url, is_read, created_at) ' .
                'VALUES (:user_id, :title, :message, :type, :url, 0, NOW())'
            );
            $studentName = trim($student['first_name'] . ' ' . $student['last_name']);
            foreach ($recipientIds as $recipientId) {
                $notify->execute([
                    ':user_id' => (int)$recipientId,
                    ':title' => 'Student Document Submitted',
                    ':message' => $studentName . ' (LRN: ' . $lrn . ') uploaded or replaced: ' . $documentName . '. Please review the submitted document.',
                    ':type' => 'Document Submission',
                    ':url' => 'registration_review.php',
                ]);
            }
        } catch (Throwable $notificationError) {
            error_log('Document notification failed: ' . $notificationError->getMessage());
        }
    } catch (Throwable $e) {
        $err = $e instanceof PDOException
            ? 'Unable to save document information in the database. Please check that the student_files table exists and its columns match database/migration_student_files.sql.'
            : $e->getMessage();
    }
}

$rows = $files->show_data($lrn);
require 'includes/header.php';
?>
<div class="page-heading"><div><h1>My Enrollment Documents</h1><p class="text-muted mb-0">View your submitted files and verification status.</p></div></div>
<?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

<?php if (!$rows): ?>
<div class="card p-4 mb-3"><p class="text-muted mb-0">No documents uploaded yet.</p></div>
<?php else: ?>
<div class="row g-3 mb-3">
<?php foreach ($rows as $row):
    $status = (string)($row['verification_status'] ?? 'Pending');
    $statusClass = in_array(strtolower($status), ['verified', 'approved', 'accepted'], true)
        ? 'text-bg-success'
        : (in_array(strtolower($status), ['rejected', 'declined'], true) ? 'text-bg-danger' : 'text-bg-warning');
    $documentType = (string)($row['document_name'] ?? 'Document');
?>
<div class="col-md-6">
    <div class="card p-3 h-100">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-3 flex-wrap">
            <div>
                <div class="text-muted small mb-1">Document type</div>
                <span class="badge text-bg-primary fs-6" style="white-space:normal;text-align:left;"><?= e($documentType) ?></span>
            </div>
            <span class="badge <?= $statusClass ?>"><?= e($status) ?></span>
        </div>
        <div class="small text-muted mb-1">Registrar remarks</div>
        <p class="mb-3"><?= e(trim((string)($row['remarks'] ?? '')) ?: 'No remarks yet.') ?></p>
        <div class="mt-auto">
            <a class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener" href="../students/student_file.php?id=<?= (int)$row['file_id'] ?>">View file</a>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php if (!in_array($student['student_status'], ['Graduated', 'Transferred', 'Dropped'], true)): ?>
<div class="card p-3">
    <h5>Upload or Replace Document</h5>
    <p>You can also submit a physical copy at school. The Registrar can upload it for you.</p>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <div class="row g-2">
            <div class="col-md-5">
                <label class="form-label">Document Type</label>
                <select class="form-select" name="document_name" required>
                    <?php foreach ($files->types() as $type): ?>
                        <option value="<?= e($type) ?>"><?= e($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">Choose JPG, PNG or PDF (max 5 MB)</label>
                <input class="form-control" type="file" name="document" accept=".jpg,.jpeg,.png,.pdf" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit">Upload</button>
            </div>
        </div>
    </form>
</div>
<?php endif; ?>
<?php require 'includes/footer.php'; ?>
