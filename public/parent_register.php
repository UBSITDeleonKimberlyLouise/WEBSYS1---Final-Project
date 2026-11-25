<?php
chdir(dirname(__DIR__));

session_start();

require_once 'dbconn.php';
require_once 'includes/functions.php';
require_once 'classes/StudentFiles.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    try {
        $lrn = trim($_POST['student_lrn'] ?? '');

        if (!preg_match('/^[0-9]{12}$/', $lrn)) {
            throw new Exception('LRN must contain exactly 12 digits.');
        }

        $category = $_POST['enrollment_category'] ?? '';

        if (!in_array($category, ['Incoming', 'Returning', 'Transferee', 'Balik-Aral'], true)) {
            throw new Exception('Choose a valid enrollment category.');
        }

        foreach (['first_name', 'last_name', 'birth_date', 'sex', 'parent_name', 'parent_relationship', 'parent_contact'] as $field) {
            if (trim($_POST[$field] ?? '') === '') {
                throw new Exception('Please complete all required fields.');
            }
        }

        if (empty($_POST['certify'])) {
            throw new Exception('Please confirm the certification before submitting.');
        }

        if (!in_array($_POST['sex'], ['Male', 'Female'], true)) {
            throw new Exception('Choose a valid sex.');
        }

        $data = [];

        $fields = [
            'psa_birth_certificate_no',
            'first_name',
            'middle_name',
            'last_name',
            'extension_name',
            'birth_date',
            'sex',
            'place_of_birth',
            'religion',
            'mother_tongue',
            'is_ip_member',
            'is_4ps_beneficiary',
            'student_email',
            'student_contact_number',
            'current_house_no',
            'current_street_sitio',
            'current_barangay',
            'current_municipality',
            'current_province',
            'current_zip_code',
            'permanent_same_as_current',
            'permanent_house_no',
            'permanent_street_sitio',
            'permanent_barangay',
            'permanent_municipality',
            'permanent_province',
            'permanent_zip_code',
            'father_first_name',
            'father_middle_name',
            'father_last_name',
            'father_contact_number',
            'mother_maiden_first_name',
            'mother_maiden_middle_name',
            'mother_maiden_last_name',
            'mother_contact_number',
            'guardian_first_name',
            'guardian_middle_name',
            'guardian_last_name',
            'guardian_contact_number',
            'guardian_relationship',
            'parent_name',
            'parent_relationship',
            'parent_contact',
            'parent_email',
            'medical_conditions',
            'current_medications',
            'special_medical_concerns',
            'emergency_contact_name',
            'emergency_contact_relationship',
            'emergency_contact_number',
            'previous_school',
            'last_grade_completed',
            'last_school_year_attended'
        ];

        foreach ($fields as $field) {
            $data[$field] = trim($_POST[$field] ?? '');
        }

        $emailLabels = [
            'student_email' => 'Student email',
            'parent_email' => 'Parent / Guardian email',
        ];

        foreach ($emailLabels as $field => $label) {
            $value = $data[$field];
            if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new Exception($label . ' must be a valid email address.');
            }
        }

        $contactLabels = [
            'parent_contact' => 'Parent / Guardian contact number',
            'student_contact_number' => 'Student contact number',
            'father_contact_number' => 'Father contact number',
            'mother_contact_number' => 'Mother contact number',
            'guardian_contact_number' => 'Guardian contact number',
            'emergency_contact_number' => 'Emergency contact number',
        ];

        foreach ($contactLabels as $field => $label) {
            $value = $data[$field];
            if ($value !== '' && !preg_match('/^[0-9]{11}$/', $value)) {
                throw new Exception($label . ' must contain exactly 11 digits.');
            }
        }

        $data['distance_learning'] = $_POST['distance_learning'] ?? [];
        $data['certified'] = 1;

        $query = $pdo->prepare("
            SELECT COUNT(*)
            FROM student_registration_applications
            WHERE student_lrn = ?
        ");
        $query->execute([$lrn]);

        if ((int) $query->fetchColumn() > 0) {
            throw new Exception('This LRN is already registered in the database.');
        }

        $studentEmail = $data['student_email'];
        if ($studentEmail !== '') {
            $query = $pdo->prepare("
                SELECT COUNT(*)
                FROM student_registration_applications
                WHERE LOWER(JSON_UNQUOTE(JSON_EXTRACT(application_data, '$.student_email'))) = LOWER(?)
            ");
            $query->execute([$studentEmail]);

            if ((int) $query->fetchColumn() > 0) {
                throw new Exception('This student email is already registered in the database.');
            }
        }

        $psaBirthCertNo = $data['psa_birth_certificate_no'];
        if ($psaBirthCertNo !== '') {
            $query = $pdo->prepare("
                SELECT COUNT(*)
                FROM student_registration_applications
                WHERE JSON_UNQUOTE(JSON_EXTRACT(application_data, '$.psa_birth_certificate_no')) = ?
            ");
            $query->execute([$psaBirthCertNo]);

            if ((int) $query->fetchColumn() > 0) {
                throw new Exception('This PSA Birth Certificate No. is already registered in the database.');
            }
        }

        $query = $pdo->prepare("
            INSERT INTO student_registration_applications (
                student_lrn,
                enrollment_category,
                application_data
            ) VALUES (?, ?, ?)
        ");

        $query->execute([
            $lrn,
            $category,
            json_encode($data, JSON_UNESCAPED_UNICODE)
        ]);

        $files = new StudentFiles($pdo);
        $uploaded = 0;

        foreach ($_FILES['docs']['name'] ?? [] as $i => $unused) {
            if (($_FILES['docs']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $file = [];

            foreach (['name', 'tmp_name', 'size', 'error', 'type'] as $key) {
                $file[$key] = $_FILES['docs'][$key][$i];
            }

            $files->save_file($lrn, $files->types()[$i], $file);
            $uploaded++;
        }

        $success = 'Registration submitted. ' . $uploaded . ' document(s) uploaded. The Registrar will review your requirements. You may also submit physical copies at school.';

    } catch (Throwable $e) {
        $error = $e instanceof PDOException
            ? 'Unable to submit. Please check the information or contact the school.'
            : $e->getMessage();
    }
}

$phoneFields = [
    'student_contact_number',
    'father_contact_number',
    'mother_contact_number',
    'guardian_contact_number',
    'parent_contact',
    'emergency_contact_number',
];
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Student Registration | ELTNHS</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<div class="container py-4" style="max-width: 1050px;">
    <div class="card p-4">

        <h2 class="page-title">ELTNHS Student Registration</h2>

        <p class="text-muted">
            Parent/guardian online registration form. Upload JPG, PNG or PDF documents
            (up to 5 MB each), or submit physical copies at school.
        </p>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= e($success) ?>
            </div>
        <?php else: ?>

            <form method="post" enctype="multipart/form-data" novalidate>

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrfToken()) ?>"
                >

                <h5 class="mt-2">Enrollment Information</h5>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">12-digit LRN *</label>
                        <input
                            class="form-control"
                            name="student_lrn"
                            value="<?= e($_POST['student_lrn'] ?? '') ?>"
                            maxlength="12"
                            pattern="[0-9]{12}"
                            title="LRN must contain exactly 12 digits"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Enrollment Category *</label>
                        <select class="form-select" name="enrollment_category" required>
                            <option value="">Select</option>
                            <?php foreach (['Incoming', 'Returning', 'Transferee', 'Balik-Aral'] as $cat): ?>
                                <option <?= (($_POST['enrollment_category'] ?? '') === $cat) ? 'selected' : '' ?>><?= e($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">PSA Birth Certificate No.</label>
                        <input class="form-control" name="psa_birth_certificate_no" value="<?= e($_POST['psa_birth_certificate_no'] ?? '') ?>">
                    </div>
                </div>

                <h5 class="mt-4">Student Information</h5>

                <div class="row g-3">
                    <?php
                    $inputs = [
                        'first_name' => 'First Name *',
                        'middle_name' => 'Middle Name',
                        'last_name' => 'Last Name *',
                        'extension_name' => 'Suffix',
                        'birth_date' => 'Birth Date *',
                        'place_of_birth' => 'Place of Birth',
                        'religion' => 'Religion',
                        'mother_tongue' => 'Mother Tongue',
                        'student_email' => 'Student Email',
                        'student_contact_number' => 'Student Contact Number'
                    ];

                    foreach ($inputs as $name => $label):
                        $isPhone = in_array($name, $phoneFields, true);
                        $isEmail = ($name === 'student_email');
                    ?>
                        <div class="col-md-4">
                            <label class="form-label">
                                <?= e($label) ?>
                            </label>

                            <input
                                class="form-control"
                                name="<?= $name ?>"
                                value="<?= e($_POST[$name] ?? '') ?>"
                                <?= $name === 'birth_date' ? 'type="date"' : '' ?>
                                <?= $isEmail ? 'type="email"' : '' ?>
                                <?= $isPhone ? 'type="tel" maxlength="11" pattern="[0-9]{11}" title="Must be an 11-digit number"' : '' ?>
                                <?= $name === 'birth_date' || in_array($name, ['first_name', 'last_name']) ? 'required' : '' ?>
                            >
                        </div>
                    <?php endforeach; ?>

                    <div class="col-md-4">
                        <label class="form-label">Sex *</label>
                        <select class="form-select" name="sex" required>
                            <option value="">Select</option>
                            <?php foreach (['Male', 'Female'] as $sexOpt): ?>
                                <option <?= (($_POST['sex'] ?? '') === $sexOpt) ? 'selected' : '' ?>><?= e($sexOpt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">IP Member</label>
                        <select class="form-select" name="is_ip_member">
                            <option value="" <?= (($_POST['is_ip_member'] ?? '') === '') ? 'selected' : '' ?>>No</option>
                            <option value="1" <?= (($_POST['is_ip_member'] ?? '') === '1') ? 'selected' : '' ?>>Yes</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">4Ps Beneficiary</label>
                        <select class="form-select" name="is_4ps_beneficiary">
                            <option value="" <?= (($_POST['is_4ps_beneficiary'] ?? '') === '') ? 'selected' : '' ?>>No</option>
                            <option value="1" <?= (($_POST['is_4ps_beneficiary'] ?? '') === '1') ? 'selected' : '' ?>>Yes</option>
                        </select>
                    </div>
                </div>

                <h5 class="mt-4">Current Address</h5>

                <div class="row g-3">
                    <?php
                    foreach ([
                        'current_house_no' => 'House / Building No.',
                        'current_street_sitio' => 'Street / Sitio',
                        'current_barangay' => 'Barangay',
                        'current_municipality' => 'Municipality / City',
                        'current_province' => 'Province',
                        'current_zip_code' => 'ZIP Code'
                    ] as $name => $label):
                    ?>
                        <div class="col-md-4">
                            <label class="form-label">
                                <?= e($label) ?>
                            </label>
                            <input class="form-control" name="<?= $name ?>" value="<?= e($_POST[$name] ?? '') ?>">
                        </div>
                    <?php endforeach; ?>
                </div>

                <h5 class="mt-4">Permanent Address</h5>

                <div class="row g-3">
                    <div class="col-12">
                        <label>
                            <input
                                type="hidden"
                                name="permanent_same_as_current"
                                value="0"
                            >

                            <input
                                type="checkbox"
                                name="permanent_same_as_current"
                                value="1"
                                <?= !isset($_POST['permanent_same_as_current']) || $_POST['permanent_same_as_current'] === '1' ? 'checked' : '' ?>
                            >

                            Same as current address
                        </label>
                    </div>

                    <?php
                    foreach ([
                        'permanent_house_no' => 'House / Building No.',
                        'permanent_street_sitio' => 'Street / Sitio',
                        'permanent_barangay' => 'Barangay',
                        'permanent_municipality' => 'Municipality / City',
                        'permanent_province' => 'Province',
                        'permanent_zip_code' => 'ZIP Code'
                    ] as $name => $label):
                    ?>
                        <div class="col-md-4">
                            <label class="form-label">
                                <?= e($label) ?>
                            </label>
                            <input class="form-control" name="<?= $name ?>" value="<?= e($_POST[$name] ?? '') ?>">
                        </div>
                    <?php endforeach; ?>
                </div>

                <h5 class="mt-4">Parent / Guardian Information</h5>

                <div class="row g-3">
                    <?php
                    foreach ([
                        'parent_name' => 'Parent / Guardian Full Name *',
                        'parent_relationship' => 'Relationship to Student *',
                        'parent_contact' => 'Contact Number *',
                        'parent_email' => 'Email',
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
                        'guardian_contact_number' => 'Guardian Contact',
                        'guardian_relationship' => 'Guardian Relationship'
                    ] as $name => $label):
                        $isPhone = in_array($name, $phoneFields, true);
                    ?>
                        <div class="col-md-4">
                            <label class="form-label">
                                <?= e($label) ?>
                            </label>

                            <input
                                class="form-control"
                                name="<?= $name ?>"
                                value="<?= e($_POST[$name] ?? '') ?>"
                                <?= $name === 'parent_email' ? 'type="email"' : '' ?>
                                <?= $isPhone ? 'type="tel" maxlength="11" pattern="[0-9]{11}" title="Must be an 11-digit number"' : '' ?>
                                <?= in_array($name, ['parent_name', 'parent_relationship', 'parent_contact']) ? 'required' : '' ?>
                            >
                        </div>
                    <?php endforeach; ?>
                </div>

                <h5 class="mt-4">Health and Emergency Information</h5>

                <div class="row g-3">
                    <?php
                    foreach ([
                        'medical_conditions' => 'Medical Conditions',
                        'current_medications' => 'Current Medications',
                        'special_medical_concerns' => 'Special Medical Concerns',
                        'emergency_contact_name' => 'Emergency Contact Name',
                        'emergency_contact_relationship' => 'Emergency Contact Relationship',
                        'emergency_contact_number' => 'Emergency Contact Number'
                    ] as $name => $label):
                        $isPhone = in_array($name, $phoneFields, true);
                    ?>
                        <div class="col-md-4">
                            <label class="form-label">
                                <?= e($label) ?>
                            </label>
                            <input
                                class="form-control"
                                name="<?= $name ?>"
                                value="<?= e($_POST[$name] ?? '') ?>"
                                <?= $isPhone ? 'type="tel" maxlength="11" pattern="[0-9]{11}" title="Must be an 11-digit number"' : '' ?>
                            >
                        </div>
                    <?php endforeach; ?>
                </div>

                <h5 class="mt-4">Previous School Information</h5>

                <div class="row g-3">
                    <?php
                    foreach ([
                        'previous_school' => 'Previous School',
                        'last_grade_completed' => 'Last Grade Completed',
                        'last_school_year_attended' => 'Last School Year Attended'
                    ] as $name => $label):
                    ?>
                        <div class="col-md-4">
                            <label class="form-label">
                                <?= e($label) ?>
                            </label>
                            <input class="form-control" name="<?= $name ?>" value="<?= e($_POST[$name] ?? '') ?>">
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="card p-3 mt-4">
                    <h5>Preferred Distance Learning Modalities</h5>

                    <p>
                        If the school implements distance learning aside from face-to-face
                        instruction, what would you prefer for your child? Check all that apply.
                    </p>

                    <div class="row">
                        <?php
                        $selectedModalities = $_POST['distance_learning'] ?? [];
                        foreach ([
                            'Blended / Combination',
                            'Educational Television',
                            'Homeschooling',
                            'Modular - Digital',
                            'Modular - Print',
                            'Online',
                            'Radio-Based'
                        ] as $modality):
                        ?>
                            <div class="col-md-6 mb-2">
                                <label>
                                    <input
                                        type="checkbox"
                                        name="distance_learning[]"
                                        value="<?= e($modality) ?>"
                                        <?= in_array($modality, $selectedModalities, true) ? 'checked' : '' ?>
                                    >
                                    <?= e($modality) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card p-3 mt-4">
                    <h5>Enrollment Documents (optional uploads)</h5>

                    <p class="text-muted small">
                        JPG, PNG, PDF only. Maximum 5 MB per file.
                        You may submit physical documents to the Registrar instead.
                    </p>

                    <div class="row g-3">
                        <?php foreach ((new StudentFiles($pdo))->types() as $i => $doc): ?>
                            <div class="col-md-6">
                                <label class="form-label">
                                    <?= e($doc) ?>
                                </label>

                                <input
                                    class="form-control"
                                    type="file"
                                    name="docs[]"
                                    accept=".jpg,.jpeg,.png,.pdf"
                                >
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-check mt-3">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        name="certify"
                        value="1"
                        id="certify"
                        <?= !empty($_POST['certify']) ? 'checked' : '' ?>
                        required
                    >

                    <label class="form-check-label" for="certify">
                        I hereby certify that the information given is true and correct
                        to the best of my knowledge, and I allow DepEd to use my child's
                        details to create or update their learner profile in the Learner
                        Information System.
                    </label>
                </div>

                <div class="alert alert-info mt-4 mb-3">
                    Submitting this form does not mean the student is officially enrolled.
                    The Registrar must verify the information and documents first.
                </div>

                <button class="btn btn-primary">
                    Submit Registration
                </button>

            </form>

        <?php endif; ?>

    </div>
</div>

</body>
</html>