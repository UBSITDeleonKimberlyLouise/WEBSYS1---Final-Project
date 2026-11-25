
<?php

require_once __DIR__ . '/functions.php';

$role = $_SESSION['role'] ?? '';

$menus = [
    'Student' => [
        ['Dashboard', '../shared/dashboard.php', 'Home'],
        ['My Profile', '../students/student_profile.php', 'Profile'],
        ['Re-enrollment', '../shared/reenroll.php', 'Re-enrollment'],
        ['My Schedule', '../students/student_schedule.php', 'Schedule'],
        ['My Grades', '../students/student_grades.php', 'Grades'],
        ['My Attendance', '../students/student_attendance.php', 'Attendance'],
        ['Scholarships', '../scholarship/student_scholarships.php', 'Scholarships'],
        ['Past Records', '../students/student_past_records.php', 'History'],
    ],

    'School Registrar' => [
        ['Dashboard', '../shared/dashboard.php', 'Home'],
        ['Review Parent Registrations', '../registrar/registration_review.php', 'Review'],
        ['Enrollment Management', '../shared/modules.php?module=enrollments', 'Enrollment'],
        ['Re-enrollment', '../shared/reenroll.php', 'Re-enrollment'],
        ['Requirements & Accounts', '../registrar/registrar_requirements.php', 'Requirements'],
        ['Announcements', '../shared/modules.php?module=announcements', 'Announcements'],
        ['Password Reset Requests', '../auth/password_resets.php', 'Reset'],
        ['School Year Management', '../shared/grade_corrections.php', 'Grades'],
        ['Reports', '../shared/modules.php?module=reports', 'Reports'],
    ],

    'Teacher Adviser' => [
        ['Dashboard', '../shared/dashboard.php', 'Home'],
        ['Profile', '../teachers/teacher_profile.php', 'Profile'],
        ['Advisory Grades', '../advisers/advisory_class.php', 'Advisory'],
        ['Assigned Teaching Subjects', '../teachers/teacher_grade_entry.php', 'Grades'],
        ['Attendance', '../advisers/adviser_attendance.php', 'Attendance'],
        ['Advisory Schedule', '../advisers/adviser_subjects.php', 'Setup'],
        ['Schedule', '../teachers/teacher_schedule.php', 'Schedule'],
        ['Past Records', '../advisers/adviser_past_records.php', 'History'],
        ['Reports', '../advisers/adviser_monthly_attendance.php', 'Reports'],
    ],

    'Subject Teacher' => [
        ['Dashboard', '../shared/dashboard.php', 'Home'],
        ['Profile', '../teachers/teacher_profile.php', 'Profile'],
        ['Gradebook', '../teachers/teacher_grade_entry.php', 'Grades'],
        ['Schedule', '../teachers/teacher_schedule.php', 'Schedule'],
        ['Past Records', '../teachers/teacher_past_records.php', 'History'],
    ],

    'Guidance Counselor' => [
        ['Dashboard', '../guidance/guidance_dashboard.php', 'Home'],
        ['Announcements', '../guidance/guidance_announcements.php', 'Announcements'],
        ['Guidance Interview', '../guidance/guidance.php', 'Assessments'],
        ['Student Masterlist', '../guidance/guidance_students.php', 'Students'],
        ['My Profile', '../guidance/guidance_profile.php', 'Profile'],
    ],

    'Clinic Personnel' => [
        ['Dashboard', '../shared/dashboard.php', 'Home'],
        ['Announcements', '../clinic/clinic_announcements.php', 'Announcements'],
        ['Clinic Visits', '../clinic/clinic.php', 'Visits'],
        ['Student Masterlist', '../clinic/clinic_students.php', 'Students'],
    ],

    'Scholarship Coordinator' => [
        ['Dashboard', '../shared/dashboard.php', 'Home'],
        ['Programs', '../scholarship/scholarship.php?view=programs', 'Programs'],
        ['Applications', '../scholarship/scholarship.php?view=applications', 'Applications'],
        ['Monitoring', '../scholarship/scholarship.php?view=monitoring', 'Monitoring'],
        ['Reports', '../scholarship/scholarship.php?view=reports', 'Reports'],
        ['My Profile', '../shared/staff_profile.php', 'Profile'],
    ],

    'System Administrator' => [
        ['Dashboard', '../shared/dashboard.php', 'Home'],
        ['Review Parent Registrations', '../registrar/registration_review.php', 'Review'],
        ['Enrollment Management', '../shared/modules.php?module=enrollments', 'Enrollment'],
        ['Student Masterlist', '../registrar/student_masterlist.php', 'Students'],
        ['Password Reset Requests', '../auth/password_resets.php', 'Reset'],
        ['Announcements', '../shared/modules.php?module=announcements', 'Announcements'],
        ['School Year Management', '../admin/school_years.php', 'Years'],
        ['Manage Accounts', '../admin/manage_accounts.php', 'Accounts'],
        ['Grading Management', '../shared/grade_corrections.php', 'Grades'],
        ['Reports', '../shared/modules.php?module=reports', 'Reports'],
    ],
];

$current = basename($_SERVER['PHP_SELF']);
$currentModule = $_GET['module'] ?? '';
$currentPage = $_GET['page'] ?? '';

function menuActive($url, $current, $currentModule, $currentPage)
{
    $url = basename($url);

    if (strpos($url, 'scholarship.php?view=') === 0) {
        return $current === 'scholarship.php'
            && ($_GET['view'] ?? 'programs') === substr(
                $url,
                strlen('scholarship.php?view=')
            );
    }

    if (strpos($url, 'modules.php?module=') === 0) {
        return $current === 'modules.php'
            && $currentModule === substr(
                $url,
                strlen('modules.php?module=')
            );
    }

    if (strpos($url, 'portal.php?page=') === 0) {
        return $current === 'portal.php'
            && $currentPage === substr(
                $url,
                strlen('portal.php?page=')
            );
    }

    return $current === $url;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ELTNHS Student Information System</title>

    <script>
        try {
            if (localStorage.getItem('sidebarCollapsed') === '1') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (e) {}
    </script>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../assets/style.css">

    <style>
        .topbar-left {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .sidebar-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: #fff;
            color: #1a2f5e;
            font-size: 1.25rem;
            line-height: 1;
            cursor: pointer;
        }

        .sidebar-toggle:hover {
            background: #f1f4fa;
        }

        .sidebar-toggle:focus-visible {
            outline: 2px solid #0d6efd;
            outline-offset: 2px;
        }

        .sidebar,
        .app-layout {
            transition:
                width .2s ease,
                min-width .2s ease,
                flex-basis .2s ease,
                grid-template-columns .2s ease;
        }

        html.sidebar-collapsed .sidebar {
            width: 72px !important;
            min-width: 72px !important;
            flex: 0 0 72px !important;
            overflow-x: hidden;
        }

        html.sidebar-collapsed .app-layout {
            grid-template-columns: 72px minmax(0, 1fr) !important;
        }

        html.sidebar-collapsed .sidebar-heading {
            visibility: hidden;
            height: 0;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        html.sidebar-collapsed .side-link {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
            gap: 0;
        }

        html.sidebar-collapsed .side-link > span:not(.side-icon) {
            display: none;
        }

        html.sidebar-collapsed .side-link .side-icon {
            margin: 0;
        }
    </style>
</head>

<body>
    <header class="topbar">
        <div class="topbar-left">
            <button
                type="button"
                id="sidebarToggle"
                class="sidebar-toggle"
                aria-label="Toggle sidebar menu"
                aria-controls="sidebar"
                aria-expanded="true"
                title="Collapse / expand menu"
            >
                &#9776;
            </button>

            <a class="brand" href="../shared/dashboard.php">
                <img
                    src="../assets/school_logo.png"
                    alt="School logo"
                    style="width:43px;height:43px;object-fit:contain;background:white;border-radius:50%;padding:3px"
                >

                <span>
                    <strong>ELTNHS</strong>
                    <small>Student Information System</small>
                </span>
            </a>
        </div>

        <div class="topbar-right">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php
                $nq = $GLOBALS['pdo']->prepare("
                    SELECT COUNT(*)
                    FROM user_notifications
                    WHERE user_id = ?
                      AND is_read = 0
                ");

                $nq->execute([$_SESSION['user_id']]);
                $ncount = (int) $nq->fetchColumn();
                ?>

                <a
                    class="btn btn-sm btn-outline-primary me-2"
                    href="../shared/notifications.php"
                >
                    Notifications (<?= $ncount ?>)
                </a>
            <?php endif; ?>

            <span class="account-label">
                <?= e($_SESSION['username'] ?? '') ?>
                <b><?= e($role) ?></b>
            </span>

            <a class="logout-link" href="../auth/logout.php">
                Log out
            </a>
        </div>
    </header>

    <div class="app-layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-heading">
                MAIN MENU
            </div>

            <nav class="side-nav">
                <?php foreach (($menus[$role] ?? []) as $item): ?>
                    <?php
                    $active = menuActive(
                        $item[1],
                        $current,
                        $currentModule,
                        $currentPage
                    );
                    ?>

                    <a
                        class="side-link <?= $active ? 'active' : '' ?>"
                        href="<?= e($item[1]) ?>"
                        title="<?= e($item[0]) ?>"
                    >
                        <span class="side-icon">
                            <?= e(substr($item[2], 0, 1)) ?>
                        </span>

                        <span>
                            <?= e($item[0]) ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <script>
            (function () {
                var root = document.documentElement;
                var btn = document.getElementById('sidebarToggle');

                function sync() {
                    btn.setAttribute(
                        'aria-expanded',
                        root.classList.contains('sidebar-collapsed')
                            ? 'false'
                            : 'true'
                    );
                }

                btn.addEventListener('click', function () {
                    var collapsed = root.classList.toggle('sidebar-collapsed');

                    try {
                        localStorage.setItem(
                            'sidebarCollapsed',
                            collapsed ? '1' : '0'
                        );
                    } catch (e) {}

                    sync();
                });

                sync();
            })();
        </script>

        <main class="main-content">
            <?php showMessage(); ?>
