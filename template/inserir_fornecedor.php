<?php
// Configurações para exibir erros (apenas para debug)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. GARANTE QUE A RESPOSTA SEJA JSON
header('Content-Type: application/json; charset=utf-8'); 
include 'conexao.php'; 

// Verifica a conexão
if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Falha na conexão com o banco de dados.']);
    exit;
}

// Verifica se o método é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'error' => 'Método inválido']);
  exit;
}

// Limpa e pega os dados
$CNPJ = $_POST['CNPJ'] ?? '';
$Fornecedor = $_POST['Fornecedor'] ?? '';
$NumContato = $_POST['NumContato'] ?? '';

if ($CNPJ === '' || $Fornecedor === '' || $NumContato === '') {
    echo json_encode(['success' => false, 'error' => 'Todos os campos são obrigatórios.']);
    exit;
}

try {
    // Usando Prepared Statements para segurança e evitar SQL Injection
    $stmt = $conn->prepare("INSERT INTO cadfornecedor (CNPJ, Fornecedor, NumContato) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $CNPJ, $Fornecedor, $NumContato);
    
    if (!$stmt->execute()) {
        // Se a execução falhar (ex: CNPJ duplicado), retorna o erro do banco
        throw new Exception("Erro ao cadastrar: " . $stmt->error);
    }
    $stmt->close();
    
    // ✅ RETORNA SUCESSO EM JSON (SEM REDIRECIONAR)
    echo json_encode(['success' => true]); 
    
} catch (Exception $e) {
    // ❌ RETORNA ERRO EM JSON
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>