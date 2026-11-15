<?
require_once __DIR__ . '/../dbconn.php';
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('This setup page can only be opened from the local computer.');
}

$username = 'admin123';
$password = 'pass123';

$stmt = $pdo->prepare('SELECT user_id FROM users WHERE username = ? LIMIT 1');
$stmt->execute([$username]);

if ($stmt->fetch()) {
    $message = 'The admin123 account already exists. No changes were made.';
} else {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $insert = $pdo->prepare("INSERT INTO users (username, password_hash, role, account_status, must_change_password) VALUES (?, ?, 'System Administrator', 'Active', 1)");
    $insert->execute([$username, $hash]);
    $message = 'The demo administrator account was created successfully.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Demo Administrator</title>
</head>
<body class="container py-5">
    <div class="card p-4 mx-auto">
        <h3>Demo Administrator Setup</h3>
        <div class="alert alert-info"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <p>
            <strong>Username:</strong> admin123<br>
            <strong>Temporary password:</strong> pass123
        </p>
        <p>Log in at <code>http://localhost/eltnhs_project/</code>. You will be asked to change the password.</p>
        <p class="text-danger mb-0"><strong>Important:</strong> Delete <code>create_demo_admin.php</code> from your project folder now.</p>
    </div>
</body>
</html>