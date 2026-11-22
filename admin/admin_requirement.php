<?php
chdir(dirname(__DIR__));

// Admin and Registrar use the same enrollment verification workflow.
session_start();
require_once 'includes/functions.php';
requireRole(['System Administrator']);
$query = isset($_GET['enrollment_id']) ? '?enrollment_id=' . (int)$_GET['enrollment_id'] : '';
header('Location: ../registrar/registrar_requirements.php' . $query);
exit;
