<?php
session_start();

class Auth {
    public static function login($username, $password, $pdo) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['password'] === $password) { // Plaintext for demo as planned, hash recommended
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            return true;
        }
        return false;
    }

    public static function logout() {
        session_destroy();
    }

    public static function check() {
        return isset($_SESSION['user_id']);
    }
}
