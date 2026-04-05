<?php
// student_sidebar.php
if (!isset($_SESSION['student_logged_in'])) exit;
$current_page = basename($_SERVER['PHP_SELF']);
$_sid = $_SESSION['student_account_id'] ?? 0;
?>
<aside class="portal-sidebar" style="width: 260px; background: #0D1117; height: 100vh; position: fixed; left: 0; top: 0; border-right: 1px solid rgba(255,255,255,0.05); display: flex; flex-direction: column; z-index: 1000; font-family: 'Inter', sans-serif;">
    <div style="padding:2rem 1.5rem; display:flex; gap:1rem; align-items:center;">
        <img src="logo.png" alt="Logo" style="width:40px !important; height:40px !important; max-width:40px !important; object-fit:contain;">
        <div>
            <h3 style="margin:0; font-size:1.1rem; letter-spacing:1px; color:#f8fafc;">IEEE</h3>
            <p style="margin:0; font-size:0.7rem; color:#94A3B8;">MIU Student Branch</p>
        </div>
    </div>
    
    <nav class="sidebar-nav" style="flex-grow: 1; overflow-y: auto;">
        <ul style="list-style: none; padding: 1rem; margin:0;">
            <li class="<?= $current_page == 'student_dashboard.php' ? 'active' : '' ?>">
                <a href="student_dashboard.php" style="display:flex; align-items:center; gap:1rem; padding:0.8rem 1.2rem; color:<?= $current_page == 'student_dashboard.php' ? '#8B5CF6' : '#94A3B8' ?>; <?= $current_page == 'student_dashboard.php' ? 'background: rgba(139, 92, 246, 0.1);' : '' ?> text-decoration:none; border-radius:12px; font-weight:500; font-size:0.95rem;">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>
            </li>
            <li class="<?= $current_page == 'my_courses.php' ? 'active' : '' ?>">
                <a href="my_courses.php" style="display:flex; align-items:center; gap:1rem; padding:0.8rem 1.2rem; color:<?= $current_page == 'my_courses.php' ? '#8B5CF6' : '#94A3B8' ?>; <?= $current_page == 'my_courses.php' ? 'background: rgba(139, 92, 246, 0.1);' : '' ?> text-decoration:none; border-radius:12px; font-weight:500; font-size:0.95rem;">
                    <i class="fas fa-book"></i> My Courses
                </a>
            </li>
            <li class="<?= $current_page == 'grades.php' ? 'active' : '' ?>">
                <a href="grades.php" style="display:flex; align-items:center; gap:1rem; padding:0.8rem 1.2rem; color:<?= $current_page == 'grades.php' ? '#8B5CF6' : '#94A3B8' ?>; <?= $current_page == 'grades.php' ? 'background: rgba(139, 92, 246, 0.1);' : '' ?> text-decoration:none; border-radius:12px; font-weight:500; font-size:0.95rem;">
                    <i class="fas fa-edit"></i> Grades
                </a>
            </li>
            <li class="<?= $current_page == 'library.php' ? 'active' : '' ?>">
                <a href="library.php" style="display:flex; align-items:center; gap:1rem; padding:0.8rem 1.2rem; color:<?= $current_page == 'library.php' ? '#8B5CF6' : '#94A3B8' ?>; <?= $current_page == 'library.php' ? 'background: rgba(139, 92, 246, 0.1);' : '' ?> text-decoration:none; border-radius:12px; font-weight:500; font-size:0.95rem;">
                    <i class="fas fa-book-reader"></i> Library
                </a>
            </li>
            <li class="<?= $current_page == 'profile.php' ? 'active' : '' ?>">
                <a href="profile.php?id=<?= $_sid ?>" style="display:flex; align-items:center; gap:1rem; padding:0.8rem 1.2rem; color:<?= $current_page == 'profile.php' ? '#8B5CF6' : '#94A3B8' ?>; <?= $current_page == 'profile.php' ? 'background: rgba(139, 92, 246, 0.1);' : '' ?> text-decoration:none; border-radius:12px; font-weight:500; font-size:0.95rem;">
                    <i class="fas fa-user"></i> Profile
                </a>
            </li>
            
            <!-- NEW LINKS ADDED FOR WEBSITE NAVIGATION -->
            <li style="margin: 1.5rem 1rem 0.5rem 1.2rem; font-size: 0.75rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 1px;">Website</li>
            
            <li>
                <a href="index.php" style="display:flex; align-items:center; gap:1rem; padding:0.8rem 1.2rem; color:#94A3B8; text-decoration:none; border-radius:12px; font-weight:500; font-size:0.95rem;">
                    <i class="fas fa-home"></i> Home
                </a>
            </li>
            <li class="<?= $current_page == 'board.php' ? 'active' : '' ?>">
                <a href="board.php" style="display:flex; align-items:center; gap:1rem; padding:0.8rem 1.2rem; color:<?= $current_page == 'board.php' ? '#8B5CF6' : '#94A3B8' ?>; <?= $current_page == 'board.php' ? 'background: rgba(139, 92, 246, 0.1);' : '' ?> text-decoration:none; border-radius:12px; font-weight:500; font-size:0.95rem;">
                    <i class="fas fa-users"></i> Board
                </a>
            </li>
            <li class="<?= $current_page == 'gallery.php' ? 'active' : '' ?>">
                <a href="gallery.php" style="display:flex; align-items:center; gap:1rem; padding:0.8rem 1.2rem; color:<?= $current_page == 'gallery.php' ? '#8B5CF6' : '#94A3B8' ?>; <?= $current_page == 'gallery.php' ? 'background: rgba(139, 92, 246, 0.1);' : '' ?> text-decoration:none; border-radius:12px; font-weight:500; font-size:0.95rem;">
                    <i class="fas fa-images"></i> Gallery
                </a>
            </li>

            <li style="margin-top:2rem;">
                <a href="support.php" style="display:flex; align-items:center; gap:1rem; padding:0.8rem 1.2rem; color:#94A3B8; text-decoration:none; border-radius:12px; font-weight:500; font-size:0.95rem;">
                    <i class="fas fa-question-circle"></i> Support
                </a>
            </li>
            <li>
                <a href="logout.php" style="display:flex; align-items:center; gap:1rem; padding:0.8rem 1.2rem; color:#f43f5e; text-decoration:none; border-radius:12px; font-weight:700; font-size:0.95rem;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </nav>
</aside>
