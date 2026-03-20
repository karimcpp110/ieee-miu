<?php
require_once 'Auth.php';
require_once 'Database.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $email = $_POST['email'];

    $token = Auth::requestReset($email, $db->getPDO());
    if ($token) {
        // In a real app, send email. For demo/InfinityFree, show link.
        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
        $message = "Click the link below to reset your password (normally sent via email):<br><a href='$resetLink'>$resetLink</a>";
    } else {
        $error = "Email address not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Forgot Password - IEEE MIU</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="auth-page">
    <div class="glass-panel auth-card">
        <h1 class="text-gradient">Forgot Password</h1>
        <p>Enter your account email to receive a reset link.</p>

        <?php if ($message): ?>
            <div class="alert success">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="styled-form">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-input" required placeholder="name@example.com">
            </div>
            <button type="submit" class="btn btn-primary btn-full">Request Reset Link</button>
        </form>

        <div class="auth-footer">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</body>

</html>