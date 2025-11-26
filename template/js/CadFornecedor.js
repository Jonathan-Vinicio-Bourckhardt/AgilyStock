// CadFornecedor.js

$(document).ready(function() {
    
    // ==========================================================
    // A. LÓGICA DE EXIBIÇÃO DE ALERTA NO CENTRO DA PÁGINA
    // ==========================================================
    
    function centerAlert() {
        var alert = $('#center-alert');
        if (alert.length) {
            var windowHeight = $(window).height();
            var alertHeight = alert.outerHeight();
            var topPosition = (windowHeight - alertHeight) / 2;
            alert.css('top', topPosition + 'px');
        }
    }

    centerAlert();
    $(window).on('resize', centerAlert);

    setTimeout(function() {
        $('#center-alert').fadeOut('slow');
    }, 5000);


    // ==========================================================
    // B. LÓGICA DE ALTERNÂNCIA DE SENHA (TOGGLE PASSWORD)
    // ==========================================================
    
    $('.toggle-password').click(function() {
        var input = $($(this).attr('toggle'));
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            $(this).removeClass('bi-eye-slash').addClass('bi-eye');
        } else {
            input.attr('type', 'password');
            $(this).removeClass('bi-eye').addClass('bi-eye-slash');
        }
    });

    // ==========================================================
    // C. LÓGICA DE CLIQUE NA LOGO (AJUSTADA COM NOVA POSIÇÃO)
    // ==========================================================
    
    const logoContainer = $('.logo-container');

    logoContainer.on('click', function(e) {
        e.stopPropagation(); 
        
        $('.logo-menu-button').remove(); 
        
        const logoutButton = $(`
            <a class="btn btn-sm btn-danger logo-menu-button" href="logout.php" 
               style="position: absolute; top: 70px; left: 100px; z-index: 1000; padding: 5px 10px;"> 
                Sair <i class="bi bi-box-arrow-right"></i>
            </a>
        `);
        
        logoContainer.append(logoutButton);

        $(document).one('click', function() {
            logoutButton.remove();
        });
    });

    // ==========================================================
    // D. VALIDAÇÃO DE CAMPOS E MÁSCARAS
    // ==========================================================
    
    // Funções de Comportamento de Máscara
    var SPMaskBehavior = function (val) {
      return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
    },
    spOptions = {
      onKeyPress: function(val, e, field, options) {
          field.mask(SPMaskBehavior.apply({}, arguments), options);
        }
    };
    
    // 1. Aplica a máscara de CNPJ ao campo de novo cadastro
    const cnpjNovoInput = $('input[name="CNPJ_Novo"]');
    if (cnpjNovoInput.length) {
        cnpjNovoInput.mask('00.000.000/0000-00', {reverse: true});
    }

    // 2. Aplica a máscara de Telefone ao campo de novo cadastro
    const contatoNovoInput = $('input[name="NumContato_Novo"]');
    if (contatoNovoInput.length) {
        contatoNovoInput.mask(SPMaskBehavior, spOptions);
    }
    
    // 3. Máscara de CNPJ para campo com ID #cnpj (se existir em outra página)
    if ($('#cnpj').length) {
        $('#cnpj').mask('00.000.000/0000-00', {reverse: true});
    }

    // 4. Máscara de Telefone para campo com ID #telefone (se existir em outra página)
    if ($('#telefone').length) {
        $('#telefone').mask(SPMaskBehavior, spOptions);
    }
    
    // Validação de email: Adiciona a classe 'is-invalid' se o formato for inválido
    $('#email').on('input', function() {
        var email = $(this).val();
        var regex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
        if (!regex.test(email) && email !== '') {
            $(this).addClass('is-invalid').removeClass('is-valid');
        } else if (email === '') {
            $(this).removeClass('is-invalid is-valid'); // Remove validacao se estiver vazio
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
        }
    });

    // Prevenir o envio do formulário se o campo de e-mail estiver inválido
    $('form').on('submit', function(e) {
        if ($('#email').hasClass('is-invalid')) {
            e.preventDefault();
        }
    });
    
    // ==========================================================
    // F. VALIDAÇÃO DE SUBMISSÃO DO NOVO FORNECEDOR (Formato de Dígitos)
    // ==========================================================
    $('#form-cadastro-fornecedor').on('submit', function(e) {
        const cnpjRaw = $('input[name="CNPJ_Novo"]').val().replace(/\D/g, '');
        const contatoRaw = $('input[name="NumContato_Novo"]').val().replace(/\D/g, '');

        if (cnpjRaw.length !== 14) {
            alert('Por favor, insira um CNPJ válido com 14 dígitos.');
            $('input[name="CNPJ_Novo"]').focus();
            e.preventDefault();
            return;
        }

        if (contatoRaw.length < 10 || contatoRaw.length > 11) {
            alert('Por favor, insira um número de contato válido (10 ou 11 dígitos).');
            $('input[name="NumContato_Novo"]').focus();
            e.preventDefault();
            return;
        }
    });


});

// ==========================================================
// E. AÇÕES DA TABELA DE FORNECEDORES (Funções Globais)
// ==========================================================

// Alterna entre modo de visualização e edição
function toggleEdit(cnpj) {
    const row = $(`#row-${cnpj}`);
    row.find('.view-cnpj, .view-fornecedor, .view-contato').toggle();
    row.find('.edit-input').toggle();
    row.find('.btn-edit, .btn-delete').toggle();
    row.find('.btn-save, .btn-cancel').toggle();
    
    // Reaplicar máscara no campo de edição de contato, se visível
    const editContato = row.find('input[name="NumContato"].edit-input');
    if (editContato.is(':visible')) {
         var SPMaskBehavior = function (val) {
            return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
        },
        spOptions = {
            onKeyPress: function(val, e, field, options) {
                field.mask(SPMaskBehavior.apply({}, arguments), options);
            }
        };
        editContato.mask(SPMaskBehavior, spOptions);
    }
}

// Salva as alterações de um fornecedor
function salvarFornecedor(cnpj) {
    const row = $(`#row-${cnpj}`);
    const novoFornecedor = row.find('input[name="Fornecedor"]').val();
    const novoContato = row.find('input[name="NumContato"]').val().replace(/\D/g, ''); // Limpa a máscara

    if (novoFornecedor.trim() === '' || novoContato.trim() === '') {
        alert('Por favor, preencha todos os campos.');
        return;
    }
    
    if (novoContato.length < 10 || novoContato.length > 11) {
        alert('O número de contato deve ter 10 ou 11 dígitos.');
        return;
    }

    // Usar AJAX para enviar os dados para o script de atualização (Exemplo: update_fornecedor.php)
    $.post('update_fornecedor.php', {
        action: 'update',
        cnpj_antigo: cnpj,
        fornecedor: novoFornecedor,
        contato: novoContato
    }, function(response) {
        if (response.success) {
            alert('Fornecedor atualizado com sucesso!');
            window.location.reload(); 
        } else {
            alert('Erro ao atualizar fornecedor: ' + response.message);
        }
    }, 'json').fail(function() {
        alert('Erro de comunicação com o servidor ao salvar.');
    });
}

// Exclui um fornecedor
function excluirFornecedor(cnpj, nome) {
    if (confirm(`Tem certeza que deseja excluir o fornecedor "${nome}" (CNPJ: ${cnpj})? Esta ação é irreversível e pode afetar produtos relacionados.`)) {
        
        // Usar AJAX para enviar o CNPJ para o script de exclusão (Exemplo: delete_fornecedor.php)
        $.post('delete_fornecedor.php', {
            action: 'delete',
            cnpj: cnpj
        }, function(response) {
            if (response.success) {
                alert('Fornecedor excluído com sucesso!');
                $(`#row-${cnpj}`).fadeOut(500, function() {
                    $(this).remove();
                });
            } else {
                alert('Erro ao excluir fornecedor: ' + response.message);
            }
        }, 'json').fail(function() {
            alert('Erro de comunicação com o servidor ao excluir.');
        });
    }
}