<?php
chdir(dirname(__DIR__));

session_start();
require_once 'includes/functions.php';
requireRole(['System Administrator']);
$query = isset($_GET['enrollment_id']) ? '?enrollment_id=' . (int)$_GET['enrollment_id'] : '';
header('Location: ../registrar/registrar_requirements.php' . $query);
exit;
