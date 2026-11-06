<?php
/**
 * Freshy — Seu estoque doméstico, mais inteligente
 * Arquivo de Configuração Central e Carregador de Ambiente
 */

// Define caminhos fundamentais
define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'app');
define('PUBLIC_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'public');

/**
 * Função simples e segura para carregar variáveis do arquivo .env
 */
function load_env(string $path): void {
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Ignora comentários e linhas vazias
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove aspas simples ou duplas ao redor do valor
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Carrega o arquivo .env se existir
load_env(ROOT_PATH . DIRECTORY_SEPARATOR . '.env');

/**
 * Obtém valor de variável de ambiente com valor padrão
 */
function env(string $key, mixed $default = null): mixed {
    $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($val === false || $val === null) {
        return $default;
    }

    // Trata valores booleanos em string
    $lower = strtolower($val);
    if ($lower === 'true' || $lower === '(true)') return true;
    if ($lower === 'false' || $lower === '(false)') return false;
    if ($lower === 'null' || $lower === '(null)') return null;

    return $val;
}

// Configurações gerais da aplicação
define('APP_NAME', env('APP_NAME', 'Freshy'));
define('APP_ENV', env('APP_ENV', 'development'));
define('APP_DEBUG', (bool) env('APP_DEBUG', true));
define('APP_URL', rtrim(env('APP_URL', 'http://localhost/sofial/Freshy/public'), '/'));

// Configuração de exibição de erros conforme ambiente
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
}

// Fuso horário padrão do Brasil
date_default_timezone_set('America/Sao_Paulo');

// Inicialização segura de sessão (apenas em ambiente web)
if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
    // Diretivas de segurança de cookie de sessão
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    session_name(env('SESSION_NAME', 'freshy_session'));

    session_set_cookie_params([
        'lifetime' => (int) env('SESSION_LIFETIME', 7200),
        'path'     => '/',
        'domain'   => '',
        'secure'   => $is_https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

// Carrega os helpers essenciais
require_once APP_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// Carrega a conexão com o banco de dados
require_once APP_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
