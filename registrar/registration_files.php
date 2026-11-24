<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'includes/functions.php';
require_once 'classes/StudentFiles.php';
requireRole(['School Registrar','System Administrator']);

$lrn = trim($_GET['lrn'] ?? $_POST['lrn'] ?? '');
$files = new StudentFiles($pdo);
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    try {
        if (!preg_match('/^[0-9]{12}$/', $lrn)) {
            throw new Exception('Enter a valid 12-digit LRN.');
        }
        $check = $pdo->prepare('SELECT COUNT(*) FROM student_registration_applications WHERE student_lrn = ?');
        $check->execute([$lrn]);
        if (!$check->fetchColumn()) {
            throw new Exception('No registration found for this LRN.');
        }

        if (isset($_POST['upload_file'])) {
            $files->save_file($lrn, $_POST['document_name'] ?? '', $_FILES['document'] ?? [], $_SESSION['user_id']);
            $msg = 'Document uploaded successfully.';
        } elseif (isset($_POST['verify_file'])) {
            $id = (int)($_POST['file_id'] ?? 0);
            $check = $pdo->prepare('SELECT COUNT(*) FROM student_files WHERE file_id = ? AND student_lrn = ?');
            $check->execute([$id, $lrn]);
            if (!$check->fetchColumn()) {
                throw new Exception('Document not found for this LRN.');
            }
            $files->update_status($id, $_POST['file_status'] ?? '', trim($_POST['file_note'] ?? ''), $_SESSION['user_id']);
            $msg = 'Document status saved.';
        }
    } catch (Throwable $e) {
        $err = $e instanceof PDOException ? 'Unable to save the document.' : $e->getMessage();
    }
}

$validLrn = (bool)preg_match('/^[0-9]{12}$/', $lrn);
$rows = $validLrn ? $files->show_data($lrn) : [];
$statusClasses = [
    'Pending' => 'text-bg-warning',
    'Accepted' => 'text-bg-success',
    'Rejected' => 'text-bg-danger',
];
require 'includes/header.php';
?>
<div class="page-heading">
    <div>
        <h1>Registration Documents</h1>
    </div>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="alert alert-danger"><?= e($err) ?></div>
<?php endif; ?>

<div class="card p-3 mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-md-9">
            <label class="form-label" for="student-lrn">Student LRN</label>
            <input class="form-control" id="student-lrn" name="lrn" maxlength="12"
                   inputmode="numeric" pattern="[0-9]{12}" value="<?= e($lrn) ?>"
                   placeholder="Enter the 12-digit LRN" required>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100">Search</button>
        </div>
    </form>
</div>

<?php if ($validLrn): ?>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="text-muted small">Student LRN: <strong><?= e($lrn) ?></strong></div>
        <span class="badge text-bg-secondary"><?= count($rows) ?> uploaded <?= count($rows) === 1 ? 'file' : 'files' ?></span>
    </div>

    <?php if (!$rows): ?>
        <div class="card p-4 mb-3 text-muted">No documents uploaded yet.</div>
    <?php else: ?>
        <div class="row g-3 mb-3">
            <?php foreach ($rows as $row):
                $status = (string)($row['verification_status'] ?? 'Pending');
                $statusClass = $statusClasses[$status] ?? 'text-bg-secondary';
            ?>
                <div class="col-12 col-xl-6">
                    <div class="card p-3 h-100">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                            <div>
                                <div class="text-muted small mb-1">Document type</div>
                                <span class="badge text-bg-primary" style="font-size:0.85rem;white-space:normal;text-align:left;">
                                    <?= e($row['document_name']) ?>
                                </span>
                            </div>
                            <span class="badge <?= e($statusClass) ?>"><?= e($status) ?></span>
                        </div>

                        <div class="mb-3">
                            <a class="btn btn-sm btn-outline-primary"
                               href="../students/student_file.php?id=<?= (int)$row['file_id'] ?>"
                               target="_blank" rel="noopener noreferrer">View / Preview File</a>
                        </div>

                        <form method="post" class="mt-auto">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="lrn" value="<?= e($lrn) ?>">
                            <input type="hidden" name="file_id" value="<?= (int)$row['file_id'] ?>">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label">Verification Status</label>
                                    <select class="form-select" name="file_status" required>
                                        <?php foreach (['Pending', 'Accepted', 'Rejected'] as $option): ?>
                                            <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>>
                                                <?= e($option) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Registrar Remarks</label>
                                    <input class="form-control" name="file_note"
                                           value="<?= e($row['remarks'] ?? '') ?>"
                                           placeholder="Optional remarks">
                                </div>
                                <div class="col-12 text-end mt-3">
                                    <button type="submit" class="btn btn-primary" name="verify_file" value="1">
                                        Save Verification
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card p-3 mb-3">
        <h5 class="mb-2">Upload or Replace a File</h5>
        <p class="text-muted small">For physical copies, the Registrar can take a photo and upload it here.</p>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="lrn" value="<?= e($lrn) ?>">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Document Type</label>
                    <select class="form-select" name="document_name" required>
                        <?php foreach ($files->types() as $type): ?>
                            <option value="<?= e($type) ?>"><?= e($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">JPG, PNG or PDF (max 5 MB)</label>
                    <input class="form-control" type="file" name="document"
                           accept=".jpg,.jpeg,.png,.pdf" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100" name="upload_file" value="1">Upload File</button>
                </div>
            </div>
        </form>
    </div>
<?php endif; ?>
<?php require 'includes/footer.php'; ?>
