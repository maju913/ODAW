<?php
date_default_timezone_set('America/Sao_Paulo');
session_start();
require __DIR__ . '/funcoes.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao   = $_POST['acao'] ?? '';
    $ancora = '';

    switch ($acao) {
        case 'calcular':
            $a  = $_POST['a'] ?? '';
            $b  = $_POST['b'] ?? '';
            $op = $_POST['op'] ?? '';

            if (is_numeric($a) && is_numeric($b)) {
                $a = (float) $a;
                $b = (float) $b;
                $_SESSION['msg_calc'] = formatarNumero($a) . " $op " . formatarNumero($b)
                                      . ' = ' . calcular($a, $b, $op);
            } else {
                $_SESSION['msg_calc'] = 'Digite dois números válidos.';
            }
            $ancora = '#calculadora';
            break;

        case 'senha':
            $_SESSION['msg_senha'] = gerarSenha(
                (int) ($_POST['tamanho'] ?? 12),
                isset($_POST['simbolos'])
            );
            $ancora = '#senha';
            break;

        case 'salvar_nome':
            $_SESSION['nome'] = mb_substr(trim($_POST['nome'] ?? ''), 0, 40);
            $ancora = '#saudacao-box';
            break;

        case 'limpar':
            session_unset();
            session_destroy();
            setcookie('ultima_visita', '', time() - 3600, '/');   // apaga o cookie
            break;
    }

    header('Location: ' . $_SERVER['PHP_SELF'] . $ancora);   // evita reenvio ao dar F5
    exit;
}

$ultimaVisita = $_COOKIE['ultima_visita'] ?? null;
setcookie('ultima_visita', date('d/m/Y H:i'), [
    'expires'  => time() + 60 * 60 * 24 * 30,   // 30 dias
    'path'     => '/',
    'samesite' => 'Lax',
]);
$_SESSION['acessos'] = ($_SESSION['acessos'] ?? 0) + 1;

$msgCalc  = $_SESSION['msg_calc']  ?? null;
$msgSenha = $_SESSION['msg_senha'] ?? null;
unset($_SESSION['msg_calc'], $_SESSION['msg_senha']);

$nomeSalvo = $_SESSION['nome'] ?? '';

$visitas = contarVisita(__DIR__ . '/contador.txt');

$frase  = 'PHP é muito legal';
$frutas = ['banana', 'maçã', 'uva', 'laranja'];
$notas  = [7.5, 8, 6.5, 9];

$frutasOrdenadas  = $frutas;
sort($frutasOrdenadas);
$frutasMaiusculas = array_map('mb_strtoupper', $frutas);
$stats            = estatisticas($notas);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Exercícios PHP - inicial</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h1>Exercícios PHP - inicial</h1>

<section>
    <h2>1 - Data e hora atual</h2>
    <p>Hoje é <?= date('d/m/Y') ?> e agora são <?= date('H:i') ?>h</p>
</section>

<section>
    <h2>2 - Functions</h2>

    <div class="bloco" id="calculadora">
        <h3>Calculadora</h3>
        <form method="post" class="linha">
            <input type="hidden" name="acao" value="calcular">
            <input type="number" name="a" step="any" placeholder="Número 1" required>
            <select name="op">
                <option value="+">+</option>
                <option value="-">−</option>
                <option value="*">×</option>
                <option value="/">÷</option>
            </select>
            <input type="number" name="b" step="any" placeholder="Número 2" required>
            <button type="submit">Calcular</button>
        </form>
        <?php if ($msgCalc): ?>
            <p class="resultado"><?= e($msgCalc) ?></p>
        <?php endif; ?>
    </div>

    <div class="bloco" id="saudacao-box">
        <h3>Saudação</h3>
        <form method="post" class="linha">
            <input type="hidden" name="acao" value="salvar_nome">
            <input type="text" name="nome" id="nome" placeholder="Digite seu nome"
                   maxlength="40" value="<?= e($nomeSalvo) ?>">
            <button type="submit">Salvar na sessão</button>
        </form>
        <p class="resultado" id="saudacao">Olá, visitante!</p>
    </div>

    <div class="bloco" id="senha">
        <h3>Gerador de senha</h3>
        <form method="post" class="linha">
            <input type="hidden" name="acao" value="senha">
            <label>Tamanho:
                <input type="number" name="tamanho" min="6" max="64" value="12">
            </label>
            <label><input type="checkbox" name="simbolos" checked> Símbolos</label>
            <button type="submit">Gerar</button>
        </form>
        <?php if ($msgSenha): ?>
            <p class="resultado">
                <code id="senhaGerada"><?= e($msgSenha) ?></code>
                <button type="button" id="btnCopiar">Copiar</button>
            </p>
        <?php endif; ?>
    </div>

    <div class="bloco">
        <h3>Funções de String e Array</h3>
        <p><strong>Frase:</strong> <?= e($frase) ?></p>
        <p><?= e(resumoTexto($frase)) ?></p>
        <p><strong>Frutas:</strong> <?= e(implode(', ', $frutas)) ?></p>
        <p><strong>Ordenadas (<code>sort</code>):</strong> <?= e(implode(', ', $frutasOrdenadas)) ?></p>
        <p><strong>Maiúsculas (<code>array_map</code>):</strong> <?= e(implode(', ', $frutasMaiusculas)) ?></p>
        <p><strong>Tem "uva"? (<code>in_array</code>):</strong> <?= in_array('uva', $frutas) ? 'Sim' : 'Não' ?></p>
        <p><strong>Notas:</strong> <?= implode(' / ', $notas) ?></p>
        <p>Soma: <?= formatarNumero($stats['soma']) ?> |
           Média: <?= formatarNumero($stats['media']) ?> |
           Maior: <?= formatarNumero($stats['maior']) ?> |
           Menor: <?= formatarNumero($stats['menor']) ?></p>
    </div>
</section>

<section>
    <h2>3 - Contador de visitas</h2>
    <p>Esta página foi visitada <strong><?= $visitas ?></strong> vezes.</p>
</section>

<section>
    <h2>4 - Cookie e Session</h2>

    <p><strong>Cookie:</strong>
        <?= $ultimaVisita
            ? 'Sua última visita foi em ' . e($ultimaVisita) . '.'
            : 'Esta é sua primeira visita (nenhum cookie encontrado).' ?>
    </p>

    <p><strong>Session:</strong> você abriu esta página
        <strong><?= $_SESSION['acessos'] ?></strong> vez(es) nesta sessão.</p>

    <p><strong>Nome na sessão:</strong>
        <?= $nomeSalvo !== '' ? e($nomeSalvo) : '(nenhum nome salvo)' ?></p>

    <form method="post">
        <input type="hidden" name="acao" value="limpar">
        <button type="submit">Limpar cookie e sessão</button>
    </form>
</section>

<script src="script.js"></script>
</body>
</html>