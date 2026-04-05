<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../../Database.php';

function apiResponse($data = [], $status = 200, $message = '')
{
    http_response_code($status);
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data,
        'timestamp' => time()
    ]);
    exit;
}

function enforceApiKey() {
    $headers = apache_request_headers();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (empty($authHeader)) {
        apiResponse(null, 401, 'Unauthorized: Missing Authorization header. Provide Bearer Token.');
    }
    
    $token = trim(str_replace('Bearer', '', $authHeader));
    
    $db = new Database();
    
    // Check students first
    $stmt = $db->query("SELECT id, full_name, email FROM students WHERE api_key = ? LIMIT 1", [$token]);
    $student = $stmt->fetch();
    
    if ($student) {
        $student['role'] = 'student';
        return $student;
    }
    
    // Check admins/instructors
    $stmt_admin = $db->query("SELECT id, username, role FROM users WHERE api_key = ? LIMIT 1", [$token]);
    $admin = $stmt_admin->fetch();
    
    if ($admin) {
        return $admin;
    }

    apiResponse(null, 401, 'Unauthorized: Invalid API Key');
    return false;
}
?>