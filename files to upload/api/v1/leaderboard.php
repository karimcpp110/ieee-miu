<?php
require_once 'api_header.php';
require_once '../../Gamification.php';

$gamification = new Gamification();
$limit = $_GET['limit'] ?? 10;
$leaderboard = $gamification->getLeaderboard((int) $limit);

apiResponse($leaderboard, 200, "Hall of Fame data retrieved.");
?>