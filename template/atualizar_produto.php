<?php
// OBRIGATÓRIO: Iniciar a sessão para que $_SESSION['id_empresa'] funcione
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

// Função de saída de erro, garantindo retorno JSON em caso de falha.
function responseError($conn, $msg) {
    if ($conn && $conn->in_transaction) {
        $conn->rollback();
    }
    if ($conn) {
        $conn->close();
    }
    die(json_encode(['success' => false, 'error' => $msg]));
}

// 🛑 VERIFICAÇÃO DE SESSÃO E ID DE EMPRESA 🛑
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['id_empresa'])) {
    responseError(null, "Acesso negado. O usuário deve estar logado.");
}
$id_empresa_logada = $_SESSION['id_empresa'];
// 🛑 FIM DA VERIFICAÇÃO 🛑

include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responseError($conn, 'Método de requisição inválido.');
}

// 1. CAPTURA DOS DADOS (usando os nomes enviados pelo JS)
$codProdutoAntigo = $_POST['CodProdutoAntigo'] ?? ''; 
$tipo = $_POST['Tipo'] ?? '';
$formato = $_POST['Formato'] ?? '';
$produto = $_POST['Produto'] ?? '';
$cnpjFornecedor = $_POST['Fornecedor'] ?? ''; // Valor novo/atual do fornecedor

// 2. VALIDAÇÃO MÍNIMA
if (empty($codProdutoAntigo) || empty($tipo) || empty($formato) || empty($produto) || empty($cnpjFornecedor)) {
    responseError($conn, 'Todos os campos são obrigatórios.');
}

$conn->begin_transaction();

try {
    // 3. ATUALIZAR NA TABELA cadproduto
    // 🛑 CORREÇÃO: Adicionando filtro AND id_empresa = ? 🛑
    $sqlProd = "UPDATE cadproduto SET 
                    Tipo = ?, 
                    Formato = ?, 
                    Produto = ?, 
                    Fornecedor = ? 
                WHERE CodProduto = ? AND id_empresa = ?";

    $stmtProd = $conn->prepare($sqlProd);
    // Bind: 4 strings (ssss), 1 string (s) do CodProduto, 1 inteiro (i) do id_empresa
    $stmtProd->bind_param("sssssi", $tipo, $formato, $produto, $cnpjFornecedor, $codProdutoAntigo, $id_empresa_logada); 
    
    if (!$stmtProd->execute()) {
        throw new Exception("Erro ao atualizar Produto (cadproduto): " . $stmtProd->error);
    }
    $affected_rows_prod = $stmtProd->affected_rows;
    $stmtProd->close();

    // 4. ATUALIZAR NA TABELA DE JUNÇÃO produto_fornecedor
    // 🛑 CORREÇÃO: Adicionando filtro AND id_empresa = ? 🛑
    $sqlJunc = "UPDATE produto_fornecedor SET 
                    CNPJ_Fornecedor_FK = ?, 
                    Formato = ? 
                WHERE CodProduto_FK = ? AND id_empresa = ?";
    
    $stmtJunc = $conn->prepare($sqlJunc);
    // Bind: 2 strings (ss), 1 string (s) do CodProduto_FK, 1 inteiro (i) do id_empresa
    $stmtJunc->bind_param("sssi", $cnpjFornecedor, $formato, $codProdutoAntigo, $id_empresa_logada); 
    
    if (!$stmtJunc->execute()) {
        throw new Exception("Erro ao atualizar Tabela de Junção (produto_fornecedor): " . $stmtJunc->error);
    }
    $affected_rows_junc = $stmtJunc->affected_rows;
    $stmtJunc->close();
    
    // Verificação opcional: Se nenhum registro foi afetado, pode ser porque o produto não existe OU 
    // porque o registro não pertence à empresa logada, o que é seguro, mas pode gerar um aviso na interface.
    if ($affected_rows_prod === 0 && $affected_rows_junc === 0) {
         // O commit é seguro aqui pois mesmo 0 linhas afetadas é uma operação bem sucedida
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Nenhuma alteração detectada ou produto não encontrado/pertencente à sua conta.']);
        $conn->close();
        exit;
    }

    $conn->commit();
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>