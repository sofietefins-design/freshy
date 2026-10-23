<?php
/**
 * Freshy — Funções Auxiliares Globais de Segurança e Utilidades
 */

/**
 * Escapa strings contra ataques Cross-Site Scripting (XSS)
 */
function e(?string $value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Gera ou recupera o token CSRF atual da sessão
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Gera um campo de formulário HTML oculto com o token CSRF
 */
function csrf_field(): string {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Valida o token CSRF recebido na requisição POST
 */
function csrf_verify(): bool {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true;
    }

    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    $sessionToken = $_SESSION['csrf_token'] ?? null;

    if (!$token || !$sessionToken) {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

/**
 * Gera URL absoluta para um recurso do sistema
 */
function url(string $path = ''): string {
    $path = ltrim($path, '/');
    return APP_URL . ($path !== '' ? '/' . $path : '');
}

/**
 * Gera URL absoluta para um asset (CSS, JS, imagens)
 */
function asset(string $path): string {
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Redireciona para uma URL relativa ou absoluta do sistema
 */
function redirect(string $path): never {
    $target = str_starts_with($path, 'http://') || str_starts_with($path, 'https://')
        ? $path
        : url($path);

    header("Location: {$target}");
    exit;
}

/**
 * Define ou obtém mensagens flash na sessão
 */
function flash(string $type, ?string $message = null): ?string {
    if ($message !== null) {
        $_SESSION['flash'][$type] = $message;
        return null;
    }

    if (isset($_SESSION['flash'][$type])) {
        $msg = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $msg;
    }

    return null;
}

/**
 * Retorna todas as mensagens flash pendentes e limpa a sessão
 */
function get_all_flashes(): array {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/**
 * Verifica se o usuário atual está autenticado
 */
function is_authenticated(): bool {
    return !empty($_SESSION['user']['id']);
}

/**
 * Retorna os dados do usuário autenticado na sessão
 */
function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Retorna o ID do usuário autenticado ou null
 */
function user_id(): ?int {
    return isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null;
}

/**
 * Exige autenticação para acessar a página atual
 */
function require_auth(): void {
    if (!is_authenticated()) {
        flash('warning', 'Você precisa fazer login para acessar esta página.');
        redirect('login.php');
    }
}

/**
 * Exige que o usuário seja visitante (não logado)
 */
function require_guest(): void {
    if (is_authenticated()) {
        redirect('dashboard.php');
    }
}

/**
 * Formata data no formato brasileiro (d/m/Y)
 */
function format_date(?string $date): string {
    if (empty($date)) {
        return '-';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date('d/m/Y', $timestamp) : '-';
}

/**
 * Formata data e hora no formato brasileiro (d/m/Y H:i)
 */
function format_datetime(?string $datetime): string {
    if (empty($datetime)) {
        return '-';
    }
    $timestamp = strtotime($datetime);
    return $timestamp ? date('d/m/Y H:i', $timestamp) : '-';
}
