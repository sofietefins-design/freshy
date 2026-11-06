<?php
/**
 * Freshy — Processamento de Registro de Desperdício
 */

require_once __DIR__ . '/../app/config/config.php';

require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('estoque.php');
}

if (!csrf_verify()) {
    flash('error', 'Validação de segurança CSRF falhou. Tente novamente.');
    redirect('estoque.php');
}

$usuarioId  = user_id();
$produtoId  = (int) ($_POST['produto_id'] ?? 0);
$quantidade = (float) ($_POST['quantidade'] ?? 0);
$motivo     = trim($_POST['motivo'] ?? '');

if (empty($motivo)) {
    flash('error', 'O motivo do desperdício é obrigatório.');
    redirect('estoque.php');
}

try {
    $res = Movimentacao::registrarDesperdicio($usuarioId, $produtoId, $quantidade, $motivo);

    $msg = "Desperdício de {$res['quantidade']} {$res['unidade']} de '{$res['produto']}' registrado no histórico. Motivo: '{$motivo}'. Novo estoque: {$res['novo_estoque']} {$res['unidade']}.";
    flash('warning', $msg);
} catch (Exception $e) {
    flash('error', $e->getMessage());
}

redirect('estoque.php');
