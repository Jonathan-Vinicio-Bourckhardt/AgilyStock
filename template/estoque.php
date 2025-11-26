<?php
// OBRIGATÓRIO: Iniciar a sessão
session_start();

// --- Configuração e Conexão (Início) ---
include 'conexao.php'; // Assume que define $conn

// Variáveis de Configuração
$LIMITE_PRODUTO = 30; // Limite de caracteres para exibição na tabela

// Variável de Estado da Conexão
$conexao_falhou = !isset($conn) || $conn->connect_error;

// --- Funções de Lógica ---

/**
 * Verifica o status de login e retorna o ID da empresa.
 * Redireciona em caso de falha.
 */
function verificarLogin() {
    if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['id_empresa'])) {
        header('Location: ../CadLog.php');
        exit;
    }
    return $_SESSION['id_empresa'];
}

/**
 * Busca o CNPJ formatado da empresa logada.
 */
function buscarCnpj($conn, $id_empresa_logada) {
    if (!$conn || $conn->connect_error) {
        return "Erro de Conexão";
    }

    $sql_cnpj = "SELECT cnpj FROM empresas WHERE id = ?";
    $stmt_cnpj = $conn->prepare($sql_cnpj);

    if ($stmt_cnpj) {
        $stmt_cnpj->bind_param("i", $id_empresa_logada);
        $stmt_cnpj->execute();
        $resultado_cnpj = $stmt_cnpj->get_result();

        if ($resultado_cnpj && $resultado_cnpj->num_rows === 1) {
            $cnpj_raw = $resultado_cnpj->fetch_assoc()['cnpj'];
            $stmt_cnpj->close();

            // Formatação do CNPJ (XX.XXX.XXX/XXXX-XX)
            if (strlen($cnpj_raw) === 14 && is_numeric($cnpj_raw)) {
                return preg_replace("/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/", "$1.$2.$3/$4-$5", $cnpj_raw);
            }
            return "CNPJ Inválido";
        }
        $stmt_cnpj->close();
    }
    return "CNPJ não encontrado";
}

/**
 * Calcula o Valor Total Geral do Estoque para a empresa logada.
 */
function calcularValorTotalGeral($conn, $id_empresa_logada) {
    if (!$conn || $conn->connect_error) {
        return ['total' => null, 'formatado' => "Erro de Conexão", 'classe' => 'text-danger'];
    }

    $sqlValorTotalGeral = "SELECT SUM(e.ValorTotal) AS TotalGeral
                            FROM estoque e
                            INNER JOIN produto_fornecedor pf ON e.CodProdFor_FK = pf.CodProdFor
                            INNER JOIN cadproduto cp ON pf.CodProduto_FK = cp.CodProduto
                            WHERE cp.id_empresa = ?";

    $stmtTotalGeral = $conn->prepare($sqlValorTotalGeral);
    if ($stmtTotalGeral) {
        $stmtTotalGeral->bind_param("i", $id_empresa_logada);
        $stmtTotalGeral->execute();
        $resTotalGeral = $stmtTotalGeral->get_result();

        if (!$resTotalGeral) {
            return ['total' => null, 'formatado' => "Erro de Consulta Total: " . $conn->error, 'classe' => 'text-danger'];
        }

        $totalGeral = $resTotalGeral->fetch_assoc()['TotalGeral'] ?? 0;
        $stmtTotalGeral->close();

        $classeTotalGeral = ($totalGeral < 0) ? 'text-danger' : 'text-success';
        $totalFormatado = "R$ " . number_format((float)$totalGeral, 2, ',', '.');
        
        return ['total' => $totalGeral, 'formatado' => $totalFormatado, 'classe' => $classeTotalGeral];
    }
    return ['total' => null, 'formatado' => "Erro de Preparação Total: " . $conn->error, 'classe' => 'text-danger'];
}


/**
 * Executa a consulta do Estoque Detalhado e retorna os resultados.
 */
function buscarEstoqueDetalhado($conn, $id_empresa_logada) {
    if (!$conn || $conn->connect_error) {
        return null; // Retorna nulo em caso de falha de conexão
    }
    
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
                    WHERE cp.id_empresa = ? AND (e.Quantidade != 0 OR e.ValorTotal != 0)
                    ORDER BY cp.Produto ASC";

    $stmtEstoque = $conn->prepare($sqlEstoque);
    if ($stmtEstoque) {
        $stmtEstoque->bind_param("i", $id_empresa_logada);
        $stmtEstoque->execute();
        $resultadoEstoque = $stmtEstoque->get_result();
        $stmtEstoque->close();
        
        return $resultadoEstoque; // Retorna o objeto mysqli_result
    }
    return false; // Retorna false em caso de erro na preparação
}

// --- Execução da Lógica Principal ---

$id_empresa_logada = verificarLogin();
$nome_empresa = $_SESSION['nome_empresa'] ?? "Empresa Desconhecida";

// Busca o CNPJ
$cnpj_empresa = buscarCnpj($conn, $id_empresa_logada);

// Calcula o valor total
$totalGeralData = calcularValorTotalGeral($conn, $id_empresa_logada);
$totalFormatado = $totalGeralData['formatado'];
$classeTotalGeral = $totalGeralData['classe'];

// Busca os dados detalhados (serão processados no HTML)
$resultadoEstoque = buscarEstoqueDetalhado($conn, $id_empresa_logada);
$erroConsultaTabela = ($resultadoEstoque === false) ? $conn->error : null;

// --- Fechamento da Conexão (Fim da Lógica PHP) ---
if (isset($conn) && !$conexao_falhou) {
    $conn->close();
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

    <style>
        /* Estilos do sidebar, mantidos para consistência */
        .sidebar {
            height: 100vh;
            position: fixed;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding-bottom: 0;
        }

        .sidebar-footer {
            padding: 20px;
            padding-top: 10px;
            color: #ffffff;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.85em;
        }

        .content {
            margin-left: 250px;
            padding: 20px;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div>
        <div class="logo-container">
            <img src="./img/logo.png" alt="Agile Stock Logo" class="logo-img">
            <h4 class="logo-text">Agile Stock</h4>
        </div>
        <a href="estoque.php">Estoque</a>
        <a href="CadQuant.php">Movimentações</a>
        <a href="CadProduto.php">Produtos</a>
        <a href="CadFornecedor.php">Fornecedores</a>
    </div>

    <div class="sidebar-footer">
        <div style="font-size: 1.1em; font-weight: bold; color: #a0d4a0; margin-bottom: 5px;">
            <?= htmlspecialchars($nome_empresa); ?>
        </div>
        <div style="font-size: 0.9em;">
            <span style="font-weight: 300;">CNPJ:</span>
            <span style="font-weight: 500;"><?= htmlspecialchars($cnpj_empresa); ?></span>
        </div>
    </div>
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
                <?php if ($conexao_falhou): ?>
                    <tr><td colspan='6' class='text-center text-danger'>❌ Erro de Conexão com o Banco de Dados.</td></tr>
                <?php elseif ($erroConsultaTabela): ?>
                    <tr><td colspan='6' class='text-center text-danger'>❌ Erro na Consulta SQL (Tabela): <?= htmlspecialchars($erroConsultaTabela); ?></td></tr>
                <?php elseif ($resultadoEstoque->num_rows > 0): ?>
                    <?php while ($item = $resultadoEstoque->fetch_assoc()): 
                        $valorTotalNumerico = (float) $item['ValorTotal'];
                        $classeCor = ($valorTotalNumerico < 0) ? 'text-danger fw-bold' : '';
                        $qtdFormatada = number_format($item['Quantidade'], 2, ',', '.') . " " . htmlspecialchars($item['Formato']);
                        $valorFormatado = "R$ " . number_format($valorTotalNumerico, 2, ',', '.');
                        
                        $codProdFor = $item['CodProdFor'];
                        $codigoDoProduto = $item['CodigoDoProduto'];
                        
                        // Preparação de strings para JavaScript/HTML
                        $produtoOriginal = $item['Produto'];
                        $produtoExibicao = mb_strlen($produtoOriginal, 'UTF-8') > $LIMITE_PRODUTO 
                                        ? mb_substr($produtoOriginal, 0, $LIMITE_PRODUTO, 'UTF-8') . '...'
                                        : $produtoOriginal;
                        
                        $produtoExibicaoHTML = "<span title='" . htmlspecialchars($produtoOriginal) . "'>" . htmlspecialchars($produtoExibicao) . "</span>";
                        $produtoEscapadoJS = htmlspecialchars(addslashes($item['Produto']));
                        $fornecedorEscapadoJS = htmlspecialchars(addslashes($item['NomeFornecedor']));
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($item['Tipo']); ?></td>
                            <td><?= $produtoExibicaoHTML; ?></td>
                            <td><?= htmlspecialchars($item['NomeFornecedor']); ?></td>
                            <td><?= $qtdFormatada; ?></td>
                            <td class="<?= $classeCor; ?>"><?= $valorFormatado; ?></td>
                            <td>
                                <button
                                    class='btn btn-sm btn-info'
                                    onclick="abrirModalDetalhes(<?= $codProdFor; ?>, <?= $codigoDoProduto; ?>, '<?= $produtoEscapadoJS; ?>', '<?= $fornecedorEscapadoJS; ?>')">
                                    <i class='bi bi-eye'></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan='6' class='text-center'>Estoque vazio.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

---

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/estoque.js"></script> <script>
// Referência ao modal (necessita do Bootstrap JS)
const modalDetalhes = new bootstrap.Modal(document.getElementById('modalDetalhes'));

// Função para CARREGAR o Histórico (Requer o arquivo buscar_comentarios.php)
async function carregarHistoricoComentarios(codProdFor) {
    const historicoDiv = document.getElementById('historico-comentarios');
    historicoDiv.innerHTML = '<p class="text-center text-muted">Carregando...</p>';

    try {
        const resp = await fetch(`buscar_comentarios.php?CodProdFor=${codProdFor}`);
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


// FUNÇÃO ATUALIZADA: Abre o modal e carrega os dados
async function abrirModalDetalhes(codProdFor, codigoDoProduto, nomeProduto, nomeFornecedor) {
    // 1. Atualizar os títulos no modal
    document.getElementById('modalDetalhesLabel').innerText = `Detalhes do Item #${codigoDoProduto}`;
    document.getElementById('detalhe-nome-produto').innerText = `Produto: ${nomeProduto}`;
    document.getElementById('detalhe-fornecedor').innerText = `Fornecedor: ${nomeFornecedor}`;

    // 2. Definir o FK no formulário de comentário
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
// CÓDIGO DO GRÁFICO (Requer o arquivo obter_dados_grafico.php)
// ==========================================================

// Função para buscar os dados e desenhar o gráfico
async function desenharGraficoEstoque() {
    try {
        // 1. Faz a requisição ao arquivo PHP que retorna os dados formatados em JSON
        const resp = await fetch('obter_dados_grafico.php');
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
                        beginAtZero: false,
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
        document.getElementById('graficoEstoque').parentElement.innerHTML = '<p class="text-danger text-center">Não foi possível carregar o gráfico. Verifique o PHP (obter_dados_grafico.php).</p>';
    }
}

// Chama a função ao carregar a página
window.onload = function() {
    desenharGraficoEstoque();
};
</script>
</body>
</html>