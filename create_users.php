<?php
require_once 'Database.php';

$db = new Database();

echo "<h2>Account Creation & Security Sync Tool</h2>";
$adminEmail = "kwael7934@gmail.com";

try {
    // 1. Sync Existing Admin Accounts to specified email
    $db->query("UPDATE users SET email = ? WHERE username IN ('admin', 'hr_user', 'instructor_user')", [$adminEmail]);
    echo "<p style='color:blue'>ℹ️ Synced existing admin, hr_user, and instructor_user to: <b>$adminEmail</b></p>";

    // 2. Create HR Account if missing
    $hr_password = password_hash('hr_password123', PASSWORD_BCRYPT);
    $db->query("INSERT IGNORE INTO users (username, password, role, email) VALUES (?, ?, ?, ?)", [
        'hr_user',
        $hr_password,
        'HR',
        $adminEmail
    ]);
    echo "<p style='color:green'>✅ HR Account verified/created: <b>hr_user</b> / <b>hr_password123</b> (Hashed)</p>";

    // 3. Create Instructor Account if missing
    $inst_password = password_hash('inst_password123', PASSWORD_BCRYPT);
    $db->query("INSERT IGNORE INTO users (username, password, role, email) VALUES (?, ?, ?, ?)", [
        'instructor_user',
        $inst_password,
        'Instructor',
        $adminEmail
    ]);
    echo "<p style='color:green'>✅ Instructor Account verified/created: <b>instructor_user</b> / <b>inst_password123</b> (Hashed)</p>";

    echo "<hr><p><b>Security Status:</b> All high-level access is now linked to <b>$adminEmail</b>. Reset links for any of these accounts will be sent to your hand only.</p>";
    echo "<p style='color:red'><strong>IMPORTANT:</strong> Delete this file (<code>create_users.php</code>) after running it!</p>";

} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>