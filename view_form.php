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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($form['title']) ?> - IEEE MIU</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

    <?php include 'navbar.php'; ?>

    <main class="container narrow-container">
        <div class="glass-panel form-viewer-card">
            <div class="form-header-main">
                <h1 class="text-gradient"><?= htmlspecialchars($form['title']) ?></h1>
                <p class="form-subtitle"><?= htmlspecialchars($form['description']) ?></p>
            </div>

            <?php if ($msg): ?>
                <div class="success-state">
                    <div class="icon-box-large pulse"><i class="fas fa-check-circle"></i></div>
                    <h2>Submission Successful!</h2>
                    <p><?= $msg ?></p>
                    <a href="index.php" class="btn btn-primary">Return Home</a>
                </div>
            <?php else: ?>

                <form method="POST" class="styled-form dynamic-form">
                    <?php foreach ($fields as $field): ?>
                        <?php $fieldName = str_replace(' ', '_', strtolower($field['label'])); ?>
                        <div class="form-group field-animate">
                            <label class="form-label">
                                <?= htmlspecialchars($field['label']) ?>
                                <?php if ($field['required']): ?><span class="required-star">*</span><?php endif; ?>
                            </label>

                            <div class="input-wrapper">
                                <?php if ($field['type'] === 'textarea'): ?>
                                    <textarea name="<?= $fieldName ?>" class="form-textarea" rows="4"
                                        <?= $field['required'] ? 'required' : '' ?> placeholder="Your response..."></textarea>

                                <?php elseif ($field['type'] === 'select'): ?>
                                    <select name="<?= $fieldName ?>" class="form-input" <?= $field['required'] ? 'required' : '' ?>>
                                        <option value="" disabled selected>Select an option</option>
                                        <?php foreach ($field['options'] as $opt): ?>
                                            <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                <?php elseif ($field['type'] === 'radio'): ?>
                                    <div class="options-stack">
                                        <?php foreach ($field['options'] as $opt): ?>
                                            <label class="option-label">
                                                <input type="radio" name="<?= $fieldName ?>" value="<?= htmlspecialchars($opt) ?>"
                                                    <?= $field['required'] ? 'required' : '' ?>>
                                                <span class="custom-radio"></span>
                                                <?= htmlspecialchars($opt) ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>

                                <?php elseif ($field['type'] === 'checkbox'): ?>
                                    <div class="options-stack">
                                        <?php foreach ($field['options'] as $opt): ?>
                                            <label class="option-label">
                                                <input type="checkbox" name="<?= $fieldName ?>[]" value="<?= htmlspecialchars($opt) ?>">
                                                <span class="custom-checkbox"></span>
                                                <?= htmlspecialchars($opt) ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>

                                <?php else: ?>
                                    <!-- text, email, number, date -->
                                    <input type="<?= htmlspecialchars($field['type']) ?>" name="<?= $fieldName ?>"
                                        class="form-input" <?= $field['required'] ? 'required' : '' ?>
                                        placeholder="Enter <?= strtolower($field['label']) ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <button type="submit" class="btn btn-primary btn-full submit-btn">
                        Submit Registration <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <style>
        .narrow-container {
            max-width: 800px;
            margin: 4rem auto;
        }

        .form-viewer-card {
            padding: clamp(2rem, 5vw, 4rem);
        }

        .form-header-main {
            text-align: center;
            margin-bottom: 3.5rem;
        }

        .form-header-main h1 {
            font-size: clamp(2rem, 5vw, 3rem);
            margin-bottom: 0.75rem;
        }

        .form-subtitle {
            color: var(--text-muted);
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .required-star {
            color: #ff4757;
            margin-left: 3px;
        }

        .input-wrapper {
            position: relative;
        }

        .options-stack {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .option-label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            color: var(--text-muted);
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            transition: var(--transition);
            border: 1px solid transparent;
        }

        .option-label:hover {
            background: rgba(255, 255, 255, 0.06);
            color: var(--text-main);
            border-color: var(--glass-border);
        }

        .option-label input {
            display: none;
        }

        .custom-radio,
        .custom-checkbox {
            width: 18px;
            height: 18px;
            border: 2px solid var(--glass-border);
            display: inline-block;
            position: relative;
            transition: var(--transition);
        }

        .custom-radio {
            border-radius: 50%;
        }

        .custom-checkbox {
            border-radius: 4px;
        }

        .option-label input:checked+span {
            border-color: var(--primary-neon);
            box-shadow: 0 0 10px rgba(0, 98, 155, 0.4);
        }

        .option-label input:checked+span::after {
            content: '';
            position: absolute;
            background: var(--primary-neon);
        }

        .option-label input:checked+.custom-radio::after {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .option-label input:checked+.custom-checkbox::after {
            width: 10px;
            height: 5px;
            border-left: 2px solid var(--bg-color);
            border-bottom: 2px solid var(--bg-color);
            transform: rotate(-45deg);
            top: 4px;
            left: 3px;
        }

        .option-label input:checked {}

        .option-label:has(input:checked) {
            background: rgba(0, 98, 155, 0.05);
            border-color: rgba(0, 98, 155, 0.2);
            color: var(--text-main);
        }

        .submit-btn {
            padding: 1.2rem;
            font-size: 1.1rem;
            margin-top: 2rem;
        }

        .success-state {
            text-align: center;
            padding: 3rem 0;
        }

        .success-state h2 {
            margin: 1.5rem 0 1rem;
        }

        .success-state p {
            margin-bottom: 2.5rem;
            color: var(--text-muted);
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.1);
                opacity: 0.7;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .field-animate {
            animation: fadeInUp 0.6s ease-out both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Stagger animations */
        .field-animate:nth-child(1) {
            animation-delay: 0.1s;
        }

        .field-animate:nth-child(2) {
            animation-delay: 0.2s;
        }

        .field-animate:nth-child(3) {
            animation-delay: 0.3s;
        }

        .field-animate:nth-child(4) {
            animation-delay: 0.4s;
        }

        .field-animate:nth-child(n+5) {
            animation-delay: 0.5s;
        }
    </style>

</body>

</html>