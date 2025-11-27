<?php
// OBRIGATÓRIO: Iniciar a sessão
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ativa exceções do MySQLi
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// =======================================================================
// FUNÇÃO DE VALIDAÇÃO DE CNPJ
// =======================================================================
function validaCNPJ(string $cnpj): bool
{
    $cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);

    if (strlen($cnpj) != 14 || preg_match('/(\d)\1{13}/', $cnpj)) {
        return false;
    }

    // primeiro dígito
    for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
        $soma += $cnpj[$i] * $j;
        $j = ($j == 2) ? 9 : $j - 1;
    }
    $resto = $soma % 11;
    $digito_1 = ($resto < 2) ? 0 : 11 - $resto;

    // segundo dígito
    for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++) {
        $soma += $cnpj[$i] * $j;
        $j = ($j == 2) ? 9 : $j - 1;
    }
    $resto = $soma % 11;
    $digito_2 = ($resto < 2) ? 0 : 11 - $resto;

    return $cnpj[12] == $digito_1 && $cnpj[13] == $digito_2;
}
// =======================================================================

// INCLUI A CONEXÃO
require_once 'conexao.php';

$mensagem_status = "";
$modal_reabrir = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST['nome_empresa'], $_POST['cnpj'], $_POST['email'], $_POST['senha'])) {

        $nome_empresa = $_POST['nome_empresa'];
        $cnpj_limpo = preg_replace('/[^0-9]/', '', $_POST['cnpj']);
        $email = $_POST['email'];
        $senha_limpa = $_POST['senha'];

        // VALIDAR CNPJ
        if (!validaCNPJ($cnpj_limpo)) {
            $mensagem_status = '<div class="alert alert-danger mt-3" role="alert">
                Erro ao cadastrar: O CNPJ informado não é válido.
            </div>';
            $modal_reabrir = true;
            goto fim;
        }

        $senha_criptografada = password_hash($senha_limpa, PASSWORD_DEFAULT);

        try {

            $stmt = $conn->prepare("INSERT INTO Empresas (nome_empresa, cnpj, email, senha) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nome_empresa, $cnpj_limpo, $email, $senha_criptografada);
            $stmt->execute();

            $mensagem_status = '<div class="alert alert-success mt-3" role="alert">
                Cadastro realizado com sucesso! Você pode fazer login agora.
            </div>';

        } catch (mysqli_sql_exception $e) {

            if (strpos($e->getMessage(), "Duplicate entry") !== false) {
                $mensagem_status = '<div class="alert alert-danger mt-3" role="alert">
                    Erro ao cadastrar: CNPJ ou E-mail já está em uso.
                </div>';
            } else {
                $mensagem_status = '<div class="alert alert-danger mt-3" role="alert">
                    Erro SQL: ' . htmlspecialchars($e->getMessage()) . '
                </div>';
            }

            $modal_reabrir = true;
        }

        if (isset($stmt)) {
            $stmt->close();
        }

    } else {
        $mensagem_status = '<div class="alert alert-warning mt-3" role="alert">
            Erro: Por favor, preencha todos os campos.
        </div>';
        $modal_reabrir = true;
    }
}

fim: 
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Agily Stock - Cadastro</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/CadCad.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
</head>
<body>

<div class="text-center container mt-5">
    <h2 class="fw-bold">Bem-vindo ao Agily Stock<br>Gestão de Estoque Ágil e Eficiente!</h2>
    <p class="mt-3">Cadastre sua empresa para começar!</p>

    <?php echo $mensagem_status; ?>

    <button class="btn btn-custom mt-3" data-bs-toggle="modal" data-bs-target="#exampleModal">
        Cadastrar-se
    </button>

    <div class="modal fade" id="exampleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h1 class="modal-title fs-5">Cadastro</h1>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <form method="POST">
                        <div class="mb-3">
                          <input type="text" class="form-control text-center" name="nome_empresa" placeholder="Nome da Empresa" required>
                        </div>

                        <div class="mb-3">
                          <input type="text" class="form-control text-center" id="CNPJCadastro" name="cnpj" placeholder="CNPJ" maxlength="18" required>
                        </div>

                        <div class="mb-3">
                          <input type="email" class="form-control text-center" name="email" placeholder="E-mail" required>
                        </div>

                        <div class="mb-3">
                          <input type="password" class="form-control text-center" name="senha" placeholder="Senha" required>
                        </div>

                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                            <button type="submit" class="btn btn-primary">Cadastrar</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <p class="mt-3">Já tem conta? <a href="CadLog.php">Faça login</a></p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function(){
    $('#CNPJCadastro').mask('00.000.000/0000-00');

    const reabrirModal = <?php echo $modal_reabrir ? 'true' : 'false'; ?>;
    if (reabrirModal) {
        const modal = new bootstrap.Modal(document.getElementById('exampleModal'));
        modal.show();
    }
});
</script>

</body>
</html>
