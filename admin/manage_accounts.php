<?php
chdir(dirname(__DIR__));

session_start();require_once 'dbconn.php';require_once 'includes/functions.php';requireRole(['System Administrator']);
$message='';$error='';$roles=['School Registrar','Subject Teacher','Teacher Adviser','Scholarship Coordinator'];
if($_SERVER['REQUEST_METHOD']==='POST'){
 verifyCsrf();$action=$_POST['account_action']??'';$uid=(int)($_POST['user_id']??0);
 try{
  if($action==='archive'||$action==='restore'){
   $q=$pdo->prepare('SELECT user_id,role FROM users WHERE user_id=?');$q->execute([$uid]);$target=$q->fetch();if(!$target)throw new Exception('Account not found.');if($target['role']==='System Administrator')throw new Exception('Administrator accounts cannot be archived here.');
   $status=$action==='archive'?'Inactive':'Active';$pdo->prepare('UPDATE users SET account_status=? WHERE user_id=?')->execute([$status,$uid]);
   $pdo->prepare('UPDATE staff_profiles SET employment_status=? WHERE user_id=?')->execute([$status,$uid]);
   $pdo->prepare('UPDATE teachers SET employment_status=? WHERE user_id=?')->execute([$status,$uid]);$message=$status==='Inactive'?'Account archived. Existing school records are preserved.':'Account restored.';
  }elseif($action==='reset_student_password'){
   $q=$pdo->prepare("SELECT user_id FROM users WHERE user_id=? AND role='Student'");$q->execute([$uid]);if(!$q->fetch())throw new Exception('Student account not found.');
   $pdo->prepare('UPDATE users SET password_hash=?,must_change_password=1,account_status=\'Active\' WHERE user_id=?')->execute([password_hash('1',PASSWORD_DEFAULT),$uid]);$message='Student password reset to 1. The student must change it after logging in.';
  }elseif($action==='change_role'){
   $newRole=$_POST['new_role']??''; if(!in_array($newRole,$roles,true)) throw new Exception('Choose a valid staff role.');
   $q=$pdo->prepare("SELECT user_id,role FROM users WHERE user_id=? AND role<>'System Administrator'");$q->execute([$uid]);$target=$q->fetch();if(!$target)throw new Exception('Staff account not found.');
   $pdo->beginTransaction();$pdo->prepare('UPDATE users SET role=? WHERE user_id=?')->execute([$newRole,$uid]);
   if(in_array($newRole,['Subject Teacher','Teacher Adviser'],true)){
    $sp=$pdo->prepare('SELECT * FROM staff_profiles WHERE user_id=?');$sp->execute([$uid]);$p=$sp->fetch();
    if(!$p) throw new Exception('Staff profile is missing.');
    $t=$pdo->prepare('SELECT teacher_id FROM teachers WHERE user_id=?');$t->execute([$uid]);
    if($t->fetchColumn()) $pdo->prepare('UPDATE teachers SET employee_number=?,first_name=?,middle_name=?,last_name=?,contact_number=?,email=?,employment_status=? WHERE user_id=?')->execute([$p['employee_number'],$p['first_name'],$p['middle_name'],$p['last_name'],$p['contact_number'],$p['email'],$p['employment_status'],$uid]);
    else $pdo->prepare('INSERT INTO teachers(user_id,employee_number,first_name,middle_name,last_name,contact_number,email,employment_status) VALUES(?,?,?,?,?,?,?,?)')->execute([$uid,$p['employee_number'],$p['first_name'],$p['middle_name'],$p['last_name'],$p['contact_number'],$p['email'],$p['employment_status']]);
   }
   $teacherLookup=$pdo->prepare('SELECT teacher_id FROM teachers WHERE user_id=?');$teacherLookup->execute([$uid]);$teacherId=(int)$teacherLookup->fetchColumn();if($teacherId && $newRole==='Subject Teacher')$pdo->prepare('UPDATE class_sections SET adviser_teacher_id=NULL WHERE adviser_teacher_id=?')->execute([$teacherId]);if($teacherId && $newRole==='Teacher Adviser')$pdo->prepare('UPDATE section_subjects SET teacher_id=NULL WHERE teacher_id=?')->execute([$teacherId]);
   $pdo->commit();$message='Staff role updated. Incompatible current assignments were cleared so the new role can be assigned correctly; student grades and history were preserved.';
  }elseif($action==='create_staff'){
   $username=trim($_POST['username']??'');$password=$_POST['password']??'';$role=$_POST['role']??'';$employee=trim($_POST['employee_number']??'');$first=trim($_POST['first_name']??'');$middle=trim($_POST['middle_name']??'');$last=trim($_POST['last_name']??'');$address=trim($_POST['address']??'');$contact=trim($_POST['contact_number']??'');$email=trim($_POST['email']??'');
   if($username===''||strlen($password)<8||!in_array($role,$roles,true)||$employee===''||$first===''||$last===''||$address===''||$contact===''||$email==='')throw new Exception('Complete username, password (at least 8 characters), employee number, name, address, contact number, email, and role.');
   if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))throw new Exception('Enter a valid email address.');
   $pdo->beginTransaction();$q=$pdo->prepare('INSERT INTO users(username,password_hash,role,must_change_password,created_by) VALUES(?,?,?,1,?)');$q->execute([$username,password_hash($password,PASSWORD_DEFAULT),$role,$_SESSION['user_id']]);$newId=(int)$pdo->lastInsertId();
   $q=$pdo->prepare('INSERT INTO staff_profiles(user_id,employee_number,first_name,middle_name,last_name,address,contact_number,email,employment_status) VALUES(?,?,?,?,?,?,?,?,\'Active\')');$q->execute([$newId,$employee,$first,$middle?:null,$last,$address?:null,$contact?:null,$email?:null]);
   if(in_array($role,['Subject Teacher','Teacher Adviser'],true)){$q=$pdo->prepare('INSERT INTO teachers(user_id,employee_number,first_name,middle_name,last_name,contact_number,email) VALUES(?,?,?,?,?,?,?)');$q->execute([$newId,$employee,$first,$middle?:null,$last,$contact?:null,$email?:null]);}
   $pdo->commit();$message=$role.' account created. The employee profile includes contact details and address. They must change their temporary password after first login.';
  }else{throw new Exception('Unknown account action.');}
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error=$e instanceof PDOException?'Unable to complete the action. Check duplicate username or employee number. Details: '.($e->errorInfo[2]??$e->getMessage()):$e->getMessage();}
}
$accounts=$pdo->query("SELECT u.user_id,u.username,u.role,u.account_status,u.created_at,COALESCE(NULLIF(CONCAT_WS(' ',sp.first_name,sp.middle_name,sp.last_name),''),NULLIF(CONCAT_WS(' ',t.first_name,t.middle_name,t.last_name),''),NULLIF(CONCAT_WS(' ',s.first_name,s.middle_name,s.last_name),''),'—') full_name,COALESCE(sp.employee_number,t.employee_number) employee_number,sp.address,COALESCE(sp.contact_number,t.contact_number) contact_number,COALESCE(sp.email,t.email) email,s.student_lrn FROM users u LEFT JOIN staff_profiles sp ON sp.user_id=u.user_id LEFT JOIN teachers t ON t.user_id=u.user_id LEFT JOIN students s ON s.user_id=u.user_id WHERE u.role<>'System Administrator' ORDER BY (u.account_status='Inactive'),u.created_at DESC")->fetchAll();
require 'includes/header.php';?>
<div class="page-heading"><div><h1>Manage Accounts</h1></div></div>
<?php if($message):?><div class="alert alert-success"><?=e($message)?></div><?php endif;?><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<div class="card p-3 mb-4"><h5>Create Employee Account</h5><p class="small text-muted">Create accounts for the Registrar, Subject Teachers, and Teacher Advisers. Staff roles can be changed later without deleting their teaching records.</p><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><input type="hidden" name="account_action" value="create_staff"><div class="row g-3"><div class="col-md-4"><label class="form-label">Username</label><input class="form-control" name="username" maxlength="50" placeholder="e.g. evelyn.reyes" required></div><div class="col-md-4"><label class="form-label">Temporary Password</label><input class="form-control" type="password" name="password" minlength="8" placeholder="At least 8 characters" required></div><div class="col-md-4"><label class="form-label">Employee Role</label><select class="form-select" name="role" required><option value="">Choose role</option><?php foreach($roles as $r):?><option value="<?=e($r)?>"><?=e($r)?></option><?php endforeach;?></select></div><div class="col-md-3"><label class="form-label">Employee Number</label><input class="form-control" name="employee_number" placeholder="Employee ID" required></div><div class="col-md-3"><label class="form-label">First Name</label><input class="form-control" name="first_name" required></div><div class="col-md-3"><label class="form-label">Middle Name</label><input class="form-control" name="middle_name"></div><div class="col-md-3"><label class="form-label">Last Name</label><input class="form-control" name="last_name" required></div><div class="col-md-6"><label class="form-label">Address</label><input class="form-control" name="address" placeholder="House no., street, barangay, city" required></div><div class="col-md-3"><label class="form-label">Contact Number</label><input class="form-control" name="contact_number" placeholder="09XXXXXXXXX" required></div><div class="col-md-3"><label class="form-label">Email Address</label><input class="form-control" type="email" name="email" placeholder="name@example.com" required></div></div><button class="btn btn-primary mt-3">Create Employee Account</button></form></div>
<div class="card p-3"><h5>All Accounts</h5><p class="small text-muted">Archive instead of deleting accounts to preserve class assignments, grades, and student history.</p><div class="row g-3 mb-3">
 <div class="col-md-6"><button type="button" class="btn btn-outline-primary w-100 text-start p-3" onclick="showAccountFolder('Student')"><strong>Student Accounts</strong><div class="small">Click to open student accounts</div></button></div>
 <div class="col-md-6"><button type="button" class="btn btn-outline-primary w-100 text-start p-3" onclick="showAccountFolder('Employee')"><strong>Employee Accounts</strong><div class="small">Click to open employee accounts</div></button></div>
</div>
<div id="accountFolderPanel" style="display:none">
 <h6 id="accountFolderTitle"></h6>
 <input type="search" class="form-control mb-3" id="accountFolderSearch" placeholder="Search name, username, role, or details" oninput="filterAccountRows()">
<div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Name / Employee</th><th>Username / LRN</th><th>Role</th><th>Contact Details</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach($accounts as $a):?><tr data-account-group="<?= $a['role']==='Student'?'Student':'Employee' ?>"><td><strong><?=e($a['full_name'])?></strong><div class="small text-muted"><?=e($a['employee_number']??($a['student_lrn']??'Student'))?></div></td><td><?=e($a['username'])?></td><td><?=e($a['role'])?><?php if(in_array($a['role'],['Subject Teacher','Teacher Adviser'],true)):?><form method="post" class="mt-1 d-flex gap-1"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><input type="hidden" name="account_action" value="change_role"><input type="hidden" name="user_id" value="<?=$a['user_id']?>"><select class="form-select form-select-sm" name="new_role"><option <?= $a['role']==='Subject Teacher'?'selected':'' ?>>Subject Teacher</option><option <?= $a['role']==='Teacher Adviser'?'selected':'' ?>>Teacher Adviser</option></select><button class="btn btn-sm btn-outline-primary">Update</button></form><?php endif;?></td><td><div><?=e($a['contact_number']??'—')?></div><div class="small text-muted"><?=e($a['email']??'')?></div><div class="small text-muted"><?=e($a['address']??'')?></div></td><td><span class="badge <?=$a['account_status']==='Active'?'text-bg-success':'text-bg-secondary'?>"><?=e($a['account_status'])?></span></td><td><div class="d-flex flex-wrap gap-1"><?php if($a['role']==='Student'):?><form method="post" onsubmit="return confirm('Reset this student password to 1?');"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><input type="hidden" name="account_action" value="reset_student_password"><input type="hidden" name="user_id" value="<?=$a['user_id']?>"><button class="btn btn-sm btn-outline-primary">Reset Password</button></form><?php endif;?><?php if($a['account_status']==='Inactive'):?><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><input type="hidden" name="account_action" value="restore"><input type="hidden" name="user_id" value="<?=$a['user_id']?>"><button class="btn btn-sm btn-outline-success">Restore</button></form><?php else:?><form method="post" onsubmit="return confirm('Archive this account?');"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><input type="hidden" name="account_action" value="archive"><input type="hidden" name="user_id" value="<?=$a['user_id']?>"><button class="btn btn-sm btn-outline-danger">Archive</button></form><?php endif;?></div></td></tr><?php endforeach;?></tbody></table></div></div></div>
<script>
var currentAccountFolder = '';
function showAccountFolder(group) {
  currentAccountFolder = group;
  document.getElementById('accountFolderPanel').style.display = 'block';
  document.getElementById('accountFolderTitle').textContent = group === 'Student' ? 'Student Accounts' : 'Employee Accounts';
  document.getElementById('accountFolderSearch').value = '';
  filterAccountRows();
}
function filterAccountRows() {
  var search = document.getElementById('accountFolderSearch').value.toLowerCase();
  var rows = document.querySelectorAll('tr[data-account-group]');
  for (var i = 0; i < rows.length; i++) {
    var row = rows[i];
    var matchGroup = row.getAttribute('data-account-group') === currentAccountFolder;
    var matchSearch = row.textContent.toLowerCase().indexOf(search) !== -1;
    row.style.display = matchGroup && matchSearch ? '' : 'none';
  }
}
</script>
<?php require 'includes/footer.php';?>
