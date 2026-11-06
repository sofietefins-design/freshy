<?php
/**
 * Freshy — Exibição de Mensagens Flash e Alertas
 */
$flashes = get_all_flashes();
?>
<?php if (!empty($flashes)): ?>
    <div class="container mt-3">
        <?php foreach ($flashes as $type => $message): ?>
            <?php
            // Normaliza classes Bootstrap
            $alertClass = match($type) {
                'error'   => 'danger',
                'success' => 'success',
                'warning' => 'warning',
                'info'    => 'info',
                default   => 'secondary',
            };
            $iconClass = match($type) {
                'error'   => 'bi-exclamation-octagon-fill',
                'success' => 'bi-check-circle-fill',
                'warning' => 'bi-exclamation-triangle-fill',
                'info'    => 'bi-info-circle-fill',
                default   => 'bi-bell-fill',
            };
            ?>
            <div class="alert alert-<?= e($alertClass) ?> alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm" role="alert">
                <i class="bi <?= e($iconClass) ?> fs-5"></i>
                <div class="flex-grow-1"><?= e($message) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
