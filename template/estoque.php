<?php
// O $conn DEVE vir do conexao.php. Se houver erro aqui, o script para.
include 'conexao.php'; 

// Define o limite de caracteres para exibição na tabela
$LIMITE_PRODUTO = 30; 

// Variável para armazenar erros de conexão
// Se $conn não foi definida em conexao.php ou tem erro de conexão
$conexao_falhou = !isset($conn) || (isset($conn->connect_error) && $conn->connect_error);

// 1. CÁLCULO DO VALOR TOTAL GERAL
if ($conexao_falhou) {
    $totalFormatado = "Erro de Conexão";
} else {
    // Consulta para obter o VALOR TOTAL GERAL
    $sqlValorTotalGeral = "SELECT SUM(ValorTotal) AS TotalGeral FROM estoque";
    $resTotalGeral = $conn->query($sqlValorTotalGeral);

    if (!$resTotalGeral) {
        // Exibe o erro exato do MySQL para diagnosticar o problema de coluna/tabela
        $totalFormatado = "Erro de Consulta Total: " . $conn->error; 
    } else {
        $totalGeral = $resTotalGeral->fetch_assoc()['TotalGeral'] ?? 0;
        
        // Determina a classe para o Valor Total Geral
        $classeTotalGeral = ($totalGeral < 0) ? 'text-danger' : 'text-success';
        
        $totalFormatado = "R$ " . number_format($totalGeral, 2, ',', '.');
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agile Stock - Estoque</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/estoque.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> 
</head>
<body>

<div class="sidebar">
    <div class="logo-container">
        <img src="./img/logo.png" alt="Agile Stock Logo" class="logo-img">
        <h4 class="logo-text">Agile Stock</h4>
    </div>

    <a href="estoque.php">Estoque</a> 
    <a href="CadQuant.php">Movimentações</a>
    <a href="CadProduto.php">Produtos</a>
    <a href="CadFornecedor.php">Fornecedores</a>
</div>

<div class="content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0">Valor Atual Em Estoque</h2>
        <a href="exportar_excel.php" class="btn btn-success">
            <i class="bi bi-file-earmark-spreadsheet"></i> Relatório
        </a>
    </div>

    <div class="header-estoque mb-5">
        <?php if ($conexao_falhou): ?>
            <h3 class='text-danger m-0'>❌ Erro de Conexão com o Banco de Dados. Verifique conexao.php.</h3>
        <?php else: ?>
            <h3 class='<?php echo $classeTotalGeral; ?> m-0'>Total em Estoque: <?php echo $totalFormatado; ?></h3>
        <?php endif; ?>
    </div>
    <div class="row mb-5">
        <div class="col-md-8 offset-md-2 col-lg-6 offset-lg-3">
            <h4>Distribuição de Valor por Categoria</h4>
            <canvas id="graficoEstoque"></canvas>
        </div>
    </div>

<h4 class="mt-4">Estoque Detalhado</h4>

<div class="history-container">
    <table class="table table-striped history-table">
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Produto</th>
                <th>Fornecedor</th>
                <th>Quantidade</th>
                <th>Valor Total</th>
                <th>Ações</th> 
            </tr>
        </thead>
        <tbody>
            <?php
            // 2. CONSULTA E PREENCHIMENTO DA TABELA
            if (!$conexao_falhou) { 
                // CONSULTA SQL ATUALIZADA PARA BUSCAR CodProduto (para o modal)
                $sqlEstoque = "SELECT 
                                            cp.Tipo, 
                                            cp.Produto, 
                                            cf.Fornecedor AS NomeFornecedor,
                                            e.Quantidade, 
                                            e.ValorTotal,
                                            cp.Formato,
                                            pf.CodProdFor,
                                            cp.CodProduto AS CodigoDoProduto 
                                        FROM estoque e
                                        INNER JOIN produto_fornecedor pf ON e.CodProdFor_FK = pf.CodProdFor
                                        INNER JOIN cadproduto cp ON pf.CodProduto_FK = cp.CodProduto
                                        INNER JOIN cadfornecedor cf ON pf.CNPJ_Fornecedor_FK = cf.CNPJ
                                        WHERE e.Quantidade != 0 OR e.ValorTotal != 0 
                                        ORDER BY cp.Produto ASC";

                $resultadoEstoque = $conn->query($sqlEstoque);

                if (!$resultadoEstoque) {
                    // Exibe erro de consulta
                    echo "<tr><td colspan='6' class='text-center text-danger'>❌ Erro na Consulta SQL (Tabela): " . $conn->error . "</td></tr>";
                } elseif ($resultadoEstoque->num_rows > 0) {
                    while ($item = $resultadoEstoque->fetch_assoc()) {
                        
                        // ----------------------------------------------------
                        // LÓGICA DE COR PARA VALOR TOTAL NEGATIVO
                        // ----------------------------------------------------
                        $valorTotalNumerico = (float) $item['ValorTotal'];
                        
                        // Determina a classe CSS: 'text-danger fw-bold' se negativo, vazio se positivo/zero
                        $classeCor = ($valorTotalNumerico < 0) ? 'text-danger fw-bold' : '';

                        $qtdFormatada = number_format($item['Quantidade'], 2, ',', '.') . " " . $item['Formato'];
                        $valorFormatado = "R$ " . number_format($valorTotalNumerico, 2, ',', '.');
                        // ----------------------------------------------------

                        $codProdFor = $item['CodProdFor']; // Chave Estrangeira para o formulário de comentários (FK)
                        $codigoDoProduto = $item['CodigoDoProduto']; // Codigo do Produto para o título do modal
                        
                        // Escapa strings para uso em JavaScript (JSON-safe)
                        $produtoEscapado = htmlspecialchars(addslashes($item['Produto']));
                        $fornecedorEscapado = htmlspecialchars(addslashes($item['NomeFornecedor']));

                        // Lógica de limite de caracteres (para evitar quebras no layout)
                        $produtoOriginal = $item['Produto'];
                        if (mb_strlen($produtoOriginal, 'UTF-8') > $LIMITE_PRODUTO) {
                            $produtoExibicao = mb_substr($produtoOriginal, 0, $LIMITE_PRODUTO, 'UTF-8') . '...';
                            // Adiciona o 'title' para mostrar o nome completo ao passar o mouse
                            $produtoExibicao = "<span title='{$produtoOriginal}'>{$produtoExibicao}</span>";
                        } else {
                            $produtoExibicao = $produtoOriginal;
                        }
            ?>
            <tr>
                <td><?php echo $item['Tipo']; ?></td>
                <td><?php echo $produtoExibicao; ?></td>
                <td><?php echo $item['NomeFornecedor']; ?></td>
                <td><?php echo $qtdFormatada; ?></td>
                <td class="<?php echo $classeCor; ?>"><?php echo $valorFormatado; ?></td> 
                <td>
                    <button 
                        class='btn btn-sm btn-info' 
                        onclick="abrirModalDetalhes(<?php echo $codProdFor; ?>, <?php echo $codigoDoProduto; ?>, '<?php echo $produtoEscapado; ?>', '<?php echo $fornecedorEscapado; ?>')">
                        <i class='bi bi-eye'></i>
                    </button>
                </td>
            </tr>
            <?php 
                    } // Fim do while
                } else { 
            ?>
            <tr><td colspan='6' class='text-center'>Estoque vazio.</td></tr>
            <?php
                } // Fim do if ($resultadoEstoque->num_rows > 0)
            }
            
            // Fecha a conexão se ela foi aberta com sucesso
            if (isset($conn) && !$conexao_falhou) { 
                $conn->close(); 
            }
            ?>
        </tbody>
    </table>
</div>
</div>

---

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/estoque.js"></script>

<div class="modal fade" id="modalDetalhes" tabindex="-1" aria-labelledby="modalDetalhesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetalhesLabel">Detalhes do Item em Estoque</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                
                <h6 id="detalhe-nome-produto"></h6>
                <p id="detalhe-fornecedor"></p>
                <hr>

                <h6>Histórico de Comentários:</h6>
                <div id="historico-comentarios" class="mb-3" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                    Nenhum comentário cadastrado.
                </div>
                
                <h6>Novo Comentário:</h6>
                <form id="form-comentario">
                    <input type="hidden" id="CodProdFor_FK" name="CodProdFor_FK">
                    
                    <textarea id="novo-comentario" name="comentario" class="form-control mb-2" rows="3" placeholder="Adicione seu comentário (Máximo 200 caracteres)..." maxlength="200" required></textarea>
                    
                    <button type="submit" class="btn btn-primary btn-sm">Salvar Comentário</button>
                </form>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

---

<script>
// Referência ao modal (necessita do Bootstrap JS)
const modalDetalhes = new bootstrap.Modal(document.getElementById('modalDetalhes'));

// Função para CARREGAR o Histórico (Requer o arquivo carregar_detalhes.php)
async function carregarHistoricoComentarios(codProdFor) {
    const historicoDiv = document.getElementById('historico-comentarios');
    historicoDiv.innerHTML = '<p class="text-center text-muted">Carregando...</p>';

    try {
        const resp = await fetch(`carregar_detalhes.php?CodProdFor=${codProdFor}`);
        const data = await resp.json();

        if (data.success && data.comentarios.length > 0) {
            historicoDiv.innerHTML = data.comentarios.map(c => 
                `<div class="alert alert-light p-2 mb-1 border">
                    <small class="text-muted d-block">${c.data_comentario}</small>
                    ${c.comentario.replace(/\n/g, '<br>')}
                </div>`
            ).join('');
        } else {
            historicoDiv.innerHTML = '<p class="text-center text-muted">Nenhum comentário cadastrado.</p>';
        }

    } catch (error) {
        historicoDiv.innerHTML = '<p class="text-danger">Erro ao carregar comentários.</p>';
        console.error("Erro ao carregar detalhes:", error);
    }
}


// FUNÇÃO ATUALIZADA: RECEBE E US A O CODIGO DO PRODUTO (codigoDoProduto) NO TÍTULO
async function abrirModalDetalhes(codProdFor, codigoDoProduto, nomeProduto, nomeFornecedor) {
    // 1. Atualizar os títulos no modal - USA O CODIGO DO PRODUTO
    document.getElementById('modalDetalhesLabel').innerText = `Detalhes do Item #${codigoDoProduto}`;
    document.getElementById('detalhe-nome-produto').innerText = `Produto: ${nomeProduto}`;
    document.getElementById('detalhe-fornecedor').innerText = `Fornecedor: ${nomeFornecedor}`;
    
    // 2. Definir o FK no formulário de comentário - MANTÉM CodProdFor
    document.getElementById('CodProdFor_FK').value = codProdFor;
    
    // 3. Carregar o histórico de comentários
    await carregarHistoricoComentarios(codProdFor); 

    // 4. Abrir o modal
    modalDetalhes.show();
}

// ----------------------------------------------------------------------
// Script para SALVAR o Novo Comentário (Requer o arquivo salvar_comentario.php)
document.getElementById('form-comentario').addEventListener('submit', async (e) => {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const codProdFor = document.getElementById('CodProdFor_FK').value;

    const resp = await fetch('salvar_comentario.php', {
        method: 'POST',
        body: formData
    });

    const resultado = await resp.json();

    if (resultado.success) {
        alert('✅ Comentário salvo com sucesso!');
        form.reset(); // Limpa o textarea
        // Recarrega apenas o histórico de comentários no modal
        await carregarHistoricoComentarios(codProdFor); 
    } else {
        alert('❌ Erro ao salvar comentário: ' + resultado.error);
        console.error('Erro de Comentário:', resultado.error);
    }
});


// ==========================================================
// CÓDIGO DO GRÁFICO (Requer o arquivo dados_grafico.php)
// ==========================================================

// Função para buscar os dados e desenhar o gráfico
async function desenharGraficoEstoque() {
    try {
        // 1. Faz a requisição ao arquivo PHP que retorna os dados formatados em JSON
        const resp = await fetch('dados_grafico.php');
        const json = await resp.json();

        if (!json.success || json.dados.data.length === 0) {
            // Se não houver dados, exibe uma mensagem no lugar do gráfico
            document.getElementById('graficoEstoque').parentElement.innerHTML = '<p class="text-muted text-center">Nenhum dado de estoque para exibir o gráfico.</p>';
            return;
        }

        const ctx = document.getElementById('graficoEstoque').getContext('2d');
        
        // Cores padrão do gráfico
        const cores = [
            'rgba(255, 99, 132, 0.7)', 
            'rgba(54, 162, 235, 0.7)', 
            'rgba(255, 206, 86, 0.7)', 
            'rgba(75, 192, 192, 0.7)', 
            'rgba(153, 102, 255, 0.7)'
        ];

        // 2. Cria o Gráfico de Barras
        new Chart(ctx, {
            type: 'bar', // Tipo de gráfico: 'bar' (barras)
            data: {
                labels: json.dados.labels,
                datasets: [{
                    label: 'Valor Total (R$) em Estoque por Categoria',
                    data: json.dados.data,
                    backgroundColor: cores.slice(0, json.dados.labels.length),
                    borderColor: 'rgba(0, 0, 0, 0.8)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: false, // Pode começar abaixo de zero para mostrar valores negativos
                        title: {
                            display: true,
                            text: 'Valor Total (R$)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                // Formata o valor como moeda
                                label += 'R$ ' + context.parsed.y.toFixed(2).replace('.', ',');
                                return label;
                            }
                        }
                    }
                }
            }
        });

    } catch (error) {
        console.error("Erro ao desenhar o gráfico:", error);
        document.getElementById('graficoEstoque').parentElement.innerHTML = '<p class="text-danger text-center">Não foi possível carregar o gráfico. Verifique o PHP (dados_grafico.php).</p>';
    }
}

// Chama a função ao carregar a página
window.onload = function() {
    desenharGraficoEstoque();
};
</script>
</body>
</html>