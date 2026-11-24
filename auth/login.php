<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'classes/Auth.php';
require_once 'includes/functions.php';

$auth = new Auth($pdo);
$error = '';
$newsQuery = $pdo->query("SELECT title, content, publish_date FROM announcements WHERE announcement_status='Published' AND visibility='Public' AND target_audience='All' AND publish_date<=NOW() AND (expiry_date IS NULL OR expiry_date>=NOW()) ORDER BY publish_date DESC LIMIT 10");
$news = $newsQuery->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($auth->login(trim($_POST['username'] ?? ''), $_POST['password'] ?? '')) {
        header('Location: ../shared/dashboard.php');
        exit;
    }
    $error = 'Incorrect username or password, or the account is inactive.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ELTNHS | School Portal Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="login-page">
    <div class="container login-container">
        <div class="row g-4 align-items-stretch justify-content-center login-columns">
            <div class="col-lg-7 d-flex">
                <section class="card p-4 p-md-5 w-100 login-announcements">
                    <h2 class="login-section-title">School Announcements</h2>
                    <div class="announcement-list">
                        <?php if (!$news): ?>
                            <p class="text-muted">No public announcements have been posted yet.</p>
                        <?php endif; ?>
                        <?php foreach ($news as $announcement): ?>
                            <article class="announcement-item py-3 border-bottom">
                                <div class="small text-muted mb-1"><?= e(date('M d, Y', strtotime($announcement['publish_date']))) ?></div>
                                <h5><?= e($announcement['title']) ?></h5>
                                <p class="mb-0"><?= nl2br(e($announcement['content'])) ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
            <div class="col-lg-5 d-flex">
                <section class="card p-4 p-md-5 w-100 login-card text-center">
                    <img src="../assets/school_logo.png" class="school-logo mx-auto mb-3" alt="ELTNHS school logo">
                    <h1 class="login-school-name">Eastern La Trinidad National High School</h1>
                    <?php if ($error): ?>
                        <div class="alert alert-danger text-start"><?= e($error) ?></div>
                    <?php endif; ?>
                    <form method="post" class="text-start">
                        <label class="form-label" for="username">Username</label>
                        <input class="form-control mb-3" id="username" name="username" autocomplete="username" required>
                        <label class="form-label" for="password">Password</label>
                        <input class="form-control mb-3" id="password" type="password" name="password" autocomplete="current-password" required>
                        <button class="btn btn-primary w-100" type="submit">Log in</button>
                    </form>
                    <div class="mt-4"><a href="../public/parent_register.php">Student Registration</a></div>
                    <div class="mt-2"><a href="../auth/request_password_reset.php">Forgot Password?</a></div>
                </section>
            </div>
        </div>
    </div>
</body>
</html>
