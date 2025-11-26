<?php
// Inicia a sessão (necessário para session_destroy() funcionar corretamente)
session_start();

// Destrói todas as variáveis de sessão
session_unset();

// Destrói a sessão
session_destroy(); 

// 🛑 ALTERAÇÃO: Redireciona para a página de Login/Cadastro.
// O caminho ideal dependeria da estrutura final, mas mantemos a referência CadLog.php
// (Se você deseja que o redirecionamento seja absoluto e não relativo ao local do script: header("Location: /template/CadLog.php");)
header("Location: ../template/CadLog.php"); 
exit; 
?>