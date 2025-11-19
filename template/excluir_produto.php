<?php
include 'conexao.php';

// Define o cabeçalho para retorno JSON
header('Content-Type: application/json; charset=utf-8');

if (isset($_GET['codProduto'])) {
    $codProduto = $_GET['codProduto'];

    // Inicia a transação
    $conn->begin_transaction();

    try {
        // PREPARAÇÃO: Subquery para obter o CodProdFor para as tabelas filhas (cadmovimento e estoque)
        // A tabela produto_fornecedor pode ter múltiplos CodProdFor para o mesmo CodProduto_FK (se Formato fosse diferente),
        // mas é o elo necessário para as tabelas estoque e cadmovimento.
        $subqueryCodProdFor = "SELECT CodProdFor FROM produto_fornecedor WHERE CodProduto_FK = ?";

        
        // 1. EXCLUIR DA TABELA cadmovimento (Tabela neta que causa o erro de FK)
        $stmtMov = $conn->prepare("DELETE FROM cadmovimento WHERE CodProdFor_FK IN ({$subqueryCodProdFor})");
        $stmtMov->bind_param("i", $codProduto);
        if (!$stmtMov->execute()) {
            throw new Exception("Erro ao excluir o movimento (cadmovimento): " . $stmtMov->error);
        }
        $stmtMov->close();


        // 2. EXCLUIR DA TABELA estoque (NOVA REGRA DO USUÁRIO)
        $stmtEstoque = $conn->prepare("DELETE FROM estoque WHERE CodProdFor_FK IN ({$subqueryCodProdFor})");
        $stmtEstoque->bind_param("i", $codProduto);
        if (!$stmtEstoque->execute()) {
            throw new Exception("Erro ao excluir do estoque: " . $stmtEstoque->error);
        }
        $stmtEstoque->close();


        // 3. EXCLUIR DA TABELA DE JUNÇÃO (produto_fornecedor)
        $stmtJunc = $conn->prepare("DELETE FROM produto_fornecedor WHERE CodProduto_FK = ?");
        $stmtJunc->bind_param("i", $codProduto);
        if (!$stmtJunc->execute()) {
            throw new Exception("Erro ao excluir da tabela de junção (produto_fornecedor): " . $stmtJunc->error);
        }
        $stmtJunc->close();

        // 4. EXCLUIR DA TABELA PRINCIPAL (cadproduto)
        $stmtProd = $conn->prepare("DELETE FROM cadproduto WHERE CodProduto = ?");
        $stmtProd->bind_param("i", $codProduto);
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