
<?php
chdir(dirname(__DIR__));

session_start();

require_once 'dbconn.php';
require_once 'includes/functions.php';

requireRole(['School Registrar', 'System Administrator']);

$fields = [
    'first_name' => 'First Name',
    'middle_name' => 'Middle Name',
    'last_name' => 'Last Name',
    'extension_name' => 'Extension Name',
    'sex' => 'Sex',
    'birth_date' => 'Birthdate',
    'student_status' => 'Student Status',
    'current_house_no' => 'House No.',
    'current_street_sitio' => 'Street / Sitio',
    'current_barangay' => 'Barangay',
    'current_municipality' => 'Municipality',
    'current_province' => 'Province',
    'current_zip_code' => 'ZIP Code',
    'father_first_name' => 'Father First Name',
    'father_middle_name' => 'Father Middle Name',
    'father_last_name' => 'Father Last Name',
    'father_contact_number' => 'Father Contact',
    'mother_maiden_first_name' => 'Mother First Name',
    'mother_maiden_middle_name' => 'Mother Middle Name',
    'mother_maiden_last_name' => 'Mother Last Name',
    'mother_contact_number' => 'Mother Contact',
    'guardian_first_name' => 'Guardian First Name',
    'guardian_middle_name' => 'Guardian Middle Name',
    'guardian_last_name' => 'Guardian Last Name',
    'guardian_contact_number' => 'Guardian Contact'
];

$lrn = trim((string)($_POST['search_lrn'] ?? $_POST['student_lrn'] ?? ''));
$msg = '';
$err = '';
$student = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    try {
        if (($_POST['student_action'] ?? '') !== 'save') {
            // Search only: no record update.
        } else {
            if (!preg_match('/^[0-9]{12}$/', $lrn)) {
                throw new Exception('Invalid LRN.');
            }

            $data = [];

            foreach ($fields as $key => $label) {
                $data[$key] = trim((string)($_POST[$key] ?? ''));
            }

            if ($data['first_name'] === '' || $data['last_name'] === '') {
                throw new Exception('First and last names are required.');
            }

            if (!in_array($data['sex'], ['Male', 'Female'], true)) {
                throw new Exception('Choose a valid sex.');
            }

            if (!in_array($data['student_status'], ['Active', 'Transferred', 'Dropped', 'Graduated', 'Inactive'], true)) {
                throw new Exception('Invalid student status.');
            }

            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $data['birth_date']);

            if (!$date || $date->format('Y-m-d') !== $data['birth_date']) {
                throw new Exception('Invalid birthdate.');
            }

            foreach ($data as $key => $value) {
                if (
                    !in_array($key, ['first_name', 'last_name', 'sex', 'birth_date', 'student_status'], true)
                    && $value === ''
                ) {
                    $data[$key] = null;
                }
            }

            $sets = implode(',', array_map(
                static fn($key) => "`$key`=?",
                array_keys($data)
            ));

            $stmt = $pdo->prepare("UPDATE students SET $sets WHERE student_lrn=?");
            $stmt->execute([...array_values($data), $lrn]);

            if (!$stmt->rowCount()) {
                $q = $pdo->prepare('SELECT 1 FROM students WHERE student_lrn=?');
                $q->execute([$lrn]);

                if (!$q->fetchColumn()) {
                    throw new Exception('Student not found.');
                }
            }

            $msg = 'Student information saved.';
        }
    } catch (Throwable $e) {
        $err = $e instanceof PDOException
            ? 'Unable to save student details.'
            : ' ' . $e->getMessage();

        error_log('Student edit: ' . $e->getMessage());
    }
}

if (preg_match('/^[0-9]{12}$/', $lrn)) {
    $q = $pdo->prepare('SELECT * FROM students WHERE student_lrn=?');
    $q->execute([$lrn]);
    $student = $q->fetch(PDO::FETCH_ASSOC);
}

require 'includes/header.php';
?>

<div class="page-heading">
    <div>
        <h1>Student Management</h1>
        <p>Admin and Registrar can update student and parent/guardian details.</p>
    </div>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>

<?php if ($err): ?>
    <div class="alert alert-danger"><?= e($err) ?></div>
<?php endif; ?>

<div class="card p-3 mb-3">
    <form method="post" class="d-flex gap-2">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

        <input
            class="form-control"
            name="search_lrn"
            value="<?= e($lrn) ?>"
            placeholder="Enter 12-digit student LRN"
            pattern="[0-9]{12}"
            required
        >

        <button class="btn btn-primary">Search</button>
    </form>
</div>

<?php if ($lrn !== '' && !$student): ?>
    <div class="alert alert-warning">No matching student record found.</div>
<?php endif; ?>

<?php if ($student): ?>
    <div class="card p-4">
        <h5>
            <?= e($student['last_name'] . ', ' . $student['first_name']) ?>
            — <?= e($lrn) ?>
        </h5>

        <form method="post">
            <input type="hidden" name="student_action" value="save">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="student_lrn" value="<?= e($lrn) ?>">

            <div class="row g-3">
                <?php foreach ($fields as $key => $label): ?>
                    <div class="col-md-4">
                        <label class="form-label"><?= e($label) ?></label>

                        <?php if ($key === 'sex' || $key === 'student_status'): ?>
                            <select class="form-select" name="<?= e($key) ?>">
                                <?php foreach (
                                    ($key === 'sex'
                                        ? ['Male', 'Female']
                                        : ['Active', 'Transferred', 'Dropped', 'Graduated', 'Inactive']
                                    ) as $v
                                ): ?>
                                    <option
                                        value="<?= e($v) ?>"
                                        <?= ($student[$key] ?? '') === $v ? 'selected' : '' ?>
                                    >
                                        <?= e($v) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input
                                class="form-control"
                                name="<?= e($key) ?>"
                                value="<?= e($student[$key] ?? '') ?>"
                                <?= $key === 'birth_date' ? 'type="date"' : '' ?>
                                <?= in_array($key, ['first_name', 'last_name', 'birth_date'], true) ? 'required' : '' ?>
                            >
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <button class="btn btn-primary mt-3">Save Changes</button>
        </form>
    </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
