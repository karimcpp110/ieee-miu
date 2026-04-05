<?php
header('Content-Type: application/json');
require_once 'api_header.php';

// Ensure standard API security is enforced
$authData = enforceApiKey();

// Process incoming payload
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!isset($input['scorePercentage']) || !isset($input['insights'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing exam context data']);
    exit;
}

$score = (float)$input['scorePercentage'];
$insights = $input['insights']; // Array of incorrect questions with student's answer vs correct

// If they aced it, short-circuit
if ($score >= 100) {
    echo json_encode([
        'status' => 'success',
        'feedback' => "Perfect score! 🌟 You demonstrated complete mastery of this module. No further hints needed, keep up the legendary work!"
    ]);
    exit;
}

// ------------------------------------------------------------------
// AI ENGINE HOOKUP 
// Define your API key in a secure env variable in production
// ------------------------------------------------------------------
$openAiApiKey = getenv('OPENAI_API_KEY') ?: '';

if (!empty($openAiApiKey)) {
    // REAL OPENAI CALL
    $prompt = "You are an AI teaching assistant. A student just scored {$score}% on an exam.\nHere are the questions they got wrong:\n";
    foreach ($insights as $insight) {
        $prompt .= "- Q: {$insight['question']}\n  Student wrote: {$insight['student_ans']}\n  Correct was: {$insight['correct_ans']}\n";
    }
    $prompt .= "\nWrite a friendly, constructive 3-4 sentence paragraph analyzing their specific mistakes. Identify the core concept they are struggling with (e.g., 'Arrays', 'Pointers', 'React Lifecycle') based on the questions, and give them a highly specific hint. Do NOT give them the direct answers again, just teach the concept.";

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    $payload = json_encode([
        "model" => "gpt-3.5-turbo",
        "messages" => [
            ["role" => "system", "content" => "You are a helpful, expert tutor for engineering students."],
            ["role" => "user", "content" => $prompt]
        ],
        "temperature" => 0.7,
        "max_tokens" => 200
    ]);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $openAiApiKey
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        $responseData = json_decode($response, true);
        $aiText = $responseData['choices'][0]['message']['content'] ?? "Analyzing...";
        echo json_encode(['status' => 'success', 'feedback' => trim($aiText), 'engine' => 'openai']);
        exit;
    }
}

// ------------------------------------------------------------------
// HEURISTIC FALLBACK MOCK (Demo/Portfolio Mode)
// ------------------------------------------------------------------
sleep(1); // Simulate AI thinking time for realistic UX

// Basic keyword detection to guess the topic
$topics = [
    'array' => 'Data Structures (Arrays)',
    'loop' => 'Control Flow (Loops)',
    'pointer' => 'Memory Management (Pointers)',
    'react' => 'React Frontend Concepts',
    'sql' => 'Database Queries',
    'api' => 'API Interactions',
    'class' => 'Object-Oriented Programming',
    'function' => 'Functions and Scope'
];

$detectedTopic = "Core Fundamentals";
foreach ($insights as $insight) {
    global $detectedTopic;
    foreach ($topics as $keyword => $topicName) {
        if (stripos($insight['question'], $keyword) !== false || stripos($insight['correct_ans'], $keyword) !== false) {
            $detectedTopic = $topicName;
            break 2;
        }
    }
}

$encouragement = $score >= 60 ? "Great job passing! However, to reach mastery," : "You didn't pass this time, but every failure is a stepping stone.";

if (count($insights) === 1) {
    $qLabel = htmlspecialchars($insights[0]['question']);
    $feedback = "$encouragement I noticed you slipped up on '$qLabel'. This shows a slight gap in **{$detectedTopic}**. Next time, remember to trace the execution step-by-step before answering. You've got this!";
} else {
    $feedback = "$encouragement I'm analyzing your incorrect answers, and it looks like you are consistently struggling with **{$detectedTopic}**. Specifically, questions involving these mechanics tripped you up. I highly recommend re-watching the module on {$detectedTopic} and practicing a few sandbox examples before retaking. You are very close!";
}

echo json_encode([
    'status' => 'success', 
    'feedback' => $feedback,
    'engine' => 'heuristic'
]);
