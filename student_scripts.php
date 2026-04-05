<script>
// student_scripts.php
// JavaScript for shared student portal functionality (Notifications)

document.addEventListener('DOMContentLoaded', function() {
    const dashBell = document.getElementById('dashBell');
    const dashDropdown = document.getElementById('dashNotiDropdown');
    const dashNotiList = document.getElementById('dashNotiList');
    const dashClearBtn = document.getElementById('dashClearNoti');

    if (dashBell && dashDropdown) {
        dashBell.addEventListener('click', (e) => {
            e.stopPropagation();
            dashDropdown.classList.toggle('active');
            if (dashDropdown.classList.contains('active')) {
                fetchDashNotifications();
            }
        });

        document.addEventListener('click', () => dashDropdown.classList.remove('active'));
        dashDropdown.addEventListener('click', (e) => e.stopPropagation());

        if (dashClearBtn) {
            dashClearBtn.addEventListener('click', () => {
                fetch('get_notifications.php?action=mark_all_read', { method: 'POST' })
                    .then(() => {
                        const pulse = dashBell.querySelector('.pulse-dot');
                        if (pulse) pulse.style.visibility = 'hidden';
                        dashNotiList.innerHTML = '<p style="padding:1.5rem; text-align:center; color:var(--text-muted);">All caught up! 🎉</p>';
                        dashDropdown.classList.remove('active');
                    });
            });
        }

        function fetchDashNotifications() {
            fetch('get_notifications.php')
                .then(res => res.json())
                .then(data => {
                    if (data.length > 0) {
                        const pulse = dashBell.querySelector('.pulse-dot');
                        if (pulse) pulse.style.visibility = 'visible';
                        dashNotiList.innerHTML = data.map(n => `
                            <div class="dash-noti-item ${n.type}" style="padding:1rem; display:flex; gap:1rem; border-bottom:1px solid rgba(255,255,255,0.03);">
                                <div class="noti-icon" style="font-size:1.2rem;">${n.type === 'success' ? '🏆' : '📢'}</div>
                                <div class="noti-content">
                                    <div class="noti-title" style="font-weight:700; font-size:0.9rem;">${n.title}</div>
                                    <div class="noti-text" style="font-size:0.8rem; color:var(--text-muted);">${n.message}</div>
                                    <div class="noti-date" style="font-size:0.7rem; color:rgba(255,255,255,0.3); margin-top:0.3rem;">${n.time_ago}</div>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        const pulse = dashBell.querySelector('.pulse-dot');
                        if (pulse) pulse.style.visibility = 'hidden';
                        dashNotiList.innerHTML = '<p style="padding:1.5rem; text-align:center; color:var(--text-muted);">No new notifications</p>';
                    }
                })
                .catch(() => {
                    dashNotiList.innerHTML = '<p style="padding:1.5rem; text-align:center; color:var(--text-muted);">Unable to load notifications.</p>';
                });
        }
        
        // Initial check for pulse dot
        fetch('get_notifications.php')
            .then(res => res.json())
            .then(data => {
                const pulse = dashBell.querySelector('.pulse-dot');
                if (pulse) pulse.style.visibility = (data.length > 0) ? 'visible' : 'hidden';
            });
    }
});
</script>
