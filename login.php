<?php
require_once 'Database.php';
require_once 'Auth.php';

if (Auth::check()) {
    header("Location: dashboard.php");
    exit;
}

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
    <title>Admin Access - IEEE MIU</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="auth-page">

    <main class="auth-container">
        <div class="glass-panel auth-card">
            <div class="auth-header" style="text-align: center; margin-bottom: 3rem;">
                <img src="logo.png?v=1" alt="IEEE MIU Logo"
                    style="height: 80px; width: auto; margin-bottom: 2rem; display: block; margin-left: auto; margin-right: auto;">
                <h1>Admin <span class="text-gradient">Access</span></h1>
                <p>Management dashboard authentication.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-lock"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="styled-form">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user-shield"></i>
                        <input type="text" name="username" class="form-input" required placeholder="admin_user">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-key"></i>
                        <input type="password" name="password" class="form-input" required placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-full">
                    Secure Login <i class="fas fa-shield-alt"></i>
                </button>
            </form>

            <div class="auth-footer">
                <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Home</a>
            </div>
        </div>
    </main>

    <style>
        .auth-page {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem;
        }

        .auth-container {
            width: 100%;
            max-width: 440px;
        }

        .auth-card {
            padding: clamp(2rem, 8vw, 4rem);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .auth-logo {
            font-size: 1.2rem;
            font-weight: 800;
            margin-bottom: 1rem;
            letter-spacing: -1px;
        }

        .auth-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .auth-header p {
            color: var(--text-muted);
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .input-with-icon .form-input {
            padding-left: 2.8rem;
        }

        .btn-full {
            width: 100%;
            justify-content: center;
            margin-top: 1rem;
        }

        .auth-footer {
            text-align: center;
            margin-top: 2.5rem;
        }

        .back-link {
            text-decoration: none;
            font-weight: 600;
            color: var(--text-muted);
            font-size: 0.85rem;
            transition: var(--transition);
        }

        .back-link:hover {
            color: var(--text-main);
        }

        .alert {
            padding: 1rem;
            background: rgba(255, 71, 87, 0.1);
            color: #ff4757;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
            border: 1px solid rgba(255, 71, 87, 0.2);
        }
    </style>

</body>

</html>