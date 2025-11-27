document.addEventListener('DOMContentLoaded', function() {
    const btnCadastrar = document.getElementById('btnCadastrar');
    const formCadastro = document.getElementById('formCadastro');
    
    // CORREÇÃO FINAL DO CAMINHO: Sobe da pasta 'js', sobe da pasta 'template', e entra em 'php'
    const URL_CADASTRO = '../../php/cadastrar_empresa.php'; // ⚠️ Ajustado o nome do arquivo PHP para ser mais claro ⚠️

    if (btnCadastrar && formCadastro) {
        btnCadastrar.addEventListener('click', function(e) {
            e.preventDefault(); 

            // Coleta de valores
            const nome = document.getElementById('NomeCadastro').value.trim();
            const cnpj = document.getElementById('CNPJCadastro').value.trim();
            const email = document.getElementById('EmailCadastro').value.trim();
            const senha = document.getElementById('SenhaCadastro').value;

            // Validação Front-end
            if (nome === '' || cnpj === '' || email === '' || senha === '') {
                alert('Preencha todos os campos obrigatórios.');
                return;
            }
            if (cnpj.length !== 14 || !/^\d+$/.test(cnpj)) {
                alert('O CNPJ deve ter 14 dígitos (apenas números).');
                return;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                alert('E-mail inválido.');
                return;
            }

            const formData = new FormData(formCadastro);

            // Envio e Processamento AJAX
            fetch(URL_CADASTRO, { 
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    // Se houver 404, este erro é lançado
                    throw new Error(`Erro HTTP: ${response.status} - Verifique o console.`);
                }
                return response.json();
            })
            .then(data => {
                alert(data.message);
                
                if (data.success) {
                    const modalElement = document.getElementById('modalCadastro');
                    if (typeof bootstrap !== 'undefined' && modalElement) {
                        const modalBootstrap = bootstrap.Modal.getInstance(modalElement);
                        if (modalBootstrap) {
                            modalBootstrap.hide();
                        }
                    }
                    
                    // 🛑 MUDANÇA: O PHP faz o login automático. Redirecionar para o dashboard. 🛑
                    // O arquivo PHP cadastrar_empresa.php envia 'redirect: true' no sucesso.
                    window.location.href = '../dashboard.php'; // ⚠️ Ajuste o caminho conforme necessário para a página inicial logada ⚠️
                }
            })
            .catch(error => {
                console.error('Erro na requisição ou no servidor:', error);
                alert('Erro: Arquivo PHP não encontrado. Verifique se o caminho ' + URL_CADASTRO + ' está correto na estrutura de pastas.');
            });
        });
    }
});