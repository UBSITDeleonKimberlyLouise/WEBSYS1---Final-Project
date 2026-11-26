
<?php
chdir(dirname(__DIR__));

session_start();

require_once 'dbconn.php';
require_once 'includes/functions.php';
require_once 'classes/StudentFiles.php';

requireRole(['School Registrar', 'System Administrator']);

$msg = '';
$err = '';

$files = new StudentFiles($pdo);

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    (
        isset($_POST['upload_student_file']) ||
        isset($_POST['verify_student_file'])
    )
) {
    verifyCsrf();

    try {
        $eid = (int) ($_POST['enrollment_id'] ?? 0);

        $q = $pdo->prepare(
            'SELECT student_lrn
            FROM enrollments
            WHERE enrollment_id = ?'
        );

        $q->execute([$eid]);
        $lrn = $q->fetchColumn();

        if (!$lrn) {
            throw new Exception('Student enrollment not found.');
        }

        if (isset($_POST['upload_student_file'])) {
            $files->save_file(
                $lrn,
                $_POST['document_name'] ?? '',
                $_FILES['document'] ?? [],
                $_SESSION['user_id']
            );

            $msg = 'Document uploaded successfully. Verification reset to Pending.';

        } else {
            $id = (int) ($_POST['file_id'] ?? 0);

            $q = $pdo->prepare(
                'SELECT COUNT(*)
                FROM student_files
                WHERE file_id = ?
                  AND student_lrn = ?'
            );

            $q->execute([$id, $lrn]);

            if (!$q->fetchColumn()) {
                throw new Exception('Document does not belong to this student.');
            }

            $files->update_status(
                $id,
                $_POST['file_status'] ?? '',
                trim($_POST['file_note'] ?? ''),
                $_SESSION['user_id']
            );

            $msg = 'Document verification updated.';
        }

    } catch (Throwable $e) {
        $err = $e instanceof PDOException
            ? 'Unable to save document information: ' .
                ($e->errorInfo[2] ?? $e->getMessage())
            : $e->getMessage();
    }
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !isset($_POST['upload_student_file']) &&
    !isset($_POST['verify_student_file'])
) {
    verifyCsrf();

    $eid = (int) ($_POST['enrollment_id'] ?? 0);

    try {
        if (isset($_POST['add_requirement'])) {
            $name = trim($_POST['document_name'] ?? '');

            if (!$eid || $name === '') {
                throw new Exception('Choose an enrollment and enter a requirement name.');
            }

            $q = $pdo->prepare(
                'INSERT INTO enrollment_documents (
                    enrollment_id,
                    document_name,
                    is_required
                ) VALUES (?, ?, ?)'
            );

            $q->execute([
                $eid,
                $name,
                isset($_POST['is_required']) ? 1 : 0
            ]);

            $msg = 'Requirement added to the checklist.';

        } elseif (isset($_POST['save_all_requirements'])) {
            $statuses = $_POST['verification_status'] ?? [];
            $remarks = $_POST['remarks'] ?? [];

            $pdo->beginTransaction();

            $check = $pdo->prepare(
                'SELECT enrollment_document_id
                FROM enrollment_documents
                WHERE enrollment_document_id = ?
                  AND enrollment_id = ?'
            );

            $update = $pdo->prepare(
                'UPDATE enrollment_documents
                SET
                    verification_status = ?,
                    verified_by = ?,
                    verified_at = NOW(),
                    remarks = ?
                WHERE enrollment_document_id = ?
                  AND enrollment_id = ?'
            );

            foreach ($statuses as $docId => $status) {
                if (!in_array(
                    $status,
                    ['Not Submitted', 'Pending', 'Accepted', 'Rejected', 'Not Required'],
                    true
                )) {
                    throw new Exception('Invalid requirement status.');
                }

                $check->execute([(int) $docId, $eid]);

                if (!$check->fetch()) {
                    throw new Exception('A requirement does not belong to the selected enrollment.');
                }

                $update->execute([
                    $status,
                    $_SESSION['user_id'],
                    trim($remarks[$docId] ?? '') ?: null,
                    (int) $docId,
                    $eid
                ]);
            }

            $checkDocs = $pdo->prepare(
                'SELECT
                    document_name,
                    is_required,
                    verification_status
                FROM enrollment_documents
                WHERE enrollment_id = ?'
            );

            $checkDocs->execute([$eid]);
            $allDocs = $checkDocs->fetchAll(PDO::FETCH_ASSOC);

            if (!$allDocs) {
                throw new Exception('No checklist items configured.');
            }

            $fileCheck = $pdo->prepare(
                "SELECT COUNT(*)
                FROM student_files sf
                JOIN enrollments e
                    ON e.student_lrn = sf.student_lrn
                WHERE e.enrollment_id = ?
                  AND sf.document_name = ?
                  AND sf.verification_status = 'Accepted'"
            );

            $remaining = 0;

            foreach ($allDocs as $item) {
                if ($item['verification_status'] === 'Accepted') {
                    $fileCheck->execute([
                        $eid,
                        $item['document_name']
                    ]);

                    if (!(int) $fileCheck->fetchColumn()) {
                        throw new Exception(
                            'Upload and accept the file for: ' .
                            $item['document_name']
                        );
                    }
                }

                if (
                    (int) $item['is_required'] === 1 &&
                    $item['verification_status'] !== 'Accepted'
                ) {
                    $remaining++;
                }
            }

            $newStatus = 'Pending';

            $pdo->prepare(
                "UPDATE enrollments
                SET enrollment_status = ?
                WHERE enrollment_id = ?
                  AND enrollment_status IN (
                      'Pending',
                      'Incomplete Requirements',
                      'For Verification',
                      'Temporarily Enrolled',
                      'For Guidance Approval'
                  )"
            )->execute([$newStatus, $eid]);

            $pdo->commit();

            $msg = 'All requirements saved. Enrollment status: ' . $newStatus . '.';

        } elseif (isset($_POST['finalize_enrollment'])) {
            if (!$eid) {
                throw new Exception('Select a student enrollment first.');
            }

            $pdo->beginTransaction();

            $lock = $pdo->prepare(
                "SELECT
                    e.*,
                    cs.school_year_id AS section_year,
                    cs.grade_level AS section_grade
                FROM enrollments e
                LEFT JOIN class_sections cs
                    ON cs.section_id = e.section_id
                WHERE e.enrollment_id = ?
                FOR UPDATE"
            );

            $lock->execute([$eid]);
            $en = $lock->fetch(PDO::FETCH_ASSOC);

            if (!$en) {
                throw new Exception('Enrollment not found.');
            }

            if ($en['enrollment_status'] === 'Officially Enrolled') {
                throw new Exception('Student is already officially enrolled.');
            }

            if (in_array(
                $en['enrollment_status'],
                ['Withdrawn', 'Rejected', 'Completed'],
                true
            )) {
                throw new Exception('This enrollment cannot be finalized.');
            }

            if (
                empty($en['section_id']) ||
                (int) $en['section_year'] !== (int) $en['school_year_id'] ||
                $en['section_grade'] !== $en['grade_level']
            ) {
                throw new Exception('Assign a valid section for the same grade and school year first.');
            }

            if (
                $en['guidance_assessment_status'] !== 'Passed' ||
                $en['guidance_approval_status'] !== 'Approved'
            ) {
                throw new Exception('Guidance interview must be Passed and Approved first.');
            }

            $docStmt = $pdo->prepare(
                'SELECT
                    document_name,
                    verification_status
                FROM enrollment_documents
                WHERE enrollment_id = ?
                  AND is_required = 1'
            );

            $docStmt->execute([$eid]);
            $requiredDocs = $docStmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$requiredDocs) {
                throw new Exception('Required document checklist is not configured.');
            }

            $acceptedFile = $pdo->prepare(
                "SELECT COUNT(*)
                FROM student_files
                WHERE student_lrn = ?
                  AND document_name = ?
                  AND verification_status = 'Accepted'
                  AND file_path IS NOT NULL
                  AND file_path <> ''"
            );

            foreach ($requiredDocs as $doc) {
                if ($doc['verification_status'] !== 'Accepted') {
                    throw new Exception(
                        'Requirement not verified: ' . $doc['document_name']
                    );
                }

                $acceptedFile->execute([
                    $en['student_lrn'],
                    $doc['document_name']
                ]);

                if (!(int) $acceptedFile->fetchColumn()) {
                    throw new Exception(
                        'Verified upload missing for: ' . $doc['document_name']
                    );
                }
            }

            $pdo->prepare(
                "UPDATE enrollments
                SET
                    enrollment_status = 'Officially Enrolled',
                    processed_by = ?
                WHERE enrollment_id = ?"
            )->execute([
                $_SESSION['user_id'],
                $eid
            ]);

            $pdo->commit();

            $msg = 'Enrollment finalized: Officially Enrolled.';

        } elseif (isset($_POST['create_student_account'])) {
            $pdo->beginTransaction();

            $q = $pdo->prepare(
                'SELECT
                    e.enrollment_id,
                    e.student_lrn,
                    s.user_id
                FROM enrollments e
                JOIN students s
                    ON s.student_lrn = e.student_lrn
                WHERE e.enrollment_id = ?
                FOR UPDATE'
            );

            $q->execute([$eid]);
            $record = $q->fetch();

            if (!$record) {
                throw new Exception('Enrollment record not found.');
            }

            if (empty($record['user_id'])) {
                $username = $record['student_lrn'];

                $q = $pdo->prepare(
                    "INSERT INTO users (
                        username,
                        password_hash,
                        role,
                        must_change_password,
                        created_by
                    ) VALUES (?, ?, 'Student', 1, ?)"
                );

                $q->execute([
                    $username,
                    password_hash($username, PASSWORD_DEFAULT),
                    $_SESSION['user_id']
                ]);

                $newId = (int) $pdo->lastInsertId();

                $pdo->prepare(
                    'UPDATE students
                    SET user_id = ?
                    WHERE student_lrn = ?'
                )->execute([
                    $newId,
                    $username
                ]);

                $msg = 'Student account created. Username: ' .
                    $username .
                    ' | Temporary password: ' .
                    $username .
                    '. Give these credentials to the student securely. The student must change the password after first login.';

            } else {
                $msg = 'This student already has an account. The existing account was kept; no duplicate was created.';
            }

            $pdo->commit();
        }

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $err = $e instanceof PDOException
            ? 'Unable to save this action. Check whether this record already exists. Database message: ' .
                ($e->errorInfo[2] ?? $e->getMessage())
            : $e->getMessage();
    }
}

$enrollments = $pdo->query(
    "SELECT
        e.enrollment_id,
        e.student_lrn,
        e.grade_level,
        e.enrollment_status,
        e.section_id,
        sy.school_year_name,
        s.first_name,
        s.last_name,
        s.user_id,
        cs.section_name
    FROM enrollments e
    JOIN students s
        ON s.student_lrn = e.student_lrn
    JOIN school_years sy
        ON sy.school_year_id = e.school_year_id
    LEFT JOIN class_sections cs
        ON cs.section_id = e.section_id
    ORDER BY e.enrollment_id DESC"
)->fetchAll();

$selected = (int) (
    $_POST['enrollment_id'] ??
    $_GET['enrollment_id'] ??
    0
);

if (!$selected && !empty($_SESSION['enrollment_lrn'])) {
    $q = $pdo->prepare(
        'SELECT enrollment_id
        FROM enrollments
        WHERE student_lrn = ?
        ORDER BY enrollment_id DESC
        LIMIT 1'
    );

    $q->execute([trim($_SESSION['enrollment_lrn'])]);

    $selected = (int) ($q->fetchColumn() ?: 0);
}

$docs = [];
$selectedInfo = null;
$studentFiles = [];

if ($selected) {
    $q = $pdo->prepare(
        'SELECT
            e.*,
            s.*,
            s.user_id AS student_user_id,
            sy.school_year_name,
            cs.section_name
        FROM enrollments e
        JOIN students s
            ON s.student_lrn = e.student_lrn
        JOIN school_years sy
            ON sy.school_year_id = e.school_year_id
        LEFT JOIN class_sections cs
            ON cs.section_id = e.section_id
        WHERE e.enrollment_id = ?'
    );

    $q->execute([$selected]);
    $selectedInfo = $q->fetch();

    if ($selectedInfo) {
        $studentFiles = $files->show_data($selectedInfo['student_lrn']);
    }

    $defaults = [
        ['PSA Birth Certificate', 1],
        ['SF9 / Report Card', 1],
        ['Certificate of Good Moral Character', 1],
        [
            'Certificate of Transfer / Form 137',
            (($selectedInfo['enrollment_category'] ?? '') === 'Transferee' ? 1 : 0)
        ],
        ['2x2 ID Picture', 0]
    ];

    $existing = $pdo->prepare(
        'SELECT COUNT(*)
        FROM enrollment_documents
        WHERE enrollment_id = ?
          AND document_name = ?'
    );

    $ins = $pdo->prepare(
        'INSERT INTO enrollment_documents (
            enrollment_id,
            document_name,
            is_required
        ) VALUES (?, ?, ?)'
    );

    foreach ($defaults as [$dn, $required]) {
        $existing->execute([$selected, $dn]);

        if (!(int) $existing->fetchColumn()) {
            $ins->execute([$selected, $dn, $required]);
        }
    }

    $q = $pdo->prepare(
        'SELECT *
        FROM enrollment_documents
        WHERE enrollment_id = ?
        ORDER BY enrollment_document_id'
    );

    $q->execute([$selected]);
    $docs = $q->fetchAll();
}

if (isset($_GET['embedded']) && $_GET['embedded'] === '1') {
    echo '<!doctype html>
    <html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="../assets/style.css">
    </head>
    <body class="p-0" style="background:transparent">';
} else {
    require 'includes/header.php';
}
?>

<?php if (!isset($_GET['embedded']) || $_GET['embedded'] !== '1'): ?>
    <div class="mb-3">
        <h5 class="mb-1">Requirements Review &amp; Student Accounts</h5>
    </div>
<?php endif; ?>

<?php if ($msg): ?>
    <div class="alert alert-success">
        <?= e($msg) ?>
    </div>
<?php endif; ?>

<?php if ($err): ?>
    <div class="alert alert-danger">
        <?= e($err) ?>
    </div>
<?php endif; ?>

<div class="card p-3 mb-3">
    <form method="post">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrfToken()) ?>"
        >

        <label class="form-label">Select enrollment</label>

        <select
            class="form-select form-select-sm"
            name="enrollment_id"
            onchange="this.form.submit()"
        >
            <option value="">Choose student</option>

            <?php foreach ($enrollments as $en): ?>
                <option
                    value="<?= $en['enrollment_id'] ?>"
                    <?= $selected === (int) $en['enrollment_id'] ? 'selected' : '' ?>
                >
                    <?= e(
                        $en['last_name'] . ', ' .
                        $en['first_name'] . ' · ' .
                        $en['student_lrn'] . ' · ' .
                        $en['grade_level'] . ' · ' .
                        $en['school_year_name']
                    ) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if ($selectedInfo):

    $byName = [];

    foreach ($studentFiles as $f) {
        if (!empty($f['file_path'])) {
            $byName[$f['document_name']] = $f;
        }
    }

    $requiredDocs = array_values(
        array_filter(
            $docs,
            static fn($d) => (int) $d['is_required'] === 1
        )
    );

    $uploadedRequired = 0;

    foreach ($requiredDocs as $d) {
        if (isset($byName[$d['document_name']])) {
            $uploadedRequired++;
        }
    }

    $requiredTotal = count($requiredDocs);

    $uploadedPercent = $requiredTotal
        ? round($uploadedRequired * 100 / $requiredTotal)
        : 0;

    $guidancePassed =
        ($selectedInfo['guidance_assessment_status'] ?? '') === 'Passed' &&
        ($selectedInfo['guidance_approval_status'] ?? '') === 'Approved';

    $allAccepted = $requiredTotal > 0;

    foreach ($requiredDocs as $d) {
        $f = $byName[$d['document_name']] ?? null;

        if (
            $d['verification_status'] !== 'Accepted' ||
            !$f ||
            $f['verification_status'] !== 'Accepted'
        ) {
            $allAccepted = false;
        }
    }

    $canFinalize =
        $guidancePassed &&
        $allAccepted &&
        !empty($selectedInfo['section_id']) &&
        !in_array(
            $selectedInfo['enrollment_status'],
            ['Officially Enrolled', 'Withdrawn', 'Rejected', 'Completed'],
            true
        );
?>

<style>
    .ev-card {
        border: 1px solid #e6ebf0;
        border-radius: 14px;
        background: #fff;
        padding: 20px;
        margin-bottom: 16px;
        box-shadow: 0 2px 9px rgba(22, 37, 56, .035);
    }

    .ev-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 14px;
    }

    .ev-title {
        font-size: 1.15rem;
        font-weight: 700;
        margin: 0;
    }

    .ev-muted {
        font-size: .85rem;
        color: #697889;
    }

    .ev-mini {
        border: 1px solid #e6ebf0;
        border-radius: 12px;
        padding: 17px;
        height: 100%;
    }

    .ev-mini h6 {
        font-size: .92rem;
        font-weight: 700;
    }

    .ev-pill {
        display: inline-block;
        border-radius: 30px;
        padding: 5px 10px;
        background: #fff4db;
        color: #916500;
        font-size: .76rem;
        font-weight: 700;
    }

    .ev-pill.good {
        background: #e7f7ed;
        color: #17633d;
    }

    .ev-pill.bad {
        background: #fde9e9;
        color: #a62c2c;
    }

    .ev-progress {
        height: 9px;
        background: #e8edf2;
        border-radius: 10px;
        overflow: hidden;
    }

    .ev-progress > div {
        height: 100%;
        background: #27875c;
    }

    .ev-doc {
        border-top: 1px solid #e9edf1;
        padding: 14px 0;
    }

    .ev-doc:first-of-type {
        border-top: 0;
    }

    .ev-doc-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }

    .ev-doc-name {
        font-weight: 650;
        font-size: .92rem;
    }

    .ev-file {
        max-width: 240px;
        max-height: 170px;
        object-fit: contain;
    }

    .ev-detail summary {
        cursor: pointer;
        list-style: none;
    }

    .ev-detail summary::-webkit-details-marker {
        display: none;
    }

    .ev-detail[open] summary {
        margin-bottom: 12px;
    }

    .ev-section-note {
        font-size: .8rem;
        color: #718093;
    }
</style>

<div class="ev-card">
    <div class="ev-head">
        <div>
            <h4 class="ev-title">
                <?= e($selectedInfo['last_name'] . ', ' . $selectedInfo['first_name']) ?>
            </h4>

            <div class="ev-muted mt-1">
                LRN: <?= e($selectedInfo['student_lrn']) ?>
                · <?= e($selectedInfo['grade_level']) ?>
                · <?= e($selectedInfo['school_year_name']) ?>
                · <?= e($selectedInfo['section_name'] ?: 'No section assigned') ?>
            </div>
        </div>

        <span class="ev-pill <?= $selectedInfo['enrollment_status'] === 'Officially Enrolled' ? 'good' : '' ?>">
            <?= e($selectedInfo['enrollment_status']) ?>
        </span>
    </div>

    <details class="mt-3">
        <summary
            class="text-primary small fw-semibold"
            style="cursor: pointer;"
        >
            View student information
        </summary>

        <div class="row g-2 mt-2">
            <?php
            $profileFields = [
                'birth_date' => 'Birth Date',
                'sex' => 'Sex',
                'psa_birth_certificate_no' => 'PSA Birth Certificate No.',
                'place_of_birth' => 'Place of Birth',
                'religion' => 'Religion',
                'mother_tongue' => 'Mother Tongue',
                'current_house_no' => 'Current House No.',
                'current_street_sitio' => 'Current Street / Sitio',
                'current_barangay' => 'Current Barangay',
                'current_municipality' => 'Current Municipality',
                'current_province' => 'Current Province',
                'current_zip_code' => 'Current ZIP Code',
                'father_first_name' => 'Father First Name',
                'father_middle_name' => 'Father Middle Name',
                'father_last_name' => 'Father Last Name',
                'father_contact_number' => 'Father Contact',
                'mother_maiden_first_name' => 'Mother First Name',
                'mother_maiden_middle_name' => 'Mother Middle Name',
                'mother_maiden_last_name' => 'Mother Maiden Last Name',
                'mother_contact_number' => 'Mother Contact',
                'guardian_first_name' => 'Guardian First Name',
                'guardian_middle_name' => 'Guardian Middle Name',
                'guardian_last_name' => 'Guardian Last Name',
                'guardian_contact_number' => 'Guardian Contact'
            ];

            foreach ($profileFields as $field => $label):
            ?>
                <div class="col-md-4">
                    <small class="text-muted d-block">
                        <?= e($label) ?>
                    </small>

                    <strong>
                        <?= e($selectedInfo[$field] ?? '—') ?>
                    </strong>
                </div>
            <?php endforeach; ?>
        </div>
    </details>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="ev-mini">
            <h6>Guidance Interview</h6>

            <span class="ev-pill <?= $guidancePassed
                ? 'good'
                : (($selectedInfo['guidance_assessment_status'] ?? '') === 'Failed' ? 'bad' : '') ?>">
                <?= e($selectedInfo['guidance_assessment_status'] ?: 'Pending') ?>
            </span>

            <div class="ev-muted mt-2">
                Remarks:
                <?= e($selectedInfo['guidance_assessment_remarks'] ?: 'No remarks yet') ?>
            </div>

            <div class="ev-section-note mt-2">
                Result managed by Guidance Counselor
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="ev-mini">
            <h6>Student Account</h6>

            <?php if (!empty($selectedInfo['student_user_id'])): ?>
                <span class="ev-pill good">Created</span>

                <div class="ev-muted mt-2">
                    Student must change the password on first login.
                </div>

            <?php else: ?>
                <span class="ev-pill">Not created</span>

                <form
                    method="post"
                    class="mt-3"
                    onsubmit="return confirm('Create student account?');"
                >
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(csrfToken()) ?>"
                    >

                    <input
                        type="hidden"
                        name="enrollment_id"
                        value="<?= $selected ?>"
                    >

                    <button
                        class="btn btn-primary btn-sm"
                        name="create_student_account"
                        value="1"
                    >
                        Create Student Account
                    </button>
                </form>

                <div class="ev-section-note mt-2">
                    Username: student LRN · First-login password change required
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="ev-card">
    <div class="ev-head mb-2">
        <div>
            <h5 class="mb-1">Document Checklist</h5>

            <div class="ev-muted">
                Files submitted during registration appear automatically.
            </div>
        </div>

        <div class="text-end">
            <div style="font-size: 1.55rem; font-weight: 750; line-height: 1.1;">
                <?= $uploadedRequired ?>/<?= $requiredTotal ?>
            </div>

            <div class="ev-muted">Uploaded</div>
        </div>
    </div>

    <div class="ev-progress mb-2">
        <div style="width: <?= $uploadedPercent ?>%;"></div>
    </div>

    <div class="ev-muted mb-3">
        <?= $requiredTotal - $uploadedRequired ?> required document(s) missing
    </div>

    <?php foreach ($docs as $d):
        $file = $byName[$d['document_name']] ?? null;
        $docId = (int) $d['enrollment_document_id'];
    ?>
        <div class="ev-doc">
            <details class="ev-detail">
                <summary class="ev-doc-head">
                    <div>
                        <div class="ev-doc-name">
                            <?= e($d['document_name']) ?>

                            <span class="ev-section-note">
                                · <?= $d['is_required'] ? 'Required' : 'Optional' ?>
                            </span>
                        </div>

                        <div class="ev-section-note">
                            <?= $file
                                ? 'Uploaded · ' . e($file['verification_status'])
                                : 'Not uploaded' ?>
                        </div>
                    </div>

                    <span class="btn btn-sm btn-outline-secondary">
                        Manage
                    </span>
                </summary>

                <div class="row g-3">
                    <div class="col-lg-5">
                        <?php if ($file): ?>
                            <a
                                class="btn btn-sm btn-outline-primary mb-2"
                                href="../students/student_file.php?id=<?= (int) $file['file_id'] ?>"
                                target="_blank"
                                rel="noopener"
                            >
                                Preview document
                            </a>

                            <?php if (preg_match('/\.(jpe?g|png)$/i', $file['file_path'] ?? '')): ?>
                                <div>
                                    <img
                                        class="img-thumbnail ev-file"
                                        src="../students/student_file.php?id=<?= (int) $file['file_id'] ?>"
                                        alt="Document preview"
                                    >
                                </div>
                            <?php endif; ?>

                            <form method="post" class="mt-2">
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(csrfToken()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="enrollment_id"
                                    value="<?= $selected ?>"
                                >

                                <input
                                    type="hidden"
                                    name="file_id"
                                    value="<?= (int) $file['file_id'] ?>"
                                >

                                <label class="form-label small">
                                    File review
                                </label>

                                <select
                                    class="form-select form-select-sm mb-2"
                                    name="file_status"
                                >
                                    <?php foreach (['Pending', 'Accepted', 'Rejected'] as $status): ?>
                                        <option
                                            value="<?= $status ?>"
                                            <?= $file['verification_status'] === $status ? 'selected' : '' ?>
                                        >
                                            <?= $status ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <input
                                    class="form-control form-control-sm mb-2"
                                    name="file_note"
                                    maxlength="500"
                                    value="<?= e($file['remarks'] ?? '') ?>"
                                    placeholder="File remarks"
                                >

                                <button
                                    class="btn btn-sm btn-primary"
                                    name="verify_student_file"
                                    value="1"
                                >
                                    Save File Review
                                </button>
                            </form>

                        <?php else: ?>
                            <div class="ev-muted">
                                No file submitted yet.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-lg-7">
                        <form method="post" enctype="multipart/form-data">
                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(csrfToken()) ?>"
                            >

                            <input
                                type="hidden"
                                name="enrollment_id"
                                value="<?= $selected ?>"
                            >

                            <input
                                type="hidden"
                                name="document_name"
                                value="<?= e($d['document_name']) ?>"
                            >

                            <label class="form-label small">
                                Upload / Replace
                            </label>

                            <input
                                class="form-control form-control-sm mb-2"
                                type="file"
                                name="document"
                                accept=".jpg,.jpeg,.png,.pdf"
                                required
                            >

                            <button
                                class="btn btn-sm btn-outline-primary"
                                name="upload_student_file"
                                value="1"
                            >
                                Upload File
                            </button>
                        </form>
                    </div>
                </div>
            </details>
        </div>
    <?php endforeach; ?>

    <?php if (!$docs): ?>
        <div class="text-muted">
            No requirements configured.
        </div>
    <?php endif; ?>

    <details class="mt-3">
        <summary
            class="small fw-semibold text-primary"
            style="cursor: pointer;"
        >
            Document requirement review
        </summary>

        <form method="post" class="mt-3">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrfToken()) ?>"
            >

            <input
                type="hidden"
                name="enrollment_id"
                value="<?= $selected ?>"
            >

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Requirement</th>
                            <th>Verification</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($docs as $d): ?>
                            <tr>
                                <td><?= e($d['document_name']) ?></td>

                                <td>
                                    <select
                                        class="form-select form-select-sm"
                                        name="verification_status[<?= $d['enrollment_document_id'] ?>]"
                                    >
                                        <?php foreach (
                                            ['Not Submitted', 'Pending', 'Accepted', 'Rejected', 'Not Required']
                                            as $st
                                        ): ?>
                                            <option
                                                value="<?= $st ?>"
                                                <?= $d['verification_status'] === $st ? 'selected' : '' ?>
                                            >
                                                <?= $st ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                                <td>
                                    <input
                                        class="form-control form-control-sm"
                                        name="remarks[<?= $d['enrollment_document_id'] ?>]"
                                        value="<?= e($d['remarks'] ?? '') ?>"
                                        placeholder="Optional remarks"
                                    >
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <button
                class="btn btn-primary btn-sm"
                name="save_all_requirements"
                value="1"
            >
                Save Requirement Review
            </button>
        </form>
    </details>
</div>

<div class="ev-card">
    <div class="ev-head">
        <div>
            <h5 class="mb-1">Official Enrollment</h5>

            <div class="ev-muted">
                Guidance Passed, required documents accepted, and section assigned.
            </div>
        </div>

        <span class="ev-pill <?= $canFinalize ? 'good' : '' ?>">
            <?= $canFinalize ? 'Ready' : 'Not ready' ?>
        </span>
    </div>

    <?php if ($selectedInfo['enrollment_status'] !== 'Officially Enrolled'): ?>
        <form
            method="post"
            class="mt-3"
            onsubmit="return confirm('Finalize this student as Officially Enrolled?');"
        >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrfToken()) ?>"
            >

            <input
                type="hidden"
                name="enrollment_id"
                value="<?= $selected ?>"
            >

            <button
                class="btn btn-success"
                name="finalize_enrollment"
                value="1"
                <?= $canFinalize ? '' : 'disabled' ?>
            >
                Finalize Official Enrollment
            </button>
        </form>
    <?php endif; ?>
</div>

<?php endif; ?>

<?php
if (isset($_GET['embedded']) && $_GET['embedded'] === '1') {
    echo '</body></html>';
} else {
    require 'includes/footer.php';
}
?>
