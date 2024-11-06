<?php

use Controllers\AntiScrapingMiddleware;
use Controllers\HoneyPot;
use Controllers\RateLimiter;
use Controllers\RefererChecker;
use Controllers\UserAgentBlocker;
use Controllers\BlacklistChecker;
use Controllers\AwsIpChecker;

require_once 'Controllers/RateLimiter.php';
require_once 'Controllers/HoneyPot.php';
require_once 'Controllers/UserAgentBlocker.php';
require_once 'Controllers/RefererChecker.php';
require_once 'Controllers/BlacklistChecker.php';
require_once 'Controllers/AwsIpChecker.php';
require_once 'Controllers/AntiScrapingMiddleware.php';

session_start();

$config = [];

$redis = new Redis();
$redis->connect('127.0.0.1');

$config['ip'] = $_SERVER['REMOTE_ADDR'];
$config['userAgent'] = $_SERVER['HTTP_USER_AGENT'];
$config['captchaResponse'] = $_POST['g-recaptcha-response'] ?? '';
$config['honeyPot'] = new HoneyPot();
$config['userAgentBlocker'] = null;
$config['refererChecker'] = new RefererChecker(['http://localhost/ScrapingPrevent/']);

$defaultConfig = [
    'js_enabled' => true,
    'user_agent_blocker' => true,
    'referer_checker' => true,
    'rate_limiter' => true,
    'honey_pot' => true,
    'captcha' => true,
    'blacklist_checker' => true,
    'aws_ip_checker' => true,
    'cookie_id_enabled' => true,
    'js_penalty' => 25,
    'user_agent_penalty' => 25,
    'referer_penalty' => 25,
    'rate_penalty' => 25,
    'honey_pot_penalty' => 25,
    'captcha_penalty' => 10,
    'blacklist_penalty' => 100,
    'aws_penalty' => 25,
    'cookie_id_penalty' => 25,
];

$cookie_id = $_COOKIE['user_identification'] ?? null;

if ($cookie_id) {
    require_once 'db_connection.php';
    $database = new Database();
    $conn = $database->getConnection();
    $database->createTables();

    $query = "SELECT config_type_id, config_value, penalty_value 
              FROM user_activity_has_config_type 
              WHERE user_activity_id = (SELECT id FROM user_activity WHERE cookie_id = :cookie_id LIMIT 1)";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':cookie_id', $cookie_id);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        switch ($row['config_type_id']) {
            case 1:
                $config['js_enabled'] = (bool) $row['config_value'];
                $config['js_penalty'] = (int) $row['penalty_value'];
                break;
            case 2:
                $config['user_agent_blocker'] = (bool) $row['config_value'];
                $config['user_agent_penalty'] = (int) $row['penalty_value'];
                break;
            case 3:
                $config['referer_checker'] = (bool) $row['config_value'];
                $config['referer_penalty'] = (int) $row['penalty_value'];
                break;
            case 4:
                $config['rate_limiter'] = (bool) $row['config_value'];
                $config['rate_penalty'] = (int) $row['penalty_value'];
                break;
            case 5:
                $config['honey_pot'] = (bool) $row['config_value'];
                $config['honey_pot_penalty'] = (int) $row['penalty_value'];
                break;
            case 6:
                $config['captcha'] = (bool) $row['config_value'];
                $config['captcha_penalty'] = (int) $row['penalty_value'];
                break;
            case 7:
                $config['blacklist_checker'] = (bool) $row['config_value'];
                $config['blacklist_penalty'] = (int) $row['penalty_value'];
                break;
            case 8:
                $config['aws_ip_checker'] = (bool) $row['config_value'];
                $config['aws_penalty'] = (int) $row['penalty_value'];
                break;
            case 9:
                $config['cookie_id_enabled'] = (bool) $row['config_value'];
                $config['cookie_id_penalty'] = (int) $row['penalty_value'];
                break;
        }
    }
} else {
    $config = array_merge($defaultConfig, $config);
}

foreach ($defaultConfig as $key => $value) {
    if (!isset($config[$key])) {
        $config[$key] = $value;
    }
}

if ($config['user_agent_blocker'] === true) {
    $config['userAgentBlocker'] = new UserAgentBlocker(['curl', 'scrapy', 'python', 'PostmanRuntime/7.42.0']);
} else {
    $config['userAgentBlocker'] = false;
}

if ($config['blacklist_checker'] === true) {
    $blacklistChecker = new BlacklistChecker('a3799fd9bb2d1974505418b50d4c0769b19a2fb4390bf58c32c177b123290b64941c1c1c28f14ec7');
} else {
    $blacklistChecker = false;
}

$awsIpChecker = $config['aws_ip_checker'] === true ? new AwsIpChecker($redis) : false;

$ip = $_SERVER['REMOTE_ADDR'];
$cookieId = $_COOKIE['user_identification'] ?? null;
$userActivity = new UserActivity();
$userId = null;
if ($cookieId) {
    $userData = $userActivity->loadUserActivity($cookieId, $ip);
    if ($userData) {
        $userId = $userData['id'];
    }
}

if ($ip === null) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $userActivity->createUserActivity($ip, $cookieId);
    $userData = $userActivity->loadUserActivity($cookieId, $ip);
    if ($userData) {
        $userId = $userData['id'];
    }
}

$rateLimiter = new RateLimiter($redis, $blacklistChecker, $config, $userId, $awsIpChecker);
$antiScrapingMiddleware = new AntiScrapingMiddleware($config, $rateLimiter, $userId);

$rateLimiter->setAntiScrapingMiddleware($antiScrapingMiddleware);

$config['rateLimiter'] = $rateLimiter;

return [
    'config' => $config,
    'rateLimiter' => $rateLimiter,
];