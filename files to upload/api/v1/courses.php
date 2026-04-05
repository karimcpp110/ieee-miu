<?php
require_once 'api_header.php';
require_once '../../Course.php';

// Enforce auth
$user = enforceApiKey();

$courseModel = new Course();
$courses = $courseModel->getAll();

apiResponse($courses, 200, "Successfully retrieved " . count($courses) . " courses.");
?>