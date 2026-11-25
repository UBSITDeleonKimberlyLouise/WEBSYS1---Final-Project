
<?php
chdir(dirname(__DIR__));

session_start();

require_once 'dbconn.php';
require_once 'includes/functions.php';
require_once 'classes/StudentFiles.php';

requireRole(['Student']);

$q = $pdo->prepare('SELECT * FROM students WHERE user_id = ?');
$q->execute([$_SESSION['user_id']]);
$student = $q->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    http_response_code(404);
    exit('Student profile not found.');
}

$lrn = (string) $student['student_lrn'];

$files = new StudentFiles($pdo);
$message = '';
$error = '';

$readOnly = in_array(
    $student['student_status'] ?? '',
    ['Graduated', 'Transferred', 'Dropped'],
    true
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    try {
        if ($readOnly) {
            throw new Exception('Your documents are read-only.');
        }

        $name = trim((string) ($_POST['document_name'] ?? ''));

        if (!in_array($name, $files->types(), true)) {
            throw new Exception('Invalid document type.');
        }

        $old = $pdo->prepare(
            'SELECT verification_status
             FROM student_files
             WHERE student_lrn = ? AND document_name = ?'
        );

        $old->execute([$lrn, $name]);
        $status = strtolower((string) $old->fetchColumn());

        if (in_array($status, ['accepted', 'verified', 'approved'], true)) {
            throw new Exception(
                'This document is already verified. Contact the Registrar before replacing it.'
            );
        }

        $files->save_file(
            $lrn,
            $name,
            $_FILES['document'] ?? [],
            (int) $_SESSION['user_id']
        );

        $message = 'Document submitted successfully. Waiting for Registrar review.';

        try {
            $registrars = $pdo->query(
                "SELECT user_id FROM users WHERE role = 'School Registrar'"
            );

            $notify = $pdo->prepare(
                'INSERT INTO user_notifications
                    (user_id, title, message, notification_type, related_url, is_read, created_at)
                 VALUES (?, ?, ?, ?, ?, 0, NOW())'
            );

            $studentName = trim(
                ($student['first_name'] ?? '') . ' ' .
                ($student['last_name'] ?? '')
            );

            foreach ($registrars->fetchAll(PDO::FETCH_COLUMN) as $uid) {
                $notify->execute([
                    (int) $uid,
                    'Student Document Submitted',
                    $studentName . ' (LRN: ' . $lrn . ') submitted ' . $name . '.',
                    'Document Submission',
                    'registration_review.php'
                ]);
            }
        } catch (Throwable $notificationError) {
            error_log(
                'Student document notification: ' .
                $notificationError->getMessage()
            );
        }
    } catch (Throwable $ex) {
        error_log('Student profile upload: ' . $ex->getMessage());

        $error = $ex instanceof PDOException
            ? 'Unable to save the document. Please contact the administrator.'
            : $ex->getMessage();
    }
}

$docs = $files->show_data($lrn);
$documentMap = [];

foreach ($docs as $doc) {
    $documentMap[$doc['document_name']] = $doc;
}

function studentDetail(array $student, array $possibleColumns): string
{
    foreach ($possibleColumns as $column) {
        if (
            array_key_exists($column, $student) &&
            trim((string) ($student[$column] ?? '')) !== ''
        ) {
            return (string) $student[$column];
        }
    }

    return '';
}

$profileFields = [
    'LRN' => $lrn,
    'Full Name' => trim(implode(' ', array_filter([
        $student['first_name'] ?? '',
        $student['middle_name'] ?? '',
        $student['last_name'] ?? '',
        $student['extension_name'] ?? ''
    ], static function ($v) {
        return trim((string) $v) !== '';
    }))),
    'Sex' => studentDetail($student, ['sex', 'gender']),
    'Birthdate' => studentDetail($student, ['birthdate', 'birth_date', 'date_of_birth']),
    'Student Status' => studentDetail($student, ['student_status'])
];

$addressFields = [
    'Address' => studentDetail(
        $student,
        ['complete_address', 'current_address', 'address', 'residential_address']
    ),
];

if ($addressFields['Address'] === '') {
    $parts = [];

    foreach ([
        ['house_no', 'house_number'],
        ['sitio_street', 'sitio', 'street'],
        ['barangay'],
        ['municipality', 'city'],
        ['province'],
        ['zip_code', 'postal_code']
    ] as $alternatives) {
        $part = studentDetail($student, $alternatives);

        if ($part !== '') {
            $parts[] = $part;
        }
    }

    $addressFields['Address'] = implode(', ', $parts);
}

$familyFields = [
    "Father's Name" => studentDetail(
        $student,
        ['father_name', 'father_full_name', 'fathers_name']
    ),
    "Father's Contact" => studentDetail(
        $student,
        ['father_contact', 'father_contact_number', 'father_phone', 'father_mobile']
    ),
    "Mother's Name" => studentDetail(
        $student,
        ['mother_name', 'mother_maiden_name', 'mother_full_name', 'mothers_name']
    ),
    "Mother's Contact" => studentDetail(
        $student,
        ['mother_contact', 'mother_contact_number', 'mother_phone', 'mother_mobile']
    ),
    "Guardian's Name" => studentDetail(
        $student,
        ['guardian_name', 'legal_guardian_name', 'guardian_full_name', 'primary_guardian']
    ),
    "Guardian's Contact" => studentDetail(
        $student,
        ['guardian_contact', 'guardian_contact_number', 'guardian_phone', 'guardian_mobile']
    ),
];

$fullName = trim(implode(' ', array_filter([
    $student['first_name'] ?? '',
    $student['middle_name'] ?? '',
    $student['last_name'] ?? '',
    $student['extension_name'] ?? ''
], static function ($v) {
    return trim((string) $v) !== '';
})));

$initial = strtoupper(substr((string) ($student['first_name'] ?? 'S'), 0, 1));

require 'includes/header.php';
?>

<style>
    .student-profile-card {
        border-radius: 16px;
    }

    .student-profile-avatar {
        width: 68px;
        height: 68px;
        border-radius: 50%;
        background: #2b7dc1;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 27px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .student-profile-label {
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 7px;
    }

    .student-profile-value {
        background: #f6f8fc;
        border: 1px solid #d7e1ee;
        border-radius: 10px;
        min-height: 52px;
        padding: 13px 15px;
        color: #26313e;
        overflow-wrap: anywhere;
    }

    .student-profile-section-title {
        font-size: 19px;
        font-weight: 700;
        color: #344255;
        margin-bottom: 20px;
    }

    .student-doc-card {
        border: 1px solid #dbe4f0;
        border-radius: 12px;
        padding: 20px;
        height: 100%;
    }

    @media (max-width: 576px) {
        .student-profile-avatar {
            width: 56px;
            height: 56px;
        }
    }
</style>

<div class="page-heading">
    <div>
        <h1>My Profile</h1>
        <p>Your student information and enrollment documents.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success">
        <?= e($message) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <?= e($error) ?>
    </div>
<?php endif; ?>

<div class="card p-4 mb-3 student-profile-card">
    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="student-profile-avatar">
            <?= e($initial) ?>
        </div>

        <div>
            <h3 class="mb-1" style="font-weight:700;color:#222b35;">
                <?= e($fullName ?: 'Student') ?>
            </h3>
            <div class="text-muted">Student information</div>
        </div>
    </div>

    <div class="row g-3">
        <?php foreach ($profileFields as $label => $value): ?>
            <div class="col-md-6 col-xl-4">
                <div class="student-profile-label">
                    <?= e($label) ?>
                </div>

                <div class="student-profile-value">
                    <?= trim((string) $value) !== ''
                        ? e((string) $value)
                        : '—' ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card p-4 mb-3 student-profile-card">
    <h5 class="student-profile-section-title">Address</h5>

    <?php foreach ($addressFields as $label => $value): ?>
        <div class="student-profile-label">
            <?= e($label) ?>
        </div>

        <div class="student-profile-value">
            <?= trim($value) !== '' ? e($value) : '—' ?>
        </div>
    <?php endforeach; ?>
</div>

<div class="card p-4 mb-3 student-profile-card">
    <h5 class="student-profile-section-title">
        Parent / Guardian Information
    </h5>

    <div class="row g-3">
        <?php foreach ($familyFields as $label => $value): ?>
            <div class="col-md-6 col-xl-4">
                <div class="student-profile-label">
                    <?= e($label) ?>
                </div>

                <div class="student-profile-value">
                    <?= trim($value) !== '' ? e($value) : '—' ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card p-4 mb-3 student-profile-card">
    <h5 class="student-profile-section-title mb-2">
        Document Requirements
    </h5>

    <p class="text-muted small mb-4">
        Preview submitted documents, review Registrar remarks, and upload or replace documents when permitted.
    </p>

    <div class="row g-3">
        <?php foreach ($files->types() as $type):
            $doc = $documentMap[$type] ?? null;
            $status = (string) ($doc['verification_status'] ?? 'Not Submitted');
            $statusLower = strtolower($status);

            $verified = in_array(
                $statusLower,
                ['accepted', 'verified', 'approved'],
                true
            );

            $rejected = in_array(
                $statusLower,
                ['rejected', 'declined'],
                true
            );

            $badge = $verified
                ? 'text-bg-success'
                : ($rejected
                    ? 'text-bg-danger'
                    : ($doc ? 'text-bg-warning' : 'text-bg-secondary'));
        ?>
            <div class="col-lg-6">
                <div class="student-doc-card">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3 flex-wrap">
                        <strong><?= e($type) ?></strong>
                        <span class="badge <?= $badge ?>">
                            <?= e($status) ?>
                        </span>
                    </div>

                    <?php if ($doc): ?>
                        <div class="student-profile-label">
                            Registrar Remarks
                        </div>

                        <div class="student-profile-value mb-3">
                            <?= e(trim((string) ($doc['remarks'] ?? '')) ?: 'No remarks yet.') ?>
                        </div>

                        <a
                            class="btn btn-sm btn-outline-primary mb-3"
                            href="../students/student_file.php?id=<?= (int) $doc['file_id'] ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Preview Document
                        </a>
                    <?php else: ?>
                        <p class="text-muted small">
                            No document uploaded yet.
                        </p>
                    <?php endif; ?>

                    <?php if (!$readOnly && !$verified): ?>
                        <form method="post" enctype="multipart/form-data">
                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(csrfToken()) ?>"
                            >

                            <input
                                type="hidden"
                                name="document_name"
                                value="<?= e($type) ?>"
                            >

                            <label class="student-profile-label">
                                <?= $doc ? 'Replace Document' : 'Upload Document' ?>
                            </label>

                            <input
                                type="file"
                                name="document"
                                class="form-control form-control-sm mb-2"
                                accept=".jpg,.jpeg,.png,.pdf"
                                required
                            >

                            <div class="small text-muted mb-2">
                                JPG, PNG or PDF · Maximum 5 MB
                            </div>

                            <button type="submit" class="btn btn-sm btn-primary">
                                <?= $doc ? 'Reupload' : 'Upload' ?>
                            </button>
                        </form>
                    <?php elseif ($verified): ?>
                        <p class="small text-success mb-0">
                            Verified by Registrar. Contact the Registrar if a replacement is needed.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
