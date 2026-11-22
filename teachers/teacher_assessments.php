<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'includes/functions.php';
requireRole(['Subject Teacher', 'Teacher Adviser']);
$userId = (int)$_SESSION['user_id'];
$teacherStmt = $pdo->prepare('SELECT teacher_id FROM teachers WHERE user_id = ? AND employment_status = ?');
$teacherStmt->execute([$userId, 'Active']);
$teacherId = (int)$teacherStmt->fetchColumn();
if (!$teacherId) { http_response_code(403); exit('No active teacher profile linked to your account.'); }

// Authorization is based on actual assigned English/Filipino/Mathematics subjects,
// never merely on the role or a client-supplied assessment type.
$assignmentsSql = "SELECT DISTINCT ss.section_subject_id, cs.section_id, cs.grade_level,
 cs.section_name, cs.school_year_id, s.subject_name,
 CASE WHEN LOWER(TRIM(s.subject_name)) IN ('english','filipino') THEN 'Reading'
      WHEN LOWER(TRIM(s.subject_name)) IN ('mathematics','math') THEN 'Numeracy'
 END AS assessment_type
 FROM section_subjects ss
 JOIN subjects s ON s.subject_id = ss.subject_id
 JOIN class_sections cs ON cs.section_id = ss.section_id
 JOIN school_years sy ON sy.school_year_id = cs.school_year_id
 WHERE ss.teacher_id = ? AND sy.year_status = 'Current'
 AND (LOWER(TRIM(s.subject_name)) IN ('english','filipino','mathematics','math'))
 ORDER BY cs.grade_level, cs.section_name, s.subject_name";
$stmt = $pdo->prepare($assignmentsSql);
$stmt->execute([$teacherId]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
$assignmentMap = [];
foreach ($assignments as $a) $assignmentMap[(int)$a['section_subject_id']] = $a;
$selectedId = (int)($_POST['assignment_id'] ?? $_GET['assignment_id'] ?? 0);
if (!$selectedId && $assignments) $selectedId = (int)$assignments[0]['section_subject_id'];
$selected = $assignmentMap[$selectedId] ?? null;
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $enrollmentId = filter_input(INPUT_POST, 'enrollment_id', FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? '';
    $notes = trim((string)($_POST['notes'] ?? ''));
    if (!$selected || !$enrollmentId || !in_array($status, ['Pending','Passed','Failed'], true) || mb_strlen($notes) > 500) {
        $error = 'Invalid assignment, learner, result, or notes.';
    } else {
        try {
            $pdo->beginTransaction();
            $valid = $pdo->prepare("SELECT e.enrollment_id FROM enrollments e
                JOIN school_years sy ON sy.school_year_id=e.school_year_id
                WHERE e.enrollment_id=? AND e.section_id=? AND e.school_year_id=?
                  AND e.grade_level=? AND sy.year_status='Current'
                  AND e.enrollment_status NOT IN ('Rejected','Withdrawn') FOR UPDATE");
            $valid->execute([$enrollmentId, $selected['section_id'], $selected['school_year_id'], $selected['grade_level']]);
            if (!$valid->fetchColumn()) throw new RuntimeException('Learner is not eligible in this assigned section.');
            $save = $pdo->prepare("INSERT INTO learner_assessments
                (enrollment_id,assessment_type,assessment_status,assessed_by,notes)
                VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE
                assessment_status=VALUES(assessment_status),assessed_by=VALUES(assessed_by),
                notes=VALUES(notes),assessed_at=CURRENT_TIMESTAMP");
            $save->execute([$enrollmentId, $selected['assessment_type'], $status, $userId, $notes ?: null]);
            $pdo->commit();
            $message = $selected['assessment_type'] . ' assessment saved.';
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log($ex->getMessage());
            $error = 'Could not save assessment. Check learner assignment and database migration.';
        }
    }
}
$learners = [];
if ($selected) {
    $q = $pdo->prepare("SELECT e.enrollment_id,e.student_lrn,st.first_name,st.last_name,
        la.assessment_status,la.notes
        FROM enrollments e JOIN students st ON st.student_lrn=e.student_lrn
        LEFT JOIN learner_assessments la ON la.enrollment_id=e.enrollment_id AND la.assessment_type=?
        WHERE e.section_id=? AND e.school_year_id=? AND e.grade_level=?
          AND e.enrollment_status NOT IN ('Rejected','Withdrawn')
        ORDER BY st.last_name,st.first_name");
    $q->execute([$selected['assessment_type'],$selected['section_id'],$selected['school_year_id'],$selected['grade_level']]);
    $learners = $q->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reading and Numeracy Assessments</title>
<style>
*{box-sizing:border-box}body{font:14px Arial,sans-serif;margin:0;background:#f3f5f8;color:#263342}
main{max-width:1100px;margin:30px auto;padding:0 18px}h1{font-size:23px}a{color:#2877bb}
.panel{background:white;border:1px solid #e1e6ec;border-radius:9px;padding:20px;margin:16px 0}
select,input,textarea,button{font:inherit;padding:9px;border:1px solid #ccd5df;border-radius:5px}
select{max-width:100%;width:100%}textarea{width:100%;min-height:54px;resize:vertical}
button{background:#2877bb;color:white;border:0;cursor:pointer}.notice{padding:12px;background:#eaf7ee}.error{padding:12px;background:#fff0f0}
table{border-collapse:collapse;width:100%}th,td{padding:11px;text-align:left;border-bottom:1px solid #e7ebf0;vertical-align:top}
.assessment-form{display:grid;grid-template-columns:130px minmax(180px,1fr) auto;gap:8px;align-items:start}
@media(max-width:700px){.assessment-form{grid-template-columns:1fr}table,thead,tbody,tr,td{display:block}thead{display:none}tr{padding:12px;border-bottom:1px solid #ddd}td{border:0;padding:5px}}
</style></head><body><main>
<p><a href="../shared/dashboard.php">Back to Dashboard</a></p>
<h1>Reading and Numeracy Assessments</h1>
<p>English/Filipino teachers handle Reading. Mathematics teachers handle Numeracy. These results do not block Guidance approval.</p>
<?php if ($message): ?><p class="notice"><?= e($message) ?></p><?php endif; ?>
<?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
<div class="panel"><form method="get"><label for="assignment_id">Assigned class and assessment</label>
<select name="assignment_id" id="assignment_id" onchange="this.form.submit()">
<?php foreach ($assignments as $a): ?>
<option value="<?= (int)$a['section_subject_id'] ?>" <?= (int)$a['section_subject_id']===$selectedId?'selected':'' ?>>
<?= e($a['grade_level'].' / '.$a['section_name'].' / '.$a['subject_name'].' — '.$a['assessment_type']) ?>
</option><?php endforeach; ?>
</select><noscript><button type="submit">View</button></noscript></form></div>
<div class="panel">
<?php if (!$selected): ?><p>No assigned English, Filipino, or Mathematics subject in the current school year.</p>
<?php else: ?><h2><?= e($selected['assessment_type']) ?> — <?= e($selected['grade_level'].' / '.$selected['section_name']) ?></h2>
<table><thead><tr><th>LRN</th><th>Student</th><th>Assessment</th></tr></thead><tbody>
<?php foreach ($learners as $student): ?><tr><td><?= e($student['student_lrn']) ?></td>
<td><?= e($student['last_name'].', '.$student['first_name']) ?></td><td>
<form method="post" class="assessment-form">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<input type="hidden" name="assignment_id" value="<?= $selectedId ?>">
<input type="hidden" name="enrollment_id" value="<?= (int)$student['enrollment_id'] ?>">
<select name="status" aria-label="Assessment result">
<?php foreach (['Pending','Passed','Failed'] as $choice): ?>
<option value="<?= e($choice) ?>" <?= ($student['assessment_status'] ?? 'Pending')===$choice?'selected':'' ?>><?= e($choice) ?></option>
<?php endforeach; ?></select>
<textarea name="notes" maxlength="500" placeholder="Comments (optional)"><?= e($student['notes'] ?? '') ?></textarea>
<button type="submit">Save</button></form></td></tr><?php endforeach; ?>
</tbody></table><?php if (!$learners): ?><p>No eligible learners in this section.</p><?php endif; ?>
<?php endif; ?></div></main></body></html>
