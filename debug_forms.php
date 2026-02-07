<?php
require_once 'Database.php';
require_once 'Form.php';

$formModel = new Form();
$forms = $formModel->getAll();

echo "=== FORMS DEBUG ===\n\n";

foreach ($forms as $form) {
    echo "Form ID: " . $form['id'] . "\n";
    echo "Title: " . $form['title'] . "\n";
    echo "Fields JSON: " . $form['fields_json'] . "\n\n";
    
    $submissions = $formModel->getSubmissions($form['id']);
    echo "Total Submissions: " . count($submissions) . "\n";
    
    if (count($submissions) > 0) {
        echo "\nSample Submission Data:\n";
        echo $submissions[0]['data_json'] . "\n";
        
        $data = json_decode($submissions[0]['data_json'], true);
        echo "\nParsed Data:\n";
        print_r($data);
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}
?>
