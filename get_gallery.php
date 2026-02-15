<?php
require_once 'Auth.php';
require_once 'Database.php';
require_once 'Event.php';

if (!Auth::check()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['event_id'])) {
    echo json_encode([]);
    exit;
}

$eventModel = new Event();
echo json_encode($eventModel->getGallery($_GET['event_id']));
