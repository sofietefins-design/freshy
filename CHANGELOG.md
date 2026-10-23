# Changelog

Todas as alterações notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

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
