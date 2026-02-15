<?php require_once 'Auth.php'; ?>
<nav class="glass-panel main-nav">
    <div class="nav-container">
        <div class="nav-logo">
            <a href="index.php" style="display: flex; align-items: center;">
                <img src="logo.png?v=1" alt="IEEE MIU Logo" style="height: 60px; width: auto;">
            </a>
        </div>

        <button class="hamburger" id="hamburger" aria-label="Toggle Navigation">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>

        <div class="nav-links" id="nav-links">
            <a href="index.php"
                class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">Home</a>
            <a href="courses.php"
                class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'courses.php' ? 'active' : '' ?>">Courses</a>
            <a href="events.php"
                class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'events.php' ? 'active' : '' ?>">Events</a>
            <a href="gallery.php"
                class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'gallery.php' ? 'active' : '' ?>">Gallery</a>
            <a href="board.php"
                class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'board.php' ? 'active' : '' ?>">Board</a>
            <?php if (Auth::check()): ?>
                <a href="dashboard.php" class="nav-link dashboard-link">Dashboard</a>
                <a href="logout.php" class="btn btn-outline" style="padding: 0.4rem 1rem;">Logout</a>
            <?php elseif (isset($_SESSION['student_logged_in'])): ?>
                <div class="user-pill glass-panel">
                    <span class="hi-text">
                        <a href="student_dashboard.php" style="text-decoration: none; color: inherit;">
                            Hi, <?= htmlspecialchars(explode(' ', $_SESSION['student_name'])[0]) ?>
                        </a>
                    </span>
                    <a href="logout.php" class="logout-icon" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            <?php else: ?>
                <div class="nav-auth">
                    <a href="login.php" class="admin-link">Admin</a>
                    <a href="student_login.php" class="btn btn-primary">Student Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<?php if (Auth::check()): ?>
    <!-- Persistent Admin Toolbar -->
    <div class="admin-toolbar glass-panel">
        <div class="toolbar-container">
            <div class="toolbar-brand">
                <span class="badge">ADMIN</span>
            </div>
            <div class="toolbar-shortcuts">
                <a href="dashboard.php?tab=registrations" title="Registrations"><i class="fas fa-users"></i></a>
                <a href="dashboard.php?tab=board" title="Board Members"><i class="fas fa-id-badge"></i></a>
                <a href="dashboard.php?tab=events" title="Events"><i class="fas fa-calendar-alt"></i></a>
                <a href="dashboard.php?tab=forms" title="Dynamic Forms"><i class="fas fa-poll"></i></a>
                <a href="dashboard.php?tab=courses" title="LMS Management"><i class="fas fa-book-open"></i></a>
            </div>
            <div class="toolbar-actions">
                <a href="dashboard.php" class="btn-dashboard">Dashboard</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    const hamburger = document.getElementById('hamburger');
    const navLinks = document.getElementById('nav-links');

    hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('active');
        navLinks.classList.toggle('active');
    });

    // Close menu on click outside
    document.addEventListener('click', (e) => {
        if (!hamburger.contains(e.target) && !navLinks.contains(e.target)) {
            hamburger.classList.remove('active');
            navLinks.classList.remove('active');
        }
    });
</script>

<style>
    .main-nav {
        margin: 1.5rem auto 3rem;
        padding: 0.8rem 2rem;
        position: sticky;
        top: 1.5rem;
        z-index: 1000;
        width: calc(100% - 2rem);
        max-width: 1200px;
        border-radius: var(--border-radius-xl);
        border: 1px solid rgba(255, 255, 255, 0.06);
    }

    .nav-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .nav-logo {
        font-size: 1.5rem;
        font-weight: 800;
        letter-spacing: -1px;
    }

    .nav-links {
        display: flex;
        gap: 2rem;
        align-items: center;
    }

    .nav-link {
        color: var(--text-muted);
        text-decoration: none;
        font-weight: 500;
        font-size: 0.95rem;
        transition: var(--transition);
        position: relative;
    }

    .nav-link:after {
        content: '';
        position: absolute;
        bottom: -5px;
        left: 0;
        width: 0;
        height: 2px;
        background: var(--primary-neon);
        transition: var(--transition);
    }

    .nav-link:hover,
    .nav-link.active {
        color: var(--text-main);
    }

    .nav-link:hover:after,
    .nav-link.active:after {
        width: 100%;
    }

    .dashboard-link {
        color: var(--secondary-neon) !important;
    }

    .user-pill {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.4rem 1rem;
        border-radius: 100px;
    }

    .hi-text {
        font-weight: 600;
        color: var(--primary-neon);
    }

    .logout-icon {
        color: var(--text-muted);
        transition: var(--transition);
    }

    .logout-icon:hover {
        color: #ff4757;
    }

    .nav-auth {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    /* Admin Toolbar Styles */
    .admin-toolbar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 45px;
        z-index: 1001;
        border-radius: 0;
        border: none;
        border-bottom: 1px solid var(--glass-border);
        padding: 0 2rem;
        display: flex;
        align-items: center;
        background: rgba(11, 15, 26, 0.8);
    }

    .toolbar-container {
        width: 100%;
        max-width: 1400px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .toolbar-brand .badge {
        background: var(--primary-neon);
        color: var(--bg-color);
        font-size: 0.7rem;
        padding: 0.2rem 0.6rem;
        font-weight: 800;
    }

    .toolbar-shortcuts {
        display: flex;
        gap: 1.5rem;
    }

    .toolbar-shortcuts a {
        color: var(--text-muted);
        font-size: 1rem;
        transition: var(--transition);
    }

    .toolbar-shortcuts a:hover {
        color: var(--primary-neon);
        transform: translateY(-2px);
    }

    .btn-dashboard {
        color: var(--text-main);
        text-decoration: none;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 0.3rem 0.8rem;
        background: var(--glass-bg-bright);
        border-radius: 4px;
        transition: var(--transition);
    }

    .btn-dashboard:hover {
        background: var(--primary-neon);
        color: var(--bg-color);
    }

    /* Adjust navbar for toolbar */
    <?php if (Auth::check()): ?>
        .main-nav {
            top: 3.5rem !important;
        }

    <?php endif; ?>

    .admin-link {
        color: var(--text-muted);
        text-decoration: none;
        font-size: 0.9rem;
        transition: var(--transition);
    }

    .admin-link:hover {
        color: var(--text-main);
    }

    /* Hamburger Styles */
    .hamburger {
        display: none;
        flex-direction: column;
        gap: 5px;
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px;
    }

    .hamburger .bar {
        width: 25px;
        height: 2px;
        background: var(--primary-neon);
        transition: var(--transition);
        border-radius: 2px;
    }

    .hamburger.active .bar:nth-child(1) {
        transform: translateY(7px) rotate(45deg);
    }

    .hamburger.active .bar:nth-child(2) {
        opacity: 0;
    }

    .hamburger.active .bar:nth-child(3) {
        transform: translateY(-7px) rotate(-45deg);
    }

    @media (max-width: 768px) {
        .hamburger {
            display: flex;
        }

        .nav-links {
            position: fixed;
            top: 5.5rem;
            left: 1rem;
            right: 1rem;
            flex-direction: column;
            background: rgba(11, 15, 26, 0.98);
            backdrop-filter: blur(20px);
            padding: 2rem;
            border-radius: 24px;
            border: 1px solid var(--glass-border);
            transform: translateY(-20px);
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }

        .nav-links.active {
            transform: translateY(0);
            opacity: 1;
            visibility: visible;
        }

        .nav-auth {
            flex-direction: column;
            width: 100%;
            gap: 1rem;
        }

        .nav-auth .btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>