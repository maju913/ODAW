<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/funcoes.php';

header('Content-Type: application/json; charset=utf-8');

function responder(int $status, array $dados): void
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, ['erro' => 'Método não permitido.']);
}

$dados = json_decode(file_get_contents('php://input'), true);
$resultadosValidos = ['Vale Tudo', 'Avenida Brasil', 'Senhora do Destino', 'A Favorita'];
if (!is_array($dados)
    || !is_string($dados['resultado'] ?? null)
    || !in_array($dados['resultado'], $resultadosValidos, true)) {
    responder(400, ['erro' => 'Resultado do quiz inválido.']);
}

$comentario = $dados['comentario'] ?? '';
if (!is_string($comentario) || strlen($comentario) > 65535) {
    responder(400, ['erro' => 'Comentário inválido.']);
}

$idUsuario = isset($_SESSION['usuario']['id']) ? (int) $_SESSION['usuario']['id'] : null;
$resultado = $dados['resultado'];
$comentario = trim($comentario);
$conexao = null;
$consulta = null;
$falhaAoSalvar = false;

try {
    $conexao = conectarBanco();
    $consulta = $conexao->prepare(
        'INSERT INTO resultado_quiz (id_usuario, resultado, comentario) VALUES (?, ?, ?)'
    );
    $consulta->bind_param('iss', $idUsuario, $resultado, $comentario);
    $consulta->execute();
} catch (mysqli_sql_exception $erroBanco) {
    error_log('Erro ao salvar resultado do quiz: ' . $erroBanco->getMessage());
    $falhaAoSalvar = true;
} finally {
    if ($consulta instanceof mysqli_stmt) {
        $consulta->close();
    }
    if ($conexao instanceof mysqli) {
        $conexao->close();
    }
}

if ($falhaAoSalvar) {
    responder(500, ['erro' => 'Não foi possível salvar o resultado no banco de dados.']);
}

responder(201, ['salvo' => true]);
