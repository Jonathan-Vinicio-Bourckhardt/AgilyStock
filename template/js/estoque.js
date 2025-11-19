function cadastrarProduto() {
  const produtoFornecedor = document.getElementById("produtoFornecedor").value;
  const quantidade = document.getElementById("quantidade").value;
  const valor = document.getElementById("valor").value;

  if (!produtoFornecedor || !quantidade || !valor) {
    alert("Preencha todos os campos!");
    return;
  }

  // Cria um objeto FormData para enviar os dados
  const formData = new FormData();
  formData.append('CodProdFor', produtoFornecedor); // Assumindo que produtoFornecedor é o CodProdFor
  formData.append('quantidade', quantidade);
  formData.append('valor', valor);

  // Envia os dados para o servidor usando Fetch API
  fetch('salvar_quantidade.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert("✅ Produto cadastrado com sucesso!");
      
      // Limpa os campos
      document.getElementById("produtoFornecedor").value = "";
      document.getElementById("quantidade").value = "";
      document.getElementById("valor").value = "";
      
      // Redireciona ou recarrega a página de estoque, se necessário
      // window.location.href = 'estoque.php'; 

    } else {
      alert("❌ Erro ao cadastrar produto: " + data.error);
    }
  })
  .catch(error => {
    console.error('Erro na requisição:', error);
    alert("Erro de comunicação com o servidor.");
  });
}