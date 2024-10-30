<?php
require_once '../Controllers/AntiScrapingMiddleware.php';
use Controllers\AntiScrapingMiddleware;
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

$sanctions = $userActivity->loadSanctionsByUserId($userId);

$antiScraping = new AntiScrapingMiddleware($config, $rateLimiter, $userId);

$antiScraping->handle();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Nova Sanção</title>
    <link rel="stylesheet" href="../css/CSS.css">
</head>
<body>

<?php require '../nav.php'; ?>

<div class="sanction-container">
    <h3 class="sanction-title">Adicionar Nova Sanção</h3>
    <form method="post" action="../add_sanction.php" class="sanction-form">
        <input type="hidden" name="user_id" value="<?= htmlspecialchars($userId) ?>">

        <div class="sanction-form-group">
            <label class="sanction-form-label">Score Threshold: </label>
            <input type="number" name="score_threshold" class="sanction-form-input" required>
        </div>

        <div class="sanction-form-group">
            <label class="sanction-form-label">Tipo de Sanção: </label>
            <select name="sanction_type" class="sanction-form-input" required>
                <option value="timeout">Timeout</option>
                <option value="error">Erro</option>
                <option value="block">Bloquear</option>
            </select>
        </div>

        <div class="sanction-form-group">
            <label class="sanction-form-label">Valor da Sanção (em segundos para timeout): </label>
            <input type="number" name="sanction_value" class="sanction-form-input">
        </div>

        <button type="submit" class="sanction-submit-button">Adicionar Sanção</button>
    </form>
</div>

</body>
</html>