-- ========================================================
-- Freshy — Seu estoque doméstico, mais inteligente
-- Schema do Banco de Dados Relacional MySQL 8.0+
-- ========================================================

CREATE DATABASE IF NOT EXISTS `freshy_db`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `freshy_db`;

-- Desabilita checagem temporária de foreign keys para criação limpa
SET FOREIGN_KEY_CHECKS = 0;

-- 1. TABELA DE USUÁRIOS
DROP TABLE IF EXISTS `itens_lista_compras`;
DROP TABLE IF EXISTS `listas_compras`;
DROP TABLE IF EXISTS `movimentacoes`;
DROP TABLE IF EXISTS `produtos`;
DROP TABLE IF EXISTS `locais`;
DROP TABLE IF EXISTS `categorias`;
DROP TABLE IF EXISTS `usuarios`;

CREATE TABLE `usuarios` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(150) NOT NULL,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `senha_hash` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_usuarios_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABELA DE CATEGORIAS
CREATE TABLE `categorias` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT UNSIGNED NOT NULL,
    `nome` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_categoria_usuario_nome` (`usuario_id`, `nome`),
    INDEX `idx_categorias_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABELA DE LOCAIS DE ARMAZENAMENTO
CREATE TABLE `locais` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT UNSIGNED NOT NULL,
    `nome` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_local_usuario_nome` (`usuario_id`, `nome`),
    INDEX `idx_locais_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABELA DE PRODUTOS (MYSTOCK)
CREATE TABLE `produtos` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT UNSIGNED NOT NULL,
    `categoria_id` INT UNSIGNED NOT NULL,
    `local_id` INT UNSIGNED NOT NULL,
    `nome` VARCHAR(150) NOT NULL,
    `quantidade` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `unidade` VARCHAR(20) NOT NULL DEFAULT 'un',
    `quantidade_minima` DECIMAL(10, 2) NOT NULL DEFAULT 1.00,
    `data_validade` DATE NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`local_id`) REFERENCES `locais`(`id`) ON DELETE RESTRICT,
    INDEX `idx_produtos_usuario` (`usuario_id`),
    INDEX `idx_produtos_validade` (`data_validade`),
    INDEX `idx_produtos_categoria` (`categoria_id`),
    INDEX `idx_produtos_local` (`local_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABELA DE MOVIMENTAÇÕES (AUDITORIA, CONSUMO E DESPERDÍCIO)
CREATE TABLE `movimentacoes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT UNSIGNED NOT NULL,
    `produto_id` INT UNSIGNED NOT NULL,
    `tipo` ENUM('ENTRADA', 'CONSUMO', 'DESPERDICIO') NOT NULL,
    `quantidade` DECIMAL(10, 2) NOT NULL,
    `motivo` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE CASCADE,
    INDEX `idx_movimentacoes_usuario` (`usuario_id`),
    INDEX `idx_movimentacoes_produto` (`produto_id`),
    INDEX `idx_movimentacoes_tipo` (`tipo`),
    INDEX `idx_movimentacoes_data` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. TABELA DE LISTAS DE COMPRAS
CREATE TABLE `listas_compras` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
    INDEX `idx_listas_compras_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. TABELA DE ITENS DA LISTA DE COMPRAS
CREATE TABLE `itens_lista_compras` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `lista_id` INT UNSIGNED NOT NULL,
    `produto_id` INT UNSIGNED NULL,
    `nome_item` VARCHAR(150) NOT NULL,
    `quantidade` DECIMAL(10, 2) NOT NULL DEFAULT 1.00,
    `comprado` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`lista_id`) REFERENCES `listas_compras`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE SET NULL,
    INDEX `idx_itens_lista` (`lista_id`),
    INDEX `idx_itens_produto` (`produto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
