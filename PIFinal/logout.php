<?php
// OBRIGATÓRIO: Iniciar a sessão.
// Isso permite acessar e manipular as variáveis de sessão.
session_start();

// Limpa todas as variáveis de sessão
$_SESSION = array();

// Se for desejado destruir completamente a sessão, apague também o cookie de sessão.
// Nota: Isso irá destruir a sessão, e não apenas os dados da sessão.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destrói a sessão.
session_destroy();

// Redireciona para a página de login (CadLog.php).
// O caminho foi ajustado para apontar para o subdiretório 'template/', 
// que é um caminho comum para arquivos de login em projetos.
header('Location: template/CadLog.php');
exit;
?>