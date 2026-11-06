<?php
/**
 * Freshy — Script de Migração e Criação do Banco de Dados
 * Executável via CLI: php database/migrate.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';

echo "=== Freshy: Executando Migração de Banco de Dados ===\n";

$host    = env('DB_HOST', '127.0.0.1');
$port    = env('DB_PORT', '3306');
$dbname  = env('DB_NAME', 'freshy_db');
$user    = env('DB_USER', 'root');
$pass    = env('DB_PASS', '');
$charset = env('DB_CHARSET', 'utf8mb4');

try {
    // Conecta inicialmente sem selecionar a base para poder criá-la se não existir
    $dsnNoDb = "mysql:host={$host};port={$port};charset={$charset}";
    $pdoInit = new PDO($dsnNoDb, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "1. Criando banco de dados `{$dbname}` se não existir...\n";
    $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET {$charset} COLLATE {$charset}_unicode_ci;");

    // Conecta agora à base criada
    $pdo = db();
    echo "2. Conectado com sucesso ao banco `{$dbname}`.\n";

    // Executa schema.sql
    $schemaPath = __DIR__ . '/schema.sql';
    if (!file_exists($schemaPath)) {
        throw new RuntimeException("Arquivo schema.sql não encontrado em: {$schemaPath}");
    }
    echo "3. Aplicando tabelas do `schema.sql`...\n";
    $schemaSql = file_get_contents($schemaPath);
    $pdo->exec($schemaSql);
    echo "   -> Tabelas e chaves estrangeiras criadas com sucesso!\n";

    // Executa seed.sql
    $seedPath = __DIR__ . '/seed.sql';
    if (file_exists($seedPath)) {
        echo "4. Aplicando dados de teste do `seed.sql`...\n";
        $seedSql = file_get_contents($seedPath);
        $pdo->exec($seedSql);
        echo "   -> Dados iniciais de teste inseridos com sucesso!\n";
    }

    echo "=== Migração concluída com 100% de sucesso! ===\n";
} catch (Exception $e) {
    echo "ERRO durante a migração: " . $e->getMessage() . "\n";
    exit(1);
}
