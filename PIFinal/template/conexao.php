<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "agilestock";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    // Retorna JSON em caso de falha de conexão e encerra
    header('Content-Type: application/json; charset=utf-8');
    die(json_encode([
        'success' => false, 
        'error' => "Erro de conexão com o Banco de Dados: " . $conn->connect_error
    ]));
}