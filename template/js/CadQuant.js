document.addEventListener('DOMContentLoaded', function() {
    const formMovimento = document.getElementById('form-movimento');
    const selectProdutoFornecedor = document.getElementById('produtoFornecedor');
    const selectTipo = document.getElementById('tipo');
    // ID UNIFICADO para o campo oculto que enviará o valor
    const ID_TIPO_HIDDEN = 'tipo-hidden'; 

    // 1. Lógica para preencher o campo 'Tipo'
    if (selectProdutoFornecedor && formMovimento) {
        selectProdutoFornecedor.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            // O valor do 'tipo' é lido do atributo 'data-tipo' da opção selecionada
            const tipoProduto = selectedOption.getAttribute('data-tipo'); 

            // Obtém a referência para o campo oculto
            let inputTipoHidden = document.getElementById(ID_TIPO_HIDDEN);

            if (tipoProduto) {
                // 1.1. CRIAÇÃO/ATUALIZAÇÃO DO CAMPO HIDDEN
                if (!inputTipoHidden) {
                    inputTipoHidden = document.createElement('input');
                    inputTipoHidden.type = 'hidden';
                    inputTipoHidden.name = 'tipo'; // Nome do campo esperado pelo PHP
                    inputTipoHidden.id = ID_TIPO_HIDDEN;
                    formMovimento.appendChild(inputTipoHidden);
                }
                
                // 1.2. PREENCHIMENTO DO VALOR NO CAMPO HIDDEN
                inputTipoHidden.value = tipoProduto;
                
                // 1.3. ATUALIZA O SELECT VISÍVEL (apenas para exibição)
                // Capitaliza a primeira letra para exibição (ex: 'produto' -> 'Produto')
                const tipoExibicao = tipoProduto.charAt(0).toUpperCase() + tipoProduto.slice(1);
                selectTipo.innerHTML = `<option value="${tipoProduto}" selected>${tipoExibicao}</option>`;
            } else {
                // Limpa/Remove os campos se nada for selecionado
                inputTipoHidden?.remove();
                selectTipo.innerHTML = `<option value="" selected disabled>Selecione um produto</option>`;
            }
        });
    }

    // 2. Lógica para submeter o formulário via AJAX
    if (formMovimento) {
        formMovimento.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(e.target);
            
            // Verifica se o campo 'tipo' oculto foi preenchido
            const tipoValor = document.getElementById(ID_TIPO_HIDDEN)?.value;
            
            if (!tipoValor) {
                alert('❌ Por favor, selecione um Produto/Fornecedor para preencher o Tipo.');
                return;
            }
            // Adiciona o valor do Tipo ao FormData para garantir que ele seja enviado
            formData.set('tipo', tipoValor);
            
            // Desabilita o botão de cadastro para evitar submissões duplicadas
            const submitButton = e.submitter;
            submitButton.disabled = true;

            try {
                // ✅ CORREÇÃO: Usando 'inserir_movimento.php' conforme solicitado
                const resp = await fetch('inserir_movimento.php', { 
                    method: 'POST',
                    body: formData
                });
                
                const responseText = await resp.text();
                submitButton.disabled = false; // Reabilita após receber a resposta

                if (!resp.ok) {
                    // Trata erros de nível HTTP (4xx, 5xx)
                    throw new Error(`Erro HTTP ${resp.status}. Resposta: ${responseText.substring(0, 100)}...`);
                }

                let resultado;
                try {
                    // Tenta fazer o parse da resposta JSON
                    resultado = JSON.parse(responseText);
                } catch (jsonError) {
                    console.error('Erro ao tentar parsear JSON. Resposta não é JSON:', responseText);
                    alert('❌ Falha na resposta do servidor (SyntaxError). O PHP deve retornar apenas JSON.');
                    return; 
                }

                if (resultado.success) {
                    // Exibe a mensagem de sucesso (pode incluir o alerta de estoque negativo)
                    alert('✅ ' + resultado.message); 
                    location.reload(); // Recarrega a página após o sucesso
                } else {
                    // Exibe o erro retornado pelo script PHP
                    alert('❌ Erro ao cadastrar: ' + resultado.error);
                    console.error('Erro de Validação/SQL:', resultado.error);
                }
            } catch (error) {
                submitButton.disabled = false;
                alert('❌ Erro de conexão ou servidor. Tente novamente. Detalhes: ' + error.message); 
                console.error('Erro na requisição:', error);
            }
        });
    }
});