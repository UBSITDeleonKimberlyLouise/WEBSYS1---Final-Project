<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

require __DIR__ . '/../includes/header.php';
?>

<div class="card p-4">
    <h1>Physical Requirements Submission</h1>
    <p>Students submit required paper documents directly to the School Registrar. </p>
    <a class="btn btn-primary" href="portal.php">Return to Student Portal</a>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
