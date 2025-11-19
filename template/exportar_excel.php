<?php
// Inclui o arquivo de conexão
include 'conexao.php'; 

// Variável para armazenar erros de conexão
$conexao_falhou = !isset($conn) || (isset($conn->connect_error) && $conn->connect_error);

if ($conexao_falhou) {
    // Em caso de erro de conexão, exibe uma mensagem e interrompe
    die("Erro de Conexão com o Banco de Dados. Verifique o arquivo conexao.php.");
}

// 1. DEFINIÇÃO DOS HEADERS PARA DOWNLOAD DO ARQUIVO CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="relatorio_estoque_completo_' . date('Y-m-d') . '.csv"');

// Abrir o stream de saída (memória)
$output = fopen('php://output', 'w');

// Configurar o delimitador e encoding para CSV
// Define o ponto e vírgula como delimitador padrão para melhor compatibilidade com Excel em português
fputcsv($output, array('sep=;'), ';'); 

// 2. DEFINE E ESCREVE OS CABEÇALHOS DO ARQUIVO (Adicionando Codigo do Produto e Formato)
$cabecalhos = array(
    'CodProduto', 
    'Tipo', 
    'Produto', 
    'Fornecedor', 
    'Quantidade', 
    'Formato',
    'Valor Total'
);
fputcsv($output, $cabecalhos, ';'); // Escreve os cabeçalhos no arquivo

// 3. CONSULTA SQL COMPLETA para buscar os dados
$sqlEstoque = "SELECT 
                     cp.CodProduto,
                     cp.Tipo, 
                     cp.Produto, 
                     cf.Fornecedor AS NomeFornecedor,
                     e.Quantidade, 
                     e.ValorTotal,
                     cp.Formato
                 FROM estoque e
                 INNER JOIN produto_fornecedor pf ON e.CodProdFor_FK = pf.CodProdFor
                 INNER JOIN cadproduto cp ON pf.CodProduto_FK = cp.CodProduto
                 INNER JOIN cadfornecedor cf ON pf.CNPJ_Fornecedor_FK = cf.CNPJ
                 WHERE e.Quantidade != 0 OR e.ValorTotal != 0 
                 ORDER BY cp.Produto ASC";

$resultadoEstoque = $conn->query($sqlEstoque);

if ($resultadoEstoque && $resultadoEstoque->num_rows > 0) {
    // 4. PREENCHE O ARQUIVO COM OS DADOS
    while ($item = $resultadoEstoque->fetch_assoc()) {
        
        // Formata Quantidade e ValorTotal para o CSV (usando vírgula como separador decimal)
        $qtdFormatada = number_format($item['Quantidade'], 2, ',', ''); // Ex: 100,00
        $valorFormatado = number_format((float) $item['ValorTotal'], 2, ',', ''); // Ex: -4,00
        
        // Array com os dados prontos para a linha do CSV
        $linha = array(
            $item['CodProduto'],
            $item['Tipo'],
            $item['Produto'],
            $item['NomeFornecedor'],
            $qtdFormatada,
            $item['Formato'],
            $valorFormatado
        );
        
        // Escreve a linha no arquivo CSV
        fputcsv($output, $linha, ';');
    }
}

// 5. FECHA OS ARQUIVOS E A CONEXÃO
fclose($output);
$conn->close();

exit; // Garante que nenhum HTML ou outro conteúdo seja adicionado após o CSV
?>