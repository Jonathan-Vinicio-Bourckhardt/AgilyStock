<?php
// Inicia a sessão
session_start();

// Inclui o arquivo de conexão
require_once 'conexao.php';

// Define o cabeçalho para retornar JSON
header('Content-Type: application/json');

// Garante que a requisição é um POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Verifica a conexão
    if (!isset($conn) || $conn->connect_error) {
        // Usa $conn se for o nome da variável de conexão
        $conexao->close();
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => 'Falha na conexão com o banco de dados.']));
    }

    // 1. Coleta e sanitiza os dados
    $login_user = $conexao->real_escape_string(trim($_POST['login']));
    $senha_pura = $_POST['senha'];

    // 2. Validação básica
    if (empty($login_user) || empty($senha_pura)) {
        echo json_encode(['success' => false, 'message' => 'Preencha todos os campos.']);
        $conexao->close();
        exit;
    }

    // 3. Prepara a busca (pode ser por CNPJ ou E-mail)
    $sql = "SELECT id, nome_empresa, senha FROM empresas WHERE email = ? OR cnpj = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("ss", $login_user, $login_user);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows == 1) {
        $empresa = $resultado->fetch_assoc();

        // 4. Verifica a senha
        if (password_verify($senha_pura, $empresa['senha'])) {
            // Login bem-sucedido: Cria as variáveis de sessão
            $_SESSION['logado'] = true;
            
            // 🛑 AÇÃO DE CONSISTÊNCIA: Padronizar para 'id_empresa' 🛑
            $_SESSION['id_empresa'] = $empresa['id']; 
            
            $_SESSION['nome_empresa'] = $empresa['nome_empresa'];
            
            echo json_encode(['success' => true, 'message' => 'Login realizado com sucesso!', 'redirect' => 'dashboard.php']); // Redirecionar para o painel
        } else {
            // Senha incorreta
            echo json_encode(['success' => false, 'message' => 'Credenciais inválidas.']);
        }
    } else {
        // Usuário (CNPJ/E-mail) não encontrado
        echo json_encode(['success' => false, 'message' => 'Credenciais inválidas.']);
    }

    // 5. Fecha a conexão
    $stmt->close();
    $conexao->close();

} else {
    // Resposta se não for POST
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
}
?>