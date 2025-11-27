function cadastrarProduto() {
  const tipo = document.getElementById("tipo").value;
  const produtoFornecedor = document.getElementById("produtoFornecedor").value;
  const quantidade = document.getElementById("quantidade").value;
  const valor = document.getElementById("valor").value;

  if (!produtoFornecedor || !quantidade || !valor) {
    alert("Preencha todos os campos!");
    return;
  }

  const tabela = document.getElementById("tabelaHistorico");

  const linha = `
    <tr>
      <td>${tipo}</td>
      <td>${produtoFornecedor}</td>
      <td>${quantidade}</td>
      <td>R$ ${valor}</td>
    </tr>
  `;

  tabela.innerHTML += linha;

  // Limpa os campos
  document.getElementById("produtoFornecedor").value = "";
  document.getElementById("quantidade").value = "";
  document.getElementById("valor").value = "";
}
