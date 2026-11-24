<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'classes/ELTNHS.php';
require_once 'includes/functions.php';
requireLogin();
$system = new ELTNHS($pdo);
$role = $_SESSION['role'];
$name = $_SESSION['username'];
$stats = [];
$actions = [];
$recentTitle = 'Quick guide';
$recentRows = [];
if ($role === 'Student') {
 $student = $system->getOne('SELECT * FROM students WHERE user_id=?',[$_SESSION['user_id']]);
 $enrollment = $student ? $system->getOne("SELECT e.*,sy.school_year_name,cs.section_name FROM enrollments e JOIN school_years sy ON e.school_year_id=sy.school_year_id LEFT JOIN class_sections cs ON e.section_id=cs.section_id WHERE e.student_lrn=? ORDER BY e.enrollment_id DESC LIMIT 1",[$student['student_lrn']]) : null;
 $stats = [ ['Current Grade',$enrollment['grade_level'] ?? 'Not available',''], ['Section',$enrollment['section_name'] ?? 'Not assigned',''], ['Enrollment Status',$enrollment['enrollment_status'] ?? 'No enrollment',''] ];
 $actions = [['My Profile','portal.php?page=profile','View your personal information'],['My Schedule','portal.php?page=schedule','Check your subjects and class times'],['Grades','portal.php?page=grades','View grades by school year and term']];
 $recentTitle='Latest Announcements';
 $recentRows=$system->getAll("SELECT title,announcement_category,publish_date FROM announcements WHERE announcement_status='Published' AND publish_date<=NOW() AND (expiry_date IS NULL OR expiry_date>=NOW()) AND target_audience IN ('All','Students') ORDER BY publish_date DESC LIMIT 5");
 $history=[];
 if($student){
  $history=$system->getAll('SELECT sy.school_year_name,sy.year_status,e.grade_level,cs.section_name,e.enrollment_status,e.final_result FROM enrollments e JOIN school_years sy ON sy.school_year_id=e.school_year_id LEFT JOIN class_sections cs ON cs.section_id=e.section_id WHERE e.student_lrn=? ORDER BY sy.school_year_id DESC,e.enrollment_id DESC',[$student['student_lrn']]);
 }
 $actions=[['My Profile','student_profile.php','View your profile and documents'],['My Schedule','student_schedule.php','View current schedule'],['My Grades','student_grades.php','View current grades'],['Scholarships','student_scholarships.php','View and apply for scholarships']];
} elseif ($role === 'School Registrar') {
 $stats=[['Student Profiles',$system->getOne('SELECT COUNT(*) total FROM students')['total'],'All registered students'],['Enrollment Records',$system->getOne('SELECT COUNT(*) total FROM enrollments')['total'],'All school-year records'],['Pending Processing',$system->getOne("SELECT COUNT(*) total FROM enrollments WHERE enrollment_status IN ('Pending','Incomplete Requirements','For Verification')")['total'],'Needs follow-up']];
 $actions=[['Process Enrollment','modules.php?module=enrollments','Create the enrollment record and submission code'],['Review Requirements','registrar_requirements.php','Check submitted files and verify each requirement'],['View Reports','modules.php?module=reports','Review enrollment summaries']];
 $recentTitle='Recent Enrollment Records';
 $recentRows=$system->getAll('SELECT s.student_lrn,CONCAT(s.last_name,", ",s.first_name) student_name,e.grade_level,e.enrollment_status,sy.school_year_name FROM enrollments e JOIN students s ON e.student_lrn=s.student_lrn JOIN school_years sy ON e.school_year_id=sy.school_year_id ORDER BY e.enrollment_id DESC LIMIT 6');
} elseif ($role === 'System Administrator') {
 $stats=[['Total Students Enrolled',$system->getOne("SELECT COUNT(DISTINCT student_lrn) total FROM enrollments WHERE enrollment_status='Officially Enrolled'")['total'],'Students officially enrolled across school years'],['Total Accounts',$system->getOne('SELECT COUNT(*) total FROM users')['total'],'All system users'],['Inactive Accounts',$system->getOne("SELECT COUNT(*) total FROM users WHERE account_status<>'Active'")['total'],'Accounts not active']];
 $actions=[['Manage Accounts','manage_accounts.php','Manage staff and student accounts'],['Student Masterlist','student_masterlist.php','Search student profiles and enrollment records'],['Announcements','modules.php?module=announcements','Create and review announcements'],['All Reports','modules.php?module=reports','SF1, SF2, and SF5 principal reports']];
 $recentTitle='System and Account Information';
 $recentRows=$system->getAll('SELECT role,COUNT(*) total FROM users GROUP BY role ORDER BY role');
} elseif ($role === 'Teacher Adviser') {
 $teacher=$system->getOne('SELECT teacher_id,first_name,last_name FROM teachers WHERE user_id=?',[$_SESSION['user_id']]);$teacherId=$teacher['teacher_id']??0;
 $stats=[['Advisory Sections',$system->getOne('SELECT COUNT(*) total FROM class_sections WHERE adviser_teacher_id=?',[$teacherId])['total'],'Sections assigned to you'],['Advisory Students',$system->getOne("SELECT COUNT(DISTINCT e.student_lrn) total FROM enrollments e JOIN class_sections cs ON cs.section_id=e.section_id WHERE cs.adviser_teacher_id=? AND e.enrollment_status='Officially Enrolled'",[$teacherId])['total'],'Officially enrolled learners']];
 $actions=[['My Profile','teacher_profile.php','View your staff information'],['My Advisory Class','advisory_class.php','View students, grades, and result statuses'],['My Advisory Setup','adviser_subjects.php','Manage subjects, subject teachers, and schedules'],['Notifications','notifications.php','View school announcements and updates']];
 $recentTitle='';$recentRows=[];
} elseif ($role === 'Subject Teacher') {
 $teacher=$system->getOne('SELECT teacher_id,first_name,last_name FROM teachers WHERE user_id=?',[$_SESSION['user_id']]);
 $teacherId=$teacher['teacher_id']??0;
 $stats=[['Assigned Subjects',$system->getOne('SELECT COUNT(*) total FROM section_subjects WHERE teacher_id=?',[$teacherId])['total'],'Your subject assignments'],['Assigned Sections',$system->getOne('SELECT COUNT(DISTINCT cs.section_id) total FROM class_sections cs LEFT JOIN section_subjects ss ON ss.section_id=cs.section_id WHERE cs.adviser_teacher_id=? OR ss.teacher_id=?',[$teacherId,$teacherId])['total'],'Sections you teach or advise'],['Students in Classes',$system->getOne('SELECT COUNT(DISTINCT e.student_lrn) total FROM enrollments e JOIN class_sections cs ON cs.section_id=e.section_id LEFT JOIN section_subjects ss ON ss.section_id=cs.section_id WHERE (ss.teacher_id=? OR cs.adviser_teacher_id=?) AND e.enrollment_status=\'Officially Enrolled\'',[$teacherId,$teacherId])['total'],'Based on your assigned classes']];
 $actions=[['My Profile','teacher_profile.php','View your staff information'],['My Classes & Grade Encoding','classroom.php','Choose your assigned grade, section, subject, and grading period'],['My Schedule','portal.php?page=schedule','View your subject schedule'],['Announcements','modules.php?module=announcements','Read school announcements'],['Notifications','notifications.php','View updates and requests']];
 $recentTitle='My Assigned Subjects';
 $recentRows=$system->getAll('SELECT cs.section_name,s.subject_name,ss.schedule_days,ss.start_time,ss.end_time FROM section_subjects ss JOIN class_sections cs ON ss.section_id=cs.section_id JOIN subjects s ON ss.subject_id=s.subject_id WHERE (ss.teacher_id=? OR cs.adviser_teacher_id=?) ORDER BY cs.section_name,s.subject_name LIMIT 8',[$teacherId,$teacherId]);
} elseif ($role === 'Guidance Counselor') {
 $stats = [['Current Assessments', $system->getOne('SELECT COUNT(*) total FROM guidance_records')['total'], 'Recorded evaluations']];
 $actions = [['Guidance Assessments','guidance.php','Review students and record assessments']];
 $recentRows = [];
} elseif ($role === 'Clinic Personnel') {
 $stats = [['Clinic Visits', $system->getOne('SELECT COUNT(*) total FROM clinic_visits')['total'], 'Recorded visits']];
 $actions = [['Student Masterlist','clinic_students.php','Search students and emergency contacts'],['Clinic Visits','clinic.php','Record student clinic visits']];
 $recentRows = [];
} elseif ($role === 'Scholarship Coordinator') {
 $stats=[['Published Programs',$system->getOne("SELECT COUNT(*) total FROM scholarship_programs WHERE program_status='Published'")['total'],'Available scholarship listings'],['Applications',$system->getOne('SELECT COUNT(*) total FROM scholarship_applications')['total'],'Recorded applications'],['Beneficiaries',$system->getOne('SELECT COUNT(*) total FROM scholarship_awards')['total'],'Recorded scholarship awards']];
 $actions=[['Scholarship Programs','modules.php?module=programs','Add providers and publish opportunities'],['Applications','modules.php?module=applications','Review student applications'],['Scholarship Monitoring','modules.php?module=monitoring','Track beneficiaries and assistance'],['Reports','modules.php?module=reports','View scholarship summaries']];
 $recentTitle='Recent Applications';
 $recentRows=$system->getAll('SELECT a.student_lrn,p.program_name,a.application_status,a.application_date FROM scholarship_applications a JOIN scholarship_programs p ON a.program_id=p.program_id ORDER BY a.application_id DESC LIMIT 6');
}
require 'includes/header.php';
?>
<div class="page-heading"><div><h1>Dashboard</h1></div></div>
<div class="row g-3 mb-4">
 <?php foreach($stats as $stat): ?><div class="col-md-4"><div class="card stat-card"><div class="stat-label"><?= e($stat[0]) ?></div><div class="stat-value"><?= e($stat[1]) ?></div><div class="stat-note"><?= e($stat[2]) ?></div></div></div><?php endforeach; ?>
</div>

<?php if($role==='Student'): ?>
<div class="card p-3 mb-4"><h5>Enrollment History</h5>
<?php if(!$history): ?><p class="text-muted mb-0">No enrollment records found.</p><?php else: ?>
<div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>School Year</th><th>Grade</th><th>Section</th><th>Enrollment Status</th><th>Final Result</th></tr></thead><tbody>
<?php foreach($history as $h): ?><tr><td><?=e($h['school_year_name'])?> <?= $h['year_status']==='Current'?'(Current)':'' ?></td><td><?=e($h['grade_level'])?></td><td><?=e($h['section_name']?:'Unassigned')?></td><td><?=e($h['enrollment_status'])?></td><td><?=e($h['final_result']?:'—')?></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></div>
<?php endif; ?>
<?php if($role==='School Registrar'): ?>
<div class="card p-3 mb-4"><h5 class="mb-3">Student Registration to Enrollment</h5><div class="workflow-steps"><div class="workflow-step"><span class="workflow-number">1</span><span><strong>Parent Registration</strong><small>Parent submits the student registration form; Registrar reviews it</small></span></div><div class="workflow-step"><span class="workflow-number">2</span><span><strong>Enrollment</strong><small>Select school year, grade, requirements, and section</small></span></div><div class="workflow-step"><span class="workflow-number">3</span><span><strong>Requirements Verification</strong><small>Registrar verifies physical documents and confirms enrollment</small></span></div></div><p class="small-note mb-0">Returning student? Search the existing LRN and reuse the profile. Do not create a second profile or a second student account.</p></div>
<?php endif; ?>
<?php if($role==='Teacher Adviser'): ?>
<?php else: ?><div class="card p-3"><div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0"><?= e($recentTitle) ?></h5><?php if($role==='School Registrar'): ?><a href="../shared/modules.php?module=enrollments" class="small text-decoration-none">Open Enrollment Management</a><?php endif; ?></div>
<?php if(!$recentRows): ?><p class="text-muted mb-0">No records to display yet.</p><?php elseif($role==='Student'): ?><div class="list-group list-group-flush"><?php foreach($recentRows as $r): ?><div class="list-group-item px-0"><strong><?= e($r['title']) ?></strong><div class="small-note"><?= e($r['announcement_category'].' · '.$r['publish_date']) ?></div></div><?php endforeach; ?></div>
<?php elseif($role==='System Administrator'): ?><p class="mb-2">Total user accounts: <strong><?= (int)$system->getOne('SELECT COUNT(*) AS total FROM users')['total'] ?></strong></p><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Role</th><th>Accounts</th></tr></thead><tbody><?php foreach($recentRows as $r): ?><tr><td><?= e($r['role']) ?></td><td><?= (int)$r['total'] ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php elseif(in_array($role,['Teacher Adviser','Subject Teacher'],true)): ?><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Section</th><th>Subject</th><th>School Year</th><th>Days</th><th>Time</th></tr></thead><tbody><?php foreach($recentRows as $r): ?><tr><td><?= e($r['section_name']) ?></td><td><?= e($r['subject_name']) ?></td><td>Full Year</td><td><?= e($r['schedule_days']) ?></td><td><?= e(($r['start_time']??'').' - '.($r['end_time']??'')) ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php else: ?><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Student</th><th>LRN</th><th>School Year</th><th>Grade</th><th>Status</th></tr></thead><tbody><?php foreach($recentRows as $r): ?><tr><td><?= e($r['student_name']) ?></td><td><?= e($r['student_lrn']) ?></td><td><?= e($r['school_year_name']) ?></td><td><?= e($r['grade_level']) ?></td><td><?= e($r['enrollment_status']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</div><?php endif; ?>
<?php require 'includes/footer.php'; ?>
