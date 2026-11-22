<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'includes/functions.php';
requireRole(['Scholarship Coordinator']);
$q = $pdo->prepare('SELECT u.username,u.role,s.* FROM users u LEFT JOIN staff_profiles s ON s.user_id=u.user_id WHERE u.user_id=?');
$q->execute([$_SESSION['user_id']]);
$row = $q->fetch();
require 'includes/header.php';
?>
<h2>My Profile</h2><div class="card p-4">
<h4><?= e(trim(($row['first_name']??'').' '.($row['last_name']??''))) ?></h4>
<p>Username: <?= e($row['username']) ?></p><p>Role: <?= e($row['role']) ?></p>
<p>Email: <?= e($row['email']??'—') ?></p><p>Contact: <?= e($row['contact_number']??'—') ?></p>
</div><?php require 'includes/footer.php'; ?>
