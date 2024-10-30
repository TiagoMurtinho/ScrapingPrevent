<?php
$data = require '../config.php';
require_once '../Controllers/UserActivity.php';

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

use Controllers\AntiScrapingMiddleware;
require_once '../Controllers/AntiScrapingMiddleware.php';

$middleware = new AntiScrapingMiddleware($config, $rateLimiter, $userId);
$message = '';
$result = $middleware->handle();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($result !== true) {
        $message = $result;
    } else {
        $message = '<div class="success-message">Formulário enviado com sucesso!</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulário</title>
    <link rel="stylesheet" href="../css/CSS.css">
</head>
<body>
<?php require '../nav.php'; ?>
<div class="form-container">
    <h1 class="form-title">Formulário</h1>
    <form method="POST" action="form.php" class="form">
        <?= $message ?>
        <input type="hidden" name="js_enabled" value="false" class="js-enabled-field">

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="name">
                    <p class="nome">Nome:</p>
                    <input type="text" name="name" placeholder="Nome" required class="form-input" id="name"/>
                </label>
            </div>

            <div class="form-group recaptcha-group">
                <label class="form-label">
                    <div class="g-recaptcha" data-sitekey="6Lc5FWMqAAAAAJZ6pFC4q4Vr_OUjJE1sdLLEdpq1"></div>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">
                <?= $config['honeyPot']->generateHoneyPot() ?>
            </label>
        </div>

        <button type="submit" class="form-submit-button">Enviar</button>
    </form>
</div>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script src="../js/custom.js"></script>
<script>
    document.querySelector('input[name="email"]').value = '123';
</script>
</body>
</html>

