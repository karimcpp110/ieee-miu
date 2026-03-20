<?php
require_once 'Auth.php';
require_once 'Database.php';

$db = new Database();
$token = $_GET['token'] ?? '';
$user = Auth::verifyToken($token, $db->getPDO());

if (!$user) {
    die("Invalid or expired token.");
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPass = $_POST['password'];
    $confirmPass = $_POST['confirm_password'];

    if ($newPass !== $confirmPass) {
        $message = "<div class='alert error'>Passwords do not match!</div>";
    } else {
        if (Auth::resetPassword($token, $newPass, $db->getPDO())) {
            header("Location: login.php?reset=success");
            exit;
        } else {
            $message = "<div class='alert error'>Failed to reset password.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reset Password - IEEE MIU</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="auth-page">
    <div class="glass-panel auth-card">
        <h1 class="text-gradient">Reset Password</h1>
        <p>Enter a new password for your account.</p>

        <?= $message ?>

        <form method="POST" class="styled-form">
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="password" class="form-input" required minlength="6">
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-input" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary btn-full">Reset Password</button>
        </form>
    </div>
</body>

</html>