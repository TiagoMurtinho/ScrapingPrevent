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
require_once '../data.php';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados</title>
    <link rel="stylesheet" href="../css/CSS.css">
</head>
<body>
<?php require '../nav.php'; ?>
<div class="results-container">
    <h1 class="results-title">Resultados</h1>
    <table class="results-table">
        <thead>
        <tr class="table-header">
            <th class="table-header-item">Nome do Produto</th>
            <th class="table-header-item">Preço</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($data as $item): ?>
            <tr class="table-row">
                <td class="table-cell"><?= htmlspecialchars($item['name']) ?></td>
                <td class="table-cell"><?= htmlspecialchars($item['price']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>