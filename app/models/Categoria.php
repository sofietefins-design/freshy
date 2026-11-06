<?php
/**
 * Freshy — Model de Categorias
 */

declare(strict_types=1);

class Categoria {
    /**
     * Lista todas as categorias do usuário com contagem de produtos associados
     */
    public static function allByUsuario(int $usuarioId): array {
        $sql = "
            SELECT c.*, COUNT(p.id) AS total_produtos
            FROM `categorias` c
            LEFT JOIN `produtos` p ON p.categoria_id = c.id AND p.usuario_id = c.usuario_id
            WHERE c.usuario_id = :uid
            GROUP BY c.id
            ORDER BY c.nome ASC
        ";
        $stmt = db()->prepare($sql);
        $stmt->execute([':uid' => $usuarioId]);
        return $stmt->fetchAll();
    }

    /**
     * Busca uma categoria por ID garantindo a propriedade do usuário
     */
    public static function findByIdAndUsuario(int $id, int $usuarioId): ?array {
        $stmt = db()->prepare("SELECT * FROM `categorias` WHERE `id` = :id AND `usuario_id` = :uid LIMIT 1");
        $stmt->execute([':id' => $id, ':uid' => $usuarioId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Verifica se já existe categoria com o mesmo nome para este usuário
     */
    public static function existsByName(int $usuarioId, string $nome, ?int $exceptId = null): bool {
        $sql = "SELECT id FROM `categorias` WHERE `usuario_id` = :uid AND LOWER(TRIM(nome)) = LOWER(TRIM(:nome))";
        $params = [':uid' => $usuarioId, ':nome' => $nome];

        if ($exceptId !== null) {
            $sql .= " AND id != :except_id";
            $params[':except_id'] = $exceptId;
        }

        $stmt = db()->prepare($sql . " LIMIT 1");
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    /**
     * Cadastra nova categoria
     */
    public static function create(int $usuarioId, string $nome): int {
        $stmt = db()->prepare("INSERT INTO `categorias` (`usuario_id`, `nome`) VALUES (:uid, :nome)");
        $stmt->execute([':uid' => $usuarioId, ':nome' => trim($nome)]);
        return (int) db()->lastInsertId();
    }

    /**
     * Atualiza categoria do usuário
     */
    public static function update(int $id, int $usuarioId, string $nome): bool {
        $stmt = db()->prepare("UPDATE `categorias` SET `nome` = :nome WHERE `id` = :id AND `usuario_id` = :uid");
        return $stmt->execute([':nome' => trim($nome), ':id' => $id, ':uid' => $usuarioId]);
    }

    /**
     * Verifica se a categoria possui produtos vinculados
     */
    public static function hasProdutos(int $id, int $usuarioId): bool {
        $stmt = db()->prepare("SELECT id FROM `produtos` WHERE `categoria_id` = :cid AND `usuario_id` = :uid LIMIT 1");
        $stmt->execute([':cid' => $id, ':uid' => $usuarioId]);
        return (bool) $stmt->fetch();
    }

    /**
     * Exclui categoria se pertencer ao usuário e não tiver produtos
     */
    public static function delete(int $id, int $usuarioId): bool {
        if (self::hasProdutos($id, $usuarioId)) {
            return false;
        }
        $stmt = db()->prepare("DELETE FROM `categorias` WHERE `id` = :id AND `usuario_id` = :uid");
        return $stmt->execute([':id' => $id, ':uid' => $usuarioId]);
    }
}
