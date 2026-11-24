<?php
chdir(dirname(__DIR__));

session_start();
require_once 'dbconn.php';
require_once 'includes/functions.php';
requireRole(['Clinic Personnel']);

$search = trim((string)($_POST['search'] ?? ''));
$students = [];
$error = '';
$searched = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_action']);

if ($searched) {
    verifyCsrf();
    if ($search === '') {
        $error = 'Enter a student LRN or name to search.';
    } elseif (strlen($search) > 100) {
        $error = 'Search text is too long.';
    } else {
        try {
            $sql = "SELECT s.*, cs.grade_level, cs.section_name
                    FROM students s
                    LEFT JOIN enrollments e ON e.enrollment_id = (
                        SELECT e2.enrollment_id FROM enrollments e2
                        WHERE e2.student_lrn = s.student_lrn
                        ORDER BY e2.enrollment_id DESC LIMIT 1
                    )
                    LEFT JOIN class_sections cs ON cs.section_id = e.section_id
                    WHERE s.student_lrn LIKE :lrn
                       OR s.last_name LIKE :last_name
                       OR s.first_name LIKE :first_name
                    ORDER BY CASE WHEN s.sex = 'Male' THEN 0 ELSE 1 END,
                             s.last_name, s.first_name
                    LIMIT 150";
            $prep = $pdo->prepare($sql);
            $term = '%' . $search . '%';
            $prep->execute([':lrn' => $term, ':last_name' => $term, ':first_name' => $term]);
            $students = $prep->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $ex) {
            error_log('Clinic masterlist query failed: ' . $ex->getMessage());
            $error = 'Unable to load student records. Please check the PHP error log.';
        }
    }
}
require 'includes/header.php';
?>
<div class="page-heading"><div><h1>Student Masterlist</h1></div></div>
<div class="card p-3 mb-3">
    <form method="post" class="row g-2">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <div class="col-md-9"><input name="search" class="form-control" maxlength="100" value="<?= e($search) ?>" placeholder="Search LRN or student name" required></div>
        <div class="col-md-3"><button type="submit" name="search_action" value="1" class="btn btn-primary w-100">Search</button></div>
    </form>
</div>
<?php if ($error): ?><div class="alert alert-warning"><?= e($error) ?></div><?php endif; ?>
<?php if ($searched && !$error): ?>
    <?php if (!$students): ?>
        <div class="card p-3"><p class="mb-0">No students found matching your search.</p></div>
    <?php else: ?>
        <div class="card p-3 mb-3">
            <h5>Search Results (<?= count($students) ?>)</h5>
            <?php foreach ($students as $s): ?>
                <div class="border rounded p-3 mb-2">
                    <div class="fw-semibold mb-3"><strong><?= e($s['last_name'] . ', ' . $s['first_name'] . ' ' . ($s['middle_name'] ?? '')) ?></strong> — <?= e($s['student_lrn']) ?> <span class="text-muted">(<?= e(($s['grade_level'] ?: 'Unassigned') . ' / ' . ($s['section_name'] ?: 'Unassigned')) ?>)</span></div>
                    <div class="row g-3 mt-2">
                        <?php foreach (['student_lrn'=>'LRN','sex'=>'Sex','birth_date'=>'Birthdate','student_status'=>'Status',
                            'student_contact_number'=>'Student Contact','current_house_no'=>'House No.',
                            'current_street_sitio'=>'Street','current_barangay'=>'Barangay',
                            'current_municipality'=>'Municipality','current_province'=>'Province',
                            'parent_guardian_name'=>'Parent / Guardian','parent_guardian_contact'=>'Guardian Contact',
                            'emergency_contact_name'=>'Emergency Contact',
                            'emergency_contact_relationship'=>'Relationship',
                            'emergency_contact_number'=>'Emergency Phone',
                            'health_conditions'=>'Health Conditions','allergies'=>'Allergies',
                            'current_medications'=>'Current Medications'] as $field => $label): ?>
                            <div class="col-md-4"><div class="text-muted small"><?= e($label) ?></div><strong><?= e($s[$field] ?? '') ?></strong></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
<?php require 'includes/footer.php'; ?>
