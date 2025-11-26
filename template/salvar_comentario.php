<?php
// OBRIGATÓRIO: Iniciar a sessão
session_start();

// 🛑 Novo: Obter o ID da empresa logada (ou sair se não estiver logado)
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['id_empresa'])) { 
    header('Content-Type: application/json; charset=utf-8');
    die(json_encode(['success' => false, 'error' => 'Acesso negado. Usuário não logado.']));
}
$id_empresa_logada = $_SESSION['id_empresa'];
// 🛑 Fim da verificação 🛑

header('Content-Type: application/json; charset=utf-8');
include 'conexao.php';

// Define o limite máximo de caracteres permitido (Deve ser igual ou menor que o campo no DB)
$LIMITE_CARACTERES = 80; 

$codProdFor = $_POST['CodProdFor_FK'] ?? null;
$comentario = $_POST['comentario'] ?? '';

// 1. Validação inicial de campos vazios
if (!$codProdFor || $comentario === '') {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos. ID do produto e comentário são obrigatórios.']);
    exit;
}

// 2. Validação Silenciosa do Limite de Caracteres (Backend Security Check)
if (mb_strlen($comentario, 'UTF-8') > $LIMITE_CARACTERES) {
    $comentario = mb_substr($comentario, 0, $LIMITE_CARACTERES, 'UTF-8');
}


try {
    // 3. Verifica a conexão com o banco de dados
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Falha na conexão com o banco de dados.");
    }
    
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
        throw new Exception("Acesso negado. O item de estoque não pertence a esta empresa ou não existe.");
    }
    $stmtCheck->close();
    // 🛑 Fim da pré-verificação 🛑


    // 4. Prepara e executa a inserção (agora que o CodProdFor foi validado)
    $stmt = $conn->prepare("INSERT INTO comentarios_estoque (CodProdFor_FK, comentario) VALUES (?, ?)");
    
    if (!$stmt) {
        throw new Exception("Erro de preparação SQL: " . $conn->error);
    }
    
    // i: integer, s: string
    $stmt->bind_param("is", $codProdFor, $comentario); 
    
    if (!$stmt->execute()) {
        throw new Exception("Erro ao inserir comentário: " . $stmt->error);
    }
    $stmt->close();
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// 5. Fecha a conexão
if (isset($conn) && $conn) {
    $conn->close();
}
?>