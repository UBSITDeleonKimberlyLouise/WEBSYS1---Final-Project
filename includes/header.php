<?php
chdir(dirname(__DIR__));

require_once __DIR__ . '/functions.php';
$role = $_SESSION['role'] ?? '';
$menus = [
  'Student' => [
  ['Dashboard','../shared/dashboard.php','Home'],
  ['My Profile','../students/student_profile.php','Profile'],['Re-enrollment','../shared/reenroll.php','Enrollment'],
  ['My Schedule','../students/student_schedule.php','Schedule'],
  ['My Grades','../students/student_grades.php','Grades'],
  ['Past Records','../students/student_past_records.php','History'],
  ['Scholarships','../students/student_scholarships.php','Scholarships'],
 ],
 'School Registrar' => [['Dashboard','../shared/dashboard.php','Home'],['Review Parent Registrations','../registrar/registration_review.php','Review'],['Enrollment Management','../shared/modules.php?module=enrollments','Enrollment'],['Re-enrollment','../shared/reenroll.php','Re-enroll'],['Student Management','../shared/student_management.php','Students'],['Requirements & Accounts','../registrar/registrar_requirements.php','Requirements'],['Announcements','../shared/modules.php?module=announcements','Announcements'],['Password Reset Requests','../auth/password_resets.php','Reset'],['Reports','../shared/modules.php?module=reports','Reports']],
 'Teacher Adviser' => [
 ['Dashboard','../shared/dashboard.php','Home'],
 ['My Profile','../teachers/teacher_profile.php','Profile'],
 ['My Advisory Class','../advisers/advisory_class.php','Advisory'],
 ['My Teaching Subjects','../teachers/teacher_grade_entry.php','Grades'],
 ['Attendance','../advisers/adviser_attendance.php','Attendance'],
 ['Reports','../advisers/adviser_monthly_attendance.php','Reports'],
 ['Class Setup','../advisers/adviser_subjects.php','Setup'],
 ['My Schedule','../teachers/teacher_schedule.php','Schedule'],
 ['Past Records','../advisers/adviser_past_records.php','History']
],
 'Subject Teacher' => [
    ['Dashboard','../shared/dashboard.php','Home'],['My Profile','../teachers/teacher_profile.php','Profile'],
    ['My Assigned Subjects','../teachers/teacher_grade_entry.php','Grades'],
    ['My Schedule','../teachers/teacher_schedule.php','Schedule'],
    ['Past Records','../teachers/teacher_past_records.php','History']
],
 'Scholarship Coordinator' => [['Dashboard','../shared/dashboard.php','Home'],['My Profile','../shared/staff_profile.php','Profile'],['Scholarship Programs','../shared/modules.php?module=programs','Programs'],['Applications','../shared/modules.php?module=applications','Applications'],['Scholarship Monitoring','../shared/modules.php?module=monitoring','Monitoring'],['Announcements','../shared/modules.php?module=announcements','Announcements'],['Reports','../shared/modules.php?module=reports','Reports']],
 'Guidance Counselor' => [['Dashboard','../guidance/guidance_dashboard.php','Home'],['Announcements','../guidance/guidance_announcements.php','Announcements'],['Guidance Interview','../guidance/guidance.php','Assessments'],['Student Masterlist','../guidance/guidance_students.php','Students'],['My Profile','../guidance/guidance_profile.php','Profile']],
 'Clinic Personnel' => [['Dashboard','../shared/dashboard.php','Home'],['Announcements','../clinic/clinic_announcements.php','Announcements'],['Clinic Visits','../clinic/clinic.php','Visits'],['Student Masterlist','../clinic/clinic_students.php','Students']],
 'System Administrator' => [['Dashboard','../shared/dashboard.php','Home'],['Review Parent Registrations','../registrar/registration_review.php','Review'],['Enrollment Management','../shared/modules.php?module=enrollments','Enrollment'],['Re-enrollment','../shared/reenroll.php','Re-enroll'],['Student Management','../shared/student_management.php','Students'],['Student Masterlist','../registrar/student_masterlist.php','Students'],['Password Reset Requests','../auth/password_resets.php','Reset'],['Announcements','../shared/modules.php?module=announcements','Announcements'],['School Year Management','../admin/school_years.php','Years'],['Manage Accounts','../admin/manage_accounts.php','Accounts'],['Reports','../shared/modules.php?module=reports','Reports']]
];
$current = basename($_SERVER['PHP_SELF']);
$basePath = preg_replace('~/[^/]+/[^/]+$~', '/', $_SERVER['SCRIPT_NAME']);
if (!function_exists('eltnhsUrl')) { function eltnhsUrl($path) { global $basePath; return rtrim($basePath,'/') . '/' . ltrim(preg_replace('~^\.\./~','',$path),'/'); } }
$currentModule = $_GET['module'] ?? '';
$currentPage = $_GET['page'] ?? '';
function menuActive($url, $current, $currentModule, $currentPage) {
 if (strpos($url, 'modules.php?module=') !== false) return $current === 'modules.php' && $currentModule === substr($url, strpos($url, 'modules.php?module=') + strlen('modules.php?module='));
 if (strpos($url, 'portal.php?page=') !== false) return $current === 'portal.php' && $currentPage === substr($url, strpos($url, 'portal.php?page=') + strlen('portal.php?page='));
 return $current === basename(parse_url($url, PHP_URL_PATH));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>ELTNHS Student Information System</title>
 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
 <link rel="stylesheet" href="<?= e(eltnhsUrl('assets/style.css')) ?>">
</head>
<body>
<header class="topbar">
 <a class="brand" href="<?= e(eltnhsUrl('shared/dashboard.php')) ?>"><img src="<?= e(eltnhsUrl('assets/school_logo.png')) ?>" alt="School logo" style="width:43px;height:43px;object-fit:contain;background:white;border-radius:50%;padding:3px"><span><strong>ELTNHS</strong><small>Student Information System</small></span></a>
 <div class="topbar-right"><?php if(isset($_SESSION['user_id'])): $nq=$GLOBALS['pdo']->prepare('SELECT COUNT(*) FROM user_notifications WHERE user_id=? AND is_read=0'); $nq->execute([$_SESSION['user_id']]); $ncount=(int)$nq->fetchColumn(); ?><a class="btn btn-sm btn-outline-primary me-2" href="<?= e(eltnhsUrl('shared/notifications.php')) ?>">Notifications (<?=$ncount?>)</a><?php endif; ?><span class="account-label"><?= e($_SESSION['username'] ?? '') ?> <b><?= e($role) ?></b></span><a class="logout-link" href="<?= e(eltnhsUrl('auth/logout.php')) ?>">Log out</a></div>
</header>
<div class="app-layout">
 <aside class="sidebar">
  <div class="sidebar-heading">MAIN MENU</div>
  <nav class="side-nav">
   <?php foreach (($menus[$role] ?? []) as $item): $active = menuActive($item[1],$current,$currentModule,$currentPage); ?>
    <a class="side-link <?= $active ? 'active' : '' ?>" href="<?= e(eltnhsUrl($item[1])) ?>"><span class="side-icon"><?= e(substr($item[2],0,1)) ?></span><span><?= e($item[0]) ?></span></a>
   <?php endforeach; ?>
  </nav>
 </aside>
 <main class="main-content">
  <?php showMessage(); ?>
