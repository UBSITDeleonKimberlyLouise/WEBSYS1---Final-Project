<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'includes/functions.php';
requireLogin();

$uid = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (isset($_POST['mark_all']) && $role !== 'Student') {
        $q = $pdo->prepare('UPDATE user_notifications SET is_read = 1 WHERE user_id = ?');
        $q->execute([$uid]);
    } elseif (isset($_POST['read_id']) && $role !== 'Student') {
        $q = $pdo->prepare('UPDATE user_notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?');
        $q->execute([(int)$_POST['read_id'], $uid]);
    }
    header('Location: ../shared/notifications.php');
    exit;
}

$rows = [];
if ($role === 'Student') {
    $q = $pdo->prepare("SELECT title, content AS message, 'Announcement' AS notification_type,
        publish_date AS created_at, 'modules.php?module=announcements' AS related_url,
        NULL AS notification_id, 1 AS is_read
        FROM announcements
        WHERE announcement_status='Published' AND publish_date<=NOW()
        AND (expiry_date IS NULL OR expiry_date>=NOW()) AND target_audience IN ('All','Students')
        UNION ALL
        SELECT p.program_name, CONCAT(COALESCE(p.description,''),' Deadline: ',COALESCE(p.application_deadline,'Not specified')),
        'Scholarship',p.created_at,'../students/student_scholarships.php',NULL,1
        FROM scholarship_programs p WHERE p.program_status='Published'
        ORDER BY created_at DESC LIMIT 50");
    $q->execute();
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);
} else {
    $q = $pdo->prepare('SELECT notification_id, title, message, notification_type, created_at, related_url, is_read FROM user_notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 40');
    $q->execute([$uid]);
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);

    $aud = $role === 'School Registrar' ? 'Registrar' : ($role === 'Subject Teacher' ? 'Subject Teachers' : ($role === 'Teacher Adviser' ? 'Teacher Advisers' : 'All'));
    $a = $pdo->prepare("SELECT title, content AS message, 'Announcement' AS notification_type,
        publish_date AS created_at, 'modules.php?module=announcements' AS related_url,
        NULL AS notification_id, 1 AS is_read
        FROM announcements WHERE announcement_status='Published' AND publish_date<=NOW()
        AND (expiry_date IS NULL OR expiry_date>=NOW())
        AND target_audience IN ('All','Teachers',?)
        ORDER BY publish_date DESC LIMIT 20");
    $a->execute([$aud]);
    $rows = array_merge($rows, $a->fetchAll(PDO::FETCH_ASSOC));
    usort($rows, static function ($a, $b) {
        return strcmp((string)$b['created_at'], (string)$a['created_at']);
    });
}
require 'includes/header.php';
?>
<div class="page-heading">
    <div><h1>Notifications</h1></div>
</div>
<?php if ($role === 'School Registrar'):
    $pending = (int)$pdo->query("SELECT COUNT(*) FROM password_reset_requests WHERE request_status='Pending'")->fetchColumn();
?>
<div class="card p-3 mb-3">
    <h5>Password Reset Requests <span class="badge text-bg-warning"><?= $pending ?></span></h5>
    <a href="../auth/password_resets.php">Open requests and reset student passwords</a>
</div>
<?php endif; ?>
<div class="card p-3">
    <div class="d-flex justify-content-between align-items-center gap-2">
        <h5>Recent Updates</h5>
        <?php if ($role !== 'Student'): ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <button class="btn btn-sm btn-outline-secondary" name="mark_all" value="1">Mark all read</button>
        </form>
        <?php endif; ?>
    </div>
    <?php if (!$rows): ?>
        <p class="text-muted mb-0">No notifications yet.</p>
    <?php else: ?>
        <?php foreach ($rows as $i => $n): ?>
        <div class="border-bottom py-3">
            <div class="d-flex justify-content-between align-items-center gap-2">
                <strong><?= ($i + 1) ?>. <?= e($n['title']) ?></strong>
                <?php if (!empty($n['notification_id']) && !(int)$n['is_read']): ?>
                    <span class="badge text-bg-primary">Unread</span>
                <?php endif; ?>
            </div>
            <p class="mb-1"><?= nl2br(e($n['message'])) ?></p>
            <small class="text-muted"><?= e($n['notification_type']) ?> · <?= e($n['created_at']) ?></small>
            <div class="d-flex align-items-center gap-3 mt-2">
                <?php if (!empty($n['related_url'])): ?>
                    <a href="<?= e($n['related_url']) ?>">Open</a>
                <?php endif; ?>
                <?php if (!empty($n['notification_id']) && !(int)$n['is_read']): ?>
                    <form method="post" class="mb-0">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <button class="btn btn-sm btn-outline-secondary" name="read_id" value="<?= (int)$n['notification_id'] ?>">Mark as read</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php require 'includes/footer.php'; ?>
