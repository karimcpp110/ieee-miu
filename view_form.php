<?php
require_once 'Database.php';
require_once 'Form.php';

$formModel = new Form();
$msg = '';

if (!isset($_GET['id'])) {
    die("Form ID not specified.");
}

$form = $formModel->getById($_GET['id']);
if (!$form) {
    die("Form not found.");
}

$fields = json_decode($form['fields_json'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [];
    foreach ($fields as $field) {
        $key = str_replace(' ', '_', strtolower($field['label']));
        $data[$field['label']] = $_POST[$key] ?? '';
    }
    $formModel->submit($form['id'], $data);
    $msg = "Form submitted successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($form['title']) ?> - IEEE MIU</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container" style="max-width: 800px;">
    <div class="glass-panel" style="padding: 3rem;">
        <h1 style="color: var(--primary-neon); margin-bottom: 0.5rem;"><?= htmlspecialchars($form['title']) ?></h1>
        <p style="color: var(--text-muted); margin-bottom: 2rem; font-size: 1.1rem;"><?= htmlspecialchars($form['description']) ?></p>

        <?php if($msg): ?>
            <div style="background: rgba(0, 255, 0, 0.1); color: #00ffaa; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid rgba(0, 255, 0, 0.2); text-align: center;">
                <?= $msg ?>
            </div>
        <?php else: ?>

        <form method="POST">
            <?php foreach ($fields as $field): ?>
                <?php $fieldName = str_replace(' ', '_', strtolower($field['label'])); ?>
                <div class="form-group">
                    <label class="form-label">
                        <?= htmlspecialchars($field['label']) ?>
                        <?php if($field['required']): ?><span style="color: #ff4444;">*</span><?php endif; ?>
                    </label>
                    
                    <?php if ($field['type'] === 'textarea'): ?>
                        <textarea name="<?= $fieldName ?>" class="form-textarea" rows="4" <?= $field['required']?'required':'' ?>></textarea>
                    
                    <?php elseif ($field['type'] === 'select'): ?>
                        <select name="<?= $fieldName ?>" class="form-input" <?= $field['required']?'required':'' ?>>
                            <option value="">Select an option</option>
                            <?php foreach($field['options'] as $opt): ?>
                                <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></option>
                            <?php endforeach; ?>
                        </select>

                    <?php elseif ($field['type'] === 'radio'): ?>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem;">
                            <?php foreach($field['options'] as $opt): ?>
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <input type="radio" name="<?= $fieldName ?>" value="<?= htmlspecialchars($opt) ?>" <?= $field['required']?'required':'' ?>>
                                    <?= htmlspecialchars($opt) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                    <?php elseif ($field['type'] === 'checkbox'): ?>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem;">
                            <?php foreach($field['options'] as $opt): ?>
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <input type="checkbox" name="<?= $fieldName ?>[]" value="<?= htmlspecialchars($opt) ?>">
                                    <?= htmlspecialchars($opt) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                    <?php else: ?>
                        <!-- text, email, number, date -->
                        <input type="<?= htmlspecialchars($field['type']) ?>" name="<?= $fieldName ?>" class="form-input" <?= $field['required']?'required':'' ?>>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Submit</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<footer style="text-align: center; padding: 3rem; color: var(--text-muted); margin-top: 4rem;">
    <p>&copy; <?= date('Y') ?> IEEE MIU Student Branch. Created by <a href="https://www.linkedin.com/in/karim-wael-40132b360/" target="_blank" style="color: var(--secondary-neon); text-decoration: none;">Karim Wael</a>.</p>
</footer>

</body>
</html>
