<?php
/**
 * Freshy — Model de Produtos (MyStock)
 */

declare(strict_types=1);

class Produto {
    /**
     * Lista produtos do usuário com suporte a filtros dinâmicos
     */
    public static function allByUsuario(int $usuarioId, array $filters = []): array {
        $sql = "
            SELECT p.*, 
                   c.nome AS categoria_nome, 
                   l.nome AS local_nome
            FROM `produtos` p
            INNER JOIN `categorias` c ON c.id = p.categoria_id AND c.usuario_id = p.usuario_id
            INNER JOIN `locais` l ON l.id = p.local_id AND l.usuario_id = p.usuario_id
            WHERE p.usuario_id = :uid
        ";
        $params = [':uid' => $usuarioId];

        // Filtro por busca textual (nome do produto)
        if (!empty($filters['busca'])) {
            $sql .= " AND p.nome LIKE :busca";
            $params[':busca'] = '%' . trim($filters['busca']) . '%';
        }

        // Filtro por categoria
        if (!empty($filters['categoria_id'])) {
            $sql .= " AND p.categoria_id = :cat_id";
            $params[':cat_id'] = (int) $filters['categoria_id'];
        }

        // Filtro por local
        if (!empty($filters['local_id'])) {
            $sql .= " AND p.local_id = :loc_id";
            $params[':loc_id'] = (int) $filters['local_id'];
        }

        // Ordenação padrão inteligente:
        // 1. Produtos com validade primeiro (mais próximos de vencer), depois sem validade
        // 2. Ordem alfabética
        $sql .= " ORDER BY CASE WHEN p.data_validade IS NULL THEN 1 ELSE 0 END, p.data_validade ASC, p.nome ASC";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $produtos = $stmt->fetchAll();

        // Processa as regras de negócio em cada produto
        foreach ($produtos as &$prod) {
            $prod['status_validade'] = calcular_status_validade($prod['data_validade']);
            $prod['status_estoque']  = calcular_status_estoque($prod['quantidade'], $prod['quantidade_minima']);
            $prod['is_baixo']        = is_estoque_baixo($prod['quantidade'], $prod['quantidade_minima']);
        }
        unset($prod);

        // Aplica filtro de situação pós-cálculo se solicitado
        if (!empty($filters['situacao'])) {
            $sit = $filters['situacao'];
            $produtos = array_values(array_filter($produtos, function ($p) use ($sit) {
                if ($sit === 'baixo') return $p['is_baixo'];
                if ($sit === 'vencido') return $p['status_validade']['status'] === 'vencido';
                if ($sit === 'critico') return $p['status_validade']['status'] === 'critico';
                if ($sit === 'atencao') return $p['status_validade']['status'] === 'atencao';
                if ($sit === 'normal') return $p['status_validade']['status'] === 'normal';
                return true;
            }));
        }

        return $produtos;
    }

    /**
     * Busca um produto por ID garantindo que pertence ao usuário autenticado
     */
    public static function findByIdAndUsuario(int $id, int $usuarioId): ?array {
        $sql = "
            SELECT p.*, 
                   c.nome AS categoria_nome, 
                   l.nome AS local_nome
            FROM `produtos` p
            INNER JOIN `categorias` c ON c.id = p.categoria_id AND c.usuario_id = p.usuario_id
            INNER JOIN `locais` l ON l.id = p.local_id AND l.usuario_id = p.usuario_id
            WHERE p.id = :id AND p.usuario_id = :uid
            LIMIT 1
        ";
        $stmt = db()->prepare($sql);
        $stmt->execute([':id' => $id, ':uid' => $usuarioId]);
        $prod = $stmt->fetch();

        if ($prod) {
            $prod['status_validade'] = calcular_status_validade($prod['data_validade']);
            $prod['status_estoque']  = calcular_status_estoque($prod['quantidade'], $prod['quantidade_minima']);
            $prod['is_baixo']        = is_estoque_baixo($prod['quantidade'], $prod['quantidade_minima']);
            return $prod;
        }

        return null;
    }

    /**
     * Cadastra um novo produto com transação, registrando a entrada inicial no histórico
     */
    public static function create(int $usuarioId, array $data): int {
        $pdo = db();
        $pdo->beginTransaction();

        try {
            // Garante que categoria e local pertencem ao usuário
            $cat = Categoria::findByIdAndUsuario((int) $data['categoria_id'], $usuarioId);
            $loc = Local::findByIdAndUsuario((int) $data['local_id'], $usuarioId);

            if (!$cat || !$loc) {
                throw new InvalidArgumentException('Categoria ou local inválido ou não pertencente ao seu usuário.');
            }

            $validade = !empty($data['data_validade']) ? $data['data_validade'] : null;

            $stmt = $pdo->prepare("
                INSERT INTO `produtos` 
                (`usuario_id`, `categoria_id`, `local_id`, `nome`, `quantidade`, `unidade`, `quantidade_minima`, `data_validade`, `created_at`)
                VALUES 
                (:uid, :cid, :lid, :nome, :qtd, :unidade, :qtd_min, :validade, NOW())
            ");

            $stmt->execute([
                ':uid'      => $usuarioId,
                ':cid'      => (int) $data['categoria_id'],
                ':lid'      => (int) $data['local_id'],
                ':nome'     => trim($data['nome']),
                ':qtd'      => (float) $data['quantidade'],
                ':unidade'  => trim($data['unidade'] ?? 'un'),
                ':qtd_min'  => (float) ($data['quantidade_minima'] ?? 1.0),
                ':validade' => $validade,
            ]);

            $produtoId = (int) $pdo->lastInsertId();

            // Se cadastrado com quantidade > 0, registra a movimentação de ENTRADA inicial
            if ((float)$data['quantidade'] > 0) {
                $stmtMov = $pdo->prepare("
                    INSERT INTO `movimentacoes` (`usuario_id`, `produto_id`, `tipo`, `quantidade`, `motivo`, `created_at`)
                    VALUES (:uid, :pid, 'ENTRADA', :qtd, :motivo, NOW())
                ");
                $stmtMov->execute([
                    ':uid'    => $usuarioId,
                    ':pid'    => $produtoId,
                    ':qtd'    => (float) $data['quantidade'],
                    ':motivo' => 'Cadastro inicial no MyStock',
                ]);
            }

            $pdo->commit();
            return $produtoId;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Atualiza dados de um produto existente
     */
    public static function update(int $id, int $usuarioId, array $data): bool {
        // Valida propriedade da categoria e local
        $cat = Categoria::findByIdAndUsuario((int) $data['categoria_id'], $usuarioId);
        $loc = Local::findByIdAndUsuario((int) $data['local_id'], $usuarioId);

        if (!$cat || !$loc) {
            throw new InvalidArgumentException('Categoria ou local selecionado não pertence ao seu usuário.');
        }

        $validade = !empty($data['data_validade']) ? $data['data_validade'] : null;

        $stmt = db()->prepare("
            UPDATE `produtos` SET
                `categoria_id`      = :cid,
                `local_id`          = :lid,
                `nome`              = :nome,
                `quantidade`        = :qtd,
                `unidade`           = :unidade,
                `quantidade_minima` = :qtd_min,
                `data_validade`     = :validade
            WHERE `id` = :id AND `usuario_id` = :uid
        ");

        return $stmt->execute([
            ':cid'      => (int) $data['categoria_id'],
            ':lid'      => (int) $data['local_id'],
            ':nome'     => trim($data['nome']),
            ':qtd'      => (float) $data['quantidade'],
            ':unidade'  => trim($data['unidade'] ?? 'un'),
            ':qtd_min'  => (float) ($data['quantidade_minima'] ?? 1.0),
            ':validade' => $validade,
            ':id'       => $id,
            ':uid'      => $usuarioId,
        ]);
    }

    /**
     * Exclui produto garantindo propriedade do usuário
     */
    public static function delete(int $id, int $usuarioId): bool {
        $stmt = db()->prepare("DELETE FROM `produtos` WHERE `id` = :id AND `usuario_id` = :uid");
        $stmt->execute([':id' => $id, ':uid' => $usuarioId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Estatísticas agregadas calculadas para o Dashboard e filtros
     */
    public static function getStats(int $usuarioId): array {
        $produtos = self::allByUsuario($usuarioId);

        $total        = count($produtos);
        $baixo        = 0;
        $vencidos     = 0;
        $criticos     = 0;
        $atencao      = 0;
        $normais      = 0;
        $semValidade  = 0;

        foreach ($produtos as $p) {
            if ($p['is_baixo']) $baixo++;
            match($p['status_validade']['status']) {
                'vencido'      => $vencidos++,
                'critico'      => $criticos++,
                'atencao'      => $atencao++,
                'normal'       => $normais++,
                'sem_validade' => $semValidade++,
                default        => null,
            };
        }

        return [
            'total'        => $total,
            'baixo'        => $baixo,
            'vencidos'     => $vencidos,
            'criticos'     => $criticos,
            'atencao'      => $atencao,
            'normais'      => $normais,
            'sem_validade' => $semValidade,
        ];
    }
}
