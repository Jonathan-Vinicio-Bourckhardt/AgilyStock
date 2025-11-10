<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');
include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'error' => 'Método inválido']);
  exit;
}

$tipo = $_POST['tipo'] ?? '';
$formato = $_POST['formato'] ?? '';
$produto = $_POST['produto'] ?? '';
// AGORA RECEBE O CNPJ graças à correção no CadProduto.php
$cnpjFornecedor = $_POST['fornecedor'] ?? ''; 

if ($produto === '' || $cnpjFornecedor === '') {
  echo json_encode(['success' => false, 'error' => 'Campos obrigatórios (Produto e Fornecedor/CNPJ)']);
  exit;
}

// Inicia a transação para garantir que ambas as inserções funcionem ou que ambas falhem
$conn->begin_transaction();

try {
    // 1. INSERIR NA TABELA cadproduto
    // A coluna 'Fornecedor' armazena o CNPJ (VARCHAR) para ser usado no JOIN do histórico.
    $stmtProd = $conn->prepare("INSERT INTO cadproduto (Tipo, Formato, Produto, Fornecedor) VALUES (?, ?, ?, ?)");
    $stmtProd->bind_param("ssss", $tipo, $formato, $produto, $cnpjFornecedor); 
    
    if (!$stmtProd->execute()) {
        throw new Exception("Erro ao inserir Produto (cadproduto): " . $stmtProd->error);
    }
    
    // Obter o CodProduto que acabou de ser inserido
    $codProduto = $conn->insert_id;
    $stmtProd->close();

    // 2. INSERIR NA TABELA DE JUNÇÃO produto_fornecedor
    if ($codProduto === 0) {
        throw new Exception("Falha ao obter o ID do novo produto (CodProduto).");
    }
    
    $stmtJunc = $conn->prepare("INSERT INTO produto_fornecedor (CodProduto_FK, CNPJ_Fornecedor_FK, Formato) VALUES (?, ?, ?)");
    // 'i' para CodProduto, 's' para CNPJ_Fornecedor_FK, 's' para Formato
    $stmtJunc->bind_param("iss", $codProduto, $cnpjFornecedor, $formato); 
    
    if (!$stmtJunc->execute()) {
        throw new Exception("Erro ao inserir na Tabela de Junção (produto_fornecedor): " . $stmtJunc->error);
    }
    
    $stmtJunc->close();
    
    // Se tudo funcionou, finaliza a transação
    $conn->commit();
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Se algo falhar, desfaz as alterações
    $conn->rollback();
    
    // Retorna o erro específico
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>