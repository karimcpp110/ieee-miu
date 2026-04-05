<?php
// student_header.php
// Centralized header for all student portal pages.
// Requirements: $studentName and $studentUniversityId must be set in the parent page.

if (!isset($studentName)) {
    $studentName = $_SESSION['student_name'] ?? 'Student';
}
if (!isset($studentUniversityId)) {
    $studentUniversityId = $_SESSION['student_university_id'] ?? 'N/A';
}

// Support email updated to user's request
$supportEmail = "karim2405560@miuegypt.edu.eg";
?>
<header class="portal-header d-flex justify-between align-center">
    <div class="header-welcome">
        <h1>Welcome Back, <?= htmlspecialchars(explode(' ', $studentName)[0]) ?>!</h1>
        <p><?= htmlspecialchars($studentName) ?>, Student ID: <?= htmlspecialchars($studentUniversityId) ?> • <span class="text-gradient" style="font-weight:700;">IEEE Member</span></p>
    </div>
    <div class="header-actions">
        <div class="header-icons">
            <div class="notif-wrapper" style="position:relative; display:inline-block;">
                <div class="notif-bell" id="dashBell" title="Notifications" style="cursor:pointer; position:relative;">
                     <i class="far fa-bell"></i>
                     <span class="pulse-dot"></span>
                </div>
                <div id="dashNotiDropdown" class="dash-noti-dropdown">
                    <div class="noti-header">
                        <span>Recent Notifications</span>
                        <button id="dashClearNoti" style="background:none; border:none; color:var(--portal-accent-blue); cursor:pointer; font-size:0.8rem; font-weight:700;">Clear</button>
                    </div>
                    <div id="dashNotiList" class="dash-noti-list">
                        <p style="padding:1.5rem; text-align:center; color:var(--text-muted);">Loading...</p>
                    </div>
                </div>
            </div>
            <a href="mailto:<?= $supportEmail ?>" title="Email Support" style="color:inherit; text-decoration:none;">
                <i class="far fa-envelope"></i>
            </a>
        </div>
        <div class="header-profile" style="display:flex; align-items:center; gap:1rem; padding-left:1rem; border-left:1px solid rgba(255,255,255,0.05);">
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($studentName) ?>&background=8B5CF6&color=fff" alt="Avatar" style="width:40px; height:40px; border-radius:12px;">
            <div class="profile-info" style="display:none; @media (min-width: 1200px) { display:block; }">
                <div style="font-weight:700; font-size:0.9rem;"><?= htmlspecialchars($studentName) ?></div>
                <div style="font-size:0.75rem; color:var(--text-muted);">Level Novice</div>
            </div>
        </div>
    </div>
</header>
