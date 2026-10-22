<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/funcoes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método não permitido.');
}

$idUsuario = exigirUsuarioLogado();
if (!tokenCsrfValido($_POST['csrf_token'] ?? null)) {
    $_SESSION['flash_erro'] = 'A solicitação expirou. Atualize a página e tente novamente.';
    header('Location: meu_cadastro.php');
    exit;
}

$conexao = null;
$erroAoExcluir = false;
try {
    $conexao = conectarBanco();
    excluirUsuario($conexao, $idUsuario);
} catch (mysqli_sql_exception $erroBanco) {
    error_log('Erro ao excluir cadastro do usuário: ' . $erroBanco->getMessage());
    $erroAoExcluir = true;
} finally {
    if ($conexao instanceof mysqli) {
        $conexao->close();
    }
}

if ($erroAoExcluir) {
    $_SESSION['flash_erro'] = 'Não foi possível excluir seu cadastro. Tente novamente.';
    header('Location: meu_cadastro.php');
    exit;
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $parametros = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $parametros['path'],
        $parametros['domain'],
        $parametros['secure'],
        $parametros['httponly']
    );
}
session_destroy();

header('Location: index.php');
exit;
