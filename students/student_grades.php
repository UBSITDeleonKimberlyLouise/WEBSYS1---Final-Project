<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'includes/functions.php';
requireRole(['Student']);
$q=$pdo->prepare('SELECT student_lrn FROM students WHERE user_id=?');$q->execute([$_SESSION['user_id']]);$lrn=$q->fetchColumn();
if (!$lrn) { http_response_code(404); exit('Student profile not found.'); }
$q=$pdo->prepare("SELECT sy.school_year_name,e.grade_level,cs.section_name,s.subject_name,ss.section_subject_id,g.grading_period,g.grade_value,g.remarks FROM enrollments e JOIN school_years sy ON sy.school_year_id=e.school_year_id JOIN class_sections cs ON cs.section_id=e.section_id AND cs.school_year_id=e.school_year_id JOIN section_subjects ss ON ss.section_id=cs.section_id JOIN subjects s ON s.subject_id=ss.subject_id LEFT JOIN grades g ON g.enrollment_id=e.enrollment_id AND g.section_subject_id=ss.section_subject_id WHERE e.student_lrn=? AND sy.year_status='Current' ORDER BY s.subject_name");
$q->execute([$lrn]);$rows=$q->fetchAll(PDO::FETCH_ASSOC);$subjects=[];
foreach($rows as $r){$id=(int)$r['section_subject_id'];if(!isset($subjects[$id]))$subjects[$id]=['name'=>$r['subject_name'],'grades'=>[]];if($r['grading_period']!==null)$subjects[$id]['grades'][$r['grading_period']]=$r['grade_value'];}
require 'includes/header.php';
require 'includes/print_layout.php';
?>
<div class="page-heading"><div><h1>My Grades</h1><p>Grades for the current school year. Blank means no grade has been encoded.</p></div></div>
<div class="card p-3"><?php if (!$rows): ?><p class="text-muted mb-0">No current subjects or grades available.</p><?php else: ?>
<h5><?=e($rows[0]['school_year_name'].' · '.$rows[0]['grade_level'].' · '.$rows[0]['section_name'])?></h5>
<div class="table-responsive"><table class="table table-hover"><thead><tr><th>Subject</th><th>Term 1</th><th>Term 2</th><th>Term 3</th></tr></thead><tbody>
<?php foreach($subjects as $s): ?><tr><td><?=e($s['name'])?></td><?php foreach(['Prelim','Midterm','Finals'] as $p): ?><td><?=isset($s['grades'][$p]) && $s['grades'][$p]!==null?e((string)$s['grades'][$p]):'—'?></td><?php endforeach; ?></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></div>
 </div>
</div>
<?php require 'includes/footer.php'; ?>
