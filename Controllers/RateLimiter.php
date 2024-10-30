<?php

namespace Controllers;

use UserActivity;

require_once 'UserActivity.php';

class RateLimiter
{
    private $redis;
    private $awsIpChecker;
    private $blacklistChecker;
    private $antiScrapingMiddleware;
    private $config;
    private $userActivity;
    private $userId;
    private $cookieId;
    private $currentScore;

    public function __construct($redis, $blacklistChecker, $config, $userId, $awsIpChecker = null)
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $this->redis = $redis;
        $this->blacklistChecker = $blacklistChecker;
        $this->config= $config;
        $this->userId = $userId;
        $this->cookieId = $_COOKIE['user_identification'] ?? uniqid('user_', true);
        $this->userActivity = new UserActivity($this->userId);
        $userData = $this->userActivity->loadUserActivity($this->cookieId, $ip);
        $this->currentScore = $userData['score'] ?? 0;
        $this->awsIpChecker = $awsIpChecker ?: false;

    }

    public function setAntiScrapingMiddleware(AntiScrapingMiddleware $antiScrapingMiddleware)
    {
        $this->antiScrapingMiddleware = $antiScrapingMiddleware;
    }

    public function isAllowed(): bool
    {
        global $ip;
        $currentSecond = time();
        /*$ip = "47.108.95.236";*/
        /* $ip = "13.49.33.123";*/

        if (isset($this->blacklistChecker) && $this->blacklistChecker !== false) {
            if ($this->blacklistChecker->isIpBlacklisted($ip)) {
                $this->antiScrapingMiddleware->increaseScore($this->config['blacklist_penalty']);
                $newScore = $this->currentScore + $this->config['blacklist_penalty'];
                $this->userActivity->updateScoreInDatabase($newScore);
                $this->currentScore = $newScore;
            }
        }

        if ($this->awsIpChecker && $this->awsIpChecker->isAwsIp($ip) && $this->awsIpChecker->isAwsIpMarked($ip)) {
            require '../Views/error-view.php';
            exit;
        }

        $this->logRequest($ip);
        $averageRequestsPerSecond = $this->calculateAverageRequestsPerSecond($ip);
        $dynamicLimit = $this->adjustLimitBasedOnAverage($averageRequestsPerSecond);

        $key = "request_count:$ip:$currentSecond";
        $requestCount = $this->redis->get($key);

        if ($requestCount === false) {
            $this->redis->set($key, 1, 1);
            return true;
        }

        if ($requestCount >= $dynamicLimit) {
            $this->handleInfraction($ip);
            return false;
        }

        $this->redis->incr($key);
        return true;
    }

    private function logRequest($ip)
    {
        $key = "request_count:$ip";
        $currentTime = time();
        $this->redis->rPush($key, $currentTime);
        $this->redis->expire($key, 86400);
    }

    private function calculateAverageRequestsPerSecond($ip): float
    {
        $key = "request_count:$ip";
        $currentTime = time();
        $timestamps = $this->redis->lRange($key, 0, -1);

        $totalRequests = count($timestamps);

        if ($totalRequests === 0) {
            return 0.0;
        }

        $firstRequestTime = intval($timestamps[0]);
        $totalTimeElapsed = $currentTime - $firstRequestTime;

        return $totalTimeElapsed > 0 ? $totalRequests / $totalTimeElapsed : $totalRequests;
    }

    private function adjustLimitBasedOnAverage($averageRequests): int
    {
        $adjustedLimit = max(3, intval($averageRequests));
        return $adjustedLimit;
    }

    private function handleInfraction($ip)
    {
        $infractionCount = $this->logInfraction($ip);

        $newScore = $this->currentScore;

        if ($infractionCount == 1) {
            $newScore += $this->config['rate_penalty'];
        } elseif ($infractionCount == 2) {
            if ($this->awsIpChecker && $this->awsIpChecker->isAwsIp($ip)) {
                $this->awsIpChecker->markAwsIpAsOffending($ip);
                $newScore += $this->config['aws_penalty'];
            }
            $newScore += $this->config['rate_penalty'];
        } elseif ($infractionCount >= 3) {
            $newScore += $this->config['rate_penalty'];
        }

        $this->currentScore = $newScore;
        $this->userActivity->updateScoreInDatabase($newScore);

    }

    private function logInfraction($ip): int
    {
        $key = "infraction_count:$ip";
        $infractionCount = $this->redis->get($key);

        if ($infractionCount === false) {
            $infractionCount = 1;
        } else {
            $infractionCount++;
        }

        $this->redis->set($key, $infractionCount, 86400);
        return $infractionCount;
    }

    public function resetRequests($ip)
    {
        $key = "request_count:$ip";
        $this->redis->del($key);
    }

    public function getRequestCount($ip): int
    {
        $key = "request_count:$ip";
        $requestCount = $this->redis->get($key);
        return $requestCount === false ? 0 : intval($requestCount);
    }
}