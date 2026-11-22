<?php
chdir(dirname(__DIR__));

session_start();
require_once dirname(__DIR__) . '/dbconn.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['School Registrar', 'System Administrator'], true)) {
    http_response_code(403);
    exit('Access denied.');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Invalid document ID.');
}

$stmt = $pdo->prepare('SELECT ed.file_path, e.student_lrn FROM enrollment_documents ed JOIN enrollments e ON e.enrollment_id = ed.enrollment_id WHERE ed.enrollment_document_id = ?');
$stmt->execute([$id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$doc || empty($doc['file_path'])) {
    http_response_code(404);
    exit('No uploaded file found for this document.');
}

$lrn = (string)$doc['student_lrn'];
if (!preg_match('/^[0-9]{12}$/', $lrn)) {
    http_response_code(404);
    exit('Invalid student document location.');
}

$base = realpath(dirname(__DIR__) . '/storage/student_documents/' . $lrn);
$file = realpath(dirname(__DIR__) . '/' . ltrim($doc['file_path'], '/'));
if (!$base || !$file || !is_file($file) || strpos($file, $base . DIRECTORY_SEPARATOR) !== 0) {
    http_response_code(404);
    exit('File not found on the server. The database may contain a sample filename without a physical upload.');
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file);
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], true)) {
    http_response_code(415);
    exit('Unsupported file type.');
}
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . str_replace(['"', "\r", "\n"], '', basename($file)) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($file);
exit;
