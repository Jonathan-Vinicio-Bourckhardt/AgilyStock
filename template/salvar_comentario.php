<?php
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
// Se o usuário burlar o JS/HTML e enviar mais de 80 caracteres, o backend apenas impede o salvamento.
if (mb_strlen($comentario, 'UTF-8') > $LIMITE_CARACTERES) {
    // É crucial TRUNCAR o texto para evitar um erro no banco de dados,
    // garantindo que, se o limite for ultrapassado, o que foi digitado
    // será salvo até o limite, sem falhar a requisição.
    $comentario = mb_substr($comentario, 0, $LIMITE_CARACTERES, 'UTF-8');
}


try {
    // 3. Verifica a conexão com o banco de dados
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Falha na conexão com o banco de dados.");
    }
    
    // 4. Prepara e executa a inserção
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