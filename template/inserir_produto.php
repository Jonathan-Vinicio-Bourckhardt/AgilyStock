<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');
include 'conexao.php'; // Garante que a conexão com o banco está ativa

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
    exit;
}

// NOVO: Recebe o Codigo do Produto que agora é manual
$codProduto = $_POST['codProduto'] ?? ''; 
$tipo = $_POST['tipo'] ?? '';
$formato = $_POST['formato'] ?? '';
$produto = $_POST['produto'] ?? '';
$cnpjFornecedor = $_POST['fornecedor'] ?? ''; 

if ($codProduto === '' || $produto === '' || $cnpjFornecedor === '') {
    echo json_encode(['success' => false, 'error' => 'Campos obrigatórios (Código, Produto e Fornecedor/CNPJ)']);
    exit;
}

// Inicia a transação
$conn->begin_transaction();

try {
    // 1. INSERIR NA TABELA cadproduto
    // MODIFICADO: Inclui CodProduto na lista de colunas e valores a serem inseridos
    $stmtProd = $conn->prepare("INSERT INTO cadproduto (CodProduto, Tipo, Formato, Produto, Fornecedor) VALUES (?, ?, ?, ?, ?)");
    // 's' para CodProduto (ajustar para 'i' se o código for estritamente numérico no DB)
    $stmtProd->bind_param("sssss", $codProduto, $tipo, $formato, $produto, $cnpjFornecedor); 
    
    if (!$stmtProd->execute()) {
        // Isso captura erros como 'Duplicate entry' se o código já existir
        throw new Exception("Erro ao inserir Produto (cadproduto): " . $stmtProd->error);
    }
    
    $stmtProd->close();

    // 2. INSERIR NA TABELA DE JUNÇÃO produto_fornecedor
    // O CodProduto agora é a variável $codProduto (recebida via POST), não $conn->insert_id
    
    $stmtJunc = $conn->prepare("INSERT INTO produto_fornecedor (CodProduto_FK, CNPJ_Fornecedor_FK, Formato) VALUES (?, ?, ?)");
    // 's' para CodProduto_FK (ajustar para 'i' se o código for estritamente numérico no DB)
    $stmtJunc->bind_param("sss", $codProduto, $cnpjFornecedor, $formato); 
    
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