<?php
require_once 'Auth.php';
session_start();

// Allow both admins and students
$isStudent = isset($_SESSION['student_logged_in']);
$isAdmin   = Auth::check();

if (!$isStudent && !$isAdmin) {
    header("Location: student_login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support - IEEE MIU Portal</title>
    <link rel="stylesheet" href="portal-style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="portal-body">

<div class="portal-layout">
    <?php if ($isStudent): ?>
        <?php include 'student_sidebar.php'; ?>
    <?php endif; ?>

    <main class="portal-main-area" style="<?= !$isStudent ? 'margin-left:0; max-width:900px; margin:0 auto; padding:3rem 2rem;' : '' ?>">

        <?php if ($isAdmin): include 'navbar.php'; endif; ?>

        <div style="margin-bottom: 2rem;">
            <h1 style="font-size: 2rem; font-weight: 800; margin:0;">
                <span class="text-gradient">Support</span> & Contact
            </h1>
            <p style="color: var(--text-muted, #94A3B8); margin-top: 0.5rem;">
                Need help? Reach out to the platform developer directly.
            </p>
        </div>

        <!-- Developer Card -->
        <div style="
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            padding: 2.5rem;
            max-width: 640px;
            display: flex;
            gap: 2rem;
            align-items: flex-start;
            backdrop-filter: blur(10px);
            box-shadow: 0 20px 60px rgba(139,92,246,0.15);
        ">
            <!-- Avatar -->
            <div style="flex-shrink:0;">
                <img src="https://ui-avatars.com/api/?name=Karim+Wael&background=8B5CF6&color=fff&size=100"
                     alt="Karim Wael"
                     style="width:90px; height:90px; border-radius:50%; border: 3px solid rgba(139,92,246,0.5); box-shadow: 0 0 20px rgba(139,92,246,0.4);">
            </div>

            <!-- Info -->
            <div style="flex:1;">
                <h2 style="margin:0 0 0.25rem; font-size:1.5rem; font-weight:800;">Karim Wael</h2>
                <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-bottom:1.2rem;">
                    <span style="background:rgba(139,92,246,0.15); color:#a78bfa; font-size:0.75rem; padding:3px 10px; border-radius:20px; border:1px solid rgba(139,92,246,0.3); font-weight:600;">
                        <i class="fas fa-code" style="margin-right:4px;"></i> Web Developer
                    </span>
                    <span style="background:rgba(59,130,246,0.15); color:#60a5fa; font-size:0.75rem; padding:3px 10px; border-radius:20px; border:1px solid rgba(59,130,246,0.3); font-weight:600;">
                        <i class="fas fa-flask" style="margin-right:4px;"></i> Co-Head, R&amp;D Committee
                    </span>
                    <span style="background:rgba(16,185,129,0.1); color:#34d399; font-size:0.75rem; padding:3px 10px; border-radius:20px; border:1px solid rgba(16,185,129,0.3); font-weight:600;">
                        <i class="fas fa-university" style="margin-right:4px;"></i> IEEE MIU Student Branch
                    </span>
                </div>

                <div style="display:flex; flex-direction:column; gap:0.8rem;">
                    <a href="mailto:kwael7934@gmail.com"
                       style="display:flex; align-items:center; gap:0.75rem; color:inherit; text-decoration:none; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07); border-radius:10px; padding:0.75rem 1rem; transition:all 0.2s ease;"
                       onmouseover="this.style.background='rgba(139,92,246,0.1)'; this.style.borderColor='rgba(139,92,246,0.4)';"
                       onmouseout="this.style.background='rgba(255,255,255,0.03)'; this.style.borderColor='rgba(255,255,255,0.07)';">
                        <i class="fas fa-envelope" style="color:#a78bfa; width:18px; text-align:center;"></i>
                        <div>
                            <div style="font-size:0.7rem; color:#94A3B8; text-transform:uppercase; letter-spacing:1px;">Personal Email</div>
                            <div style="font-weight:600; font-size:0.95rem;">kwael7934@gmail.com</div>
                        </div>
                    </a>

                    <a href="mailto:karim2405560@miuegypt.edu.eg"
                       style="display:flex; align-items:center; gap:0.75rem; color:inherit; text-decoration:none; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07); border-radius:10px; padding:0.75rem 1rem; transition:all 0.2s ease;"
                       onmouseover="this.style.background='rgba(59,130,246,0.1)'; this.style.borderColor='rgba(59,130,246,0.4)';"
                       onmouseout="this.style.background='rgba(255,255,255,0.03)'; this.style.borderColor='rgba(255,255,255,0.07)';">
                        <i class="fas fa-university" style="color:#60a5fa; width:18px; text-align:center;"></i>
                        <div>
                            <div style="font-size:0.7rem; color:#94A3B8; text-transform:uppercase; letter-spacing:1px;">IEEE / University Email</div>
                            <div style="font-weight:600; font-size:0.95rem;">karim2405560@miuegypt.edu.eg</div>
                        </div>
                    </a>

                    <a href="https://www.linkedin.com/in/karim-wael-40132b360/" target="_blank"
                       style="display:flex; align-items:center; gap:0.75rem; color:inherit; text-decoration:none; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07); border-radius:10px; padding:0.75rem 1rem; transition:all 0.2s ease;"
                       onmouseover="this.style.background='rgba(10,102,194,0.1)'; this.style.borderColor='rgba(10,102,194,0.5)';"
                       onmouseout="this.style.background='rgba(255,255,255,0.03)'; this.style.borderColor='rgba(255,255,255,0.07)';">
                        <i class="fab fa-linkedin" style="color:#0a66c2; width:18px; text-align:center; font-size:1.1rem;"></i>
                        <div>
                            <div style="font-size:0.7rem; color:#94A3B8; text-transform:uppercase; letter-spacing:1px;">LinkedIn</div>
                            <div style="font-weight:600; font-size:0.95rem;">Karim Wael · LinkedIn Profile</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Response time note -->
        <p style="color:#94A3B8; font-size:0.85rem; margin-top:1.5rem;">
            <i class="fas fa-clock" style="margin-right:6px;"></i>
            Typical response time: <strong style="color:#fff;">within 24 hours</strong> on business days.
        </p>

        <a href="student_dashboard.php" style="display:inline-flex; align-items:center; gap:0.5rem; margin-top:1rem; color:#a78bfa; text-decoration:none; font-weight:600; font-size:0.9rem;">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

    </main>
</div>

<style>
    body.portal-body {
        background: #0D1117;
        margin: 0;
        font-family: 'Inter', sans-serif;
        color: #fff;
        min-height: 100vh;
    }
    :root {
        --portal-bg: #0D1117;
        --text-muted: #94A3B8;
    }
    .portal-layout {
        display: flex;
        min-height: 100vh;
    }
    .portal-main-area {
        margin-left: 260px;
        flex: 1;
        padding: 2.5rem 2rem;
    }
    .text-gradient {
        background: linear-gradient(135deg, #a78bfa, #60a5fa);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    @media (max-width: 768px) {
        .portal-main-area { margin-left: 0; padding: 1.5rem; }
    }
</style>

</body>
</html>
