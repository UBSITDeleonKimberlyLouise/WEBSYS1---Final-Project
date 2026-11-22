<?php
chdir(dirname(__DIR__));

class Auth {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function login($username, $password) {
        $sql = "SELECT * FROM users WHERE username = ? AND account_status = 'Active' LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['must_change_password'] = $user['must_change_password'];
            return true;
        }
        return false;
    }

    public function changePassword($user_id, $new_password) {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE user_id = ?');
        return $stmt->execute([$hash, $user_id]);
    }
}
?>
