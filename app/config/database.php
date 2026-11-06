<?php
/**
 * Freshy — Seu estoque doméstico, mais inteligente
 * Gerenciador de Conexão com Banco de Dados via PDO
 */

declare(strict_types=1);

/**
 * Retorna a instância única (Singleton) da conexão PDO com o MySQL
 */
function db(): PDO {
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host    = env('DB_HOST', '127.0.0.1');
    $port    = env('DB_PORT', '3306');
    $dbname  = env('DB_NAME', 'freshy_db');
    $user    = env('DB_USER', 'root');
    $pass    = env('DB_PASS', '');
    $charset = env('DB_CHARSET', 'utf8mb4');

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci",
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Registra o erro técnico em log sem expor credenciais ao usuário
        error_log('Freshy DB Connection Error: ' . $e->getMessage());

        if (defined('APP_DEBUG') && APP_DEBUG) {
            die('<strong>Erro de Conexão com o Banco de Dados:</strong> ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
        }

        die('Desculpe, ocorreu uma instabilidade temporária no banco de dados. Por favor, tente novamente em alguns instantes.');
    }
}
