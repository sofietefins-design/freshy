<?php
/**
 * Freshy — Processamento de Registro de Consumo
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

try {
    $res = Movimentacao::registrarConsumo($usuarioId, $produtoId, $quantidade, $motivo);

    $msg = "Consumo de {$res['quantidade']} {$res['unidade']} de '{$res['produto']}' registrado! Novo estoque: {$res['novo_estoque']} {$res['unidade']}.";
    if ($res['estoque_baixo']) {
        $msg .= " ⚠️ ATENÇÃO: O estoque deste produto ficou igual ou abaixo do nível mínimo.";
    }

    flash('success', $msg);
} catch (Exception $e) {
    flash('error', $e->getMessage());
}

redirect('estoque.php');
