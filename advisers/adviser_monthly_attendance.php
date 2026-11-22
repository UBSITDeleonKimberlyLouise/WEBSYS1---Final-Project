<?php
chdir(dirname(__DIR__));

session_start();require_once 'dbconn.php';require_once 'includes/functions.php';requireRole(['Teacher Adviser']);
$q=$pdo->prepare('SELECT teacher_id FROM teachers WHERE user_id=?');$q->execute([$_SESSION['user_id']]);$tid=(int)$q->fetchColumn();
// Sections are scoped to the logged-in adviser's saved assignments.
$q=$pdo->prepare("SELECT cs.section_id,cs.grade_level,cs.section_name,cs.school_year_id,sy.school_year_name,sy.year_status,sy.start_date,sy.end_date FROM class_sections cs JOIN school_years sy ON sy.school_year_id=cs.school_year_id WHERE cs.adviser_teacher_id=? ORDER BY CASE WHEN sy.year_status='Current' THEN 0 ELSE 1 END,sy.school_year_name DESC,cs.grade_level,cs.section_name");
$q->execute([$tid]);$allSections=$q->fetchAll(PDO::FETCH_ASSOC);
$currentSections=array_values(array_filter($allSections,fn($s)=>$s['year_status']==='Current'));
$pastSections=array_values(array_filter($allSections,fn($s)=>$s['year_status']!=='Current'));
$mode=(($_GET['view']??'current')==='history')?'history':'current';
$available=$mode==='history'?$pastSections:$currentSections;
$requested=(int)($_GET['section_id']??0);
$section=null;
foreach($available as $candidate){if((int)$candidate['section_id']===$requested){$section=$candidate;break;}}
if(!$section)$section=$available[0]??null;
$id=(int)($section['section_id']??0);
$month=(string)($_GET['month']??date('Y-m'));
if(!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$month))$month=date('Y-m');
if($mode==='history' && !isset($_GET['month']) && $section){$month=substr((string)($section['end_date']?:$section['start_date']?:date('Y-m')),0,7);}
$from=$month.'-01';$to=date('Y-m-d',strtotime($from.' +1 month'));
$students=[];$days=[];$byStudent=[];$summary=[];$error='';
if($id){try{
 $q=$pdo->prepare("SELECT e.enrollment_id,st.sex,st.last_name,st.first_name,e.enrollment_date,e.enrollment_status FROM enrollments e JOIN students st ON st.student_lrn=e.student_lrn WHERE e.section_id=? AND e.school_year_id=? AND e.enrollment_date<? AND e.enrollment_status IN ('Officially Enrolled','Completed') ORDER BY st.last_name,st.first_name");$q->execute([$id,$section['school_year_id'],$to]);$students=$q->fetchAll(PDO::FETCH_ASSOC);
 $q=$pdo->prepare('SELECT a.enrollment_id,a.attendance_date,a.attendance_status FROM official_daily_attendance a JOIN enrollments e ON e.enrollment_id=a.enrollment_id WHERE e.section_id=? AND e.school_year_id=? AND a.attendance_date>=? AND a.attendance_date<? ORDER BY a.attendance_date');$q->execute([$id,$section['school_year_id'],$from,$to]);foreach($q->fetchAll(PDO::FETCH_ASSOC) as $r){$byStudent[$r['enrollment_id']][$r['attendance_date']]=$r['attendance_status'];$days[$r['attendance_date']]=true;}
 $dayList=array_keys($days);sort($dayList);
 foreach(['Male','Female','Total'] as $sex)$summary[$sex]=['registered'=>0,'present'=>0,'absent'=>0,'tardy'=>0,'ada'=>null,'percentage'=>null,'five_days'=>0];
 foreach($students as $s){$sex=$s['sex'];$endRegistered=($s['enrollment_date']<=$to && $s['enrollment_date']<=date('Y-m-d',strtotime($to.' -1 day')));if(!$endRegistered)continue;foreach([$sex,'Total'] as $key)$summary[$key]['registered']++;
  $streak=0;$hasFive=false;
  foreach($dayList as $day){if($day<$s['enrollment_date'])continue;$status=$byStudent[$s['enrollment_id']][$day]??null;if($status==='Absent'){$streak++;}elseif($status!==null){$streak=0;}else{$streak=0;}if($streak>=5)$hasFive=true;
   foreach([$sex,'Total'] as $key){if($status==='Present'||$status==='Tardy')$summary[$key]['present']++;if($status==='Absent')$summary[$key]['absent']++;if($status==='Tardy')$summary[$key]['tardy']++;}
  }if($hasFive)foreach([$sex,'Total'] as $key)$summary[$key]['five_days']++;
 }
 $n=count($dayList);foreach($summary as &$r){if($n){$r['ada']=round($r['present']/$n,2);if($r['registered'])$r['percentage']=round($r['ada']/$r['registered']*100,2);}}unset($r);
 }catch(PDOException $ex){error_log('Monthly attendance: '.$ex->getMessage());$error='Unable to load attendance. Import the SQL migration first.';}}
require 'includes/header.php';
?>
<style>
.monthly-report-filter{border:1px solid #e4eaf1;border-radius:14px;background:#fff;padding:22px 24px;margin-bottom:20px;box-shadow:0 3px 12px rgba(31,52,78,.035)}
.monthly-report-filter .report-context{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px}
.monthly-report-filter .section-tag{background:#eef4fa;color:#244f79;border-radius:9px;padding:10px 14px;font-weight:600}
.monthly-report-filter .report-caption{font-size:13px;color:#758296}
.monthly-report-filter .report-controls{display:flex;align-items:end;gap:12px;flex-wrap:wrap}
.monthly-report-filter .report-controls .month-field{min-width:220px;max-width:280px;flex:1}
.monthly-report-filter .report-controls .btn{min-height:38px;padding-left:22px;padding-right:22px}
.monthly-report-filter .form-label{font-weight:600;color:#34455a;font-size:13px;margin-bottom:6px}
@media(max-width:575px){.monthly-report-filter{padding:17px}.monthly-report-filter .report-controls .month-field,.monthly-report-filter .report-controls .btn{width:100%;max-width:none}}
</style>
<div class="page-heading"><div><h1>Attendance Reports</h1><p>Official daily attendance and previous advisory records</p></div></div>
<?php if($error):?><div class="alert alert-warning"><?=e($error)?></div><?php endif;?>
<div class="monthly-report-filter">
  <div class="report-context" style="margin-bottom:12px">
    <a class="btn <?= $mode==='current'?'btn-primary':'btn-outline-primary' ?>" href="?view=current">Current Summary</a>
    <a class="btn <?= $mode==='history'?'btn-primary':'btn-outline-primary' ?>" href="?view=history">Past Records</a>
  </div>
  <?php if($section): ?>
    <div class="report-context"><span class="section-tag"><?=e($section['grade_level'].' · '.$section['section_name'])?></span><span class="report-caption"><?=e($section['school_year_name'])?> · <?= $mode==='history'?'Previous advisory assignment':'Current advisory class' ?></span></div>
    <form method="get" class="report-controls">
      <input type="hidden" name="view" value="<?=e($mode)?>">
      <?php if($mode==='history' || count($available)>1): ?>
      <div class="month-field"><label class="form-label" for="report-section">Advisory Section</label>
        <select class="form-select" id="report-section" name="section_id" required>
        <?php foreach($available as $option):?><option value="<?= (int)$option['section_id'] ?>" <?= (int)$option['section_id']===$id?'selected':'' ?>><?=e($option['school_year_name'].' · '.$option['grade_level'].' · '.$option['section_name'])?></option><?php endforeach;?>
        </select></div>
      <?php else: ?><input type="hidden" name="section_id" value="<?=$id?>"><?php endif;?>
      <div class="month-field"><label for="report-month" class="form-label">Month</label><input id="report-month" class="form-control" type="month" name="month" value="<?=e($month)?>" required></div>
      <button class="btn btn-primary" type="submit">View Summary</button>
    </form>
  <?php else: ?>
    <div class="alert alert-info mb-0"><?= $mode==='history'?'No previous advisory sections are recorded for your account.':'No advisory section is assigned to your account for the current school year.' ?></div>
  <?php endif; ?>
</div>
<?php if($id&&!$error):?><div class="card p-3"><h5><?=e($section['grade_level'].' · '.$section['section_name'])?> — <?=e($month)?></h5><p class="small text-muted">Recorded attendance dates: <?=count($days)?>. Missing dates are not counted as present or absent. School calendar and first-Friday baseline are not yet configured; enrollment percentage is intentionally left blank.</p><div class="table-responsive"><table class="table table-bordered table-sm align-middle"><thead><tr><th>Monthly Summary</th><th>Male</th><th>Female</th><th>Total</th></tr></thead><tbody>
<?php $labels=['registered'=>'Registered learners (end of month)','present'=>'Recorded present/tardy entries','absent'=>'Recorded absent entries','tardy'=>'Tardy entries','ada'=>'Average daily attendance (recorded dates)','percentage'=>'Attendance percentage (provisional)','five_days'=>'5 consecutive recorded school-day absences'];foreach($labels as $key=>$label):?><tr><td><?=e($label)?></td><?php foreach(['Male','Female','Total'] as $sex):?><td><?=isset($summary[$sex][$key])&&$summary[$sex][$key]!==null?e((string)$summary[$sex][$key]).($key==='percentage'?'%':''):'—'?></td><?php endforeach;?></tr><?php endforeach;?><tr><td>Percentage of enrollment vs first Friday</td><td>—</td><td>—</td><td>—</td></tr>
</tbody></table></div><div class="alert alert-info mb-0">Draft monitoring summary, not a certified SF2. Previous-year reports are read-only. Final attendance percentages require the official number of school days and a validated attendance policy for Tardy/Excused. Five-day flags use recorded dates only; verify school days, holidays, and missing records before official reporting.</div></div><?php endif;?>
<?php require 'includes/footer.php'; ?>
