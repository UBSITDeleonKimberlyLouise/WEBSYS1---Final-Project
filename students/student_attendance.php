<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'includes/functions.php';
requireRole(['Student']);

$sql = "SELECT sy.school_year_name, sy.school_year_id, sy.year_status, e.enrollment_id, e.grade_level,
               e.semester, cs.section_name, s.subject_name, ss.section_subject_id,
               a.attendance_date, a.attendance_status, a.remarks
        FROM attendance a
        JOIN enrollments e ON e.enrollment_id=a.enrollment_id
        JOIN school_years sy ON sy.school_year_id=e.school_year_id
        LEFT JOIN class_sections cs ON cs.section_id=e.section_id
        JOIN section_subjects ss ON ss.section_subject_id=a.section_subject_id
        JOIN subjects s ON s.subject_id=ss.subject_id
        JOIN students st ON st.student_lrn=e.student_lrn
        WHERE st.user_id=?
        ORDER BY sy.school_year_name DESC, e.enrollment_id DESC,
                 s.subject_name, a.attendance_date DESC";
$q=$pdo->prepare($sql);
$q->execute([$_SESSION['user_id']]);
$rows=$q->fetchAll(PDO::FETCH_ASSOC);
$data=[];
foreach($rows as $r){
 $jhs=in_array((string)$r['grade_level'],['Grade 7','Grade 8','Grade 9','Grade 10','7','8','9','10'],true);
 $semester=$jhs?'Full Year':($r['semester']??'First');
 $year=$r['school_year_name'];
 $groupKey=$r['enrollment_id'].'|'.$semester;
 if(!isset($data[$year][$groupKey])){
  $data[$year][$groupKey]=['grade'=>$r['grade_level'],'section'=>$r['section_name']??'Unassigned','semester'=>$semester,'subjects'=>[]];
 }
 $subjectKey=$r['section_subject_id'];
 if(!isset($data[$year][$groupKey]['subjects'][$subjectKey])){
  $data[$year][$groupKey]['subjects'][$subjectKey]=['name'=>$r['subject_name'],'days'=>[]];
 }
 $data[$year][$groupKey]['subjects'][$subjectKey]['days'][]=$r;
}
$selectedYear = (string)($_GET['school_year'] ?? '');
if ($selectedYear === '' || !array_key_exists($selectedYear, $data)) {
    $selectedYear = '';
    foreach ($rows as $row) {
        if (($row['year_status'] ?? '') === 'Current') {
            $selectedYear = (string)$row['school_year_name'];
            break;
        }
    }
    if ($selectedYear === '' && $data) {
        $selectedYear = (string)array_key_first($data);
    }
}
require 'includes/header.php';
?>
<div class="page-heading"><div><h1>My Attendance</h1><p>Attendance records organized by school year and subject.</p></div><button type="button" class="btn btn-outline-primary print-action" onclick="window.print()">🖨️ Print</button></div>
<?php if (!$data): ?>
<div class="card p-4">No attendance records have been posted yet.</div>
<?php else: ?>
<div class="card p-3 mb-3">
 <form method="get" class="row g-2 align-items-end">
  <div class="col-md-6">
   <label for="school_year" class="form-label fw-semibold">School Year</label>
   <select name="school_year" id="school_year" class="form-select" onchange="this.form.submit()">
    <?php foreach ($data as $year => $groups): ?>
     <option value="<?=e($year)?>" <?=((string)$year===$selectedYear)?'selected':''?>><?=e($year)?></option>
    <?php endforeach; ?>
   </select>
  </div>
  <div class="col-md-6"><p class="text-muted small mb-1">Select a school year to view current or past attendance records.</p></div>
 </form>
</div>
<div class="card p-3 mb-3">
 <h5 class="mb-3">School Year <?=e($selectedYear)?></h5>
 <?php foreach ($data[$selectedYear] as $group): ?>
 <div class="border rounded p-3 mb-3">
  <h6 class="mb-3"><?=e($group['grade'])?> · Section <?=e($group['section'])?> · <?=e($group['semester'])?></h6>
  <?php foreach ($group['subjects'] as $subject): ?>
  <details class="border rounded p-3 mb-2">
   <summary class="fw-semibold"><?=e($subject['name'])?> <span class="text-muted small">(<?=count($subject['days'])?> records)</span></summary>
   <div class="table-responsive mt-3"><table class="table table-sm align-middle mb-0"><thead><tr><th>#</th><th>Date</th><th>Status</th><th>Remarks</th></tr></thead><tbody>
   <?php foreach ($subject['days'] as $i=>$day): ?><tr><td><?=$i+1?></td><td><?=e($day['attendance_date'])?></td><td><?=e($day['attendance_status'])?></td><td><?=e($day['remarks']??'—')?></td></tr><?php endforeach; ?>
   </tbody></table></div>
  </details>
  <?php endforeach; ?>
 </div>
 <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require 'includes/footer.php'; ?>
