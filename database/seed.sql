-- ========================================================
-- Freshy — Dados Iniciais e Demonstrativos (Seed)
-- ========================================================

USE `freshy_db`;

-- Insere Usuário Demonstrativo (senha: senha123)
INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha_hash`, `created_at`) VALUES
(1, 'Usuário Demo Freshy', 'demo@freshy.com.br', '$2y$10$YbJ1B3QmP/UR1Vhn0o.GpOtXapPOVtYTLrobXTG1DRYs4ZKB0g1/6', NOW());

-- Insere Categorias Padrão para o Usuário 1
INSERT INTO `categorias` (`id`, `usuario_id`, `nome`) VALUES
(1, 1, 'Laticínios'),
(2, 1, 'Hortifrúti'),
(3, 1, 'Mercearia'),
(4, 1, 'Padaria'),
(5, 1, 'Carnes e Aves'),
(6, 1, 'Bebidas'),
(7, 1, 'Limpeza');

-- Insere Locais de Armazenamento para o Usuário 1
INSERT INTO `locais` (`id`, `usuario_id`, `nome`) VALUES
(1, 1, 'Geladeira'),
(2, 1, 'Freezer'),
(3, 1, 'Despensa'),
(4, 1, 'Armário da Cozinha'),
(5, 1, 'Lavanderia');

-- Insere Produtos com diferentes status de validade e estoque:
-- 1. Leite Integral: Atenção (vence em 5 dias)
-- 2. Iogurte Natural: Crítico (vence em 2 dias)
-- 3. Molho de Tomate: Vencido (venceu há 3 dias)
-- 4. Arroz Branco 5kg: Estoque Baixo (1 pacote, mínimo 2)
-- 5. Café Torrado e Moído 500g: Normal (vence em 90 dias, estoque 3)
-- 6. Detergente Neutro 500ml: Sem validade obrigatória / longo prazo, estoque normal
INSERT INTO `produtos` (`id`, `usuario_id`, `categoria_id`, `local_id`, `nome`, `quantidade`, `unidade`, `quantidade_minima`, `data_validade`, `created_at`) VALUES
(1, 1, 1, 1, 'Leite Integral 1L', 4.00, 'un', 2.00, DATE_ADD(CURRENT_DATE, INTERVAL 5 DAY), NOW()),
(2, 1, 1, 1, 'Iogurte Natural 170g', 2.00, 'un', 2.00, DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY), NOW()),
(3, 1, 3, 3, 'Molho de Tomate Tradicional 340g', 1.00, 'un', 2.00, DATE_SUB(CURRENT_DATE, INTERVAL 3 DAY), NOW()),
(4, 1, 3, 3, 'Arroz Branco Tipo 1 (5kg)', 1.00, 'pct', 2.00, DATE_ADD(CURRENT_DATE, INTERVAL 180 DAY), NOW()),
(5, 1, 3, 4, 'Café Torrado e Moído 500g', 3.00, 'un', 1.00, DATE_ADD(CURRENT_DATE, INTERVAL 90 DAY), NOW()),
(6, 1, 7, 5, 'Detergente Neutro 500ml', 4.00, 'frasco', 2.00, DATE_ADD(CURRENT_DATE, INTERVAL 365 DAY), NOW());

-- Insere Movimentações Iniciais de Auditoria
INSERT INTO `movimentacoes` (`usuario_id`, `produto_id`, `tipo`, `quantidade`, `motivo`, `created_at`) VALUES
(1, 1, 'ENTRADA', 5.00, 'Cadastro inicial no estoque', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 1, 'CONSUMO', 1.00, 'Consumo da família no café da manhã', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 2, 'ENTRADA', 3.00, 'Cadastro inicial no estoque', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1, 2, 'CONSUMO', 1.00, 'Lanche da tarde', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 3, 'ENTRADA', 1.00, 'Cadastro inicial', DATE_SUB(NOW(), INTERVAL 10 DAY)),
(1, 4, 'ENTRADA', 2.00, 'Compra mensal', DATE_SUB(NOW(), INTERVAL 15 DAY)),
(1, 4, 'CONSUMO', 1.00, 'Preparo de almoço', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1, 5, 'ENTRADA', 3.00, 'Compra mensal', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1, 6, 'ENTRADA', 4.00, 'Compra mensal de limpeza', DATE_SUB(NOW(), INTERVAL 7 DAY));

-- Insere Lista de Compras Inicial do Usuário 1
INSERT INTO `listas_compras` (`id`, `usuario_id`, `created_at`) VALUES
(1, 1, NOW());

-- Insere Itens na Lista de Compras
-- Item 1 vinculado ao Arroz (estoque baixo)
-- Item 2 vinculado ao Molho de Tomate (vencido/baixo)
-- Item 3 manual (não cadastrado ainda no estoque: 'Pão de Forma')
INSERT INTO `itens_lista_compras` (`lista_id`, `produto_id`, `nome_item`, `quantidade`, `comprado`, `created_at`) VALUES
(1, 4, 'Arroz Branco Tipo 1 (5kg)', 2.00, 0, NOW()),
(1, 3, 'Molho de Tomate Tradicional 340g', 2.00, 0, NOW()),
(1, NULL, 'Pão de Forma Integral', 1.00, 1, NOW());
