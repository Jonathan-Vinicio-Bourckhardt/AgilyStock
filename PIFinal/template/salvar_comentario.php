<?php
// Define o tipo de conteúdo como JSON antes de qualquer saída
header('Content-Type: application/json; charset=utf-8');
session_start();

// Resposta de erro padrão (função auxiliar para evitar repetição)
function sendError($message) {
    die(json_encode(['success' => false, 'error' => $message]));
}

// 🛑 1. VERIFICAÇÃO DE SESSÃO E LOGIN 🛑
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['id_empresa'])) { 
    sendError('Acesso negado. Usuário não logado.');
}
$id_empresa_logada = $_SESSION['id_empresa'];

// 2. CONFIGURAÇÕES E VARIÁVEIS
include 'conexao.php'; // Inclui a conexão
$LIMITE_CARACTERES = 80; 

$codProdFor = $_POST['CodProdFor_FK'] ?? null;
$comentario = $_POST['comentario'] ?? '';

// 3. VALIDAÇÃO DE ENTRADA
if (!$codProdFor || !is_numeric($codProdFor)) {
    sendError('ID do produto inválido ou ausente.');
}

$comentario = trim($comentario);

if ($comentario === '') {
    sendError('O campo comentário é obrigatório.');
}

// 4. VALIDAÇÃO SILENCIOSA DO LIMITE DE CARACTERES
if (mb_strlen($comentario, 'UTF-8') > $LIMITE_CARACTERES) {
    // Trunca a string para o limite, garantindo que não exceda o DB
    $comentario = mb_substr($comentario, 0, $LIMITE_CARACTERES, 'UTF-8'); 
}

// Escape HTML para segurança contra XSS
$comentario = htmlspecialchars($comentario, ENT_QUOTES, 'UTF-8');


try {
    // 5. VERIFICAÇÃO DE CONEXÃO
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Falha na conexão com o banco de dados.");
    }
    
    // 🛑 6. AÇÃO DE ISOLAMENTO DE DADOS (VERIFICA SE O ITEM PERTENCE À EMPRESA) 🛑
    // Esta é a parte mais importante para a segurança.
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


    // 7. INSERÇÃO DO COMENTÁRIO (Após a validação de segurança)
    // ⚠️ Corrigido: Sua tabela 'comentarios_estoque' não tem a coluna 'data_comentario' no INSERT original. 
    // Foi adicionado 'NOW()' para preenchê-la automaticamente, o que é necessário.
    $sqlInsert = "INSERT INTO comentarios_estoque (CodProdFor_FK, comentario, data_comentario) 
                  VALUES (?, ?, NOW())";
    
    $stmt = $conn->prepare($sqlInsert);
    
    if (!$stmt) {
        throw new Exception("Erro de preparação SQL (Insert): " . $conn->error);
    }
    
    $stmt->bind_param("is", $codProdFor, $comentario); 
    
    if (!$stmt->execute()) {
        throw new Exception("Erro ao inserir comentário: " . $stmt->error);
    }
    $stmt->close();
    
    // 8. SUCESSO
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // 9. TRATAMENTO DE ERRO
    sendError($e->getMessage());
} finally {
    // 10. FECHAMENTO DA CONEXÃO
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>