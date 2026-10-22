<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/funcoes.php';

$idUsuario = exigirUsuarioLogado();
$usuario = null;
$erro = (string) ($_SESSION['flash_erro'] ?? '');
unset($_SESSION['flash_erro']);
$conexao = null;

try {
    $conexao = conectarBanco();
    $usuario = buscarCadastroPorId($conexao, $idUsuario);
} catch (mysqli_sql_exception $erroBanco) {
    error_log('Erro ao consultar cadastro do usuário: ' . $erroBanco->getMessage());
    $erro = 'Não foi possível carregar seu cadastro. Tente novamente.';
} finally {
    if ($conexao instanceof mysqli) {
        $conexao->close();
    }
}

if ($usuario === null && $erro === '') {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Meu cadastro | Cafezinho, Dona Helena?</title>
  <link rel="shortcut icon" href="imagens/tvicon.png" type="image/png">
  <link rel="stylesheet" href="css/auth.css">
</head>
<body>
  <a class="home-link" href="index.php">← Voltar ao site</a>
  <main class="auth-container wide">
    <section class="auth-card">
      <header class="auth-header">
        <span class="eyebrow">Área da conta</span>
        <h1>Meu cadastro</h1>
        <p>Confira as informações cadastradas na sua conta.</p>
      </header>

      <?php if (isset($_GET['alterado'])): ?>
        <p class="success-message" role="status">Cadastro atualizado com sucesso.</p>
      <?php endif; ?>
      <?php if ($erro !== ''): ?>
        <div class="error-summary" role="alert"><?= e($erro) ?></div>
      <?php elseif ($usuario !== null): ?>
        <dl class="summary">
          <div><dt>Nome</dt><dd><?= e($usuario['nome']) ?></dd></div>
          <div><dt>E-mail</dt><dd><?= e($usuario['email']) ?></dd></div>
          <div><dt>Nascimento</dt><dd><?= e(date('d/m/Y', strtotime($usuario['data_nascimento']))) ?></dd></div>
          <div><dt>Gênero</dt><dd><?= e($usuario['genero']) ?></dd></div>
          <div><dt>Novela favorita</dt><dd><?= e($usuario['novela_favorita']) ?></dd></div>
          <div><dt>Interesses</dt><dd><?= e(implode(', ', $usuario['interesses'])) ?></dd></div>
          <div><dt>Sobre você</dt><dd><?= e($usuario['bio']) ?></dd></div>
        </dl>

        <div class="form-actions">
          <a class="primary-button" href="editar_cadastro.php">Alterar cadastro</a>
          <form method="post" action="excluir_cadastro.php"
                onsubmit="return confirm('Tem certeza de que deseja excluir sua conta? Esta ação não pode ser desfeita.');">
            <input type="hidden" name="csrf_token" value="<?= e(tokenCsrf()) ?>">
            <button class="danger-button" type="submit">Excluir cadastro</button>
          </form>
        </div>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
