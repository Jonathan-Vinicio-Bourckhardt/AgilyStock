<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

function responseError($conn, $msg) {
    if ($conn && $conn->ping()) {
        $conn->close();
    }
    die(json_encode(['success' => false, 'error' => $msg]));
}

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['id_empresa'])) { 
    responseError(null, 'Acesso negado. Usuário não logado.');
}
$id_empresa_logada = $_SESSION['id_empresa'];

include 'conexao.php';

// ✅ CORREÇÃO APLICADA: Obtém o CNPJ da requisição POST (chave 'cnpj')
$cnpj = $_POST['cnpj'] ?? null;

if (isset($cnpj)) {
    $cnpj = preg_replace('/\D/', '', $cnpj); 

    if (empty($cnpj) || strlen($cnpj) !== 14) {
        responseError($conn, "CNPJ do Fornecedor é inválido ou não fornecido.");
    }
    
    $conn->begin_transaction();

    try {
        // ... (Toda a sua lógica de exclusão em cascata)
        
        // 1. Encontrar e Excluir Produtos Vinculados
        $sqlFindProducts = "SELECT CodProduto FROM cadproduto WHERE Fornecedor = ? AND id_empresa = ?";
        // ... (código para encontrar produtos)
        
        // 2. Excluir o Fornecedor Principal (Com filtro de id_empresa)
        $stmtForn = $conn->prepare("DELETE FROM cadfornecedor WHERE CNPJ = ? AND id_empresa = ?");
        $stmtForn->bind_param("si", $cnpj, $id_empresa_logada);
        if (!$stmtForn->execute()) {
            throw new Exception("Erro ao excluir o fornecedor: " . $stmtForn->error);
        }
        $stmtForn->close();

        // Finaliza a transação
        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Fornecedor e dados vinculados excluídos com sucesso.']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => "Falha na transação de exclusão: " . $e->getMessage()]);
    }

    $conn->close();
} else {
    responseError($conn, 'CNPJ do Fornecedor não fornecido na requisição.');
}
// Não use a tag de fechamento 