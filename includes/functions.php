<?php
chdir(dirname(__DIR__));

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../auth/login.php');
        exit;
    }
    // Re-check account status on every protected request so archived users lose access immediately.
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
        $statusCheck = $GLOBALS['pdo']->prepare('SELECT account_status FROM users WHERE user_id=?');
        $statusCheck->execute([$_SESSION['user_id']]);
        if ($statusCheck->fetchColumn() !== 'Active') {
            $_SESSION = [];
            session_destroy();
            header('Location: ../auth/login.php');
            exit;
        }
    }
    if (!empty($_SESSION['must_change_password']) && basename($_SERVER['PHP_SELF']) !== 'change_password.php' && basename($_SERVER['PHP_SELF']) !== 'logout.php') {
        header('Location: ../auth/change_password.php');
        exit;
    }
}

function requireRole($allowed_roles) {
    requireLogin();
    if (!in_array($_SESSION['role'], $allowed_roles, true)) {
        http_response_code(403);
        exit('You do not have permission to open this page.');
    }
}

function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(400);
            exit('Invalid form request. Please go back and try again.');
        }
    }
}

function showMessage() {
    if (!empty($_SESSION['message'])) {
        echo '<div class="message">' . e($_SESSION['message']) . '</div>';
        unset($_SESSION['message']);
    }
}

function redirectModule($module) {
    header('Location: ../shared/modules.php?module=' . urlencode($module));
    exit;
}
?>
