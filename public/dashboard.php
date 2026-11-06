<?php
/**
 * Freshy — Painel de Controle Principal (Dashboard com Métricas Reais)
 */

require_once __DIR__ . '/../app/config/config.php';

require_auth();

$usuarioId = user_id();
$user = current_user();
$pageTitle = 'Dashboard';
$activePage = 'dashboard';

// Carrega métricas e dados reais do banco
$stats        = Produto::getStats($usuarioId);
$recentes     = Movimentacao::getRecentes($usuarioId, 6);
$itensCompras = ListaCompras::getItens($usuarioId);
$comprasPendentes = array_filter($itensCompras, fn($i) => (int)$i['comprado'] === 0);

// Produtos críticos ou vencidos em destaque
$produtosAlerta = Produto::allByUsuario($usuarioId, ['situacao' => 'critico']);
$produtosVencidos = Produto::allByUsuario($usuarioId, ['situacao' => 'vencido']);

require_once APP_PATH . '/views/partials/header.php';
require_once APP_PATH . '/views/partials/navbar.php';
require_once APP_PATH . '/views/partials/alerts.php';
?>

<main class="py-4">
    <div class="container">
        <!-- Boas-vindas e Ações Rápidas -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">
                    Olá, <span class="text-success"><?= e($user['nome']) ?></span>! 🍃
                </h1>
                <p class="text-muted mb-0 small">
                    Aqui está o panorama em tempo real do seu estoque doméstico hoje (<?= date('d/m/Y') ?>).
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= url('estoque.php') ?>" class="btn btn-freshy btn-sm px-3 shadow-sm">
                    <i class="bi bi-boxes"></i> Ver MyStock
                </a>
                <a href="<?= url('compras.php') ?>" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="bi bi-cart3"></i> Lista de Compras
                </a>
            </div>
        </div>

        <!-- Alerta em Destaque se houver Vencidos -->
        <?php if ($stats['vencidos'] > 0): ?>
            <div class="alert alert-danger d-flex align-items-center justify-content-between shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-diamond-fill fs-4"></i>
                    <div>
                        <strong>Atenção:</strong> Você possui <strong><?= $stats['vencidos'] ?> produto(s) vencido(s)</strong> no estoque.
                        Verifique para registrar o consumo ou o descarte adequado.
                    </div>
                </div>
                <a href="<?= url('estoque.php?situacao=vencido') ?>" class="btn btn-danger btn-sm text-nowrap ms-2">
                    Ver Vencidos
                </a>
            </div>
        <?php endif; ?>

        <!-- Cards com Métricas Reais do Banco -->
        <div class="row g-3 mb-4">
            <!-- Total de Produtos -->
            <div class="col-12 col-sm-6 col-lg-3">
                <a href="<?= url('estoque.php') ?>" class="text-decoration-none">
                    <div class="freshy-card p-3 shadow-sm h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Total no Estoque</span>
                            <div class="bg-success-subtle text-success p-2 rounded-circle">
                                <i class="bi bi-boxes fs-5"></i>
                            </div>
                        </div>
                        <h2 class="h3 fw-bold text-dark mb-0"><?= $stats['total'] ?></h2>
                        <small class="text-muted">Itens ativos monitorados</small>
                    </div>
                </a>
            </div>

            <!-- Estoque Baixo -->
            <div class="col-12 col-sm-6 col-lg-3">
                <a href="<?= url('estoque.php?situacao=baixo') ?>" class="text-decoration-none">
                    <div class="freshy-card p-3 shadow-sm h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Estoque Baixo</span>
                            <div class="bg-warning-subtle text-warning p-2 rounded-circle">
                                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                            </div>
                        </div>
                        <h2 class="h3 fw-bold text-warning mb-0"><?= $stats['baixo'] ?></h2>
                        <small class="text-muted">Quantidade &le; Mínimo</small>
                    </div>
                </a>
            </div>

            <!-- Críticos (0 a 3 dias) -->
            <div class="col-12 col-sm-6 col-lg-3">
                <a href="<?= url('estoque.php?situacao=critico') ?>" class="text-decoration-none">
                    <div class="freshy-card p-3 shadow-sm h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Críticos (0 a 3 dias)</span>
                            <div class="bg-danger-subtle text-danger p-2 rounded-circle">
                                <i class="bi bi-alarm-fill fs-5"></i>
                            </div>
                        </div>
                        <h2 class="h3 fw-bold text-danger mb-0"><?= $stats['criticos'] ?></h2>
                        <small class="text-muted">Consumir urgentemente</small>
                    </div>
                </a>
            </div>

            <!-- Lista de Compras Pendente -->
            <div class="col-12 col-sm-6 col-lg-3">
                <a href="<?= url('compras.php') ?>" class="text-decoration-none">
                    <div class="freshy-card p-3 shadow-sm h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Para Comprar</span>
                            <div class="bg-info-subtle text-info p-2 rounded-circle">
                                <i class="bi bi-cart3 fs-5"></i>
                            </div>
                        </div>
                        <h2 class="h3 fw-bold text-dark mb-0"><?= count($comprasPendentes) ?></h2>
                        <small class="text-muted">Itens na lista de compras</small>
                    </div>
                </a>
            </div>
        </div>

        <div class="row g-4">
            <!-- Coluna Principal: Movimentações Recentes (Consumo / Desperdício / Entrada) -->
            <div class="col-lg-8">
                <div class="freshy-card p-4 shadow-sm mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark mb-0">
                            <i class="bi bi-clock-history text-success"></i> Movimentações Recentes
                        </h5>
                        <a href="<?= url('historico.php') ?>" class="small text-decoration-none text-success fw-semibold">
                            Ver histórico completo &rarr;
                        </a>
                    </div>

                    <?php if (empty($recentes)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-journal-x display-6 d-block mb-2 text-secondary"></i>
                            Nenhuma movimentação registrada recentemente.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Produto</th>
                                        <th class="text-center">Quantidade</th>
                                        <th>Data / Hora</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentes as $mov): ?>
                                        <?php
                                        $tipoBadge = match($mov['tipo']) {
                                            'ENTRADA'     => 'bg-primary-subtle text-primary border border-primary-subtle',
                                            'CONSUMO'     => 'bg-success-subtle text-success border border-success-subtle',
                                            'DESPERDICIO' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                            default       => 'bg-secondary',
                                        };
                                        $tipoIcon = match($mov['tipo']) {
                                            'ENTRADA'     => 'bi-box-arrow-in-down',
                                            'CONSUMO'     => 'bi-check2-circle',
                                            'DESPERDICIO' => 'bi-trash3',
                                            default       => 'bi-circle',
                                        };
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="badge <?= $tipoBadge ?> d-inline-flex align-items-center gap-1">
                                                    <i class="bi <?= $tipoIcon ?>"></i> <?= e($mov['tipo']) ?>
                                                </span>
                                            </td>
                                            <td class="fw-semibold text-dark">
                                                <?= e($mov['produto_nome']) ?>
                                            </td>
                                            <td class="text-center fw-bold">
                                                <?= (float)$mov['quantidade'] ?> <small class="text-muted fw-normal"><?= e($mov['produto_unidade']) ?></small>
                                            </td>
                                            <td class="text-muted small">
                                                <?= format_datetime($mov['created_at']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Produtos com Atenção (Vencimento Próximo) -->
                <?php if (!empty($produtosAlerta)): ?>
                    <div class="freshy-card p-4 shadow-sm">
                        <h5 class="fw-bold text-dark mb-3">
                            <i class="bi bi-alarm text-warning"></i> Produtos que Vencem nos Próximos 3 Dias
                        </h5>
                        <div class="list-group list-group-flush">
                            <?php foreach ($produtosAlerta as $pa): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-bottom">
                                    <div>
                                        <strong class="text-dark"><?= e($pa['nome']) ?></strong>
                                        <small class="text-muted d-block">
                                            Local: <?= e($pa['local_nome']) ?> &bull; Estoque: <?= (float)$pa['quantidade'] ?> <?= e($pa['unidade']) ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge <?= e($pa['status_validade']['badge_class']) ?>">
                                            <?= e($pa['status_validade']['label']) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Coluna Lateral: Resumo da Lista de Compras e Dicas Rápidas -->
            <div class="col-lg-4">
                <!-- Card de Compras Pendentes -->
                <div class="freshy-card p-4 shadow-sm mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark mb-0">
                            <i class="bi bi-cart3 text-success"></i> Lista de Compras
                        </h5>
                        <a href="<?= url('compras.php') ?>" class="small text-success text-decoration-none fw-semibold">
                            Gerenciar &rarr;
                        </a>
                    </div>

                    <?php if (empty($comprasPendentes)): ?>
                        <div class="text-center py-3 text-muted small">
                            <i class="bi bi-bag-check display-6 d-block mb-1 text-success"></i>
                            Sua lista de compras está em dia!
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush mb-3">
                            <?php 
                            $mostrados = array_slice($comprasPendentes, 0, 5);
                            foreach ($mostrados as $item): 
                            ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                    <span class="text-dark"><?= e($item['nome_item']) ?></span>
                                    <span class="badge bg-light text-dark border">
                                        <?= (float)$item['quantidade'] ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (count($comprasPendentes) > 5): ?>
                            <small class="text-muted d-block text-center mb-2">
                                + <?= count($comprasPendentes) - 5 ?> outro(s) item(ns)...
                            </small>
                        <?php endif; ?>
                    <?php endif; ?>

                    <a href="<?= url('compras.php') ?>" class="btn btn-outline-success w-100 btn-sm">
                        <i class="bi bi-cart-plus"></i> Abrir Lista de Compras
                    </a>
                </div>

                <!-- Card de Dicas de Organização -->
                <div class="freshy-card p-4 shadow-sm bg-light">
                    <h6 class="fw-bold text-dark mb-2">
                        <i class="bi bi-shield-check text-success"></i> Dica de Frescor Freshy
                    </h6>
                    <p class="small text-muted mb-0">
                        Consuma primeiro os itens destacados em <strong>Crítico</strong> e <strong>Atenção</strong>.
                        Ao planejar refeições com base nos itens mais próximos da validade, sua família economiza e combate o desperdício alimentar.
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once APP_PATH . '/views/partials/footer.php'; ?>
