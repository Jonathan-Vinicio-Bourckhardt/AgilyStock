<?php
// OBRIGATÓRIO: Iniciar a sessão para gerenciar o login do usuário
session_start();

// Opcional: Redirecionar se o usuário já estiver logado
if (isset($_SESSION['logado']) && $_SESSION['logado'] === true) {
    // Redireciona para o novo destino: CadFornecedor.php
    header("Location: CadFornecedor.php");
    exit;
}

// Inclui o arquivo de conexão
require_once 'conexao.php'; 

$mensagem_status = "";

// Verifica se o formulário foi submetido
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (isset($_POST['login'], $_POST['senha'])) {
        
        $login = $_POST['login']; // Pode ser CNPJ ou Email
        $senha_digitada = $_POST['senha']; 

        // 1. Prepara a instrução SQL para buscar o usuário pelo Email OU CNPJ
        $stmt = $conn->prepare("SELECT id, nome_empresa, senha FROM Empresas WHERE email = ? OR cnpj = ?");
        
        // Liga os parâmetros (s: string)
        $stmt->bind_param("ss", $login, $login);

        // Executa a busca
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            // Usuário encontrado
            $usuario = $resultado->fetch_assoc();
            $senha_hash = $usuario['senha'];

            // 2. VERIFICAÇÃO DE SENHA COM password_verify()
            if (password_verify($senha_digitada, $senha_hash)) {
                // Senha correta! Inicia a sessão
                $_SESSION['logado'] = true;
                $_SESSION['id_empresa'] = $usuario['id'];
                $_SESSION['nome_empresa'] = $usuario['nome_empresa'];
                
                // 3. REDIRECIONAMENTO CORRIGIDO:
                header("Location: CadFornecedor.php");
                exit();
            } else {
                // Senha incorreta
                $mensagem_status = '<div class="alert alert-danger mt-3" role="alert">Erro: Senha incorreta.</div>';
            }
        } else {
            // Usuário não encontrado
            $mensagem_status = '<div class="alert alert-danger mt-3" role="alert">Erro: CNPJ ou Email não encontrado.</div>';
        }

        $stmt->close();
    } else {
        $mensagem_status = '<div class="alert alert-warning mt-3" role="alert">Erro: Por favor, preencha todos os campos de login.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Agile Stock - Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/CadLog.css">
</head>
<body>
  <div class="text-center container mt-5">
    <h2 class="fw-bold">Bem-vindo ao Agile Stock -<br>Gestão de Estoque Ágil e Eficiente!</h2>
    <p class="mt-3">Faça login para começar a gerenciar seu estoque agora!</p>
    
    <?php echo $mensagem_status; // Exibe mensagem de status aqui ?>

    <div class="ModalCadastro">
      <button class="btn btn-custom mt-3" data-bs-toggle="modal" data-bs-target="#modalLogin">
        Fazer login no Agile Stock
      </button>

      <div class="modal fade" id="modalLogin" tabindex="-1" aria-labelledby="modalLoginLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            
            <div class="modal-header">
              <h1 class="modal-title fs-5" id="modalLoginLabel">Login</h1>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body">
                              <form id="formLogin" method="POST">
                <div class="mb-3">
                  <input type="text" class="form-control text-center" id="CNPJEmail" name="login" placeholder="CNPJ ou Email" maxlength="50" required>
                </div>

                <div class="mb-3">
                  <input type="password" class="form-control text-center" id="SenhaLogin" name="senha" placeholder="Senha" maxlength="20" required>
                </div>
              
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                  <button type="submit" class="btn btn-primary" id="btnLogar">Logar</button>
                </div>
                
                </form>
            </div>

          </div>
        </div>
      </div> 
    </div> 
    
    <p class="mt-3">Ainda não tem conta? <a href="CadCad.php">Cadastre-se</a></p>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
