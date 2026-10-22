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

function dataValida(string $data): bool
{
    $objeto = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
    return $objeto !== false
        && $objeto->format('Y-m-d') === $data
        && $objeto <= new DateTimeImmutable('today');
}

function validarCadastro(array $dados): array
{
    $erros = [];
    $nome = trim((string) ($dados['nome'] ?? ''));
    $email = normalizarEmail((string) ($dados['email'] ?? ''));
    $senha = (string) ($dados['senha'] ?? '');
    $confirmacao = (string) ($dados['confirmar_senha'] ?? '');
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
    if (strlen($senha) < 8 || !preg_match('/[A-Za-z]/', $senha) || !preg_match('/\d/', $senha)) {
        $erros['senha'] = 'A senha deve ter no mínimo 8 caracteres, uma letra e um número.';
    }
    if ($senha !== $confirmacao) {
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
    if ($interesses === [] || array_diff($interesses, $interessesValidos)) {
        $erros['interesses'] = 'Escolha pelo menos um interesse válido.';
    }
    $bio = trim((string) ($dados['bio'] ?? ''));
    if (mb_strlen($bio) < 10 || strlen($bio) > 65535) {
        $erros['bio'] = 'Conte um pouco sobre você usando pelo menos 10 caracteres.';
    }
    if (!isset($dados['termos'])) {
        $erros['termos'] = 'Você precisa aceitar os termos para criar a conta.';
    }

    return $erros;
}
