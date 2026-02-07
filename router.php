<?php
// Simple router for PHP built-in server to serve static files
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// If it's a request for an uploaded file
if (preg_match('/^\/uploads\//', $uri)) {
    $file = __DIR__ . $uri;
    
    if (file_exists($file) && is_file($file)) {
        // Get the file extension
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        
        // Set appropriate content type
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];
        
        $contentType = $mimeTypes[strtolower($ext)] ?? 'application/octet-stream';
        
        header('Content-Type: ' . $contentType);
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    } else {
        http_response_code(404);
        echo "File not found: $uri";
        exit;
    }
}

// Otherwise, let PHP handle it normally
return false;
?>
