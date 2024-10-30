<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../Controllers/AwsIpChecker.php';
require_once '../Controllers/AntiScrapingMiddleware.php';
use Controllers\AntiScrapingMiddleware;
use Controllers\AwsIpChecker;

$data = require '../config.php';

$config = $data['config'];
$rateLimiter = $data['rateLimiter'];
$ip = $_SERVER['REMOTE_ADDR'];
$cookieId = $_COOKIE['user_identification'] ?? null;

$redis = new Redis();
$redis->connect('127.0.0.1');
$userActivity = new UserActivity();
$awsIpChecker = new AwsIpChecker($redis);
$userData = $userActivity->loadUserActivity($cookieId, $ip);

$userId = $userData['id'];
$userActivity->setUserId($userId);

$antiScraping = new AntiScrapingMiddleware($config, $rateLimiter, $userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ip'])) {
    $ipToUnblock = $_POST['ip'];
    $awsIpChecker->unmarkAwsIp($ipToUnblock);
    $userActivity->updateErrorDisplayedInDatabase(0);
    $antiScraping->decreaseScore(15);
}



$antiScraping->handle();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página Inicial</title>
    <link rel="stylesheet" href="../css/CSS.css">
</head>
<body>
<?php require '../nav.php'; ?>
<div class="config-container">
    <h1>Bem-vindo ao Microsite!</h1>
</div>
<script src="../js/custom.js"></script>
</body>
</html>