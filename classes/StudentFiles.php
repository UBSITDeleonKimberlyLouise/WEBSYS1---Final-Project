<?php
chdir(dirname(__DIR__));

class StudentFiles {
    private $pdo;

    function __construct($pdo) {
        $this->pdo = $pdo;
    }

    function types() {
        return ['PSA Birth Certificate', 'SF9 / Report Card', 'Certificate of Good Moral Character', 'Certificate of Transfer / Form 137', '2x2 ID Picture', 'Other Document'];
    }

    function show_data($lrn) {
        $sql = 'SELECT * FROM student_files WHERE student_lrn = :lrn ORDER BY document_name';
        $prep = $this->pdo->prepare($sql);
        $prep->execute([':lrn' => $lrn]);
        return $prep->fetchAll(PDO::FETCH_ASSOC);
    }

    function save_file($lrn, $name, $file, $by = null) {
        if (!preg_match('/^[0-9]{12}$/', $lrn)) throw new Exception('Invalid student LRN.');
        if (!in_array($name, $this->types(), true)) throw new Exception('Invalid document type.');
        if (!isset($file['error'])) throw new Exception('No document was received.');
        if ((int)$file['error'] !== UPLOAD_ERR_OK) {
            $messages=[UPLOAD_ERR_INI_SIZE=>'File exceeds the PHP upload_max_filesize setting.',UPLOAD_ERR_FORM_SIZE=>'File is too large.',UPLOAD_ERR_PARTIAL=>'File upload was interrupted.',UPLOAD_ERR_NO_FILE=>'Please select a file.',UPLOAD_ERR_NO_TMP_DIR=>'PHP temporary upload folder is missing.',UPLOAD_ERR_CANT_WRITE=>'PHP cannot write the uploaded file to disk.',UPLOAD_ERR_EXTENSION=>'A PHP extension blocked the upload.'];
            throw new Exception($messages[(int)$file['error']] ?? 'Upload failed.');
        }
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) throw new Exception('Invalid uploaded file.');
        if ($file['size'] < 1 || $file['size'] > 5 * 1024 * 1024) throw new Exception('Maximum file size is 5 MB.');
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
        if (!isset($allowed[$mime])) throw new Exception('Only JPG, PNG and PDF files are accepted.');
        $storageRoot = dirname(__DIR__) . '/storage/student_documents';
        if (!is_dir($storageRoot) && !@mkdir($storageRoot, 0775, true) && !is_dir($storageRoot)) {
            throw new Exception('Cannot create storage/student_documents. Give Apache/PHP write permission to the storage folder.');
        }
        if (!is_writable($storageRoot)) {
            throw new Exception('storage/student_documents is not writable by Apache/PHP. Fix its folder ownership or permissions.');
        }
        $dir = $storageRoot . '/' . $lrn;
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) throw new Exception('Unable to create the document folder.');
        if (!is_writable($dir)) {
            // Try to repair permissions only when the PHP process owns the directory.
            @chmod($dir, 0775);
            if (!is_writable($dir)) throw new Exception('Document folder is not writable by Apache/PHP. Fix folder ownership/permissions on the server (storage/student_documents).');
        }
        $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
        $path = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $path)) throw new Exception('PHP could not move the uploaded document into storage/student_documents. Check the folder permissions.');
        @chmod($path, 0644);
        $relative = 'storage/student_documents/' . $lrn . '/' . $filename;
        $oldPath = false;
        try {
        $prev = $this->pdo->prepare('SELECT file_path FROM student_files WHERE student_lrn = ? AND document_name = ?');
        $prev->execute([$lrn, $name]);
        $oldPath = $prev->fetchColumn();
            $sql = "INSERT INTO student_files (student_lrn, document_name, file_path, verification_status, uploaded_by)
                    VALUES (:lrn, :name, :path, 'Pending', :uid)
                    ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), verification_status = 'Pending',
                    remarks = NULL, verified_by = NULL, verified_at = NULL, uploaded_by = VALUES(uploaded_by), uploaded_at = CURRENT_TIMESTAMP";
            $prep = $this->pdo->prepare($sql);
            $prep->execute([':lrn' => $lrn, ':name' => $name, ':path' => $relative, ':uid' => $by]);
        } catch (Throwable $e) {
            @unlink($path);
            throw $e;
        }
        // Remove the old file only after the new database record is saved.
        if ($oldPath && $oldPath !== $relative) {
            $old = realpath(dirname(__DIR__) . '/' . $oldPath);
            $base = realpath(dirname(__DIR__) . '/storage/student_documents/' . $lrn);
            if ($old && $base && strpos($old, $base . DIRECTORY_SEPARATOR) === 0 && is_file($old)) @unlink($old);
        }
    }

    function update_status($id, $status, $note, $uid) {
        if (!in_array($status, ['Pending', 'Accepted', 'Rejected'], true)) throw new Exception('Invalid status.');
        $sql = 'UPDATE student_files SET verification_status = :status, remarks = :note,
                verified_by = :uid, verified_at = NOW() WHERE file_id = :id';
        $prep = $this->pdo->prepare($sql);
        $prep->execute([':status' => $status, ':note' => $note, ':uid' => $uid, ':id' => $id]);
    }
}
