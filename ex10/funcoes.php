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

function carregarUsuarios(string $arquivo): array
{
    if (!is_file($arquivo)) {
        return [];
    }

    $usuarios = [];
    foreach (file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $linha) {
        $usuario = json_decode($linha, true);
        if (is_array($usuario) && isset($usuario['email'], $usuario['senha'])) {
            $usuarios[normalizarEmail((string) $usuario['email'])] = $usuario;
        }
    }

    return $usuarios;
}

function salvarUsuario(string $arquivo, array $usuario): bool
{
    $handle = fopen($arquivo, 'c+');
    if ($handle === false || !flock($handle, LOCK_EX)) {
        return false;
    }

    rewind($handle);
    $conteudo = stream_get_contents($handle) ?: '';
    foreach (preg_split('/\R/', $conteudo, -1, PREG_SPLIT_NO_EMPTY) as $linha) {
        $existente = json_decode($linha, true);
        if (is_array($existente)
            && normalizarEmail((string) ($existente['email'] ?? '')) === normalizarEmail($usuario['email'])) {
            flock($handle, LOCK_UN);
            fclose($handle);
            return false;
        }
    }

    fseek($handle, 0, SEEK_END);
    $gravou = fwrite($handle, json_encode($usuario, JSON_UNESCAPED_UNICODE) . PHP_EOL) !== false;
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    return $gravou;
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
    $generos = ['Feminino', 'Masculino', 'Não binário', 'Prefiro não informar'];
    $novelas = ['Avenida Brasil', 'Vale Tudo', 'O Cravo e a Rosa', 'Senhora do Destino'];
    $interessesValidos = ['Notícias', 'Atrizes', 'Aberturas', 'Quiz'];
    $interesses = is_array($dados['interesses'] ?? null) ? $dados['interesses'] : [];

    if (mb_strlen($nome) < 3 || !preg_match('/^[\p{L} .\'-]+$/u', $nome)) {
        $erros['nome'] = 'Informe um nome válido com pelo menos 3 caracteres.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
    if (mb_strlen(trim((string) ($dados['bio'] ?? ''))) < 10) {
        $erros['bio'] = 'Conte um pouco sobre você usando pelo menos 10 caracteres.';
    }
    if (!isset($dados['termos'])) {
        $erros['termos'] = 'Você precisa aceitar os termos para criar a conta.';
    }

    return $erros;
}

