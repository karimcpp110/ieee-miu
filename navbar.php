<?php require_once 'Auth.php'; ?>
<nav class="glass-panel main-nav">
    <div class="nav-logo">
        IEEE <span style="color: var(--text-main);">MIU</span>
    </div>
    
    <button class="hamburger" id="hamburger">
        <i class="fas fa-bars"></i>
    </button>

    <div class="nav-links" id="nav-links">
        <a href="index.php" class="nav-link">Home</a>
        <a href="courses.php" class="nav-link">Courses</a>
        <?php if (Auth::check()): ?>
            <a href="dashboard.php" class="nav-link dashboard-link">Dashboard</a>
            <a href="logout.php" class="nav-link">Logout</a>
        <?php elseif (isset($_SESSION['student_logged_in'])): ?>
            <div class="user-info">
                <span class="hi-text">Hi, <?= htmlspecialchars($_SESSION['student_name']) ?></span>
                <a href="logout.php" class="nav-link">Logout</a>
            </div>
        <?php else: ?>
            <a href="student_login.php" class="btn btn-primary nav-btn">Student Login</a>
            <a href="login.php" class="admin-link">Admin</a>
        <?php endif; ?>
    </div>
</nav>

<script>
document.getElementById('hamburger').addEventListener('click', function() {
    document.getElementById('nav-links').classList.toggle('active');
});
</script>

<style>
.main-nav {
    margin-bottom: 2rem;
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 1rem;
    z-index: 1000; /* Higher z-index for visibility */
}

.nav-links {
    display: flex;
    gap: 1.5rem;
    align-items: center;
}

.nav-link {
    color: var(--text-muted);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
}

.nav-link:hover {
    color: var(--primary-neon);
}

.dashboard-link {
    color: var(--secondary-neon) !important;
}

.hi-text {
    color: var(--secondary-neon);
    margin-right: 1rem;
    font-weight: 600;
}

.nav-btn {
    padding: 0.5rem 1rem !important;
    font-size: 0.9rem !important;
}

.admin-link {
    font-size: 0.9rem;
    opacity: 0.7;
    color: var(--text-muted);
    text-decoration: none;
}

.hamburger {
    display: none;
    background: none;
    border: none;
    color: var(--primary-neon);
    font-size: 1.5rem;
    cursor: pointer;
}

/* Mobile Adjustments for Navbar */
@media (max-width: 768px) {
    .main-nav {
        padding: 0.8rem 1.2rem;
    }
    
    .hamburger {
        display: block;
    }
    
    .nav-links {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        flex-direction: column;
        background: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(20px);
        padding: 1.5rem;
        gap: 1.5rem;
        border: 1px solid var(--glass-border);
        border-top: none;
        border-radius: 0 0 16px 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    
    .nav-links.active {
        display: flex;
        animation: slideDown 0.3s ease forwards;
    }
    
    .user-info {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
