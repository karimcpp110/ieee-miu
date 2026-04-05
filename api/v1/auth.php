<?php
require_once 'api_header.php';
require_once '../../Database.php';

// The auth endpoint should NOT call enforceApiKey() as it is the entry point.
$db = new Database();

// Get JSON input
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

$login = isset($input['email']) ? $input['email'] : (isset($input['username']) ? $input['username'] : '');
$password = isset($input['password']) ? $input['password'] : '';

if (empty($login) || empty($password)) {
    apiResponse(null, 400, 'Missing credentials (email/username and password required)');
}

// 1. Check Students Table first
$student = $db->query("SELECT * FROM students WHERE email = ? LIMIT 1", [$login])->fetch();

if ($student) {
    // Check password (handling legacy plain text)
    $isMatch = false;
    if (password_verify($password, $student['password'])) {
        $isMatch = true;
    } elseif ($password === $student['password']) {
        $isMatch = true;
    }

    if ($isMatch) {
        $apiKey = $student['api_key'];
        if (empty($apiKey)) {
            $apiKey = bin2hex(random_bytes(32));
            $db->query("UPDATE students SET api_key = ? WHERE id = ?", [$apiKey, $student['id']]);
        }
        apiResponse([
            'token' => $apiKey,
            'user' => [
                'id' => $student['id'],
                'name' => $student['full_name'],
                'email' => $student['email'],
                'role' => 'student'
            ]
        ], 200, 'Authentication successful');
    }
}

// 2. Check Users Table (Admins/Instructors)
$user = $db->query("SELECT * FROM users WHERE username = ? LIMIT 1", [$login])->fetch();

if ($user) {
    if (password_verify($password, $user['password']) || $password === $user['password']) {
        $apiKey = $user['api_key'];
        if (empty($apiKey)) {
            $apiKey = bin2hex(random_bytes(32));
            $db->query("UPDATE users SET api_key = ? WHERE id = ?", [$apiKey, $user['id']]);
        }
        apiResponse([
            'token' => $apiKey,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ]
        ], 200, 'Authentication successful');
    }
}

// If we reached here, auth failed
apiResponse(null, 401, 'Invalid credentials');
