<?php
/**
 * Freshy — Model de Locais de Armazenamento
 */

declare(strict_types=1);

class Local {
    /**
     * Lista todos os locais do usuário com contagem de produtos associados
     */
    public static function allByUsuario(int $usuarioId): array {
        $sql = "
            SELECT l.*, COUNT(p.id) AS total_produtos
            FROM `locais` l
            LEFT JOIN `produtos` p ON p.local_id = l.id AND p.usuario_id = l.usuario_id
            WHERE l.usuario_id = :uid
            GROUP BY l.id
            ORDER BY l.nome ASC
        ";
        $stmt = db()->prepare($sql);
        $stmt->execute([':uid' => $usuarioId]);
        return $stmt->fetchAll();
    }

    /**
     * Busca um local por ID garantindo a propriedade do usuário
     */
    public static function findByIdAndUsuario(int $id, int $usuarioId): ?array {
        $stmt = db()->prepare("SELECT * FROM `locais` WHERE `id` = :id AND `usuario_id` = :uid LIMIT 1");
        $stmt->execute([':id' => $id, ':uid' => $usuarioId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Verifica se já existe local com o mesmo nome para este usuário
     */
    public static function existsByName(int $usuarioId, string $nome, ?int $exceptId = null): bool {
        $sql = "SELECT id FROM `locais` WHERE `usuario_id` = :uid AND LOWER(TRIM(nome)) = LOWER(TRIM(:nome))";
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
     * Cadastra novo local
     */
    public static function create(int $usuarioId, string $nome): int {
        $stmt = db()->prepare("INSERT INTO `locais` (`usuario_id`, `nome`) VALUES (:uid, :nome)");
        $stmt->execute([':uid' => $usuarioId, ':nome' => trim($nome)]);
        return (int) db()->lastInsertId();
    }

    /**
     * Atualiza local do usuário
     */
    public static function update(int $id, int $usuarioId, string $nome): bool {
        $stmt = db()->prepare("UPDATE `locais` SET `nome` = :nome WHERE `id` = :id AND `usuario_id` = :uid");
        return $stmt->execute([':nome' => trim($nome), ':id' => $id, ':uid' => $usuarioId]);
    }

    /**
     * Verifica se o local possui produtos vinculados
     */
    public static function hasProdutos(int $id, int $usuarioId): bool {
        $stmt = db()->prepare("SELECT id FROM `produtos` WHERE `local_id` = :lid AND `usuario_id` = :uid LIMIT 1");
        $stmt->execute([':lid' => $id, ':uid' => $usuarioId]);
        return (bool) $stmt->fetch();
    }

    /**
     * Exclui local se pertencer ao usuário e não tiver produtos vinculados
     */
    public static function delete(int $id, int $usuarioId): bool {
        if (self::hasProdutos($id, $usuarioId)) {
            return false;
        }
        $stmt = db()->prepare("DELETE FROM `locais` WHERE `id` = :id AND `usuario_id` = :uid");
        return $stmt->execute([':id' => $id, ':uid' => $usuarioId]);
    }
}
