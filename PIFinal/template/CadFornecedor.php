<?php include 'conexao.php'; ?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Agile Stock - Cadastro de Fornecedor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/CadFornecedor.css">
</head>
<body>

<!-- Sidebar -->
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

<!-- Conteúdo -->
<div class="content">
  <h2 class="mb-4">Cadastro de Fornecedor</h2>

  <!-- Formulário de Entrada -->
  <form action="inserir_fornecedor.php" method="POST">
    <table class="table table-bordered bg-white">
      <thead>
        <tr>
          <th>CNPJ</th>
          <th>Fornecedor</th>
          <th>Num.Contato</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><input name="CNPJ" type="text" maxlength="14" class="form-control" placeholder="CNPJ" required></td>
          <td><input name="Fornecedor" type="text" maxlength="100" class="form-control" placeholder="Fornecedor" required></td>
          <td><input name="NumContato" type="text" maxlength="11" class="form-control" placeholder="Num. Contato" required></td>
          <td><button type="submit" class="btn btn-success w-100">Cadastrar</button></td>
        </tr>
      </tbody>
    </table>
  </form>

  <!-- Histórico -->
  <h4 class="mt-4">Histórico</h4>

  <div class="history-container">
    <table class="table table-striped history-table">
      <thead>
        <tr>
          <th>CNPJ</th>
          <th>Fornecedor</th>
          <th>Num.Contato</th>
        </tr>
      </thead>
      <tbody>
        <?php
          $sql = "SELECT * FROM cadfornecedor ORDER BY Fornecedor ASC";
          $resultado = $conn->query($sql);

          if ($resultado->num_rows > 0) {
            while ($linha = $resultado->fetch_assoc()) {
              echo "<tr>
                      <td>{$linha['CNPJ']}</td>
                      <td>{$linha['Fornecedor']}</td>
                      <td>{$linha['NumContato']}</td>
                    </tr>";
            }
          } else {
            echo "<tr><td colspan='3'>Nenhum fornecedor cadastrado.</td></tr>";
          }

          $conn->close();
        ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
