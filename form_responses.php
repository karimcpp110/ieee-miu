<?php
require_once 'Auth.php';
require_once 'Database.php';
require_once 'Form.php';
require_once 'Notification.php';
require_once 'EmailQueue.php';
require_once 'Logger.php';

if (!Auth::check()) {
    header("Location: login.php");
    exit;
}

if (!Auth::isHR()) {
    die("Unauthorized access. Admin or HR role required.");
}

if (!isset($_GET['id'])) {
    die("Form ID not specified.");
}

$db = new Database();
$formModel = new Form();
$form = $formModel->getById($_GET['id']);
if (!$form) {
    die("Form not found.");
}

// --- 1. HANDLE STATUS UPDATES ---
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $subId = $_POST['submission_id'];
    $status = $_POST['status'];
    $notes = $_POST['admin_notes'];

    // Simple update query via Form model
    if ($formModel->updateSubmission($subId, $status, $notes)) {
        $message = "<div class='alert success'>Status updated successfully!</div>";

        // --- NOTIFICATION / EMAIL INTEGRATION ---
        $sub = $db->query("SELECT * FROM submissions WHERE id = ?", [$subId])->fetch();
        $formData = json_decode($sub['data_json'], true);

        // 1. Notify student if logged in
        if (isset($sub['student_id']) && $sub['student_id']) {
            $noti = new Notification();
            $noti->create(
                "Update: " . $form['title'],
                "Your submission status has been updated to: **$status**." . ($notes ? " Notes: $notes" : ""),
                ($status === 'Accepted' ? 'success' : ($status === 'Rejected' ? 'danger' : 'info')),
                null,
                $sub['student_id']
            );
        }

        // 2. Email if possible
        $email = null;
        foreach ($formData as $key => $val) {
            if (strpos(strtolower($key), 'email') !== false) {
                $email = $val;
                break;
            }
        }

        if ($email) {
            $eq = new EmailQueue();
            $emailContent = "<h2>Form Status Update</h2>";
            $emailContent .= "<p>Your submission for <strong>" . $form['title'] . "</strong> has been <strong>$status</strong>.</p>";
            if ($notes)
                $emailContent .= "<p><strong>Committee Notes:</strong> $notes</p>";
            $emailContent .= "<p>Best regards,<br>IEEE MIU Team</p>";
            $eq->enqueue($email, "Application Update: " . $form['title'], $emailContent);
        }
    } else {
        $message = "<div class='alert error'>Failed to update status. <strong>Note:</strong> You might need to run <a
        href='db_repair.php' target='_blank'>db_repair.php</a> to update your database.</div>";
    }
}

// --- 2. BROADCAST LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_broadcast') {
    $subject = trim($_POST['broadcast_subject']);
    $messageBody = trim($_POST['broadcast_message']);
    $targetEmails = isset($_POST['target_emails']) ? json_decode($_POST['target_emails'], true) : [];

    if (empty($subject) || empty($messageBody)) {
        $message = "<div class='alert error'>Subject and Message cannot be empty.</div>";
    } elseif (empty($targetEmails)) {
        $message = "<div class='alert error'>No target emails selected.</div>";
    } else {
        $eq = new EmailQueue();
        $enqueuedCount = 0;
        foreach ($targetEmails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $eq->enqueue($email, $subject, $messageBody);
                $enqueuedCount++;
            }
        }
        $message = "<div class='alert success'>Successfully queued $enqueuedCount emails for sending!</div>";
        Logger::log("Form Broadcast", "Queued $enqueuedCount emails for form ID: " . $form['id']);
    }
}

// --- 3. EXPORT LOGIC ---
if (isset($_GET['export']) && $_GET['export'] == '1') {
    $submissions = $formModel->getSubmissions($_GET['id']);
    $fields = json_decode($form['fields_json'], true);
    $onlyAccepted = isset($_GET['accepted_only']) && $_GET['accepted_only'] == '1';

    // Filter if requested
    if ($onlyAccepted) {
        $submissions = array_filter($submissions, function ($sub) {
            $status = isset($sub['status']) ? $sub['status'] : 'Pending';
            return $status === 'Accepted';
        });
    }

    // Prepare CSV Headers
    $csvHeaders = ['Submission Date', 'Status', 'Committee Opinion'];
    foreach ($fields as $f) {
        $csvHeaders[] = $f['label'];
    }

    // Set Headers to Download File
    $filename = 'responses_' . preg_replace('/[^a-z0-9]+/i', '_', $form['title']);
    if ($onlyAccepted)
        $filename .= '_ACCEPTED_ONLY';
    $filename .= '_' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    // Open Output Stream
    $output = fopen('php://output', 'w');

    // Add BOM for Excel UTF-8 compatibility
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Write Header Row
    fputcsv($output, $csvHeaders);

    // Write Data Rows
    foreach ($submissions as $sub) {
        $data = json_decode($sub['data_json'], true);
        $row = [];

        // Meta Columns
        $row[] = date('Y-m-d H:i:s', strtotime($sub['submitted_at']));
        $row[] = isset($sub['status']) ? $sub['status'] : 'Pending';
        $row[] = isset($sub['admin_notes']) ? $sub['admin_notes'] : '';

        // Field Columns
        foreach ($fields as $f) {
            $label = $f['label'];
            $val = isset($data[$label]) ? $data[$label] : '';

            // Handle array values
            if (is_array($val)) {
                $val = implode(', ', $val);
            }

            // Force Excel to treat as text to avoid scientific notation
            if (is_numeric($val) && strlen((string) $val) > 6) {
                $val = '="' . $val . '"';
            }

            $row[] = $val;
        }
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// --- 3. FETCH DATA FOR DISPLAY ---
$submissions = $formModel->getSubmissions($_GET['id']);
$fields = json_decode($form['fields_json'], true);

// Extract headers
$headers = [];
foreach ($fields as $f) {
    $headers[$f['label']] = $f['type'];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Responses: <?= htmlspecialchars($form['title']) ?> - IEEE MIU</title>
    <link rel="stylesheet" href="style.css?v=10">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .status-select {
            padding: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-weight: bold;
            cursor: pointer;
            background: rgba(0, 0, 0, 0.2);
            color: #fff;
        }

        .status-Pending {
            color: #f39c12;
            border-color: #f39c12;
        }

        .status-Accepted {
            color: #2ecc71;
            border-color: #2ecc71;
        }

        .status-Rejected {
            color: #e74c3c;
            border-color: #e74c3c;
        }

        .notes-input {
            width: 100%;
            padding: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            background: rgba(0, 0, 0, 0.2);
            color: #fff;
            min-width: 150px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
        }

        .alert.success {
            background: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
            border: 1px solid rgba(46, 204, 113, 0.3);
        }

        .alert.error {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        .export-controls {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255, 255, 255, 0.05);
            padding: 8px 15px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            color: #fff;
            cursor: pointer;
            font-size: 0.9em;
            user-select: none;
        }

        .checkbox-wrapper input {
            margin-right: 8px;
            width: 16px;
            height: 16px;
            accent-color: var(--secondary-neon);
        }

        .styled-table td {
            vertical-align: middle;
        }
    </style>
</head>

<body class="dashboard-page">

    <div class="sidebar-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="logo.png" alt="IEEE MIU" class="sidebar-logo" width="32" height="32"
                    style="width: 32px; height: 32px; flex-shrink: 0;">
                <h2 class="text-gradient" style="font-size: 1.2rem; margin: 0;">Portal</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php?tab=registrations" class="nav-link">
                    <i class="fas fa-users"></i> Registrations
                </a>
                <a href="dashboard.php?tab=forms" class="nav-link active">
                    <i class="fas fa-poll"></i> Dynamic Forms
                </a>
                <a href="dashboard.php?tab=courses" class="nav-link">
                    <i class="fas fa-book-open"></i> LMS Management
                </a>
                <div style="margin-top: auto; padding: 1rem;">
                    <a href="logout.php" class="nav-link text-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </nav>
        </aside>

        <main class="main-content">
            <div class="glass-panel dashboard-header">
                <div class="header-content">
                    <h1 class="text-gradient">Form Responses</h1>
                    <p>Viewing submissions for: <strong
                            class="user-accent"><?= htmlspecialchars($form['title']) ?></strong></p>
                </div>

                <div class="header-actions" style="display: flex; gap: 1rem; align-items: center;">
                    <button type="button" class="btn btn-secondary" onclick="openBroadcastModal()" style="padding: 0.5rem 1rem; min-height: auto;">
                        <i class="fas fa-bullhorn"></i> Broadcast
                    </button>
                    <form action="form_responses.php" method="GET" class="export-controls">
                        <input type="hidden" name="id" value="<?= $form['id'] ?>">
                        <input type="hidden" name="export" value="1">
                        <label class="checkbox-wrapper" title="Only export submissions marked as Accepted">
                            <input type="checkbox" name="accepted_only" value="1">
                            Export Accepted Only
                        </label>
                        <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem; min-height: auto;">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                    </form>
                    <a href="dashboard.php?tab=forms" class="btn btn-outline"
                        style="padding: 0.5rem 1rem; min-height: auto;">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>

            <?= $message ?>

            <div class="glass-panel content-section">
                <div class="section-header">
                    <h2 class="text-gradient">Submissions</h2>
                    <span class="count-badge"><?= count($submissions) ?> Records</span>
                </div>

                <?php if (empty($submissions)): ?>
                    <p style="padding: 2rem; text-align: center; color: var(--text-muted);">No responses submitted yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <?php foreach ($headers as $label => $type): ?>
                                        <th><?= htmlspecialchars($label) ?></th>
                                    <?php endforeach; ?>
                                    <th>Date</th>
                                    <th width="120">Status</th>
                                    <th>Committee Opinion</th>
                                    <th width="80">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($submissions as $sub):
                                    $data = json_decode($sub['data_json'], true);
                                    $status = isset($sub['status']) ? $sub['status'] : 'Pending';
                                    $notes = isset($sub['admin_notes']) ? $sub['admin_notes'] : '';
                                    $rowId = $sub['id'];
                                    ?>
                                    <tr id="row-<?= $rowId ?>">
                                        <?php foreach ($headers as $label => $type): ?>
                                            <td>
                                                <?php
                                                $val = isset($data[$label]) ? $data[$label] : '-';
                                                if (is_array($val)) {
                                                    echo htmlspecialchars(implode(', ', $val));
                                                } else {
                                                    echo htmlspecialchars($val);
                                                }
                                                ?>
                                            </td>
                                        <?php endforeach; ?>
                                        <td class="date-td"><?= date('M d, H:i', strtotime($sub['submitted_at'])) ?></td>
                                        <td>
                                            <select id="status-<?= $rowId ?>" class="status-select status-<?= $status ?>"
                                                onchange="updateColor(this)">
                                                <option value="Pending" <?= $status == 'Pending' ? 'selected' : '' ?>>Pending
                                                </option>
                                                <option value="Accepted" <?= $status == 'Accepted' ? 'selected' : '' ?>>Accepted
                                                </option>
                                                <option value="Rejected" <?= $status == 'Rejected' ? 'selected' : '' ?>>Rejected
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" id="notes-<?= $rowId ?>" class="notes-input"
                                                value="<?= htmlspecialchars($notes) ?>" placeholder="Add comments...">
                                        </td>
                                        <td>
                                            <button type="button" onclick="saveRow(<?= $rowId ?>)"
                                                class="btn btn-sm btn-secondary" title="Save Changes"
                                                style="padding: 0.5rem; min-height: auto;">
                                                <i class="fas fa-save"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <form id="updateForm" method="POST" style="display:none;">
        <input type="hidden" name="update_status" value="1">
        <input type="hidden" name="submission_id" id="form-sub-id">
        <input type="hidden" name="status" id="form-status">
        <input type="hidden" name="admin_notes" id="form-notes">
    </form>

    <!-- Broadcast Modal -->
    <div id="broadcastModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; align-items:center; justify-content:center;">
        <div class="modal-content glass-panel" style="max-width: 600px; width:100%;">
            <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                <h3 class="text-gradient" style="margin:0;"><i class="fas fa-bullhorn"></i> Broadcast Message</h3>
                <button type="button" class="btn-clear" onclick="closeBroadcastModal()" style="color:var(--text-muted); font-size:1.5rem;">&times;</button>
            </div>
            <form method="POST" class="styled-form" id="broadcastForm">
                <input type="hidden" name="action" value="send_broadcast">
                <input type="hidden" name="target_emails" id="target-emails-input">
                
                <div class="form-group">
                    <label>Target Audience</label>
                    <div id="audience-info" style="padding: 10px; background: rgba(0,0,0,0.2); border-radius: 4px; font-size: 0.9rem;">
                        Extracting emails...
                    </div>
                </div>

                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" name="broadcast_subject" class="form-input" required placeholder="e.g. Important Update Regarding Your Application">
                </div>
                
                <div class="form-group">
                    <label>Message (HTML Allowed)</label>
                    <textarea name="broadcast_message" class="form-textarea" rows="6" required placeholder="Dear applicant..."></textarea>
                </div>
                
                <button type="button" onclick="submitBroadcast()" class="btn btn-primary btn-full" id="btn-send-broadcast">Send Broadcast</button>
            </form>
        </div>
    </div>

    <script>
        const allSubmissions = <?= json_encode($submissions) ?>;
        
        function extractEmails() {
            const emails = [];
            allSubmissions.forEach(sub => {
                try {
                    const data = JSON.parse(sub.data_json);
                    Object.keys(data).forEach(key => {
                        if (key.toLowerCase().includes('email') || typeof data[key] === 'string' && data[key].includes('@')) {
                            const em = data[key].trim();
                            // Basic regex check before adding
                            if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em) && !emails.includes(em)) {
                                emails.push(em);
                            }
                        }
                    });
                } catch (e) { console.error("Could not parse submission data for row", sub.id); }
            });
            return emails;
        }

        function openBroadcastModal() {
            const emails = extractEmails();
            const btn = document.getElementById('btn-send-broadcast');
            const info = document.getElementById('audience-info');
            
            document.getElementById('target-emails-input').value = JSON.stringify(emails);
            document.getElementById('broadcastModal').style.display = 'flex';
            
            if (emails.length === 0) {
                info.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> No valid email addresses found in these submissions.</span>';
                btn.disabled = true;
            } else {
                info.innerHTML = `<span class="text-success"><i class="fas fa-check-circle"></i> Found <strong>${emails.length}</strong> unique email addresses.</span>`;
                btn.disabled = false;
                btn.innerText = `Send to ${emails.length} recipients`;
            }
        }
        
        function closeBroadcastModal() {
            document.getElementById('broadcastModal').style.display = 'none';
        }

        function submitBroadcast() {
            if (document.getElementById('target-emails-input').value === '[]') {
                alert("Cannot send broadcast. No emails found.");
                return;
            }
            if (confirm("Are you sure you want to queue this message to all extracted emails?")) {
                document.getElementById('broadcastForm').submit();
            }
        }

        function updateColor(select) {
            select.className = 'status-select status-' + select.value;
        }

        function saveRow(id) {
            document.getElementById('form-sub-id').value = id;
            document.getElementById('form-status').value = document.getElementById('status-' + id).value;
            document.getElementById('form-notes').value = document.getElementById('notes-' + id).value;
            document.getElementById('updateForm').submit();
        }
    </script>
</body>

</html>