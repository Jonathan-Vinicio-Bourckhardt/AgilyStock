<?php
// Define o tipo de conteúdo como JSON
header('Content-Type: application/json; charset=utf-8');
session_start();

// Função auxiliar para retornar erro formatado
function sendError($message) {
    die(json_encode(['success' => false, 'error' => $message, 'comentarios' => []]));
}

// 🛑 1. VERIFICAÇÃO DE SESSÃO E LOGIN 🛑
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['id_empresa'])) { 
    sendError('Acesso negado. Usuário não logado.');
}
$id_empresa_logada = $_SESSION['id_empresa'];

// 2. VERIFICAÇÃO DE PARÂMETRO
$codProdFor = $_GET['CodProdFor'] ?? null;

if (!isset($codProdFor) || !is_numeric($codProdFor)) {
    sendError('Código do produto/fornecedor inválido ou ausente.');
}

$codProdFor = (int)$codProdFor;

// 3. CONFIGURAÇÃO E CONEXÃO
include 'conexao.php'; 

try {
    // 4. VERIFICAÇÃO DE CONEXÃO
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Falha na conexão com o banco de dados.");
    }

    // 🛑 5. AÇÃO DE ISOLAMENTO DE DADOS (VERIFICA SE O ITEM PERTENCE À EMPRESA) 🛑
    // Garante que a empresa logada tem permissão para ver os comentários deste item.
    $sqlCheck = "SELECT cp.CodProduto 
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
        throw new Exception("Acesso negado. O item de estoque não pertence a esta empresa ou não existe.");
    }
    $stmtCheck->close();
    // 🛑 Fim da pré-verificação 🛑

    
    // 6. CONSULTA PARA BUSCAR COMENTÁRIOS
    $sql = "
        SELECT 
            c.comentario, 
            DATE_FORMAT(c.data_comentario, '%d/%m/%Y %H:%i') AS data_comentario
        FROM 
            comentarios_estoque c
        WHERE 
            c.CodProdFor_FK = ?
        ORDER BY 
            c.data_comentario DESC;
    ";

    $comentarios = [];

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Erro na preparação da consulta SQL: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $codProdFor); 
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado) {
        while ($linha = $resultado->fetch_assoc()) {
            // Decodifica entidades HTML se houver (o salvamento deve ter usado htmlspecialchars)
            $linha['comentario'] = htmlspecialchars_decode($linha['comentario'], ENT_QUOTES);
            $comentarios[] = $linha; 
        }
    }
    
    $stmt->close();
    
    // 7. SUCESSO
    echo json_encode(['success' => true, 'comentarios' => $comentarios]);

} catch (Exception $e) {
    // 8. TRATAMENTO DE ERRO
    sendError($e->getMessage());
} finally {
    // 9. FECHAMENTO DA CONEXÃO
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>