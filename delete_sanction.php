<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require 'db_connection.php';
$database = new Database();
$conn = $database->getConnection();

if (isset($_GET['id'])) {
    $sanctionId = intval($_GET['id']);

    $sql = "DELETE FROM sanction WHERE id = :id";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bindParam(':id', $sanctionId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Sanção excluída com sucesso!";
        } else {
            $_SESSION['message'] = "Erro ao excluir a sanção: " . $stmt->error;
        }

    } else {
        $_SESSION['message'] = "Erro ao preparar a consulta: " . $conn->error;
    }
} else {
    $_SESSION['message'] = "ID da sanção não fornecido.";
}

header("Location: Views/sanction-view.php");
exit();
?>