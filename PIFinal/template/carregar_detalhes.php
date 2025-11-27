<?php
header('Content-Type: application/json; charset=utf-8');
// OBRIGATÓRIO: Iniciar a sessão
session_start();

// 🛑 Novo: Obter o ID da empresa logada (ou sair se não estiver logado)
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['id_empresa'])) { 
    // Em APIs, em vez de redirecionar, retorna um erro de autorização
    echo json_encode(['success' => false, 'error' => 'Acesso negado. Usuário não logado.']);
    exit;
}
$id_empresa_logada = $_SESSION['id_empresa'];
// 🛑 Fim da verificação 🛑

include 'conexao.php';

$codProdFor = $_GET['CodProdFor'] ?? null;

if (!$codProdFor) {
    echo json_encode(['success' => false, 'error' => 'Código do Item não fornecido.']);
    exit;
}

try {
    // 🛑 AÇÃO DE ISOLAMENTO: Adicionar JOIN e WHERE para garantir que o CodProdFor pertença à empresa logada 🛑
    $sql = "SELECT ce.comentario, ce.data_comentario 
            FROM comentarios_estoque ce
            INNER JOIN produto_fornecedor pf ON ce.CodProdFor_FK = pf.CodProdFor
            INNER JOIN cadproduto cp ON pf.CodProduto_FK = cp.CodProduto
            WHERE ce.CodProdFor_FK = ? AND cp.id_empresa = ? 
            ORDER BY ce.data_comentario DESC";

    $stmt = $conn->prepare($sql);
    
    // Agora ligamos dois parâmetros: o código do produto/fornecedor (i) e o ID da empresa (i)
    $stmt->bind_param("ii", $codProdFor, $id_empresa_logada);
    
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $comentarios = [];
    while ($row = $resultado->fetch_assoc()) {
        $comentarios[] = $row;
    }
    
    $stmt->close();
    
    echo json_encode(['success' => true, 'comentarios' => $comentarios]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>