<?php
include 'conexao.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Agile Stock - Cadastro de Produto</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/CadProduto.css">
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
  <h2 class="mb-4">Cadastro de Produto</h2>

  <form id="form-produto">
    <table class="table table-bordered bg-white">
      <thead>
        <tr>
          <th>Tipo</th>
          <th>Formato</th>
          <th>Produto</th>
          <th>Fornecedor</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <select id="tipo" name="tipo" class="form-control">
              <option value="fruta">Fruta</option>
              <option value="verdura">Verdura</option>
              <option value="legume">Legume</option>
              <option value="outro">Outro</option>
            </select>
          </td>

          <td>
            <select id="formato" name="formato" class="form-control">
              <option value="kg">KG</option>
              <option value="unidade">Unidade</option>
            </select>
          </td>

          <td><input id="produto" name="produto" type="text" class="form-control" placeholder="Produto" required></td>

          <td>
            <select id="fornecedor" name="fornecedor" class="form-control" required>
              <option value="">Selecione...</option>
              <?php
              // 🔹 puxar fornecedores do banco
              $sqlFornecedor = "SELECT CNPJ, Fornecedor FROM cadfornecedor ORDER BY Fornecedor ASC";
              $res = $conn->query($sqlFornecedor);
              if ($res && $res->num_rows > 0) {
                while ($row = $res->fetch_assoc()) {
                  // ✅ CORREÇÃO: O VALOR ENVIADO AGORA É O CNPJ, não o nome
                  echo "<option value='{$row['CNPJ']}'>{$row['Fornecedor']} ({$row['CNPJ']})</option>";
                }
              }
              ?>
            </select>
          </td>

          <td><button type="submit" class="btn btn-success w-100">Cadastrar</button></td>
        </tr>
      </tbody>
    </table>
  </form>

  <h4 class="mt-4">Histórico</h4>

  <div class="history-container">
    <table class="table table-striped history-table">
      <thead>
        <tr>
          <th>Tipo</th>
          <th>Formato</th>
          <th>Produto</th>
          <th>Fornecedor</th>
        </tr>
      </thead>
      <tbody>
        <?php
        // 🔹 CORREÇÃO: Puxar produtos fazendo JOIN com cadfornecedor (p.Fornecedor agora armazena o CNPJ)
        $sqlProdutos = "SELECT p.Tipo, p.Formato, p.Produto, f.Fornecedor AS NomeFornecedor 
                        FROM cadproduto p
                        INNER JOIN cadfornecedor f ON p.Fornecedor = f.CNPJ
                        ORDER BY p.CodProduto DESC";
        $resultado = $conn->query($sqlProdutos);

        if ($resultado && $resultado->num_rows > 0) {
          while ($p = $resultado->fetch_assoc()) {
            echo "<tr>
                    <td>{$p['Tipo']}</td>
                    <td>{$p['Formato']}</td>
                    <td>{$p['Produto']}</td>
                    <td>{$p['NomeFornecedor']}</td>
                  </tr>";
          }
        } else {
          echo "<tr><td colspan='4' class='text-center'>Nenhum produto cadastrado ainda.</td></tr>";
        }

        $conn->close();
        ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.getElementById('form-produto').addEventListener('submit', async (e) => {
  e.preventDefault();

  const formData = new FormData(e.target);

  const resp = await fetch('inserir_produto.php', {
    method: 'POST',
    body: formData
  });
    
  // 💥 Melhoria na captura de erro JSON (já está no seu código, mantido aqui)
  const responseText = await resp.text();

  let resultado;
  try {
      resultado = JSON.parse(responseText);
  } catch (jsonError) {
      alert('❌ Erro no servidor: A resposta não é JSON. Verifique o PHP.');
      console.error('Resposta do Servidor:', responseText);
      return; 
  }

  if (resultado.success) {
    alert('✅ Produto cadastrado com sucesso!');
    location.reload(); // recarrega a página para atualizar histórico
  } else {
    alert('❌ Erro ao cadastrar: ' + resultado.error);
    console.error('Erro de Inserção:', resultado.error);
  }
});
</script>

</body>
</html>