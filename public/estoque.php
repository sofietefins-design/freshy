<?php
/**
 * Freshy — Módulo Principal MyStock (Estoque Doméstico)
 */

require_once __DIR__ . '/../app/config/config.php';

require_auth();

$usuarioId = user_id();
$pageTitle = 'MyStock — Estoque Doméstico';
$activePage = 'estoque';

// Processamento de Ações POST de Produto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Token de segurança CSRF inválido ou expirado. Tente novamente.');
        redirect('estoque.php');
    }

    $action = $_POST['action'] ?? '';

    // 1. Cadastrar Produto
    if ($action === 'create_produto') {
        $nome       = trim($_POST['nome'] ?? '');
        $categoriaId = (int) ($_POST['categoria_id'] ?? 0);
        $localId    = (int) ($_POST['local_id'] ?? 0);
        $quantidade = (float) ($_POST['quantidade'] ?? 0);
        $unidade    = trim($_POST['unidade'] ?? 'un');
        $qtdMinima  = (float) ($_POST['quantidade_minima'] ?? 1);
        $validade   = trim($_POST['data_validade'] ?? '');

        if (mb_strlen($nome) < 2) {
            flash('error', 'O nome do produto deve conter pelo menos 2 caracteres.');
        } elseif ($categoriaId <= 0 || $localId <= 0) {
            flash('error', 'Selecione uma categoria e um local válidos.');
        } elseif ($quantidade < 0 || $qtdMinima < 0) {
            flash('error', 'As quantidades não podem ser negativas.');
        } else {
            try {
                Produto::create($usuarioId, [
                    'categoria_id'      => $categoriaId,
                    'local_id'          => $localId,
                    'nome'              => $nome,
                    'quantidade'        => $quantidade,
                    'unidade'           => $unidade,
                    'quantidade_minima' => $qtdMinima,
                    'data_validade'     => !empty($validade) ? $validade : null,
                ]);
                flash('success', "Produto '{$nome}' adicionado ao seu estoque com sucesso!");
            } catch (Exception $e) {
                flash('error', 'Erro ao salvar produto: ' . $e->getMessage());
            }
        }
        redirect('estoque.php');
    }

    // 2. Editar Produto
    if ($action === 'edit_produto') {
        $id         = (int) ($_POST['id'] ?? 0);
        $nome       = trim($_POST['nome'] ?? '');
        $categoriaId = (int) ($_POST['categoria_id'] ?? 0);
        $localId    = (int) ($_POST['local_id'] ?? 0);
        $quantidade = (float) ($_POST['quantidade'] ?? 0);
        $unidade    = trim($_POST['unidade'] ?? 'un');
        $qtdMinima  = (float) ($_POST['quantidade_minima'] ?? 1);
        $validade   = trim($_POST['data_validade'] ?? '');

        $prod = Produto::findByIdAndUsuario($id, $usuarioId);
        if (!$prod) {
            flash('error', 'Produto não encontrado ou você não tem permissão para alterá-lo.');
        } elseif (mb_strlen($nome) < 2) {
            flash('error', 'O nome do produto deve ter pelo menos 2 caracteres.');
        } elseif ($quantidade < 0 || $qtdMinima < 0) {
            flash('error', 'As quantidades não podem ser negativas.');
        } else {
            try {
                Produto::update($id, $usuarioId, [
                    'categoria_id'      => $categoriaId,
                    'local_id'          => $localId,
                    'nome'              => $nome,
                    'quantidade'        => $quantidade,
                    'unidade'           => $unidade,
                    'quantidade_minima' => $qtdMinima,
                    'data_validade'     => !empty($validade) ? $validade : null,
                ]);
                flash('success', "Produto '{$nome}' atualizado com sucesso!");
            } catch (Exception $e) {
                flash('error', 'Erro ao atualizar produto: ' . $e->getMessage());
            }
        }
        redirect('estoque.php');
    }

    // 3. Excluir Produto
    if ($action === 'delete_produto') {
        $id = (int) ($_POST['id'] ?? 0);
        $prod = Produto::findByIdAndUsuario($id, $usuarioId);

        if (!$prod) {
            flash('error', 'Produto não encontrado ou permissão negada.');
        } else {
            Produto::delete($id, $usuarioId);
            flash('success', "Produto '{$prod['nome']}' excluído do estoque com sucesso!");
        }
        redirect('estoque.php');
    }

    // 4. Ação Rápida: Adicionar à Lista de Compras
    if ($action === 'add_to_compras') {
        $id = (int) ($_POST['produto_id'] ?? 0);
        $qtdComprar = (float) ($_POST['quantidade_comprar'] ?? 1);

        $prod = Produto::findByIdAndUsuario($id, $usuarioId);
        if (!$prod) {
            flash('error', 'Produto não encontrado.');
        } else {
            // Obtém ou cria lista de compras
            $listaId = ListaCompras::getOrCreateListaId($usuarioId);
            ItemListaCompras::addItem($listaId, $prod['id'], $prod['nome'], $qtdComprar > 0 ? $qtdComprar : 1);
            flash('success', "'{$prod['nome']}' foi adicionado à sua Lista de Compras!");
        }
        redirect('estoque.php');
    }
}

// Filtros recebidos via GET
$filters = [
    'busca'        => trim($_GET['busca'] ?? ''),
    'categoria_id' => !empty($_GET['categoria_id']) ? (int) $_GET['categoria_id'] : null,
    'local_id'     => !empty($_GET['local_id']) ? (int) $_GET['local_id'] : null,
    'situacao'     => trim($_GET['situacao'] ?? ''),
];

// Dados para a tela
$produtos   = Produto::allByUsuario($usuarioId, $filters);
$stats      = Produto::getStats($usuarioId);
$categorias = Categoria::allByUsuario($usuarioId);
$locais     = Local::allByUsuario($usuarioId);

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
                    <i class="bi bi-boxes text-success"></i> MyStock — Estoque Doméstico
                </h1>
                <p class="text-muted mb-0 small">
                    Acompanhe quantidades, unidades, locais e validades dos seus produtos em tempo real.
                </p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-freshy" data-bs-toggle="modal" data-bs-target="#modalNovoProduto">
                    <i class="bi bi-plus-lg"></i> Novo Produto
                </button>
            </div>
        </div>

        <!-- Cards de Resumo / Filtros Rápidos de Situação -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <a href="<?= url('estoque.php') ?>" class="text-decoration-none">
                    <div class="freshy-card p-3 d-flex align-items-center gap-3 <?= empty($filters['situacao']) ? 'border-success bg-light' : '' ?>">
                        <div class="bg-success-subtle text-success p-3 rounded-circle">
                            <i class="bi bi-boxes fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Total no Estoque</span>
                            <span class="h4 fw-bold text-dark mb-0"><?= $stats['total'] ?></span>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3">
                <a href="<?= url('estoque.php?situacao=baixo') ?>" class="text-decoration-none">
                    <div class="freshy-card p-3 d-flex align-items-center gap-3 <?= $filters['situacao'] === 'baixo' ? 'border-warning bg-light' : '' ?>">
                        <div class="bg-warning-subtle text-warning p-3 rounded-circle">
                            <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Estoque Baixo</span>
                            <span class="h4 fw-bold text-warning mb-0"><?= $stats['baixo'] ?></span>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3">
                <a href="<?= url('estoque.php?situacao=critico') ?>" class="text-decoration-none">
                    <div class="freshy-card p-3 d-flex align-items-center gap-3 <?= $filters['situacao'] === 'critico' ? 'border-danger bg-light' : '' ?>">
                        <div class="bg-danger-subtle text-danger p-3 rounded-circle">
                            <i class="bi bi-alarm-fill fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Críticos (0 a 3 dias)</span>
                            <span class="h4 fw-bold text-danger mb-0"><?= $stats['criticos'] ?></span>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3">
                <a href="<?= url('estoque.php?situacao=vencido') ?>" class="text-decoration-none">
                    <div class="freshy-card p-3 d-flex align-items-center gap-3 <?= $filters['situacao'] === 'vencido' ? 'border-danger bg-danger-subtle' : '' ?>">
                        <div class="bg-danger text-white p-3 rounded-circle">
                            <i class="bi bi-x-octagon-fill fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Vencidos</span>
                            <span class="h4 fw-bold text-danger mb-0"><?= $stats['vencidos'] ?></span>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Barra de Busca e Filtros -->
        <div class="freshy-card p-3 p-md-4 shadow-sm mb-4">
            <form method="GET" action="<?= url('estoque.php') ?>" class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold text-muted">Pesquisar por nome</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" name="busca" value="<?= e($filters['busca']) ?>" placeholder="Ex: Leite, Arroz, Café...">
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold text-muted">Categoria</label>
                    <select class="form-select" name="categoria_id">
                        <option value="">Todas as categorias</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $filters['categoria_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                                <?= e($c['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold text-muted">Local</label>
                    <select class="form-select" name="local_id">
                        <option value="">Todos os locais</option>
                        <?php foreach ($locais as $l): ?>
                            <option value="<?= $l['id'] ?>" <?= $filters['local_id'] === (int)$l['id'] ? 'selected' : '' ?>>
                                <?= e($l['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold text-muted">Situação</label>
                    <select class="form-select" name="situacao">
                        <option value="">Todas as situações</option>
                        <option value="baixo" <?= $filters['situacao'] === 'baixo' ? 'selected' : '' ?>>Estoque Baixo</option>
                        <option value="critico" <?= $filters['situacao'] === 'critico' ? 'selected' : '' ?>>Crítico (0 a 3 dias)</option>
                        <option value="atencao" <?= $filters['situacao'] === 'atencao' ? 'selected' : '' ?>>Atenção (4 a 7 dias)</option>
                        <option value="vencido" <?= $filters['situacao'] === 'vencido' ? 'selected' : '' ?>>Vencido</option>
                        <option value="normal" <?= $filters['situacao'] === 'normal' ? 'selected' : '' ?>>Normal</option>
                    </select>
                </div>

                <div class="col-6 col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-freshy w-100" title="Filtrar">
                        <i class="bi bi-funnel"></i>
                    </button>
                    <?php if (!empty($filters['busca']) || !empty($filters['categoria_id']) || !empty($filters['local_id']) || !empty($filters['situacao'])): ?>
                        <a href="<?= url('estoque.php') ?>" class="btn btn-outline-secondary" title="Limpar Filtros">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Tabela de Produtos -->
        <div class="freshy-card p-3 p-md-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 text-dark">
                    Produtos Cadastrados (<?= count($produtos) ?>)
                </h5>
                <small class="text-muted">Ordenado por proximidade de validade</small>
            </div>

            <?php if (empty($produtos)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-4 text-muted d-block mb-3"></i>
                    <h5 class="fw-bold text-secondary">Nenhum produto encontrado</h5>
                    <p class="text-muted small">
                        <?php if (!empty($filters['busca']) || !empty($filters['situacao']) || !empty($filters['categoria_id']) || !empty($filters['local_id'])): ?>
                            Nenhum item corresponde aos filtros selecionados. Tente limpar os filtros.
                        <?php else: ?>
                            Seu estoque está vazio. Clique em "+ Novo Produto" para começar a organizar sua casa!
                        <?php endif; ?>
                    </p>
                    <button class="btn btn-freshy btn-sm" data-bs-toggle="modal" data-bs-target="#modalNovoProduto">
                        <i class="bi bi-plus-lg"></i> Cadastrar Primeiro Produto
                    </button>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Produto</th>
                                <th>Categoria / Local</th>
                                <th class="text-center">Quantidade</th>
                                <th>Validade</th>
                                <th>Status</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produtos as $p): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($p['nome']) ?></div>
                                        <small class="text-muted">Mínimo: <?= (float)$p['quantidade_minima'] ?> <?= e($p['unidade']) ?></small>
                                    </td>
                                    <td>
                                        <div><i class="bi bi-tag text-success me-1"></i> <?= e($p['categoria_nome']) ?></div>
                                        <small class="text-muted"><i class="bi bi-geo-alt me-1"></i> <?= e($p['local_nome']) ?></small>
                                    </td>
                                    <td class="text-center">
                                        <div class="fs-6 fw-bold <?= $p['is_baixo'] ? 'text-danger' : 'text-dark' ?>">
                                            <?= (float) $p['quantidade'] ?> <span class="fs-7 fw-normal text-muted"><?= e($p['unidade']) ?></span>
                                        </div>
                                        <?php if ($p['is_baixo']): ?>
                                            <span class="badge badge-status-baixo mt-1">
                                                <i class="bi bi-exclamation-triangle-fill"></i> Baixo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?= format_date($p['data_validade']) ?></div>
                                        <small class="text-muted"><?= e($p['status_validade']['dias_texto']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?= e($p['status_validade']['badge_class']) ?>">
                                            <i class="bi <?= e($p['status_validade']['icon']) ?>"></i>
                                            <?= e($p['status_validade']['label']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <!-- Botão Consumir -->
                                        <button class="btn btn-sm btn-outline-success me-1" data-bs-toggle="modal" data-bs-target="#modalConsumo<?= $p['id'] ?>" title="Registrar Consumo">
                                            <i class="bi bi-check2-circle"></i> Consumir
                                        </button>

                                        <!-- Botão Desperdício -->
                                        <button class="btn btn-sm btn-outline-warning me-1" data-bs-toggle="modal" data-bs-target="#modalDesperdicio<?= $p['id'] ?>" title="Registrar Desperdício">
                                            <i class="bi bi-trash3"></i>
                                        </button>

                                        <!-- Menu Dropdown de Mais Ações -->
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li>
                                                    <!-- Adicionar à lista de compras -->
                                                    <form method="POST" action="<?= url('estoque.php') ?>">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="add_to_compras">
                                                        <input type="hidden" name="produto_id" value="<?= $p['id'] ?>">
                                                        <input type="hidden" name="quantidade_comprar" value="<?= max(1, (float)$p['quantidade_minima'] - (float)$p['quantidade']) ?>">
                                                        <button type="submit" class="dropdown-item d-flex align-items-center gap-2">
                                                            <i class="bi bi-cart-plus text-primary"></i> Adicionar à Lista de Compras
                                                        </button>
                                                    </form>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button class="dropdown-item d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalEditProduto<?= $p['id'] ?>">
                                                        <i class="bi bi-pencil text-secondary"></i> Editar Produto
                                                    </button>
                                                </li>
                                                <li>
                                                    <form method="POST" action="<?= url('estoque.php') ?>" onsubmit="return confirm('Tem certeza que deseja excluir o produto <?= e($p['nome']) ?> do estoque?');">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="delete_produto">
                                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                        <button type="submit" class="dropdown-item text-danger d-flex align-items-center gap-2">
                                                            <i class="bi bi-trash"></i> Excluir Produto
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- MODAL: REGISTRAR CONSUMO DO PRODUTO -->
                                        <div class="modal fade" id="modalConsumo<?= $p['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content text-start">
                                                    <div class="modal-header bg-success-subtle text-success">
                                                        <h5 class="modal-title fw-bold">
                                                            <i class="bi bi-check2-circle"></i> Registrar Consumo
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST" action="<?= url('consumo.php') ?>">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="produto_id" value="<?= $p['id'] ?>">
                                                        <div class="modal-body">
                                                            <p class="mb-3">
                                                                Produto: <strong><?= e($p['nome']) ?></strong><br>
                                                                Estoque disponível: <strong class="text-success"><?= (float)$p['quantidade'] ?> <?= e($p['unidade']) ?></strong>
                                                            </p>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Quantidade Consumida</label>
                                                                <div class="input-group">
                                                                    <input type="number" step="0.01" min="0.01" max="<?= (float)$p['quantidade'] ?>" class="form-control" name="quantidade" value="1" required>
                                                                    <span class="input-group-text"><?= e($p['unidade']) ?></span>
                                                                </div>
                                                                <div class="form-text">O sistema não permite saldo negativo.</div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Observação / Motivo (Opcional)</label>
                                                                <input type="text" class="form-control" name="motivo" placeholder="Ex: Café da manhã, Almoço de domingo">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-success btn-sm">Confirmar Consumo</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- MODAL: REGISTRAR DESPERDÍCIO DO PRODUTO -->
                                        <div class="modal fade" id="modalDesperdicio<?= $p['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content text-start">
                                                    <div class="modal-header bg-warning-subtle text-warning">
                                                        <h5 class="modal-title fw-bold">
                                                            <i class="bi bi-trash3"></i> Registrar Desperdício
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST" action="<?= url('desperdicio.php') ?>">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="produto_id" value="<?= $p['id'] ?>">
                                                        <div class="modal-body">
                                                            <p class="mb-3">
                                                                Produto: <strong><?= e($p['nome']) ?></strong><br>
                                                                Estoque disponível: <strong class="text-warning"><?= (float)$p['quantidade'] ?> <?= e($p['unidade']) ?></strong>
                                                            </p>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Quantidade Desperdiçada</label>
                                                                <div class="input-group">
                                                                    <input type="number" step="0.01" min="0.01" max="<?= (float)$p['quantidade'] ?>" class="form-control" name="quantidade" value="1" required>
                                                                    <span class="input-group-text"><?= e($p['unidade']) ?></span>
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Motivo do Descarte (Obrigatório)</label>
                                                                <select class="form-select" name="motivo" required>
                                                                    <option value="">Selecione o motivo...</option>
                                                                    <option value="Prazo de validade vencido">Prazo de validade vencido</option>
                                                                    <option value="Estragado / mofo / azedou">Estragado / mofo / azedou</option>
                                                                    <option value="Embalagem danificada / contaminação">Embalagem danificada / contaminação</option>
                                                                    <option value="Preparo excessivo / sobra não consumida">Preparo excessivo / sobra não consumida</option>
                                                                    <option value="Outro motivo">Outro motivo</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-warning btn-sm">Confirmar Desperdício</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- MODAL: EDITAR PRODUTO -->
                                        <div class="modal fade" id="modalEditProduto<?= $p['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                                <div class="modal-content text-start">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold">Editar Produto: <?= e($p['nome']) ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST" action="<?= url('estoque.php') ?>">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="edit_produto">
                                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                        <div class="modal-body">
                                                            <div class="row g-3">
                                                                <div class="col-12 col-md-6">
                                                                    <label class="form-label fw-semibold">Nome do Produto</label>
                                                                    <input type="text" class="form-control" name="nome" value="<?= e($p['nome']) ?>" required>
                                                                </div>

                                                                <div class="col-6 col-md-3">
                                                                    <label class="form-label fw-semibold">Categoria</label>
                                                                    <select class="form-select" name="categoria_id" required>
                                                                        <?php foreach ($categorias as $cat): ?>
                                                                            <option value="<?= $cat['id'] ?>" <?= (int)$p['categoria_id'] === (int)$cat['id'] ? 'selected' : '' ?>>
                                                                                <?= e($cat['nome']) ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>

                                                                <div class="col-6 col-md-3">
                                                                    <label class="form-label fw-semibold">Local</label>
                                                                    <select class="form-select" name="local_id" required>
                                                                        <?php foreach ($locais as $loc): ?>
                                                                            <option value="<?= $loc['id'] ?>" <?= (int)$p['local_id'] === (int)$loc['id'] ? 'selected' : '' ?>>
                                                                                <?= e($loc['nome']) ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>

                                                                <div class="col-6 col-md-3">
                                                                    <label class="form-label fw-semibold">Quantidade Atual</label>
                                                                    <input type="number" step="0.01" min="0" class="form-control" name="quantidade" value="<?= (float)$p['quantidade'] ?>" required>
                                                                </div>

                                                                <div class="col-6 col-md-3">
                                                                    <label class="form-label fw-semibold">Unidade</label>
                                                                    <select class="form-select" name="unidade">
                                                                        <?php
                                                                        $unidades = ['un', 'kg', 'g', 'l', 'ml', 'pct', 'cx', 'lata', 'frasco', 'garrafa', 'duzia'];
                                                                        foreach ($unidades as $u):
                                                                        ?>
                                                                            <option value="<?= $u ?>" <?= $p['unidade'] === $u ? 'selected' : '' ?>><?= $u ?></option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>

                                                                <div class="col-6 col-md-3">
                                                                    <label class="form-label fw-semibold">Estoque Mínimo</label>
                                                                    <input type="number" step="0.01" min="0" class="form-control" name="quantidade_minima" value="<?= (float)$p['quantidade_minima'] ?>" required>
                                                                </div>

                                                                <div class="col-6 col-md-3">
                                                                    <label class="form-label fw-semibold">Data de Validade</label>
                                                                    <input type="date" class="form-control" name="data_validade" value="<?= e($p['data_validade'] ?? '') ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-freshy btn-sm">Salvar Alterações</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
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

<!-- MODAL GERAL: CADASTRAR NOVO PRODUTO -->
<div class="modal fade" id="modalNovoProduto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-box-seam"></i> Cadastrar Novo Produto
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= url('estoque.php') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create_produto">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Nome do Produto *</label>
                            <input type="text" class="form-control" name="nome" required placeholder="Ex: Queijo Muçarela Fatiado 500g">
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label fw-semibold">Categoria *</label>
                            <select class="form-select" name="categoria_id" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= e($cat['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label fw-semibold">Local de Armazenamento *</label>
                            <select class="form-select" name="local_id" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($locais as $loc): ?>
                                    <option value="<?= $loc['id'] ?>"><?= e($loc['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label fw-semibold">Quantidade Inicial *</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="quantidade" value="1" required>
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label fw-semibold">Unidade de Medida *</label>
                            <select class="form-select" name="unidade">
                                <option value="un">un (Unidade)</option>
                                <option value="kg">kg (Quilograma)</option>
                                <option value="g">g (Grama)</option>
                                <option value="l">l (Litro)</option>
                                <option value="ml">ml (Mililitro)</option>
                                <option value="pct">pct (Pacote)</option>
                                <option value="cx">cx (Caixa)</option>
                                <option value="lata">lata (Lata)</option>
                                <option value="frasco">frasco (Frasco)</option>
                                <option value="garrafa">garrafa (Garrafa)</option>
                                <option value="duzia">duzia (Dúzia)</option>
                            </select>
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label fw-semibold">Estoque Mínimo de Alerta *</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="quantidade_minima" value="1" required>
                            <div class="form-text">Gera alerta quando o estoque for menor ou igual.</div>
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label fw-semibold">Data de Validade</label>
                            <input type="date" class="form-control" name="data_validade">
                            <div class="form-text">Opcional para produtos não perecíveis.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-freshy">Salvar no Estoque</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once APP_PATH . '/views/partials/footer.php'; ?>
