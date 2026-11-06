<?php
/**
 * Freshy — Gerenciamento de Categorias e Locais de Armazenamento
 */

require_once __DIR__ . '/../app/config/config.php';

require_auth();

$usuarioId = user_id();
$pageTitle = 'Categorias e Locais';
$activePage = 'categorias';

// Processamento de Ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Token de segurança inválido ou expirado. Tente novamente.');
        redirect('categorias.php');
    }

    $action = $_POST['action'] ?? '';

    // 1. Criar Categoria
    if ($action === 'add_categoria') {
        $nome = trim($_POST['nome'] ?? '');
        if (mb_strlen($nome) < 2) {
            flash('error', 'O nome da categoria deve ter pelo menos 2 caracteres.');
        } elseif (Categoria::existsByName($usuarioId, $nome)) {
            flash('warning', "A categoria '{$nome}' já existe no seu catálogo.");
        } else {
            Categoria::create($usuarioId, $nome);
            flash('success', "Categoria '{$nome}' cadastrada com sucesso!");
        }
        redirect('categorias.php?tab=categorias');
    }

    // 2. Editar Categoria
    if ($action === 'edit_categoria') {
        $id = (int) ($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');

        $cat = Categoria::findByIdAndUsuario($id, $usuarioId);
        if (!$cat) {
            flash('error', 'Categoria não encontrada ou você não tem permissão para alterá-la.');
        } elseif (mb_strlen($nome) < 2) {
            flash('error', 'O nome da categoria deve ter pelo menos 2 caracteres.');
        } elseif (Categoria::existsByName($usuarioId, $nome, $id)) {
            flash('warning', "Já existe outra categoria chamada '{$nome}'.");
        } else {
            Categoria::update($id, $usuarioId, $nome);
            flash('success', "Categoria atualizada com sucesso para '{$nome}'.");
        }
        redirect('categorias.php?tab=categorias');
    }

    // 3. Excluir Categoria
    if ($action === 'delete_categoria') {
        $id = (int) ($_POST['id'] ?? 0);
        $cat = Categoria::findByIdAndUsuario($id, $usuarioId);

        if (!$cat) {
            flash('error', 'Categoria não encontrada ou permissão negada.');
        } elseif (Categoria::hasProdutos($id, $usuarioId)) {
            flash('warning', "A categoria '{$cat['nome']}' possui produtos vinculados no estoque e não pode ser excluída.");
        } else {
            Categoria::delete($id, $usuarioId);
            flash('success', "Categoria '{$cat['nome']}' excluída com sucesso!");
        }
        redirect('categorias.php?tab=categorias');
    }

    // 4. Criar Local
    if ($action === 'add_local') {
        $nome = trim($_POST['nome'] ?? '');
        if (mb_strlen($nome) < 2) {
            flash('error', 'O nome do local deve ter pelo menos 2 caracteres.');
        } elseif (Local::existsByName($usuarioId, $nome)) {
            flash('warning', "O local '{$nome}' já existe.");
        } else {
            Local::create($usuarioId, $nome);
            flash('success', "Local de armazenamento '{$nome}' cadastrado com sucesso!");
        }
        redirect('categorias.php?tab=locais');
    }

    // 5. Editar Local
    if ($action === 'edit_local') {
        $id = (int) ($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');

        $loc = Local::findByIdAndUsuario($id, $usuarioId);
        if (!$loc) {
            flash('error', 'Local não encontrado ou permissão negada.');
        } elseif (mb_strlen($nome) < 2) {
            flash('error', 'O nome do local deve ter pelo menos 2 caracteres.');
        } elseif (Local::existsByName($usuarioId, $nome, $id)) {
            flash('warning', "Já existe outro local chamado '{$nome}'.");
        } else {
            Local::update($id, $usuarioId, $nome);
            flash('success', "Local atualizado com sucesso para '{$nome}'.");
        }
        redirect('categorias.php?tab=locais');
    }

    // 6. Excluir Local
    if ($action === 'delete_local') {
        $id = (int) ($_POST['id'] ?? 0);
        $loc = Local::findByIdAndUsuario($id, $usuarioId);

        if (!$loc) {
            flash('error', 'Local não encontrado ou permissão negada.');
        } elseif (Local::hasProdutos($id, $usuarioId)) {
            flash('warning', "O local '{$loc['nome']}' possui produtos vinculados e não pode ser excluído.");
        } else {
            Local::delete($id, $usuarioId);
            flash('success', "Local '{$loc['nome']}' excluído com sucesso!");
        }
        redirect('categorias.php?tab=locais');
    }
}

// Carrega listagens pertencentes ao usuário autenticado
$categorias = Categoria::allByUsuario($usuarioId);
$locais = Local::allByUsuario($usuarioId);
$activeTab = $_GET['tab'] ?? 'categorias';

require_once APP_PATH . '/views/partials/header.php';
require_once APP_PATH . '/views/partials/navbar.php';
require_once APP_PATH . '/views/partials/alerts.php';
?>

<main class="py-4">
    <div class="container">
        <!-- Cabeçalho da Página -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">
                    <i class="bi bi-tags text-success"></i> Categorias &amp; Locais de Armazenamento
                </h1>
                <p class="text-muted mb-0 small">
                    Organize os departamentos e os pontos de armazenamento dos seus mantimentos domésticos.
                </p>
            </div>
            <a href="<?= url('estoque.php') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Voltar ao MyStock
            </a>
        </div>

        <!-- Abas de Navegação -->
        <ul class="nav nav-pills mb-4 gap-2" id="catLocTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $activeTab === 'categorias' ? 'active' : '' ?> px-4" id="categorias-tab" data-bs-toggle="pill" data-bs-target="#tab-categorias" type="button" role="tab">
                    <i class="bi bi-grid-fill"></i> Categorias (<?= count($categorias) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $activeTab === 'locais' ? 'active' : '' ?> px-4" id="locais-tab" data-bs-toggle="pill" data-bs-target="#tab-locais" type="button" role="tab">
                    <i class="bi bi-geo-alt-fill"></i> Locais de Armazenamento (<?= count($locais) ?>)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="catLocTabsContent">
            <!-- ABA 1: CATEGORIAS -->
            <div class="tab-pane fade <?= $activeTab === 'categorias' ? 'show active' : '' ?>" id="tab-categorias" role="tabpanel">
                <div class="row g-4">
                    <!-- Formulário de Nova Categoria -->
                    <div class="col-lg-4">
                        <div class="freshy-card p-4 shadow-sm">
                            <h5 class="fw-bold text-success mb-3">
                                <i class="bi bi-plus-circle-fill"></i> Nova Categoria
                            </h5>
                            <form method="POST" action="<?= url('categorias.php') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="add_categoria">

                                <div class="mb-3">
                                    <label for="cat_nome" class="form-label fw-semibold">Nome da Categoria</label>
                                    <input type="text" class="form-control" id="cat_nome" name="nome" required placeholder="Ex: Bebidas, Grãos, Frios">
                                    <div class="form-text">Categorias facilitam filtrar seus itens no estoque.</div>
                                </div>

                                <button type="submit" class="btn btn-freshy w-100">
                                    <i class="bi bi-check2"></i> Salvar Categoria
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Tabela de Categorias -->
                    <div class="col-lg-8">
                        <div class="freshy-card p-4 shadow-sm">
                            <h5 class="fw-bold text-dark mb-3">
                                Categorias Cadastradas
                            </h5>
                            <?php if (empty($categorias)): ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-tag display-6 d-block mb-2 text-secondary"></i>
                                    Nenhuma categoria cadastrada ainda. Crie a primeira ao lado!
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nome</th>
                                                <th class="text-center">Produtos no Estoque</th>
                                                <th class="text-end">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($categorias as $cat): ?>
                                                <tr>
                                                    <td class="fw-semibold">
                                                        <i class="bi bi-tag text-success me-1"></i>
                                                        <?= e($cat['nome']) ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-light text-dark border">
                                                            <?= (int) $cat['total_produtos'] ?> produto(s)
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        <!-- Botão Editar Modal -->
                                                        <button class="btn btn-sm btn-outline-secondary me-1" data-bs-toggle="modal" data-bs-target="#modalEditCat<?= $cat['id'] ?>" title="Editar Categoria">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>

                                                        <!-- Formulário Excluir -->
                                                        <form method="POST" action="<?= url('categorias.php') ?>" class="d-inline">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="action" value="delete_categoria">
                                                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Deseja realmente excluir a categoria '<?= e($cat['nome']) ?>'?" <?= (int)$cat['total_produtos'] > 0 ? 'disabled title="Não é possível excluir: existem produtos vinculados"' : 'title="Excluir Categoria"' ?>>
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>

                                                        <!-- Modal de Edição -->
                                                        <div class="modal fade" id="modalEditCat<?= $cat['id'] ?>" tabindex="-1" aria-hidden="true">
                                                            <div class="modal-dialog modal-dialog-centered">
                                                                <div class="modal-content text-start">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title fw-bold">Editar Categoria</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <form method="POST" action="<?= url('categorias.php') ?>">
                                                                        <?= csrf_field() ?>
                                                                        <input type="hidden" name="action" value="edit_categoria">
                                                                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                                                        <div class="modal-body">
                                                                            <div class="mb-3">
                                                                                <label class="form-label fw-semibold">Nome da Categoria</label>
                                                                                <input type="text" class="form-control" name="nome" value="<?= e($cat['nome']) ?>" required>
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
                </div>
            </div>

            <!-- ABA 2: LOCAIS DE ARMAZENAMENTO -->
            <div class="tab-pane fade <?= $activeTab === 'locais' ? 'show active' : '' ?>" id="tab-locais" role="tabpanel">
                <div class="row g-4">
                    <!-- Formulário de Novo Local -->
                    <div class="col-lg-4">
                        <div class="freshy-card p-4 shadow-sm">
                            <h5 class="fw-bold text-success mb-3">
                                <i class="bi bi-plus-circle-fill"></i> Novo Local
                            </h5>
                            <form method="POST" action="<?= url('categorias.php') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="add_local">

                                <div class="mb-3">
                                    <label for="loc_nome" class="form-label fw-semibold">Nome do Local</label>
                                    <input type="text" class="form-control" id="loc_nome" name="nome" required placeholder="Ex: Geladeira, Despensa, Freezer">
                                    <div class="form-text">Locais identificam onde seus mantimentos estão guardados.</div>
                                </div>

                                <button type="submit" class="btn btn-freshy w-100">
                                    <i class="bi bi-check2"></i> Salvar Local
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Tabela de Locais -->
                    <div class="col-lg-8">
                        <div class="freshy-card p-4 shadow-sm">
                            <h5 class="fw-bold text-dark mb-3">
                                Locais Cadastrados
                            </h5>
                            <?php if (empty($locais)): ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-geo-alt display-6 d-block mb-2 text-secondary"></i>
                                    Nenhum local de armazenamento cadastrado ainda. Crie o primeiro ao lado!
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nome</th>
                                                <th class="text-center">Produtos no Estoque</th>
                                                <th class="text-end">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($locais as $loc): ?>
                                                <tr>
                                                    <td class="fw-semibold">
                                                        <i class="bi bi-geo-alt text-success me-1"></i>
                                                        <?= e($loc['nome']) ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-light text-dark border">
                                                            <?= (int) $loc['total_produtos'] ?> produto(s)
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        <!-- Botão Editar Modal -->
                                                        <button class="btn btn-sm btn-outline-secondary me-1" data-bs-toggle="modal" data-bs-target="#modalEditLoc<?= $loc['id'] ?>" title="Editar Local">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>

                                                        <!-- Formulário Excluir -->
                                                        <form method="POST" action="<?= url('categorias.php') ?>" class="d-inline">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="action" value="delete_local">
                                                            <input type="hidden" name="id" value="<?= $loc['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Deseja realmente excluir o local '<?= e($loc['nome']) ?>'?" <?= (int)$loc['total_produtos'] > 0 ? 'disabled title="Não é possível excluir: existem produtos vinculados"' : 'title="Excluir Local"' ?>>
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>

                                                        <!-- Modal de Edição -->
                                                        <div class="modal fade" id="modalEditLoc<?= $loc['id'] ?>" tabindex="-1" aria-hidden="true">
                                                            <div class="modal-dialog modal-dialog-centered">
                                                                <div class="modal-content text-start">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title fw-bold">Editar Local</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <form method="POST" action="<?= url('categorias.php') ?>">
                                                                        <?= csrf_field() ?>
                                                                        <input type="hidden" name="action" value="edit_local">
                                                                        <input type="hidden" name="id" value="<?= $loc['id'] ?>">
                                                                        <div class="modal-body">
                                                                            <div class="mb-3">
                                                                                <label class="form-label fw-semibold">Nome do Local</label>
                                                                                <input type="text" class="form-control" name="nome" value="<?= e($loc['nome']) ?>" required>
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
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once APP_PATH . '/views/partials/footer.php'; ?>
