<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'conexao.php'; 
// ...?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agile Stock - Fornecedores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/CadFornecedor.css">
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
    <h2 class="mb-4">Cadastro de Fornecedor</h2>

    <form id="form-cadastro-fornecedor">
        <table class="table table-bordered bg-white">
            <thead>
                <tr>
                    <th>CNPJ</th>
                    <th>Fornecedor</th>
                    <th>Num.Contato</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input name="CNPJ" type="text" maxlength="18" class="form-control" placeholder="XX.XXX.XXX/XXXX-XX" required></td>
                    <td><input name="Fornecedor" type="text" maxlength="100" class="form-control" placeholder="Empresa de Alimentos LTDA" required></td>
                    <td><input name="NumContato" type="text" maxlength="15" class="form-control" placeholder="(XX) XXXXX-XXXX" required></td>
                    <td><button type="submit" class="btn btn-success w-100">Cadastrar</button></td>
                </tr>
            </tbody>
        </table>
    </form>

    <h4 class="mt-4">Fornecedores</h4>

    <div class="history-container">
        <table class="table table-striped history-table">
            <thead>
                <tr>
                    <th>CNPJ</th>
                    <th>Fornecedor</th>
                    <th>Num.Contato</th>
                    <th>Ações</th> 
                </tr>
            </thead>
            <tbody>
                <?php
                    $sql = "SELECT * FROM cadfornecedor ORDER BY Fornecedor ASC";
                    $resultado = $conn->query($sql);

                    if ($resultado->num_rows > 0) {
                        while ($linha = $resultado->fetch_assoc()) {
                            $cnpj = $linha['CNPJ'];
                            $fornecedor = htmlspecialchars($linha['Fornecedor']);
                            $contato = $linha['NumContato'];

                            // 🛑 NOVO: LÓGICA DE LIMITE DE CARACTERES (30)
                            $limite_caracteres = 30;
                            if (strlen($fornecedor) > $limite_caracteres) {
                                $fornecedor_exibir = mb_substr($fornecedor, 0, $limite_caracteres) . '...';
                            } else {
                                $fornecedor_exibir = $fornecedor;
                            }
                            // 🛑 FIM DA LÓGICA DE LIMITE

                            // 🎯 Formatação do CNPJ para exibição no histórico
                            if (strlen($cnpj) == 14) {
                                $cnpj_exibir = substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
                            } else {
                                $cnpj_exibir = $cnpj;
                            }

                            // 🎯 Formatação do Contato para exibição no histórico (máscara flexível)
                            if (strlen($contato) == 11) {
                                $contato_exibir = '(' . substr($contato, 0, 2) . ') ' . substr($contato, 2, 5) . '-' . substr($contato, 7, 4);
                            } elseif (strlen($contato) == 10) {
                                $contato_exibir = '(' . substr($contato, 0, 2) . ') ' . substr($contato, 2, 4) . '-' . substr($contato, 6, 4);
                            } else {
                                $contato_exibir = $contato;
                            }


                            echo "<tr id='row-{$cnpj}'>
                                
                                <td>
                                    <span class='view-cnpj'>{$cnpj_exibir}</span>
                                    <input name='CNPJ' type='hidden' value='{$cnpj}'>
                                </td>
                                
                                <td>
                                    <span class='view-fornecedor'>{$fornecedor_exibir}</span> <input name='Fornecedor' type='text' class='form-control edit-input' value='{$fornecedor}' style='display:none;' required>
                                </td>
                                
                                <td>
                                    <span class='view-contato'>{$contato_exibir}</span>
                                    <input name='NumContato' type='text' class='form-control edit-input' value='{$contato}' style='display:none;' required>
                                </td>
                                
                                <td>
                                    <button type='button' class='btn btn-sm btn-primary btn-edit' onclick='toggleEdit(\"{$cnpj}\")'>
                                        <i class='bi bi-pencil'></i>
                                    </button>
                                    
                                    <button type='button' class='btn btn-sm btn-success btn-save' onclick='salvarFornecedor(\"{$cnpj}\")' style='display:none;'>
                                        <i class='bi bi-save'></i>
                                    </button>
                                    
                                    <button type='button' class='btn btn-sm btn-danger btn-delete' onclick='excluirFornecedor(\"{$cnpj}\", \"{$fornecedor}\")'>
                                        <i class='bi bi-trash'></i>
                                    </button>
                                    
                                    <button type='button' class='btn btn-sm btn-secondary btn-cancel' onclick='toggleEdit(\"{$cnpj}\")' style='display:none;'>
                                        <i class='bi bi-x-lg'></i>
                                    </button>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' class='text-center'>Nenhum fornecedor cadastrado.</td></tr>";
                    }

                    $conn->close();
                ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

<script>
// FUNÇÃO DE CADASTRO (PARA NÃO REDIRECIONAR)
document.getElementById('form-cadastro-fornecedor').addEventListener('submit', async (e) => {
    e.preventDefault(); 
    const formData = new FormData(e.target);

    // Remove a máscara para obter APENAS os números
    const cnpj = $('input[name="CNPJ"]').val().replace(/\D/g, '');
    const numContato = $('input[name="NumContato"]').val().replace(/\D/g, '');

    // 🛑 VALIDAÇÃO DE TAMANHO FIXO PARA CNPJ
    if (cnpj.length !== 14) {
        alert('❌ Erro: O CNPJ deve conter exatamente 14 dígitos numéricos.');
        return; 
    }
    
    // 🛑 VALIDAÇÃO DE TAMANHO FIXO PARA NUM. CONTATO
    if (numContato.length !== 10 && numContato.length !== 11) {
        alert('❌ Erro: O Número de Contato deve conter 10 (fixo) ou 11 (celular) dígitos numéricos.');
        return; 
    }


    formData.set('CNPJ', cnpj);
    formData.set('NumContato', numContato);

    const resp = await fetch('inserir_fornecedor.php', {
        method: 'POST',
        body: formData
    });

    const responseText = await resp.text();
    let resultado;
    try {
        resultado = JSON.parse(responseText);
    } catch (jsonError) {
        alert('❌ Erro no servidor: A resposta não é JSON. Verifique se o PHP retornou HTML ou um erro fatal.');
        console.error('Resposta do Servidor:', responseText);
        return;
    }

    if (resultado.success) {
        alert('✅ Fornecedor cadastrado com sucesso!');
        location.reload(); 
    } else {
        alert('❌ Erro ao cadastrar: ' + resultado.error);
        console.error('Erro de Inserção:', resultado.error);
    }
});


// 🚨 FUNÇÃO DE EXCLUSÃO (RESOLVE O PROBLEMA DA TELA PRETA AO EXCLUIR)
async function excluirFornecedor(cnpj, nomeFornecedor) {
    if (!confirm(`🗑️ Tem certeza que deseja EXCLUIR o fornecedor "${nomeFornecedor}" (CNPJ: ${cnpj})? Esta ação EXCLUIRÁ TODOS OS PRODUTOS vinculados, estoques e movimentos.`)) {
        return;
    }

    // ✅ O fetch envia a requisição sem sair da página
    const resp = await fetch(`excluir_fornecedor.php?CNPJ=${cnpj}`, {
        method: 'GET'
    });
    
    const responseText = await resp.text();
    
    let resultado;
    try {
        resultado = JSON.parse(responseText);
    } catch (jsonError) {
        alert('❌ Erro no servidor: A resposta não é JSON. Verifique o excluir_fornecedor.php.');
        console.error('Resposta do Servidor:', responseText);
        return; 
    }

    if (resultado.success) {
        alert(`✅ Fornecedor ${nomeFornecedor} e todos os produtos vinculados foram excluídos com sucesso!`);
        location.reload(); // Recarrega para atualizar a lista
    } else {
        alert('❌ Erro ao excluir: ' + resultado.error);
        console.error('Erro de Exclusão:', resultado.error);
    }
}


// FUNÇÕES DE EDIÇÃO INLINE 
function toggleEdit(cnpj) {
    const row = document.getElementById(`row-${cnpj}`);
    
    row.querySelectorAll('.view-fornecedor, .view-contato').forEach(span => {
        span.style.display = span.style.display === 'none' ? 'inline' : 'none';
    });
    
    row.querySelectorAll('.edit-input').forEach(input => {
        input.style.display = input.style.display === 'none' ? 'block' : 'none'; 
    });

    row.querySelector('.btn-edit').style.display = row.querySelector('.btn-edit').style.display === 'none' ? 'inline-block' : 'none';
    row.querySelector('.btn-delete').style.display = row.querySelector('.btn-delete').style.display === 'none' ? 'inline-block' : 'none';
    row.querySelector('.btn-save').style.display = row.querySelector('.btn-save').style.display === 'none' ? 'inline-block' : 'none';
    row.querySelector('.btn-cancel').style.display = row.querySelector('.btn-cancel').style.display === 'none' ? 'inline-block' : 'none';
}

// 🛑 FUNÇÃO SALVAR FORNECEDOR CORRIGIDA 🛑
async function salvarFornecedor(cnpj) {
    // Busca a linha da tabela (TR) que contém os inputs de edição
    const row = document.getElementById(`row-${cnpj}`);
    
    // Busca os inputs VISÍVEIS (os inputs de edição) dentro da linha
    const fornecedorInput = row.querySelector('input[name="Fornecedor"].edit-input');
    const contatoInput = row.querySelector('input[name="NumContato"].edit-input');
    
    // O CNPJ é um input HIDDEN que está dentro da linha (row)
    const cnpjInputHidden = row.querySelector('input[name="CNPJ"][type="hidden"]'); 

    // 🛑 VALIDAÇÃO DE NULIDADE (CRÍTICO)
    if (!cnpjInputHidden || !fornecedorInput || !contatoInput) {
        console.error('Erro de DOM: Campos de edição não encontrados. CNPJ: ' + cnpj);
        alert('❌ Erro: Não foi possível localizar os campos para salvar a edição. Verifique o console.');
        return;
    }
    
    // Preparação dos dados
    const numContato = contatoInput.value.replace(/\D/g, ''); // Limpa o contato
    
    // 🛑 VALIDAÇÃO DE TAMANHO FIXO PARA NUM. CONTATO
    if (numContato.length !== 10 && numContato.length !== 11) {
        alert('❌ Erro: O Número de Contato deve conter 10 (fixo) ou 11 (celular) dígitos numéricos.');
        return; 
    }
    
    // Monta o FormData manualmente
    const formData = new FormData();
    formData.append('CNPJ', cnpjInputHidden.value); // Valor do input hidden
    formData.append('Fornecedor', fornecedorInput.value); 
    formData.append('NumContato', numContato); // Valor limpo para o DB

    const resp = await fetch('atualizar_fornecedor.php', {
        method: 'POST',
        body: formData
    });
    
    const responseText = await resp.text();
    
    let resultado;
    try {
        resultado = JSON.parse(responseText);
    } catch (jsonError) {
        alert('❌ Erro no servidor ao salvar: A resposta não é JSON. Verifique o PHP.');
        console.error('Resposta do Servidor:', responseText);
        return; 
    }

    if (resultado.success) {
        alert('✅ Fornecedor atualizado com sucesso!');
        location.reload();
    } else {
        alert('❌ Erro ao atualizar: ' + resultado.error);
        console.error('Erro de Atualização:', resultado.error);
    }
}


// 🚀 APLICAÇÃO DAS MÁSCARAS (jQuery)
$(document).ready(function() {
    // 1. CNPJ - Máscara reversa para CNPJ (14 dígitos fixos)
    $('input[name="CNPJ"]').mask('00.000.000/0000-00', {reverse: true});
    
    // 2. Telefone/Celular (Máscara Flexível)
    var maskBehavior = function (val) {
      return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
    },
    options = {
        onKeyPress: function(val, e, field, options) {
          field.mask(maskBehavior.apply({}, arguments), options);
        }
    };
    
    // Aplica a máscara flexível para os campos de NumContato
    $('input[name="NumContato"]').mask(maskBehavior, options);
    $('input[name="NumContato"].edit-input').mask(maskBehavior, options);
});
</script>
</body>
</html>