<?php
require_once 'Database.php';

session_start();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $db = new Database();
    
    $stmt = $db->query("SELECT * FROM students WHERE email = ? AND password = ?", [$email, $password]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        $_SESSION['student_logged_in'] = true;
        $_SESSION['student_account_id'] = $student['id'];
        $_SESSION['student_name'] = $student['full_name'];
        $_SESSION['student_email'] = $student['email'];
        
        header("Location: courses.php");
        exit;
    } else {
        $msg = "Invalid email or password. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - IEEE MIU</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="auth-page">

<main class="auth-container">
    <div class="glass-panel auth-card">
        <div class="auth-header">
            <div class="auth-logo">IEEE <span class="text-gradient">MIU</span></div>
            <h1>Student <span class="text-gradient">Portal</span></h1>
            <p>Access your courses and learning resources.</p>
        </div>
        
        <?php if($msg): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="styled-form">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <div class="input-with-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" class="form-input" required placeholder="student@example.com">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" class="form-input" required placeholder="••••••••">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                Enter Portal <i class="fas fa-sign-in-alt"></i>
            </button>
        </form>
        
        <div class="auth-footer">
            <p>Don't have an account? <a href="student_register.php" class="text-gradient">Register Now</a></p>
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
    max-width: 480px;
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
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.auth-footer p {
    font-size: 0.95rem;
    color: var(--text-muted);
}

.auth-footer a {
    text-decoration: none;
    font-weight: 600;
}

.back-link {
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

