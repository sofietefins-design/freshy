<?php
/**
 * Freshy — Model de Usuários
 */

declare(strict_types=1);

class Usuario {
    /**
     * Busca usuário por e-mail
     */
    public static function findByEmail(string $email): ?array {
        $stmt = db()->prepare("SELECT * FROM `usuarios` WHERE `email` = :email LIMIT 1");
        $stmt->execute([':email' => trim($email)]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Busca usuário por ID
     */
    public static function findById(int $id): ?array {
        $stmt = db()->prepare("SELECT `id`, `nome`, `email`, `created_at` FROM `usuarios` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Verifica se e-mail já está cadastrado
     */
    public static function emailExists(string $email, ?int $exceptUserId = null): bool {
        $sql = "SELECT `id` FROM `usuarios` WHERE `email` = :email";
        $params = [':email' => trim($email)];

        if ($exceptUserId !== null) {
            $sql .= " AND `id` != :except_id";
            $params[':except_id'] = $exceptUserId;
        }

        $stmt = db()->prepare($sql . " LIMIT 1");
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    /**
     * Cadastra um novo usuário com senha criptografada e inicializa categorias, locais e lista de compras
     */
    public static function create(string $nome, string $email, string $senha): int {
        $pdo = db();
        $pdo->beginTransaction();

        try {
            $hash = password_hash($senha, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("
                INSERT INTO `usuarios` (`nome`, `email`, `senha_hash`, `created_at`)
                VALUES (:nome, :email, :senha_hash, NOW())
            ");
            $stmt->execute([
                ':nome'       => trim($nome),
                ':email'      => trim(strtolower($email)),
                ':senha_hash' => $hash,
            ]);

            $userId = (int) $pdo->lastInsertId();

            // Cria categorias padrão para o novo usuário
            $categoriasPadrao = ['Laticínios', 'Hortifrúti', 'Mercearia', 'Padaria', 'Carnes e Aves', 'Bebidas', 'Limpeza', 'Congelados', 'Outros'];
            $stmtCat = $pdo->prepare("INSERT INTO `categorias` (`usuario_id`, `nome`) VALUES (:uid, :nome)");
            foreach ($categoriasPadrao as $cat) {
                $stmtCat->execute([':uid' => $userId, ':nome' => $cat]);
            }

            // Cria locais de armazenamento padrão
            $locaisPadrao = ['Geladeira', 'Freezer', 'Despensa', 'Armário da Cozinha', 'Lavanderia', 'Bancada'];
            $stmtLoc = $pdo->prepare("INSERT INTO `locais` (`usuario_id`, `nome`) VALUES (:uid, :nome)");
            foreach ($locaisPadrao as $loc) {
                $stmtLoc->execute([':uid' => $userId, ':nome' => $loc]);
            }

            // Cria lista de compras inicial vazia para o usuário
            $stmtLista = $pdo->prepare("INSERT INTO `listas_compras` (`usuario_id`, `created_at`) VALUES (:uid, NOW())");
            $stmtLista->execute([':uid' => $userId]);

            $pdo->commit();
            return $userId;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Autentica e retorna os dados do usuário ou null
     */
    public static function autenticar(string $email, string $senha): ?array {
        $user = self::findByEmail(strtolower(trim($email)));
        if (!$user) {
            return null;
        }

        if (password_verify($senha, $user['senha_hash'])) {
            // Remove o hash de senha antes de retornar os dados
            unset($user['senha_hash']);
            return $user;
        }

        return null;
    }
}
