<?php
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

include 'conexao.php';

// Define o cabeçalho para retorno JSON
header('Content-Type: application/json; charset=utf-8');

if (isset($_GET['codProduto'])) {
    $codProduto = $_GET['codProduto'];

    // 🛑 AÇÃO DE ISOLAMENTO (PRÉ-VERIFICAÇÃO): Checa se o produto pertence à empresa logada 🛑
    $sqlCheck = "SELECT CodProduto FROM cadproduto WHERE CodProduto = ? AND id_empresa = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("ii", $codProduto, $id_empresa_logada);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    
    if ($resultCheck->num_rows === 0) {
        $stmtCheck->close();
        echo json_encode(['success' => false, 'error' => 'Produto não encontrado ou acesso negado.']);
        $conn->close();
        exit;
    }
    $stmtCheck->close();
    // 🛑 Fim da pré-verificação 🛑
    

    // Inicia a transação
    $conn->begin_transaction();

    try {
        // PREPARAÇÃO: Subquery para obter o CodProdFor para as tabelas filhas (cadmovimento e estoque)
        $subqueryCodProdFor = "SELECT CodProdFor FROM produto_fornecedor WHERE CodProduto_FK = ?";

        
        // 1. EXCLUIR DE cadmovimento (usando o CodProduto que já foi verificado como sendo da empresa)
        $stmtMov = $conn->prepare("DELETE FROM cadmovimento WHERE CodProdFor_FK IN ({$subqueryCodProdFor})");
        $stmtMov->bind_param("i", $codProduto);
        if (!$stmtMov->execute()) {
            throw new Exception("Erro ao excluir o movimento (cadmovimento): " . $stmtMov->error);
        }
        $stmtMov->close();


        // 2. EXCLUIR DE estoque
        $stmtEstoque = $conn->prepare("DELETE FROM estoque WHERE CodProdFor_FK IN ({$subqueryCodProdFor})");
        $stmtEstoque->bind_param("i", $codProduto);
        if (!$stmtEstoque->execute()) {
            throw new Exception("Erro ao excluir do estoque: " . $stmtEstoque->error);
        }
        $stmtEstoque->close();
        
        // 3. EXCLUIR DE produto_fornecedor
        $stmtJunc = $conn->prepare("DELETE FROM produto_fornecedor WHERE CodProduto_FK = ?");
        $stmtJunc->bind_param("i", $codProduto);
        if (!$stmtJunc->execute()) {
            throw new Exception("Erro ao excluir da tabela de junção (produto_fornecedor): " . $stmtJunc->error);
        }
        $stmtJunc->close();

        // 4. EXCLUIR DA TABELA PRINCIPAL (cadproduto)
        // 🛑 AÇÃO DE ISOLAMENTO (FINAL): Adicionar filtro id_empresa 🛑
        $stmtProd = $conn->prepare("DELETE FROM cadproduto WHERE CodProduto = ? AND id_empresa = ?");
        $stmtProd->bind_param("ii", $codProduto, $id_empresa_logada);
        if (!$stmtProd->execute()) {
            throw new Exception("Erro ao excluir o produto (cadproduto): " . $stmtProd->error);
        }
        $stmtProd->close();

        // Finaliza a transação
        $conn->commit();

        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        // Desfaz as alterações e retorna o erro
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }

    $conn->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Código do Produto não fornecido.']);
}
?>