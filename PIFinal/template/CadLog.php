<?php
// INICIAR SESSÃO
session_start();

// Se já estiver logado, redireciona
if (isset($_SESSION['logado']) && $_SESSION['logado'] === true) {
    header("Location: CadFornecedor.php");
    exit;
}

// ATIVA EXCEÇÕES DO MYSQLI (compatível com CadCad.php)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// INCLUI CONEXÃO
require_once 'conexao.php';

$mensagem_status = "";

// PROCESSA LOGIN
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST['login'], $_POST['senha'])) {

        $login = trim($_POST['login']);  // pode ser email OU cnpj
        $senha_digitada = $_POST['senha'];

        try {
            // Consulta o usuário por EMAIL ou CNPJ
            $stmt = $conn->prepare("
                SELECT id, nome_empresa, cnpj, email, senha 
                FROM Empresas 
                WHERE email = ? OR cnpj = ?
                LIMIT 1
            ");
            
            $stmt->bind_param("ss", $login, $login);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado->num_rows === 1) {

                $usuario = $resultado->fetch_assoc();

                // Comparar senha usando password_verify
                if (password_verify($senha_digitada, $usuario['senha'])) {

                    // Sessão do usuário autenticado
                    $_SESSION['logado'] = true;
                    $_SESSION['id_empresa']  = $usuario['id'];
                    $_SESSION['nome_empresa'] = $usuario['nome_empresa'];

                    header("Location: CadFornecedor.php");
                    exit;

                } else {
                    $mensagem_status = '<div class="alert alert-danger mt-3">Senha incorreta.</div>';
                }

            } else {
                $mensagem_status = '<div class="alert alert-danger mt-3">CNPJ ou Email não encontrado.</div>';
            }

        } catch (mysqli_sql_exception $e) {
            $mensagem_status = '<div class="alert alert-danger mt-3">
                Erro ao processar login: ' . htmlspecialchars($e->getMessage()) . '
            </div>';
        }

        if (isset($stmt)) {
            $stmt->close();
        }

    } else {
        $mensagem_status = '<div class="alert alert-warning mt-3">Preencha todos os campos.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agily Stock - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/CadLog.css">
</head>
<body>
    <div class="text-center container mt-5">
        <h2 class="fw-bold">Bem-vindo ao Agily Stock<br>Gestão de Estoque Ágil e Eficiente!</h2>
        <p class="mt-3">Acesse sua conta para continuar.</p>
        
        <?php echo $mensagem_status; ?>

        <button class="btn btn-custom mt-3" data-bs-toggle="modal" data-bs-target="#modalLogin">
            Fazer login
        </button>

        <!-- MODAL -->
        <div class="modal fade" id="modalLogin" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">

                    <div class="modal-header">
                        <h1 class="modal-title fs-5">Login</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <form method="POST">

                            <div class="mb-3">
                                <input type="text" class="form-control text-center" 
                                    name="login" placeholder="CNPJ ou Email" required>
                            </div>

                            <div class="mb-3">
                                <input type="password" class="form-control text-center" 
                                    name="senha" placeholder="Senha" required>
                            </div>

                            <div class="modal-footer">
                                <button class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                <button type="submit" class="btn btn-primary">Logar</button>
                            </div>

                        </form>
                    </div>

                </div>
            </div>
        </div>

        <p class="mt-3">Ainda não tem conta? <a href="CadCad.php">Cadastre-se</a></p>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
