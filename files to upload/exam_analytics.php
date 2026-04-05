<?php
require_once 'Auth.php';
require_once 'Database.php';
require_once 'Form.php';

if (!Auth::isAdmin() && !Auth::isInstructor() && !Auth::isHR()) {
    die("Unauthorized access.");
}

$formId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$formId) die("Missing Form ID.");

$db = new Database();
$formModel = new Form();
$form = $formModel->getById($formId);

if (!$form || $form['type'] !== 'exam') {
    die("Invalid Exam ID.");
}

$submissions = $formModel->getSubmissions($formId);
$fields = json_decode($form['fields_json'], true);

// Aggregation Variables
$totalSubmissions = count($submissions);
$totalSum = 0;
$passedCount = 0;
$highestScore = 0;
$lowestScore = 100;
$questionStats = [];
$calculatedScores = []; // Holds all scores for distribution chart

// Initialize Question Stats
foreach ($fields as $idx => $f) {
    if (!isset($f['type'])) continue;
    $questionStats[$idx] = [
        'label' => $f['label'],
        'type' => $f['type'],
        'correct' => 0,
        'attempts' => 0,
        'incorrect_answers' => []
    ];
}

foreach ($submissions as $sub) {
    $data = json_decode($sub['data_json'], true);
    
    // Extract Grade if exists
    // The grade is stored as "X / Y (Z%)" or just "Passed/Failed" in Passing_Status
    // We'll calculate it ourselves to be 100% accurate
    $score = 0;
    $totalPossible = count($fields);
    $correctInThisSub = 0;

    foreach ($fields as $idx => $f) {
        $isCorrect = false;
        $studentAns = null;

        if ($f['type'] === 'mcq') {
            $studentAns = isset($data['q_' . $idx]) ? (int)$data['q_' . $idx] : -1;
            if ($studentAns == (int)$f['correct']) $isCorrect = true;
        } 
        elseif ($f['type'] === 'true_false') {
            $studentAns = isset($data['q_' . $idx]) ? $data['q_' . $idx] : '';
            if ($studentAns === $f['correct']) $isCorrect = true;
        }
        elseif ($f['type'] === 'short_answer') {
            $studentAns = isset($data['q_' . $idx]) ? trim($data['q_' . $idx]) : '';
            if (strcasecmp($studentAns, trim($f['correct'])) === 0) $isCorrect = true;
        }
        elseif ($f['type'] === 'matching') {
            // All pairs must match for 1 point
            $allMatch = true;
            foreach ($f['pairs'] as $pIdx => $pair) {
                $ans = isset($data['match_' . $idx . '_' . $pIdx]) ? $data['match_' . $idx . '_' . $pIdx] : '';
                if ($ans !== $pair['target']) { $allMatch = false; break; }
            }
            if ($allMatch) $isCorrect = true;
            $studentAns = "Complex Matching"; // Simplified for incorrect answers tracking
        }
        elseif ($f['type'] === 'ordering') {
            // Order must be exact. Separator in view_form.js is " | "
            $studentAns = isset($data['order_' . $idx]) ? $data['order_' . $idx] : '';
            $correctOrderStr = implode(' | ', $f['items']);
            if ($studentAns === $correctOrderStr) $isCorrect = true;
        }

        if ($isCorrect) {
            $questionStats[$idx]['correct']++;
            $correctInThisSub++;
        } else {
            if ($studentAns !== null && $studentAns !== -1 && $studentAns !== '') {
                $ansKey = is_numeric($studentAns) && $f['type'] === 'mcq' ? ($f['options'][$studentAns]['text'] ?? "Option $studentAns") : $studentAns;
                $questionStats[$idx]['incorrect_answers'][$ansKey] = ($questionStats[$idx]['incorrect_answers'][$ansKey] ?? 0) + 1;
            }
        }
        $questionStats[$idx]['attempts']++;
    }

    $subPercentage = ($totalPossible > 0) ? ($correctInThisSub / $totalPossible) * 100 : 0;
    $calculatedScores[] = $subPercentage;
    
    $totalSum += $subPercentage;
    if ($subPercentage >= 60) $passedCount++;
    if ($subPercentage > $highestScore) $highestScore = $subPercentage;
    if ($subPercentage < $lowestScore) $lowestScore = $subPercentage;
}

$avgScore = ($totalSubmissions > 0) ? round($totalSum / $totalSubmissions, 1) : 0;
$passRate = ($totalSubmissions > 0) ? round(($passedCount / $totalSubmissions) * 100, 1) : 0;
if ($totalSubmissions == 0) $lowestScore = 0;

// Sort incorrect answers to find common pitfalls
foreach ($questionStats as &$qs) {
    arsort($qs['incorrect_answers']);
    $qs['incorrect_answers'] = array_slice($qs['incorrect_answers'], 0, 3, true);
}
unset($qs);

// Bottleneck detector: questions with success rate < 50%
$bottlenecks = [];
foreach ($questionStats as $idx => $qs) {
    $successRate = ($qs['attempts'] > 0) ? ($qs['correct'] / $qs['attempts']) * 100 : 0;
    if ($successRate < 50 && $qs['attempts'] > 0) {
        $bottlenecks[] = array_merge($qs, ['rate' => round($successRate, 1)]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics: <?= htmlspecialchars($form['title']) ?> - IEEE MIU</title>
    <link rel="stylesheet" href="style.css?v=20">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --crit-red: #ff4757;
            --warn-gold: #ffa502;
            --success-green: #2ecc71;
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .stat-card .value {
            font-size: 2.5rem;
            font-weight: 800;
            margin: 0.5rem 0;
            display: block;
        }

        .stat-card .label {
            color: var(--text-muted);
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
        }

        .stat-card i {
            position: absolute;
            right: -10px;
            bottom: -10px;
            font-size: 5rem;
            opacity: 0.05;
        }

        .bottleneck-section {
            margin-bottom: 3rem;
        }

        .bottleneck-card {
            background: rgba(255, 71, 87, 0.05);
            border: 1px dashed var(--crit-red);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .bottleneck-card .rate-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 4px solid var(--crit-red);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: var(--crit-red);
            flex-shrink: 0;
        }

        .success-bar-bg {
            height: 8px;
            background: rgba(255,255,255,0.05);
            border-radius: 4px;
            margin-top: 1rem;
            overflow: hidden;
            border: 1px solid var(--glass-border);
        }

        .success-bar-fill {
            height: 100%;
            background: var(--primary-neon);
            box-shadow: 0 0 10px var(--primary-neon);
            transition: width 1s ease-out;
        }

        .pitfall-tag {
            background: rgba(255, 165, 2, 0.1);
            color: var(--warn-gold);
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-right: 0.5rem;
            border: 1px solid rgba(255, 165, 2, 0.2);
        }

        .question-detail-card {
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }

        .question-detail-card:hover {
            border-color: var(--secondary-neon);
        }

        .badge-type {
            font-size: 0.65rem;
            text-transform: uppercase;
            padding: 2px 6px;
            border-radius: 4px;
            background: rgba(255,255,255,0.1);
            color: var(--text-muted);
            margin-left: 10px;
            vertical-align: middle;
        }
    </style>
</head>
<body class="dashboard-page">
    <div class="sidebar-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="logo.png" alt="IEEE MIU" class="sidebar-logo" width="32" height="32">
                <h2 class="text-gradient" style="font-size: 1.2rem; margin: 0;">Portal</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php?tab=exams" class="nav-link active">
                    <i class="fas fa-arrow-left"></i> Back to Exams
                </a>
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i> Home
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="glass-panel dashboard-header" style="margin-bottom: 2rem;">
                <div class="header-content">
                    <h1 class="text-gradient">Advanced Performance Analytics</h1>
                    <p style="margin-top: 0.5rem;">Insights for: <strong><?= htmlspecialchars($form['title']) ?></strong></p>
                </div>
                <div class="header-actions">
                     <a href="form_responses.php?id=<?= $formId ?>" class="btn btn-outline btn-sm">
                        <i class="fas fa-list-alt"></i> Raw Responses
                    </a>
                </div>
            </header>

            <!-- Summary Section -->
            <div class="analytics-grid">
                <div class="glass-panel stat-card">
                    <span class="label">Pass Rate</span>
                    <span class="value text-gradient"><?= $passRate ?>%</span>
                    <p class="text-muted" style="font-size:0.8rem;"><?= $passedCount ?> / <?= $totalSubmissions ?> Passed</p>
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="glass-panel stat-card">
                    <span class="label">Average Score</span>
                    <span class="value" style="color: var(--secondary-neon);"><?= $avgScore ?>%</span>
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="glass-panel stat-card">
                    <span class="label">Highest Score</span>
                    <span class="value" style="color: var(--primary-neon);"><?= $highestScore ?>%</span>
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="glass-panel stat-card">
                    <span class="label">Submissions</span>
                    <span class="value"><?= $totalSubmissions ?></span>
                    <i class="fas fa-users"></i>
                </div>
            </div>

            <!-- Chart.js Visualizations -->
            <div class="analytics-grid" style="grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); margin-top: 1rem;">
                <div class="glass-panel" style="padding: 1.5rem;">
                    <h3 style="margin-bottom: 1rem; text-align: center;">Pass vs Fail Distribution</h3>
                    <div style="position: relative; height: 250px; width: 100%;">
                        <canvas id="passFailChart"></canvas>
                    </div>
                </div>
                <div class="glass-panel" style="padding: 1.5rem;">
                    <h3 style="margin-bottom: 1rem; text-align: center;">Score Distribution</h3>
                    <div style="position: relative; height: 250px; width: 100%;">
                        <canvas id="scoreDistChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Bottleneck Alerts -->
            <?php if (!empty($bottlenecks)): ?>
            <div class="bottleneck-section">
                <h3 style="margin-bottom: 1rem; display:flex; align-items:center; gap: 10px;">
                    <i class="fas fa-exclamation-triangle" style="color:var(--crit-red)"></i> 
                    Bottleneck Questions
                </h3>
                <?php foreach ($bottlenecks as $b): ?>
                <div class="bottleneck-card">
                    <div class="rate-circle"><?= $b['rate'] ?>%</div>
                    <div>
                        <h4 style="margin:0;"><?= htmlspecialchars($b['label']) ?></h4>
                        <p style="margin:5px 0 0; color:var(--text-muted); font-size:0.85rem;">
                            Common Mistake: 
                            <?php if (!empty($b['incorrect_answers'])): ?>
                                <span class="pitfall-tag"><?= htmlspecialchars(key($b['incorrect_answers'])) ?></span>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Detailed Breakdown -->
            <div class="detailed-section">
                <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-list-check"></i> Question Performance Breakdown</h3>
                
                <?php if (empty($questionStats)): ?>
                    <p class="glass-panel" style="padding:2rem; text-align:center; color:var(--text-muted);">No questions found in this assessment.</p>
                <?php else: ?>
                    <?php foreach ($questionStats as $idx => $qs): 
                        $rate = ($qs['attempts'] > 0) ? round(($qs['correct'] / $qs['attempts']) * 100, 1) : 0;
                        $color = ($rate >= 80) ? 'var(--primary-neon)' : (($rate >= 50) ? 'var(--secondary-neon)' : 'var(--crit-red)');
                    ?>
                    <div class="glass-panel question-detail-card">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                            <div>
                                <h4 style="margin:0;">
                                    Q<?= $idx + 1 ?>: <?= htmlspecialchars($qs['label']) ?>
                                    <span class="badge-type"><?= str_replace('_', ' ', $qs['type']) ?></span>
                                </h4>
                                <div style="margin-top:0.5rem; font-size:0.85rem;">
                                    <span style="color:var(--success-green);"><?= $qs['correct'] ?> Correct</span> • 
                                    <span style="color:var(--crit-red);"><?= $qs['attempts'] - $qs['correct'] ?> Incorrect</span>
                                </div>
                            </div>
                            <div style="text-align:right;">
                                <span style="font-weight:bold; color: <?= $color ?>; font-size: 1.2rem;"><?= $rate ?>%</span>
                                <div style="font-size:0.75rem; color:var(--text-muted);">Success Rate</div>
                            </div>
                        </div>
                        
                        <div class="success-bar-bg">
                            <div class="success-bar-fill" style="width: <?= $rate ?>%; background: <?= $color ?>; box-shadow: 0 0 10px <?= $color ?>;"></div>
                        </div>

                        <?php if (!empty($qs['incorrect_answers'])): ?>
                        <div style="margin-top:1rem; border-top: 1px solid rgba(255,255,255,0.05); padding-top:0.8rem;">
                            <span style="font-size:0.8rem; color:var(--text-muted); margin-right: 10px;">Top Wrong Answers:</span>
                            <?php foreach ($qs['incorrect_answers'] as $ans => $count): ?>
                                <span class="pitfall-tag"><?= htmlspecialchars($ans) ?> (<?= $count ?>)</span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Background Decoration -->
    <div class="glow-sphere" style="top: 10%; right: -5%; width: 400px; height: 400px; background: rgba(0, 243, 255, 0.05);"></div>
    <div class="glow-sphere" style="bottom: -10%; left: -5%; width: 500px; height: 500px; background: rgba(0, 255, 163, 0.03);"></div>

    <!-- Chart.js Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Data for Pass/Fail Chart
            const passed = <?= $passedCount ?>;
            const failed = <?= $totalSubmissions - $passedCount ?>;
            
            const passFailCtx = document.getElementById('passFailChart').getContext('2d');
            new Chart(passFailCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Passed', 'Failed'],
                    datasets: [{
                        data: [passed, failed],
                        backgroundColor: ['#2ecc71', '#ff4757'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: '#a0a0b0' }
                        }
                    }
                }
            });

            // Calculate Score Distributions (0-20, 21-40, 41-60, 61-80, 81-100)
            const scores = <?= json_encode($calculatedScores) ?>;
            let bins = [0, 0, 0, 0, 0];
            scores.forEach(s => {
                if(s <= 20) bins[0]++;
                else if(s <= 40) bins[1]++;
                else if(s <= 60) bins[2]++;
                else if(s <= 80) bins[3]++;
                else bins[4]++;
            });

            const scoreDistCtx = document.getElementById('scoreDistChart').getContext('2d');
            new Chart(scoreDistCtx, {
                type: 'bar',
                data: {
                    labels: ['0-20%', '21-40%', '41-60%', '61-80%', '81-100%'],
                    datasets: [{
                        label: 'Students',
                        data: bins,
                        backgroundColor: '#00f3ff',
                        borderRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, color: '#a0a0b0' },
                            grid: { color: 'rgba(255,255,255,0.05)' }
                        },
                        x: {
                            ticks: { color: '#a0a0b0' },
                            grid: { display: false }
                        }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        });
    </script>
</body>
</html>
