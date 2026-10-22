<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/funcoes.php';

/*
 * Painel de teste para visualizar e excluir cadastros e resultados do quiz.
 * Este painel só deve ser usado localmente com a variável de ambiente
 * EX12_ENABLE_TEST_ADMIN=1 configurada no servidor.
 * no xampp, por exemplo, você pode adicionar a linha abaixo no arquivo `C:\xampp\apache\bin\httpd.conf`:
 * SetEnv EX12_ENABLE_TEST_ADMIN 1
 * ubuntu/linux, você pode adicionar a linha abaixo no arquivo `/etc/apache2/envvars`:
 * export EX12_ENABLE_TEST_ADMIN=1
 * terminando, reinicie o servidor apache.
*/

if (!adminTesteAtivo()) {
    http_response_code(403);
    $adminHabilitado = false;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!tokenCsrfValido($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        $erro = 'A solicitação expirou. Volte à página inicial e tente novamente.';
    } else {
        $acao = (string) ($_POST['acao'] ?? '');
        if ($acao === 'entrar') {
            session_regenerate_id(true);
            $_SESSION['admin_teste'] = true;
            header('Location: admin.php');
            exit;
        }

        exigirAdminTeste();
        if ($acao === 'sair') {
            unset($_SESSION['admin_teste']);
            header('Location: index.php');
            exit;
        }

        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id === null || $id < 1) {
            http_response_code(400);
            $erro = 'Identificador inválido.';
        } elseif (!in_array($acao, ['excluir_usuario', 'excluir_quiz'], true)) {
            http_response_code(400);
            $erro = 'Ação inválida.';
        } else {
            $conexao = null;
            try {
                $conexao = conectarBanco();
                if ($acao === 'excluir_usuario') {
                    excluirUsuario($conexao, $id);
                    $_SESSION['flash_admin'] = 'Cadastro de usuário excluído. Os resultados associados foram mantidos sem vínculo.';
                } else {
                    excluirResultadoQuiz($conexao, $id);
                    $_SESSION['flash_admin'] = 'Resultado do quiz excluído.';
                }
            } catch (mysqli_sql_exception $erroBanco) {
                error_log('Erro em operação do painel de teste: ' . $erroBanco->getMessage());
                $_SESSION['flash_admin_erro'] = 'Não foi possível concluir a exclusão. Tente novamente.';
            } finally {
                if ($conexao instanceof mysqli) {
                    $conexao->close();
                }
            }
            header('Location: admin.php');
            exit;
        }
    }
} else {
    exigirAdminTeste();
    $adminHabilitado = true;
}

if (!isset($adminHabilitado)) {
    $adminHabilitado = adminTesteAtivo()
        && ($_SESSION['admin_teste'] ?? false) === true;
}

$erro = $erro ?? '';
$mensagem = (string) ($_SESSION['flash_admin'] ?? '');
$erroBanco = (string) ($_SESSION['flash_admin_erro'] ?? '');
unset($_SESSION['flash_admin'], $_SESSION['flash_admin_erro']);
$usuarios = [];
$resultados = [];

if ($adminHabilitado) {
    try {
        $conexao = conectarBanco();
        $usuarios = $conexao->query(
            'SELECT id, nome, email, data_nascimento, genero, novela_favorita, interesses, bio
             FROM usuarios
             ORDER BY id DESC'
        )->fetch_all(MYSQLI_ASSOC);
        $resultados = $conexao->query(
            'SELECT rq.id, rq.resultado, rq.comentario, u.nome AS nome_usuario, u.email AS email_usuario
             FROM resultado_quiz AS rq
             LEFT JOIN usuarios AS u ON u.id = rq.id_usuario
             ORDER BY rq.id DESC'
        )->fetch_all(MYSQLI_ASSOC);
        $conexao->close();
    } catch (mysqli_sql_exception $erroConsulta) {
        error_log('Erro ao consultar dados no painel de teste: ' . $erroConsulta->getMessage());
        $erroBanco = 'Não foi possível carregar os dados do banco de dados.';
        if (isset($conexao) && $conexao instanceof mysqli) {
            $conexao->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Painel Admin | Cafezinho, Dona Helena?</title>
  <link rel="shortcut icon" href="imagens/tvicon.png" type="image/png">
  <link rel="stylesheet" href="css/auth.css">
</head>
<body>
  <a class="home-link" href="index.php">← Página inicial</a>
  <main class="auth-container wide">
    <section class="auth-card admin-card">
      <?php if (!$adminHabilitado): ?>
        <header class="auth-header">
          <span class="eyebrow">Painel de teste</span>
          <h1>Acesso indisponível</h1>
          <p>O painel só pode ser usado localmente com <code>EX12_ENABLE_TEST_ADMIN=1</code> configurado no ambiente do servidor.</p>
        </header>
      <?php else: ?>
        <header class="auth-header">
          <span class="eyebrow">Painel de teste</span>
          <h1>Cadastros e resultados</h1>
          <p>Visualização dos registros; este painel permite apenas excluir.</p>
        </header>

        <form class="admin-logout" method="post" action="admin.php">
          <input type="hidden" name="csrf_token" value="<?= e(tokenCsrf()) ?>">
          <input type="hidden" name="acao" value="sair">
          <button class="secondary-button" type="submit">Sair do Admin</button>
        </form>

        <?php if ($erro !== ''): ?><div class="error-summary" role="alert"><?= e($erro) ?></div><?php endif; ?>
        <?php if ($erroBanco !== ''): ?><div class="error-summary" role="alert"><?= e($erroBanco) ?></div><?php endif; ?>
        <?php if ($mensagem !== ''): ?><p class="success-message" role="status"><?= e($mensagem) ?></p><?php endif; ?>

        <section class="admin-section" aria-labelledby="usuarios-heading">
          <h2 id="usuarios-heading">Cadastros de usuários (<?= count($usuarios) ?>)</h2>
          <?php if ($usuarios === []): ?>
            <p>Nenhum cadastro encontrado.</p>
          <?php else: ?>
            <?php foreach ($usuarios as $usuario): ?>
              <?php $interesses = json_decode((string) $usuario['interesses'], true); ?>
              <article class="admin-record">
                <h3><?= e($usuario['nome']) ?> <small>#<?= e($usuario['id']) ?></small></h3>
                <dl class="summary">
                  <div><dt>E-mail</dt><dd><?= e($usuario['email']) ?></dd></div>
                  <div><dt>Nascimento</dt><dd><?= e(date('d/m/Y', strtotime($usuario['data_nascimento']))) ?></dd></div>
                  <div><dt>Gênero</dt><dd><?= e($usuario['genero']) ?></dd></div>
                  <div><dt>Novela favorita</dt><dd><?= e($usuario['novela_favorita']) ?></dd></div>
                  <div><dt>Interesses</dt><dd><?= e(implode(', ', is_array($interesses) ? $interesses : [])) ?></dd></div>
                  <div><dt>Sobre</dt><dd><?= e($usuario['bio']) ?></dd></div>
                </dl>
                <form method="post" action="admin.php"
                      onsubmit="return confirm('Excluir o cadastro de <?= e($usuario['nome']) ?>? Esta ação não pode ser desfeita.');">
                  <input type="hidden" name="csrf_token" value="<?= e(tokenCsrf()) ?>">
                  <input type="hidden" name="acao" value="excluir_usuario">
                  <input type="hidden" name="id" value="<?= e($usuario['id']) ?>">
                  <button class="danger-button" type="submit">Excluir cadastro</button>
                </form>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </section>

        <section class="admin-section" aria-labelledby="quiz-heading">
          <h2 id="quiz-heading">Resultados dos quizzes (<?= count($resultados) ?>)</h2>
          <?php if ($resultados === []): ?>
            <p>Nenhum resultado encontrado.</p>
          <?php else: ?>
            <?php foreach ($resultados as $resultado): ?>
              <article class="admin-record">
                <h3>Resultado #<?= e($resultado['id']) ?>: <?= e($resultado['resultado']) ?></h3>
                <dl class="summary">
                  <div><dt>Usuário</dt><dd><?= e($resultado['nome_usuario'] ?? 'Conta excluída ou Inexistente') ?></dd></div>
                  <div><dt>E-mail</dt><dd><?= e($resultado['email_usuario'] ?? '—') ?></dd></div>
                  <div><dt>Comentário</dt><dd><?= e($resultado['comentario'] ?? '') ?></dd></div>
                </dl>
                <form method="post" action="admin.php"
                      onsubmit="return confirm('Excluir este resultado do quiz? Esta ação não pode ser desfeita.');">
                  <input type="hidden" name="csrf_token" value="<?= e(tokenCsrf()) ?>">
                  <input type="hidden" name="acao" value="excluir_quiz">
                  <input type="hidden" name="id" value="<?= e($resultado['id']) ?>">
                  <button class="danger-button" type="submit">Excluir resultado</button>
                </form>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </section>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
