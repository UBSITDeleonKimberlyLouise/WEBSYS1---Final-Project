<?php
chdir(dirname(__DIR__));

session_start();

require_once 'dbconn.php';
require_once 'includes/functions.php';

requireRole(['Teacher Adviser', 'Subject Teacher']);

$uid = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT
        u.username,
        u.role,
        t.teacher_id,
        t.employee_number,
        t.first_name,
        t.middle_name,
        t.last_name,
        t.contact_number,
        t.email,
        t.employment_status,
        sp.address
    FROM users u
    LEFT JOIN teachers t
        ON t.user_id = u.user_id
    LEFT JOIN staff_profiles sp
        ON sp.user_id = u.user_id
    WHERE u.user_id = ?
");

$stmt->execute([$uid]);

$person = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$person) {
    http_response_code(404);
    exit('Account not found.');
}

function showField($label, $value)
{
    echo '
        <div class="col-md-6 mb-3">
            <div class="text-muted small">' . e($label) . '</div>
            <div class="border rounded p-2 bg-light">' .
                e($value === null || $value === '' ? 'Not provided' : $value) .
            '</div>
        </div>
    ';
}

require 'includes/header.php';
?>

<div class="page-heading">
    <div>
        <h1>My Profile</h1>
        <p>Teacher account and personnel information</p>
    </div>
</div>

<div class="card p-4">
    <h4 class="mb-1">
        <?= e(
            trim(
                ($person['first_name'] ?? '') . ' ' .
                ($person['middle_name'] ?? '') . ' ' .
                ($person['last_name'] ?? '')
            ) ?: 'Teacher Profile'
        ) ?>
    </h4>

    <p class="text-muted mb-4">
        <?= e($person['role']) ?>
    </p>

    <?php if (empty($person['teacher_id'])): ?>
        <div class="alert alert-warning">
            Your teacher record is not linked to this account yet.
            Please contact the Administrator.
        </div>
    <?php endif; ?>

    <div class="row">
        <?php
        showField('Employee Number', $person['employee_number']);
        showField('Username', $person['username']);
        showField('Role', $person['role']);
        showField('Email', $person['email']);
        showField('Contact Number', $person['contact_number']);
        showField('Employment Status', $person['employment_status']);
        showField('Address', $person['address']);
        ?>
    </div>
</div>

<?php require 'includes/footer.php'; ?>