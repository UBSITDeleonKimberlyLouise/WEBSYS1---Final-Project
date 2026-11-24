<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'includes/functions.php';
requireRole(['Guidance Counselor']);
$stmt = $pdo->prepare('SELECT sp.*, u.username, u.role FROM staff_profiles sp JOIN users u ON u.user_id = sp.user_id WHERE sp.user_id = ? LIMIT 1');
$stmt->execute([$_SESSION['user_id']]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
$fullName = trim(($staff['first_name'] ?? '') . ' ' . ($staff['middle_name'] ?? '') . ' ' . ($staff['last_name'] ?? ''));
$display = static function ($value): string {
    $value = trim((string)($value ?? ''));
    return e($value !== '' ? $value : '—');
};
require 'includes/header.php';
?>
<div class="page-heading"><div><h1>My Profile</h1></div></div>
<div class="card p-4">
  <div class="d-flex align-items-center gap-3 mb-3">
    <div class="brand-mark" style="width:58px;height:58px;font-size:25px;border-radius:50%">G</div>
    <div><h4 class="mb-1"><?= $display($fullName !== '' ? $fullName : 'Guidance Counselor Profile') ?></h4><div class="small-note">Employee information</div></div>
  </div>
  <div class="row g-3">
    <div class="col-md-4"><span class="small-note">Employee Number</span><div><?= $display($staff['employee_number'] ?? null) ?></div></div>
    <div class="col-md-4"><span class="small-note">Role</span><div><?= $display($staff['role'] ?? $_SESSION['role']) ?></div></div>
    <div class="col-md-4"><span class="small-note">Username</span><div><?= $display($staff['username'] ?? $_SESSION['username'] ?? null) ?></div></div>
    <div class="col-md-6"><span class="small-note">Email</span><div><?= $display($staff['email'] ?? null) ?></div></div>
    <div class="col-md-6"><span class="small-note">Contact Number</span><div><?= $display($staff['contact_number'] ?? null) ?></div></div>
    <div class="col-12"><span class="small-note">Address</span><div><?= $display($staff['address'] ?? null) ?></div></div>
  </div>
</div>
<?php require 'includes/footer.php'; ?>
