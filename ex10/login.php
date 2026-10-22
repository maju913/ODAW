<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/funcoes.php';

if (isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}

$email = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = normalizarEmail((string) ($_POST['email'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');

    if ($email === '' || $senha === '') {
        $erro = 'Preencha o e-mail e a senha.';
    } else {
        $usuario = carregarUsuarios(__DIR__ . '/autenticacao.txt')[$email] ?? null;
        if ($usuario && password_verify($senha, (string) $usuario['senha'])) {
            session_regenerate_id(true);
            $_SESSION['usuario'] = ['nome' => $usuario['nome'], 'email' => $usuario['email']];
            header('Location: index.php');
            exit;
        }
        $erro = 'E-mail ou senha incorretos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | Cafezinho, Dona Helena?</title>
  <link rel="shortcut icon" href="imagens/tvicon.png" type="image/png">
  <link rel="stylesheet" href="css/auth.css">
</head>
<body>
  <a class="home-link" href="index.php">← Voltar ao site</a>
  <main class="auth-container">
    <section class="auth-card login-card">
      <header class="auth-header">
        <span class="eyebrow">Bem-vinda de volta</span>
        <h1>Entre na sua conta</h1>
        <p>O próximo capítulo está esperando por você.</p>
      </header>

      <?php if ($erro): ?><div class="error-summary" role="alert"><?= e($erro) ?></div><?php endif; ?>

      <form method="post" action="login.php">
        <div class="field full">
          <label for="email">E-mail</label>
          <input type="email" id="email" name="email" value="<?= e($email) ?>" autocomplete="email" required autofocus>
        </div>
        <div class="field full">
          <label for="senha">Senha</label>
          <input type="password" id="senha" name="senha" autocomplete="current-password" required>
        </div>
        <button class="primary-button full-button" type="submit">Entrar</button>
      </form>
      <p class="switch-page">Ainda não possui conta? <a href="cadastro.php">Cadastre-se</a>.</p>
    </section>
  </main>
</body>
</html>

