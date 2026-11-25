
<?php
chdir(dirname(__DIR__));

session_start();

require_once 'dbconn.php';
require_once 'classes/ELTNHS.php';
require_once 'includes/functions.php';

requireLogin();

$system = new ELTNHS($pdo);
$role = $_SESSION['role'];
$page = $_GET['page'] ?? 'profile';

if (!in_array($page, ['profile', 'schedule', 'grades'], true)) {
    http_response_code(404);
    exit('Page not found.');
}

if ($role === 'Student') {
    $profile = $system->getOne(
        'SELECT * FROM students WHERE user_id = ?',
        [$_SESSION['user_id']]
    );

    if (!$profile) {
        http_response_code(404);
        exit('Your student profile is not linked to this account. Please contact the Registrar.');
    }

    $enrollments = $system->getAll(
        'SELECT
            e.*,
            sy.school_year_name,
            cs.section_name
        FROM enrollments e
        JOIN school_years sy
            ON e.school_year_id = sy.school_year_id
        LEFT JOIN class_sections cs
            ON e.section_id = cs.section_id
        WHERE e.student_lrn = ?
        ORDER BY e.enrollment_id DESC',
        [$profile['student_lrn']]
    );

    if ($page === 'schedule') {
        $schedule = $system->getAll(
            "SELECT
                sy.school_year_name,
                e.grade_level,
                cs.section_name,
                s.subject_code,
                s.subject_name,
                ss.schedule_days,
                ss.start_time,
                ss.end_time,
                ss.room,
                COALESCE(
                    CONCAT(t.first_name, ' ', t.last_name),
                    'TBA'
                ) teacher_name
            FROM enrollments e
            JOIN school_years sy
                ON e.school_year_id = sy.school_year_id
            JOIN class_sections cs
                ON e.section_id = cs.section_id
            JOIN section_subjects ss
                ON ss.section_id = cs.section_id
            JOIN subjects s
                ON ss.subject_id = s.subject_id
            LEFT JOIN teachers t
                ON ss.teacher_id = t.teacher_id
            WHERE e.student_lrn = ?
              AND e.enrollment_status IN (
                  'Officially Enrolled',
                  'Temporarily Enrolled'
              )
            ORDER BY
                sy.school_year_name DESC,
                ss.start_time",
            [$profile['student_lrn']]
        );
    }

    if ($page === 'grades') {
        $grades = $system->getAll(
            "SELECT
                sy.school_year_name,
                e.grade_level,
                e.final_result,
                ss.section_subject_id,
                s.subject_code,
                s.subject_name,
                COALESCE(
                    CONCAT(t.first_name, ' ', t.last_name),
                    'Not assigned'
                ) teacher_name,
                g.grading_period,
                g.grade_value,
                g.remarks,
                g.result_status
            FROM grades g
            JOIN enrollments e
                ON g.enrollment_id = e.enrollment_id
            JOIN school_years sy
                ON e.school_year_id = sy.school_year_id
            JOIN section_subjects ss
                ON g.section_subject_id = ss.section_subject_id
            JOIN subjects s
                ON ss.subject_id = s.subject_id
            LEFT JOIN teachers t
                ON ss.teacher_id = t.teacher_id
            WHERE e.student_lrn = ?
            ORDER BY
                sy.school_year_name DESC,
                s.subject_name,
                FIELD(
                    g.grading_period,
                    'Prelim',
                    'Term 1',
                    'Midterm',
                    'Term 2',
                    'Finals',
                    'Term 3'
                )",
            [$profile['student_lrn']]
        );
    }

} else {
    $teacher = $system->getOne(
        'SELECT * FROM teachers WHERE user_id = ?',
        [$_SESSION['user_id']]
    );

    if (!$teacher) {
        http_response_code(404);
        exit('Your teacher profile is not linked to this account. Please contact the Registrar.');
    }

    if (!in_array($page, ['schedule', 'profile'], true)) {
        http_response_code(403);
        exit('This page is not available for this account.');
    }

    $schedule = $system->getAll(
        "SELECT
            sy.school_year_name,
            cs.grade_level,
            cs.section_name,
            s.subject_code,
            s.subject_name,
            ss.schedule_days,
            ss.start_time,
            ss.end_time,
            ss.room
        FROM section_subjects ss
        JOIN class_sections cs
            ON ss.section_id = cs.section_id
        JOIN school_years sy
            ON cs.school_year_id = sy.school_year_id
        JOIN subjects s
            ON ss.subject_id = s.subject_id
        WHERE ss.teacher_id = ?
        ORDER BY
            sy.school_year_name DESC,
            ss.start_time",
        [$teacher['teacher_id']]
    );
}

$title = [
    'profile' => 'My Profile',
    'schedule' => 'My Schedule',
    'grades' => 'Grades'
][$page];

require 'includes/header.php';
?>

<?php if ($role === 'Student'): ?>
    <div class="mb-3">
        <a
            class="btn btn-outline-primary"
            href="../students/student_requirements.php"
        >
            My Enrollment Documents
        </a>
    </div>
<?php endif; ?>

<div class="page-heading">
    <div>
        <h1><?= e($title) ?></h1>
        <p>
            <?= $role === 'Student'
                ? 'Your personal school information.'
                : 'Your assigned teaching schedule.' ?>
        </p>
    </div>

    <?php if ($page === 'grades' || $page === 'schedule'): ?>
        <button
            type="button"
            class="btn btn-outline-primary print-action"
            onclick="window.print()"
            aria-label="Print this page"
            title="Print this page"
        >
            🖨️ Print
        </button>
    <?php endif; ?>
</div>

<?php if ($page === 'profile' && $role === 'Student'): ?>

    <div class="card p-4 mb-3">
        <div class="d-flex align-items-center gap-3 mb-3">
            <div
                class="brand-mark"
                style="width: 58px; height: 58px; font-size: 25px; border-radius: 50%;"
            >
                S
            </div>

            <div>
                <h4 class="mb-1">
                    <?= e(
                        $profile['last_name'] . ', ' .
                        $profile['first_name'] . ' ' .
                        ($profile['middle_name'] ?? '')
                    ) ?>
                </h4>

                <div class="small-note">
                    Learner Reference Number:
                    <?= e($profile['student_lrn']) ?>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <span class="small-note">Birth Date</span>
                <div><?= e($profile['birth_date']) ?></div>
            </div>

            <div class="col-md-4">
                <span class="small-note">Sex</span>
                <div><?= e($profile['sex']) ?></div>
            </div>

            <div class="col-md-4">
                <span class="small-note">Place of Birth</span>
                <div>
                    <?= e($profile['place_of_birth'] ?? 'Not provided') ?>
                </div>
            </div>

            <div class="col-md-6">
                <span class="small-note">Address</span>
                <div>
                    <?= e(implode(', ', array_filter([
                        $profile['current_house_no'],
                        $profile['current_street_sitio'],
                        $profile['current_barangay'],
                        $profile['current_municipality'],
                        $profile['current_province']
                    ]))) ?>
                </div>
            </div>

            <div class="col-md-6">
                <span class="small-note">Student Status</span>
                <div><?= e($profile['student_status']) ?></div>
            </div>

            <div class="col-md-4">
                <span class="small-note">Student Email</span>
                <div>
                    <?= e($profile['student_email'] ?? '—') ?>
                </div>
            </div>

            <div class="col-md-4">
                <span class="small-note">Student Contact</span>
                <div>
                    <?= e($profile['student_contact_number'] ?? '—') ?>
                </div>
            </div>

            <div class="col-md-4">
                <span class="small-note">Mother Tongue</span>
                <div>
                    <?= e($profile['mother_tongue'] ?? '—') ?>
                </div>
            </div>

            <div class="col-md-4">
                <span class="small-note">Father</span>
                <div>
                    <?= e(
                        trim(
                            ($profile['father_first_name'] ?? '') . ' ' .
                            ($profile['father_middle_name'] ?? '') . ' ' .
                            ($profile['father_last_name'] ?? '')
                        ) ?: '—'
                    ) ?>
                    · <?= e($profile['father_contact_number'] ?? '') ?>
                </div>
            </div>

            <div class="col-md-4">
                <span class="small-note">Mother</span>
                <div>
                    <?= e(
                        trim(
                            ($profile['mother_maiden_first_name'] ?? '') . ' ' .
                            ($profile['mother_maiden_middle_name'] ?? '') . ' ' .
                            ($profile['mother_maiden_last_name'] ?? '')
                        ) ?: '—'
                    ) ?>
                    · <?= e($profile['mother_contact_number'] ?? '') ?>
                </div>
            </div>

            <div class="col-md-4">
                <span class="small-note">Guardian</span>
                <div>
                    <?= e(
                        trim(
                            ($profile['guardian_first_name'] ?? '') . ' ' .
                            ($profile['guardian_middle_name'] ?? '') . ' ' .
                            ($profile['guardian_last_name'] ?? '')
                        ) ?: '—'
                    ) ?>
                    · <?= e($profile['guardian_contact_number'] ?? '') ?>
                </div>
            </div>

            <div class="col-md-6">
                <span class="small-note">Medical Conditions</span>
                <div>
                    <?= e($profile['health_conditions'] ?? '—') ?>
                </div>
            </div>

            <div class="col-md-6">
                <span class="small-note">Medication / Medical Concerns</span>
                <div>
                    <?= e(
                        ($profile['current_medications'] ?? '—') . ' / ' .
                        ($profile['special_medical_concerns'] ?? '—')
                    ) ?>
                </div>
            </div>

            <div class="col-md-6">
                <span class="small-note">Emergency Contact</span>
                <div>
                    <?= e(
                        ($profile['emergency_contact_name'] ?? '—') . ' · ' .
                        ($profile['emergency_contact_relationship'] ?? '') . ' · ' .
                        ($profile['emergency_contact_number'] ?? '')
                    ) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card p-3">
        <h5>Enrollment History</h5>

        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>School Year</th>
                        <th>Grade</th>
                        <th>Section</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($enrollments as $en): ?>
                        <tr>
                            <td><?= e($en['school_year_name']) ?></td>
                            <td><?= e($en['grade_level']) ?></td>
                            <td>
                                <?= e($en['section_name'] ?? 'Not assigned') ?>
                            </td>
                            <td><?= e($en['enrollment_status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($page === 'profile' && $role !== 'Student'):

    $staff = $system->getOne(
        'SELECT
            sp.*,
            u.username,
            u.role
        FROM staff_profiles sp
        JOIN users u
            ON u.user_id = sp.user_id
        WHERE sp.user_id = ?',
        [$_SESSION['user_id']]
    );

    $staff = $staff ?: [];

    $staffName = trim(
        ($staff['first_name'] ?? '') . ' ' .
        ($staff['middle_name'] ?? '') . ' ' .
        ($staff['last_name'] ?? '')
    );

    $staffFields = [
        ['Employee Number', $staff['employee_number'] ?? '—'],
        ['Role', $staff['role'] ?? $role],
        ['Username', $staff['username'] ?? ($_SESSION['username'] ?? '—')],
        ['Email', $staff['email'] ?? '—'],
        ['Contact Number', $staff['contact_number'] ?? '—'],
        ['Address', $staff['address'] ?? '—']
    ];
?>

    <style>
        .teacher-profile-card {
            padding: 24px;
            max-width: 100%;
            min-width: 0;
        }

        .teacher-profile-header {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #e7ebf0;
            min-width: 0;
        }

        .teacher-profile-avatar {
            width: 56px;
            height: 56px;
            flex: 0 0 56px;
            border-radius: 50%;
            background: #eaf3fb;
            color: #2877bb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
        }

        .teacher-profile-heading {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .teacher-profile-heading h4 {
            font-size: 20px;
            line-height: 1.35;
            margin: 0 0 4px;
        }

        .teacher-profile-heading p {
            margin: 0;
            color: #7a8795;
            font-size: 13px;
        }

        .teacher-profile-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .teacher-profile-field {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .teacher-profile-field label {
            font-size: 12px;
            font-weight: 600;
            color: #667586;
            margin: 0;
        }

        .teacher-profile-value {
            min-height: 44px;
            padding: 11px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafd;
            overflow-wrap: anywhere;
            word-break: normal;
            line-height: 1.5;
        }

        .teacher-profile-field:nth-child(4),
        .teacher-profile-field:nth-child(5) {
            grid-column: span 1;
        }

        .teacher-profile-field:last-child {
            grid-column: 1 / -1;
        }

        @media (max-width: 950px) {
            .teacher-profile-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 560px) {
            .teacher-profile-card {
                padding: 16px;
            }

            .teacher-profile-grid {
                grid-template-columns: minmax(0, 1fr);
                gap: 13px;
            }

            .teacher-profile-header {
                gap: 12px;
            }

            .teacher-profile-heading h4 {
                font-size: 18px;
            }
        }
    </style>

    <div class="card teacher-profile-card">
        <div class="teacher-profile-header">
            <div class="teacher-profile-avatar" aria-hidden="true">
                T
            </div>

            <div class="teacher-profile-heading">
                <h4>
                    <?= e($staffName !== '' ? $staffName : 'Staff Profile') ?>
                </h4>
                <p><?= e($role) ?></p>
            </div>
        </div>

        <div class="teacher-profile-grid">
            <?php foreach ($staffFields as $field): ?>
                <div class="teacher-profile-field">
                    <label><?= e($field[0]) ?></label>

                    <div class="teacher-profile-value">
                        <?= e((string) ($field[1] !== '' ? $field[1] : '—')) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

<?php elseif ($page === 'schedule'): ?>

    <div class="card p-3">
        <h5 class="mb-3">Class Schedule</h5>

        <?php if (!$schedule): ?>
            <p class="text-muted mb-0">
                No schedule has been assigned yet. Please contact the Registrar.
            </p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <?php if ($role === 'Student'): ?>
                                <th>School Year</th>
                            <?php endif; ?>

                            <th>Subject</th>
                            <th>Section</th>
                            <th>Days</th>
                            <th>Time</th>
                            <th>Room</th>
                            <th><?= $role === 'Student' ? 'Teacher' : 'Grade' ?></th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($schedule as $s): ?>
                            <tr>
                                <?php if ($role === 'Student'): ?>
                                    <td><?= e($s['school_year_name']) ?></td>
                                <?php endif; ?>

                                <td>
                                    <strong><?= e($s['subject_code']) ?></strong>
                                    <div><?= e($s['subject_name']) ?></div>
                                </td>

                                <td><?= e($s['section_name']) ?></td>

                                <td>
                                    <?= e($s['schedule_days'] ?? 'TBA') ?>
                                </td>

                                <td>
                                    <?= e(
                                        ($s['start_time']
                                            ? date('g:i A', strtotime($s['start_time']))
                                            : 'TBA'
                                        ) . ' - ' .
                                        ($s['end_time']
                                            ? date('g:i A', strtotime($s['end_time']))
                                            : 'TBA'
                                        )
                                    ) ?>
                                </td>

                                <td><?= e($s['room'] ?? 'TBA') ?></td>

                                <td>
                                    <?= e(
                                        $role === 'Student'
                                            ? ($s['teacher_name'] ?? '')
                                            : $s['grade_level']
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </div>

<?php elseif ($page === 'grades' && $role === 'Student'): ?>

    <div class="card p-3">
        <h5 class="mb-3">Grade Records</h5>

        <p class="small-note">
            Grades are grouped by school year, subject, and grading period.
        </p>

        <?php if (!$grades): ?>
            <p class="text-muted mb-0">
                No grades have been posted yet.
            </p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>School Year</th>
                            <th>Grade Level</th>
                            <th>Teacher</th>
                            <th>Subject</th>
                            <th>Term 1</th>
                            <th>Term 2</th>
                            <th>Term 3</th>
                            <th>Result Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $grouped = [];

                        foreach ($grades as $g) {
                            $key = $g['school_year_name'] . '|' .
                                $g['grade_level'] . '|' .
                                $g['section_subject_id'] . '|' .
                                $g['subject_code'] . '|' .
                                $g['subject_name'];

                            $grouped[$key]['base'] = $g;

                            $mapped = [
                                'Prelim' => 'Term 1',
                                'Midterm' => 'Term 2',
                                'Finals' => 'Term 3'
                            ];

                            $term = $mapped[$g['grading_period']] ?? $g['grading_period'];

                            $grouped[$key][$term] = $g['grade_value'];
                        }

                        foreach ($grouped as $g):
                        ?>
                            <tr>
                                <td>
                                    <?= e($g['base']['school_year_name']) ?>
                                </td>

                                <td>
                                    <?= e($g['base']['grade_level']) ?>
                                </td>

                                <td>
                                    <?= e($g['base']['teacher_name']) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= e($g['base']['subject_code']) ?>
                                    </strong>
                                    <div>
                                        <?= e($g['base']['subject_name']) ?>
                                    </div>
                                </td>

                                <td><?= e($g['Term 1'] ?? '—') ?></td>
                                <td><?= e($g['Term 2'] ?? '—') ?></td>
                                <td><?= e($g['Term 3'] ?? '—') ?></td>

                                <td>
                                    <?= e(
                                        isset($g['Term 3']) &&
                                        $g['Term 3'] !== null &&
                                        $g['Term 3'] !== ''
                                            ? (
                                                (float) $g['Term 3'] >= 75
                                                    ? 'Passed'
                                                    : 'Failed'
                                            )
                                            : ''
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </div>

<?php endif; ?>

<?php require 'includes/footer.php'; ?>
