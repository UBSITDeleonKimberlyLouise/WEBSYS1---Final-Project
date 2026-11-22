<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'classes/Auth.php';
require_once 'includes/functions.php';
requireLogin();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (strlen($new) < 8) $error = 'Password must be at least 8 characters.';
    elseif ($new !== $confirm) $error = 'Passwords do not match.';
    else {
        (new Auth($pdo))->changePassword($_SESSION['user_id'], $new);
        $_SESSION['must_change_password'] = 0;
        header('Location: ../shared/dashboard.php'); exit;
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Change Password</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="container py-5"><div class="card p-4 mx-auto" style="max-width:500px"><h3>Change your password</h3><p>Please create a new password before continuing.</p><?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><form method="POST"><label class="form-label">New password</label><input class="form-control mb-3" type="password" name="new_password" minlength="8" required><label class="form-label">Confirm password</label><input class="form-control mb-3" type="password" name="confirm_password" required><button class="btn btn-primary">Update password</button></form></div></body></html>
