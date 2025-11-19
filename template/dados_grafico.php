<?php
header('Content-Type: application/json; charset=utf-8');
include 'conexao.php';

// Consulta SQL para somar o ValorTotal AGRUPADO por Tipo
$sql = "SELECT 
            cp.Tipo, 
            SUM(e.ValorTotal) AS ValorPorTipo 
        FROM estoque e
        INNER JOIN produto_fornecedor pf ON e.CodProdFor_FK = pf.CodProdFor
        INNER JOIN cadproduto cp ON pf.CodProduto_FK = cp.CodProduto
        GROUP BY cp.Tipo
        HAVING SUM(e.ValorTotal) > 0"; // Garante que só categorias com valor em estoque apareçam

$resultado = $conn->query($sql);

$dados = [
    'labels' => [], // Nomes das categorias (ex: Fruta, Verdura)
    'data' => []    // Valores totais (ex: 150.00, 75.50)
];

if ($resultado && $resultado->num_rows > 0) {
    while ($linha = $resultado->fetch_assoc()) {
        $dados['labels'][] = ucfirst($linha['Tipo']); // Formata o nome da categoria
        $dados['data'][] = (float) $linha['ValorPorTipo']; // Converte para float
    }
}

// Retorna os dados em JSON
echo json_encode(['success' => true, 'dados' => $dados]);

$conn->close();
?>