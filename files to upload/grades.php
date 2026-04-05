<?php
require_once 'Auth.php';
require_once 'Database.php';

if (!isset($_SESSION['student_logged_in'])) { header("Location: student_login.php"); exit; }

$db = new Database();
$studentName = $_SESSION['student_name'];
$studentUniversityId = $_SESSION['student_university_id'] ?? 'N/A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grades - IEEE MIU</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --portal-bg: #0B0E14;
            --portal-sidebar: #0D1117;
            --portal-accent: #8B5CF6;
            --portal-accent-blue: #3B82F6;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }
        body { background: var(--portal-bg); color: var(--text-main); font-family: 'Inter', sans-serif; margin: 0; min-height: 100vh; }
        .portal-layout { display: flex; min-height: 100vh; width: 100%; }
        .portal-sidebar { width: 260px; background: var(--portal-sidebar); height: 100vh; position: fixed; left: 0; top: 0; border-right: 1px solid rgba(255,255,255,0.05); display: flex; flex-direction: column; z-index: 1000; }
        .portal-main-area { margin-left: 260px; flex-grow: 1; padding: 2.5rem; min-height: 100vh; width: calc(100% - 260px); }
        .portal-glass-panel { background: rgba(22, 27, 34, 0.7); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 20px; padding: 1.5rem; margin-top: 2rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { text-align: left; padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.05); }
        th { color: var(--text-muted); font-weight: 500; font-size: 0.85rem; }
        .sidebar-nav ul { list-style: none; padding: 1rem; margin: 0; }
        .sidebar-nav a { display: flex; align-items: center; gap: 1rem; padding: 0.8rem 1.2rem; color: var(--text-muted); text-decoration: none; border-radius: 12px; }
        .sidebar-nav li.active a { background: rgba(139, 92, 246, 0.1); color: var(--portal-accent); }
    </style>
    <link rel="stylesheet" href="style.css">
</head>
<body class="portal-body">
    <div class="portal-layout">
        <?php include 'student_sidebar.php'; ?>
        <main class="portal-main-area">
            <?php include 'student_header.php'; ?>
            <div class="portal-glass-panel">
                 <h3>Recent Results</h3>
                 <table>
                     <thead><tr><th>Course</th><th>Score</th><th>Status</th></tr></thead>
                     <tbody>
                         <tr><td style="font-weight:600;">Introduction to AI</td><td>85/100</td><td><span style="color:#10b981; background:rgba(16,185,129,0.1); padding:4px 10px; border-radius:8px; font-size:0.8rem; font-weight:700;">Passed</span></td></tr>
                         <tr><td style="font-weight:600;">Circuit Theory Lab</td><td>92/100</td><td><span style="color:#10b981; background:rgba(16,185,129,0.1); padding:4px 10px; border-radius:8px; font-size:0.8rem; font-weight:700;">Passed</span></td></tr>
                     </tbody>
                 </table>
            </div>
        </main>
    </div>
    <?php include 'student_scripts.php'; ?>
</body>
</html>
