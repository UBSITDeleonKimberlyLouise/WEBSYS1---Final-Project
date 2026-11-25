<?php

function scholarshipDocNames($text): array {
    $names = [];
    foreach (preg_split('/[\r\n,;]+/', (string)$text) as $line) {
        $line = mb_substr(trim($line), 0, 120);
        if ($line !== '' && !in_array($line, $names, true)) $names[] = $line;
    }
    return $names;
}

function scholarshipFinalStatus(string $status): bool {
    return in_array($status, ['Approved', 'Rejected', 'Withdrawn'], true);
}

function scholarshipUpload(string $lrn, array $file): string {
    if (!preg_match('/^[0-9]{12}$/', $lrn)) throw new Exception('Invalid student LRN.');
    if (!isset($file['error'])) throw new Exception('No document was received.');
    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        $messages = [UPLOAD_ERR_INI_SIZE => 'File exceeds the PHP upload_max_filesize setting.', UPLOAD_ERR_FORM_SIZE => 'File is too large.', UPLOAD_ERR_PARTIAL => 'File upload was interrupted.', UPLOAD_ERR_NO_FILE => 'Please select a file for every required document.', UPLOAD_ERR_NO_TMP_DIR => 'PHP temporary upload folder is missing.', UPLOAD_ERR_CANT_WRITE => 'PHP cannot write the uploaded file to disk.', UPLOAD_ERR_EXTENSION => 'A PHP extension blocked the upload.'];
        throw new Exception($messages[(int)$file['error']] ?? 'Upload failed.');
    }
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) throw new Exception('Invalid uploaded file.');
    if ($file['size'] < 1 || $file['size'] > 5 * 1024 * 1024) throw new Exception('Maximum file size is 5 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
    if (!isset($allowed[$mime])) throw new Exception('Only JPG, PNG and PDF files are accepted.');
    $root = dirname(__DIR__) . '/storage/scholarship_documents';
    $dir = $root . '/' . $lrn;
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) throw new Exception('Cannot create storage/scholarship_documents. Give Apache/PHP write permission to the storage folder.');
    if (!is_writable($dir)) throw new Exception('storage/scholarship_documents is not writable by Apache/PHP.');
    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) throw new Exception('PHP could not move the uploaded document into storage.');
    @chmod($dir . '/' . $filename, 0644);
    return 'storage/scholarship_documents/' . $lrn . '/' . $filename;
}

function scholarshipDeleteFile($relative): void {
    if (!$relative) return;
    $base = realpath(dirname(__DIR__) . '/storage/scholarship_documents');
    $file = realpath(dirname(__DIR__) . '/' . $relative);
    if ($base && $file && strpos($file, $base . DIRECTORY_SEPARATOR) === 0 && is_file($file)) @unlink($file);
}

function scholarshipFileKey(string $name): string {
    return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $name));
}
function scholarshipMatchUploads(array $names, array $files): array {
    $uploads = [];
    foreach (($files['name'] ?? []) as $i => $filename) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        $file = [];
        foreach (['name','type','tmp_name','error','size'] as $field) $file[$field] = $files[$field][$i] ?? null;
        $uploads[] = $file;
    }
    if (count($uploads) !== count($names)) throw new Exception('Please select exactly ' . count($names) . ' document(s). Any order is accepted.');
    return $uploads;
}
