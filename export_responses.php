<?php
require_once 'Auth.php';
require_once 'Database.php';
require_once 'Form.php';

// 1. Security Check
if (!Auth::check()) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Form ID not specified.");
}

$formId = $_GET['id'];
$formModel = new Form();
$form = $formModel->getById($formId);

if (!$form) {
    die("Form not found.");
}

// 2. Fetch Data
$submissions = $formModel->getSubmissions($formId);
$fields = json_decode($form['fields_json'], true);

// 3. Prepare CSV Headers
$csvHeaders = ['Submission Date'];
foreach ($fields as $f) {
    $csvHeaders[] = $f['label'];
}

// 4. Set Headers to Download File
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=responses_' . preg_replace('/[^a-z0-9]+/i', '_', $form['title']) . '_' . date('Y-m-d') . '.csv');

// 5. Open Output Stream
$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 compatibility
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Write Header Row
fputcsv($output, $csvHeaders);

// 6. Write Data Rows
foreach ($submissions as $sub) {
    $data = json_decode($sub['data_json'], true);
    $row = [];

    // Date Column
    $row[] = date('Y-m-d H:i:s', strtotime($sub['submitted_at']));

    // Field Columns
    foreach ($fields as $f) {
        $label = $f['label'];
        $val = $data[$label] ?? '';

        // Handle array values (like checkboxes)
        if (is_array($val)) {
            $val = implode(', ', $val);
        }

        $row[] = $val;
    }

    fputcsv($output, $row);
}

fclose($output);
exit;
