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
    .then(response => {
        if (!response.ok) {
            // Tratar erros HTTP
            throw new Error(`Erro HTTP: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert("✅ Produto cadastrado com sucesso!");
            
            // Limpa os campos
            document.getElementById("produtoFornecedor").value = "";
            document.getElementById("quantidade").value = "";
            document.getElementById("valor").value = "";
            
            // Recarrega a página se for necessário atualizar o estoque
            location.reload(); 

        } else {
            // 🛑 AÇÃO DE SEGURANÇA: Tratar sessão expirada ou acesso negado 🛑
            if (data.error && data.error.includes('Acesso negado')) {
                alert('❌ Acesso negado ou sessão expirada. Você será redirecionado para o login.');
                // ⚠️ Ajuste o caminho conforme sua estrutura.
                window.location.href = '../template/CadLog.php'; 
                return;
            }

            alert("❌ Erro ao cadastrar produto: " + data.error);
        }
    })
    .catch(error => {
        console.error('Erro na requisição:', error);
        alert("Erro de comunicação com o servidor. Detalhes: " + error.message);
    });
}