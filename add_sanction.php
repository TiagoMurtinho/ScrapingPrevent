<?php
require 'config.php';

$userActivity = new UserActivity();

$userId = $_POST['user_id'];
$scoreThreshold = $_POST['score_threshold'];
$sanctionType = $_POST['sanction_type'];
$sanctionValue = !empty($_POST['sanction_value']) ? intval($_POST['sanction_value']) : null;

if ($userId && $scoreThreshold && $sanctionType) {
    $success = $userActivity->addSanction($userId, $scoreThreshold, $sanctionType, $sanctionValue);
    if ($success) {
        header("Location: Views/sanction-view.php?user_id=$userId");
        exit;
    } else {
        echo "Erro ao adicionar sanção. Por favor, tente novamente.";
    }
} else {
    echo "Campos obrigatórios não preenchidos.";
}
?>