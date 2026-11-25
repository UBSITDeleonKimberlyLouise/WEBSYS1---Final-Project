<?php

function registeredNamePassword(PDO $pdo, int $userId): string
{
    $stmt = $pdo->prepare("SELECT COALESCE(s.first_name, sp.first_name, t.first_name) AS first_name,
        COALESCE(s.last_name, sp.last_name, t.last_name) AS last_name
        FROM users u
        LEFT JOIN students s ON s.user_id = u.user_id
        LEFT JOIN staff_profiles sp ON sp.user_id = u.user_id
        LEFT JOIN teachers t ON t.user_id = u.user_id
        WHERE u.user_id = ? AND u.account_status = 'Active'
        AND u.role <> 'System Administrator'");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || empty($user['first_name']) || empty($user['last_name'])) {
        throw new RuntimeException('Active account with complete registered name not found.');
    }
    $password = preg_replace('/[^a-z0-9]/', '', strtolower($user['first_name'] . $user['last_name']));
    if ($password === '') {
        throw new RuntimeException('Unable to generate temporary password.');
    }
    return $password;
}
