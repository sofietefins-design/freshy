<?php
/**
 * Freshy — Histórico Completo de Movimentações (Entradas, Consumo e Desperdício)
 */

require_once __DIR__ . '/../app/config/config.php';

require_auth();

$usuarioId = user_id();
$pageTitle = 'Histórico de Movimentações';
$activePage = 'historico';

// Filtros recebidos via GET
$filters = [
    'tipo'       => trim($_GET['tipo'] ?? ''),
    'produto_id' => !empty($_GET['produto_id']) ? (int) $_GET['produto_id'] : null,
];

// Dados do histórico e produtos do usuário
$movimentacoes = Movimentacao::allByUsuario($usuarioId, $filters);
$todosProdutos = Produto::allByUsuario($usuarioId);

// Contadores rápidos para resumo
$totalGeral     = count($movimentacoes);
$totalEntradas  = count(array_filter($movimentacoes, fn($m) => $m['tipo'] === 'ENTRADA'));
$totalConsumos  = count(array_filter($movimentacoes, fn($m) => $m['tipo'] === 'CONSUMO'));
$totalDescartes = count(array_filter($movimentacoes, fn($m) => $m['tipo'] === 'DESPERDICIO'));

require_once APP_PATH . '/views/partials/header.php';
require_once APP_PATH . '/views/partials/navbar.php';
require_once APP_PATH . '/views/partials/alerts.php';
?>

<main class="py-4">
    <div class="container">
        <!-- Cabeçalho -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">
                    <i class="bi bi-clock-history text-success"></i> Histórico de Movimentações
                </h1>
                <p class="text-muted mb-0 small">
                    Rastreie todas as entradas de produtos, consumos diários e registros de desperdício da sua residência.
                </p>
            </div>
            <a href="<?= url('estoque.php') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-boxes"></i> Ir para MyStock
            </a>
        </div>

        <!-- Cards de Resumo -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="freshy-card p-3 shadow-sm border-start border-4 border-secondary">
                    <span class="text-muted small d-block">Total de Registros</span>
                    <span class="h4 fw-bold text-dark mb-0"><?= $totalGeral ?></span>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="freshy-card p-3 shadow-sm border-start border-4 border-primary">
                    <span class="text-muted small d-block">Entradas / Compras</span>
                    <span class="h4 fw-bold text-primary mb-0"><?= $totalEntradas ?></span>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="freshy-card p-3 shadow-sm border-start border-4 border-success">
                    <span class="text-muted small d-block">Consumos Realizados</span>
                    <span class="h4 fw-bold text-success mb-0"><?= $totalConsumos ?></span>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="freshy-card p-3 shadow-sm border-start border-4 border-danger">
                    <span class="text-muted small d-block">Desperdícios / Descartes</span>
                    <span class="h4 fw-bold text-danger mb-0"><?= $totalDescartes ?></span>
                </div>
            </div>
        </div>

        <!-- Filtros do Histórico -->
        <div class="freshy-card p-3 p-md-4 shadow-sm mb-4">
            <form method="GET" action="<?= url('historico.php') ?>" class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold text-muted">Tipo de Movimentação</label>
                    <select class="form-select" name="tipo">
                        <option value="">Todos os tipos</option>
                        <option value="ENTRADA" <?= $filters['tipo'] === 'ENTRADA' ? 'selected' : '' ?>>Entrada (Cadastro inicial / Compra)</option>
                        <option value="CONSUMO" <?= $filters['tipo'] === 'CONSUMO' ? 'selected' : '' ?>>Consumo</option>
                        <option value="DESPERDICIO" <?= $filters['tipo'] === 'DESPERDICIO' ? 'selected' : '' ?>>Desperdício / Descarte</option>
                    </select>
                </div>

                <div class="col-12 col-md-5">
                    <label class="form-label small fw-semibold text-muted">Produto</label>
                    <select class="form-select" name="produto_id">
                        <option value="">Todos os produtos</option>
                        <?php foreach ($todosProdutos as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $filters['produto_id'] === (int)$p['id'] ? 'selected' : '' ?>>
                                <?= e($p['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-freshy w-100">
                        <i class="bi bi-funnel"></i> Filtrar Histórico
                    </button>
                    <?php if (!empty($filters['tipo']) || !empty($filters['produto_id'])): ?>
                        <a href="<?= url('historico.php') ?>" class="btn btn-outline-secondary" title="Limpar Filtros">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Tabela de Movimentações -->
        <div class="freshy-card p-4 shadow-sm">
            <?php if (empty($movimentacoes)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-clock-history display-5 d-block mb-3 text-secondary"></i>
                    <h5 class="fw-bold text-dark">Nenhuma movimentação encontrada</h5>
                    <p class="small text-muted mb-0">
                        Nenhum registro corresponde aos filtros selecionados ou ainda não foram registradas operações.
                    </p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Data e Hora</th>
                                <th>Tipo</th>
                                <th>Produto</th>
                                <th class="text-center">Quantidade</th>
                                <th>Motivo / Justificativa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimentacoes as $m): ?>
                                <?php
                                $badgeClass = match($m['tipo']) {
                                    'ENTRADA'     => 'bg-primary-subtle text-primary border border-primary-subtle',
                                    'CONSUMO'     => 'bg-success-subtle text-success border border-success-subtle',
                                    'DESPERDICIO' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                    default       => 'bg-secondary',
                                };
                                $iconClass = match($m['tipo']) {
                                    'ENTRADA'     => 'bi-box-arrow-in-down',
                                    'CONSUMO'     => 'bi-check2-circle',
                                    'DESPERDICIO' => 'bi-trash3',
                                    default       => 'bi-circle',
                                };
                                ?>
                                <tr>
                                    <td class="text-muted small text-nowrap">
                                        <i class="bi bi-calendar3 me-1"></i>
                                        <?= format_datetime($m['created_at']) ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $badgeClass ?> d-inline-flex align-items-center gap-1 py-1 px-2">
                                            <i class="bi <?= $iconClass ?>"></i> <?= e($m['tipo']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($m['produto_nome']) ?></div>
                                        <small class="text-muted"><i class="bi bi-tag me-1"></i><?= e($m['categoria_nome']) ?></small>
                                    </td>
                                    <td class="text-center fw-bold fs-6">
                                        <?= (float)$m['quantidade'] ?> <small class="text-muted fw-normal"><?= e($m['produto_unidade']) ?></small>
                                    </td>
                                    <td class="text-secondary small">
                                        <?= !empty($m['motivo']) ? e($m['motivo']) : '<span class="text-muted">-</span>' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once APP_PATH . '/views/partials/footer.php'; ?>
