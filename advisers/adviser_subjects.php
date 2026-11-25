<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'includes/functions.php';

requireRole(['Teacher Adviser']);

const SCHEDULE_DAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

/** Turns saved text such as "Mon-Fri", "Mon, Wed, Fri" or "MWF" into ['Mon','Wed','Fri']. */
function parseDays(?string $str): array
{
    $s = strtolower(trim((string)$str));
    if ($s === '') {
        return [];
    }
    $order = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
    $s = strtr($s, [
        'monday' => 'mon', 'tuesday' => 'tue', 'tues' => 'tue', 'wednesday' => 'wed',
        'thursday' => 'thu', 'thurs' => 'thu', 'thur' => 'thu', 'friday' => 'fri', 'saturday' => 'sat',
    ]);
    $found = [];
    $s = preg_replace_callback('/(mon|tue|wed|thu|fri|sat)\s*[-–—]\s*(mon|tue|wed|thu|fri|sat)/u', function ($m) use (&$found, $order) {
        $a = array_search($m[1], $order);
        $b = array_search($m[2], $order);
        for ($i = $a; $i <= $b; $i++) {
            $found[] = $order[$i];
        }
        return ' ';
    }, $s);
    $s = preg_replace_callback('/\bm\s*-\s*f\b/', function () use (&$found) {
        array_push($found, 'mon', 'tue', 'wed', 'thu', 'fri');
        return ' ';
    }, $s);
    $s = preg_replace_callback('/mon|tue|wed|thu|fri|sat/', function ($m) use (&$found) {
        $found[] = $m[0];
        return ' ';
    }, $s);
    $letters = ['m' => 'mon', 't' => 'tue', 'w' => 'wed', 'th' => 'thu', 'f' => 'fri', 's' => 'sat'];
    foreach (preg_split('/[^a-z]+/', $s, -1, PREG_SPLIT_NO_EMPTY) as $tok) {
        if (preg_match('/^(th|[mtwfs])+$/', $tok)) {
            preg_match_all('/th|[mtwfs]/', $tok, $c);
            foreach ($c[0] as $ch) {
                $found[] = $letters[$ch];
            }
        }
    }
    return array_map('ucfirst', array_values(array_intersect($order, array_unique($found))));
}

function formatDays(array $days): string
{
    $days = array_values(array_intersect(SCHEDULE_DAYS, $days));
    return $days === ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'] ? 'Mon-Fri' : implode(', ', $days);
}

function timeLabel(string $hhmm): string
{
    return date('g:i A', strtotime($hhmm));
}

/** <option> list in 30-minute steps. A saved value outside the grid is kept so nothing is silently lost. */
function timeOptions(int $fromMin, int $toMin, string $selected): string
{
    $values = [];
    for ($m = $fromMin; $m <= $toMin; $m += 30) {
        $values[] = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
    }
    if ($selected !== '' && !in_array($selected, $values, true)) {
        $values[] = $selected;
        sort($values);
    }
    $html = '<option value="">--:--</option>';
    foreach ($values as $v) {
        $label = timeLabel($v);
        $html .= '<option value="' . e($v) . '" data-label="' . e($label) . '"' . ($v === $selected ? ' selected' : '') . '>' . e($label) . '</option>';
    }
    return $html;
}

/** Two schedule entries (days, start, end as HH:MM) clash when they share a day and their times intersect. */
function schedulesOverlap(array $a, array $b): bool
{
    return $a['start'] !== '' && $b['start'] !== ''
        && array_intersect($a['days'], $b['days'])
        && $a['start'] < $b['end']
        && $a['end'] > $b['start'];
}

/** Saved schedules of every OTHER section in the same school year (any grade level). */
function loadExternalSchedules(PDO $pdo, array $section): array
{
    $q = $pdo->prepare("
        SELECT ss.teacher_id, ss.room, ss.schedule_days, ss.start_time, ss.end_time,
               s.subject_name, cs.grade_level, cs.section_name
        FROM section_subjects ss
        JOIN class_sections cs ON cs.section_id = ss.section_id
        JOIN subjects s ON s.subject_id = ss.subject_id
        WHERE cs.school_year_id = ?
          AND ss.section_id <> ?
          AND ss.schedule_days IS NOT NULL AND ss.start_time IS NOT NULL AND ss.end_time IS NOT NULL
    ");
    $q->execute([$section['school_year_id'], $section['section_id']]);
    $out = [];
    foreach ($q->fetchAll() as $r) {
        $days = parseDays($r['schedule_days']);
        if (!$days) {
            continue;
        }
        $out[] = [
            'teacher' => $r['teacher_id'] !== null ? (string)$r['teacher_id'] : '',
            'room'    => strtolower(trim((string)$r['room'])),
            'days'    => $days,
            'start'   => substr($r['start_time'], 0, 5),
            'end'     => substr($r['end_time'], 0, 5),
            'label'   => $r['subject_name'] . ' (' . $r['grade_level'] . ' - ' . $r['section_name'] . ')',
        ];
    }
    return $out;
}

/** Returns an error message for the first clash found, or null when the plan is clean. */
function findScheduleConflict(array $plan, array $external, array $teacherNames): ?string
{
    $ids = array_keys($plan);
    foreach ($ids as $i => $idA) {
        $a = $plan[$idA];
        if ($a['start'] === '') {
            continue;
        }
        // Same section: a class cannot be in two subjects at once
        foreach (array_slice($ids, $i + 1) as $idB) {
            $b = $plan[$idB];
            if (schedulesOverlap($a, $b)) {
                return $a['name'] . ' and ' . $b['name'] . ' overlap (' . formatDays(array_values(array_intersect($a['days'], $b['days']))) . ', '
                    . timeLabel($a['start']) . ' - ' . timeLabel($a['end']) . '). A section cannot have two subjects at the same time.';
            }
        }
        // Other sections / grade levels: same teacher or same room
        foreach ($external as $x) {
            if (!schedulesOverlap($a, $x)) {
                continue;
            }
            if ($a['teacher'] !== null && (string)$a['teacher'] === $x['teacher']) {
                return ($teacherNames[$a['teacher']] ?? 'This teacher') . ' is already teaching ' . $x['label'] . ' at '
                    . timeLabel($x['start']) . ' - ' . timeLabel($x['end']) . ' (' . formatDays($x['days']) . '), which overlaps with ' . $a['name'] . '.';
            }
            if ($a['room'] !== '' && strtolower($a['room']) === $x['room']) {
                return 'Room ' . $a['room'] . ' is already used by ' . $x['label'] . ' at '
                    . timeLabel($x['start']) . ' - ' . timeLabel($x['end']) . ' (' . formatDays($x['days']) . '), which overlaps with ' . $a['name'] . '.';
            }
        }
    }
    return null;
}

$userId = (int)$_SESSION['user_id'];
$q = $pdo->prepare('SELECT teacher_id FROM teachers WHERE user_id = ?');
$q->execute([$userId]);
$teacherId = (int)$q->fetchColumn();

if (!$teacherId) {
    http_response_code(403);
    exit('Your staff profile is not linked to an Adviser record. Ask the System Administrator to check your account.');
}

$hasLegacySemester = (bool)$pdo->query("SHOW COLUMNS FROM section_subjects LIKE 'semester'")->fetch();
$message = '';
$error = '';

$sectionsQ = $pdo->prepare("
    SELECT cs.section_id, cs.grade_level, cs.section_name, sy.school_year_name
    FROM class_sections cs
    JOIN school_years sy ON sy.school_year_id = cs.school_year_id
    WHERE cs.adviser_teacher_id = ? AND sy.year_status = 'Current'
    ORDER BY sy.school_year_name DESC, cs.grade_level, cs.section_name
");
$sectionsQ->execute([$teacherId]);
$sections = $sectionsQ->fetchAll();

$teachers = $pdo->query("
    SELECT t.teacher_id, t.first_name, t.middle_name, t.last_name
    FROM teachers t
    JOIN users u ON u.user_id = t.user_id
    WHERE t.employment_status = 'Active'
      AND u.account_status = 'Active'
      AND u.role IN ('Subject Teacher', 'Teacher Adviser')
    ORDER BY t.last_name, t.first_name
")->fetchAll();

$teacherNames = [];
foreach ($teachers as $t) {
    $teacherNames[(string)$t['teacher_id']] = trim($t['first_name'] . ' ' . $t['last_name']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    try {
        $sectionId = (int)($_POST['section_id'] ?? 0);
        $check = $pdo->prepare('SELECT cs.* FROM class_sections cs JOIN school_years sy ON sy.school_year_id=cs.school_year_id WHERE cs.section_id = ? AND cs.adviser_teacher_id = ? AND sy.year_status = \'Current\'');
        $check->execute([$sectionId, $teacherId]);
        $section = $check->fetch();

        if (!$section) {
            throw new Exception('You can only manage subjects under your own advisory section.');
        }

        $action = $_POST['action'] ?? '';

        $validTeacher = $pdo->prepare("
            SELECT t.teacher_id
            FROM teachers t
            JOIN users u ON u.user_id = t.user_id
            WHERE t.teacher_id = ?
              AND t.employment_status = 'Active'
              AND u.account_status = 'Active'
              AND u.role IN ('Subject Teacher', 'Teacher Adviser')
        ");

        if ($action === 'add_subject') {
            $subjectId = (int)($_POST['subject_id'] ?? 0);
            $teacherChoice = trim($_POST['teacher_id'] ?? '');

            $sub = $pdo->prepare("SELECT subject_id FROM subjects WHERE subject_id = ? AND grade_level = ? AND subject_status = 'Active'");
            $sub->execute([$subjectId, $section['grade_level']]);
            if (!$sub->fetchColumn()) {
                throw new Exception('Choose a subject from the list for ' . $section['grade_level'] . '.');
            }

            $dup = $pdo->prepare('SELECT 1 FROM section_subjects WHERE section_id = ? AND subject_id = ?');
            $dup->execute([$sectionId, $subjectId]);
            if ($dup->fetchColumn()) {
                throw new Exception('That subject is already in your advisory section.');
            }

            $assignedTeacher = null;
            if ($teacherChoice !== '') {
                $validTeacher->execute([(int)$teacherChoice]);
                $assignedTeacher = $validTeacher->fetchColumn();
                if (!$assignedTeacher) {
                    throw new Exception('Select an active Subject Teacher or Teacher Adviser account.');
                }
            }

            $pdo->prepare($hasLegacySemester
                ? "INSERT INTO section_subjects(section_id, subject_id, teacher_id, semester) VALUES(?, ?, ?, 'Full Year')"
                : "INSERT INTO section_subjects(section_id, subject_id, teacher_id) VALUES(?, ?, ?)")
                ->execute([$sectionId, $subjectId, $assignedTeacher]);

            $message = 'Subject added. If the teacher is still TBA, you can assign one later from the table below.';

        } elseif ($action === 'bulk_update') {
            // Start from what is saved, then overlay what was submitted, so every check sees the final picture.
            $cur = $pdo->prepare('
                SELECT ss.section_subject_id, ss.teacher_id, ss.schedule_days, ss.start_time, ss.end_time, ss.room, s.subject_name
                FROM section_subjects ss
                JOIN subjects s ON s.subject_id = ss.subject_id
                WHERE ss.section_id = ?
            ');
            $cur->execute([$sectionId]);
            $plan = [];
            foreach ($cur->fetchAll() as $r) {
                $plan[(int)$r['section_subject_id']] = [
                    'name'    => $r['subject_name'],
                    'teacher' => $r['teacher_id'] !== null ? (int)$r['teacher_id'] : null,
                    'days'    => parseDays($r['schedule_days']),
                    'start'   => $r['start_time'] ? substr($r['start_time'], 0, 5) : '',
                    'end'     => $r['end_time'] ? substr($r['end_time'], 0, 5) : '',
                    'room'    => trim((string)$r['room']),
                ];
            }

            $submitted = $_POST['rows'] ?? [];
            foreach ($submitted as $rowId => $rowData) {
                $rowId = (int)$rowId;
                if (!isset($plan[$rowId])) {
                    throw new Exception('A subject row does not belong to this advisory section.');
                }
                $name = $plan[$rowId]['name'];

                $teacherChoice = trim($rowData['teacher_id'] ?? '');
                $teacherIdChoice = null;
                if ($teacherChoice !== '') {
                    $validTeacher->execute([(int)$teacherChoice]);
                    $teacherIdChoice = $validTeacher->fetchColumn();
                    if (!$teacherIdChoice) {
                        throw new Exception('Select a valid active teaching account for ' . $name . '.');
                    }
                    $teacherIdChoice = (int)$teacherIdChoice;
                }

                $days = array_values(array_intersect(SCHEDULE_DAYS, (array)($rowData['days'] ?? [])));
                $start = trim($rowData['start_time'] ?? '');
                $end = trim($rowData['end_time'] ?? '');
                $room = trim($rowData['room'] ?? '');

                foreach ([$start, $end] as $tm) {
                    if ($tm !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $tm)) {
                        throw new Exception('Invalid time for ' . $name . '.');
                    }
                }
                if (($days || $start !== '' || $end !== '') && (!$days || $start === '' || $end === '')) {
                    throw new Exception($name . ': choose the days, a start time and an end time together (or leave all three empty).');
                }
                if ($start !== '' && $start >= $end) {
                    throw new Exception($name . ': end time must be later than start time.');
                }

                $plan[$rowId] = ['name' => $name, 'teacher' => $teacherIdChoice, 'days' => $days, 'start' => $start, 'end' => $end, 'room' => $room];
            }

            $conflict = findScheduleConflict($plan, loadExternalSchedules($pdo, $section), $teacherNames);
            if ($conflict) {
                throw new Exception($conflict);
            }

            $pdo->beginTransaction();
            $upd = $pdo->prepare('UPDATE section_subjects SET teacher_id = ?, schedule_days = ?, start_time = ?, end_time = ?, room = ? WHERE section_subject_id = ?');
            foreach ($submitted as $rowId => $_) {
                $p = $plan[(int)$rowId];
                $upd->execute([
                    $p['teacher'],
                    $p['days'] ? formatDays($p['days']) : null,
                    $p['start'] ?: null,
                    $p['end'] ?: null,
                    $p['room'] ?: null,
                    (int)$rowId,
                ]);
            }
            $pdo->commit();
            $message = 'All teacher assignments and schedules were saved.';

        } else {
            throw new Exception('Unknown action.');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e instanceof PDOException
            ? 'Unable to save. Check for a duplicate subject code or assignment. Details: ' . ($e->errorInfo[2] ?? $e->getMessage())
            : $e->getMessage();
    }
}

$selectedSection = (int)($_GET['section_id'] ?? $_POST['section_id'] ?? ($sections[0]['section_id'] ?? 0));
$validIds = array_map(fn($s) => (int)$s['section_id'], $sections);
if (!in_array($selectedSection, $validIds, true)) {
    $selectedSection = 0;
}

$assignments = [];
$availableSubjects = [];
$external = [];
$selectedSectionRow = null;
foreach ($sections as $s) {
    if ((int)$s['section_id'] === $selectedSection) {
        $selectedSectionRow = $s;
    }
}
if ($selectedSection) {
    // Subjects of this grade level that are not yet in the section
    $q = $pdo->prepare("
        SELECT s.subject_id, s.subject_code, s.subject_name
        FROM subjects s
        WHERE s.grade_level = ?
          AND s.subject_status = 'Active'
          AND s.subject_id NOT IN (SELECT subject_id FROM section_subjects WHERE section_id = ?)
        ORDER BY s.subject_name
    ");
    $q->execute([$selectedSectionRow['grade_level'], $selectedSection]);
    $availableSubjects = $q->fetchAll();

    $sec = $pdo->prepare('SELECT * FROM class_sections WHERE section_id = ?');
    $sec->execute([$selectedSection]);
    $external = loadExternalSchedules($pdo, $sec->fetch());

    $q = $pdo->prepare("
        SELECT ss.section_subject_id, ss.schedule_days, ss.start_time, ss.end_time, ss.room,
               s.subject_code, s.subject_name, t.teacher_id,
               CONCAT_WS(' ', t.first_name, t.middle_name, t.last_name) AS teacher_name
        FROM section_subjects ss
        JOIN subjects s ON s.subject_id = ss.subject_id
        LEFT JOIN teachers t ON t.teacher_id = ss.teacher_id
        WHERE ss.section_id = ? 
        ORDER BY s.subject_name
    ");
    $q->execute([$selectedSection]);
    $assignments = $q->fetchAll();
}

require 'includes/header.php';
?>

<div class="page-heading">
    <div>
        <h1>Class Setup</h1>
        <p>Pick subjects for your advisory section from the grade-level list and assign active subject teachers. You can leave a teacher as TBA and assign one later.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<?php if (!$sections): ?>
    <div class="card p-4">No advisory section is assigned to you yet. Ask the Registrar to assign you as an Adviser.</div>
<?php else: ?>
    <div class="card p-3 mb-3">
        <label class="form-label">Choose your advisory section</label>
        <form method="get">
            <select class="form-select" name="section_id" onchange="this.form.submit()">
                <?php foreach ($sections as $s): ?>
                    <option value="<?= $s['section_id'] ?>" <?= $selectedSection === (int)$s['section_id'] ? 'selected' : '' ?>>
                        <?= e($s['grade_level'] . ' - ' . $s['section_name'] . ' · ' . $s['school_year_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if ($selectedSection): ?>
        <details class="card p-3 mb-3">
            <summary class="fw-bold">Create Subject</summary>
            <div class="mt-3">
                <h5>Add a Subject</h5>
                <form method="post" class="row g-2">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="action" value="add_subject">
                    <input type="hidden" name="section_id" value="<?= $selectedSection ?>">

                    <div class="col-md-4">
                        <label class="form-label">Subject</label>
                        <select class="form-select" name="subject_id" required <?= $availableSubjects ? '' : 'disabled' ?>>
                            <option value=""><?= $availableSubjects ? 'Choose a subject' : 'No subjects left to add' ?></option>
                            <?php foreach ($availableSubjects as $sub): ?>
                                <option value="<?= (int)$sub['subject_id'] ?>"><?= e($sub['subject_name'] . ' (' . $sub['subject_code'] . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">School Year</label>
                        <input class="form-control" value="Full Year" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Subject Teacher</label>
                        <select class="form-select" name="teacher_id">
                            <option value="">TBA - assign later</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?= $t['teacher_id'] ?>">
                                    <?= e($t['last_name'] . ', ' . $t['first_name'] . ' ' . ($t['middle_name'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100" <?= $availableSubjects ? '' : 'disabled' ?>>Add</button>
                    </div>
                </form>
            </div>
        </details>

        <div class="card p-3">
            <h5>Subjects in My Advisory Section</h5>
            <p class="small text-muted">Set the assigned teacher and schedule, then save everything once. Time slots already taken by another subject, the same teacher, or the same room are blocked out.</p>

            <form method="post" id="setupForm">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="action" value="bulk_update">
                <input type="hidden" name="section_id" value="<?= $selectedSection ?>">

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Subject</th>
                                <th>Assigned Teacher</th>
                                <th>Schedule</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $i => $a):
                                $rid = (int)$a['section_subject_id'];
                                $startVal = $a['start_time'] ? substr($a['start_time'], 0, 5) : '';
                                $endVal = $a['end_time'] ? substr($a['end_time'], 0, 5) : '';
                                $savedDays = parseDays($a['schedule_days']);
                            ?>
                                <tr data-row data-label="<?= e($a['subject_name']) ?>">
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <strong><?= e($a['subject_name']) ?></strong>
                                        <div class="small text-muted"><?= e($a['subject_code']) ?></div>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm t-sel" name="rows[<?= $rid ?>][teacher_id]">
                                            <option value="">TBA</option>
                                            <?php foreach ($teachers as $t): ?>
                                                <option value="<?= $t['teacher_id'] ?>" <?= $a['teacher_id'] == $t['teacher_id'] ? 'selected' : '' ?>>
                                                    <?= e($t['last_name'] . ', ' . $t['first_name'] . ' ' . ($t['middle_name'] ?? '')) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td style="min-width: 300px;">
                                        <div class="d-flex flex-wrap gap-2 mb-2">
                                            <?php foreach (SCHEDULE_DAYS as $d): ?>
                                                <label class="small d-flex align-items-center gap-1 mb-0">
                                                    <input class="form-check-input mt-0 day-cb" type="checkbox" name="rows[<?= $rid ?>][days][]" value="<?= $d ?>" <?= in_array($d, $savedDays, true) ? 'checked' : '' ?>>
                                                    <?= $d ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="d-flex gap-1">
                                            <select class="form-select form-select-sm s-sel" name="rows[<?= $rid ?>][start_time]" aria-label="Start time"><?= timeOptions(6 * 60, 17 * 60 + 30, $startVal) ?></select>
                                            <select class="form-select form-select-sm e-sel" name="rows[<?= $rid ?>][end_time]" aria-label="End time"><?= timeOptions(6 * 60 + 30, 18 * 60, $endVal) ?></select>
                                        </div>
                                        <input class="form-control form-control-sm mt-1 room-in" name="rows[<?= $rid ?>][room]" value="<?= e($a['room'] ?? '') ?>" placeholder="Room">
                                        <div class="small text-danger mt-1 sched-note"></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (!$assignments): ?>
                                <tr>
                                    <td colspan="4" class="text-muted">No subjects have been added yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($assignments): ?>
                    <button class="btn btn-primary">Save All Updates</button>
                <?php endif; ?>
            </form>

            <script type="application/json" id="extSchedules"><?= json_encode($external, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
            <script>
            (function () {
                var form = document.getElementById('setupForm');
                if (!form) return;
                var ext = JSON.parse(document.getElementById('extSchedules').textContent || '[]');
                var rows = Array.prototype.slice.call(form.querySelectorAll('tr[data-row]'));
                var toMin = function (t) { var p = t.split(':'); return (+p[0]) * 60 + (+p[1]); };
                var clash = function (a1, a2, b1, b2) { return a1 < b2 && a2 > b1; };

                function read(tr) {
                    return {
                        days: Array.prototype.map.call(tr.querySelectorAll('.day-cb:checked'), function (c) { return c.value; }),
                        teacher: tr.querySelector('.t-sel').value,
                        room: tr.querySelector('.room-in').value.trim().toLowerCase(),
                        s: tr.querySelector('.s-sel'),
                        e: tr.querySelector('.e-sel'),
                        label: tr.getAttribute('data-label')
                    };
                }

                function busyFor(tr, st) {
                    var busy = [];
                    if (!st.days.length) return busy;
                    var share = function (d) { return d.some(function (x) { return st.days.indexOf(x) > -1; }); };
                    rows.forEach(function (o) {
                        if (o === tr) return;
                        var os = read(o);
                        if (os.s.value && os.e.value && share(os.days)) {
                            busy.push({ a: toMin(os.s.value), b: toMin(os.e.value), why: 'overlaps ' + os.label + ' in this section' });
                        }
                    });
                    ext.forEach(function (x) {
                        if (!share(x.days)) return;
                        var sameTeacher = st.teacher !== '' && x.teacher === st.teacher;
                        var sameRoom = st.room !== '' && x.room === st.room;
                        if (sameTeacher || sameRoom) {
                            busy.push({ a: toMin(x.start), b: toMin(x.end), why: (sameTeacher ? 'teacher is busy with ' : 'room is used by ') + x.label });
                        }
                    });
                    return busy;
                }

                function mark(opt, disabled, taken) {
                    opt.disabled = disabled;
                    opt.textContent = opt.getAttribute('data-label') + (disabled && taken ? ' (taken)' : '');
                }

                function refresh() {
                    rows.forEach(function (tr) {
                        var st = read(tr), busy = busyFor(tr, st);
                        var sv = st.s.value ? toMin(st.s.value) : null;
                        var ev = st.e.value ? toMin(st.e.value) : null;
                        var hit = function (a, b) { return busy.filter(function (x) { return clash(a, b, x.a, x.b); })[0]; };

                        Array.prototype.forEach.call(st.s.options, function (o) {
                            if (!o.value) return;
                            var t = toMin(o.value);
                            if (ev !== null && t >= ev) { mark(o, true, false); return; }
                            mark(o, !!hit(t, ev !== null ? ev : t + 30), true);
                        });
                        Array.prototype.forEach.call(st.e.options, function (o) {
                            if (!o.value) return;
                            var t = toMin(o.value);
                            if (sv !== null && t <= sv) { mark(o, true, false); return; }
                            mark(o, !!hit(sv !== null ? sv : t - 30, t), true);
                        });

                        var problem = null;
                        if (sv !== null && ev !== null) {
                            if (ev <= sv) problem = 'End time must be later than start time.';
                            else { var h = hit(sv, ev); if (h) problem = 'This time is not available: ' + h.why + '.'; }
                        }
                        st.s.classList.toggle('is-invalid', !!problem);
                        st.e.classList.toggle('is-invalid', !!problem);
                        tr.querySelector('.sched-note').textContent = problem || '';
                    });
                }

                form.addEventListener('change', refresh);
                form.addEventListener('input', refresh);
                form.addEventListener('submit', function (ev) {
                    refresh();
                    if (form.querySelector('.is-invalid')) {
                        ev.preventDefault();
                        alert('Please fix the highlighted schedule conflicts before saving.');
                    }
                });
                refresh();
            })();
            </script>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>