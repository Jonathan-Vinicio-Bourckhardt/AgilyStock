<?php
include 'conexao.php';

// Define o cabeçalho para retorno JSON
header('Content-Type: application/json; charset=utf-8');

// ✅ CORREÇÃO: Verifica se 'CNPJ' ou 'cnpj' estão presentes na requisição GET
$cnpj = $_GET['CNPJ'] ?? $_GET['cnpj'] ?? null;

if (isset($cnpj)) {
    // Inicia a transação
    $conn->begin_transaction();

    try {
        // 1. ENCONTRAR TODOS OS CodProduto VINCULADOS AO FORNECEDOR
        $sqlFindProducts = "SELECT CodProduto FROM cadproduto WHERE Fornecedor = ?";
        $stmtFind = $conn->prepare($sqlFindProducts);
        $stmtFind->bind_param("s", $cnpj);
        $stmtFind->execute();
        $resultProducts = $stmtFind->get_result();
        $stmtFind->close();

        $codProdutosToDelete = [];
        while ($row = $resultProducts->fetch_assoc()) {
            $codProdutosToDelete[] = $row['CodProduto'];
        }

        // Se houver produtos vinculados, executamos a EXCLUSÃO EM CASCATA para cada um
        if (!empty($codProdutosToDelete)) {
            $subqueryCodProdFor = "SELECT CodProdFor FROM produto_fornecedor WHERE CodProduto_FK = ?";

            foreach ($codProdutosToDelete as $codProduto) {
                
                // A. EXCLUIR DE cadmovimento
                $stmtMov = $conn->prepare("DELETE FROM cadmovimento WHERE CodProdFor_FK IN ({$subqueryCodProdFor})");
                $stmtMov->bind_param("i", $codProduto);
                if (!$stmtMov->execute()) {
                    throw new Exception("Erro (P: {$codProduto}) ao excluir movimento: " . $stmtMov->error);
                }
                $stmtMov->close();


                // B. EXCLUIR DE estoque
                $stmtEstoque = $conn->prepare("DELETE FROM estoque WHERE CodProdFor_FK IN ({$subqueryCodProdFor})");
                $stmtEstoque->bind_param("i", $codProduto);
                if (!$stmtEstoque->execute()) {
                    throw new Exception("Erro (P: {$codProduto}) ao excluir do estoque: " . $stmtEstoque->error);
                }
                $stmtEstoque->close();
                

                // C. EXCLUIR DE produto_fornecedor
                $stmtJunc = $conn->prepare("DELETE FROM produto_fornecedor WHERE CodProduto_FK = ?");
                $stmtJunc->bind_param("i", $codProduto);
                if (!$stmtJunc->execute()) {
                    throw new Exception("Erro (P: {$codProduto}) ao excluir de produto_fornecedor: " . $stmtJunc->error);
                }
                $stmtJunc->close();

                
                // D. EXCLUIR DE cadproduto
                $stmtProd = $conn->prepare("DELETE FROM cadproduto WHERE CodProduto = ?");
                $stmtProd->bind_param("i", $codProduto);
                if (!$stmtProd->execute()) {
                    throw new Exception("Erro (P: {$codProduto}) ao excluir produto: " . $stmtProd->error);
                }
                $stmtProd->close();
            }
        }

        // 2. EXCLUIR O FORNECEDOR PRINCIPAL
        $stmtForn = $conn->prepare("DELETE FROM cadfornecedor WHERE CNPJ = ?");
        $stmtForn->bind_param("s", $cnpj);
        if (!$stmtForn->execute()) {
            throw new Exception("Erro ao excluir o fornecedor: " . $stmtForn->error);
        }
        $stmtForn->close();

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
    // Retorna o erro se o CNPJ não for encontrado
    echo json_encode(['success' => false, 'error' => 'CNPJ do Fornecedor não fornecido.']);
}
?>