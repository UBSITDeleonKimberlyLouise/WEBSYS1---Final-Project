<?php
chdir(dirname(__DIR__));

session_start();
require_once dirname(__DIR__) . '/dbconn.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireRole(['System Administrator', 'School Registrar']);
verifyCsrf();

function shsFetch(PDO $pdo, string $sql, array $params = []): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function shsOne(PDO $pdo, string $sql, array $params = []): ?array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}
function shsInt($value): int { return filter_var($value, FILTER_VALIDATE_INT) ?: 0; }

$error = '';
$notice = '';
$current = shsOne($pdo, "SELECT school_year_id, school_year_name FROM school_years WHERE year_status='Current' LIMIT 1");
$yearId = $current ? (int)$current['school_year_id'] : 0;
$tracks = ['Academic', 'TechPro'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!$yearId) throw new RuntimeException('Set a Current school year first.');
        $action = $_POST['action'] ?? '';
        if ($action === 'create_cluster') {
            $track = $_POST['track'] ?? '';
            $name = trim($_POST['cluster_name'] ?? '');
            if (!in_array($track, $tracks, true) || $name === '' || strlen($name) > 120) {
                throw new RuntimeException('Select a valid track and enter a cluster name (up to 120 characters).');
            }
            $stmt = $pdo->prepare('INSERT INTO elective_clusters (cluster_name, track) VALUES (?, ?)');
            $stmt->execute([$name, $track]);
            $notice = 'Elective cluster created.';
        } elseif ($action === 'link_subject') {
            $clusterId = shsInt($_POST['cluster_id'] ?? 0);
            $subjectId = shsInt($_POST['subject_id'] ?? 0);
            $cluster = shsOne($pdo, 'SELECT * FROM elective_clusters WHERE cluster_id=? AND is_active=1', [$clusterId]);
            $subject = shsOne($pdo, "SELECT * FROM subjects WHERE subject_id=? AND grade_level IN ('Grade 11','Grade 12') AND subject_status='Active'", [$subjectId]);
            if (!$cluster || !$subject) throw new RuntimeException('Select an active cluster and a Grade 11/12 subject.');
            $stmt = $pdo->prepare('INSERT IGNORE INTO subject_elective_clusters (subject_id,cluster_id) VALUES (?,?)');
            $stmt->execute([$subjectId, $clusterId]);
            $notice = 'Subject linked to cluster.';
        } elseif ($action === 'assign_elective') {
            $enrollmentId = shsInt($_POST['enrollment_id'] ?? 0);
            $sectionSubjectId = shsInt($_POST['section_subject_id'] ?? 0);
            $pdo->beginTransaction();
            $enrollment = shsOne($pdo, "SELECT e.enrollment_id,e.section_id,e.grade_level,e.shs_track,e.school_year_id FROM enrollments e WHERE e.enrollment_id=? AND e.school_year_id=? AND e.grade_level IN ('Grade 11','Grade 12') FOR UPDATE", [$enrollmentId, $yearId]);
            if (!$enrollment || !$enrollment['section_id'] || !in_array($enrollment['shs_track'], $tracks, true)) {
                throw new RuntimeException('Student must have a current-year SHS enrollment, section, and Academic/TechPro track.');
            }
            $assignment = shsOne($pdo, "SELECT ss.section_subject_id,s.grade_level FROM section_subjects ss JOIN class_sections cs ON cs.section_id=ss.section_id JOIN subjects s ON s.subject_id=ss.subject_id WHERE ss.section_subject_id=? AND ss.section_id=? AND cs.school_year_id=? AND cs.shs_track=? AND s.grade_level=? AND EXISTS (SELECT 1 FROM subject_elective_clusters sec JOIN elective_clusters ec ON ec.cluster_id=sec.cluster_id WHERE sec.subject_id=ss.subject_id AND ec.track=? AND ec.is_active=1)", [$sectionSubjectId, $enrollment['section_id'], $yearId, $enrollment['shs_track'], $enrollment['grade_level'], $enrollment['shs_track']]);
            if (!$assignment) throw new RuntimeException('Selected elective is not assigned to this section, grade, and track.');
            $stmt = $pdo->prepare('INSERT IGNORE INTO student_electives (enrollment_id,section_subject_id) VALUES (?,?)');
            $stmt->execute([$enrollmentId, $sectionSubjectId]);
            $pdo->commit();
            $notice = 'Student elective assigned.';
        } elseif ($action === 'remove_elective') {
            $enrollmentId = shsInt($_POST['enrollment_id'] ?? 0);
            $sectionSubjectId = shsInt($_POST['section_subject_id'] ?? 0);
            $pdo->beginTransaction();
            $row = shsOne($pdo, "SELECT se.enrollment_id FROM student_electives se JOIN enrollments e ON e.enrollment_id=se.enrollment_id WHERE se.enrollment_id=? AND se.section_subject_id=? AND e.school_year_id=? AND e.grade_level IN ('Grade 11','Grade 12') FOR UPDATE", [$enrollmentId, $sectionSubjectId, $yearId]);
            if (!$row) throw new RuntimeException('Elective assignment not found for current school year.');
            $hasGrades = shsOne($pdo, 'SELECT grade_id FROM grades WHERE enrollment_id=? AND section_subject_id=? LIMIT 1', [$enrollmentId, $sectionSubjectId]);
            $hasAttendance = shsOne($pdo, 'SELECT attendance_id FROM attendance WHERE enrollment_id=? AND section_subject_id=? LIMIT 1', [$enrollmentId, $sectionSubjectId]);
            if ($hasGrades || $hasAttendance) throw new RuntimeException('Cannot remove elective with recorded grades or attendance.');
            $stmt = $pdo->prepare('DELETE FROM student_electives WHERE enrollment_id=? AND section_subject_id=?');
            $stmt->execute([$enrollmentId, $sectionSubjectId]);
            $pdo->commit();
            $notice = 'Elective assignment removed.';
        } else {
            throw new RuntimeException('Unknown action.');
        }
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $ex instanceof PDOException ? 'Database operation failed. Check for duplicates or missing required records.' : $ex->getMessage();
    }
}

$clusters = shsFetch($pdo, 'SELECT ec.*, COUNT(sec.subject_id) AS subject_count FROM elective_clusters ec LEFT JOIN subject_elective_clusters sec ON sec.cluster_id=ec.cluster_id GROUP BY ec.cluster_id ORDER BY ec.track, ec.cluster_name');
$subjects = shsFetch($pdo, "SELECT subject_id,subject_code,subject_name,grade_level FROM subjects WHERE grade_level IN ('Grade 11','Grade 12') AND subject_status='Active' ORDER BY grade_level,subject_name");
$enrollments = $yearId ? shsFetch($pdo, "SELECT e.enrollment_id,e.student_lrn,e.grade_level,e.shs_track,e.section_id,cs.section_name,CONCAT(s.last_name,', ',s.first_name) AS student_name FROM enrollments e JOIN students s ON s.student_lrn=e.student_lrn LEFT JOIN class_sections cs ON cs.section_id=e.section_id WHERE e.school_year_id=? AND e.grade_level IN ('Grade 11','Grade 12') ORDER BY e.grade_level,s.last_name,s.first_name", [$yearId]) : [];
$offerings = $yearId ? shsFetch($pdo, "SELECT ss.section_subject_id,ss.section_id,cs.shs_track,cs.section_name,s.subject_name,s.grade_level FROM section_subjects ss JOIN class_sections cs ON cs.section_id=ss.section_id JOIN subjects s ON s.subject_id=ss.subject_id WHERE cs.school_year_id=? AND cs.grade_level IN ('Grade 11','Grade 12') AND EXISTS (SELECT 1 FROM subject_elective_clusters sec JOIN elective_clusters ec ON ec.cluster_id=sec.cluster_id WHERE sec.subject_id=ss.subject_id AND ec.track=cs.shs_track AND ec.is_active=1) ORDER BY cs.section_name,s.subject_name", [$yearId]) : [];
$assigned = $yearId ? shsFetch($pdo, "SELECT se.enrollment_id,se.section_subject_id,e.student_lrn,CONCAT(st.last_name,', ',st.first_name) AS student_name,s.subject_name FROM student_electives se JOIN enrollments e ON e.enrollment_id=se.enrollment_id JOIN students st ON st.student_lrn=e.student_lrn JOIN section_subjects ss ON ss.section_subject_id=se.section_subject_id JOIN subjects s ON s.subject_id=ss.subject_id WHERE e.school_year_id=? ORDER BY st.last_name,s.subject_name", [$yearId]) : [];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>SHS Electives | ELTNHS</title>
<style>
*{box-sizing:border-box}body{font:14px Arial,sans-serif;background:#f3f5f8;color:#263342;margin:0}.wrap{max-width:1080px;margin:25px auto;padding:0 18px}.top{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}.card{background:white;border:1px solid #e1e6ed;border-radius:10px;padding:20px;margin:16px 0}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px}label{display:block;font-weight:bold;margin:10px 0 5px}input,select,button{font:inherit;padding:10px;border:1px solid #cdd6e0;border-radius:6px;width:100%}button{background:#2877bb;color:#fff;border:0;cursor:pointer;margin-top:14px}button.danger{background:#a43131;width:auto;margin:0;padding:7px 12px}a{color:#2877bb}.alert{padding:12px;border-radius:6px;background:#fff1f0;color:#8c1c13}.ok{background:#e9f6ef;color:#1a6b3a;padding:12px;border-radius:6px}table{width:100%;border-collapse:collapse}th,td{text-align:left;border-bottom:1px solid #e8ecf0;padding:10px}.scroll{overflow:auto}.muted{color:#657488}
</style></head><body><main class="wrap"><div class="top"><div><h1>SHS Clusters of Electives</h1><p class="muted">Current school year: <?= e($current['school_year_name'] ?? 'Not set') ?></p></div><a href="../shared/dashboard.php">Back to Dashboard</a></div>
<?php if($error): ?><p class="alert"><?= e($error) ?></p><?php endif; ?><?php if($notice): ?><p class="ok"><?= e($notice) ?></p><?php endif; ?>
<section class="grid"><div class="card"><h2>1. Create cluster</h2><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="create_cluster"><label>SHS Track</label><select name="track" required><option value="">Choose track</option><?php foreach($tracks as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?></select><label>Cluster name</label><input name="cluster_name" maxlength="120" required placeholder="Enter school-approved cluster name"><button>Create Cluster</button></form></div>
<div class="card"><h2>2. Link subject to cluster</h2><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="link_subject"><label>Cluster</label><select name="cluster_id" required><option value="">Choose cluster</option><?php foreach($clusters as $c): if(!$c['is_active']) continue; ?><option value="<?= (int)$c['cluster_id'] ?>"><?= e($c['track'].' — '.$c['cluster_name']) ?></option><?php endforeach; ?></select><label>Grade 11/12 subject</label><select name="subject_id" required><option value="">Choose subject</option><?php foreach($subjects as $s): ?><option value="<?= (int)$s['subject_id'] ?>"><?= e($s['grade_level'].' — '.$s['subject_name']) ?></option><?php endforeach; ?></select><button>Link Subject</button></form><p class="muted">Subjects must already exist in Subject Management.</p></div></section>
<div class="card"><h2>3. Assign elective to student</h2><p class="muted">Student must already have a current-year SHS section and track. The subject must be assigned to that section in Subject Management.</p><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="assign_elective"><div class="grid"><div><label>Student enrollment</label><select name="enrollment_id" required><option value="">Choose student</option><?php foreach($enrollments as $en): ?><option value="<?= (int)$en['enrollment_id'] ?>"><?= e($en['student_name'].' ('.$en['student_lrn'].') — '.$en['grade_level'].' / '.($en['shs_track'] ?: 'No track').' / '.($en['section_name'] ?: 'No section')) ?></option><?php endforeach; ?></select></div><div><label>Section elective offering</label><select name="section_subject_id" required><option value="">Choose offering</option><?php foreach($offerings as $o): ?><option value="<?= (int)$o['section_subject_id'] ?>"><?= e($o['grade_level'].' / '.$o['section_name'].' / '.$o['shs_track'].' — '.$o['subject_name']) ?></option><?php endforeach; ?></select></div></div><button>Assign Elective</button></form></div>
<div class="card"><h2>Clusters</h2><div class="scroll"><table><thead><tr><th>Track</th><th>Cluster</th><th>Linked subjects</th><th>Status</th></tr></thead><tbody><?php foreach($clusters as $c): ?><tr><td><?= e($c['track']) ?></td><td><?= e($c['cluster_name']) ?></td><td><?= (int)$c['subject_count'] ?></td><td><?= $c['is_active'] ? 'Active' : 'Inactive' ?></td></tr><?php endforeach; ?></tbody></table></div></div>
<div class="card"><h2>Current-year student electives</h2><div class="scroll"><table><thead><tr><th>LRN</th><th>Student</th><th>Elective subject</th><th>Action</th></tr></thead><tbody><?php foreach($assigned as $a): ?><tr><td><?= e($a['student_lrn']) ?></td><td><?= e($a['student_name']) ?></td><td><?= e($a['subject_name']) ?></td><td><form method="post" onsubmit="return confirm('Remove this elective assignment?')"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="remove_elective"><input type="hidden" name="enrollment_id" value="<?= (int)$a['enrollment_id'] ?>"><input type="hidden" name="section_subject_id" value="<?= (int)$a['section_subject_id'] ?>"><button class="danger">Remove</button></form></td></tr><?php endforeach; ?></tbody></table></div></div>
</main></body></html>
