<?php
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

function formatarNumero(float $n): string
{
    $txt = number_format($n, 6, ',', '.');
    return rtrim(rtrim($txt, '0'), ',');
}

function calcular(float $a, float $b, string $op): string
{
    return match ($op) {
        '+'     => formatarNumero($a + $b),
        '-'     => formatarNumero($a - $b),
        '*'     => formatarNumero($a * $b),
        '/'     => $b == 0 ? 'Erro: divisão por zero' : formatarNumero($a / $b),
        default => 'Operação inválida',
    };
}

function gerarSenha(int $tamanho = 12, bool $simbolos = true): string
{
    $tamanho = max(6, min($tamanho, 64));   // limita entre 6 e 64
    $chars   = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    if ($simbolos) {
        $chars .= '!@#$%&*?';
    }

    $senha = '';
    $max   = strlen($chars) - 1;
    for ($i = 0; $i < $tamanho; $i++) {
        $senha .= $chars[random_int(0, $max)];
    }
    return $senha;
}

function resumoTexto(string $texto): string
{
    $palavras = explode(' ', $texto);                        // string -> array
    $invertido = implode('', array_reverse(mb_str_split($texto)));

    return sprintf(
        'Maiúsculas: %s | Tamanho: %d caracteres | Invertido: %s | Palavras: %d',
        mb_strtoupper($texto),
        mb_strlen($texto),
        $invertido,
        count($palavras)
    );
}

function estatisticas(array $numeros): array
{
    return [
        'soma'   => array_sum($numeros),
        'media'  => array_sum($numeros) / count($numeros),
        'maior'  => max($numeros),
        'menor'  => min($numeros),
    ];
}

function contarVisita(string $arquivo): int
{
    $total = file_exists($arquivo) ? (int) file_get_contents($arquivo) : 0;
    $total++;
    file_put_contents($arquivo, (string) $total, LOCK_EX);
    return $total;
}