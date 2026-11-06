<?php
/**
 * Freshy — Model de Lista de Compras Inteligente
 */

declare(strict_types=1);

class ListaCompras {
    /**
     * Obtém o ID da lista ativa do usuário ou cria se não existir
     */
    public static function getOrCreateListaId(int $usuarioId): int {
        $stmt = db()->prepare("SELECT id FROM `listas_compras` WHERE `usuario_id` = :uid LIMIT 1");
        $stmt->execute([':uid' => $usuarioId]);
        $id = $stmt->fetchColumn();

        if ($id) {
            return (int) $id;
        }

        $stmtInsert = db()->prepare("INSERT INTO `listas_compras` (`usuario_id`, `created_at`) VALUES (:uid, NOW())");
        $stmtInsert->execute([':uid' => $usuarioId]);
        return (int) db()->lastInsertId();
    }

    /**
     * Retorna todos os itens da lista do usuário
     */
    public static function getItens(int $usuarioId): array {
        $listaId = self::getOrCreateListaId($usuarioId);

        $sql = "
            SELECT i.*, 
                   p.quantidade AS estoque_atual, 
                   p.quantidade_minima, 
                   p.unidade,
                   c.nome AS categoria_nome
            FROM `itens_lista_compras` i
            LEFT JOIN `produtos` p ON p.id = i.produto_id AND p.usuario_id = :uid
            LEFT JOIN `categorias` c ON c.id = p.categoria_id
            WHERE i.lista_id = :lid
            ORDER BY i.comprado ASC, i.id DESC
        ";
        $stmt = db()->prepare($sql);
        $stmt->execute([':uid' => $usuarioId, ':lid' => $listaId]);
        return $stmt->fetchAll();
    }

    /**
     * Adiciona item à lista de compras
     */
    public static function adicionarItem(int $usuarioId, ?int $produtoId, string $nome, float $quantidade = 1.0): int {
        $listaId = self::getOrCreateListaId($usuarioId);

        // Se houver produtoId, verifica se pertence ao usuário
        if ($produtoId !== null) {
            $stmtProd = db()->prepare("SELECT nome FROM `produtos` WHERE `id` = :pid AND `usuario_id` = :uid");
            $stmtProd->execute([':pid' => $produtoId, ':uid' => $usuarioId]);
            $p = $stmtProd->fetch();
            if (!$p) {
                $produtoId = null; // Caso não seja do usuário, trata como manual
            } else {
                $nome = $p['nome'];
            }
        }

        // Verifica se item já está na lista e não comprado
        if ($produtoId !== null) {
            $stmtCheck = db()->prepare("SELECT id, quantidade FROM `itens_lista_compras` WHERE `lista_id` = :lid AND `produto_id` = :pid AND `comprado` = 0 LIMIT 1");
            $stmtCheck->execute([':lid' => $listaId, ':pid' => $produtoId]);
            $existing = $stmtCheck->fetch();

            if ($existing) {
                // Atualiza quantidade incrementando
                $novaQtd = (float)$existing['quantidade'] + $quantidade;
                $stmtUp = db()->prepare("UPDATE `itens_lista_compras` SET `quantidade` = :qtd WHERE `id` = :id");
                $stmtUp->execute([':qtd' => $novaQtd, ':id' => $existing['id']]);
                return (int) $existing['id'];
            }
        }

        $stmt = db()->prepare("
            INSERT INTO `itens_lista_compras` (`lista_id`, `produto_id`, `nome_item`, `quantidade`, `comprado`, `created_at`)
            VALUES (:lid, :pid, :nome, :qtd, 0, NOW())
        ");
        $stmt->execute([
            ':lid'  => $listaId,
            ':pid'  => $produtoId,
            ':nome' => trim($nome),
            ':qtd'  => $quantidade > 0 ? $quantidade : 1.0,
        ]);

        return (int) db()->lastInsertId();
    }

    /**
     * Alterna o estado de 'comprado' de um item
     */
    public static function alternarComprado(int $itemId, int $usuarioId): bool {
        $listaId = self::getOrCreateListaId($usuarioId);

        $stmt = db()->prepare("
            UPDATE `itens_lista_compras` 
            SET `comprado` = CASE WHEN `comprado` = 1 THEN 0 ELSE 1 END 
            WHERE `id` = :id AND `lista_id` = :lid
        ");
        return $stmt->execute([':id' => $itemId, ':lid' => $listaId]);
    }

    /**
     * Atualiza quantidade de um item
     */
    public static function atualizarQuantidade(int $itemId, int $usuarioId, float $quantidade): bool {
        if ($quantidade <= 0) {
            return self::removerItem($itemId, $usuarioId);
        }

        $listaId = self::getOrCreateListaId($usuarioId);
        $stmt = db()->prepare("UPDATE `itens_lista_compras` SET `quantidade` = :qtd WHERE `id` = :id AND `lista_id` = :lid");
        return $stmt->execute([':qtd' => $quantidade, ':id' => $itemId, ':lid' => $listaId]);
    }

    /**
     * Remove um item da lista
     */
    public static function removerItem(int $itemId, int $usuarioId): bool {
        $listaId = self::getOrCreateListaId($usuarioId);
        $stmt = db()->prepare("DELETE FROM `itens_lista_compras` WHERE `id` = :id AND `lista_id` = :lid");
        return $stmt->execute([':id' => $itemId, ':lid' => $listaId]);
    }

    /**
     * Importa automaticamente produtos que estão com estoque baixo
     */
    public static function importarEstoqueBaixo(int $usuarioId): int {
        $produtos = Produto::allByUsuario($usuarioId, ['situacao' => 'baixo']);
        $importados = 0;

        foreach ($produtos as $p) {
            $qtdNecessaria = max(1.0, (float)$p['quantidade_minima'] - (float)$p['quantidade']);
            self::adicionarItem($usuarioId, (int)$p['id'], $p['nome'], $qtdNecessaria);
            $importados++;
        }

        return $importados;
    }

    /**
     * Remove todos os itens marcados como comprados
     */
    public static function limparComprados(int $usuarioId): int {
        $listaId = self::getOrCreateListaId($usuarioId);
        $stmt = db()->prepare("DELETE FROM `itens_lista_compras` WHERE `lista_id` = :lid AND `comprado` = 1");
        $stmt->execute([':lid' => $listaId]);
        return $stmt->rowCount();
    }
}

class ItemListaCompras {
    public static function addItem(int $listaId, ?int $produtoId, string $nome, float $quantidade): int {
        $stmt = db()->prepare("
            INSERT INTO `itens_lista_compras` (`lista_id`, `produto_id`, `nome_item`, `quantidade`, `comprado`, `created_at`)
            VALUES (:lid, :pid, :nome, :qtd, 0, NOW())
        ");
        $stmt->execute([
            ':lid'  => $listaId,
            ':pid'  => $produtoId,
            ':nome' => trim($nome),
            ':qtd'  => $quantidade > 0 ? $quantidade : 1.0,
        ]);
        return (int) db()->lastInsertId();
    }
}
