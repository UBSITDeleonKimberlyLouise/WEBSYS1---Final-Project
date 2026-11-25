<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'includes/functions.php';
requireRole(['Guidance Counselor']);
$userId = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT enrollment_id) AS assessed, COUNT(DISTINCT CASE WHEN interview_status='Passed' THEN enrollment_id END) AS passed, COUNT(DISTINCT CASE WHEN interview_status='Failed' THEN enrollment_id END) AS failed FROM guidance_records gr WHERE assessed_by=? AND guide_id=(SELECT MAX(g2.guide_id) FROM guidance_records g2 WHERE g2.enrollment_id=gr.enrollment_id)");
$stmt->execute([$userId]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['assessed'=>0,'passed'=>0,'failed'=>0];
$stmt = $pdo->prepare("SELECT s.student_lrn, CONCAT(s.last_name, ', ',s.first_name) AS student_name, gr.interview_status, gr.assessed_at FROM guidance_records gr JOIN enrollments e ON e.enrollment_id=gr.enrollment_id JOIN students s ON s.student_lrn=e.student_lrn WHERE gr.assessed_by=? ORDER BY gr.assessed_at DESC,gr.guide_id DESC LIMIT 8");
$stmt->execute([$userId]); $recentAssessments = $stmt->fetchAll(PDO::FETCH_ASSOC);
$recentViews = $_SESSION['guidance_recent_views'] ?? [];
require 'includes/header.php';
?>
<div class="page-heading"><div><h1>Guidance Dashboard</h1><p>Your interview assessments and recently viewed student profiles.</p></div></div>
<div class="row g-3 mb-4">
<?php foreach ([['Students Assessed',$stats['assessed']],['Passed',$stats['passed']],['Failed',$stats['failed']]] as $stat): ?><div class="col-md-4"><div class="card stat-card"><div class="stat-label"><?= e($stat[0]) ?></div><div class="stat-value"><?= (int)$stat[1] ?></div></div></div><?php endforeach; ?>
</div>
<div class="row g-3">
<div class="col-lg-7"><div class="card p-3 h-100"><h5>Recent Assessments</h5><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Student</th><th>Result</th><th>Date</th></tr></thead><tbody><?php foreach ($recentAssessments as $r): ?><tr><td><?= e($r['student_name']) ?><div class="small text-muted"><?= e($r['student_lrn']) ?></div></td><td><?= e($r['interview_status']) ?></td><td><?= e($r['assessed_at']) ?></td></tr><?php endforeach; ?><?php if (!$recentAssessments): ?><tr><td colspan="3" class="text-muted">No assessments recorded yet.</td></tr><?php endif; ?></tbody></table></div><a href="../guidance/guidance.php" class="btn btn-outline-primary btn-sm align-self-start">Open Guidance Interview</a></div></div>
<div class="col-lg-5"><div class="card p-3 h-100"><h5>Recently Viewed Students</h5><p class="small text-muted">Profiles shown during searches in this login session.</p><?php foreach ($recentViews as $r): ?><div class="border-bottom py-2"><strong><?= e($r['name']) ?></strong><div class="small text-muted">LRN: <?= e($r['lrn']) ?> · <?= e($r['at']) ?></div></div><?php endforeach; ?><?php if (!$recentViews): ?><p class="text-muted">No profiles viewed in this session.</p><?php endif; ?><a href="../guidance/guidance_students.php" class="btn btn-outline-primary btn-sm mt-3 align-self-start">Open Student Masterlist</a></div></div>
</div>
<?php require 'includes/footer.php'; ?>
