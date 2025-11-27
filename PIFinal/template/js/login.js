document.addEventListener('DOMContentLoaded', function() {
    const btnLogar = document.getElementById('btnLogar');
    const formLogin = document.getElementById('formLogin');

    if (btnLogar && formLogin) {
        btnLogar.addEventListener('click', function(e) {
            e.preventDefault(); // Impede o envio padrão do formulário

            const login = document.getElementById('CNPJEmail').value.trim();
            const senha = document.getElementById('SenhaLogin').value;

            // 1. Validação simples no Front-end
            if (login === '' || senha === '') {
                alert('Por favor, preencha todos os campos.');
                return;
            }

            // 2. Coleta dos dados do formulário
            const formData = new FormData(formLogin);

            // 3. Envio dos dados via Fetch API (AJAX)
            fetch('php/processa_login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Se a resposta não for JSON (erro de servidor, HTML de erro), trata como erro
                if (!response.ok) {
                    throw new Error(`Erro HTTP: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // 4. Tratamento da resposta do PHP
                alert(data.message);

                if (data.success) {
                    // Se o login foi sucesso, redireciona para a página de destino (ex: dashboard.php)
                    window.location.href = data.redirect || 'dashboard.php';
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                alert('Ocorreu um erro ao tentar logar. Verifique a conexão.');
            });
        });
    }
});