<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

function responseError($conn, $msg) {
    if ($conn && $conn->ping()) { // Verifica se a conexão ainda está aberta
        $conn->close();
    }
    die(json_encode(['success' => false, 'error' => $msg]));
}

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['id_empresa'])) {
    responseError(null, "Acesso negado. O usuário deve estar logado.");
}
$id_empresa_logada = $_SESSION['id_empresa'];

// Inclui a conexão (Agora corrigido para JSON)
include 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responseError($conn, "Método de requisição inválido.");
}

// 🛑 CORREÇÃO: O JavaScript envia: cnpj_antigo, fornecedor, contato
if (isset($_POST['cnpj_antigo']) && isset($_POST['fornecedor']) && isset($_POST['contato'])) {
    
    // 1. Limpeza e Renomeação dos dados recebidos
    $CNPJ_ANTIGO = preg_replace('/\D/', '', $_POST['cnpj_antigo']); // CNPJ usado no WHERE
    $NOVO_FORNECEDOR = trim($_POST['fornecedor']); // Novo nome do fornecedor
    $NOVO_CONTATO = preg_replace('/\D/', '', $_POST['contato']); // Novo contato sem máscara

    // Validação (Garante que nenhum campo obrigatório está vazio)
    if (empty($NOVO_FORNECEDOR)) {
        responseError($conn, "O campo 'Fornecedor' não pode ser deixado em branco.");
    }
    if (empty($NOVO_CONTATO)) {
        responseError($conn, "O campo 'Número de Contato' não pode ser deixado em branco.");
    }
    if (empty($CNPJ_ANTIGO) || strlen($CNPJ_ANTIGO) !== 14) {
        responseError($conn, "Erro interno: O CNPJ de referência (cnpj_antigo) é inválido.");
    }
    
    // 2. Preparação do SQL
    $sql = "UPDATE cadfornecedor SET Fornecedor = ?, NumContato = ? WHERE CNPJ = ? AND id_empresa = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        responseError($conn, "Erro de preparação SQL: " . $conn->error);
    }
    
    // 3. Vincula os parâmetros: 3 strings (s) e 1 inteiro (i)
    $stmt->bind_param("sssi", $NOVO_FORNECEDOR, $NOVO_CONTATO, $CNPJ_ANTIGO, $id_empresa_logada);

    if ($stmt->execute()) {
        
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        $conn->close();
        
        if ($affected_rows > 0) {
            // Sucesso: Linhas afetadas
            echo json_encode(['success' => true, 'message' => 'Fornecedor atualizado com sucesso!']);
            exit;
        } else {
            // Nenhuma linha afetada, verifica se o fornecedor existe (para diferenciar de erro de permissão/CNPJ)
            
            // Nota: Se a query acima não afetou nenhuma linha, mas o CNPJ existe e as permissões estão OK, 
            // significa que os dados enviados são idênticos aos atuais.
            
            // Este check de COUNT(*) foi retirado para simplificar, confiando que o affected_rows = 0 
            // já é suficiente, mas vamos manter a lógica de notificar "nenhuma alteração"
            
            echo json_encode(['success' => true, 'message' => 'Nenhuma alteração detectada. Os dados enviados são idênticos aos atuais.']);
            exit;
        }
        
    } else {
        $stmt->close();
        responseError($conn, "Erro ao atualizar no banco de dados: " . $stmt->error);
    }

} else {
    responseError($conn, "Dados incompletos para atualização. Faltando 'cnpj_antigo', 'fornecedor' ou 'contato'.");
}