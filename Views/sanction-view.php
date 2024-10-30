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

session_start();
if (isset($_SESSION['message'])) {
    echo '<div class="success-message">' . $_SESSION['message'] . '</div>';
    unset($_SESSION['message']);
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Sanções</title>
    <link rel="stylesheet" href="../css/CSS.css">
</head>
<body>

<?php require '../nav.php'; ?>

<div class="sanction-container">
    <div class="title-button-container">
        <h2 class="config-title">Gerenciar Sanções</h2>
        <a href="add-sanction-view.php" class="sanction-add-button">
            Adicionar Nova Sanção
        </a>
    </div>

    <table class="results-table">
        <thead>
        <tr class="table-header">
            <th class="table-header-item">Score Threshold</th>
            <th class="table-header-item">Tipo de Sanção</th>
            <th class="table-header-item">Valor da Sanção</th>
            <th class="table-header-item">Ações</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($sanctions as $sanction): ?>
            <tr class="table-row">
                <td class="table-cell"><?= htmlspecialchars($sanction['score_threshold']) ?></td>
                <td class="table-cell"><?= htmlspecialchars($sanction['sanction_type']) ?></td>
                <td class="table-cell"><?= htmlspecialchars($sanction['sanction_value']) ?></td>
                <td class="table-cell">
                    <a href="../delete_sanction.php?id=<?= $sanction['id'] ?>" class="sanction-delete-button">Excluir</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script src="../js/custom.js"></script>
</body>
</html>