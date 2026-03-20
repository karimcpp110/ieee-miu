<?php
require_once 'api_header.php';
require_once '../../Course.php';

$courseModel = new Course();
$courses = $courseModel->getAll();

apiResponse($courses, 200, "Successfully retrieved " . count($courses) . " courses.");
?>