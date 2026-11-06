# Changelog

Todas as alterações notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [1.0.0] - 2026-11-06
### Added
- **MyStock (`public/estoque.php`, `app/models/Produto.php`):** CRUD completo de produtos, pesquisa textual, filtros por categoria, local e situação, com badges de status automáticos.
- **Regras de Negócio Centralizadas:** Cálculo automático de estoque baixo (`quantidade <= quantidade_minima`) e classificação de validade: vencido (< hoje), crítico (0 a 3 dias), atenção (4 a 7 dias) e normal (> 7 dias).
- **Consumo e Desperdício (`public/consumo.php`, `public/desperdicio.php`, `app/models/Movimentacao.php`):** Baixas atômicas de estoque protegidas por transações PDO (`FOR UPDATE`, `beginTransaction`), bloqueio estrito de estoque negativo e registro de motivo no histórico.
- **Lista de Compras Inteligente (`public/compras.php`, `app/models/ListaCompras.php`):** Gestão de compras, itens manuais e vinculados, marcação de comprados e botão para importar produtos com estoque baixo com 1 clique.
- **Dashboard em Tempo Real (`public/dashboard.php`):** Indicadores reais do MySQL (total no estoque, itens baixos, críticos, vencidos, pendências de compras e últimas movimentações).
- **Histórico de Auditoria (`public/historico.php`):** Relatório de todas as operações (entradas, consumos e desperdícios) com filtros por tipo e produto.
- **Suíte de Testes Automatizada (`tests/test_freshy.php`):** 24 testes de integração e segurança cobrindo todos os Requisitos Funcionais e isolamento de usuários (100% de aprovação).
- **Especificação Técnica v1.0 (`spec.md`):** Revisão completa com 24 critérios de aceite reescritos no formato obrigatório `Dado que..., Quando..., Então...`, seção de Histórico de Alterações da Especificação, detalhamento do DER e instruções completas de acesso e compartilhamento do repositório no GitHub.

### Security
- Transações atômicas de banco de dados para integridade de estoque em baixas e movimentações.
- Bloqueio estrito no servidor contra saldo negativo.
- Isolamento multi-tenant rigoroso por `usuario_id` em todos os Models e Controllers.
- Prepared Statements em 100% das consultas dinâmicas.

## [0.4.0] - 2026-11-06
### Added
- Model `app/models/Categoria.php` com métodos de listagem, inserção, edição, exclusão e verificação de produtos vinculados.
- Model `app/models/Local.php` para locais de armazenamento com integridade referencial.
- Interface completa `public/categorias.php` com abas para categorias e locais, modais de edição, contadores de produtos e formulários protegidos.

### Security
- Validação estrita de autorização por `usuario_id` em todas as operações com categorias e locais.
- Proteção contra exclusão de categorias e locais em uso por produtos ativos no estoque.
- Validação de tokens CSRF em todas as mutações POST.

## [0.3.0] - 2026-11-06
### Added
- Model `app/models/Usuario.php` com métodos de autenticação, busca por e-mail, verificação de unicidade e criação de usuário com transação.
- Telas públicas de autenticação: `public/cadastro.php`, `public/login.php` e `public/logout.php`.
- Templates de layout reutilizáveis: `app/views/partials/header.php`, `navbar.php`, `alerts.php`, `footer.php`.
- Centralização das regras de negócio de validade e estoque baixo em `app/helpers/functions.php`.
- Autoloader automático para classes de Models.

### Security
- Autenticação com senhas criptografadas via `password_hash()` BCRYPT e validação com `password_verify()`.
- Regeneração forçada de identificador de sessão (`session_regenerate_id(true)`) pós-login contra session fixation.
- Proteção CSRF em todos os formulários com tokens temporários da sessão e validação em tempo constante (`hash_equals`).
- Encerramento seguro de sessão com expiração de cookies no logout.

## [0.2.0] - 2026-11-06
### Added
- Arquivo `database/schema.sql` com as 7 tabelas relacionais do sistema e foreign keys com integridade referencial.
- Arquivo `database/seed.sql` com usuário demonstrativo, categorias, locais e produtos de teste em diferentes situações.
- Conexão PDO segura e singleton em `app/config/database.php` com `ATTR_EMULATE_PREPARES => false` e `ATTR_ERRMODE => ERRMODE_EXCEPTION`.
- Script CLI de migração automatizada `database/migrate.php`.

### Security
- Conexão PDO com prepared statements nativos ativos.
- Credenciais isoladas via variáveis de ambiente.

## [0.1.0] - 2026-10-23
### Added
- Estrutura inicial do projeto com pastas `public/`, `app/` (config, controllers, models, helpers, views) e `database/`.
- Arquivo `.gitignore` para proteção de credenciais e arquivos temporários.
- Arquivo `.env.example` com template seguro para variáveis de ambiente.
- Arquivo `README.md` com instruções completas de configuração, execução local e arquitetura.
- Arquivo de especificação `spec.md` preservado e integrado à documentação.
- Ponto de entrada inicial em `public/index.php`.
- Carregador de configurações e ambiente em `app/config/config.php`.
- Funções essenciais de segurança e sanitização em `app/helpers/functions.php`.

### Security
- Isolamento de credenciais via `.env` protegido pelo `.gitignore`.
- Configuração de diretivas seguras de sessão (`cookie_httponly`, `use_only_cookies`, `cookie_samesite`).
- Funções base para escape XSS (`htmlspecialchars`) e geração/validação de tokens CSRF.

### Rejected
- Frameworks pesados de backend (Laravel/Symfony) foram rejeitados para manter o projeto leve, compatível com qualquer hospedagem tradicional PHP 8.2+ e sem complexidade desnecessária.
- Notificações por WhatsApp e integração com Alexa foram consideradas, mas rejeitadas no MVP por aumentarem a complexidade fora do fluxo principal.
