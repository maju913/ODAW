<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/funcoes.php';

$idUsuario = exigirUsuarioLogado();
$conexao = null;
$usuarioAtual = null;
$dados = [];
$erros = [];
$erroGeral = '';

try {
    $conexao = conectarBanco();
    $usuarioAtual = buscarCadastroPorId($conexao, $idUsuario);
} catch (mysqli_sql_exception $erroBanco) {
    error_log('Erro ao carregar cadastro para edição: ' . $erroBanco->getMessage());
    $erroGeral = 'Não foi possível carregar seu cadastro. Tente novamente.';
} finally {
    if ($conexao instanceof mysqli) {
        $conexao->close();
        $conexao = null;
    }
}

if ($usuarioAtual === null && $erroGeral === '') {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $erroGeral === '') {
    $dados = $_POST;
    if (!tokenCsrfValido($dados['csrf_token'] ?? null)) {
        $erroGeral = 'A solicitação expirou. Atualize a página e tente novamente.';
    } else {
        $erros = validarCadastro($dados, false, false);
        $email = normalizarEmail((string) ($dados['email'] ?? ''));

        if ($erros === []) {
            $usuario = [
                'nome' => trim((string) $dados['nome']),
                'email' => $email,
                'senha' => (string) ($dados['senha'] ?? '') === ''
                    ? ''
                    : password_hash((string) $dados['senha'], PASSWORD_DEFAULT),
                'data_nascimento' => (string) $dados['data_nascimento'],
                'genero' => (string) $dados['genero'],
                'novela_favorita' => (string) $dados['novela_favorita'],
                'interesses' => array_values($dados['interesses']),
                'bio' => trim((string) $dados['bio']),
            ];

            try {
                $conexao = conectarBanco();
                if (emailEmUso($conexao, $email, $idUsuario)) {
                    $erros['email'] = 'Este e-mail já está sendo usado por outra conta.';
                } else {
                    atualizarUsuario($conexao, $idUsuario, $usuario);
                    $_SESSION['usuario']['nome'] = $usuario['nome'];
                    $_SESSION['usuario']['email'] = $usuario['email'];
                    header('Location: meu_cadastro.php?alterado=1');
                    exit;
                }
            } catch (mysqli_sql_exception $erroBanco) {
                error_log('Erro ao atualizar cadastro do usuário: ' . $erroBanco->getMessage());
                if ((int) $erroBanco->getCode() === 1062) {
                    $erros['email'] = 'Este e-mail já está sendo usado por outra conta.';
                } else {
                    $erroGeral = 'Não foi possível atualizar seu cadastro. Tente novamente.';
                }
            } catch (RuntimeException $erroBanco) {
                error_log('Erro ao preparar atualização do usuário: ' . $erroBanco->getMessage());
                $erroGeral = 'Não foi possível atualizar seu cadastro. Tente novamente.';
            } finally {
                if ($conexao instanceof mysqli) {
                    $conexao->close();
                }
            }
        }
    }
}

if ($dados === [] && $usuarioAtual !== null) {
    $dados = $usuarioAtual;
}

function valorSelecionado(string $campo, string $valor, array $dados): string
{
    return (string) ($dados[$campo] ?? '') === $valor ? ' selected' : '';
}

function opcaoMarcada(string $campo, string $valor, array $dados): string
{
    return is_array($dados[$campo] ?? null) && in_array($valor, $dados[$campo], true)
        ? ' checked'
        : '';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Alterar cadastro | Cafezinho, Dona Helena?</title>
  <link rel="shortcut icon" href="imagens/tvicon.png" type="image/png">
  <link rel="stylesheet" href="css/auth.css">
</head>
<body>
  <a class="home-link" href="meu_cadastro.php">← Meu cadastro</a>
  <main class="auth-container wide">
    <section class="auth-card">
      <header class="auth-header">
        <span class="eyebrow">Área da conta</span>
        <h1>Alterar cadastro</h1>
        <p>Deixe a senha em branco para mantê-la como está.</p>
      </header>

      <?php if ($erroGeral !== ''): ?>
        <div class="error-summary" role="alert"><?= e($erroGeral) ?></div>
      <?php endif; ?>
      <?php if ($erros !== []): ?>
        <div class="error-summary" role="alert">
          <strong>Revise os campos destacados.</strong>
          <ul>
            <?php foreach ($erros as $erro): ?>
              <li><?= e($erro) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($usuarioAtual !== null): ?>
        <form method="post" action="editar_cadastro.php" novalidate>
          <input type="hidden" name="csrf_token" value="<?= e(tokenCsrf()) ?>">
          <div class="form-grid">
            <div class="field full">
              <label for="nome">Nome completo</label>
              <input type="text" id="nome" name="nome" maxlength="100" value="<?= e($dados['nome'] ?? '') ?>" required>
              <?php if (isset($erros['nome'])): ?><span class="field-error"><?= e($erros['nome']) ?></span><?php endif; ?>
            </div>
            <div class="field full">
              <label for="email">E-mail</label>
              <input type="email" id="email" name="email" maxlength="100" value="<?= e($dados['email'] ?? '') ?>" required>
              <?php if (isset($erros['email'])): ?><span class="field-error"><?= e($erros['email']) ?></span><?php endif; ?>
            </div>
            <div class="field">
              <label for="senha">Nova senha (opcional)</label>
              <input type="password" id="senha" name="senha" autocomplete="new-password">
              <small>Se alterar, use pelo menos 8 caracteres, com letra e número.</small>
              <?php if (isset($erros['senha'])): ?><span class="field-error"><?= e($erros['senha']) ?></span><?php endif; ?>
            </div>
            <div class="field">
              <label for="confirmar_senha">Confirme a nova senha</label>
              <input type="password" id="confirmar_senha" name="confirmar_senha" autocomplete="new-password">
              <?php if (isset($erros['confirmar_senha'])): ?><span class="field-error"><?= e($erros['confirmar_senha']) ?></span><?php endif; ?>
            </div>
            <div class="field">
              <label for="data_nascimento">Data de nascimento</label>
              <input type="date" id="data_nascimento" name="data_nascimento" max="<?= date('Y-m-d') ?>" value="<?= e($dados['data_nascimento'] ?? '') ?>" required>
              <?php if (isset($erros['data_nascimento'])): ?><span class="field-error"><?= e($erros['data_nascimento']) ?></span><?php endif; ?>
            </div>
            <div class="field">
              <label for="novela_favorita">Novela favorita</label>
              <select id="novela_favorita" name="novela_favorita" required>
                <option value="">Selecione</option>
                <?php foreach (['Avenida Brasil', 'Vale Tudo', 'O Cravo e a Rosa', 'Senhora do Destino'] as $novela): ?>
                  <option value="<?= e($novela) ?>"<?= valorSelecionado('novela_favorita', $novela, $dados) ?>><?= e($novela) ?></option>
                <?php endforeach; ?>
              </select>
              <?php if (isset($erros['novela_favorita'])): ?><span class="field-error"><?= e($erros['novela_favorita']) ?></span><?php endif; ?>
            </div>
          </div>

          <fieldset>
            <legend>Gênero</legend>
            <div class="options">
              <?php foreach (['Masculino', 'Feminino', 'Outro'] as $genero): ?>
                <label><input type="radio" name="genero" value="<?= e($genero) ?>"<?= opcaoMarcada('genero', $genero, $dados) ?> required> <?= e($genero) ?></label>
              <?php endforeach; ?>
            </div>
            <?php if (isset($erros['genero'])): ?><span class="field-error"><?= e($erros['genero']) ?></span><?php endif; ?>
          </fieldset>

          <fieldset>
            <legend>Assuntos de interesse</legend>
            <div class="options">
              <?php foreach (['Notícias', 'Atrizes', 'Aberturas', 'Quiz'] as $interesse): ?>
                <label><input type="checkbox" name="interesses[]" value="<?= e($interesse) ?>"<?= opcaoMarcada('interesses', $interesse, $dados) ?>> <?= e($interesse) ?></label>
              <?php endforeach; ?>
            </div>
            <?php if (isset($erros['interesses'])): ?><span class="field-error"><?= e($erros['interesses']) ?></span><?php endif; ?>
          </fieldset>

          <div class="field full">
            <label for="bio">Conte um pouco sobre você</label>
            <textarea id="bio" name="bio" rows="4" required><?= e($dados['bio'] ?? '') ?></textarea>
            <?php if (isset($erros['bio'])): ?><span class="field-error"><?= e($erros['bio']) ?></span><?php endif; ?>
          </div>

          <div class="form-actions">
            <button class="primary-button" type="submit">Salvar alterações</button>
            <a class="secondary-button" href="meu_cadastro.php">Cancelar</a>
          </div>
        </form>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
