<?php
// CadFornecedor.php

// OBRIGATÓRIO: Iniciar a sessão
session_start();

// 🛑 1. VERIFICAÇÃO DE SESSÃO E ID DA EMPRESA 🛑
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['id_empresa'])) {
    header('Location: ../CadLog.php'); // Redireciona para o login se não estiver logado
    exit;
}
$id_empresa_logada = $_SESSION['id_empresa'];
$nome_empresa = $_SESSION['nome_empresa'] ?? "Empresa Desconhecida";

// 2. BLOCO ANTI-CACHE
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 3. INCLUIR O ARQUIVO DE CONEXÃO
include 'conexao.php'; // Assume que este arquivo define $conn

// --- 4. Lógica de Busca e Formatação do CNPJ da Empresa Logada ---
$cnpj_empresa = "CNPJ não encontrado"; // Valor padrão

if (isset($conn)) {
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

// ----------------------------------------------------
// 🛑 NOVIDADE: FUNÇÃO DE VALIDAÇÃO MATEMÁTICA DO CNPJ 🛑
// ----------------------------------------------------
function validaCNPJ($cnpj) {
    // Retira caracteres não numéricos
    $cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);
    
    // Verifica se o número de dígitos é 14
    if (strlen($cnpj) != 14) {
        return false;
    }
    
    // Verifica se todos os dígitos são iguais (ex: 33333333333333)
    // Se for igual, é inválido
    if (preg_match('/(\d)\1{13}/', $cnpj)) {
        return false;
    }
    
    // Valida o primeiro dígito verificador
    for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
        $soma += $cnpj[$i] * $j;
        $j = ($j == 2) ? 9 : $j - 1;
    }
    $resto = $soma % 11;
    if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto)) {
        return false;
    }
    
    // Valida o segundo dígito verificador
    for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++) {
        $soma += $cnpj[$i] * $j;
        $j = ($j == 2) ? 9 : $j - 1;
    }
    $resto = $soma % 11;
    return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
}
// ----------------------------------------------------

// --- 5. Processamento do Cadastro de Novo Fornecedor (AGORA COM VALIDAÇÃO DE CONTATO) ---

$mensagem_status = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['CNPJ_Novo'], $_POST['Fornecedor_Novo'], $_POST['NumContato_Novo'])) {
    
    // Captura e Limpeza dos dados
    $cnpj_novo = preg_replace('/\D/', '', $_POST['CNPJ_Novo']);
    $fornecedor_novo = trim($_POST['Fornecedor_Novo']);
    $contato_novo = preg_replace('/\D/', '', $_POST['NumContato_Novo']);

    if (empty($cnpj_novo) || empty($fornecedor_novo) || empty($contato_novo)) {
        $mensagem_status = '<div class="alert alert-warning mt-3" role="alert">Preencha todos os campos do novo fornecedor.</div>';
    } elseif (!validaCNPJ($cnpj_novo)) {
        $mensagem_status = '<div class="alert alert-danger mt-3 alert-dismissible fade show" role="alert">
            Erro ao cadastrar: CNPJ inválido. Verifique o número digitado.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    } elseif (preg_match('/(\d)\1{9,}/', $contato_novo)) { // <-- NOVO: VALIDAÇÃO DE NÚMERO DE CONTATO COM DÍGITOS REPETIDOS
        $mensagem_status = '<div class="alert alert-danger mt-3 alert-dismissible fade show" role="alert">
            Erro ao cadastrar: Número de Contato inválido ou de teste (dígitos repetidos).
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    } else {
        try {
            
            $sql_insert = "INSERT INTO cadfornecedor (CNPJ, Fornecedor, NumContato, id_empresa) VALUES (?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("sssi", $cnpj_novo, $fornecedor_novo, $contato_novo, $id_empresa_logada);

            if ($stmt_insert->execute()) {
                $mensagem_status = '<div class="alert alert-success mt-3 alert-dismissible fade show" role="alert">
                    Fornecedor cadastrado com sucesso!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>';
            } else {
                // 🛑 Captura o erro 1062 (Duplicidade) 🛑
                if ($conn->errno == 1062) {
                    
                    $duplicidade_encontrada = false;
                    
                    // --- 1. VERIFICAÇÃO DO CNPJ DUPLICADO NA EMPRESA ATUAL ---
                    $sql_check_cnpj = "SELECT 1 FROM cadfornecedor
                                             WHERE id_empresa = ? AND CNPJ = ?";
                    $stmt_check_cnpj = $conn->prepare($sql_check_cnpj);
                    $stmt_check_cnpj->bind_param("is", $id_empresa_logada, $cnpj_novo);
                    $stmt_check_cnpj->execute();
                    
                    if ($stmt_check_cnpj->get_result()->num_rows > 0) {
                        $mensagem_status = '<div class="alert alert-danger mt-3 alert-dismissible fade show" role="alert">
                            Erro ao cadastrar: CNPJ já existe.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
                        $duplicidade_encontrada = true;
                    }
                    $stmt_check_cnpj->close();
                    
                    // --- 2. VERIFICAÇÃO DO NÚMERO DE CONTATO DUPLICADO NA EMPRESA ATUAL ---
                    if (!$duplicidade_encontrada) {
                        $sql_check_contato = "SELECT 1 FROM cadfornecedor
                                                    WHERE id_empresa = ? AND NumContato = ?";
                        $stmt_check_contato = $conn->prepare($sql_check_contato);
                        $stmt_check_contato->bind_param("is", $id_empresa_logada, $contato_novo);
                        $stmt_check_contato->execute();

                        if ($stmt_check_contato->get_result()->num_rows > 0) {
                            $mensagem_status = '<div class="alert alert-danger mt-3 alert-dismissible fade show" role="alert">
                                Erro ao cadastrar: Número de Contato já existe.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
                            $duplicidade_encontrada = true;
                        }
                        $stmt_check_contato->close();
                    }

                    // Mensagem genérica se a duplicidade ocorreu, mas o campo exato não foi isolado pelo PHP
                    if (!$duplicidade_encontrada) {
                           $mensagem_status = '<div class="alert alert-danger mt-3 alert-dismissible fade show" role="alert">
                                Erro ao cadastrar: Dados duplicados não identificados. Verifique o CNPJ e o Número de Contato.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
                    }
                    
                } else {
                    // Outros erros de SQL
                    $mensagem_status = '<div class="alert alert-danger mt-3 alert-dismissible fade show" role="alert">
                        Erro ao cadastrar: ' . $stmt_insert->error . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
                }
            }
            $stmt_insert->close();

        } catch (Exception $e) {
            $mensagem_status = '<div class="alert alert-danger mt-3 alert-dismissible fade show" role="alert">
                Erro crítico: ' . $e->getMessage() . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
        }
    }
}
// --- Fim do Processamento ---
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agily Stock - Fornecedores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/CadFornecedor.css">
    
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
            <img src="./img/logo.png" alt="Agily Stock Logo" class="logo-img">
            <h4 class="logo-text">Agily Stock</h4>
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
    <h2 class="mb-4">Cadastro de Fornecedor</h2>
    
    <?php echo $mensagem_status; // Exibe mensagem de status aqui ?>

    <form id="form-cadastro-fornecedor" method="POST">
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
                    <td><input name="CNPJ_Novo" type="text" maxlength="18" class="form-control" placeholder="00.000.000/0000-00" required></td>
                    <td><input name="Fornecedor_Novo" type="text" maxlength="100" class="form-control" placeholder="Nome da Empresa LTDA" required></td>
                    <td><input name="NumContato_Novo" type="text" maxlength="15" class="form-control" placeholder="(XX) XXXXX-XXXX" required></td>
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
                    // Reabre a conexão se ela foi fechada no processamento POST (o que não deve acontecer, mas é bom garantir)
                    if (!isset($conn) || !$conn->ping()) {
                        include 'conexao.php';
                    }

                    if (isset($conn) && $conn->ping()) {
                            $sql = "SELECT CNPJ, Fornecedor, NumContato
                                    FROM cadfornecedor
                                    WHERE id_empresa = ?
                                    ORDER BY Fornecedor ASC";

                        $stmt = $conn->prepare($sql);
                        
                        if ($stmt) {
                            $stmt->bind_param("i", $id_empresa_logada);
                            $stmt->execute();
                            $resultado = $stmt->get_result();

                            if ($resultado->num_rows > 0) {
                                while ($linha = $resultado->fetch_assoc()) {
                                    $cnpj = $linha['CNPJ'];
                                    $fornecedor = htmlspecialchars($linha['Fornecedor']);
                                    $contato = $linha['NumContato'];

                                    // Lógica de Limite de Caracteres para exibição
                                    $limite_caracteres = 30;
                                    $fornecedor_exibir = (mb_strlen($fornecedor) > $limite_caracteres) ? mb_substr($fornecedor, 0, $limite_caracteres) . '...' : $fornecedor;
                                    
                                    // Formatação do CNPJ para exibição
                                    $cnpj_exibir = (strlen($cnpj) == 14) ? substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2) : $cnpj;
                                    
                                    // Formatação do Contato para exibição
                                    $contato_exibir = $contato;
                                    if (strlen($contato) == 11) {
                                        $contato_exibir = '(' . substr($contato, 0, 2) . ') ' . substr($contato, 2, 5) . '-' . substr($contato, 7, 4);
                                    } elseif (strlen($contato) == 10) {
                                        $contato_exibir = '(' . substr($contato, 0, 2) . ') ' . substr($contato, 2, 4) . '-' . substr($contato, 6, 4);
                                    }
                                    
                                    // HTML da Linha da Tabela
                                    echo "<tr id='row-{$cnpj}'>
                                        <td>
                                            <span class='view-cnpj'>{$cnpj_exibir}</span>
                                            <input name='CNPJ' type='hidden' value='{$cnpj}'>
                                        </td>
                                        <td>
                                            <span class='view-fornecedor' title='{$fornecedor}'>{$fornecedor_exibir}</span>
                                            <input name='Fornecedor' type='text' class='form-control edit-input' value='{$fornecedor}' style='display:none;' required>
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
                            $stmt->close();
                        } else {
                             // Tratar erro de preparação da query
                             echo "<tr><td colspan='4' class='text-center'>Erro ao preparar a consulta de fornecedores.</td></tr>";
                        }
                    } else {
                           echo "<tr><td colspan='4' class='text-center'>Erro de conexão com o banco de dados.</td></tr>";
                    }
                    
                    if (isset($conn)) {
                        $conn->close();
                    }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

<script src="js/CadFornecedor.js"></script>

</body>
</html>