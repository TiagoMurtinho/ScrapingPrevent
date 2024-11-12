<?php
class Database {
    private $host = 'yourhost';
    private $db_name = 'yourdb';
    private $username = 'youruser';
    private $password = 'yourpass';
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }

    public function createTables() {
        $conn = $this->getConnection();
        try {
            $conn->beginTransaction();

            $sql = "
            -- Creating the `config_type` table
            CREATE TABLE IF NOT EXISTS `config_type` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `config_type` VARCHAR(45) NULL DEFAULT NULL,
                `default_value` TINYINT NULL DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE = InnoDB AUTO_INCREMENT = 10 DEFAULT CHARACTER SET = utf8mb3;

            -- Creating the `penalties` table
            CREATE TABLE IF NOT EXISTS `penalties` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `penalty_type` VARCHAR(45) NULL DEFAULT NULL,
                `default_value` INT NULL DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE = InnoDB AUTO_INCREMENT = 10 DEFAULT CHARACTER SET = utf8mb3;

            -- Creating the `user_activity` table
            CREATE TABLE IF NOT EXISTS `user_activity` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `ip` VARCHAR(45) NOT NULL,
                `cookie_id` VARCHAR(45) NULL DEFAULT NULL,
                `score` INT NULL DEFAULT NULL,
                `requests_without_cookie` INT NULL DEFAULT NULL,
                `requests_with_different_referer` INT NULL DEFAULT NULL,
                `first_request` TINYINT NULL DEFAULT NULL,
                `captcha_attempts` INT NULL DEFAULT '0',
                `error_displayed` TINYINT NULL DEFAULT '0',
                `error_marked_once` TINYINT NULL DEFAULT NULL,
                `is_blocked` TINYINT NULL DEFAULT '0',
                `created_at` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE INDEX `cookie_id_UNIQUE` (`cookie_id` ASC)
            ) ENGINE = InnoDB AUTO_INCREMENT = 82 DEFAULT CHARACTER SET = utf8mb3;

            -- Creating the `sanction` table
            CREATE TABLE IF NOT EXISTS `sanction` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `user_activity_id` INT NOT NULL,
                `score_threshold` INT NOT NULL,
                `sanction_type` ENUM('timeout', 'error', 'block') NOT NULL,
                `sanction_value` INT NULL DEFAULT NULL,
                `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `user_activity_id` (`user_activity_id` ASC),
                CONSTRAINT `sanction_ibfk_1`
                    FOREIGN KEY (`user_activity_id`)
                    REFERENCES `user_activity` (`id`)
                    ON DELETE CASCADE
            ) ENGINE = InnoDB AUTO_INCREMENT = 6 DEFAULT CHARACTER SET = utf8mb3;

            -- Creating the `user_activity_has_config_type` table
            CREATE TABLE IF NOT EXISTS `user_activity_has_config_type` (
                `config_value` TINYINT NULL DEFAULT NULL,
                `penalty_value` INT NULL DEFAULT NULL,
                `user_activity_id` INT NOT NULL,
                `config_type_id` INT NOT NULL,
                `penalties_id` INT NOT NULL,
                PRIMARY KEY (`user_activity_id`, `config_type_id`, `penalties_id`),
                INDEX `fk_user_activity_has_config_type_config_type1_idx` (`config_type_id` ASC),
                INDEX `fk_user_activity_has_config_type_user_activity_idx` (`user_activity_id` ASC),
                INDEX `fk_user_activity_has_config_type_penalties1_idx` (`penalties_id` ASC),
                CONSTRAINT `fk_user_activity_has_config_type_config_type1`
                    FOREIGN KEY (`config_type_id`)
                    REFERENCES `config_type` (`id`),
                CONSTRAINT `fk_user_activity_has_config_type_penalties1`
                    FOREIGN KEY (`penalties_id`)
                    REFERENCES `penalties` (`id`),
                CONSTRAINT `fk_user_activity_has_config_type_user_activity`
                    FOREIGN KEY (`user_activity_id`)
                    REFERENCES `user_activity` (`id`)
            ) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8mb3;
            ";

            $conn->exec($sql);

            $conn->commit();
            echo "Tables created successfully!";
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
        }
    }
}
?>