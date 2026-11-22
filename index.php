<?php
session_start();
$target = isset($_SESSION['user_id']) ? 'shared/dashboard.php' : 'auth/login.php';
header('Location: ' . $target, true, 302);
exit;
