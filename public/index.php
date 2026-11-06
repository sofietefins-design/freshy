<?php
/**
 * Freshy — Ponto de Entrada Principal (Landing Page)
 */

require_once __DIR__ . '/../app/config/config.php';

// Redireciona usuários já autenticados diretamente para o Dashboard
if (is_authenticated()) {
    redirect('dashboard.php');
}

$pageTitle = 'Seu estoque doméstico, mais inteligente';
$activePage = 'home';

require_once APP_PATH . '/views/partials/header.php';
require_once APP_PATH . '/views/partials/navbar.php';
require_once APP_PATH . '/views/partials/alerts.php';
?>

<main class="py-5">
    <div class="container">
        <!-- Hero Section -->
        <div class="row align-items-center g-5 py-4">
            <div class="col-lg-7">
                <div class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill mb-3">
                    <i class="bi bi-shield-check"></i> Controle Doméstico Real &amp; Inteligente
                </div>
                <h1 class="display-4 fw-bold lh-1 mb-3 text-dark">
                    Seu estoque doméstico, <span class="text-success">mais inteligente</span>.
                </h1>
                <p class="lead text-secondary mb-4">
                    Cadastre os produtos que possui em casa, acompanhe quantidades e prazos de validade, identifique itens com estoque baixo e evite o desperdício alimentar mantendo sua lista de compras sempre em dia.
                </p>
                <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                    <a href="<?= url('cadastro.php') ?>" class="btn btn-freshy btn-lg px-4 me-md-2">
                        <i class="bi bi-rocket-takeoff-fill"></i> Começar Gratuitamente
                    </a>
                    <a href="<?= url('login.php') ?>" class="btn btn-outline-secondary btn-lg px-4">
                        <i class="bi bi-box-arrow-in-right"></i> Já tenho conta
                    </a>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="freshy-card p-4 shadow-sm border-0">
                    <h5 class="fw-bold mb-3 text-success d-flex align-items-center gap-2">
                        <i class="bi bi-check2-circle fs-4"></i> Funcionalidades do Freshy:
                    </h5>
                    <ul class="list-unstyled d-flex flex-column gap-3 mb-0">
                        <li class="d-flex align-items-start gap-3">
                            <div class="bg-success-subtle text-success p-2 rounded">
                                <i class="bi bi-boxes fs-5"></i>
                            </div>
                            <div>
                                <strong class="d-block text-dark">MyStock</strong>
                                <small class="text-muted">Cadastro completo com unidade, quantidade mínima e datas de validade.</small>
                            </div>
                        </li>
                        <li class="d-flex align-items-start gap-3">
                            <div class="bg-warning-subtle text-warning p-2 rounded">
                                <i class="bi bi-alarm-fill fs-5"></i>
                            </div>
                            <div>
                                <strong class="d-block text-dark">Alertas Inteligentes</strong>
                                <small class="text-muted">Classificação automática: vencidos, críticos (0-3 dias) e atenção (4-7 dias).</small>
                            </div>
                        </li>
                        <li class="d-flex align-items-start gap-3">
                            <div class="bg-primary-subtle text-primary p-2 rounded">
                                <i class="bi bi-arrow-left-right fs-5"></i>
                            </div>
                            <div>
                                <strong class="d-block text-dark">Consumo e Desperdício</strong>
                                <small class="text-muted">Baixas atômicas no estoque com registro detalhado no histórico.</small>
                            </div>
                        </li>
                        <li class="d-flex align-items-start gap-3">
                            <div class="bg-info-subtle text-info p-2 rounded">
                                <i class="bi bi-cart3 fs-5"></i>
                            </div>
                            <div>
                                <strong class="d-block text-dark">Lista de Compras Dinâmica</strong>
                                <small class="text-muted">Envie produtos em falta direto para sua lista com apenas um clique.</small>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once APP_PATH . '/views/partials/footer.php'; ?>
