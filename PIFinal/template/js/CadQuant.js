document.addEventListener('DOMContentLoaded', function() {
    const formMovimento = document.getElementById('form-movimento');
    const selectProdutoFornecedor = document.getElementById('produtoFornecedor');
    const selectTipo = document.getElementById('tipo');

    // 1. Lógica para preencher o campo 'Tipo' (Obrigatório)
    // O valor do 'Tipo' será puxado do atributo data-tipo do <option> selecionado.
    if (selectProdutoFornecedor && formMovimento) {
        selectProdutoFornecedor.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            // Certifique-se de que seu PHP (CadQuant.php) está populando 'data-tipo'
            const tipoProduto = selectedOption.getAttribute('data-tipo'); 

            // Se o tipo for válido (não nulo), atualiza o campo de exibição e o campo hidden
            if (tipoProduto) {
                // Cria/atualiza o campo hidden 'tipo' para envio no FormData
                let inputTipo = document.getElementById('tipo_hidden');
                if (!inputTipo) {
                    inputTipo = document.createElement('input');
                    inputTipo.type = 'hidden';
                    inputTipo.name = 'tipo';
                    inputTipo.id = 'tipo_hidden';
                    formMovimento.appendChild(inputTipo);
                }
                inputTipo.value = tipoProduto;
                
                // Atualiza o select de exibição
                selectTipo.innerHTML = `<option value="${tipoProduto}" selected>${tipoProduto.charAt(0).toUpperCase() + tipoProduto.slice(1)}</option>`;
            } else {
                 // Limpa os campos se nada for selecionado
                 document.getElementById('tipo_hidden')?.remove();
                 selectTipo.innerHTML = `<option value="" selected disabled>Selecionar Produto</option>`;
            }
        });
    }

    // 2. Lógica para submeter o formulário via AJAX
    if (formMovimento) {
        formMovimento.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(e.target);
            
            // Re-insere o valor do tipo no FormData, garantindo que ele vá
            const tipoValor = document.getElementById('tipo_hidden')?.value;
            if (!tipoValor) {
                 alert('❌ Por favor, selecione um Produto/Fornecedor para preencher o Tipo.');
                 return;
            }
            formData.set('tipo', tipoValor);
            
            try {
                const resp = await fetch('inserir_movimento.php', {
                    method: 'POST',
                    body: formData
                });
                
                // Tenta ler o texto da resposta para debug (útil para o erro '<')
                const responseText = await resp.text();

                if (!resp.ok) {
                    // Erro HTTP (404, 500, etc.)
                    throw new Error(`Erro HTTP ${resp.status}. Resposta: ${responseText.substring(0, 100)}...`);
                }

                let resultado;
                try {
                    // Tenta processar o JSON (captura o SyntaxError: Unexpected token '<')
                    resultado = JSON.parse(responseText);
                } catch (jsonError) {
                    console.error('Erro ao tentar parsear JSON. Resposta não é JSON:', responseText);
                    alert('❌ Falha na resposta do servidor (SyntaxError). Verifique o console. O PHP deve estar enviando um erro HTML ou texto antes do JSON.');
                    return; // Sai da função
                }

                if (resultado.success) {
                    alert('✅ Movimento cadastrado com sucesso!');
                    location.reload(); // recarrega a página para atualizar o histórico
                } else {
                    alert('❌ Erro ao cadastrar: ' + resultado.error);
                    console.error('Erro de Validação/SQL:', resultado.error);
                }
            } catch (error) {
                // Erro de rede ou erro lançado acima
                alert('❌ Erro de conexão ou servidor. Tente novamente.'); 
                console.error('Erro na requisição:', error.message);
            }
        });
    }
});