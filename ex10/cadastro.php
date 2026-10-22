<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/funcoes.php';

$arquivoUsuarios = __DIR__ . '/autenticacao.txt';
$dados = [];
$erros = [];
$cadastroConcluido = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = $_POST;
    $erros = validarCadastro($dados);
    $email = normalizarEmail((string) ($dados['email'] ?? ''));

    if (!isset($erros['email']) && isset(carregarUsuarios($arquivoUsuarios)[$email])) {
        $erros['email'] = 'Já existe uma conta cadastrada com este e-mail.';
    }

    if ($erros === []) {
        $usuario = [
            'nome' => trim((string) $dados['nome']),
            'email' => $email,
            'senha' => password_hash((string) $dados['senha'], PASSWORD_DEFAULT),
            'data_nascimento' => (string) $dados['data_nascimento'],
            'genero' => (string) $dados['genero'],
            'novela_favorita' => (string) $dados['novela_favorita'],
            'interesses' => array_values($dados['interesses']),
            'bio' => trim((string) $dados['bio']),
        ];

        if (salvarUsuario($arquivoUsuarios, $usuario)) {
            $cadastroConcluido = true;
        } else {
            $erros['geral'] = 'Não foi possível salvar a conta. Tente novamente.';
        }
    }
}

function marcado(string $campo, string $valor, array $dados): string
{
    $atual = $dados[$campo] ?? null;
    return (is_array($atual) ? in_array($valor, $atual, true) : $atual === $valor) ? ' checked' : '';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Criar conta | Cafezinho, Dona Helena?</title>
  <link rel="shortcut icon" href="imagens/tvicon.png" type="image/png">
  <link rel="stylesheet" href="css/auth.css">
</head>
<body>
  <a class="home-link" href="index.php">← Voltar ao site</a>
  <main class="auth-container wide">
    <section class="auth-card">
      <?php if ($cadastroConcluido): ?>
        <div class="confirmation" role="status">
          <span class="confirmation-icon">✓</span>
          <h1>Conta criada!</h1>
          <p>Confira as informações cadastradas:</p>
          <dl class="summary">
            <div><dt>Nome</dt><dd><?= e($usuario['nome']) ?></dd></div>
            <div><dt>E-mail</dt><dd><?= e($usuario['email']) ?></dd></div>
            <div><dt>Nascimento</dt><dd><?= e(date('d/m/Y', strtotime($usuario['data_nascimento']))) ?></dd></div>
            <div><dt>Gênero</dt><dd><?= e($usuario['genero']) ?></dd></div>
            <div><dt>Novela favorita</dt><dd><?= e($usuario['novela_favorita']) ?></dd></div>
            <div><dt>Interesses</dt><dd><?= e(implode(', ', $usuario['interesses'])) ?></dd></div>
            <div><dt>Sobre você</dt><dd><?= e($usuario['bio']) ?></dd></div>
          </dl>
          <p class="security-note">A senha foi cifrada antes de ser armazenada e não é exibida.</p>
          <a class="primary-button" href="login.php">Fazer login</a>
        </div>
      <?php else: ?>
        <header class="auth-header">
          <span class="eyebrow">Novo capítulo</span>
          <h1>Crie sua conta</h1>
          <p>Preencha todos os campos para participar do nosso clube de noveleiros.</p>
        </header>

        <?php if ($erros): ?>
          <div class="error-summary" role="alert">
            <strong>Revise o formulário.</strong>
            <ul>
              <?php foreach ($erros as $erro): ?>
                <li><?= e($erro) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" action="cadastro.php" novalidate>
          <div class="form-grid">
            <div class="field full">
              <label for="nome">Nome completo</label>
              <input type="text" id="nome" name="nome" value="<?= e($dados['nome'] ?? '') ?>" autocomplete="name" required>
              <?php if (isset($erros['nome'])): ?><span class="field-error"><?= e($erros['nome']) ?></span><?php endif; ?>
            </div>

            <div class="field full">
              <label for="email">E-mail</label>
              <input type="email" id="email" name="email" value="<?= e($dados['email'] ?? '') ?>" autocomplete="email" required>
              <?php if (isset($erros['email'])): ?><span class="field-error"><?= e($erros['email']) ?></span><?php endif; ?>
            </div>

            <div class="field">
              <label for="senha">Senha</label>
              <input type="password" id="senha" name="senha" autocomplete="new-password" required>
              <small>Mínimo de 8 caracteres, com letra e número.</small>
              <?php if (isset($erros['senha'])): ?><span class="field-error"><?= e($erros['senha']) ?></span><?php endif; ?>
            </div>

            <div class="field">
              <label for="confirmar_senha">Confirme a senha</label>
              <input type="password" id="confirmar_senha" name="confirmar_senha" autocomplete="new-password" required>
              <?php if (isset($erros['confirmar_senha'])): ?><span class="field-error"><?= e($erros['confirmar_senha']) ?></span><?php endif; ?>
            </div>

            <div class="field">
              <label for="data_nascimento">Data de nascimento</label>
              <input type="date" id="data_nascimento" name="data_nascimento" value="<?= e($dados['data_nascimento'] ?? '') ?>" max="<?= date('Y-m-d') ?>" required>
              <?php if (isset($erros['data_nascimento'])): ?><span class="field-error"><?= e($erros['data_nascimento']) ?></span><?php endif; ?>
            </div>

            <div class="field">
              <label for="novela_favorita">Novela favorita</label>
              <select id="novela_favorita" name="novela_favorita" required>
                <option value="">Selecione</option>
                <?php foreach (['Avenida Brasil', 'Vale Tudo', 'O Cravo e a Rosa', 'Senhora do Destino'] as $novela): ?>
                  <option value="<?= e($novela) ?>"<?= ($dados['novela_favorita'] ?? '') === $novela ? ' selected' : '' ?>><?= e($novela) ?></option>
                <?php endforeach; ?>
              </select>
              <?php if (isset($erros['novela_favorita'])): ?><span class="field-error"><?= e($erros['novela_favorita']) ?></span><?php endif; ?>
            </div>
          </div>

          <fieldset>
            <legend>Gênero</legend>
            <div class="options">
              <?php foreach (['Feminino', 'Masculino', 'Não binário', 'Prefiro não informar'] as $genero): ?>
                <label><input type="radio" name="genero" value="<?= e($genero) ?>"<?= marcado('genero', $genero, $dados) ?>> <?= e($genero) ?></label>
              <?php endforeach; ?>
            </div>
            <?php if (isset($erros['genero'])): ?><span class="field-error"><?= e($erros['genero']) ?></span><?php endif; ?>
          </fieldset>

          <fieldset>
            <legend>Assuntos de interesse</legend>
            <div class="options">
              <?php foreach (['Notícias', 'Atrizes', 'Aberturas', 'Quiz'] as $interesse): ?>
                <label><input type="checkbox" name="interesses[]" value="<?= e($interesse) ?>"<?= marcado('interesses', $interesse, $dados) ?>> <?= e($interesse) ?></label>
              <?php endforeach; ?>
            </div>
            <?php if (isset($erros['interesses'])): ?><span class="field-error"><?= e($erros['interesses']) ?></span><?php endif; ?>
          </fieldset>

          <div class="field full">
            <label for="bio">Conte um pouco sobre você</label>
            <textarea id="bio" name="bio" rows="4" required><?= e($dados['bio'] ?? '') ?></textarea>
            <?php if (isset($erros['bio'])): ?><span class="field-error"><?= e($erros['bio']) ?></span><?php endif; ?>
          </div>

          <label class="terms"><input type="checkbox" name="termos" value="1"<?= isset($dados['termos']) ? ' checked' : '' ?>> Li e aceito os termos de participação.</label>
          <?php if (isset($erros['termos'])): ?><span class="field-error"><?= e($erros['termos']) ?></span><?php endif; ?>
          <?php if (isset($erros['geral'])): ?><span class="field-error"><?= e($erros['geral']) ?></span><?php endif; ?>

          <div class="form-actions">
            <button class="primary-button" type="submit">Criar conta</button>
            <button class="secondary-button" type="reset">Limpar</button>
          </div>
        </form>
        <p class="switch-page">Já possui uma conta? <a href="login.php">Faça login</a>.</p>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
