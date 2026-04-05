<?php
// Self-contained admin login — no external Auth.php or class dependency
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect
if (!empty($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Direct DB connection (no class wrapper to fail)
    $host = 'sql100.infinityfree.com';
    $u    = 'if0_41134868';
    $p    = 'QQEdikbTFOdqmo';
    $db   = 'if0_41134868_miu';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $u, $p);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Brute-force protection (safe — skip if table missing)
        $locked = false;
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE (ip_address = ? OR username = ?) AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
            $stmt->execute([$ip, $username]);
            if ($stmt->fetchColumn() >= 5) {
                $locked = true;
                $error = "Account temporarily locked. Try again in 15 minutes.";
            }
        } catch (Exception $e) { /* Table missing — skip lockout check */ }

        if (!$locked) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
                // Clear failed attempts (safe)
                try { $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ? OR username = ?")->execute([$ip, $username]); } catch (Exception $e) {}

                // Ensure API key exists
                $apiKey = $user['api_key'] ?? '';
                if (empty($apiKey)) {
                    $apiKey = bin2hex(random_bytes(32));
                    try { $pdo->prepare("UPDATE users SET api_key = ? WHERE id = ?")->execute([$apiKey, $user['id']]); } catch (Exception $e) {}
                }

                // Upgrade plain-text password to hash (safe)
                if ($password === $user['password']) {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    try { $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $user['id']]); } catch (Exception $e) {}
                }

                // Set session
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'] ?? 'Admin';
                $_SESSION['email']    = $user['email'] ?? '';
                $_SESSION['api_key']  = $apiKey;

                // Load permissions (safe)
                try {
                    $st = $pdo->prepare("SELECT p.slug FROM permissions p JOIN role_permissions rp ON p.id = rp.permission_id JOIN roles r ON r.id = rp.role_id WHERE r.name = ?");
                    $st->execute([$_SESSION['role']]);
                    $_SESSION['permissions'] = $st->fetchAll(PDO::FETCH_COLUMN);
                } catch (Exception $e) { $_SESSION['permissions'] = []; }

                // Update login stats (safe)
                try { $pdo->prepare("INSERT INTO user_stats (user_id, last_login) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE last_login = NOW()")->execute([$user['id']]); } catch (Exception $e) {}

                header("Location: dashboard.php");
                exit;
            } else {
                // Record failed attempt (safe)
                try { $pdo->prepare("INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)")->execute([$ip, $username]); } catch (Exception $e) {}
                $error = "Invalid username or password.";
            }
        }
    } catch (PDOException $e) {
        $error = "Database connection failed: " . htmlspecialchars($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Access - IEEE MIU</title>
    <link rel="stylesheet" href="portal-style.css?v=<?= time() ?>">
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
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="styled-form">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user-shield"></i>
                        <input type="text" name="username" class="form-input" required placeholder="admin_user"
                            autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-key"></i>
                        <input type="password" name="password" class="form-input" required placeholder="••••••••"
                            autocomplete="current-password">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-full">
                    Secure Login <i class="fas fa-shield-alt"></i>
                </button>
            </form>

            <div class="auth-footer" style="display: flex; flex-direction: column; gap: 1rem; text-align: center; margin-top: 2.5rem;">
                <a href="forgot_password.php" class="back-link">Forgot Password?</a>
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
