<?php
header('Content-Type: application/json; charset=utf-8');
include 'conexao.php';

$codProdFor = $_GET['CodProdFor'] ?? null;

if (!$codProdFor) {
    echo json_encode(['success' => false, 'error' => 'Código do Item não fornecido.']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT comentario, data_comentario FROM comentarios_estoque WHERE CodProdFor_FK = ? ORDER BY data_comentario DESC");
    $stmt->bind_param("i", $codProdFor);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $comentarios = [];
    while ($row = $resultado->fetch_assoc()) {
        $comentarios[] = $row;
    }
    
    $stmt->close();
    
    echo json_encode(['success' => true, 'comentarios' => $comentarios]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>