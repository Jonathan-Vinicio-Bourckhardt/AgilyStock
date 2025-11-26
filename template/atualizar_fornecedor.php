<?php
// OBRIGATÓRIO: Iniciar a sessão para que $_SESSION['id_empresa'] funcione
session_start();

// Define que a resposta será JSON, e nada mais.
header('Content-Type: application/json; charset=utf-8');

// Função de saída de erro, garantindo retorno JSON em caso de falha.
function responseError($conn, $msg) {
    if ($conn) {
        $conn->close();
    }
    // Usa die() para parar a execução e garantir que apenas o JSON seja enviado
    die(json_encode(['success' => false, 'error' => $msg]));
}

// 🛑 Novo: Verificar se o usuário está logado e obter o ID da empresa.
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['id_empresa'])) {
    responseError(null, "Acesso negado. O usuário deve estar logado.");
}
$id_empresa_logada = $_SESSION['id_empresa'];
// 🛑 Fim da verificação 🛑

// Inclui a conexão
include 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responseError($conn, "Método de requisição inválido.");
}

if (isset($_POST['CNPJ']) && isset($_POST['Fornecedor']) && isset($_POST['NumContato'])) {
    
    // 1. Limpeza dos dados
    $CNPJ = preg_replace('/\D/', '', $_POST['CNPJ']); // Remove todos os não-dígitos
    $Fornecedor = trim($_POST['Fornecedor']);
    $NumContato = trim($_POST['NumContato']);

    // 🛑 INÍCIO DA NOVA VALIDAÇÃO
    if (empty($Fornecedor)) {
        responseError($conn, "O campo 'Fornecedor' não pode ser deixado em branco. Por favor, preencha-o.");
    }

    // Se o 'NumContato' também não puder ser em branco:
    if (empty($NumContato)) {
        responseError($conn, "O campo 'Número de Contato' não pode ser deixado em branco. Por favor, preencha-o.");
    }
    // 🛑 FIM DA NOVA VALIDAÇÃO
    
    // 2. Preparação do SQL
    // 🛑 CORREÇÃO: Adicionando filtro WHERE id_empresa = ? 🛑
    $sql = "UPDATE cadfornecedor SET Fornecedor = ?, NumContato = ? WHERE CNPJ = ? AND id_empresa = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        responseError($conn, "Erro de preparação SQL: " . $conn->error);
    }
    
    // 3. Vincula os parâmetros: 3 strings (s) e 1 inteiro (i)
    // 🛑 CORREÇÃO: Adicionando 'i' para o id_empresa_logada 🛑
    $stmt->bind_param("sssi", $Fornecedor, $NumContato, $CNPJ, $id_empresa_logada);

    if ($stmt->execute()) {
        
        // 4. Verificação de linhas afetadas
        $affected_rows = $stmt->affected_rows;
        
        if ($affected_rows > 0) {
            
            $stmt->close();
            $conn->close();
            // RETORNO JSON DE SUCESSO
            echo json_encode(['success' => true, 'message' => 'Fornecedor atualizado com sucesso!']);
            exit;
            
        } else {
            // 🛑 CORREÇÃO NO CHECK: Adicionando filtro id_empresa para verificar a existência 🛑
            $check_sql = "SELECT COUNT(*) FROM cadfornecedor WHERE CNPJ = ? AND id_empresa = ?";
            $check_stmt = $conn->prepare($check_sql);
            // Vincula 1 string (s) e 1 inteiro (i)
            $check_stmt->bind_param("si", $CNPJ, $id_empresa_logada);
            $check_stmt->execute();
            $check_stmt->bind_result($count);
            $check_stmt->fetch();
            $check_stmt->close();

            $stmt->close();
            $conn->close();

            if ($count > 0) {
                // CNPJ existe E pertence à empresa logada, mas não houve alteração
                echo json_encode([
                    'success' => true, 
                    'message' => 'Nenhuma alteração detectada. Os dados enviados são idênticos aos atuais.'
                ]);
            } else {
                // CNPJ não encontrado OU não pertence à empresa logada
                echo json_encode([
                    'success' => false, 
                    'error' => "Erro crítico: O CNPJ ({$CNPJ}) não foi encontrado ou você não tem permissão para editá-lo."
                ]);
            }
            exit;
        }
        
    } else {
        $stmt->close();
        responseError($conn, "Erro ao atualizar no banco de dados: " . $stmt->error);
    }

} else {
    responseError($conn, "Dados incompletos para atualização.");
}
?>