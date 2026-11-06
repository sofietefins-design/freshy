<?php
/**
 * Freshy — Model de Movimentações de Estoque (Auditoria, Consumo e Desperdício)
 */

declare(strict_types=1);

class Movimentacao {
    /**
     * Registra CONSUMO de produto com transação atômica e proteção contra saldo negativo
     */
    public static function registrarConsumo(int $usuarioId, int $produtoId, float $quantidade, ?string $motivo = null): array {
        if ($quantidade <= 0) {
            throw new InvalidArgumentException('A quantidade consumida deve ser maior que zero.');
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            // Busca o produto com bloqueio de leitura / verificação de propriedade
            $stmt = $pdo->prepare("SELECT id, nome, quantidade, quantidade_minima, unidade FROM `produtos` WHERE `id` = :pid AND `usuario_id` = :uid FOR UPDATE");
            $stmt->execute([':pid' => $produtoId, ':uid' => $usuarioId]);
            $produto = $stmt->fetch();

            if (!$produto) {
                throw new RuntimeException('Produto não encontrado ou você não tem permissão para movimentá-lo.');
            }

            $estoqueAtual = (float) $produto['quantidade'];
            if ($quantidade > $estoqueAtual) {
                throw new RuntimeException("Estoque insuficiente. Disponível: {$estoqueAtual} {$produto['unidade']}, solicitado: {$quantidade} {$produto['unidade']}.");
            }

            $novoEstoque = $estoqueAtual - $quantidade;

            // Atualiza quantidade do produto
            $stmtUpdate = $pdo->prepare("UPDATE `produtos` SET `quantidade` = :nova_qtd WHERE `id` = :pid AND `usuario_id` = :uid");
            $stmtUpdate->execute([
                ':nova_qtd' => $novoEstoque,
                ':pid'      => $produtoId,
                ':uid'      => $usuarioId,
            ]);

            // Insere registro de auditoria na tabela movimentacoes
            $stmtMov = $pdo->prepare("
                INSERT INTO `movimentacoes` (`usuario_id`, `produto_id`, `tipo`, `quantidade`, `motivo`, `created_at`)
                VALUES (:uid, :pid, 'CONSUMO', :qtd, :motivo, NOW())
            ");
            $stmtMov->execute([
                ':uid'    => $usuarioId,
                ':pid'    => $produtoId,
                ':qtd'    => $quantidade,
                ':motivo' => !empty($motivo) ? trim($motivo) : 'Consumo registrado',
            ]);

            $pdo->commit();

            return [
                'sucesso'        => true,
                'produto'        => $produto['nome'],
                'unidade'        => $produto['unidade'],
                'quantidade'     => $quantidade,
                'novo_estoque'   => $novoEstoque,
                'estoque_baixo'  => $novoEstoque <= (float) $produto['quantidade_minima'],
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Registra DESPERDÍCIO de produto com transação atômica e motivo obrigatório
     */
    public static function registrarDesperdicio(int $usuarioId, int $produtoId, float $quantidade, string $motivo): array {
        if ($quantidade <= 0) {
            throw new InvalidArgumentException('A quantidade desperdiçada deve ser maior que zero.');
        }

        if (empty(trim($motivo))) {
            throw new InvalidArgumentException('O motivo do desperdício é obrigatório.');
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            // Busca o produto com bloqueio de leitura / verificação de propriedade
            $stmt = $pdo->prepare("SELECT id, nome, quantidade, quantidade_minima, unidade FROM `produtos` WHERE `id` = :pid AND `usuario_id` = :uid FOR UPDATE");
            $stmt->execute([':pid' => $produtoId, ':uid' => $usuarioId]);
            $produto = $stmt->fetch();

            if (!$produto) {
                throw new RuntimeException('Produto não encontrado ou permissão negada.');
            }

            $estoqueAtual = (float) $produto['quantidade'];
            if ($quantidade > $estoqueAtual) {
                throw new RuntimeException("Estoque insuficiente para registrar descarte. Disponível: {$estoqueAtual} {$produto['unidade']}, informado: {$quantidade} {$produto['unidade']}.");
            }

            $novoEstoque = $estoqueAtual - $quantidade;

            // Atualiza quantidade do produto
            $stmtUpdate = $pdo->prepare("UPDATE `produtos` SET `quantidade` = :nova_qtd WHERE `id` = :pid AND `usuario_id` = :uid");
            $stmtUpdate->execute([
                ':nova_qtd' => $novoEstoque,
                ':pid'      => $produtoId,
                ':uid'      => $usuarioId,
            ]);

            // Insere na tabela movimentacoes
            $stmtMov = $pdo->prepare("
                INSERT INTO `movimentacoes` (`usuario_id`, `produto_id`, `tipo`, `quantidade`, `motivo`, `created_at`)
                VALUES (:uid, :pid, 'DESPERDICIO', :qtd, :motivo, NOW())
            ");
            $stmtMov->execute([
                ':uid'    => $usuarioId,
                ':pid'    => $produtoId,
                ':qtd'    => $quantidade,
                ':motivo' => trim($motivo),
            ]);

            $pdo->commit();

            return [
                'sucesso'        => true,
                'produto'        => $produto['nome'],
                'unidade'        => $produto['unidade'],
                'quantidade'     => $quantidade,
                'novo_estoque'   => $novoEstoque,
                'estoque_baixo'  => $novoEstoque <= (float) $produto['quantidade_minima'],
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Retorna o histórico de movimentações com filtros por tipo e produto
     */
    public static function allByUsuario(int $usuarioId, array $filters = []): array {
        $sql = "
            SELECT m.*, 
                   p.nome AS produto_nome, 
                   p.unidade AS produto_unidade,
                   c.nome AS categoria_nome
            FROM `movimentacoes` m
            INNER JOIN `produtos` p ON p.id = m.produto_id AND p.usuario_id = m.usuario_id
            INNER JOIN `categorias` c ON c.id = p.categoria_id
            WHERE m.usuario_id = :uid
        ";
        $params = [':uid' => $usuarioId];

        if (!empty($filters['tipo'])) {
            $sql .= " AND m.tipo = :tipo";
            $params[':tipo'] = strtoupper(trim($filters['tipo']));
        }

        if (!empty($filters['produto_id'])) {
            $sql .= " AND m.produto_id = :pid";
            $params[':pid'] = (int) $filters['produto_id'];
        }

        $sql .= " ORDER BY m.created_at DESC LIMIT 200";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Retorna resumo de movimentações recentes para o Dashboard
     */
    public static function getRecentes(int $usuarioId, int $limit = 5): array {
        $sql = "
            SELECT m.*, 
                   p.nome AS produto_nome, 
                   p.unidade AS produto_unidade
            FROM `movimentacoes` m
            INNER JOIN `produtos` p ON p.id = m.produto_id AND p.usuario_id = m.usuario_id
            WHERE m.usuario_id = :uid
            ORDER BY m.created_at DESC
            LIMIT :limit
        ";
        $stmt = db()->prepare($sql);
        $stmt->bindValue(':uid', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
