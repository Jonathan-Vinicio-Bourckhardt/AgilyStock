<?php
// OBRIGATÓRIO: Iniciar a sessão (necessário para o login automático)
session_start();

// Inclui o arquivo de conexão, que está na mesma pasta
// Alterado para usar o nome de variável padrão $conn para consistência
require_once 'conexao.php';

// Define o cabeçalho para retornar JSON
header('Content-Type: application/json');

// Função para retornar erro e fechar a conexão
function responseError($conn, $msg, $httpCode = 200) {
    http_response_code($httpCode);
    if ($conn) {
        @$conn->close();
    }
    die(json_encode(['success' => false, 'message' => $msg]));
}


// Garante que a requisição é um POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Verifica a conexão (usando o nome da variável esperado: $conn)
    if (!isset($conn) || $conn->connect_error) {
        responseError(null, "Falha na conexão com o banco de dados.", 500);
    }

    // 1. Verifica se todos os campos foram enviados
    if (
        !isset($_POST['nome']) ||
        !isset($_POST['cnpj']) ||
        !isset($_POST['email']) ||
        !isset($_POST['senha'])
    ) {
        responseError($conn, 'Dados incompletos recebidos.');
    }
    
    // 2. Coleta e sanitiza os dados
    $nome_empresa = $conn->real_escape_string(trim($_POST['nome']));
    $cnpj = $conn->real_escape_string(preg_replace('/[^0-9]/', '', $_POST['cnpj'])); 
    $email = $conn->real_escape_string(trim($_POST['email']));
    $senha_pura = $_POST['senha'];

    // 3. Validação Server-side (Repetição do JS para segurança)
    if (empty($nome_empresa) || empty($cnpj) || empty($email) || empty($senha_pura) || strlen($cnpj) != 14 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        responseError($conn, 'Dados inválidos ou incompletos.');
    }

    // 4. Verifica se CNPJ ou E-mail já existem
    $stmt = $conn->prepare("SELECT id FROM empresas WHERE cnpj = ? OR email = ?");
    
    if (!$stmt) {
        responseError($conn, "Erro de preparação SQL (Consulta de Existência): " . $conn->error);
    }
    
    $stmt->bind_param("ss", $cnpj, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        responseError($conn, 'CNPJ ou E-mail já cadastrados.');
    }
    $stmt->close();

    // 5. Hash da senha e Inserção
    $senha_hash = password_hash($senha_pura, PASSWORD_DEFAULT);

    $sql = "INSERT INTO empresas (nome_empresa, cnpj, email, senha) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        responseError($conn, "Erro de preparação SQL (Inserção): " . $conn->error);
    }

    $stmt->bind_param("ssss", $nome_empresa, $cnpj, $email, $senha_hash);

    if ($stmt->execute()) {
        // 🛑 AÇÃO DE SEGURANÇA: Login automático após o cadastro 🛑
        
        // Obter o ID da empresa recém-criada
        $id_empresa_cadastrada = $conn->insert_id;
        
        $_SESSION['logado'] = true;
        $_SESSION['id_empresa'] = $id_empresa_cadastrada;
        $_SESSION['nome_empresa'] = $nome_empresa;

        $stmt->close();
        $conn->close();
        
        echo json_encode(['success' => true, 'message' => 'Cadastro realizado com sucesso!', 'redirect' => true]);
    } else {
        $stmt->close();
        responseError($conn, 'Erro ao cadastrar. Tente novamente.');
    }

} else {
    responseError(null, 'Método não permitido.', 405);
}
?>