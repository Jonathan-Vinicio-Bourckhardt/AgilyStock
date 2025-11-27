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

    // Fade out de alertas normais após 5 segundos
    setTimeout(function() {
        $('.alert:not(.is-temp-alert)').fadeOut('slow');
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
    // C. REMOVIDA: Lógica de clique na logo (Botão Sair dinâmico)
    // O botão Sair fixo agora está no rodapé do sidebar no PHP,
    // e o clique na logo não faz mais nada.
    // ==========================================================
    
    // ==========================================================
    // D. VALIDAÇÃO DE CAMPOS E MÁSCARAS
    // ==========================================================
    
    // Funções de Comportamento de Máscara (Telefone)
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
    
    // CORRIGIDO: Oculta apenas os campos editáveis. O CNPJ (view-cnpj) fica visível.
    row.find('.view-fornecedor, .view-contato').toggle();
    
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
    
    const inputFornecedor = row.find('input[name="Fornecedor"]');
    const inputContato = row.find('input[name="NumContato"]');

    const novoFornecedor = inputFornecedor.val().trim();
    const novoContatoComMascara = inputContato.val();
    const novoContato = novoContatoComMascara.replace(/\D/g, ''); // Limpa a máscara

    if (novoFornecedor === '') {
        alert('O nome do Fornecedor é obrigatório e não pode estar vazio.');
        inputFornecedor.focus();
        return;
    }
    
    if (novoContato === '') {
        alert('O número de Contato é obrigatório e não pode estar vazio.');
        inputContato.focus();
        return;
    }
    
    if (novoContato.length < 10 || novoContato.length > 11) {
        alert('O número de contato deve ter 10 ou 11 dígitos (sem contar o DDD).');
        return;
    }
    
    if (!cnpj || cnpj.replace(/\D/g, '').length !== 14) {
          alert('Erro de sistema: CNPJ de referência está faltando ou é inválido. Verifique o onclick no HTML.');
          return;
    }

    // CORREÇÃO DA URL: Apenas o nome do arquivo (resolve o erro 404 de atualização)
    $.post('atualizar_fornecedor.php', {
        action: 'update',
        cnpj_antigo: cnpj.replace(/\D/g, ''), // Envia CNPJ sem máscara
        fornecedor: novoFornecedor,
        contato: novoContato
    }, function(response) {
        if (response.success) {
            alert('Fornecedor atualizado com sucesso! ' + (response.message || ''));
            window.location.reload(); 
        } else {
            // Usa o erro retornado pelo servidor
            alert('Erro ao atualizar fornecedor: ' + response.error); 
        }
    }, 'json').fail(function(xhr) {
        // Agora, se cair aqui, é provavelmente um erro 500 ou PHP Warning
        alert(`Erro de comunicação com o servidor. Status: ${xhr.status} ${xhr.statusText}. Por favor, verifique o console para detalhes.`);
    });
}

// Exclui um fornecedor
function excluirFornecedor(cnpj, nome) {
    if (confirm(`Tem certeza que deseja excluir o fornecedor "${nome}" (CNPJ: ${cnpj})? Esta ação é irreversível e pode afetar produtos relacionados.`)) {
        
        // CORREÇÃO DA URL: Usando o nome correto do arquivo PHP para exclusão
        $.post('excluir_fornecedor.php', {
            action: 'delete',
            cnpj: cnpj.replace(/\D/g, '')
        }, function(response) {
            if (response.success) {
                alert('Fornecedor excluído com sucesso!');
                $(`#row-${cnpj}`).fadeOut(500, function() {
                    $(this).remove();
                });
            } else {
                // Assume que o PHP retorna a mensagem de erro no campo 'error'
                alert('Erro ao excluir fornecedor: ' + response.error); 
            }
        }, 'json').fail(function() {
            alert('Erro de comunicação com o servidor ao excluir.');
        });
    }
}