
<?php
chdir(dirname(__DIR__));

session_start();

require_once dirname(__DIR__) . '/dbconn.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireRole(['System Administrator', 'School Registrar', 'Teacher Adviser', 'Subject Teacher']);

verifyCsrf();

function tleRows(PDO $pdo, string $sql, array $args = []): array
{
    $q = $pdo->prepare($sql);
    $q->execute($args);

    return $q->fetchAll(PDO::FETCH_ASSOC);
}

function tleOne(PDO $pdo, string $sql, array $args = []): ?array
{
    $r = tleRows($pdo, $sql, $args);

    return $r[0] ?? null;
}

function tleId($v): int
{
    return filter_var(
        $v,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    ) ?: 0;
}

$role = $_SESSION['role'];
$manager = in_array($role, ['System Administrator', 'School Registrar'], true);
$uid = (int) $_SESSION['user_id'];

$teacher = tleOne(
    $pdo,
    "SELECT teacher_id
    FROM teachers
    WHERE user_id = ?
        AND employment_status = 'Active'",
    [$uid]
);

$tid = (int) ($teacher['teacher_id'] ?? 0);

$year = tleOne(
    $pdo,
    "SELECT school_year_id, school_year_name
    FROM school_years
    WHERE year_status = 'Current'
    LIMIT 1"
);

$yearId = (int) ($year['school_year_id'] ?? 0);

$notice = '';
$error = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$yearId) {
            throw new RuntimeException('No current school year.');
        }

        $action = $_POST['action'] ?? '';

        if (
            in_array($action, ['component', 'group', 'member', 'rotation'], true)
            && !$manager
        ) {
            throw new RuntimeException(
                'Only Admin or Registrar can manage TLE groups and rotations.'
            );
        }

        if ($action === 'component') {
            $name = trim($_POST['component_name'] ?? '');

            if ($name === '' || mb_strlen($name) > 120) {
                throw new RuntimeException(
                    'Enter a component name (up to 120 characters).'
                );
            }

            $pdo->prepare(
                'INSERT INTO tle_components(component_name) VALUES (?)'
            )->execute([$name]);

            $notice = 'Component added.';
        } elseif ($action === 'group') {
            $sid = tleId($_POST['section_id'] ?? 0);
            $name = trim($_POST['group_name'] ?? '');

            if (
                !$sid
                || $name === ''
                || mb_strlen($name) > 60
                || !tleOne(
                    $pdo,
                    "SELECT section_id
                    FROM class_sections
                    WHERE section_id = ?
                        AND school_year_id = ?
                        AND grade_level IN ('Grade 7', 'Grade 8')",
                    [$sid, $yearId]
                )
            ) {
                throw new RuntimeException('Invalid section or group name.');
            }

            $pdo->prepare(
                'INSERT INTO tle_groups(section_id, group_name) VALUES (?, ?)'
            )->execute([$sid, $name]);

            $notice = 'Group added.';
        } elseif ($action === 'member') {
            $gid = tleId($_POST['group_id'] ?? 0);
            $eid = tleId($_POST['enrollment_id'] ?? 0);

            $valid = tleOne(
                $pdo,
                "SELECT e.enrollment_id
                FROM enrollments e
                JOIN tle_groups tg ON tg.section_id = e.section_id
                JOIN class_sections cs ON cs.section_id = tg.section_id
                WHERE e.enrollment_id = ?
                    AND tg.group_id = ?
                    AND e.school_year_id = ?
                    AND cs.school_year_id = ?
                    AND e.grade_level IN ('Grade 7', 'Grade 8')
                    AND e.enrollment_status NOT IN ('Rejected', 'Withdrawn')",
                [$eid, $gid, $yearId, $yearId]
            );

            if (!$valid) {
                throw new RuntimeException(
                    'Student must be enrolled in the selected group section.'
                );
            }

            $existing = tleOne(
                $pdo,
                'SELECT group_id FROM tle_group_members WHERE enrollment_id = ?',
                [$eid]
            );

            if ($existing && (int) $existing['group_id'] !== $gid) {
                throw new RuntimeException(
                    'Student already belongs to another group. Do not change group without reviewing existing grades.'
                );
            }

            $pdo->prepare(
                'INSERT IGNORE INTO tle_group_members(enrollment_id, group_id) VALUES (?, ?)'
            )->execute([$eid, $gid]);

            $notice = 'Student assigned to group.';
        } elseif ($action === 'rotation') {
            $gid = tleId($_POST['group_id'] ?? 0);
            $term = tleId($_POST['term_no'] ?? 0);
            $cid = tleId($_POST['component_id'] ?? 0);
            $teacherId = tleId($_POST['teacher_id'] ?? 0);

            if (
                $term > 3
                || !tleOne(
                    $pdo,
                    "SELECT tg.group_id
                    FROM tle_groups tg
                    JOIN class_sections cs ON cs.section_id = tg.section_id
                    WHERE tg.group_id = ?
                        AND cs.school_year_id = ?
                        AND cs.grade_level IN ('Grade 7', 'Grade 8')",
                    [$gid, $yearId]
                )
                || !tleOne(
                    $pdo,
                    'SELECT component_id FROM tle_components WHERE component_id = ?',
                    [$cid]
                )
                || !tleOne(
                    $pdo,
                    "SELECT teacher_id
                    FROM teachers
                    WHERE teacher_id = ?
                        AND employment_status = 'Active'",
                    [$teacherId]
                )
            ) {
                throw new RuntimeException(
                    'Invalid group, term, component or teacher.'
                );
            }

            $existing = tleOne(
                $pdo,
                'SELECT rotation_id FROM tle_group_rotations WHERE group_id = ? AND term_no = ?',
                [$gid, $term]
            );

            if ($existing) {
                throw new RuntimeException(
                    'This group already has a rotation for that term. Existing rotation cannot be overwritten here.'
                );
            }

            $pdo->prepare(
                'INSERT INTO tle_group_rotations(group_id, term_no, component_id, teacher_id) VALUES (?, ?, ?, ?)'
            )->execute([$gid, $term, $cid, $teacherId]);

            $notice = 'Rotation created.';
        } elseif ($action === 'grade') {
            if (!$tid) {
                throw new RuntimeException('Active teacher profile required.');
            }

            $rid = tleId($_POST['rotation_id'] ?? 0);
            $eid = tleId($_POST['enrollment_id'] ?? 0);
            $raw = trim((string) ($_POST['grade_value'] ?? ''));

            if (!is_numeric($raw) || ($value = (float) $raw) < 0 || $value > 100) {
                throw new RuntimeException('Grade must be from 0 to 100.');
            }

            $pdo->beginTransaction();

            $rotation = tleOne(
                $pdo,
                "SELECT
                    r.rotation_id,
                    r.term_no,
                    tg.section_id,
                    cs.school_year_id
                FROM tle_group_rotations r
                JOIN tle_groups tg ON tg.group_id = r.group_id
                JOIN class_sections cs ON cs.section_id = tg.section_id
                JOIN tle_group_members m ON m.group_id = tg.group_id
                JOIN enrollments e ON e.enrollment_id = m.enrollment_id
                WHERE r.rotation_id = ?
                    AND e.enrollment_id = ?
                    AND r.teacher_id = ?
                    AND e.section_id = tg.section_id
                    AND e.school_year_id = cs.school_year_id
                    AND cs.school_year_id = ?
                    AND cs.grade_level IN ('Grade 7', 'Grade 8')
                    AND e.enrollment_status NOT IN ('Rejected', 'Withdrawn')
                FOR UPDATE",
                [$rid, $eid, $tid, $yearId]
            );

            if (!$rotation) {
                throw new RuntimeException(
                    'You are not assigned to this student and rotation.'
                );
            }

            $term = 'Term ' . $rotation['term_no'];

            $locked = tleOne(
                $pdo,
                "SELECT l.lock_id
                FROM grade_period_locks l
                JOIN section_subjects ss ON ss.section_subject_id = l.section_subject_id
                JOIN subjects s ON s.subject_id = ss.subject_id
                WHERE ss.section_id = ?
                    AND l.grading_period = ?
                    AND (
                        LOWER(s.subject_name) = 'tle'
                        OR LOWER(s.subject_name) LIKE 'technology and livelihood%'
                    )
                LIMIT 1",
                [(int) $rotation['section_id'], $term]
            );

            if ($locked) {
                throw new RuntimeException('This TLE term is locked.');
            }

            $pdo->prepare(
                'INSERT INTO tle_term_grades(enrollment_id, rotation_id, grade_value, encoded_by)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    grade_value = VALUES(grade_value),
                    encoded_by = VALUES(encoded_by)'
            )->execute([$eid, $rid, $value, $uid]);

            $pdo->commit();

            $notice = 'TLE grade saved.';
        } else {
            throw new RuntimeException('Unknown action.');
        }
    }
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($ex->getMessage());

    $error = $ex instanceof PDOException
        ? 'Database operation failed. Check duplicates and database structure.'
        : $ex->getMessage();
}

$sections = $yearId
    ? tleRows(
        $pdo,
        "SELECT section_id, grade_level, section_name
        FROM class_sections
        WHERE school_year_id = ?
            AND grade_level IN ('Grade 7', 'Grade 8')
        ORDER BY grade_level, section_name",
        [$yearId]
    )
    : [];

$groups = $yearId
    ? tleRows(
        $pdo,
        "SELECT
            tg.group_id,
            tg.group_name,
            tg.section_id,
            cs.grade_level,
            cs.section_name
        FROM tle_groups tg
        JOIN class_sections cs ON cs.section_id = tg.section_id
        WHERE cs.school_year_id = ?
        ORDER BY
            cs.grade_level,
            cs.section_name,
            tg.group_name",
        [$yearId]
    )
    : [];

$students = $yearId
    ? tleRows(
        $pdo,
        "SELECT
            e.enrollment_id,
            e.student_lrn,
            e.section_id,
            CONCAT(s.last_name, ', ', s.first_name) student_name
        FROM enrollments e
        JOIN students s ON s.student_lrn = e.student_lrn
        WHERE e.school_year_id = ?
            AND e.grade_level IN ('Grade 7', 'Grade 8')
            AND e.enrollment_status NOT IN ('Rejected', 'Withdrawn')
        ORDER BY s.last_name, s.first_name",
        [$yearId]
    )
    : [];

$components = tleRows(
    $pdo,
    'SELECT component_id, component_name
    FROM tle_components
    ORDER BY component_name'
);

$teachers = tleRows(
    $pdo,
    "SELECT
        teacher_id,
        CONCAT(last_name, ', ', first_name) teacher_name
    FROM teachers
    WHERE employment_status = 'Active'
    ORDER BY last_name, first_name"
);

$rotations = $yearId
    ? tleRows(
        $pdo,
        "SELECT
            r.rotation_id,
            r.group_id,
            r.term_no,
            r.teacher_id,
            tg.group_name,
            cs.section_name,
            cs.grade_level,
            c.component_name,
            CONCAT(t.last_name, ', ', t.first_name) teacher_name
        FROM tle_group_rotations r
        JOIN tle_groups tg ON tg.group_id = r.group_id
        JOIN class_sections cs ON cs.section_id = tg.section_id
        JOIN tle_components c ON c.component_id = r.component_id
        JOIN teachers t ON t.teacher_id = r.teacher_id
        WHERE cs.school_year_id = ?
        ORDER BY
            cs.grade_level,
            cs.section_name,
            tg.group_name,
            r.term_no",
        [$yearId]
    )
    : [];

$roster = $yearId
    ? tleRows(
        $pdo,
        "SELECT
            r.rotation_id,
            r.term_no,
            r.teacher_id,
            tg.group_name,
            e.enrollment_id,
            e.student_lrn,
            CONCAT(s.last_name, ', ', s.first_name) student_name,
            g.grade_value
        FROM tle_group_rotations r
        JOIN tle_groups tg ON tg.group_id = r.group_id
        JOIN class_sections cs ON cs.section_id = tg.section_id
        JOIN tle_group_members m ON m.group_id = tg.group_id
        JOIN enrollments e
            ON e.enrollment_id = m.enrollment_id
            AND e.section_id = tg.section_id
            AND e.school_year_id = cs.school_year_id
        JOIN students s ON s.student_lrn = e.student_lrn
        LEFT JOIN tle_term_grades g
            ON g.enrollment_id = e.enrollment_id
            AND g.rotation_id = r.rotation_id
        WHERE cs.school_year_id = ?
            AND e.enrollment_status NOT IN ('Rejected', 'Withdrawn')
        ORDER BY
            r.term_no,
            tg.group_name,
            s.last_name",
        [$yearId]
    )
    : [];
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>TLE Group Rotation | ELTNHS</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font: 14px Arial, sans-serif;
            background: #f3f5f8;
            color: #263342;
            margin: 0;
        }

        main {
            max-width: 1150px;
            margin: 25px auto;
            padding: 0 18px;
        }

        header {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .card {
            background: white;
            border: 1px solid #e0e6ed;
            border-radius: 10px;
            padding: 20px;
            margin: 16px 0;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 15px;
        }

        label {
            display: block;
            font-weight: bold;
            margin: 10px 0 5px;
        }

        input,
        select,
        button {
            width: 100%;
            padding: 9px;
            border: 1px solid #cbd5e0;
            border-radius: 5px;
            font: inherit;
        }

        button {
            background: #2877bb;
            color: white;
            border: 0;
            margin-top: 12px;
            cursor: pointer;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        td,
        th {
            text-align: left;
            border-bottom: 1px solid #e5e9ef;
            padding: 9px;
        }

        a {
            color: #2877bb;
        }

        .scroll {
            overflow: auto;
        }

        .error {
            color: #a22;
        }

        .ok {
            color: #17683d;
        }

        .muted {
            color: #68778a;
        }
    </style>
</head>

<body>
    <main>
        <header>
            <div>
                <h1>Grade 7–8 TLE Group Rotation</h1>
                <p class="muted">
                    Current school year:
                    <?= e($year['school_year_name'] ?? 'Not set') ?>
                </p>
            </div>

            <a href="../shared/dashboard.php">Back to Dashboard</a>
        </header>

        <?php if ($error): ?>
            <p class="error"><?= e($error) ?></p>
        <?php endif; ?>

        <?php if ($notice): ?>
            <p class="ok"><?= e($notice) ?></p>
        <?php endif; ?>

        <?php if ($manager): ?>
            <div class="grid">
                <section class="card">
                    <h2>1. Add TLE component</h2>

                    <form method="post">
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e(csrfToken()) ?>"
                        >

                        <input type="hidden" name="action" value="component">

                        <label>School-approved component</label>
                        <input
                            name="component_name"
                            maxlength="120"
                            required
                        >

                        <button>Add component</button>
                    </form>
                </section>

                <section class="card">
                    <h2>2. Create group</h2>

                    <form method="post">
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e(csrfToken()) ?>"
                        >

                        <input type="hidden" name="action" value="group">

                        <label>Section</label>
                        <select name="section_id" required>
                            <option value="">Select section</option>

                            <?php foreach ($sections as $s): ?>
                                <option value="<?= $s['section_id'] ?>">
                                    <?= e($s['grade_level'] . ' / ' . $s['section_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label>Group name</label>
                        <input
                            name="group_name"
                            maxlength="60"
                            required
                        >

                        <button>Create group</button>
                    </form>
                </section>

                <section class="card">
                    <h2>3. Add student to group</h2>

                    <form method="post">
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e(csrfToken()) ?>"
                        >

                        <input type="hidden" name="action" value="member">

                        <label>Group</label>
                        <select name="group_id" required>
                            <option value="">Select group</option>

                            <?php foreach ($groups as $g): ?>
                                <option value="<?= $g['group_id'] ?>">
                                    <?= e(
                                        $g['grade_level']
                                        . ' / '
                                        . $g['section_name']
                                        . ' / '
                                        . $g['group_name']
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label>Student</label>
                        <select name="enrollment_id" required>
                            <option value="">Select student in same section</option>

                            <?php foreach ($students as $s): ?>
                                <option value="<?= $s['enrollment_id'] ?>">
                                    <?= e(
                                        $s['student_name']
                                        . ' ('
                                        . $s['student_lrn']
                                        . ') — section #'
                                        . $s['section_id']
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button>Assign student</button>
                    </form>
                </section>

                <section class="card">
                    <h2>4. Assign term rotation</h2>

                    <form method="post">
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e(csrfToken()) ?>"
                        >

                        <input type="hidden" name="action" value="rotation">

                        <label>Group</label>
                        <select name="group_id" required>
                            <option value="">Select group</option>

                            <?php foreach ($groups as $g): ?>
                                <option value="<?= $g['group_id'] ?>">
                                    <?= e($g['section_name'] . ' / ' . $g['group_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label>Term</label>
                        <select name="term_no">
                            <option value="1">Term 1</option>
                            <option value="2">Term 2</option>
                            <option value="3">Term 3</option>
                        </select>

                        <label>Component</label>
                        <select name="component_id" required>
                            <option value="">Select component</option>

                            <?php foreach ($components as $c): ?>
                                <option value="<?= $c['component_id'] ?>">
                                    <?= e($c['component_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label>Teacher</label>
                        <select name="teacher_id" required>
                            <option value="">Select teacher</option>

                            <?php foreach ($teachers as $t): ?>
                                <option value="<?= $t['teacher_id'] ?>">
                                    <?= e($t['teacher_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button>Assign rotation</button>
                    </form>
                </section>
            </div>
        <?php endif; ?>

        <section class="card">
            <h2>Assigned rotations</h2>

            <div class="scroll">
                <table>
                    <tr>
                        <th>Section</th>
                        <th>Group</th>
                        <th>Term</th>
                        <th>Component</th>
                        <th>Teacher</th>
                    </tr>

                    <?php foreach ($rotations as $r): ?>
                        <tr>
                            <td>
                                <?= e($r['grade_level'] . ' / ' . $r['section_name']) ?>
                            </td>
                            <td><?= e($r['group_name']) ?></td>
                            <td>Term <?= $r['term_no'] ?></td>
                            <td><?= e($r['component_name']) ?></td>
                            <td><?= e($r['teacher_name']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </section>

        <section class="card">
            <h2>Student TLE grades</h2>

            <p class="muted">
                Only the teacher assigned to the group rotation can save grades.
                Each student has one grade per assigned group/term.
                Grade locks are checked against a matching section TLE subject.
            </p>

            <div class="scroll">
                <table>
                    <tr>
                        <th>Term</th>
                        <th>Group</th>
                        <th>LRN</th>
                        <th>Student</th>
                        <th>Grade</th>
                        <th>Action</th>
                    </tr>

                    <?php foreach ($roster as $r): ?>
                        <?php if (!$manager && (int) $r['teacher_id'] !== $tid) continue; ?>

                        <tr>
                            <td>Term <?= $r['term_no'] ?></td>
                            <td><?= e($r['group_name']) ?></td>
                            <td><?= e($r['student_lrn']) ?></td>
                            <td><?= e($r['student_name']) ?></td>
                            <td><?= e($r['grade_value'] ?? '—') ?></td>

                            <td>
                                <?php if ((int) $r['teacher_id'] === $tid): ?>
                                    <form
                                        method="post"
                                        style="display:flex;gap:6px;min-width:160px"
                                    >
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(csrfToken()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="grade"
                                        >

                                        <input
                                            type="hidden"
                                            name="rotation_id"
                                            value="<?= $r['rotation_id'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="enrollment_id"
                                            value="<?= $r['enrollment_id'] ?>"
                                        >

                                        <input
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            name="grade_value"
                                            value="<?= e($r['grade_value'] ?? '') ?>"
                                            required
                                        >

                                        <button>Save</button>
                                    </form>
                                <?php else: ?>
                                    View only
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
