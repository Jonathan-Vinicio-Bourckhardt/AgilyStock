<?php
// Configuração de Erros
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// OBRIGATÓRIO: Iniciar a sessão
session_start();

header('Content-Type: application/json; charset=utf-8');

function responseError($conn, $msg) {
    if ($conn) {
        // Tenta fechar a conexão, ignorando erros se já estiver fechada
        @$conn->close();
    }
    // Retorna a mensagem de erro formatada em JSON
    die(json_encode(['success' => false, 'error' => $msg]));
}

// 🛑 Novo: Obter o ID da empresa logada (ou sair se não estiver logado)
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['id_empresa'])) { 
    responseError(null, 'Acesso negado. Usuário não logado.');
}
$id_empresa_logada = $_SESSION['id_empresa'];
// 🛑 Fim da verificação 🛑


// 1. Incluir a conexão
include 'conexao.php';

if (!isset($conn) || $conn->connect_error) {
    responseError(null, "Falha na conexão com o banco de dados. Verifique 'conexao.php'.");
}

// 2. Verificar se o método é POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responseError($conn, "Método de requisição inválido.");
}

// 3. Coletar, tratar e validar dados
$codProdFor = $_POST['codProdFor'] ?? null;
$quantidadeInput = $_POST['quantidade'] ?? null;
$valorUnitarioInput = $_POST['valorUnitario'] ?? null;
$devolucao = $_POST['devolucao'] ?? 'Não';
$acao = $_POST['acao'] ?? 'Soma';
$tipo = $_POST['tipo'] ?? null; 

if (empty($codProdFor) || empty($quantidadeInput) || empty($valorUnitarioInput)) {
    responseError($conn, "Os campos CodProdFor, Quantidade e Valor Unitário devem ser preenchidos.");
}

// Tratamento de vírgulas e conversão para float
$quantidade = (float) str_replace(',', '.', $quantidadeInput);
$valorUnitario = (float) str_replace(',', '.', $valorUnitarioInput);

// CÁLCULO: Valor Total da Movimentação
$valorTotalMovimento = $quantidade * $valorUnitario; 

$conn->begin_transaction();

try {
    // 🛑 AÇÃO DE ISOLAMENTO (VERIFICAÇÃO): Checa se o CodProdFor pertence à empresa logada 🛑
    // O CodProdFor aponta para produto_fornecedor, que aponta para cadproduto (onde está o id_empresa)
    $sqlCheck = "SELECT 
                    cp.CodProduto 
                 FROM produto_fornecedor pf 
                 INNER JOIN cadproduto cp ON pf.CodProduto_FK = cp.CodProduto
                 WHERE pf.CodProdFor = ? AND cp.id_empresa = ?";
                 
    $stmtCheck = $conn->prepare($sqlCheck);
    if (!$stmtCheck) {
         throw new Exception("Erro de preparação SQL (Check): " . $conn->error);
    }
    $stmtCheck->bind_param("ii", $codProdFor, $id_empresa_logada);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    
    if ($resultCheck->num_rows === 0) {
        $stmtCheck->close();
        throw new Exception("Acesso negado. O item não pertence a esta empresa ou não existe.");
    }
    $stmtCheck->close();
    // 🛑 Fim da pré-verificação 🛑


    // 4. Inserir na tabela cadmovimento (Histórico)
    // ⚠️ Se a tabela cadmovimento tiver id_empresa, adicione-o aqui também.
    // Assumindo que a estrutura original não tinha, mantemos:
    $sqlMovimento = "INSERT INTO cadmovimento (CodProdFor_FK, Quantidade, ValorUnitario, Devolucao, Acao, Tipo) 
                     VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sqlMovimento);
    if (!$stmt) {
        throw new Exception("Erro de preparação SQL (Movimento): " . $conn->error);
    }
    $stmt->bind_param("idssss", $codProdFor, $quantidade, $valorUnitario, $devolucao, $acao, $tipo);
    
    if (!$stmt->execute()) {
        throw new Exception("Erro ao inserir movimento: " . $stmt->error);
    }
    $stmt->close();
    
    // 5. SELECIONAR DADOS ATUAIS DO ESTOQUE
    // A tabela estoque é referenciada por CodProdFor_FK, que já foi validado.
    $sqlEstoque = "SELECT Quantidade, ValorTotal FROM estoque WHERE CodProdFor_FK = ?";
    $stmtEstoque = $conn->prepare($sqlEstoque);
    if (!$stmtEstoque) {
        throw new Exception("Erro de preparação SQL (Estoque Select): " . $conn->error);
    }
    $stmtEstoque->bind_param("i", $codProdFor);
    $stmtEstoque->execute();
    $resultadoEstoque = $stmtEstoque->get_result();
    
    $estoqueExiste = $resultadoEstoque->num_rows > 0;
    
    $estoqueAtual = 0.0;
    $valorTotalAtual = 0.0; 
    
    if ($estoqueExiste) {
        $row = $resultadoEstoque->fetch_assoc();
        // Trata NULLs no banco como zero para garantir que o cálculo seja numérico
        $estoqueAtual = (float) ($row['Quantidade'] ?? 0.0);
        $valorTotalAtual = (float) ($row['ValorTotal'] ?? 0.0); 
    }
    $stmtEstoque->close();
    
    // =========================================================================
    // 6. CÁLCULO FINAL DE ATUALIZAÇÃO (PERMITINDO VALORES NEGATIVOS)
    // =========================================================================
    
    if ($acao === 'Soma') {
        $novaQuantidade = $estoqueAtual + $quantidade;
        $novoValorTotal = $valorTotalAtual + $valorTotalMovimento;
    } else { // Ação é 'Subtracao'
        // A subtração é aplicada diretamente, permitindo resultado negativo (estoque a descoberto)
        $novaQuantidade = $estoqueAtual - $quantidade;
        $novoValorTotal = $valorTotalAtual - $valorTotalMovimento;
    }


    if ($estoqueExiste) {
        // UPDATE (Atualizar)
        $sqlUpdate = "UPDATE estoque SET Quantidade = ?, ValorTotal = ? WHERE CodProdFor_FK = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        if (!$stmtUpdate) {
            throw new Exception("Erro de preparação SQL (Estoque Update): " . $conn->error);
        }
        $stmtUpdate->bind_param("ddi", $novaQuantidade, $novoValorTotal, $codProdFor);
        
        if (!$stmtUpdate->execute()) {
            throw new Exception("Erro ao atualizar estoque: " . $stmtUpdate->error);
        }
        $stmtUpdate->close();
    } else {
        // INSERT (Inserir novo registro)
        $sqlInsert = "INSERT INTO estoque (CodProdFor_FK, Quantidade, ValorTotal) VALUES (?, ?, ?)";
        $stmtInsert = $conn->prepare($sqlInsert);
        if (!$stmtInsert) {
            throw new Exception("Erro de preparação SQL (Estoque Insert): " . $conn->error);
        }
        $stmtInsert->bind_param("idd", $codProdFor, $novaQuantidade, $novoValorTotal); 
        
        if (!$stmtInsert->execute()) {
            throw new Exception("Erro ao inserir novo estoque: " . $stmtInsert->error);
        }
        $stmtInsert->close();
    }
    
    // 7. Confirma a transação.
    $conn->commit();

    // 8. Resposta de sucesso
    $conn->close();
    echo json_encode(['success' => true, 'message' => 'Quantidade e Valor Total cadastrados com sucesso!']);

} catch (Exception $e) {
    // 9. Em caso de erro, reverte
    $conn->rollback();
    responseError($conn, "Falha na transação: " . $e->getMessage());
}
?>