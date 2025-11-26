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

// Consulta SQL para somar o ValorTotal AGRUPADO por Tipo
// 🛑 AÇÃO DE ISOLAMENTO: Adicionar o filtro id_empresa 🛑
$sql = "SELECT 
            cp.Tipo, 
            SUM(e.ValorTotal) AS ValorPorTipo 
        FROM estoque e
        INNER JOIN produto_fornecedor pf ON e.CodProdFor_FK = pf.CodProdFor
        INNER JOIN cadproduto cp ON pf.CodProduto_FK = cp.CodProduto
        WHERE cp.id_empresa = ?  -- FILTRO DE ISOLAMENTO
        GROUP BY cp.Tipo
        HAVING SUM(e.ValorTotal) > 0"; // Garante que só categorias com valor em estoque apareçam

$dados = [
    'labels' => [], // Nomes das categorias (ex: Fruta, Verdura)
    'data' => []    // Valores totais (ex: 150.00, 75.50)
];

try {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_empresa_logada); // Liga o ID da empresa
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado && $resultado->num_rows > 0) {
        while ($linha = $resultado->fetch_assoc()) {
            $dados['labels'][] = ucfirst($linha['Tipo']); // Formata o nome da categoria
            $dados['data'][] = (float) $linha['ValorPorTipo']; // Converte para float
        }
    }
    
    $stmt->close();
    
    // Retorna os dados em JSON
    echo json_encode(['success' => true, 'dados' => $dados]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>