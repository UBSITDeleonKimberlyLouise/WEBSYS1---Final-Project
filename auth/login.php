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
    <style>
        body.login-page {
            background: linear-gradient(110deg, rgba(12,30,59,.38), rgba(12,30,59,.22)), url('../assets/eltnhsb.jpg') center center / cover no-repeat fixed;
            min-height: 100vh;
        }
        .login-page .login-container { max-width: 1200px; padding: 36px 18px; }
        .login-page .login-columns { align-items: stretch !important; }
        .login-page .login-announcements {
            background: rgba(239,243,248,.94); color: #34445a;
            border: 1px solid rgba(255,255,255,.55); border-top: 4px solid #21477c; border-radius: 18px;
            min-height: 250px; backdrop-filter: blur(8px);
        }
        .login-page .login-announcements .login-section-title,
        .login-page .login-announcements .announcement-item h5 { color: #17345d; }
        .login-page .login-announcements .text-muted { color: #536174 !important; }
        .login-page .login-announcements .announcement-item { border-color: rgba(30,55,90,.16) !important; overflow-wrap: anywhere; }
        .login-page .announcement-list { max-height: 330px; }
        .login-page .login-card { background: rgba(239,243,248,.94); border-radius: 18px; min-height: 0; border-top: 4px solid #b52839; }
        .login-page .login-card .school-logo { width: 105px; height: 105px; }
        .login-page .login-card .form-control { min-height: 44px; background: #f8fafc; }
        .login-page .login-card a { text-underline-offset: 3px; }
        .login-page .login-card .btn-primary { min-height: 44px; }
        @media (max-width: 991px) {
            body.login-page { background-attachment: scroll; }
            .login-page .login-container { padding: 24px 12px; }
            .login-page .login-announcements { min-height: 0; }
            .login-page .login-columns > div:last-child { order: -1; }
        }
    </style>
</head>
<body class="login-page">
    <div class="container login-container">
        <div class="row g-4 align-items-stretch justify-content-center login-columns">
            <div class="col-lg-7 d-flex">
                <section class="card p-4 p-md-5 w-100 login-announcements">
                    <h2 class="login-section-title">School Announcements</h2>
                    <p class="text-muted mb-4">Public school updates and enrollment notices</p>
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
                    <p class="text-muted mb-4">Enrollment, Student Records and Community Scholarship Management</p>
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
