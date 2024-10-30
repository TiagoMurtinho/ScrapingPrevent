<?php
use Controllers\AwsIpChecker;
require_once '../Controllers/AwsIpChecker.php';
require_once '../Controllers/UserActivity.php';
$redis = new Redis();
$redis->connect('127.0.0.1');

$awsIpChecker = new AwsIpChecker($redis);

$ip = "13.49.33.123";
$ip = $_SERVER['REMOTE_ADDR'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ip'])) {
    $ipToUnblock = $_POST['ip'];
    $awsIpChecker->unmarkAwsIp($ipToUnblock);
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro</title>
    <link rel="stylesheet" href="../css/error.css">
</head>
<body>
<div>
    <h1>Ocorreu um Erro!</h1>
    <form action="index.php" method="post">
        <input type="hidden" name="js_enabled" value="false" class="js-enabled-field">
        <input type="hidden" name="ip" value="<?php echo htmlspecialchars($ip); ?>">
        <button type="submit" class="button">Voltar para a Página Inicial</button>
    </form>
</div>
<script src="../js/custom.js"></script>
</body>
</html>