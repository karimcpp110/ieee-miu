<?php
require_once 'api_header.php';
require_once '../../Event.php';

$eventModel = new Event();
$events = $eventModel->getAll();

apiResponse($events, 200, "Events data retrieved.");
?>