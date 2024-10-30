<?php

namespace Controllers;
use UserActivity;

require_once 'UserActivity.php';

class AntiScrapingMiddleware
{
    private $config;
    private $cookieId;
    private $score;
    private $requestsWithoutCookie;
    private $requestsWithDifferentReferer;
    private $firstRequest;
    private $rateLimiter;
    private $userActivity;
    private $captchaAttempts;
    private $errorDisplayed;
    private $errorMarkedOnce;
    private $isBlocked;
    private $userId;

    public function __construct($config,  RateLimiter $rateLimiter, $userId)
    {
        $this->config = $config;
        $this->rateLimiter = $rateLimiter;
        $this->userId = $userId;

        $ip = $_SERVER['REMOTE_ADDR'];
        $this->cookieId = $_COOKIE['user_identification'] ?? uniqid('user_', true);

        $this->userActivity = new UserActivity($this->userId);
        $userData = $this->userActivity->loadUserActivity($this->cookieId, $ip);

        if (!$userData) {
            $this->userActivity->createUserActivity($ip, $this->cookieId);
            $this->score = 0;
            $this->requestsWithoutCookie = 0;
            $this->requestsWithDifferentReferer = 0;
            $this->firstRequest = true;
            $this->captchaAttempts = 0;
            $this->errorDisplayed = 0;
            $this->errorMarkedOnce = 0;
            $this->isBlocked = 0;
        } else {
            $this->score = $userData['score'];
            $this->requestsWithoutCookie = $userData['requests_without_cookie'];
            $this->requestsWithDifferentReferer = $userData['requests_with_different_referer'];
            $this->firstRequest = $userData['first_request'];
            $this->captchaAttempts = $userData['captcha_attempts'];
            $this->errorDisplayed = $userData['error_displayed'];
            $this->errorMarkedOnce = $userData['error_marked_once'];
            $this->isBlocked = $userData['is_blocked'];
        }
    }
    public function handle()
    {
        $this->checkScore();

        if ($this->userActivity->getErrorDisplayed() === 1) {
            require '../Views/error-view.php';
            exit;
        }

        if ($this->userActivity->getIsBlocked() === 1) {
            require '../Views/error-view.php';
            exit;
        }

        if ($this->config['cookie_id_enabled']) {
            if (!isset($_COOKIE['user_identification'])) {
                $this->requestsWithoutCookie++;

                $this->userActivity->updateRequestsWithoutCookieInDatabase($this->requestsWithoutCookie);

                $this->cookieId = uniqid('user_', true);
                setcookie('user_identification', $this->cookieId, time() + (86400 * 30), "/");

                $this->userActivity->updateUserCookieId($this->cookieId, $this->userId);

            } else {
                $this->cookieId = $_COOKIE['user_identification'];
            }
        }

        if ($this->requestsWithoutCookie >= 3) {
            $this->increaseScore($this->config['cookie_id_penalty']);
            $this->requestsWithoutCookie = 0;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($this->config['js_enabled'] && (!isset($_POST['js_enabled']) || $_POST['js_enabled'] !== 'true')) {
                $this->increaseScore($this->config['js_penalty']);
            }
        }

        if ($this->config['userAgentBlocker'] !== false && is_object($this->config['userAgentBlocker'])) {
            $userAgent = $this->config['userAgent'];
            if (!$this->config['userAgentBlocker']->isAllowed($userAgent)) {
                $this->increaseScore($this->config['user_agent_penalty']);
            }
        }

        if ($this->config['referer_checker']) {
            if (!$this->config['refererChecker']->isAllowed()) {
                if (!$this->firstRequest) {
                    $this->requestsWithDifferentReferer++;

                    $this->userActivity->updateRequestsWithDifferentRefererInDatabase($this->requestsWithDifferentReferer);
                } else {
                    $this->firstRequest = false;
                    $this->userActivity->updateFirstRequestInDatabase(false);
                }

                if ($this->requestsWithDifferentReferer >= 3) {
                    $this->increaseScore($this->config['referer_penalty']);
                    $this->requestsWithDifferentReferer = 0;
                }
            }
        }

        if ($this->config['rate_limiter'] && !$this->config['rateLimiter']->isAllowed($this->cookieId)) {
            exit;
        }

        if ($this->config['honey_pot'] && !$this->config['honeyPot']->validate($_POST)) {
            $this->increaseScore($this->config['honey_pot_penalty']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $this->config['captcha'] === true) {
            $captchaResponse = $_POST['g-recaptcha-response'] ?? '';
            $captchaIsValid = $this->validateCaptcha($captchaResponse);

            $currentAttempts = $this->userActivity->getCaptchaAttempts();

            if ($captchaIsValid) {
                $this->captchaAttempts = 0;
            } else {
                $currentAttempts++;
                $this->userActivity->updateCaptchaAttemptsInDatabase($currentAttempts);

                if ($currentAttempts >= 5) {
                    $this->increaseScore($this->config['captcha_penalty']);
                    $this->userActivity->updateCaptchaAttemptsInDatabase(0);
                }
                return '<div class="error-message">Falha na verificação do CAPTCHA. Tente novamente.</div>';
            }
        }

        $this->userActivity->updateUserActivity([
            'score' => $this->score,
            'requests_without_cookie' => $this->requestsWithoutCookie,
            'requests_with_different_referer' => $this->requestsWithDifferentReferer,
            'captcha_attempts' => $this->captchaAttempts,
            'error_displayed' => $this->errorDisplayed,
            'error_marked_once' => $this->errorMarkedOnce,
            'is_blocked' => $this->isBlocked,
        ]);

        return true;
    }


    private function checkScore()
    {
        $sanctionRules = $this->userActivity->loadSanctionsByUserId($this->userId);
        global $userData;
        foreach ($sanctionRules as $rule) {
            if ($this->score >= $rule['score_threshold']) {
                switch ($rule['sanction_type']) {
                    case 'timeout':
                        sleep((int) $rule['sanction_value']);
                        break;

                    case 'error':

                        if ($userData && $userData['error_displayed'] === 0 && $userData['error_marked_once'] === 0) {
                            $this->errorDisplayed = 1;

                            $this->userActivity->updateErrorDisplayedInDatabase(1);
                            $this->userActivity->setErrorMarkedOnce(1);

                            require '../Views/error-view.php';
                        }
                        break;

                    case 'block':
                        $this->userActivity->updateUserActivity([
                            'is_blocked' => $this->isBlocked,
                        ]);
                        break;
                }
            }
        }
    }

    public function increaseScore(int $points): bool
    {

        $this->score += $points;
        if ($this->score > 100) {
            $this->score = 100;
        }

        if ($this->score === 100) {
            $this->isBlocked = true;
            echo "Usuário bloqueado devido ao score máximo de 100.<br>";
        }

        $this->userActivity->updateUserActivity([
            'is_blocked' => $this->isBlocked,
        ]);

        return true;
    }

    public function decreaseScore(int $points)
    {
        $this->score -= $points;
        if ($this->score < 0) {
            $this->score = 0;
        }

    }

    private function validateCaptcha($captchaResponse): bool
    {
        if (empty($captchaResponse)) {
            return false;
        }

        $secretKey = '6Lc5FWMqAAAAANTojmQ8gMgaTx5qijIFd-_7eRnx';
        $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$captchaResponse");
        $responseKeys = json_decode($response, true);

        return !empty($responseKeys['success']) && $responseKeys['success'] === true;
    }
}