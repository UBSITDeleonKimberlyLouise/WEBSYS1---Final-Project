<?php
session_start();
require_once __DIR__ . '/../config/dbconn.php';

/*
 * Announcements module (Module 7).
 * Standalone for now because classes/Auth.php and authorization/login.php
 * are not implemented yet in this repo. Once login is in place, swap the
 * block below for the real session/role check and remove the role dropdown
 * (it should come from the logged-in user's session, not a form field).
 */
if (empty($_SESSION['role'])) {
    $_SESSION['role'] = 'Scholarship Coordinator'; // TEMP placeholder until login exists
}
$canCreate = in_array($_SESSION['role'], ['School Registrar', 'Scholarship Coordinator'], true);

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function csrfToken() { return $_SESSION['csrf_token']; }
function checkCsrf() {
    if (($_POST['csrf_token'] ?? '') !== $_SESSION['csrf_token']) {
        http_response_code(400);
        exit('Invalid request (CSRF check failed). Please go back and try again.');
    }
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canCreate) {
    checkCsrf();
    try {
        $publishDate = str_replace('T', ' ', $_POST['publish_date']);
        $expiryDate = !empty($_POST['expiry_date']) ? str_replace('T', ' ', $_POST['expiry_date']) : null;

        $stmt = $pdo->prepare(
            'INSERT INTO announcements
                (title, content, announcement_category, target_audience, publish_date, expiry_date, announcement_status, posted_by)
             VALUES (?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $_POST['title'],
            $_POST['content'],
            $_POST['announcement_category'],
            $_POST['target_audience'],
            $publishDate,
            $expiryDate,
            $_POST['announcement_status'],
            $_SESSION['user_id'] ?? null,
        ]);

        // Notify affected users immediately if published now or in the past.
        if ($_POST['announcement_status'] === 'Published' && strtotime($publishDate) <= time()) {
            $audienceMap = [
                'Students' => ['Student'],
                'Teachers' => ['Subject Teacher', 'Teacher Adviser'],
                'Registrar' => ['School Registrar'],
                'Subject Teachers' => ['Subject Teacher'],
                'Teacher Advisers' => ['Teacher Adviser'],
            ];
            $target = $_POST['target_audience'] ?? 'All';
            $roles = ($target === 'All')
                ? ['Student', 'Subject Teacher', 'Teacher Adviser', 'Scholarship Coordinator', 'School Registrar']
                : ($audienceMap[$target] ?? []);

            if ($roles) {
                $marks = implode(',', array_fill(0, count($roles), '?'));
                $recipients = $pdo->prepare("SELECT user_id FROM users WHERE account_status = 'Active' AND role IN ($marks)");
                $recipients->execute($roles);
                $ins = $pdo->prepare(
                    "INSERT INTO user_notifications (user_id, title, message, notification_type, related_url) VALUES (?,?,?,?,?)"
                );
                foreach ($recipients->fetchAll(PDO::FETCH_COLUMN) as $uid) {
                    $ins->execute([$uid, $_POST['title'], mb_substr($_POST['content'], 0, 500), 'Announcement', 'announcements/announcements.php']);
                }
            }
        }

        $message = 'Announcement saved.';
    } catch (PDOException $ex) {
        $error = 'Database error: ' . $ex->getMessage();
    }
}

$announcements = $pdo->query(
    "SELECT a.*, u.username AS posted_by_name
     FROM announcements a
     LEFT JOIN users u ON a.posted_by = u.user_id
     WHERE a.announcement_status = 'Published' AND a.publish_date <= NOW()
       AND (a.expiry_date IS NULL OR a.expiry_date >= NOW())
     ORDER BY a.publish_date DESC"
)->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Announcements</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">

<h1>Announcements</h1>
<p class="text-muted">Module 7 — enrollment, scholarship, and general school announcements.</p>

<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<?php if ($canCreate): ?>
<div class="card p-3 mb-3">
  <h5>Post a New Announcement</h5>
  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <label class="form-label">Title</label>
    <input class="form-control mb-2" name="title" required>
    <label class="form-label">Content</label>
    <textarea class="form-control mb-2" name="content" rows="3" required></textarea>
    <div class="row">
      <div class="col">
        <label class="form-label">Category</label>
        <select class="form-select mb-2" name="announcement_category">
          <?php foreach (['Enrollment','Scholarship','General'] as $v): ?><option><?= e($v) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col">
        <label class="form-label">Target Audience</label>
        <select class="form-select mb-2" name="target_audience">
          <?php foreach (['All','Students','Teachers','Registrar','Subject Teachers','Teacher Advisers'] as $v): ?><option><?= e($v) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="row">
      <div class="col">
        <label class="form-label">Publish Date</label>
        <input class="form-control mb-2" type="datetime-local" name="publish_date" value="<?= date('Y-m-d\TH:i') ?>" required>
      </div>
      <div class="col">
        <label class="form-label">Expiry Date (optional)</label>
        <input class="form-control mb-2" type="datetime-local" name="expiry_date">
      </div>
    </div>
    <label class="form-label">Status</label>
    <select class="form-select mb-2" name="announcement_status">
      <?php foreach (['Published','Draft'] as $v): ?><option><?= e($v) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-primary">Save Announcement</button>
  </form>
</div>
<?php endif; ?>

<div class="card p-3">
  <h5>Published Announcements</h5>
  <?php if (!$announcements): ?><p class="text-muted">No announcements to display yet.</p><?php endif; ?>
  <?php foreach ($announcements as $a): ?>
    <div class="border-bottom py-2">
      <h6 class="mb-1"><?= e($a['title']) ?> <span class="badge bg-secondary"><?= e($a['announcement_category']) ?></span></h6>
      <p class="mb-1"><?= nl2br(e($a['content'])) ?></p>
      <small class="text-muted">
        Audience: <?= e($a['target_audience']) ?> ·
        Posted: <?= e($a['publish_date']) ?>
        <?php if ($a['posted_by_name']): ?> by <?= e($a['posted_by_name']) ?><?php endif; ?>
      </small>
    </div>
  <?php endforeach; ?>
</div>

</body>
</html>
