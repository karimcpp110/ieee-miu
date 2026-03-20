<?php
session_start();

class Auth
{
    public static function login($username, $password, $pdo)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // 1. Check for lockout (5 attempts in 15 minutes)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE (ip_address = ? OR username = ?) AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $stmt->execute([$ip, $username]);
        if ($stmt->fetchColumn() >= 5) {
            return 'locked';
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
            // Success! Clear failed attempts
            $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ? OR username = ?")->execute([$ip, $username]);

            // Upgrade to hash if it was plain text
            if ($password === $user['password']) {
                $newHash = password_hash($password, PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newHash, $user['id']]);
            }
            // ... rest of success logic (sessions, stats)
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = isset($user['role']) ? $user['role'] : 'Admin';
            $_SESSION['email'] = isset($user['email']) ? $user['email'] : '';

            try {
                $stmt = $pdo->prepare("SELECT p.slug FROM permissions p 
                                     JOIN role_permissions rp ON p.id = rp.permission_id 
                                     JOIN roles r ON r.id = rp.role_id 
                                     WHERE r.name = ?");
                $stmt->execute([$_SESSION['role']]);
                $_SESSION['permissions'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

                $pdo->prepare("INSERT INTO user_stats (user_id, last_login) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE last_login = NOW()")->execute([$user['id']]);
            } catch (Exception $e) {
                $_SESSION['permissions'] = [];
            }
            return true;
        }

        // Failure: Record the attempt
        $pdo->prepare("INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)")->execute([$ip, $username]);
        return false;
    }

    public static function hasPermission($permission)
    {
        if (!isset($_SESSION['permissions']))
            return false;
        if (self::isAdmin())
            return true; // Admins have all permissions
        return in_array($permission, $_SESSION['permissions']);
    }

    public static function logout()
    {
        session_unset();
        session_destroy();
    }

    // CSRF Protection
    public static function generateCSRFToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCSRFToken($token)
    {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            return false;
        }
        return true;
    }

    public static function check()
    {
        return isset($_SESSION['user_id']);
    }

    public static function getRole()
    {
        if (!isset($_SESSION['role']) && isset($_SESSION['user_id'])) {
            return 'Admin'; // Fallback for old sessions
        }
        return isset($_SESSION['role']) ? $_SESSION['role'] : 'Guest';
    }

    public static function isAdmin()
    {
        return self::getRole() === 'Admin';
    }

    public static function isHR()
    {
        return self::getRole() === 'HR' || self::isAdmin();
    }

    public static function isInstructor()
    {
        return self::getRole() === 'Instructor' || self::isAdmin();
    }

    // --- PASSWORD RESET LOGIC ---

    public static function requestReset($email, $pdo)
    {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if (!$stmt->fetch())
            return false;

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
        return $stmt->execute([$token, $expires, $email]) ? $token : false;
    }

    public static function verifyToken($token, $pdo)
    {
        $stmt = $pdo->prepare("SELECT email FROM users WHERE reset_token = ? AND reset_expires > NOW()");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    public static function resetPassword($token, $newPassword, $pdo)
    {
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?");
        return $stmt->execute([$newPassword, $token]);
    }
}
