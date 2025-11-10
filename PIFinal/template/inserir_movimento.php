<?php
error_reporting(E_ALL);
ini_set('display_errors', 1); 

header('Content-Type: application/json; charset=utf-8');
include 'conexao.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'error' => 'Método inválido.']);
  exit;
}

$devolucao = $_POST['devolucao'] ?? '';
$acao = $_POST['acao'] ?? '';
// O campo 'tipo' (Fruta, Verdura, etc.) foi corrigido para ser obtido do JS no front-end
$tipo = $_POST['tipo'] ?? ''; 
$codProdForFK = $_POST['codProdFor'] ?? ''; // PK da tabela produto_fornecedor
$quantidade = floatval($_POST['quantidade'] ?? 0);
$valorUnitario = floatval($_POST['valorUnitario'] ?? 0); 
$valorTotalMovimento = $quantidade * $valorUnitario;

if (empty($codProdForFK) || $quantidade <= 0 || $valorUnitario <= 0 || empty($tipo)) {
  echo json_encode(['success' => false, 'error' => 'Dados incompletos ou inválidos recebidos (Qtd/Valor > 0).']);
  exit;
}

// -------------------------------------------------------------
// INÍCIO DA LÓGICA DE TRANSAÇÃO (Garanta que as duas inserções funcionem ou que ambas falhem)
// -------------------------------------------------------------
$conn->begin_transaction();

try {
    // 1. INSERÇÃO NA TABELA cadmovimento (HISTÓRICO)
    $stmtMov = $conn->prepare("INSERT INTO cadmovimento (Devolucao, Acao, Tipo, CodProdFor_FK, Quantidade, ValorUnitario) VALUES (?, ?, ?, ?, ?, ?)");
    // String de tipos: 3 Strings (Devolucao, Acao, Tipo), 1 Integer (CodProdFor_FK), 2 Decimals (Quantidade, ValorUnitario)
    $stmtMov->bind_param("sssidd", $devolucao, $acao, $tipo, $codProdForFK, $quantidade, $valorUnitario); 
    
    if (!$stmtMov->execute()) {
        throw new Exception("Erro ao inserir Histórico (cadmovimento): " . $stmtMov->error);
    }
    $stmtMov->close();

    // 2. LÓGICA PARA ATUALIZAR/INSERIR NA TABELA ESTOQUE (SALDO ATUAL)

    // A. Buscar saldo atual (se existir)
    $stmtEstoque = $conn->prepare("SELECT Quantidade, ValorTotal FROM estoque WHERE CodProdFor_FK = ?");
    $stmtEstoque->bind_param("i", $codProdForFK);
    $stmtEstoque->execute();
    $resEstoque = $stmtEstoque->get_result();
    $saldoAtual = $resEstoque->fetch_assoc();
    $stmtEstoque->close();
    
    $qtdAtual = floatval($saldoAtual['Quantidade'] ?? 0);
    $valorTotalAtual = floatval($saldoAtual['ValorTotal'] ?? 0);
    
    // B. Calcular novo saldo
    if ($acao === 'Soma') {
        $novaQtd = $qtdAtual + $quantidade;
        $novoValorTotal = $valorTotalAtual + $valorTotalMovimento;
    } else { // Ação é 'Subtracao'
        $novaQtd = $qtdAtual - $quantidade;
        $novoValorTotal = $valorTotalAtual - $valorTotalMovimento;
        
        // Proteção contra estoque negativo (opcional, mas recomendado)
        if ($novaQtd < 0) {
             throw new Exception("A subtração resultaria em estoque negativo. Estoque atual: " . $qtdAtual);
        }
    }
    
    // C. INSERIR ou ATUALIZAR
    if ($saldoAtual) {
        // ATUALIZA: O registro já existe, então faz UPDATE
        $stmtUpdate = $conn->prepare("UPDATE estoque SET Quantidade = ?, ValorTotal = ? WHERE CodProdFor_FK = ?");
        $stmtUpdate->bind_param("ddi", $novaQtd, $novoValorTotal, $codProdForFK);
        
        if (!$stmtUpdate->execute()) {
            throw new Exception("Erro ao atualizar Estoque: " . $stmtUpdate->error);
        }
        $stmtUpdate->close();
        
    } else {
        // INSERE: O registro não existe (primeira entrada), então faz INSERT
        $stmtInsert = $conn->prepare("INSERT INTO estoque (CodProdFor_FK, Quantidade, ValorTotal) VALUES (?, ?, ?)");
        $stmtInsert->bind_param("idd", $codProdForFK, $novaQtd, $novoValorTotal);
        
        if (!$stmtInsert->execute()) {
            throw new Exception("Erro ao inserir Estoque: " . $stmtInsert->error);
        }
        $stmtInsert->close();
    }
    
    // Se tudo funcionou, finaliza a transação
    $conn->commit();
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Se algo falhar, desfaz as alterações (rollback)
    $conn->rollback();
    
    // Retorna o erro específico
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>