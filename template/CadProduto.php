<?php
// OBRIGATÓRIO: Iniciar a sessão
session_start();

// 🛑 Novo: Obter o ID da empresa logada (ou redirecionar se não estiver setado)
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['id_empresa'])) { 
    header('Location: ../CadLog.php'); 
    exit;
}
$id_empresa_logada = $_SESSION['id_empresa'];
// NOVO: Pega o nome da sessão (se estiver disponível, se não usa um padrão)
$nome_empresa = $_SESSION['nome_empresa'] ?? "Empresa Desconhecida"; 
// 🛑 Fim da verificação 🛑

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Supondo que 'conexao.php' está configurado e disponível
include 'conexao.php'; 

// --- NOVO: Lógica de Busca do CNPJ da Empresa Logada ---
$cnpj_empresa = "CNPJ não encontrado"; // Valor padrão

if (isset($conn)) {
    // Para evitar conflitos de variáveis com outras queries
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

// 1. PREPARAR OPÇÕES DE FORNECEDORES
// 🛑 AÇÃO DE ISOLAMENTO: Filtrar fornecedores por id_empresa 🛑
$sqlFornecedor = "SELECT CNPJ, Fornecedor FROM cadfornecedor WHERE id_empresa = $id_empresa_logada ORDER BY Fornecedor ASC";
$res = $conn->query($sqlFornecedor);
$fornecedorOptions = '';
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $cnpjForn = htmlspecialchars($row['CNPJ']);
        $nomeForn = htmlspecialchars($row['Fornecedor']);
        // Remove pontuação do CNPJ para facilitar o JS/HTML
        $cnpjForn_limpo = preg_replace('/[^0-9]/', '', $cnpjForn); 
        $fornecedorOptions .= "<option value='{$cnpjForn_limpo}'>{$nomeForn} ({$cnpjForn})</option>";
    }
}

// Escapar para uso no JavaScript
$jsFornecedorOptions = str_replace(["\r", "\n"], '', $fornecedorOptions);
$jsFornecedorOptions = str_replace("'", "\'", $jsFornecedorOptions);

// Opções de Tipo e Formato
$tipoOptions = [
    'fruta' => 'Fruta',
    'verdura' => 'Verdura',
    'legume' => 'Legume',
    'outro' => 'Outro'
];
$formatoOptions = [
    'kg' => 'KG',
    'unidade' => 'Unidade'
];

function buildSelectOptions($options, $selectedValue) {
    $html = '';
    foreach ($options as $value => $label) {
        $selected = (strtolower($value) == strtolower($selectedValue)) ? 'selected' : '';
        $html .= "<option value='{$value}' {$selected}>{$label}</option>";
    }
    return $html;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agile Stock - Cadastro de Produto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/CadProduto.css"> 
    
    <style>
        .sidebar {
            /* Garante que o sidebar ocupe toda a altura da viewport */
            height: 100vh; 
            /* Permite o posicionamento absoluto do rodapé */
            position: fixed; 
            display: flex;
            flex-direction: column;
            justify-content: space-between; /* Empurra o footer para baixo */
            padding-bottom: 0; /* Remove padding de baixo padrão, se houver */
        }

        .sidebar-footer {
            /* Estilo para a área inferior */
            padding: 20px;
            padding-top: 10px;
            color: #ffffff;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.85em;
        }
        
        /* Opcional: Ajusta o padding para o menu de navegação, se necessário */
        .sidebar > a {
            padding: 15px 20px;
        }

        /* Mantém o conteúdo à direita do sidebar */
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
    <h2 class="mb-4">Cadastro de Produto</h2>

    <form id="form-cadastro-produto">
        <table class="table table-bordered bg-white">
            <thead>
                <tr>
                    <th style="width: 15%;">Código</th>
                    <th style="width: 10%;">Tipo</th>
                    <th style="width: 10%;">Formato</th>
                    <th style="width: 25%;">Produto</th>
                    <th style="width: 30%;">Fornecedor</th>
                    <th style="width: 10%;"></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input id="codProduto" name="codProduto" type="text" class="form-control" placeholder="123" required></td>

                    <td>
                        <select id="tipo" name="tipo" class="form-control" required>
                            <?php echo buildSelectOptions($tipoOptions, ''); ?>
                        </select>
                    </td>

                    <td>
                        <select id="formato" name="formato" class="form-control" required>
                            <?php echo buildSelectOptions($formatoOptions, ''); ?>
                        </select>
                    </td>

                    <td><input id="produto" name="produto" type="text" class="form-control" placeholder="Maça" required></td>

                    <td>
                        <select id="fornecedor" name="fornecedor" class="form-control" required>
                            <option value="">Selecione...</option>
                            <?php echo $fornecedorOptions; ?>
                        </select>
                    </td>

                    <td><button type="submit" class="btn btn-success w-100">Cadastrar</button></td>
                </tr>
            </tbody>
        </table>
    </form>
    
    <h4 class="mt-4">Produtos</h4>

    <div class="history-container">
        <table class="table table-striped history-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Tipo</th>
                    <th>Formato</th>
                    <th>Produto</th>
                    <th>Fornecedor</th>
                    <th>Ações</th> 
                </tr>
            </thead>
            <tbody>
                <?php
                // 🛑 AÇÃO DE ISOLAMENTO: Filtrar produtos por id_empresa 🛑
                $sqlProdutos = "SELECT p.CodProduto, p.Tipo, p.Formato, p.Produto, p.Fornecedor AS CNPJFornecedor, f.Fornecedor AS NomeFornecedor
                                FROM cadproduto p
                                INNER JOIN cadfornecedor f ON p.Fornecedor = f.CNPJ
                                WHERE p.id_empresa = $id_empresa_logada 
                                ORDER BY p.CodProduto DESC";
                $resultado = $conn->query($sqlProdutos);

                if ($resultado && $resultado->num_rows > 0) {
                    while ($p = $resultado->fetch_assoc()) {
                        $codProd = htmlspecialchars($p['CodProduto']);
                        $tipo = htmlspecialchars($p['Tipo']);
                        $formato = htmlspecialchars($p['Formato']);
                        $produto = htmlspecialchars($p['Produto']);
                        $cnpjForn = htmlspecialchars($p['CNPJFornecedor']);
                        $nomeForn = htmlspecialchars($p['NomeFornecedor']);
                        
                        // Limpa o CNPJ para uso no JS/HTML (sem caracteres especiais)
                        $cnpjForn_limpo = preg_replace('/[^0-9]/', '', $cnpjForn);

                        $selectTipoOptions = buildSelectOptions($tipoOptions, $tipo);
                        $selectFormatoOptions = buildSelectOptions($formatoOptions, $formato);

                        $limite_caracteres = 30;
                        $nomeForn_exibir = (strlen($nomeForn) > $limite_caracteres) ? mb_substr($nomeForn, 0, $limite_caracteres) . '...' : $nomeForn;
                        $produto_exibir = (strlen($produto) > $limite_caracteres) ? mb_substr($produto, 0, $limite_caracteres) . '...' : $produto;
                        
                        echo "<tr id='row-{$codProd}'>

                                <td>
                                    <span class='view-codprod form-control-static'>{$codProd}</span>
                                    <input name='CodProdutoAntigo' type='hidden' value='{$codProd}'> 
                                </td>

                                <td>
                                    <span class='view-text view-tipo form-control-static'>{$tipoOptions[$tipo]}</span>
                                    <select name='Tipo' class='form-control edit-input select-tipo' style='display:none;' required>
                                        {$selectTipoOptions}
                                    </select>
                                </td>

                                <td>
                                    <span class='view-text view-formato form-control-static'>{$formatoOptions[$formato]}</span>
                                    <select name='Formato' class='form-control edit-input select-formato' style='display:none;' required>
                                        {$selectFormatoOptions}
                                    </select>
                                </td>

                                <td>
                                    <span class='view-text-produto form-control-static'>{$produto_exibir}</span>
                                    <input name='Produto' type='text' class='form-control edit-input' value='{$produto}' style='display:none;' required>
                                </td>

                                <td>
                                    <span class='view-fornecedor form-control-static'>{$nomeForn_exibir}</span>
                                    <select name='Fornecedor' class='form-control edit-input select-fornecedor' style='display:none;' data-selected-cnpj='{$cnpjForn_limpo}' required>
                                    </select>
                                    <input name='CNPJFornecedorHidden' type='hidden' value='{$cnpjForn_limpo}'>
                                </td>

                                <td class='action-buttons'>
                                    <button type='button' class='btn btn-sm btn-primary btn-edit' title='Editar Fornecedor' onclick='toggleEdit(\"{$codProd}\", \"{$cnpjForn_limpo}\")'>
                                        <i class='bi bi-pencil'></i>
                                    </button>

                                    <button type='button' class='btn btn-sm btn-danger btn-delete' title='Excluir' onclick='excluirProduto(\"{$codProd}\", \"{$produto}\")'>
                                        <i class='bi bi-trash'></i>
                                    </button>

                                    <button type='button' class='btn btn-sm btn-success btn-save' title='Salvar' onclick='salvarProduto(\"{$codProd}\")' style='display:none;'>
                                        <i class='bi bi-save'></i>
                                    </button>

                                    <button type='button' class='btn btn-sm btn-secondary btn-cancel' title='Cancelar' onclick='toggleEdit(\"{$codProd}\")' style='display:none;'>
                                        <i class='bi bi-x-lg'></i>
                                    </button>
                                </td>

                            </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' class='text-center'>Nenhum produto cadastrado ainda.</td></tr>";
                }

                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const fornecedorOptionsHTML = "<?php echo $jsFornecedorOptions; ?>";
    
    // O JavaScript é mantido, mas garante que o CNPJ usado seja o LIMPO para correspondência
    window.toggleEdit = function(codProduto, selectedCNPJ = null) {
        const row = document.getElementById(`row-${codProduto}`);
        if (!row) return;

        // Elementos de Visualização que DEVEM PERMANECER VISÍVEIS (Código, Tipo, Formato, Produto)
        const viewElementsFixed = row.querySelectorAll('.view-codprod, .view-tipo, .view-formato, .view-text-produto');
        // Elemento de Visualização do Fornecedor (QUE DEVE SER ESCONDIDO na edição)
        const viewFornecedor = row.querySelector('.view-fornecedor');

        // Elemento de Edição do Fornecedor (QUE DEVE SER MOSTRADO na edição)
        const selectForn = row.querySelector('.select-fornecedor');
        
        // Elementos de Edição dos demais campos (QUE DEVEM PERMANECER ESCONDIDOS)
        const hiddenEditElements = row.querySelectorAll('.edit-input:not(.select-fornecedor)'); 
        
        // 1. Alterna o estado dos Botões
        const btnEdit = row.querySelector('.btn-edit');
        const isEditing = btnEdit.style.display === 'none'; // true se o botão Editar está escondido (ou seja, estamos editando)

        if (!isEditing) {
            // ENTRANDO NO MODO DE EDIÇÃO
            
            // Mantém os SPANs fixos visíveis
            viewElementsFixed.forEach(el => { el.style.display = 'block'; }); 
            
            // ESCONDE O NOME DO FORNECEDOR (span.view-fornecedor)
            viewFornecedor.style.display = 'none';

            // MOSTRA SOMENTE o select do Fornecedor
            selectForn.style.display = 'block'; 
            
            // Garante que os outros campos de edição (Tipo, Formato, Produto) fiquem escondidos
            hiddenEditElements.forEach(el => { el.style.display = 'none'; });

            // Preenche o Fornecedor
            selectForn.innerHTML = '<option value="">Selecione...</option>' + fornecedorOptionsHTML;
            if (selectedCNPJ) {
                selectForn.value = selectedCNPJ;
            }

            // Esconde Editar/Excluir e mostra Salvar/Cancelar
            btnEdit.style.display = 'none';
            row.querySelector('.btn-delete').style.display = 'none';
            row.querySelector('.btn-save').style.display = 'inline-block';
            row.querySelector('.btn-cancel').style.display = 'inline-block';

        } else {
            // SAINDO (CANCELANDO) OU SALVANDO - Volta para o modo de visualização
            
            // Mostra todos os SPANs (incluindo o do Fornecedor)
            viewElementsFixed.forEach(el => { el.style.display = 'block'; }); 
            viewFornecedor.style.display = 'block';

            // Esconde todos os campos de edição
            selectForn.style.display = 'none';
            hiddenEditElements.forEach(el => { el.style.display = 'none'; });
            
            // Mostra Editar/Excluir e esconde Salvar/Cancelar
            btnEdit.style.display = 'inline-block';
            row.querySelector('.btn-delete').style.display = 'inline-block';
            row.querySelector('.btn-save').style.display = 'none';
            row.querySelector('.btn-cancel').style.display = 'none';
        }
    }

    // Função de Cadastro (MANTIDA)
    document.getElementById('form-cadastro-produto').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const resp = await fetch('inserir_produto.php', { method: 'POST', body: formData });
        const responseText = await resp.text();
        let resultado;
        try { resultado = JSON.parse(responseText); } catch (jsonError) { console.error('Resposta do Servidor:', responseText); return; }
        if (resultado.success) { alert('✅ Produto cadastrado com sucesso!'); location.reload(); } else { alert('❌ Erro ao cadastrar: ' + resultado.error); }
    });

    // Função de Exclusão (MANTIDA)
    window.excluirProduto = async function(codProduto, nomeProduto) {
        if (!confirm(`🗑️ Tem certeza que deseja EXCLUIR o produto "${nomeProduto}" (Código: ${codProduto})?`)) return;
        const resp = await fetch(`excluir_produto.php?codProduto=${codProduto}`, { method: 'GET' });
        const responseText = await resp.text();
        let resultado;
        try { resultado = JSON.parse(responseText); } catch (jsonError) { console.error('Resposta do Servidor:', responseText); return; }
        if (resultado.success) { alert(`✅ Produto ${nomeProduto} excluído com sucesso!`); location.reload(); } else { alert('❌ Erro ao excluir: ' + resultado.error); }
    }

    // Função de Salvamento (MANTIDA)
    window.salvarProduto = async function(codProduto) {
        const row = document.getElementById(`row-${codProduto}`);
        const formData = new FormData();
        
        // Valores que NÃO são editáveis (pegos dos selects/inputs escondidos)
        formData.append('CodProdutoAntigo', row.querySelector('input[name="CodProdutoAntigo"]').value);
        formData.append('Tipo', row.querySelector('.select-tipo').value); 
        formData.append('Formato', row.querySelector('.select-formato').value); 
        formData.append('Produto', row.querySelector('input[name="Produto"].edit-input').value);

        // Valor EDITADO (Fornecedor)
        formData.append('Fornecedor', row.querySelector('.select-fornecedor').value); 

        const resp = await fetch('atualizar_produto.php', { method: 'POST', body: formData });
        const responseText = await resp.text();
        let resultado;
        try { resultado = JSON.parse(responseText); } catch (jsonError) { console.error('Resposta do Servidor:', responseText); return; }
        if (resultado.success) { alert('✅ Fornecedor atualizado com sucesso!'); location.reload(); } else { alert('❌ Erro ao atualizar: ' + resultado.error); }
    }
</script>
</body>
</html>