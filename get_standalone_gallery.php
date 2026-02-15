<?php
require_once 'Auth.php';
require_once 'Database.php';
require_once 'galleryModel.php';

header('Content-Type: application/json');

if (!Auth::check()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (isset($_GET['section_id'])) {
    $galleryModel = new GalleryModel();
    $photos = $galleryModel->getPhotosBySection($_GET['section_id']);
    echo json_encode($photos);
} else {
    echo json_encode([]);
}
?>