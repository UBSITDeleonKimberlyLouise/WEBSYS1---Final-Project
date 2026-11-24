<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'includes/functions.php';
requireRole(['System Administrator']);

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $name = trim($_POST['school_year_name'] ?? '');
            if (!preg_match('/^\d{4}-\d{4}$/', $name)) {
                throw new Exception('Use the school year format YYYY-YYYY.');
            }
            $parts = explode('-', $name);
            if ((int)$parts[1] !== (int)$parts[0] + 1) {
                throw new Exception('The ending year must be the next year.');
            }
            $count = (int)$pdo->query("SELECT COUNT(*) FROM school_years WHERE year_status='Current'")->fetchColumn();
            $status = $count ? 'Completed' : 'Current';
            $stmt = $pdo->prepare('INSERT INTO school_years (school_year_name, year_status) VALUES (?, ?)');
            $stmt->execute([$name, $status]);
            $message = 'School year created. Set it to Current when the school is ready.';
        } elseif ($action === 'status') {
            $yearId = (int)($_POST['school_year_id'] ?? 0);
            $status = $_POST['year_status'] ?? '';
            if (!in_array($status, ['Current', 'Completed'], true)) {
                throw new Exception('Select Current or Completed.');
            }
            $pdo->beginTransaction();
            $lock = $pdo->prepare('SELECT school_year_id FROM school_years WHERE school_year_id=? FOR UPDATE');
            $lock->execute([$yearId]);
            if (!$lock->fetchColumn()) {
                throw new Exception('School year not found.');
            }
            if ($status === 'Current') {
                $other = $pdo->prepare("SELECT COUNT(*) FROM school_years WHERE year_status='Current' AND school_year_id<>?");
                $other->execute([$yearId]);
                if ((int)$other->fetchColumn() > 0) {
                    throw new Exception('Complete the existing Current school year first.');
                }
            }
            $save = $pdo->prepare('UPDATE school_years SET year_status=? WHERE school_year_id=?');
            $save->execute([$status, $yearId]);
            $pdo->commit();
            $message = 'School year updated. Completed records appear in Past Records / Archive.';
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e instanceof PDOException ? 'Unable to save. Check whether this school year already exists.' : $e->getMessage();
    }
}

$years = $pdo->query('SELECT school_year_id, school_year_name, year_status FROM school_years ORDER BY school_year_name DESC')->fetchAll();
require 'includes/header.php';
?>
<div class="page-heading">
    <div>
        <h1>School Year Management</h1>
    </div>
</div>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<div class="card p-4 mb-3">
    <h5>School Years</h5>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>School Year</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach ($years as $year): ?>
                    <tr>
                        <td><?= e($year['school_year_name']) ?></td>
                        <td><?= e($year['year_status']) ?></td>
                        <td>
                            <form method="post" class="d-flex gap-2 align-items-center flex-wrap">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="action" value="status">
                                <input type="hidden" name="school_year_id" value="<?= (int)$year['school_year_id'] ?>">
                                <select name="year_status" class="form-select form-select-sm" style="max-width:160px">
                                    <option value="Current" <?= $year['year_status']==='Current'?'selected':'' ?>>Current</option>
                                    <option value="Completed" <?= $year['year_status']==='Completed'?'selected':'' ?>>Completed</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-primary" onclick="return confirm('Save the school year status? Completed years will appear in the archive.')">Save Changes</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="card p-4">
    <h5>Add School Year</h5>
    <form method="post" class="row g-3 align-items-end">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="create">
        <div class="col-md-6">
            <label class="form-label" for="school_year_name">School Year</label>
            <input id="school_year_name" name="school_year_name" class="form-control" placeholder="2027-2028" pattern="[0-9]{4}-[0-9]{4}" required>
        </div>
        <div class="col-md-6"><button type="submit" class="btn btn-primary">Add School Year</button></div>
    </form>
    <p class="small-note mt-3 mb-0">A new school year is created as Completed when another year is Current. Finish the old year before activating the new one. This page does not delete or move student records.</p>
</div>
<?php require 'includes/footer.php'; ?>
