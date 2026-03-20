<?php
require_once 'Database.php';
require_once 'Form.php';
require_once 'EmailQueue.php';

$formModel = new Form();
$emailQueue = new EmailQueue();
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
    $respondentEmail = null;
    foreach ($fields as $idx => $field) {
        $key = str_replace(' ', '_', strtolower($field['label']));
        $value = '';

        if ($field['type'] === 'mcq') {
            $ansIdx = $_POST["q_$idx"] ?? null;
            if ($ansIdx !== null && isset($field['options'][$ansIdx])) {
                $value = $field['options'][$ansIdx]['text'];
            }
        } elseif ($field['type'] === 'true_false') {
            $value = $_POST["q_$idx"] ?? '';
        } elseif ($field['type'] === 'short_answer') {
            $value = $_POST["q_$idx"] ?? '';
        } elseif ($field['type'] === 'matching') {
            $matches = [];
            foreach ($field['pairs'] as $pIdx => $pair) {
                $target = $_POST["match_{$idx}_{$pIdx}"] ?? 'Unmatched';
                $matches[] = $pair['prompt'] . " -> " . $target;
            }
            $value = implode(' | ', $matches);
        } elseif ($field['type'] === 'ordering') {
            $value = $_POST["order_$idx"] ?? '';
        } else {
            $value = $_POST[$key] ?? '';
        }

        $data[$field['label']] = $value;

        // Try to identify if this field is an email
        if (strtolower($field['type']) === 'email' || stripos($field['label'], 'email') !== false) {
            $actualVal = is_array($value) ? implode(', ', $value) : $value;
            if ($actualVal && filter_var($actualVal, FILTER_VALIDATE_EMAIL)) {
                $respondentEmail = $actualVal;
            }
        }
    }
    $studentId = $_SESSION['student_account_id'] ?? null;
    
    // --- GRADING ENGINE ---
    $totalQuestions = 0;
    $correctCount = 0;
    foreach ($fields as $idx => $field) {
        if (in_array($field['type'], ['mcq', 'true_false', 'short_answer', 'matching', 'ordering'])) {
            $totalQuestions++;
            $userAnswer = $data[$field['label']] ?? '';
            $isCorrect = false;

            if ($field['type'] === 'mcq') {
                foreach ($field['options'] as $opt) {
                    if (!empty($opt['is_correct']) && $userAnswer == $opt['text']) {
                        $isCorrect = true;
                        break;
                    }
                }
            } elseif ($field['type'] === 'true_false') {
                if ($userAnswer == ($field['correct_answer'] ?? '')) $isCorrect = true;
            } elseif ($field['type'] === 'short_answer') {
                if (strcasecmp(trim($userAnswer), trim($field['correct_answer'] ?? '')) === 0) $isCorrect = true;
            } elseif ($field['type'] === 'matching') {
                $userMatches = explode(' | ', $userAnswer);
                $matchCount = 0;
                foreach ($field['pairs'] as $pair) {
                    $expected = $pair['prompt'] . " -> " . $pair['target'];
                    if (in_array($expected, $userMatches)) $matchCount++;
                }
                if ($matchCount == count($field['pairs'])) $isCorrect = true;
            } elseif ($field['type'] === 'ordering') {
                $expectedOrder = implode(' | ', $field['items']);
                if ($userAnswer == $expectedOrder) $isCorrect = true;
            }

            if ($isCorrect) $correctCount++;
        }
    }

    $scorePercentage = ($totalQuestions > 0) ? round(($correctCount / $totalQuestions) * 100, 1) : 0;
    $data['Final_Grade'] = "$correctCount / $totalQuestions ($scorePercentage%)";
    $data['Passing_Status'] = $scorePercentage >= 60 ? 'Passed' : 'Failed';

    $formModel->submit($form['id'], $data, $studentId);

    // Queue auto-reply if configured
    if ($respondentEmail) {
        $db = new Database(); // Need DB connection for fetching templates
        
        $subjectToUse = $form['automation_email_subject'];
        $bodyToUse = $form['automation_email_template'];
        $delayHours = isset($form['automation_delay_hours']) ? (int)$form['automation_delay_hours'] : 0;
        
        // Evaluate Conditional Logic First
        if (!empty($form['automation_conditions'])) {
            $conditions = json_decode($form['automation_conditions'], true);
            if (is_array($conditions)) {
                foreach ($conditions as $cond) {
                    $fieldLabel = $cond['field'];
                    $operator = $cond['operator'];
                    $expectedValue = $cond['value'];
                    $templateId = $cond['template_id'];
                    
                    if (isset($data[$fieldLabel])) {
                        $actualValue = $data[$fieldLabel];
                        // Handle multiple checkboxes array
                        if (is_array($actualValue)) {
                            $actualValue = implode(', ', $actualValue);
                        }
                        
                        $match = false;
                        if ($operator === '==' && strcasecmp(trim($actualValue), trim($expectedValue)) === 0) {
                            $match = true;
                        } elseif ($operator === '!=' && strcasecmp(trim($actualValue), trim($expectedValue)) !== 0) {
                            $match = true;
                        }
                        
                        if ($match && !empty($templateId)) {
                            // Fetch the custom template
                            $templateRow = $db->query("SELECT subject, body FROM email_templates WHERE id = ?", [$templateId])->fetch();
                            if ($templateRow) {
                                $subjectToUse = $templateRow['subject'];
                                $bodyToUse = $templateRow['body'];
                                break; // Stop checking conditions after first match
                            }
                        }
                    }
                }
            }
        }
        
        // Only send if we have a template to use
        if (!empty($subjectToUse) && !empty($bodyToUse)) {
            
            // Placeholder Replacement Engine
            // Find all [Field Name] tags and replace with actual submitted data
            foreach ($data as $label => $val) {
                // If it's an array (like multiple checkboxes), convert to string
                $strVal = is_array($val) ? implode(', ', $val) : $val;
                
                $searchTag = '[' . trim($label) . ']';
                $subjectToUse = str_ireplace($searchTag, htmlspecialchars($strVal), $subjectToUse);
                $bodyToUse = str_ireplace($searchTag, htmlspecialchars($strVal), $bodyToUse);
            }
            
            // Allow basic system placeholders too
            $systemTags = [
                '[Form Title]' => $form['title'],
                '[Date]' => date('Y-m-d'),
                '[Score]' => "$correctCount / $totalQuestions",
                '[Percentage]' => "$scorePercentage%",
                '[Status]' => $data['Passing_Status']
            ];
            foreach ($systemTags as $tag => $val) {
                $subjectToUse = str_ireplace($tag, $val, $subjectToUse);
                $bodyToUse = str_ireplace($tag, $val, $bodyToUse);
            }

            // Calculate exact send time based on delay
            $sendAfter = null; // Use NULL for immediate delivery (Timezone safe)
            if ($delayHours > 0) {
                $sendAfter = date('Y-m-d H:i:s', strtotime("+$delayHours hours"));
            }

            // Enqueue the processed email with delay parameters
            $emailQueue->enqueue($respondentEmail, $subjectToUse, $bodyToUse, $sendAfter);
        }
    }

    $msg = "Form submitted successfully! Your score: " . $data['Final_Grade'] . " - " . $data['Passing_Status'];
    
    // Trigger Course Completion if this is a linked exam and they passed
    if ($studentId && $data['Passing_Status'] === 'Passed') {
        $db = new Database();
        $linkedCourse = $db->query("SELECT id, title FROM courses WHERE exam_id = ?", [$form['id']])->fetch();
        if ($linkedCourse) {
            // They passed the final exam of a course!
            // We could mark it completed here, but course_details.php already has a "Mark as Completed" button
            // that checks for passing scores. We'll simply ensure they see the success message.
            $msg .= "<br>Congratulations! You have passed the final exam for " . $linkedCourse['title'] . ". You can now claim your certificate.";
            
            // Send special course completion email if a template exists
            $congratsTemplate = $db->query("SELECT subject, body FROM email_templates WHERE name LIKE '%Congratulations%' OR name LIKE '%Course Completion%' LIMIT 1")->fetch();
            if ($congratsTemplate && $respondentEmail) {
                $cSub = str_replace('[Course Title]', $linkedCourse['title'], $congratsTemplate['subject']);
                $cBody = str_replace('[Course Title]', $linkedCourse['title'], $congratsTemplate['body']);
                $emailQueue->enqueue($respondentEmail, $cSub, $cBody);
            }
        }
    }
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
                    <?php foreach ($fields as $idx_loop => $field): ?>
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

                                <?php elseif ($field['type'] === 'mcq'): ?>
                                    <?php if (!empty($field['image'])): ?>
                                        <div class="question-media">
                                            <img src="<?= htmlspecialchars($field['image']) ?>" class="question-img">
                                        </div>
                                    <?php endif; ?>
                                    <div class="options-grid">
                                        <?php foreach ($field['options'] as $idx => $opt): ?>
                                            <label class="mcq-option card-styled">
                                                <input type="radio" name="q_<?= $idx_loop ?>" value="<?= $idx ?>" required>
                                                <div class="mcq-content">
                                                    <?php if (!empty($opt['image'])): ?>
                                                        <img src="<?= htmlspecialchars($opt['image']) ?>" class="option-img">
                                                    <?php endif; ?>
                                                    <span><?= htmlspecialchars($opt['text']) ?></span>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>

                                <?php elseif ($field['type'] === 'matching'): ?>
                                    <?php if (!empty($field['image'])): ?>
                                        <div class="question-media">
                                            <img src="<?= htmlspecialchars($field['image']) ?>" class="question-img">
                                        </div>
                                    <?php endif; ?>
                                    <div class="matching-container" id="matching-<?= $idx_loop ?>">
                                        <div class="matching-prompts">
                                            <?php 
                                            $targets = array_column($field['pairs'], 'target');
                                            shuffle($targets);
                                            foreach ($field['pairs'] as $pIdx => $pair): 
                                            ?>
                                                <div class="matching-row">
                                                    <div class="prompt-text"><?= htmlspecialchars($pair['prompt']) ?></div>
                                                    <div class="drop-zone" data-q="<?= $idx_loop ?>" data-p="<?= $pIdx ?>">
                                                        <select name="match_<?= $idx_loop ?>_<?= $pIdx ?>" class="matching-select" required>
                                                            <option value="">Match...</option>
                                                            <?php foreach ($targets as $t): ?>
                                                                <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php elseif ($field['type'] === 'true_false'): ?>
                                    <?php if (!empty($field['image'])): ?>
                                        <div class="question-media">
                                            <img src="<?= htmlspecialchars($field['image']) ?>" class="question-img">
                                        </div>
                                    <?php endif; ?>
                                    <div class="tf-options-view">
                                        <label class="tf-choice">
                                            <input type="radio" name="q_<?= $idx_loop ?>" value="true" required>
                                            <span class="tf-label true-label">✅ True</span>
                                        </label>
                                        <label class="tf-choice">
                                            <input type="radio" name="q_<?= $idx_loop ?>" value="false">
                                            <span class="tf-label false-label">❌ False</span>
                                        </label>
                                    </div>

                                <?php elseif ($field['type'] === 'short_answer'): ?>
                                    <?php if (!empty($field['image'])): ?>
                                        <div class="question-media">
                                            <img src="<?= htmlspecialchars($field['image']) ?>" class="question-img">
                                        </div>
                                    <?php endif; ?>
                                    <input type="text" name="q_<?= $idx_loop ?>" class="form-input" required
                                        placeholder="Type your answer here...">

                                <?php elseif ($field['type'] === 'ordering'): ?>
                                    <?php if (!empty($field['image'])): ?>
                                        <div class="question-media">
                                            <img src="<?= htmlspecialchars($field['image']) ?>" class="question-img">
                                        </div>
                                    <?php endif; ?>
                                    <?php
                                    $shuffled = $field['items'];
                                    shuffle($shuffled);
                                    ?>
                                    <div class="ordering-sortable" id="ordering-<?= $idx_loop ?>" data-q="<?= $idx_loop ?>">
                                        <?php foreach ($shuffled as $sIdx => $item): ?>
                                            <div class="sortable-item" draggable="true" data-value="<?= htmlspecialchars($item) ?>">
                                                <i class="fas fa-grip-vertical grip-icon"></i>
                                                <span><?= htmlspecialchars($item) ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="order_<?= $idx_loop ?>" id="order-val-<?= $idx_loop ?>">

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
            border-color: var(--primary-color);
            background: var(--primary-color);
            box-shadow: 0 0 10px var(--primary-color);
        }

        /* MCQ & Matching Enhancements */
        .question-media {
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .question-img {
            max-width: 100%;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
            box-shadow: var(--neon-shadow);
        }

        .options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .mcq-option {
            cursor: pointer;
            position: relative;
            padding: 1rem;
            transition: var(--transition);
        }

        .mcq-option input { display: none; }

        .mcq-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
            text-align: center;
        }

        .option-img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
        }

        .mcq-option:has(input:checked) {
            border-color: var(--primary-color);
            background: rgba(0, 255, 163, 0.05);
            box-shadow: 0 0 15px rgba(0, 255, 163, 0.2);
        }

        .matching-container {
            background: rgba(255, 255, 255, 0.02);
            border-radius: 15px;
            padding: 1.5rem;
            border: 1px solid var(--glass-border);
        }

        .matching-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2rem;
            padding: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .matching-row:last-child { border-bottom: none; }

        .prompt-text {
            font-weight: 500;
            color: var(--text-main);
            flex: 1;
        }

        .matching-select {
            background: var(--card-bg);
            color: var(--text-main);
            border: 1px solid var(--glass-border);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            width: 200px;
            outline: none;
            transition: var(--transition);
        }

        .matching-select:focus {
            border-color: var(--secondary-neon);
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

    <script>
    // Ordering drag-and-drop
    document.querySelectorAll('.ordering-sortable').forEach(container => {
        let draggedItem = null;

        container.querySelectorAll('.sortable-item').forEach(item => {
            item.addEventListener('dragstart', e => {
                draggedItem = item;
                item.style.opacity = '0.4';
                e.dataTransfer.effectAllowed = 'move';
            });

            item.addEventListener('dragend', () => {
                item.style.opacity = '1';
                draggedItem = null;
                container.querySelectorAll('.sortable-item').forEach(i => i.classList.remove('drag-over'));
            });

            item.addEventListener('dragover', e => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                item.classList.add('drag-over');
            });

            item.addEventListener('dragleave', () => item.classList.remove('drag-over'));

            item.addEventListener('drop', e => {
                e.preventDefault();
                item.classList.remove('drag-over');
                if (draggedItem !== item) {
                    const allItems = [...container.querySelectorAll('.sortable-item')];
                    const draggedIdx = allItems.indexOf(draggedItem);
                    const targetIdx = allItems.indexOf(item);
                    if (draggedIdx < targetIdx) {
                        container.insertBefore(draggedItem, item.nextSibling);
                    } else {
                        container.insertBefore(draggedItem, item);
                    }
                }
            });
        });
    });

    // Before form submit, populate ordering hidden inputs
    document.querySelectorAll('.styled-form').forEach(form => {
        form.addEventListener('submit', () => {
            document.querySelectorAll('.ordering-sortable').forEach(container => {
                const q = container.dataset.q;
                const order = [...container.querySelectorAll('.sortable-item')]
                    .map(i => i.dataset.value).join(' | ');
                const hidden = document.getElementById(`order-val-${q}`);
                if (hidden) hidden.value = order;
            });
        });
    });
    </script>

    <style>
        /* True/False */
        .tf-options-view { display: flex; gap: 1.5rem; margin-top: 1rem; }
        .tf-choice { cursor: pointer; }
        .tf-choice input { display: none; }
        .tf-label { display: inline-block; padding: 0.8rem 2rem; border-radius: 12px; border: 2px solid var(--glass-border); background: rgba(255,255,255,0.02); transition: var(--transition); font-size: 1.1rem; font-weight: 600; }
        .tf-choice:hover .tf-label { border-color: var(--primary-color); }
        .tf-choice input:checked + .true-label { border-color: #2ecc71; background: rgba(46,204,113,0.1); box-shadow: 0 0 15px rgba(46,204,113,0.2); }
        .tf-choice input:checked + .false-label { border-color: #e74c3c; background: rgba(231,76,60,0.1); box-shadow: 0 0 15px rgba(231,76,60,0.2); }

        /* Ordering / Sortable */
        .ordering-sortable { display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1rem; }
        .sortable-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.8rem 1rem; background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); border-radius: 10px; cursor: grab; transition: var(--transition); user-select: none; }
        .sortable-item:active { cursor: grabbing; }
        .sortable-item.drag-over { border-color: var(--primary-color); background: rgba(0,255,163,0.05); }
        .grip-icon { color: var(--text-muted); font-size: 0.9rem; }
    </style>

</body>

</html>