<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');
include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido.']);
    exit;
}

// 1. CAPTURA DOS DADOS (usando os nomes enviados pelo JS)
$codProdutoAntigo = $_POST['CodProdutoAntigo'] ?? ''; 
$tipo = $_POST['Tipo'] ?? '';
$formato = $_POST['Formato'] ?? '';
$produto = $_POST['Produto'] ?? '';
$cnpjFornecedor = $_POST['Fornecedor'] ?? ''; // Valor novo/atual do fornecedor

// 2. VALIDAÇÃO MÍNIMA
if (empty($codProdutoAntigo) || empty($tipo) || empty($formato) || empty($produto) || empty($cnpjFornecedor)) {
    echo json_encode(['success' => false, 'error' => 'Todos os campos são obrigatórios.']);
    exit;
}

$conn->begin_transaction();

try {
    // 3. ATUALIZAR NA TABELA cadproduto
    // Atualiza todos os campos, pois os valores foram enviados pelo JavaScript, 
    // garantindo a consistência, mesmo que só o Fornecedor tenha sido visivelmente editado.
    $sqlProd = "UPDATE cadproduto SET 
                    Tipo = ?, 
                    Formato = ?, 
                    Produto = ?, 
                    Fornecedor = ? 
                WHERE CodProduto = ?";

    $stmtProd = $conn->prepare($sqlProd);
    $stmtProd->bind_param("sssss", $tipo, $formato, $produto, $cnpjFornecedor, $codProdutoAntigo); 
    
    if (!$stmtProd->execute()) {
        throw new Exception("Erro ao atualizar Produto (cadproduto): " . $stmtProd->error);
    }
    $stmtProd->close();

    // 4. ATUALIZAR NA TABELA DE JUNÇÃO produto_fornecedor
    $sqlJunc = "UPDATE produto_fornecedor SET 
                    CNPJ_Fornecedor_FK = ?, 
                    Formato = ? 
                WHERE CodProduto_FK = ?";
    
    $stmtJunc = $conn->prepare($sqlJunc);
    $stmtJunc->bind_param("sss", $cnpjFornecedor, $formato, $codProdutoAntigo); 
    
    if (!$stmtJunc->execute()) {
        throw new Exception("Erro ao atualizar Tabela de Junção (produto_fornecedor): " . $stmtJunc->error);
    }
    $stmtJunc->close();
    
    $conn->commit();
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>