
<?php

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
chdir(dirname(__DIR__));

session_start();

require_once 'dbconn.php';
require_once 'includes/functions.php';
require_once __DIR__ . '/../includes/name_password.php';
requireRole(['School Registrar', 'System Administrator']);

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    try {
        $pdo->beginTransaction();
        $id = (int) ($_POST['request_id'] ?? 0);

        $q = $pdo->prepare("
            SELECT
                r.request_id,
                u.user_id,
                u.role, u.account_status
            FROM password_reset_requests r
            JOIN users u ON u.user_id = r.user_id
            WHERE r.request_id = ?
            AND r.request_status = 'Pending'
            AND u.account_status = 'Active'
            FOR UPDATE
        ");

        $q->execute([$id]);
        $r = $q->fetch();

        if (!$r) {
            throw new Exception('Request is no longer pending.');
        }

        if ($r['role'] === 'System Administrator') {
            throw new Exception('Administrator password cannot be reset here.');
        }

        $temporaryPassword = registeredNamePassword($pdo, (int)$r['user_id']);

        $pdo->prepare("
            UPDATE users
            SET
                password_hash = ?,
                must_change_password = 1
            WHERE user_id = ?
        ")->execute([
            password_hash($temporaryPassword, PASSWORD_DEFAULT),
            $r['user_id']
        ]);

        $pdo->prepare("
            UPDATE password_reset_requests
            SET
                request_status = 'Completed',
                reviewed_by = ?,
                reviewed_at = NOW()
            WHERE request_id = ?
        ")->execute([
            $_SESSION['user_id'],
            $id
        ]);

        $pdo->prepare("
            INSERT INTO user_notifications (
                user_id,
                title,
                message,
                notification_type,
                related_url
            )
            VALUES (?, ?, ?, ?, ?)
        ")->execute([
            $r['user_id'],
            'Password reset completed',
            'Your password has been reset. Contact the authorized staff member for your temporary password.',
            'Password Reset',
            'login.php'
        ]);

        $pdo->commit();

        $msg = 'Password reset to the registered first and last name (lowercase, no spaces or punctuation). Verify identity before sharing. Password change is required.';

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if ($e instanceof PDOException) error_log('Password resets: ' . $e->getMessage());
        $err = $e instanceof PDOException ? 'Unable to process the password reset.' : $e->getMessage();
    }
}

$rows = $pdo->query("
    SELECT
        r.*,
        u.username,
        u.role,
        COALESCE(
            CONCAT_WS(' ', s.first_name, s.last_name),
            CONCAT_WS(' ', sp.first_name, sp.last_name)
        ) AS full_name,
        s.student_lrn
    FROM password_reset_requests r
    JOIN users u ON u.user_id = r.user_id
    LEFT JOIN students s ON s.user_id = u.user_id
    LEFT JOIN staff_profiles sp ON sp.user_id = u.user_id
    ORDER BY
        FIELD(r.request_status, 'Pending', 'Completed', 'Rejected'),
        r.requested_at DESC
")->fetchAll();

require 'includes/header.php';

?>

<div class="page-heading">
    <div>
        <h1>Password Reset Requests</h1>
        <p>Reset the password using the registered first and last name after verifying identity.</p>
    </div>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success">
        <?= e($msg) ?>
    </div>
<?php endif; ?>

<?php if ($err): ?>
    <div class="alert alert-danger">
        <?= e($err) ?>
    </div>
<?php endif; ?>

<div class="card p-3">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Username / LRN</th>
                    <th>Role</th>
                    <th>Requested</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($rows as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>

                        <td>
                            <?= e($r['full_name'] ?? '—') ?>
                        </td>

                        <td>
                            <?= e($r['student_lrn'] ?? $r['username']) ?>
                        </td>

                        <td>
                            <?= e($r['role']) ?>
                        </td>

                        <td>
                            <?= e($r['requested_at']) ?>
                        </td>

                        <td>
                            <?= e($r['request_status']) ?>
                        </td>

                        <td>
                            <?php if ($r['request_status'] === 'Pending'): ?>
                                <form
                                    method="post"
                                    onsubmit="return confirm('Reset to the registered first and last name? Verify identity first.')"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e(csrfToken()) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="request_id"
                                        value="<?= $r['request_id'] ?>"
                                    >

                                    <button class="btn btn-sm btn-primary">
                                        Reset Password
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
