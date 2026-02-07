<?php
require_once 'Auth.php';
require_once 'Database.php';
require_once 'Form.php';

if (!Auth::check()) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Form ID not specified.");
}

$formModel = new Form();
$form = $formModel->getById($_GET['id']);
if (!$form) {
    die("Form not found.");
}

$submissions = $formModel->getSubmissions($_GET['id']);
$fields = json_decode($form['fields_json'], true);

// Extract all unique headers from data to ensure we show all fields
$headers = [];
foreach ($fields as $f) {
    $headers[$f['label']] = $f['type']; // Store type for formatting if needed
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Responses: <?= htmlspecialchars($form['title']) ?> - IEEE MIU</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="container">
    <div class="glass-panel dashboard-header">
        <div class="header-content">
            <h1 class="text-gradient">Form Responses</h1>
            <p>Viewing submissions for: <strong class="user-accent"><?= htmlspecialchars($form['title']) ?></strong></p>
        </div>
        <div class="header-status">
            <a href="dashboard.php?tab=forms" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>

    <div class="glass-panel content-section">
        <div class="section-header">
            <h2 class="text-gradient">Submissions</h2>
            <span class="count-badge"><?= count($submissions) ?> Records</span>
        </div>
        
        <?php if(empty($submissions)): ?>
            <p style="padding: 2rem; text-align: center; color: var(--text-muted);">No responses submitted yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <?php foreach ($headers as $label => $type): ?>
                                <th><?= htmlspecialchars($label) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $sub): 
                            $data = json_decode($sub['data_json'], true);
                        ?>
                        <tr>
                            <td class="date-td"><?= date('M d, Y H:i', strtotime($sub['submitted_at'])) ?></td>
                            <?php foreach ($headers as $label => $type): ?>
                                <td>
                                    <?php 
                                    $val = $data[$label] ?? '-'; 
                                    if (is_array($val)) {
                                        echo htmlspecialchars(implode(', ', $val));
                                    } else {
                                        echo htmlspecialchars($val);
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
/* Form Responses Specific Styles */
.table-responsive {
    overflow-x: auto;
    max-width: 100%;
}

.styled-table {
    min-width: 100%;
    table-layout: auto;
}

.styled-table th,
.styled-table td {
    white-space: nowrap;
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    padding: 1rem;
    vertical-align: top;
}

.styled-table td {
    word-wrap: break-word;
    white-space: normal;
}

.date-td {
    min-width: 150px;
}

/* Make the table more readable */
.styled-table tbody tr:hover {
    background: rgba(0, 243, 255, 0.05);
}

.actions-group {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}
</style>

</body>
</html>
