<?php
chdir(dirname(__DIR__));

/** Central audience rules used by every announcements viewer. */
function announcementAudiencesForRole(string $role): array {
    $map = [
        'System Administrator' => ['All'],
        'School Registrar' => ['All', 'Registrar'],
        'Guidance Counselor' => ['All'],
        'Teacher Adviser' => ['All', 'Teachers', 'Teacher Advisers'],
        'Subject Teacher' => ['All', 'Teachers', 'Subject Teachers'],
        'Student' => ['All', 'Students'],
        'Clinic Personnel' => ['All'],
    ];
    return $map[$role] ?? ['All'];
}
function visibleAnnouncements(PDO $pdo, string $role): array {
    $audiences = announcementAudiencesForRole($role);
    $marks = implode(',', array_fill(0, count($audiences), '?'));
    $stmt = $pdo->prepare("SELECT a.*, u.username AS author_name FROM announcements a LEFT JOIN users u ON u.user_id=a.posted_by WHERE a.announcement_status='Published' AND a.publish_date<=NOW() AND (a.expiry_date IS NULL OR a.expiry_date>=NOW()) AND a.target_audience IN ($marks) ORDER BY a.publish_date DESC, a.announcement_id DESC");
    $stmt->execute($audiences);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
