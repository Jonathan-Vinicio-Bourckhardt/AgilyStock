<?php
include 'conexao.php'; // Inclui a conexão com o banco
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Agile Stock - Estoque</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/estoque.css">
</head>
<body>

<div class="sidebar">
  <div class="logo-container">
    <img src="./img/logo.png" alt="Agile Stock Logo" class="logo-img">
    <h4 class="logo-text">Agile Stock</h4>
  </div>

  <a href="estoque.php">Estoque</a> 
  <a href="CadQuant.php">CadQuantidade</a>
  <a href="CadProduto.php">CadProduto</a>
  <a href="CadFornecedor.php">CadFornecedor</a>
</div>

<div class="content">
  <h2 class="mb-4">Valor Atual Em Estoque</h2>

  <?php
  // Consulta para obter o VALOR TOTAL GERAL em estoque (soma de todos os ValorTotal)
  $sqlValorTotalGeral = "SELECT SUM(ValorTotal) AS TotalGeral FROM estoque";
  $resTotalGeral = $conn->query($sqlValorTotalGeral);
  $totalGeral = $resTotalGeral->fetch_assoc()['TotalGeral'] ?? 0;
  $totalFormatado = "R$ " . number_format($totalGeral, 2, ',', '.');
  
  echo "<h3 class='text-success mb-5'>Total em Estoque: " . $totalFormatado . "</h3>";
  ?>

<h4 class="mt-4">Estoque Detalhado</h4>

<div class="history-container">
  <table class="table table-striped history-table">
    <thead>
      <tr>
        <th>Tipo</th>
        <th>Produto</th>
        <th>Fornecedor</th>
        <th>Quantidade</th>
        <th>Valor Total</th>
      </tr>
    </thead>
    <tbody>
      <?php
      // 🔹 Consulta SQL para listar o ESTOQUE atual (realizando 3 JOINs)
      $sqlEstoque = "SELECT 
                        cp.Tipo, 
                        cp.Produto, 
                        cf.Fornecedor AS NomeFornecedor,
                        e.Quantidade, 
                        e.ValorTotal,
                        cp.Formato
                       FROM estoque e
                       INNER JOIN produto_fornecedor pf ON e.CodProdFor_FK = pf.CodProdFor
                       INNER JOIN cadproduto cp ON pf.CodProduto_FK = cp.CodProduto
                       INNER JOIN cadfornecedor cf ON pf.CNPJ_Fornecedor_FK = cf.CNPJ
                       WHERE e.Quantidade > 0
                       ORDER BY cp.Produto ASC";

      $resultadoEstoque = $conn->query($sqlEstoque);

      if ($resultadoEstoque && $resultadoEstoque->num_rows > 0) {
        while ($item = $resultadoEstoque->fetch_assoc()) {
          // Formata a quantidade e o valor para exibição
          $qtdFormatada = number_format($item['Quantidade'], 2, ',', '.') . " " . $item['Formato'];
          $valorFormatado = "R$ " . number_format($item['ValorTotal'], 2, ',', '.');
          
          echo "<tr>
                  <td>{$item['Tipo']}</td>
                  <td>{$item['Produto']}</td>
                  <td>{$item['NomeFornecedor']}</td>
                  <td>{$qtdFormatada}</td>
                  <td>{$valorFormatado}</td>
                </tr>";
        }
      } else {
        echo "<tr><td colspan='5' class='text-center'>Estoque vazio.</td></tr>";
      }

      $conn->close();
      ?>
    </tbody>
  </table>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/estoque.js"></script>
</body>
</html>