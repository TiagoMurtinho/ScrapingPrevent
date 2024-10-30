<?php
$data = require '../config.php';

$config = $data['config'];
$rateLimiter = $data['rateLimiter'];
$ip = $_SERVER['REMOTE_ADDR'];
$cookieId = $_COOKIE['user_identification'] ?? null;

$userId = null;
$userActivity = new UserActivity();

$userData = $userActivity->loadUserActivity($cookieId, $ip);

if ($userData) {
    $userId = $userData['id'];
}

if ($userId === null) {
    $userActivity->createUserActivity($ip, $cookieId);
    $userData = $userActivity->loadUserActivity($cookieId, $ip);
    if ($userData) {
        $userId = $userData['id'];
    }
}

require_once '../Controllers/AntiScrapingMiddleware.php';
use Controllers\AntiScrapingMiddleware;

$antiScraping = new AntiScrapingMiddleware($config, $rateLimiter, $userId);

$antiScraping->handle();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre</title>
    <link rel="stylesheet" href="../css/CSS.css">
</head>
<body>
<?php
require '../nav.php';
?>
<div class="config-container">
    <h1>Sobre o Microsite</h1>
    <p>Este é um microsite de exemplo que demonstra a aplicação de um middleware de prevenção de scraping.</p>
</div>
</body>
</html>