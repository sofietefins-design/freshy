<?php
/**
 * Freshy — Seu estoque doméstico, mais inteligente
 * Ponto de Entrada Principal
 */

require_once __DIR__ . '/../app/config/config.php';

// Se o usuário já estiver logado, redireciona para o Dashboard
if (is_authenticated()) {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?> — Seu estoque doméstico, mais inteligente</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Freshy Custom CSS -->
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
</head>
<body>
    <!-- Navbar Pública -->
    <nav class="navbar navbar-expand-lg navbar-freshy">
        <div class="container">
            <a class="navbar-brand" href="<?= url('index.php') ?>">
                <i class="bi bi-box-seam-fill"></i> <?= e(APP_NAME) ?>
            </a>
            <div class="d-flex gap-2">
                <a href="<?= url('login.php') ?>" class="btn btn-outline-light btn-sm px-3">
                    <i class="bi bi-box-arrow-in-right"></i> Entrar
                </a>
                <a href="<?= url('cadastro.php') ?>" class="btn btn-light btn-sm px-3 fw-semibold">
                    <i class="bi bi-person-plus-fill"></i> Criar Conta
                </a>
            </div>
        </div>
    </nav>

    <!-- Conteúdo Principal -->
    <main class="py-5">
        <div class="container">
            <!-- Mensagens Flash -->
            <?php foreach (get_all_flashes() as $type => $message): ?>
                <div class="alert alert-<?= e($type) ?> alert-dismissible fade show" role="alert">
                    <?= e($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            <?php endforeach; ?>

            <!-- Hero Section -->
            <div class="row align-items-center g-5 py-4">
                <div class="col-lg-7">
                    <div class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill mb-3">
                        <i class="bi bi-shield-check"></i> Controle Doméstico Real & Inteligente
                    </div>
                    <h1 class="display-4 fw-bold lh-1 mb-3 text-dark">
                        Seu estoque doméstico, <span class="text-success">mais inteligente</span>.
                    </h1>
                    <p class="lead text-secondary mb-4">
                        Organize produtos que você tem em casa, acompanhe quantidades e prazos de validade, evite desperdícios alimentares e mantenha sua lista de compras sempre atualizada.
                    </p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                        <a href="<?= url('cadastro.php') ?>" class="btn btn-freshy btn-lg px-4 me-md-2">
                            <i class="bi bi-rocket-takeoff-fill"></i> Começar Agora Gratuitamente
                        </a>
                        <a href="<?= url('login.php') ?>" class="btn btn-outline-secondary btn-lg px-4">
                            Já tenho conta
                        </a>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="freshy-card p-4 shadow-sm">
                        <h5 class="fw-bold mb-3 text-success">
                            <i class="bi bi-check2-circle"></i> O que você encontra no Freshy:
                        </h5>
                        <ul class="list-unstyled d-flex flex-column gap-3 mb-0">
                            <li class="d-flex align-items-start gap-2">
                                <i class="bi bi-upc-scan text-success fs-5"></i>
                                <div>
                                    <strong>MyStock:</strong> Cadastro detalhado com quantidade, unidade, local e validade.
                                </div>
                            </li>
                            <li class="d-flex align-items-start gap-2">
                                <i class="bi bi-alarm-fill text-warning fs-5"></i>
                                <div>
                                    <strong>Alertas Automáticos:</strong> Alertas para itens vencidos, críticos e com estoque baixo.
                                </div>
                            </li>
                            <li class="d-flex align-items-start gap-2">
                                <i class="bi bi-arrow-left-right text-primary fs-5"></i>
                                <div>
                                    <strong>Consumo & Desperdício:</strong> Baixa real de estoque e histórico de movimentações.
                                </div>
                            </li>
                            <li class="d-flex align-items-start gap-2">
                                <i class="bi bi-cart-check-fill text-info fs-5"></i>
                                <div>
                                    <strong>Lista de Compras:</strong> Itens com estoque baixo sugeridos com um clique.
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Rodapé -->
    <footer class="footer-freshy mt-auto">
        <div class="container text-center">
            <p class="mb-1">
                <strong><?= e(APP_NAME) ?></strong> — Seu estoque doméstico, mais inteligente &copy; <?= date('Y') ?>
            </p>
            <small class="text-muted">
                Desenvolvimento incremental em PHP 8.2 &amp; MySQL 8.0 • Projeto Acadêmico
            </small>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Freshy Custom JS -->
    <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
