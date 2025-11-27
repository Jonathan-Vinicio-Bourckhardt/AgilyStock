<?php
// OBRIGATÓRIO: Iniciar a sessão
session_start();

// 🛑 Novo: Obter o ID da empresa logada (ou redirecionar se não estiver setado)
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['id_empresa'])) { 
    header('Location: ../CadLog.php'); 
    exit;
}
$id_empresa_logada = $_SESSION['id_empresa'];
// Pega o nome da sessão (melhor performance)
$nome_empresa = $_SESSION['nome_empresa'] ?? "Empresa Desconhecida"; 
// 🛑 Fim da verificação 🛑

// 1. INCLUSÃO DA CONEXÃO
include 'conexao.php';

if (!isset($conn) || $conn->connect_error) {
    die("Falha na conexão com o banco de dados. Verifique 'conexao.php'.");
}

// --- Novo: Lógica de Busca do CNPJ da Empresa Logada ---
$cnpj_empresa = "CNPJ não encontrado"; // Valor padrão

// Apenas executa se a conexão for bem-sucedida
if (isset($conn)) {
    // Para evitar conflitos de variáveis com outras queries, usamos $conn->prepare
    $sql_cnpj = "SELECT cnpj FROM empresas WHERE id = ?";
    $stmt_cnpj = $conn->prepare($sql_cnpj);
    
    if ($stmt_cnpj) {
        $stmt_cnpj->bind_param("i", $id_empresa_logada);
        $stmt_cnpj->execute();
        $resultado_cnpj = $stmt_cnpj->get_result();
        
        if ($resultado_cnpj->num_rows == 1) {
            $empresa = $resultado_cnpj->fetch_assoc();
            $cnpj_raw = $empresa['cnpj'];
            
            // Formatação do CNPJ (XX.XXX.XXX/XXXX-XX)
            if (strlen($cnpj_raw) === 14 && is_numeric($cnpj_raw)) {
                $cnpj_empresa = preg_replace("/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/", "$1.$2.$3/$4-$5", $cnpj_raw);
            } else {
                 $cnpj_empresa = "CNPJ Inválido";
            }
        }
        $stmt_cnpj->close();
    }
}
// --- Fim da Lógica de Busca do CNPJ ---


// Bloco anti-cache
header("Cache-Control: no-cache, no-store, must-revalidate");  
header("Pragma: no-cache");  
header("Expires: 0");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agile Stock - Movimentações</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/CadQuant.css"> 
    
    <style>
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
        
        /* Ajuste: Aplicar estilo de padding apenas para links de navegação */
        .sidebar > div > a {
            padding: 15px 20px;
        }

        .content {
            margin-top: 0;
            padding: 20px;
            margin-left: 250px;
        }
        
        /* NOVO: Garante que a logo não tenha estilo de cursor de link */
        .logo-container {
            cursor: default; 
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div>
        <!-- A LOGO NÃO É MAIS CLICÁVEL. ESTRUTURA MANTIDA APENAS PARA EXIBIÇÃO. -->
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
        
        <a href="../logout.php" class="btn btn-outline-light btn-sm w-100 mt-2" style="font-weight: 500; font-size: 1em;">
            <i class="bi bi-box-arrow-right"></i> Sair
        </a>
    </div>
</div>

<div class="content">
    <h2 class="mb-4">Cadastro de Movimentação</h2>

    <form id="form-movimento" action="inserir_movimento.php" method="POST"> 
        <table class="table table-bordered bg-white">
            <thead>
                <tr>
                    <th>Devolução</th>
                    <th>Ação</th>
                    <th>Tipo</th>
                    <th>Produto / Fornecedor</th>
                    <th>Quantidade</th>
                    <th>Valor Unitário (R$)</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <select id="devolucao" name="devolucao" class="form-control">
                            <option value="Não">Não</option>
                            <option value="Sim">Sim</option>
                        </select>
                    </td>

                    <td>
                        <select id="acao" name="acao" class="form-control">
                            <option value="Soma">Soma</option>
                            <option value="Subtracao">Subtracao</option>
                        </select>
                    </td>

                    <td>
                        <select id="tipo" class="form-control" disabled>
                            <option value="" selected disabled>Selecione um produto</option>
                        </select>
                        <input type="hidden" id="tipo-hidden" name="tipo"> 
                    </td>

                    <td>
                        <select id="produtoFornecedor" name="codProdFor" class="form-control" required>
                            <option selected disabled value="">-- Selecione --</option>
                            
                            <?php
                            // 🛑 AÇÃO DE ISOLAMENTO: Filtrar produto_fornecedor por id_empresa 🛑
                            $sql = "SELECT 
                                        pf.CodProdFor, 
                                        cp.Tipo, 
                                        cp.Produto, 
                                        pf.Formato, 
                                        cf.Fornecedor AS NomeFornecedor 
                                    FROM produto_fornecedor pf
                                    INNER JOIN cadproduto cp ON pf.CodProduto_FK = cp.CodProduto
                                    INNER JOIN cadfornecedor cf ON pf.CNPJ_Fornecedor_FK = cf.CNPJ
                                    WHERE cp.id_empresa = $id_empresa_logada 
                                    ORDER BY cp.Produto ASC";
                            
                            $res = $conn->query($sql);
                            
                            if ($res && $res->num_rows > 0) {
                                while ($row = $res->fetch_assoc()) {
                                    
                                    // Lógica de TRUNCAMENTO (Cadastro/Dropdown)
                                    $nomeProduto = $row['Produto'];
                                    $limite = 30; // Limite de caracteres para o nome do produto no dropdown
                                    
                                    if (mb_strlen($nomeProduto) > $limite) {
                                        $produtoTruncado = mb_substr($nomeProduto, 0, $limite) . '...';
                                    } else {
                                        $produtoTruncado = $nomeProduto;
                                    }

                                    $textoOpcao = "{$produtoTruncado} ({$row['Formato']}) - {$row['NomeFornecedor']}";
                                    
                                    echo "<option 
                                                data-tipo='{$row['Tipo']}'
                                                value='{$row['CodProdFor']}'> 
                                                    {$textoOpcao}
                                                </option>";
                                }
                            } else {
                                echo "<option disabled>Nenhuma combinação Produto/Fornecedor cadastrada.</option>";
                            }
                            ?>
                        </select>
                    </td>

                    <td><input id="quantidade" name="quantidade" type="number" class="form-control" placeholder="Qtd" required step="0.01" min="0.01" max="99999.99"></td>
                    
                    <td><input id="valor" name="valorUnitario" type="number" class="form-control" placeholder="Valor Unitário" step="0.01" required min="0.01" max="99999.99"></td> 

                    <td><button type="submit" class="btn btn-success w-100">Cadastrar</button></td>
                </tr>
            </tbody>
        </table>
    </form>

    <h4 class="mt-4">Histórico de Movimentações</h4>

    <div class="history-container">
        <table class="table table-striped history-table">
            <thead>
                <tr>
                    <th>Data e Hora</th> 
                    <th>Devolução</th>
                    <th>Ação</th>
                    <th>Produto</th>
                    <th>Quantidade</th>
                    <th>Valor Unitário</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // 🛑 AÇÃO DE ISOLAMENTO: Filtrar histórico por id_empresa 🛑
                $sqlHistorico = "SELECT cm.Devolucao, cm.Acao, cp.Produto, 
                                         cm.Quantidade, cm.ValorUnitario, pf.Formato, cm.DataMovimento
                                 FROM cadmovimento cm
                                 INNER JOIN produto_fornecedor pf ON cm.CodProdFor_FK = pf.CodProdFor
                                 INNER JOIN cadproduto cp ON pf.CodProduto_FK = cp.CodProduto
                                 INNER JOIN cadfornecedor cf ON pf.CNPJ_Fornecedor_FK = cf.CNPJ
                                 WHERE cp.id_empresa = $id_empresa_logada 
                                 ORDER BY cm.CodMovimento DESC";

                $resultadoHistorico = $conn->query($sqlHistorico);

                if ($resultadoHistorico && $resultadoHistorico->num_rows > 0) {
                    while ($movimento = $resultadoHistorico->fetch_assoc()) {
                        
                        // Lógica de TRUNCAMENTO (Histórico)
                        $produtoCompleto = $movimento['Produto'];
                        $limiteCaracteres = 30; // Limite de caracteres para o nome do produto no histórico
                        
                        if (mb_strlen($produtoCompleto) > $limiteCaracteres) {
                            $produtoExibicao = mb_substr($produtoCompleto, 0, $limiteCaracteres) . '...';
                        } else {
                            $produtoExibicao = $produtoCompleto;
                        }
                        
                        $valorFormatado = "R$" . number_format($movimento['ValorUnitario'], 2, ',', '.');
                        $qtdFormatada = $movimento['Quantidade'] . " (" . $movimento['Formato'] . ")";
                        
                        $dataHoraDisplay = (isset($movimento['DataMovimento'])) 
                                             ? date('d/m/Y H:i', strtotime($movimento['DataMovimento']))
                                             : 'N/A';
                        
                        echo "<tr>
                                     <td>{$dataHoraDisplay}</td> 
                                     <td>{$movimento['Devolucao']}</td>
                                     <td>{$movimento['Acao']}</td> 
                                     <td>{$produtoExibicao}</td> <td>{$qtdFormatada}</td>
                                     <td>{$valorFormatado}</td>
                                 </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' class='text-center'>Nenhum movimento registrado.</td></tr>"; 
                }
                ?>
            </tbody>
        </table>
    </div>
</div> 

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/CadQuant.js"></script>

<?php
if (isset($conn) && $conn) {
    $conn->close();
}
?>

</body>
</html>