<?php
/**
 * Freshy — Login de Usuários
 */

require_once __DIR__ . '/../app/config/config.php';

// Redireciona se já estiver autenticado
require_guest();

$pageTitle = 'Entrar no Sistema';
$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'A sessão expirou por segurança. Por favor, tente novamente.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $senha = (string) ($_POST['senha'] ?? '');

        if (empty($email) || empty($senha)) {
            $error = 'Por favor, preencha o e-mail e a senha.';
        } else {
            $user = Usuario::autenticar($email, $senha);

            if ($user) {
                // Regeneração do ID de sessão para prevenir Session Fixation
                session_regenerate_id(true);

                // Armazena dados essenciais do usuário na sessão
                $_SESSION['user'] = [
                    'id'    => (int) $user['id'],
                    'nome'  => $user['nome'],
                    'email' => $user['email'],
                ];

                flash('success', "Olá, {$user['nome']}! Você entrou no Freshy com sucesso.");
                redirect('dashboard.php');
            } else {
                $error = 'E-mail ou senha incorretos. Verifique suas credenciais e tente novamente.';
            }
        }
    }
}

require_once APP_PATH . '/views/partials/header.php';
require_once APP_PATH . '/views/partials/navbar.php';
require_once APP_PATH . '/views/partials/alerts.php';
?>

<main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-5">
                <div class="freshy-card p-4 p-md-5 shadow-sm">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-success text-white rounded-circle mb-3" style="width: 56px; height: 56px;">
                            <i class="bi bi-box-arrow-in-right fs-3"></i>
                        </div>
                        <h2 class="h3 fw-bold text-dark">Entrar no Freshy</h2>
                        <p class="text-muted small">Acesse seu estoque doméstico inteligente</p>
                    </div>

                    <?php if ($error !== null): ?>
                        <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                            <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                            <div><?= e($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= url('login.php') ?>" novalidate>
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">E-mail</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" required placeholder="seuemail@exemplo.com" autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="senha" class="form-label fw-semibold">Senha</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="senha" name="senha" required placeholder="Sua senha secreta">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-freshy w-100 py-2 fw-semibold">
                            <i class="bi bi-box-arrow-in-right"></i> Entrar
                        </button>
                    </form>

                    <!-- Card de Apoio para Avaliação Acadêmica -->
                    <div class="mt-4 p-3 bg-light border rounded text-start">
                        <div class="d-flex align-items-center gap-2 mb-1 text-success fw-semibold small">
                            <i class="bi bi-mortarboard-fill"></i> Dados de Acesso para Demonstração:
                        </div>
                        <div class="small text-secondary">
                            <div><strong>E-mail:</strong> <code>demo@freshy.com.br</code></div>
                            <div><strong>Senha:</strong> <code>senha123</code></div>
                        </div>
                    </div>

                    <div class="text-center mt-4 pt-3 border-top">
                        <span class="text-muted small">Ainda não tem conta?</span>
                        <a href="<?= url('cadastro.php') ?>" class="text-success fw-semibold text-decoration-none ms-1">Cadastre-se grátis</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once APP_PATH . '/views/partials/footer.php'; ?>
