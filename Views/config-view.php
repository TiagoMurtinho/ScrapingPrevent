<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../db_connection.php';
$database = new Database();
$conn = $database->getConnection();

session_start();

$cookie_id = $_COOKIE['user_identification'] ?? null;

$config = [];

if ($cookie_id) {

    $query = "SELECT id FROM user_activity WHERE cookie_id = :cookie_id LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':cookie_id', $cookie_id);
    $stmt->execute();
    $user_activity = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_activity) {
        $user_activity_id = $user_activity['id'];

        $query = "SELECT config_type_id, config_value, penalty_value FROM user_activity_has_config_type WHERE user_activity_id = :user_activity_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_activity_id', $user_activity_id);
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
                default:
                    break;
            }
        }
    }
} else {
    $config = [
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
        'honey_pot_penalty' => 50,
        'captcha_penalty' => 10,
        'blacklist_penalty' => 100,
        'aws_penalty' => 25,
        'cookie_id_penalty' => 25,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $config_updates = [
        'js_enabled' => isset($_POST['js_enabled']) ? 1 : 0,
        'user_agent_blocker' => isset($_POST['user_agent_blocker']) ? 1 : 0,
        'referer_checker' => isset($_POST['referer_checker']) ? 1 : 0,
        'rate_limiter' => isset($_POST['rate_limiter']) ? 1 : 0,
        'honey_pot' => isset($_POST['honey_pot']) ? 1 : 0,
        'captcha' => isset($_POST['captcha']) ? 1 : 0,
        'blacklist_checker' => isset($_POST['blacklist_checker']) ? 1 : 0,
        'aws_ip_checker' => isset($_POST['aws_ip_checker']) ? 1 : 0,
        'cookie_id_enabled' => isset($_POST['cookie_id_enabled']) ? 1 : 0,
    ];

    $penalty_updates = [
        'js_penalty' => isset($_POST['js_penalty']) ? (int)$_POST['js_penalty'] : 25,
        'user_agent_penalty' => isset($_POST['user_agent_penalty']) ? (int)$_POST['user_agent_penalty'] : 25,
        'referer_penalty' => isset($_POST['referer_penalty']) ? (int)$_POST['referer_penalty'] : 25,
        'rate_penalty' => isset($_POST['rate_penalty']) ? (int)$_POST['rate_penalty'] : 25,
        'honey_pot_penalty' => isset($_POST['honey_pot_penalty']) ? (int)$_POST['honey_pot_penalty'] : 50,
        'captcha_penalty' => isset($_POST['captcha_penalty']) ? (int)$_POST['captcha_penalty'] : 10,
        'blacklist_penalty' => isset($_POST['blacklist_penalty']) ? (int)$_POST['blacklist_penalty'] : 100,
        'aws_penalty' => isset($_POST['aws_penalty']) ? (int)$_POST['aws_penalty'] : 25,
        'cookie_id_penalty' => isset($_POST['cookie_id_penalty']) ? (int)$_POST['cookie_id_penalty'] : 25,
    ];

    if ($cookie_id) {
        $query = "SELECT id FROM user_activity WHERE cookie_id = :cookie_id LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':cookie_id', $cookie_id);
        $stmt->execute();
        $user_activity = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user_activity) {
            $user_activity_id = $user_activity['id'];

            $config_type_mapping = [
                'js_enabled' => 1,
                'user_agent_blocker' => 2,
                'referer_checker' => 3,
                'rate_limiter' => 4,
                'honey_pot' => 5,
                'captcha' => 6,
                'blacklist_checker' => 7,
                'aws_ip_checker' => 8,
                'cookie_id_enabled' => 9
            ];

            $penalty_mapping = [
                'js_enabled' => 'js_penalty',
                'user_agent_blocker' => 'user_agent_penalty',
                'referer_checker' => 'referer_penalty',
                'rate_limiter' => 'rate_penalty',
                'honey_pot' => 'honey_pot_penalty',
                'captcha' => 'captcha_penalty',
                'blacklist_checker' => 'blacklist_penalty',
                'aws_ip_checker' => 'aws_penalty',
                'cookie_id_enabled' => 'cookie_id_penalty',
            ];

            foreach ($config_updates as $config_type => $config_value) {
                $penalty_key = $penalty_mapping[$config_type] ?? null;
                $penalty_value = $penalty_key ? $penalty_updates[$penalty_key] : 0;

                $penalty_value = max(0, min(255, $penalty_value));

                $config_type_id = $config_type_mapping[$config_type] ?? null;

                if ($config_type_id !== null) {
                    $query = "UPDATE user_activity_has_config_type 
                              SET config_value = :config_value, 
                                  penalty_value = :penalty_value 
                              WHERE user_activity_id = :user_activity_id 
                              AND config_type_id = :config_type_id";

                    $stmt = $conn->prepare($query);
                    $stmt->execute([
                        ':config_value' => $config_value,
                        ':penalty_value' => $penalty_value,
                        ':user_activity_id' => $user_activity_id,
                        ':config_type_id' => $config_type_id,
                    ]);
                }
            }

            $successMessage = "Configurações salvas e atualizadas com sucesso.";
        } else {
            $successMessage = "Nenhuma atividade de usuário encontrada para este cookie.";
        }
    } else {
        $successMessage = "Nenhum cookie_id encontrado.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Configuração de Anti-Scraping</title>
    <link rel="stylesheet" href="../css/CSS.css">
</head>
<body>
<?php require '../nav.php'; ?>

<div class="config-container">
    <h1 class="config-title">Configuração de Anti-Scraping</h1>

    <?php if (isset($successMessage)): ?>
        <p class="success-message"><?php echo $successMessage; ?></p>
    <?php endif; ?>

    <form class="config-form" method="post">
        <div class="config-form-row">
            <div class="config-form-group">
                <label class="config-form-label">
                    <input type="checkbox" name="js_enabled" <?php echo $config['js_enabled'] ? 'checked' : ''; ?> class="config-form-checkbox">
                    <span>Ativar verificação de JavaScript</span>
                </label>
            </div>
            <div class="config-form-group">
                <label class="config-form-label">
                    <input type="number" name="js_penalty" value="<?php echo $config['js_penalty'] ?? 25; ?>" min="0" class="config-form-input">
                    <span class="config-span">Pontuação para JavaScript</span>
                </label>
            </div>
        </div>

        <div class="config-form-row">
            <div class="config-form-group">
                <label class="config-form-label">
                    <input type="checkbox" name="user_agent_blocker" <?php echo $config['user_agent_blocker'] ? 'checked' : ''; ?> class="config-form-checkbox">
                    <span>Ativar bloqueio de User-Agent</span>
                </label>
            </div>
            <div class="config-form-group">
                <label class="config-form-label">
                    <input type="number" name="user_agent_penalty" value="<?php echo $config['user_agent_penalty'] ?? 25; ?>" min="0" class="config-form-input">
                    <span class="config-span">Pontuação para User-Agent</span>
                </label>
            </div>
        </div>

        <div class="config-form-row">
            <div class="config-form-group">
                <label class="config-form-label">
                    <input type="checkbox" name="referer_checker" <?php echo $config['referer_checker'] ? 'checked' : ''; ?> class="config-form-checkbox">
                    <span>Ativar verificação de Referer</span>
                </label>
            </div>

            <div class="config-form-group">
                <label class="config-form-label">
                    <input type="number" name="referer_penalty" value="<?php echo $config['referer_penalty'] ?? 25; ?>" min="0" class="config-form-input">
                    <span class="config-span">Pontuação para Referer</span>
                </label>
            </div>
        </div>

        <div class="config-form-row">
            <div class="config-form-group">
                <label class="config-form-label">
                    <input type="checkbox" name="rate_limiter" <?php echo $config['rate_limiter'] ? 'checked' : ''; ?> class="config-form-checkbox">
                    <span>Ativar limitador de taxa</span>
                </label>
            </div>
            <div class="config-form-group">
                <label class="config-form-label">
                    <input type="number" name="rate_penalty" value="<?php echo $config['rate_penalty'] ?? 25; ?>" min="0" class="config-form-input">
                    <span class="config-span">Pontuação para limitador</span>
                </label>
            </div>
        </div>

        <div class="config-form-row">
            <div class="config-form-group">
                <label class="config-form-label">
                    <input type="checkbox" name="honey_pot" <?php echo $config['honey_pot'] ? 'checked' : ''; ?> class="config-form-checkbox">
                    <span>Ativar HoneyPot</span>
                </label>
            </div>
            <div class="config-form-group">
                <label class="config-form-label">
                    <input type="number" name="honey_pot_penalty" value="<?php echo $config['honey_pot_penalty'] ?? 50; ?>" min="0" class="config-form-input">
                    <span class="config-span">Pontuação para HoneyPot</span>
                </label>
            </div>

        </div>

        <div class="config-form-row">
            <div class="config-form-group">
                <label class="config-form-label">
                    <input type="checkbox" name="captcha" <?php echo $config['captcha'] ? 'checked' : ''; ?> class="config-form-checkbox">
                    <span>Ativar CAPTCHA no formulário</span>
                </label>
            </div>
            <div class="config-form-group">
                <label class="config-form-label">
                    <input type="number" name="captcha_penalty" value="<?php echo $config['captcha_penalty'] ?? 10; ?>" min="0" class="config-form-input">
                    <span class="config-span">Pontuação para CAPTCHA</span>
                </label>
            </div>
        </div>

        <div class="config-form-row">
            <div class="config-form-group">
                <label class="config-form-label">
                    <input type="checkbox" name="blacklist_checker" <?php echo $config['blacklist_checker'] ? 'checked' : ''; ?> class="config-form-checkbox">
                    <span>Ativar Blacklist Checker</span>
                </label>
            </div>
            <div class="config-form-group">
                <label class="config-form-label">
                    <input type="number" name="blacklist_penalty" value="<?php echo $config['blacklist_penalty'] ?? 100; ?>" min="0" class="config-form-input">
                    <span class="config-span">Pontuação para Blacklist Checker</span>
                </label>
            </div>
        </div>

        <div class="config-form-row">
            <div class="config-form-group">
                <label class="config-form-label">
                    <input type="checkbox" name="aws_ip_checker" <?php echo $config['aws_ip_checker'] ? 'checked' : ''; ?> class="config-form-checkbox">
                    <span>Ativar AWS IP Checker</span>
                </label>
            </div>
            <div class="config-form-group">
                <label class="config-form-label">
                    <input type="number" name="aws_penalty" value="<?php echo $config['aws_penalty'] ?? 25; ?>" min="0" class="config-form-input">
                    <span class="config-span">Pontuação para AWS IP Checker</span>
                </label>
            </div>
        </div>

        <div class="config-form-row">
            <div class="config-form-group">
                <label class="config-form-label">
                    <input type="checkbox" name="cookie_id_enabled" <?php echo $config['cookie_id_enabled'] ? 'checked' : ''; ?> class="config-form-checkbox">
                    <span>Ativar verificação de Cookie ID</span>
                </label>
            </div>
            <div class="config-form-group">
                <label class="config-form-label">
                    <input type="number" name="cookie_id_penalty" value="<?php echo $config['cookie_id_penalty'] ?? 25; ?>" min="0" class="config-form-input">
                    <span class="config-span">Pontuação para Cookie ID</span>
                </label>
            </div>
        </div>

        <input type="submit" value="Salvar Configurações" class="submit-button">
    </form>
</div>

<script src="../js/custom.js"></script>
</body>
</html>