<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'includes/functions.php';
requireLogin();
$id = (int)($_GET['id'] ?? 0);
$prep = $pdo->prepare('SELECT d.file_path, a.student_lrn FROM scholarship_application_documents d JOIN scholarship_applications a ON a.application_id = d.application_id WHERE d.application_document_id = ?');
$prep->execute([$id]);
$doc = $prep->fetch(PDO::FETCH_ASSOC);
if (!$doc || !$doc['file_path']) { http_response_code(404); exit('Document not found.'); }
$role = $_SESSION['role'] ?? '';
if ($role === 'Student') {
    $check = $pdo->prepare('SELECT COUNT(*) FROM students WHERE user_id = ? AND student_lrn = ?');
    $check->execute([$_SESSION['user_id'], $doc['student_lrn']]);
    if (!$check->fetchColumn()) { http_response_code(403); exit('Access denied.'); }
} elseif ($role !== 'Scholarship Coordinator') {
    http_response_code(403);
    exit('Access denied.');
}
$base = realpath(dirname(__DIR__) . '/storage/scholarship_documents/' . $doc['student_lrn']);
$file = realpath(dirname(__DIR__) . '/' . $doc['file_path']);
if (!$base || !$file || strpos($file, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($file)) { http_response_code(404); exit('File not found.'); }
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file);
if (!in_array($mime, ['image/jpeg', 'image/png', 'application/pdf'], true)) { http_response_code(415); exit('Unsupported file type.'); }
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="document.' . pathinfo($file, PATHINFO_EXTENSION) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($file);
exit;
