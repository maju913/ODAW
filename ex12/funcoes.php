<?php
declare(strict_types=1);

function e(mixed $valor): string
{
    return htmlspecialchars(is_scalar($valor) ? (string) $valor : '', ENT_QUOTES, 'UTF-8');
}

function normalizarEmail(string $email): string
{
    return mb_strtolower(trim($email));
}

function conectarBanco(): mysqli
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conexao = new mysqli('localhost', 'root', '', 'cafezinho_dona_helena');
    $conexao->set_charset('utf8mb4');

    return $conexao;
}

function buscarUsuarioPorEmail(mysqli $conexao, string $email): ?array
{
    $consulta = $conexao->prepare(
        'SELECT id, nome, email, senha FROM usuarios WHERE email = ? LIMIT 1'
    );
    $consulta->bind_param('s', $email);
    $consulta->execute();
    $usuario = $consulta->get_result()->fetch_assoc();
    $consulta->close();

    return $usuario ?: null;
}

function salvarUsuario(mysqli $conexao, array $usuario): int
{
    $interesses = json_encode($usuario['interesses'], JSON_UNESCAPED_UNICODE);
    if ($interesses === false) {
        throw new RuntimeException('Não foi possível serializar os interesses do usuário.');
    }

    $consulta = $conexao->prepare(
        'INSERT INTO usuarios
            (nome, email, senha, data_nascimento, genero, novela_favorita, interesses, bio)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $consulta->bind_param(
        'ssssssss',
        $usuario['nome'],
        $usuario['email'],
        $usuario['senha'],
        $usuario['data_nascimento'],
        $usuario['genero'],
        $usuario['novela_favorita'],
        $interesses,
        $usuario['bio']
    );
    $consulta->execute();
    $id = $conexao->insert_id;
    $consulta->close();

    return $id;
}

function buscarCadastroPorId(mysqli $conexao, int $id): ?array
{
    $consulta = $conexao->prepare(
        'SELECT id, nome, email, data_nascimento, genero, novela_favorita, interesses, bio
         FROM usuarios
         WHERE id = ?'
    );
    $consulta->bind_param('i', $id);
    $consulta->execute();
    $usuario = $consulta->get_result()->fetch_assoc();
    $consulta->close();

    if (!$usuario) {
        return null;
    }

    $interesses = json_decode((string) $usuario['interesses'], true);
    $usuario['interesses'] = is_array($interesses) ? $interesses : [];

    return $usuario;
}

function emailEmUso(mysqli $conexao, string $email, int $idIgnorado): bool
{
    $consulta = $conexao->prepare(
        'SELECT id FROM usuarios WHERE email = ? AND id <> ? LIMIT 1'
    );
    $consulta->bind_param('si', $email, $idIgnorado);
    $consulta->execute();
    $emUso = $consulta->get_result()->fetch_assoc() !== null;
    $consulta->close();

    return $emUso;
}

function atualizarUsuario(mysqli $conexao, int $id, array $usuario): void
{
    $interesses = json_encode($usuario['interesses'], JSON_UNESCAPED_UNICODE);
    if ($interesses === false) {
        throw new RuntimeException('Não foi possível serializar os interesses do usuário.');
    }

    if ($usuario['senha'] !== '') {
        $consulta = $conexao->prepare(
            'UPDATE usuarios
             SET nome = ?, email = ?, senha = ?, data_nascimento = ?, genero = ?,
                 novela_favorita = ?, interesses = ?, bio = ?
             WHERE id = ?'
        );
        $consulta->bind_param(
            'ssssssssi',
            $usuario['nome'],
            $usuario['email'],
            $usuario['senha'],
            $usuario['data_nascimento'],
            $usuario['genero'],
            $usuario['novela_favorita'],
            $interesses,
            $usuario['bio'],
            $id
        );
    } else {
        $consulta = $conexao->prepare(
            'UPDATE usuarios
             SET nome = ?, email = ?, data_nascimento = ?, genero = ?,
                 novela_favorita = ?, interesses = ?, bio = ?
             WHERE id = ?'
        );
        $consulta->bind_param(
            'sssssssi',
            $usuario['nome'],
            $usuario['email'],
            $usuario['data_nascimento'],
            $usuario['genero'],
            $usuario['novela_favorita'],
            $interesses,
            $usuario['bio'],
            $id
        );
    }

    $consulta->execute();
    $consulta->close();
}

function excluirUsuario(mysqli $conexao, int $id): void
{
    $consulta = $conexao->prepare('DELETE FROM usuarios WHERE id = ?');
    $consulta->bind_param('i', $id);
    $consulta->execute();
    $consulta->close();
}

function excluirResultadoQuiz(mysqli $conexao, int $id): void
{
    $consulta = $conexao->prepare('DELETE FROM resultado_quiz WHERE id = ?');
    $consulta->bind_param('i', $id);
    $consulta->execute();
    $consulta->close();
}

function adminTesteAtivo(): bool
{
    $enderecosLocais = ['127.0.0.1', '::1', '::ffff:127.0.0.1'];
    $modoTeste = getenv('EX12_ENABLE_TEST_ADMIN');
    if ($modoTeste === false) {
        $modoTeste = $_SERVER['EX12_ENABLE_TEST_ADMIN'] ?? '';
    }

    return $modoTeste === '1'
        && in_array($_SERVER['REMOTE_ADDR'] ?? '', $enderecosLocais, true);
}

function exigirAdminTeste(): void
{
    if (!adminTesteAtivo() || ($_SESSION['admin_teste'] ?? false) !== true) {
        http_response_code(403);
        exit('Acesso ao painel de teste não autorizado.');
    }
}

function tokenCsrf(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function tokenCsrfValido(mixed $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function exigirUsuarioLogado(): int
{
    $id = filter_var($_SESSION['usuario']['id'] ?? null, FILTER_VALIDATE_INT);
    if ($id === false || $id === null || $id < 1) {
        header('Location: login.php');
        exit;
    }

    return $id;
}

function dataValida(string $data): bool
{
    $objeto = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
    return $objeto !== false
        && $objeto->format('Y-m-d') === $data
        && $objeto <= new DateTimeImmutable('today');
}

function validarCadastro(array $dados, bool $senhaObrigatoria = true, bool $termosObrigatorios = true): array
{
    $erros = [];
    $nome = trim((string) ($dados['nome'] ?? ''));
    $email = normalizarEmail((string) ($dados['email'] ?? ''));
    $senha = (string) ($dados['senha'] ?? '');
    $confirmacao = (string) ($dados['confirmar_senha'] ?? '');
    $senhaInformada = $senha !== '' || $confirmacao !== '';
    $data = (string) ($dados['data_nascimento'] ?? '');
    $generos = ['Masculino', 'Feminino', 'Outro'];
    $novelas = ['Avenida Brasil', 'Vale Tudo', 'O Cravo e a Rosa', 'Senhora do Destino'];
    $interessesValidos = ['Notícias', 'Atrizes', 'Aberturas', 'Quiz'];
    $interesses = is_array($dados['interesses'] ?? null) ? $dados['interesses'] : [];

    if (mb_strlen($nome) < 3 || mb_strlen($nome) > 100 || !preg_match('/^[\p{L} .\'-]+$/u', $nome)) {
        $erros['nome'] = 'Informe um nome válido com pelo menos 3 caracteres.';
    }
    if (mb_strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros['email'] = 'Informe um endereço de e-mail válido.';
    }
    if (($senhaObrigatoria || $senhaInformada)
        && (strlen($senha) < 8 || !preg_match('/[A-Za-z]/', $senha) || !preg_match('/\d/', $senha))) {
        $erros['senha'] = 'A senha deve ter no mínimo 8 caracteres, uma letra e um número.';
    }
    if (($senhaObrigatoria || $senhaInformada) && $senha !== $confirmacao) {
        $erros['confirmar_senha'] = 'As senhas digitadas não são iguais.';
    }
    if (!dataValida($data)) {
        $erros['data_nascimento'] = 'Informe uma data de nascimento válida.';
    }
    if (!in_array($dados['genero'] ?? '', $generos, true)) {
        $erros['genero'] = 'Escolha uma opção de gênero.';
    }
    if (!in_array($dados['novela_favorita'] ?? '', $novelas, true)) {
        $erros['novela_favorita'] = 'Escolha uma novela favorita.';
    }
    if ($interesses === []
        || count(array_filter($interesses, static fn ($interesse): bool =>
            is_string($interesse) && in_array($interesse, $interessesValidos, true)
        )) !== count($interesses)) {
        $erros['interesses'] = 'Escolha pelo menos um interesse válido.';
    }
    $bio = trim((string) ($dados['bio'] ?? ''));
    if (mb_strlen($bio) < 10 || strlen($bio) > 65535) {
        $erros['bio'] = 'Conte um pouco sobre você usando pelo menos 10 caracteres.';
    }
    if ($termosObrigatorios && !isset($dados['termos'])) {
        $erros['termos'] = 'Você precisa aceitar os termos para criar a conta.';
    }

    return $erros;
}
