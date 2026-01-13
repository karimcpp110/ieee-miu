<?php require_once 'Auth.php'; ?>
<nav class="glass-panel" style="margin-bottom: 2rem; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 1rem; z-index: 100;">
    <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary-neon);">
        IEEE <span style="color: var(--text-main);">MIU</span>
    </div>
    <div style="display: flex; gap: 1.5rem; align-items: center;">
        <a href="index.php" class="nav-link">Home</a>
        <a href="courses.php" class="nav-link">Courses</a>
        <?php if (Auth::check()): ?>
            <a href="dashboard.php" class="nav-link" style="color: var(--secondary-neon);">Dashboard</a>
            <a href="logout.php" class="nav-link">Logout</a>
        <?php elseif (isset($_SESSION['student_logged_in'])): ?>
            <span style="color: var(--secondary-neon); margin-right: 1rem;">Hi, <?= htmlspecialchars($_SESSION['student_name']) ?></span>
            <a href="logout.php" class="nav-link">Logout</a>
        <?php else: ?>
            <a href="student_login.php" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">Student Login</a>
            <a href="login.php" style="font-size: 0.9rem; opacity: 0.7; color: var(--text-muted); text-decoration: none;">Admin</a>
        <?php endif; ?>
    </div>
</nav>

<style>
.nav-link {
    color: var(--text-muted);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s;
}
.nav-link:hover {
    color: var(--primary-neon);
}
</style>
