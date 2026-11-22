<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'includes/functions.php';
requireRole(['Guidance Counselor']);

$userId = (int) $_SESSION['user_id'];
$message = '';
$error = '';
$categories = ['Enrollment', 'Scholarship', 'School', 'General'];
$audiences = ['All', 'Students', 'Teachers', 'Registrar', 'Subject Teachers', 'Teacher Advisers'];
$statuses = ['Draft', 'Published', 'Archived'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string) ($_POST['action'] ?? '');
    $id = filter_var($_POST['announcement_id'] ?? null, FILTER_VALIDATE_INT);
    try {
        if ($action === 'save') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $content = trim((string) ($_POST['content'] ?? ''));
            $category = (string) ($_POST['category'] ?? 'General');
            $audience = (string) ($_POST['audience'] ?? 'All');
            $status = (string) ($_POST['status'] ?? 'Draft');
            $publishRaw = (string) ($_POST['publish_date'] ?? '');
            $expiryRaw = (string) ($_POST['expiry_date'] ?? '');
            $publish = $publishRaw === '' ? new DateTimeImmutable() : DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $publishRaw);
            $expiry = $expiryRaw === '' ? null : DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $expiryRaw);
            if ($title === '' || mb_strlen($title) > 160 || $content === '' ||
                !in_array($category, $categories, true) || !in_array($audience, $audiences, true) ||
                !in_array($status, $statuses, true) || !$publish || ($expiryRaw !== '' && !$expiry) ||
                ($expiry && $expiry < $publish)) {
                throw new RuntimeException('Check the title, content, dates and selected options.');
            }
            $args = [$title, $content, $category, $audience, $publish->format('Y-m-d H:i:s'),
                $expiry ? $expiry->format('Y-m-d H:i:s') : null, $status];
            if ($id) {
                $stmt = $pdo->prepare('UPDATE announcements SET title=?, content=?, announcement_category=?, target_audience=?, publish_date=?, expiry_date=?, announcement_status=? WHERE announcement_id=? AND posted_by=?');
                $stmt->execute([...$args, $id, $userId]);
                if (!$stmt->rowCount()) {
                    $check = $pdo->prepare('SELECT 1 FROM announcements WHERE announcement_id=? AND posted_by=?');
                    $check->execute([$id, $userId]);
                    if (!$check->fetchColumn()) throw new RuntimeException('Announcement not found or not owned by you.');
                }
                $message = 'Announcement updated.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO announcements (title, content, announcement_category, target_audience, publish_date, expiry_date, announcement_status, posted_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([...$args, $userId]);
                $message = 'Announcement created.';
            }
        } elseif ($action === 'delete' && $id) {
            $stmt = $pdo->prepare('DELETE FROM announcements WHERE announcement_id=? AND posted_by=?');
            $stmt->execute([$id, $userId]);
            $message = $stmt->rowCount() ? 'Announcement deleted.' : 'No announcement was deleted.';
        } else {
            throw new RuntimeException('Invalid action.');
        }
    } catch (RuntimeException $ex) {
        $error = $ex->getMessage();
    } catch (Throwable $ex) {
        error_log($ex->getMessage());
        $error = 'Unable to save the announcement. Check database configuration or server error log.';
    }
}

$stmt = $pdo->prepare("SELECT * FROM announcements WHERE posted_by=? ORDER BY created_at DESC, announcement_id DESC");
$stmt->execute([$userId]);
$mine = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare("SELECT a.*, u.username AS author_name FROM announcements a LEFT JOIN users u ON u.user_id=a.posted_by WHERE a.announcement_status='Published' AND a.publish_date<=NOW() AND (a.expiry_date IS NULL OR a.expiry_date>=NOW()) AND (a.target_audience='All' OR a.target_audience='Teachers') ORDER BY a.publish_date DESC LIMIT 50");
$stmt->execute();
$visible = $stmt->fetchAll(PDO::FETCH_ASSOC);
require 'includes/header.php';
?>
<div class="page-heading"><div><h1>Announcements</h1><p>Post school announcements and manage your own posts.</p></div></div>
<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-warning"><?= e($error) ?></div><?php endif; ?>
<div class="card p-3 mb-3">
  <h5 class="mb-3">Create Announcement</h5>
  <form method="post" class="row g-3">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="action" value="save">
    <div class="col-12"><label class="form-label">Title</label><input class="form-control" name="title" maxlength="160" required></div>
    <div class="col-12"><label class="form-label">Content</label><textarea class="form-control" name="content" rows="3" required></textarea></div>
    <div class="col-md-4"><label class="form-label">Category</label><select class="form-select" name="category"><?php foreach ($categories as $v): ?><option value="<?= e($v) ?>"><?= e($v) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label">Audience</label><select class="form-select" name="audience"><?php foreach ($audiences as $v): ?><option value="<?= e($v) ?>"><?= e($v) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status"><option>Draft</option><option>Published</option></select></div>
    <div class="col-md-6"><label class="form-label">Publish Date</label><input type="datetime-local" class="form-control" name="publish_date" value="<?= date('Y-m-d\TH:i') ?>" required></div>
    <div class="col-md-6"><label class="form-label">Expiry Date (optional)</label><input type="datetime-local" class="form-control" name="expiry_date"></div>
    <div class="col-12"><button type="submit" class="btn btn-primary">Save Announcement</button></div>
  </form>
</div>
<div class="card p-3 mb-3"><h5>My Announcements</h5>
<?php if (!$mine): ?><p class="text-muted mb-0">You have not created any announcements.</p><?php endif; ?>
<?php foreach ($mine as $a): ?>
  <details class="border rounded p-3 mb-2">
    <summary class="fw-semibold"><?= e($a['title']) ?> <span class="text-muted fw-normal">· <?= e($a['announcement_status']) ?></span></summary>
    <form method="post" class="row g-3 mt-2">
      <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="announcement_id" value="<?= (int)$a['announcement_id'] ?>">
      <div class="col-12"><label class="form-label">Title</label><input class="form-control" name="title" maxlength="160" value="<?= e($a['title']) ?>" required></div>
      <div class="col-12"><label class="form-label">Content</label><textarea class="form-control" name="content" rows="3" required><?= e($a['content']) ?></textarea></div>
      <div class="col-md-4"><label class="form-label">Category</label><select class="form-select" name="category"><?php foreach ($categories as $v): ?><option <?= $a['announcement_category']===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label">Audience</label><select class="form-select" name="audience"><?php foreach ($audiences as $v): ?><option <?= $a['target_audience']===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status"><?php foreach ($statuses as $v): ?><option <?= $a['announcement_status']===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-6"><label class="form-label">Publish Date</label><input type="datetime-local" class="form-control" name="publish_date" value="<?= e(date('Y-m-d\TH:i', strtotime($a['publish_date']))) ?>" required></div>
      <div class="col-md-6"><label class="form-label">Expiry Date</label><input type="datetime-local" class="form-control" name="expiry_date" value="<?= $a['expiry_date'] ? e(date('Y-m-d\TH:i', strtotime($a['expiry_date']))) : '' ?>"></div>
      <div class="col-12 d-flex gap-2"><button class="btn btn-primary btn-sm">Save Changes</button></div>
    </form>
    <form method="post" class="mt-2" onsubmit="return confirm('Delete this announcement?');"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="announcement_id" value="<?= (int)$a['announcement_id'] ?>"><button class="btn btn-outline-danger btn-sm">Delete</button></form>
  </details>
<?php endforeach; ?></div>
<div class="card p-3 mb-3"><h5>Published Announcements</h5>
<?php if (!$visible): ?><p class="text-muted mb-0">No published announcements available.</p><?php endif; ?>
<?php foreach ($visible as $a): ?><div class="border-bottom py-3"><h6><?= e($a['title']) ?></h6><p class="mb-1"><?= nl2br(e($a['content'])) ?></p><div class="small text-muted"><?= e($a['announcement_category']) ?> · <?= e($a['publish_date']) ?> · <?= e($a['author_name'] ?? 'Staff') ?></div></div><?php endforeach; ?></div>
<?php require 'includes/footer.php'; ?>
