<?php
// Define o tipo de conteúdo como JSON
header('Content-Type: application/json; charset=utf-8');
session_start();

// Saída de Erro Padrão garantida para ser JSON
$response_default_error = [
    'success' => false,
    'error' => 'Erro interno na API do gráfico.',
    'dados' => ['labels' => [], 'data' => []]
];

// 1. Verificar Login e Obter ID da Empresa
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['id_empresa'])) { 
    $response_default_error['error'] = 'Acesso negado. Usuário não logado.';
    echo json_encode($response_default_error);
    exit;
}
$id_empresa_logada = $_SESSION['id_empresa'];

// --- Configuração e Conexão ---
include 'conexao.php';

// 2. Verificar Conexão
if (!isset($conn) || $conn->connect_error) {
    $response_default_error['error'] = 'Falha na conexão com o banco de dados: ' . ($conn->connect_error ?? 'Conexão não definida.');
    echo json_encode($response_default_error);
    exit;
}

// 3. Consulta SQL
$sqlGrafico = "
    SELECT 
        cp.Tipo, 
        SUM(e.ValorTotal) AS TotalPorTipo
    FROM 
        estoque e
    INNER JOIN 
        produto_fornecedor pf ON e.CodProdFor_FK = pf.CodProdFor
    INNER JOIN 
        cadproduto cp ON pf.CodProduto_FK = cp.CodProduto
    WHERE 
        cp.id_empresa = ? 
    GROUP BY 
        cp.Tipo
    HAVING 
        SUM(e.ValorTotal) > 0 
    ORDER BY 
        TotalPorTipo DESC;
";

$dados = ['labels' => [], 'data' => []];

try {
    $stmt = $conn->prepare($sqlGrafico);
    
    if ($stmt === false) {
        throw new Exception('Erro na preparação da consulta SQL: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $id_empresa_logada); 
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado && $resultado->num_rows > 0) {
        while ($linha = $resultado->fetch_assoc()) {
            $dados['labels'][] = ucfirst($linha['Tipo']); 
            $dados['data'][] = (float) $linha['TotalPorTipo']; 
        }
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode(['success' => true, 'dados' => $dados]);

} catch (Exception $e) {
    $response_default_error['error'] = $e->getMessage();
    if (isset($conn)) $conn->close();
    echo json_encode($response_default_error);
}
// Não use a tag de fechamento ?>