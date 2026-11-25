<?php
chdir(dirname(__DIR__));

session_start();

require_once 'dbconn.php';
require_once 'includes/functions.php';

requireRole(['Teacher Adviser', 'Subject Teacher']);

$role = $_SESSION['role'] ?? '';
$uid = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare(
    'SELECT teacher_id FROM teachers WHERE user_id = ?'
);

$stmt->execute([$uid]);

$teacherId = (int) $stmt->fetchColumn();

$advisory = [];
$teaching = [];

if ($teacherId) {
    $sql = "
        SELECT DISTINCT
            sy.school_year_name,
            cs.grade_level,
            cs.section_name,
            s.subject_code,
            s.subject_name,
            ss.schedule_days,
            ss.start_time,
            ss.end_time,
            ss.room,
            COALESCE(
                CONCAT(t.first_name, ' ', t.last_name),
                'Not assigned'
            ) AS teacher_name
        FROM section_subjects ss
        JOIN class_sections cs
            ON cs.section_id = ss.section_id
        JOIN school_years sy
            ON sy.school_year_id = cs.school_year_id
        JOIN subjects s
            ON s.subject_id = ss.subject_id
        LEFT JOIN teachers t
            ON t.teacher_id = ss.teacher_id
        WHERE %s
        ORDER BY
            sy.school_year_name DESC,
            cs.grade_level,
            cs.section_name,
            ss.start_time,
            s.subject_name
    ";

    if ($role === 'Teacher Adviser') {
        $q = $pdo->prepare(
            sprintf($sql, 'cs.adviser_teacher_id = ?')
        );

        $q->execute([$teacherId]);

        $advisory = $q->fetchAll(PDO::FETCH_ASSOC);
    }

    $q = $pdo->prepare(
        sprintf($sql, 'ss.teacher_id = ?')
    );

    $q->execute([$teacherId]);

    $teaching = $q->fetchAll(PDO::FETCH_ASSOC);
}

function scheduleTable($rows)
{
    if (!$rows) {
        echo '<p class="text-muted mb-0">No schedule assigned yet.</p>';
        return;
    }

    echo '
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>School Year</th>
                        <th>Grade / Section</th>
                        <th>Subject</th>
                        <th>Teacher</th>
                        <th>Days</th>
                        <th>Time</th>
                        <th>Room</th>
                    </tr>
                </thead>
                <tbody>
    ';

    foreach ($rows as $r) {
        $time = (
            $r['start_time']
                ? date('g:i A', strtotime($r['start_time']))
                : 'TBA'
        ) . ' – ' . (
            $r['end_time']
                ? date('g:i A', strtotime($r['end_time']))
                : 'TBA'
        );

        echo '
            <tr>
                <td>' . e($r['school_year_name']) . '</td>
                <td>' . e(
                    $r['grade_level'] . ' · ' . $r['section_name']
                ) . '</td>
                <td>
                    <strong>' . e($r['subject_name']) . '</strong>
                    <div class="small text-muted">
                        ' . e($r['subject_code']) . '
                    </div>
                </td>
                <td>' . e($r['teacher_name']) . '</td>
                <td>' . e($r['schedule_days'] ?: 'TBA') . '</td>
                <td>' . e($time) . '</td>
                <td>' . e($r['room'] ?: 'TBA') . '</td>
            </tr>
        ';
    }

    echo '
                </tbody>
            </table>
        </div>
    ';
}

require 'includes/header.php';
?>

<div class="page-heading">
    <div>
        <h1>My Schedule</h1>

        <p>
            <?= $role === 'Teacher Adviser'
                ? 'Advisory class and assigned teaching schedules in one page'
                : 'Your assigned teaching schedule' ?>
        </p>
    </div>

    <button
        type="button"
        class="btn btn-outline-primary"
        onclick="window.print()"
    >
        Print
    </button>
</div>

<?php if (!$teacherId): ?>
    <div class="alert alert-warning">
        Your teacher record is not linked to this account yet.
        Please contact the Administrator.
    </div>
<?php endif; ?>

<?php if ($role === 'Teacher Adviser'): ?>
    <div class="card p-3 mb-3">
        <h5 class="mb-3">Advisory Class Schedule</h5>

        <?php scheduleTable($advisory); ?>
    </div>
<?php endif; ?>

<div class="card p-3">
    <h5 class="mb-3">My Teaching Schedule</h5>

    <?php scheduleTable($teaching); ?>
</div>

<?php require 'includes/footer.php'; ?>