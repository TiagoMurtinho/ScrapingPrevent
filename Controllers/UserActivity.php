<?php
require_once __DIR__ . '/../db_connection.php';

class UserActivity {
    private $conn;
    private $userId;

    public function __construct($userId = null) {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->userId = $userId;
    }

    public function loadUserActivity($cookie_id = null, $ip = null) {
        if ($cookie_id) {
            $query = "SELECT * FROM user_activity WHERE cookie_id = :cookie_id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':cookie_id', $cookie_id);
            $stmt->execute();

            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($userData) {
                return $userData;
            }
        }

        if ($ip) {
            $query = "SELECT * FROM user_activity WHERE ip = :ip LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':ip', $ip);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return null;
    }

    public function updateUserActivity($data): bool
    {
        $query = "UPDATE user_activity 
                  SET score = :score, requests_without_cookie = :requestsWithoutCookie, 
                      requests_with_different_referer = :requestsWithDifferentReferer,
                      captcha_attempts = :captchaAttempts, error_displayed = :errorDisplayed, error_marked_once = :errorMarkedOnce, is_blocked = :isBlocked
                  WHERE id = :userId";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':score', $data['score']);
        $stmt->bindParam(':requestsWithoutCookie', $data['requests_without_cookie']);
        $stmt->bindParam(':requestsWithDifferentReferer', $data['requests_with_different_referer']);
        $stmt->bindParam(':captchaAttempts', $data['captcha_attempts']);
        $stmt->bindParam(':errorDisplayed', $data['error_displayed']);
        $stmt->bindParam(':errorMarkedOnce', $data['error_marked_once']);
        $stmt->bindParam(':isBlocked', $data['is_blocked']);
        $stmt->bindParam(':userId', $this->userId);
        return $stmt->execute();
    }

    public function createUserActivity($ip, $cookieId) {
        $query = "INSERT INTO user_activity (ip, cookie_id, score, requests_without_cookie, requests_with_different_referer, first_request, captcha_attempts, error_displayed, error_marked_once, is_blocked, created_at)
                  VALUES (:ip, :cookieId, 0, 0, 0, 1, 0, 0, 0, 0, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':ip', $ip);
        $stmt->bindParam(':cookieId', $cookieId);
        $stmt->execute();

        $userId = $this->conn->lastInsertId();

        $defaultConfigs = $this->getDefaultConfigTypes();
        foreach ($defaultConfigs as $config) {
            $this->addConfigToUserActivity(
                $userId,
                $config['config_type_id'],
                $config['penalty_id'],
                $config['config_value'],
                $config['penalty_value']
            );
        }

        return $this->userId;
    }

    public function getConfig() {
        $query = "SELECT config_type.config_type, config_type.default_value AS config_value, penalties.default_value AS penalty_value
                  FROM user_activity_has_config_type AS uact
                  JOIN config_type ON uact.config_type_id = config_type.id
                  JOIN penalties ON uact.penalty_type_id = penalties.id
                  WHERE uact.user_activity_id = :userId";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $this->userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDefaultConfigTypes() {

        $query = "SELECT ct.id AS config_type_id, ct.default_value AS config_value, 
                     p.id AS penalty_id, p.default_value AS penalty_value
              FROM config_type ct 
              JOIN penalties p ON ct.id = p.id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addConfigToUserActivity($userActivityId, $configTypeId, $penaltyTypeId, $configValue, $penaltyValue): bool
    {
        $query = "INSERT INTO user_activity_has_config_type 
              (user_activity_id, config_type_id, penalties_id, config_value, penalty_value) 
              VALUES (:userActivityId, :configTypeId, :penaltyTypeId, :configValue, :penaltyValue)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userActivityId', $userActivityId);
        $stmt->bindParam(':configTypeId', $configTypeId);
        $stmt->bindParam(':penaltyTypeId', $penaltyTypeId);
        $stmt->bindParam(':configValue', $configValue);
        $stmt->bindParam(':penaltyValue', $penaltyValue);
        return $stmt->execute();
    }

    public function updateCaptchaAttemptsInDatabase($attempts) {

        $query = "UPDATE user_activity SET captcha_attempts = :attempts WHERE id = :userId";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':attempts', $attempts);
        $stmt->bindParam(':userId', $this->userId);

        if (!$stmt->execute()) {
            error_log("Erro ao atualizar tentativas de CAPTCHA: " . $stmt->errorInfo()[2]);
        }
    }

    public function getCaptchaAttempts(): int
    {
        $query = "SELECT captcha_attempts FROM user_activity WHERE id = :userId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $this->userId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['captcha_attempts'] : 0;
    }

    public function updateFirstRequestInDatabase($firstRequest) {
        $query = "UPDATE user_activity SET first_request = :firstRequest WHERE id = :userId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':firstRequest', $firstRequest, PDO::PARAM_BOOL);
        $stmt->bindParam(':userId', $this->userId);

        if (!$stmt->execute()) {
            echo "Erro ao atualizar first_request: " . implode(", ", $stmt->errorInfo()) . "<br>";
        }
    }

    public function updateRequestsWithDifferentRefererInDatabase($count) {

        $query = "UPDATE user_activity SET requests_with_different_referer = :count WHERE id = :userId";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':count', $count, PDO::PARAM_INT);
        $stmt->bindParam(':userId', $this->userId);

        if (!$stmt->execute()) {
            echo "Erro ao atualizar requests_with_different_referer: " . implode(", ", $stmt->errorInfo()) . "<br>";
        }
    }

    public function updateRequestsWithoutCookieInDatabase($count) {

        $query = "UPDATE user_activity SET requests_without_cookie = :count WHERE id = :userId";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':count', $count, PDO::PARAM_INT);
        $stmt->bindParam(':userId', $this->userId);

        if (!$stmt->execute()) {
            echo "Erro ao atualizar requests_without_cookie: " . implode(", ", $stmt->errorInfo()) . "<br>";
        }
    }

    public function updateErrorDisplayedInDatabase($status) {

        if ($this->userId === null) {
            echo "userId não está definido.";
            return;
        }

        $query = "UPDATE user_activity SET error_displayed = :status WHERE id = :userId";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':status', $status, PDO::PARAM_BOOL);
        $stmt->bindParam(':userId', $this->userId);

        if (!$stmt->execute()) {
            echo "Erro ao atualizar error_displayed: " . implode(', ', $stmt->errorInfo());
            return false;
        }
        return true;
    }

    public function getErrorDisplayed() {
        $query = "SELECT error_displayed FROM user_activity WHERE id = :userId LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $this->userId);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function setErrorMarkedOnce($markedOnce) {
        if ($this->userId === null) {
            echo "userId não está definido.";
            return;
        }

        $query = "UPDATE user_activity SET error_marked_once = :markedOnce WHERE id = :userId";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':markedOnce', $markedOnce, PDO::PARAM_BOOL);
        $stmt->bindParam(':userId', $this->userId);

        if (!$stmt->execute()) {
            echo "Erro ao atualizar error_marked_once: " . implode(', ', $stmt->errorInfo());
            return false;
        }

        return true;
    }

    public function updateUserCookieId($cookieId, $userId): bool
    {
        if ($userId === null) {
            echo "userId não está definido.";
            return false;
        }

        $query = "UPDATE user_activity SET cookie_id = :cookieId WHERE id = :userId";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':cookieId', $cookieId, PDO::PARAM_STR);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            echo "Erro ao atualizar cookie_id: " . implode(', ', $stmt->errorInfo());
            return false;
        }

        return true;
    }

    public function updateScoreInDatabase($score) {
        if ($this->userId === null) {
            echo "userId não está definido.";
            return;
        }

        $query = "UPDATE user_activity SET score = :score WHERE id = :userId";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':score', $score, PDO::PARAM_INT);
        $stmt->bindParam(':userId', $this->userId);

        if (!$stmt->execute()) {
            echo "Erro ao atualizar score: " . implode(', ', $stmt->errorInfo());
            return false;
        }
        return true;
    }

    public function getIsBlocked() {
        $query = "SELECT is_blocked FROM user_activity WHERE id = :userId LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $this->userId);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function loadSanctionsByUserId($userId)
    {
        $query = "SELECT * FROM sanction WHERE user_activity_id = :userId ORDER BY score_threshold ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addSanction($userId, $scoreThreshold, $sanctionType, $sanctionValue)
    {
        $query = "INSERT INTO sanction (user_activity_id, score_threshold, sanction_type, sanction_value) 
              VALUES (:userId, :scoreThreshold, :sanctionType, :sanctionValue)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $userId);
        $stmt->bindParam(':scoreThreshold', $scoreThreshold);
        $stmt->bindParam(':sanctionType', $sanctionType);
        $stmt->bindParam(':sanctionValue', $sanctionValue);
        return $stmt->execute();
    }

    public function setUserId($userId) {
        $this->userId = $userId;
    }

}