<?php
/**
 * Freshy — Cadastro de Novos Usuários
 */

require_once __DIR__ . '/../app/config/config.php';

// Redireciona se já estiver autenticado
require_guest();

$pageTitle = 'Criar Conta';
$errors = [];
$nome = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'A validação de segurança (CSRF) expirou. Por favor, tente novamente.';
    }

    $nome            = trim($_POST['nome'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $senha           = (string) ($_POST['senha'] ?? '');
    $senhaConfirmacao = (string) ($_POST['senha_confirmacao'] ?? '');

    // Validações no servidor
    if (mb_strlen($nome) < 3) {
        $errors[] = 'O nome deve conter pelo menos 3 caracteres.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Informe um endereço de e-mail válido.';
    } elseif (Usuario::emailExists($email)) {
        $errors[] = 'Este e-mail já está em uso por outra conta. Faça login ou use outro endereço.';
    }

    if (strlen($senha) < 6) {
        $errors[] = 'A senha deve conter no mínimo 6 caracteres.';
    }

    if ($senha !== $senhaConfirmacao) {
        $errors[] = 'A confirmação de senha não confere com a senha informada.';
    }

    if (empty($errors)) {
        try {
            $userId = Usuario::create($nome, $email, $senha);

            // Realiza login automático com regeneração de sessão segura
            session_regenerate_id(true);
            $user = Usuario::findById($userId);
            $_SESSION['user'] = $user;

            flash('success', "Conta criada com sucesso! Seja muito bem-vindo ao Freshy, {$nome}.");
            redirect('dashboard.php');
        } catch (Exception $e) {
            error_log('Erro no cadastro Freshy: ' . $e->getMessage());
            $errors[] = 'Ocorreu um erro interno ao criar sua conta. Por favor, tente novamente.';
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
                            <i class="bi bi-person-plus-fill fs-3"></i>
                        </div>
                        <h2 class="h3 fw-bold text-dark">Crie sua Conta no Freshy</h2>
                        <p class="text-muted small">Organize seu estoque doméstico e reduza o desperdício</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger" role="alert">
                            <h6 class="alert-heading fw-bold mb-2">
                                <i class="bi bi-exclamation-octagon-fill"></i> Corrija os erros abaixo:
                            </h6>
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= e($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= url('cadastro.php') ?>" novalidate>
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="nome" class="form-label fw-semibold">Nome Completo</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="nome" name="nome" value="<?= e($nome) ?>" required placeholder="Ex: Maria Silva" autofocus>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">E-mail</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" required placeholder="seuemail@exemplo.com">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="senha" class="form-label fw-semibold">Senha</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="senha" name="senha" required placeholder="Mínimo de 6 caracteres">
                            </div>
                            <div class="form-text">Escolha uma senha segura de pelo menos 6 caracteres.</div>
                        </div>

                        <div class="mb-4">
                            <label for="senha_confirmacao" class="form-label fw-semibold">Confirmar Senha</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                                <input type="password" class="form-control" id="senha_confirmacao" name="senha_confirmacao" required placeholder="Repita sua senha">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-freshy w-100 py-2 fw-semibold">
                            <i class="bi bi-check-lg"></i> Concluir Cadastro
                        </button>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top">
                        <span class="text-muted small">Já possui uma conta?</span>
                        <a href="<?= url('login.php') ?>" class="text-success fw-semibold text-decoration-none ms-1">Fazer Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once APP_PATH . '/views/partials/footer.php'; ?>
