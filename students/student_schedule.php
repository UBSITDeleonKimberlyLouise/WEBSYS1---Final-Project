
<?php
chdir(dirname(__DIR__));

session_start();

require_once 'dbconn.php';
require_once 'includes/functions.php';

requireRole(['Student']);

$q = $pdo->prepare(
    'SELECT student_lrn FROM students WHERE user_id = ?'
);

$q->execute([$_SESSION['user_id']]);
$lrn = $q->fetchColumn();

if (!$lrn) {
    http_response_code(404);
    exit('Student profile not found.');
}

$q = $pdo->prepare("
    SELECT
        sy.school_year_name,
        e.grade_level,
        cs.section_name,
        s.subject_code,
        s.subject_name,
        ss.schedule_days,
        ss.start_time,
        ss.end_time,
        ss.room,
        CONCAT_WS(' ', t.first_name, t.last_name) AS teacher_name
    FROM enrollments e
    JOIN school_years sy
        ON sy.school_year_id = e.school_year_id
    JOIN class_sections cs
        ON cs.section_id = e.section_id
        AND cs.school_year_id = e.school_year_id
    JOIN section_subjects ss
        ON ss.section_id = cs.section_id
    JOIN subjects s
        ON s.subject_id = ss.subject_id
    LEFT JOIN teachers t
        ON t.teacher_id = ss.teacher_id
    WHERE e.student_lrn = ?
        AND sy.year_status = 'Current'
    ORDER BY
        ss.start_time,
        s.subject_name
");

$q->execute([$lrn]);
$rows = $q->fetchAll(PDO::FETCH_ASSOC);

require 'includes/header.php';
?>

<div class="page-heading">
    <div>
        <h1>My Schedule</h1>
        <p>My subjects and class schedule for the current school year.</p>
    </div>
</div>

<div class="card p-3">
    <?php if (!$rows): ?>
        <p class="text-muted mb-0">
            No current class schedule is available yet.
        </p>
    <?php else: ?>
        <h5>
            <?= e(
                $rows[0]['school_year_name'] . ' · ' .
                $rows[0]['grade_level'] . ' · ' .
                $rows[0]['section_name']
            ) ?>
        </h5>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Teacher</th>
                        <th>Days</th>
                        <th>Time</th>
                        <th>Room</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($rows as $r):
                        $start = $r['start_time']
                            ? date('g:i A', strtotime($r['start_time']))
                            : 'TBA';

                        $end = $r['end_time']
                            ? date('g:i A', strtotime($r['end_time']))
                            : 'TBA';
                    ?>
                        <tr>
                            <td>
                                <strong><?= e($r['subject_name']) ?></strong>
                                <div class="small text-muted">
                                    <?= e($r['subject_code']) ?>
                                </div>
                            </td>

                            <td>
                                <?= e($r['teacher_name'] ?: 'Not assigned') ?>
                            </td>

                            <td>
                                <?= e($r['schedule_days'] ?: 'TBA') ?>
                            </td>

                            <td>
                                <?= e($start . ' – ' . $end) ?>
                            </td>

                            <td>
                                <?= e($r['room'] ?: 'TBA') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>
