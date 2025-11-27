<?php
// OBRIGATÓRIO: Iniciar a sessão para gerenciar o login do usuário
session_start();

// Opcional: Redirecionar se o usuário JÁ estiver logado
if (isset($_SESSION['logado']) && $_SESSION['logado'] === true) {
    // Redireciona para a página principal do sistema
    header("Location: CadFornecedor.php");
    exit;
}

// Inclui o arquivo de conexão. Certifique-se de que 'conexao.php' está configurado corretamente.
require_once 'conexao.php'; 

$mensagem_status = "";

// --- LÓGICA DE PROCESSAMENTO DO FORMULÁRIO ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Verifica se os campos obrigatórios foram preenchidos
    if (isset($_POST['login'], $_POST['senha']) && !empty($_POST['login']) && !empty($_POST['senha'])) {
        
        // Limpa e sanitiza as entradas (previne espaços e caracteres indesejados no login)
        $login = trim($_POST['login']); // Pode ser CNPJ ou Email
        $senha_digitada = $_POST['senha']; // Senha bruta, ainda não verificada

        try {
            // 2. Prepara a instrução SQL usando prepared statements
            // Busca o usuário pelo Email OU CNPJ
            $sql = "SELECT id, nome_empresa, senha FROM Empresas WHERE email = ? OR cnpj = ?";
            $stmt = $conn->prepare($sql);
            
            // Liga os parâmetros (s: string)
            $stmt->bind_param("ss", $login, $login);

            // 3. Executa a busca
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado->num_rows === 1) {
                // Usuário encontrado
                $usuario = $resultado->fetch_assoc();
                $senha_hash = $usuario['senha'];

                // 4. VERIFICAÇÃO DE SENHA com hash seguro
                if (password_verify($senha_digitada, $senha_hash)) {
                    // Senha correta! Inicia a sessão
                    $_SESSION['logado'] = true; 
                    $_SESSION['id_empresa'] = $usuario['id'];
                    $_SESSION['nome_empresa'] = $usuario['nome_empresa'];
                    
                    // 5. REDIRECIONAMENTO para a página principal
                    $stmt->close();
                    $conn->close();
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
        } catch (Exception $e) {
            // Em caso de erro na query ou conexão
            error_log("Erro de Login: " . $e->getMessage());
            $mensagem_status = '<div class="alert alert-danger mt-3" role="alert">Ocorreu um erro interno. Tente novamente mais tarde.</div>';
        }
    } else {
        $mensagem_status = '<div class="alert alert-warning mt-3" role="alert">Erro: Por favor, preencha todos os campos de login.</div>';
    }
}
// Opcional: Fecha a conexão se não houver redirecionamento
if (isset($conn)) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agile Stock - Login</title>
    <!-- Inclui o Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Inclui seu CSS customizado -->
    <link rel="stylesheet" href="css/CadLog.css">
    
    <style>
        /* Estilo para garantir que o modal não apareça após um erro se não estiver configurado para isso no JS */
        /* Se houver mensagem de erro, o modal pode ser exibido automaticamente via JS, mas no PHP puro, exibimos a mensagem na página */
    </style>
</head>
<body>
    <div class="text-center container mt-5">
        <h2 class="fw-bold">Bem-vindo ao Agile Stock -<br>Gestão de Estoque Ágil e Eficiente!</h2>
        <p class="mt-3">Faça login para começar a gerenciar seu estoque agora!</p>
        
        <?php echo $mensagem_status; // Exibe mensagem de status aqui ?>

        <div class="ModalCadastro">
            <!-- Botão que abre o modal de login -->
            <button class="btn btn-custom mt-3" data-bs-toggle="modal" data-bs-target="#modalLogin">
                Fazer login no Agile Stock
            </button>

            <!-- Modal de Login -->
            <div class="modal fade" id="modalLogin" tabindex="-1" aria-labelledby="modalLoginLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="modalLoginLabel">Login</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        
                        <div class="modal-body">
                            <!-- O formulário envia os dados para a própria página (cadlog.php) -->
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

    <!-- Inclui o Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (!empty($mensagem_status) && strpos($mensagem_status, 'alert-danger') !== false): ?>
    <!-- Script para exibir o modal automaticamente se houver erro de login -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modal = new bootstrap.Modal(document.getElementById('modalLogin'));
            // Remove a classe 'show' para evitar conflitos na inicialização
            document.getElementById('modalLogin').classList.remove('show');
        });
    </script>
    <?php endif; ?>
</body>
</html>