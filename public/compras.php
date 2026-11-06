<?php
/**
 * Freshy — Módulo de Lista de Compras Inteligente
 */

require_once __DIR__ . '/../app/config/config.php';

require_auth();

$usuarioId = user_id();
$pageTitle = 'Lista de Compras';
$activePage = 'compras';

// Processamento de Ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Token de segurança CSRF inválido ou expirado. Tente novamente.');
        redirect('compras.php');
    }

    $action = $_POST['action'] ?? '';

    // 1. Adicionar Item
    if ($action === 'add_item') {
        $produtoId = !empty($_POST['produto_id']) ? (int) $_POST['produto_id'] : null;
        $nomeItem  = trim($_POST['nome_item'] ?? '');
        $quantidade = (float) ($_POST['quantidade'] ?? 1);

        if (empty($nomeItem) && $produtoId === null) {
            flash('error', 'Informe o nome do item ou selecione um produto.');
        } else {
            ListaCompras::adicionarItem($usuarioId, $produtoId, $nomeItem, $quantidade);
            flash('success', 'Item adicionado à lista de compras!');
        }
        redirect('compras.php');
    }

    // 2. Importar Itens com Estoque Baixo
    if ($action === 'import_baixo') {
        $qtd = ListaCompras::importarEstoqueBaixo($usuarioId);
        if ($qtd > 0) {
            flash('success', "{$qtd} produto(s) com estoque baixo foram adicionados à sua lista de compras!");
        } else {
            flash('info', 'Nenhum produto está com estoque baixo no momento.');
        }
        redirect('compras.php');
    }

    // 3. Alternar Status Comprado
    if ($action === 'toggle_comprado') {
        $itemId = (int) ($_POST['item_id'] ?? 0);
        ListaCompras::alternarComprado($itemId, $usuarioId);
        redirect('compras.php');
    }

    // 4. Alterar Quantidade
    if ($action === 'update_qtd') {
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $novaQtd = (float) ($_POST['quantidade'] ?? 1);
        ListaCompras::atualizarQuantidade($itemId, $usuarioId, $novaQtd);
        flash('success', 'Quantidade atualizada!');
        redirect('compras.php');
    }

    // 5. Excluir Item
    if ($action === 'delete_item') {
        $itemId = (int) ($_POST['item_id'] ?? 0);
        ListaCompras::removerItem($itemId, $usuarioId);
        flash('success', 'Item removido da lista.');
        redirect('compras.php');
    }

    // 6. Limpar Comprados
    if ($action === 'clear_comprados') {
        $removidos = ListaCompras::limparComprados($usuarioId);
        flash('info', "{$removidos} item(ns) comprados foram removidos da lista.");
        redirect('compras.php');
    }
}

// Carrega itens e produtos do usuário
$itens = ListaCompras::getItens($usuarioId);
$produtos = Produto::allByUsuario($usuarioId);
$stats = Produto::getStats($usuarioId);

// Separa pendentes e comprados
$pendentes = array_filter($itens, fn($i) => (int)$i['comprado'] === 0);
$comprados = array_filter($itens, fn($i) => (int)$i['comprado'] === 1);

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
                    <i class="bi bi-cart3 text-success"></i> Lista de Compras Inteligente
                </h1>
                <p class="text-muted mb-0 small">
                    Planeje suas compras domésticas e reponha automaticamente produtos com estoque baixo.
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <!-- Botão Importar Estoque Baixo -->
                <form method="POST" action="<?= url('compras.php') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="import_baixo">
                    <button type="submit" class="btn btn-warning fw-semibold shadow-sm" <?= $stats['baixo'] === 0 ? 'disabled' : '' ?>>
                        <i class="bi bi-magic"></i> Importar Estoque Baixo (<?= $stats['baixo'] ?>)
                    </button>
                </form>

                <?php if (!empty($comprados)): ?>
                    <form method="POST" action="<?= url('compras.php') ?>" onsubmit="return confirm('Deseja limpar todos os itens já marcados como comprados?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="clear_comprados">
                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-check2-all"></i> Limpar Comprados
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4">
            <!-- Coluna Esquerda: Formulário de Adição Rápida -->
            <div class="col-lg-4">
                <div class="freshy-card p-4 shadow-sm mb-4">
                    <h5 class="fw-bold text-success mb-3">
                        <i class="bi bi-plus-circle-fill"></i> Adicionar Item
                    </h5>
                    <form method="POST" action="<?= url('compras.php') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="add_item">

                        <div class="mb-3">
                            <label for="produto_id" class="form-label fw-semibold">Vincular a Produto do Estoque</label>
                            <select class="form-select" id="produto_id" name="produto_id" onchange="if(this.value){ document.getElementById('nome_item').value = this.options[this.selectedIndex].text.split(' (')[0]; }">
                                <option value="">Item avulso / Digitar nome abaixo</option>
                                <?php foreach ($produtos as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= $p['is_baixo'] ? 'class="text-danger fw-bold"' : '' ?>>
                                        <?= e($p['nome']) ?> (Estoque: <?= (float)$p['quantidade'] ?> <?= e($p['unidade']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Ou digite um item avulso que ainda não está no estoque.</div>
                        </div>

                        <div class="mb-3">
                            <label for="nome_item" class="form-label fw-semibold">Nome do Item *</label>
                            <input type="text" class="form-control" id="nome_item" name="nome_item" placeholder="Ex: Maçã Fuji, Sabão em Pó">
                        </div>

                        <div class="mb-3">
                            <label for="quantidade" class="form-label fw-semibold">Quantidade a Comprar *</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="quantidade" name="quantidade" value="1" required>
                        </div>

                        <button type="submit" class="btn btn-freshy w-100">
                            <i class="bi bi-cart-plus"></i> Inserir na Lista
                        </button>
                    </form>
                </div>

                <!-- Card Informativo -->
                <div class="freshy-card p-4 shadow-sm">
                    <h6 class="fw-bold text-dark mb-2">
                        <i class="bi bi-lightbulb-fill text-warning"></i> Como funciona a Lista?
                    </h6>
                    <p class="small text-muted mb-2">
                        - <strong>Vínculo inteligente:</strong> Itens vinculados aos produtos do seu MyStock mostram o saldo atual em casa.
                    </p>
                    <p class="small text-muted mb-0">
                        - <strong>Reposição facilitada:</strong> Ao clicar em "Importar Estoque Baixo", todos os produtos que atingiram a quantidade mínima são adicionados automaticamente!
                    </p>
                </div>
            </div>

            <!-- Coluna Direita: Listagem de Itens -->
            <div class="col-lg-8">
                <!-- Seção 1: Itens Pendentes para Comprar -->
                <div class="freshy-card p-4 shadow-sm mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark mb-0">
                            <i class="bi bi-hourglass-split text-warning"></i> Para Comprar (<?= count($pendentes) ?>)
                        </h5>
                    </div>

                    <?php if (empty($pendentes)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-check2-circle display-5 text-success d-block mb-2"></i>
                            Tudo certo! Não há itens pendentes na sua lista de compras.
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($pendentes as $item): ?>
                                <div class="list-group-item d-flex align-items-center justify-content-between p-3 gap-3 border rounded mb-2">
                                    <div class="d-flex align-items-center gap-3">
                                        <!-- Form Checkbox para Marcar como Comprado -->
                                        <form method="POST" action="<?= url('compras.php') ?>" class="m-0">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_comprado">
                                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="btn btn-outline-success btn-sm rounded-circle p-2" title="Marcar como comprado">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>

                                        <div>
                                            <div class="fw-bold text-dark fs-6"><?= e($item['nome_item']) ?></div>
                                            <div class="small text-muted">
                                                <?php if (!empty($item['produto_id'])): ?>
                                                    <span class="badge bg-light text-dark border">
                                                        <i class="bi bi-boxes"></i> Estoque atual: <?= (float)$item['estoque_atual'] ?> <?= e($item['unidade']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-subtle text-secondary">Item manual</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center gap-2">
                                        <!-- Formulário Edição Rápida de Quantidade -->
                                        <form method="POST" action="<?= url('compras.php') ?>" class="d-flex align-items-center gap-1">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="update_qtd">
                                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                            <input type="number" step="0.01" min="0.1" class="form-control form-control-sm text-center" name="quantidade" value="<?= (float)$item['quantidade'] ?>" style="width: 75px;">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Atualizar quantidade">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                        </form>

                                        <!-- Formulário Excluir Item -->
                                        <form method="POST" action="<?= url('compras.php') ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete_item">
                                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Remover da lista">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Seção 2: Itens Já Comprados -->
                <?php if (!empty($comprados)): ?>
                    <div class="freshy-card p-4 shadow-sm bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-secondary mb-0">
                                <i class="bi bi-check2-all text-success"></i> Já Comprados (<?= count($comprados) ?>)
                            </h6>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php foreach ($comprados as $item): ?>
                                <div class="list-group-item d-flex align-items-center justify-content-between p-2 gap-3 bg-transparent border-bottom">
                                    <div class="d-flex align-items-center gap-3">
                                        <form method="POST" action="<?= url('compras.php') ?>" class="m-0">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_comprado">
                                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="btn btn-success btn-sm rounded-circle p-1" title="Desmarcar / Voltar para a lista">
                                                <i class="bi bi-check"></i>
                                            </button>
                                        </form>
                                        <div class="text-decoration-line-through text-muted">
                                            <?= e($item['nome_item']) ?> (<?= (float)$item['quantidade'] ?>)
                                        </div>
                                    </div>
                                    <form method="POST" action="<?= url('compras.php') ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_item">
                                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Remover">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php require_once APP_PATH . '/views/partials/footer.php'; ?>
