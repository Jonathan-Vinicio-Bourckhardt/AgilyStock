<?php
include 'conexao.php';

$CNPJ = $_POST['CNPJ'];
$Fornecedor = $_POST['Fornecedor'];
$NumContato = $_POST['NumContato'];

// Evita erro se já existir o mesmo CNPJ
$sql = "INSERT INTO cadfornecedor (CNPJ, Fornecedor, NumContato)
        VALUES ('$CNPJ', '$Fornecedor', '$NumContato')";

if ($conn->query($sql) === TRUE) {
    header("Location: CadFornecedor.php");
    exit;
} else {
    echo "Erro ao cadastrar: " . $conn->error;
}

$conn->close();
?>
