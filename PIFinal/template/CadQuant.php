<?php
include 'conexao.php'; // 1. INCLUSÃO DA CONEXÃO
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Agile Stock - Cadastro de Quantidade</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/CadQuant.css">
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
  <h2 class="mb-4">Cadastro de Entrada de Produto</h2>

  <form id="form-movimento">
    <table class="table table-bordered bg-white">
      <thead>
        <tr>
          <th>Devolução</th>
          <th>Ação</th>
          <th>Tipo</th>
          <th>Produto / Fornecedor</th>
          <th>Quantidade</th>
          <th>Valor Unitário (R$)</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <select id="devolucao" name="devolucao" class="form-control">
              <option value="Não">Não</option>
              <option value="Sim">Sim</option>
            </select>
          </td>

          <td>
            <select id="acao" name="acao" class="form-control">
              <option value="Soma">Soma</option>
              <option value="Subtracao">Subtração</option>
            </select>
          </td>

          <td>
            <select id="tipo" class="form-control" disabled>
              <option value="" selected disabled>Selecione um produto</option>
            </select>
          </td>

          <td>
            <select id="produtoFornecedor" name="codProdFor" class="form-control" required>
              <option selected disabled value="">-- Selecione --</option>
              
              <?php
              // Esta consulta junta as tabelas para obter os nomes e os IDs corretos
              $sql = "SELECT 
                        pf.CodProdFor, 
                        cp.Tipo, 
                        cp.Produto, 
                        pf.Formato, 
                        cf.Fornecedor AS NomeFornecedor 
                      FROM produto_fornecedor pf
                      INNER JOIN cadproduto cp ON pf.CodProduto_FK = cp.CodProduto
                      INNER JOIN cadfornecedor cf ON pf.CNPJ_Fornecedor_FK = cf.CNPJ
                      ORDER BY cp.Produto ASC";
              
              $res = $conn->query($sql);
              
              if ($res && $res->num_rows > 0) {
                  while ($row = $res->fetch_assoc()) {
                      // O 'value' é o CodProdFor (PK da tabela de junção)
                      // O 'data-tipo' é o Tipo (para o js/CadQuant.js)
                      echo "<option 
                                data-tipo='{$row['Tipo']}'
                                value='{$row['CodProdFor']}'> 
                                {$row['Produto']} ({$row['Formato']}) - {$row['NomeFornecedor']}
                            </option>";
                  }
              } else {
                  // Se não houver junções (tabela produto_fornecedor vazia), exibe aviso
                  echo "<option disabled>Nenhuma combinação Produto/Fornecedor cadastrada.</option>";
              }
              ?>
            </select>
          </td>

          <td><input id="quantidade" name="quantidade" type="number" class="form-control" placeholder="Qtd" required step="0.01"></td>
          <td><input id="valor" name="valorUnitario" type="number" class="form-control" placeholder="Valor Unitário" step="0.01" required></td> 

          <td><button type="submit" class="btn btn-success w-100">Cadastrar</button></td>
        </tr>
      </tbody>
    </table>
  </form> <h4 class="mt-4">Histórico</h4>

<div class="history-container">
  <table class="table table-striped history-table">
    <thead>
      <tr>
        <th>Devolução</th>
        <th>Tipo</th>
        <th>Produto</th>
        <th>Fornecedor</th>
        <th>Quantidade</th>
        <th>Valor Unitário</th>
      </tr>
    </thead>
    <tbody>
      <?php
      // Lógica PHP para buscar o histórico de movimentos
      $sqlHistorico = "SELECT cm.Devolucao, cm.Tipo, cp.Produto, cf.Fornecedor, 
                              cm.Quantidade, cm.ValorUnitario, pf.Formato
                       FROM cadmovimento cm
                       INNER JOIN produto_fornecedor pf ON cm.CodProdFor_FK = pf.CodProdFor
                       INNER JOIN cadproduto cp ON pf.CodProduto_FK = cp.CodProduto
                       INNER JOIN cadfornecedor cf ON pf.CNPJ_Fornecedor_FK = cf.CNPJ
                       ORDER BY cm.CodMovimento DESC";

      $resultadoHistorico = $conn->query($sqlHistorico);

      if ($resultadoHistorico && $resultadoHistorico->num_rows > 0) {
        while ($movimento = $resultadoHistorico->fetch_assoc()) {
          $valorFormatado = "R$" . number_format($movimento['ValorUnitario'], 2, ',', '.');
          $qtdFormatada = $movimento['Quantidade'] . " (" . $movimento['Formato'] . ")";
          
          echo "<tr>
                  <td>{$movimento['Devolucao']}</td>
                  <td>{$movimento['Tipo']}</td>
                  <td>{$movimento['Produto']}</td>
                  <td>{$movimento['Fornecedor']}</td>
                  <td>{$qtdFormatada}</td>
                  <td>{$valorFormatado}</td>
                </tr>";
        }
      } else {
        echo "<tr><td colspan='6' class='text-center'>Nenhum movimento registrado.</td></tr>";
      }

      $conn->close();
      ?>
    </tbody>
  </table>
</div>
</div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/CadQuant.js"></script>
</body>
</html>