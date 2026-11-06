<?php
/**
 * Freshy — Barra de Navegação Superior
 * @var string $activePage Identificador da página ativa para destacar o menu
 */
$activePage = $activePage ?? '';
$user = current_user();
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-freshy sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?= is_authenticated() ? url('dashboard.php') : url('index.php') ?>">
            <i class="bi bi-box-seam-fill"></i> <?= e(APP_NAME) ?>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarFreshy" aria-controls="navbarFreshy" aria-expanded="false" aria-label="Alternar navegação">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarFreshy">
            <?php if (is_authenticated()): ?>
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>" href="<?= url('dashboard.php') ?>">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activePage === 'estoque' ? 'active' : '' ?>" href="<?= url('estoque.php') ?>">
                            <i class="bi bi-boxes"></i> MyStock
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activePage === 'categorias' ? 'active' : '' ?>" href="<?= url('categorias.php') ?>">
                            <i class="bi bi-tags"></i> Categorias &amp; Locais
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activePage === 'compras' ? 'active' : '' ?>" href="<?= url('compras.php') ?>">
                            <i class="bi bi-cart3"></i> Lista de Compras
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activePage === 'historico' ? 'active' : '' ?>" href="<?= url('historico.php') ?>">
                            <i class="bi bi-clock-history"></i> Histórico
                        </a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-3">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-light dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle fs-5"></i>
                            <span class="fw-medium"><?= e($user['nome'] ?? 'Minha Conta') ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li class="px-3 py-1">
                                <small class="text-muted d-block">Conectado como:</small>
                                <strong class="text-truncate d-block" style="max-width: 200px;"><?= e($user['email'] ?? '') ?></strong>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger d-flex align-items-center gap-2" href="<?= url('logout.php') ?>" data-confirm="Deseja realmente sair do Freshy?">
                                    <i class="bi bi-box-arrow-right"></i> Sair do Sistema
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            <?php else: ?>
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-2">
                    <li class="nav-item">
                        <a class="btn btn-outline-light btn-sm px-3" href="<?= url('login.php') ?>">
                            <i class="bi bi-box-arrow-in-right"></i> Entrar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-light btn-sm px-3 fw-semibold text-success" href="<?= url('cadastro.php') ?>">
                            <i class="bi bi-person-plus-fill"></i> Criar Conta
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>
