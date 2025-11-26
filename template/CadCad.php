<?php
// OBRIGATÓRIO: Iniciar a sessão (Embora esta seja a tela de cadastro, é bom ter em todas)
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. INCLUSÃO DA CONEXÃO
require_once 'conexao.php'; 

// 2. PROCESSAMENTO DO FORMULÁRIO (Apenas se o método for POST)
$mensagem_status = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Verifica se todos os campos necessários estão presentes
    if (isset($_POST['nome_empresa'], $_POST['cnpj'], $_POST['email'], $_POST['senha'])) {
        
        $nome_empresa = $_POST['nome_empresa'];
        $cnpj = $_POST['cnpj'];
        $email = $_POST['email'];
        $senha_limpa = $_POST['senha']; 

        // CRIPTOGRAFIA DE SENHA
        $senha_criptografada = password_hash($senha_limpa, PASSWORD_DEFAULT);

        // Prepara a instrução SQL para segurança
        // 🛑 CORREÇÃO/AJUSTE: A tabela 'empresas' deve ter um ID primário auto-incrementado.
        // Assumimos que a coluna id_empresa é redundante ou é o ID primário.
        // Se a coluna id_empresa que você adicionou for o AUTO_INCREMENT, este INSERT funciona.
        // Se a tabela tiver duas colunas de ID, isso pode gerar problemas.
        $stmt = $conn->prepare("INSERT INTO Empresas (nome_empresa, cnpj, email, senha) VALUES (?, ?, ?, ?)");
        
        // Liga os parâmetros
        $stmt->bind_param("ssss", $nome_empresa, $cnpj, $email, $senha_criptografada);

        // Executa a inserção
        if ($stmt->execute()) {
            // Sucesso
            $mensagem_status = '<div class="alert alert-success mt-3" role="alert">Cadastro realizado com sucesso! Você pode fazer login agora.</div>';
            
            // Opcional: Redirecionar em vez de mostrar a mensagem na mesma página
            // header("Location: CadLog.php?cadastro=sucesso"); 
            // exit();
        } else {
            // Erro
            // O erro mais comum aqui é se a coluna CNPJ ou Email for UNIQUE
            $erro_msg = $stmt->error;
            if (strpos($erro_msg, 'Duplicate entry') !== false) {
                 $mensagem_status = '<div class="alert alert-danger mt-3" role="alert">Erro ao cadastrar: CNPJ ou E-mail já está em uso.</div>';
            } else {
                 $mensagem_status = '<div class="alert alert-danger mt-3" role="alert">Erro ao cadastrar: ' . $erro_msg . '</div>';
            }
        }

        $stmt->close();
    } else {
        $mensagem_status = '<div class="alert alert-warning mt-3" role="alert">Erro: Por favor, preencha todos os campos do formulário.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Agile Stock - Cadastro</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Mantenha o link para o seu CSS personalizado -->
  <link rel="stylesheet" href="css/CadCad.css"> 
</head>
<body>
  <div class="text-center container mt-5">
    <h2 class="fw-bold">Bem-vindo ao Agile Stock -<br>Gestão de Estoque Ágil e Eficiente!</h2>
    <p class="mt-3">Cadastre-se para começar a gerenciar seu estoque agora!</p>
    
    <?php echo $mensagem_status; // Exibe mensagem de status aqui ?>

    <div class="ModalCadastro">
      <button class="btn btn-custom mt-3" data-bs-toggle="modal" data-bs-target="#exampleModal">
        Cadastrar-se no Agile Stock
      </button>

      <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            
            <div class="modal-header">
              <h1 class="modal-title fs-5" id="exampleModalLabel">Cadastro</h1>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
              <form method="POST">
                <div class="mb-3">
                  <input type="text" class="form-control text-center" id="NomeCadastro" name="nome_empresa" placeholder="Nome da Empresa" maxlength="50" required>
                </div>

                <div class="mb-3">
                  <input type="text" class="form-control text-center" id="CNPJCadastro" name="cnpj" placeholder="CNPJ" maxlength="14" required>
                </div>

                <div class="mb-3">
                  <input type="email" class="form-control text-center" id="EmailCadastro" name="email" placeholder="E-mail" maxlength="50" required>
                </div>

                <div class="mb-3">
                  <input type="password" class="form-control text-center" id="SenhaCadastro" name="senha" placeholder="Senha" maxlength="20" required>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                  <button type="submit" class="btn btn-primary">Cadastrar</button>
                </div>
              </form>
            </div>

          </div>
        </div>
      </div> 
    </div>
    
    <p class="mt-3">Já tem conta? <a href="CadLog.php">Faça Login</a></p>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>