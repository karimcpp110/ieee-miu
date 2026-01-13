<?php
require_once 'Database.php';
require_once 'Auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    // Quick hack: Auth::login needs the raw PDO object, simple fix:
    // We will update Auth.php to take Database instance or just expose pdo from Database class.
    // For now, let's just make Database expose pdo for this purpose, or verify manually here.
    
    // Better: Helper in Database class or just Auth singleton. 
    // Let's rely on Database class providing a query method and do it manually here or update Auth.
    // I'll update Auth to accept the Database object wrapper I built.
    
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Re-implementing simplified login logic here to avoid rewriting Auth.php right now if it expects raw PDO
    // Actually, let's just use the Database wrapper helper.
    $stmt = $db->query("SELECT * FROM users WHERE username = ?", [$username]);
    $user = $stmt->fetch();

    if ($user && $user['password'] === $password) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid credentials";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - IEEE MIU</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="display: flex; justify-content: center; align-items: center; height: 100vh;">

    <div class="glass-panel" style="padding: 3rem; width: 100%; max-width: 400px; text-align: center;">
        <h2 style="margin-bottom: 2rem; color: var(--primary-neon);">Admin Login</h2>
        
        <?php if($error): ?>
            <div style="background: rgba(255, 0, 0, 0.2); color: #ff5555; padding: 0.8rem; border-radius: 8px; margin-bottom: 1rem;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <input type="text" name="username" class="form-input" placeholder="Username" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" class="form-input" placeholder="Password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
        </form>
        <p style="margin-top: 1.5rem; color: var(--text-muted); font-size: 0.9rem;">
            <a href="index.php" style="color: var(--text-muted);">Back to Home</a>
        </p>
    </div>

</body>
</html>
