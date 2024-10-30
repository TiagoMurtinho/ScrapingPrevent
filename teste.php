<?php
require 'db_connection.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "Conexão com o banco de dados bem-sucedida!";
} else {
    echo "Erro ao conectar ao banco de dados.";
}