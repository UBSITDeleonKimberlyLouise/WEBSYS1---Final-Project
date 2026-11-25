<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'includes/functions.php';
requireRole(['Teacher Adviser','Subject Teacher']);
$userId=(int)$_SESSION['user_id'];
$q=$pdo->prepare("SELECT t.teacher_id FROM teachers t JOIN users u ON u.user_id=t.user_id WHERE t.user_id=? AND t.employment_status='Active' AND u.account_status='Active'");
$q->execute([$userId]); $teacherId=(int)$q->fetchColumn();
if (!$teacherId) { http_response_code(403); exit('No active teacher profile linked to this account.'); }
$q=$pdo->prepare("SELECT ss.section_subject_id, s.subject_name, cs.section_name, cs.grade_level, sy.school_year_name FROM section_subjects ss JOIN subjects s ON s.subject_id=ss.subject_id JOIN class_sections cs ON cs.section_id=ss.section_id JOIN school_years sy ON sy.school_year_id=cs.school_year_id WHERE ss.teacher_id=? AND sy.year_status='Current' ORDER BY cs.grade_level,cs.section_name,s.subject_name");
$q->execute([$teacherId]); $assignments=$q->fetchAll(PDO::FETCH_ASSOC);
$map=[]; foreach($assignments as $a) $map[(int)$a['section_subject_id']]=$a;
$selectedId=(int)($_POST['assignment_id']??$_GET['assignment_id']??0);
if (!$selectedId && $assignments) $selectedId=(int)$assignments[0]['section_subject_id'];
$selected=$map[$selectedId]??null;
$periods=['Prelim','Midterm','Finals'];
$period=$_POST['grading_period']??$_GET['grading_period']??'Prelim';
if (!in_array($period,$periods,true)) $period='Prelim';
$error=''; $message=''; $learners=[];
if ($selected) {
 $q=$pdo->prepare("SELECT e.enrollment_id, st.student_lrn, st.last_name, st.first_name, g.grade_value, g.remarks FROM section_subjects ss JOIN class_sections cs ON cs.section_id=ss.section_id JOIN enrollments e ON e.section_id=cs.section_id AND e.school_year_id=cs.school_year_id JOIN students st ON st.student_lrn=e.student_lrn LEFT JOIN grades g ON g.enrollment_id=e.enrollment_id AND g.section_subject_id=ss.section_subject_id AND g.grading_period=? WHERE ss.section_subject_id=? AND ss.teacher_id=? AND e.enrollment_status IN ('Pending','Temporarily Enrolled','Officially Enrolled') ORDER BY st.last_name,st.first_name,st.student_lrn");
 $q->execute([$period,$selectedId,$teacherId]); $learners=$q->fetchAll(PDO::FETCH_ASSOC);
}
if ($_SERVER['REQUEST_METHOD']==='POST') {
 verifyCsrf();
 if (!$selected) $error='Select one of your assigned subjects.';
 elseif (($_POST['action']??'')==='save_grades') {
  try {
   $allowed=array_column($learners,'enrollment_id'); $allowed=array_fill_keys(array_map('intval',$allowed),true);
   $submitted=$_POST['grades']??[];
   if (!is_array($submitted)) throw new Exception('Invalid grade submission.');
   foreach($submitted as $id=>$value) {
    if (!ctype_digit((string)$id) || !isset($allowed[(int)$id])) throw new Exception('Invalid learner in grade submission.');
    if ($value!=='' && (!is_numeric($value) || (float)$value<0 || (float)$value>100)) throw new Exception('Grades must be between 0 and 100.');
   }
   $pdo->beginTransaction();
   $save=$pdo->prepare("INSERT INTO grades(enrollment_id,section_subject_id,grading_period,grade_value,encoded_by) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE grade_value=VALUES(grade_value),encoded_by=VALUES(encoded_by)");
   foreach($submitted as $id=>$value) $save->execute([(int)$id,$selectedId,$period,$value===''?null:(float)$value,$userId]);
   $pdo->commit();
   header('Location: ../teachers/teacher_grade_entry.php?assignment_id='.$selectedId.'&grading_period='.urlencode($period).'&saved=1'); exit;
  } catch(Throwable $ex) { if ($pdo->inTransaction()) $pdo->rollBack(); error_log('Teacher grade entry: '.$ex->getMessage()); $error=$ex instanceof PDOException?'Unable to save grades. Check the server error log.':$ex->getMessage(); }
 }
}
if (isset($_GET['saved'])) $message='Grades saved successfully.';
require 'includes/header.php';
?>
<div class="page-heading"><div><h1>Grade Encoding</h1></div></div>
<?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<?php if($message):?><div class="alert alert-success"><?=e($message)?></div><?php endif;?>
<div class="card p-3 mb-3"><form method="get" class="row g-3 align-items-end"><div class="col-md-8"><label class="form-label">Assigned Subject / Section</label><select class="form-select" name="assignment_id" required><?php foreach($assignments as $a):?><option value="<?=(int)$a['section_subject_id']?>" <?=$selectedId===(int)$a['section_subject_id']?'selected':''?>><?=e($a['grade_level'].' - '.$a['section_name'].' / '.$a['subject_name'].' ('.$a['school_year_name'].')')?></option><?php endforeach;?></select></div><div class="col-md-2"><label class="form-label">Term</label><select class="form-select" name="grading_period"><?php foreach($periods as $p):?><option value="<?=e($p)?>" <?=$period===$p?'selected':''?>><?=e($p)?></option><?php endforeach;?></select></div><div class="col-md-2"><button class="btn btn-outline-primary w-100">Load</button></div></form></div>
<?php if(!$assignments):?><div class="alert alert-info">No assigned subjects for the current school year. Ask your adviser or authorized administrator to assign a subject to your teacher account.</div>
<?php elseif($selected):?><div class="card p-3"><h5><?=e($selected['subject_name'])?> — <?=e($period)?></h5><p class="small text-muted">Blank means not yet encoded. Only enrolled learners in the selected section are listed.</p><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><input type="hidden" name="action" value="save_grades"><input type="hidden" name="assignment_id" value="<?=$selectedId?>"><input type="hidden" name="grading_period" value="<?=e($period)?>"><div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>LRN</th><th>Learner</th><th style="width:160px">Grade (0–100)</th></tr></thead><tbody><?php foreach($learners as $l):?><tr><td><?=e($l['student_lrn'])?></td><td><?=e($l['last_name'].', '.$l['first_name'])?></td><td><input type="number" step="0.01" min="0" max="100" class="form-control" name="grades[<?=(int)$l['enrollment_id']?>]" value="<?=e($l['grade_value']??'')?>" aria-label="Grade for <?=e($l['last_name'].', '.$l['first_name'])?>"></td></tr><?php endforeach;?><?php if(!$learners):?><tr><td colspan="3" class="text-muted">No learners found for this assigned section.</td></tr><?php endif;?></tbody></table></div><?php if($learners):?><button class="btn btn-primary">Save Grades</button><?php endif;?></form></div><?php endif;?>
<?php require 'includes/footer.php'; ?>
