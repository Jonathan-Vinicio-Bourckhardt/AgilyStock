<?php
// Configurações para exibir erros (apenas para debug)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// OBRIGATÓRIO: Iniciar a sessão
session_start();

// 🛑 Novo: Obter o ID da empresa logada (ou sair se não estiver logado)
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['id_empresa'])) { 
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Acesso negado. Usuário não logado.']);
    exit;
}
$id_empresa_logada = $_SESSION['id_empresa'];
// 🛑 Fim da verificação 🛑

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
    // 🛑 AÇÃO DE ISOLAMENTO: Incluir id_empresa no INSERT 🛑
    $stmt = $conn->prepare("INSERT INTO cadfornecedor (CNPJ, Fornecedor, NumContato, id_empresa) VALUES (?, ?, ?, ?)");
    
    // Agora ligamos quatro parâmetros: (CNPJ, Fornecedor, NumContato) (sss) e (id_empresa) (i)
    $stmt->bind_param("sssi", $CNPJ, $Fornecedor, $NumContato, $id_empresa_logada);
    
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