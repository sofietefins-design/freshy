# Freshy — Seu estoque doméstico, mais inteligente 🍃

O **Freshy** é um sistema web funcional de controle de estoque doméstico desenvolvido para ajudar pessoas e famílias a organizarem os produtos que possuem em casa, acompanharem quantidades e datas de validade, identificarem produtos próximos do vencimento ou com estoque baixo, registrarem consumo e desperdício reais e manterem uma lista de compras inteligente.

Projeto acadêmico focado em funcionamento real, boas práticas de segurança, integridade relacional, código limpo e histórico de desenvolvimento transparente e incremental.

---

## 🚀 Funcionalidades Principais

- **Autenticação Segura:** Cadastro de usuários e login com senhas criptografadas (`password_hash` / `password_verify`), sessões seguras e proteção contra CSRF.
- **Isolamento Completo de Dados:** Cada usuário acessa e gerencia exclusivamente seus próprios registros no banco de dados.
- **Categorias e Locais:** Organização personalizada de itens por categorias (ex: Laticínios, Mercearia, Limpeza) e locais de armazenamento (ex: Geladeira, Despensa, Freezer).
- **MyStock (Estoque Doméstico):** CRUD completo de produtos com controle de quantidade, unidade de medida, quantidade mínima de segurança e data de validade.
- **Cálculo Inteligente de Validade e Estoque:**
  - Alerta de **Estoque Baixo** automático quando `quantidade <= quantidade_minima`.
  - Classificação de validade: **Vencido**, **Crítico** (0 a 3 dias), **Atenção** (4 a 7 dias) e **Normal** (> 7 dias).
- **Registro Real de Consumo:** Baixa de itens com validação rigorosa (sem permitir estoque negativo) e registro automático na tabela de movimentações em transação atômica.
- **Controle de Desperdício:** Registro do motivo do descarte (vencimento, estrago, etc.) para apoiar a redução do desperdício de alimentos.
- **Lista de Compras Inteligente:** Adição rápida de itens com estoque baixo para a lista, inclusão manual de produtos e marcação de itens já comprados.
- **Dashboard com Métricas Reais:** Indicadores dinâmicos calculados a partir dos dados reais do MySQL (produtos totais, vencidos, baixos e movimentações recentes).
- **Histórico de Movimentações:** Auditoria de todas as entradas, consumos e desperdícios por usuário.

---

## 🛠️ Tecnologias Utilizadas

- **Backend:** PHP 8.2 ou superior (arquitetura modular MVC/Service sem dependência de frameworks pesados).
- **Banco de Dados:** MySQL 8.0+ utilizando extensão `PDO` com *Prepared Statements* em 100% das consultas dinâmicas.
- **Frontend:** HTML5, CSS3 moderno com Bootstrap 5 e JavaScript vanilla para interações dinâmicas.
- **Controle de Versão:** Git e GitHub com histórico de commits semânticos por etapa.

---

## 📁 Estrutura de Pastas

```text
freshy/
├── public/                     # Diretório público acessível pelo servidor web
│   ├── index.php               # Ponto de entrada da aplicação
│   ├── login.php               # Tela e processamento de login
│   ├── cadastro.php            # Tela e processamento de cadastro
│   ├── dashboard.php           # Painel principal com métricas
│   ├── estoque.php             # Gerenciamento de estoque (MyStock)
│   ├── compras.php             # Lista de compras
│   ├── historico.php           # Histórico de movimentações
│   ├── categorias.php          # Gestão de categorias e locais
│   └── assets/                 # Recursos estáticos
│       ├── css/style.css
│       ├── js/app.js
│       └── img/
├── app/                        # Código-fonte da aplicação (fora da raiz pública)
│   ├── config/                 # Configurações globais e carregador do .env
│   │   ├── config.php
│   │   └── database.php
│   ├── controllers/            # Controladores de regras de negócio
│   ├── models/                 # Modelos de acesso a dados (PDO)
│   ├── helpers/                # Funções de sanitização, CSRF e sessão
│   │   └── functions.php
│   └── views/                  # Templates e componentes de visualização
│       └── partials/
├── database/                   # Scripts SQL versionados
│   ├── schema.sql              # Estrutura de tabelas e chaves estrangeiras
│   └── seed.sql                # Dados iniciais para testes
├── .env.example                # Modelo de variáveis de ambiente
├── .gitignore                  # Arquivos ignorados pelo Git (inclui .env)
├── README.md                   # Este arquivo de documentação
├── spec.md                     # Especificação detalhada de requisitos e DER
└── CHANGELOG.md                # Registro incremental de alterações
```

---

## ⚙️ Requisitos e Configuração Local

### Requisitos Mínimos
- **Servidor Web:** Apache (incluso no XAMPP) ou PHP Built-in Server.
- **PHP:** Versão 8.2 ou superior com extensões `pdo_mysql`, `mbstring`, `openssl`.
- **MySQL:** Versão 8.0 ou compatível.

### Passo a Passo de Instalação

1. **Clonar o repositório:**
   ```bash
   git clone https://github.com/sofietefins-design/freshy.git
   cd freshy
   ```

2. **Configurar as Variáveis de Ambiente:**
   Copie o arquivo `.env.example` para `.env`:
   ```bash
   cp .env.example .env
   ```
   Edite o `.env` ajustando suas credenciais do MySQL:
   ```env
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=freshy_db
   DB_USER=root
   DB_PASS=sua_senha
   ```

3. **Criar o Banco de Dados e Executar Migrações:**
   Você pode rodar a migração automatizada via CLI:
   ```bash
   php database/migrate.php
   ```
   Ou executar manualmente no MySQL / phpMyAdmin:
   ```sql
   CREATE DATABASE freshy_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
   ```bash
   mysql -u root -p freshy_db < database/schema.sql
   mysql -u root -p freshy_db < database/seed.sql
   ```

4. **Executar a Aplicação:**
   - **Opção A (XAMPP):** Mantenha o projeto dentro de `C:\xampp\htdocs\sofial\Freshy`, inicie os serviços Apache e MySQL no XAMPP Control Panel e acesse via navegador em:
     **`http://localhost/sofial/Freshy/public/`**
   - **Opção B (Servidor embutido do PHP):**
     ```bash
     php -S localhost:8000 -t public
     ```
     Acesse no navegador: `http://localhost:8000`

---

## 🎓 Acesso para Avaliação Acadêmica (Demonstração)

Para facilitar a avaliação imediata sem necessidade de cadastrar novos itens manualmente, o banco possui um usuário e produtos de exemplo previamente configurados em diferentes cenários (vencido, crítico, atenção e estoque baixo):

- **URL de Login:** `http://localhost/sofial/Freshy/public/login.php`
- **E-mail:** `demo@freshy.com.br`
- **Senha:** `senha123`

---

## 🧪 Suíte de Testes de Integração & Segurança

O Freshy inclui uma suíte completa de testes automatizados cobrindo os 17 Requisitos Funcionais, isolamento multi-tenant entre usuários, integridade de transações PDO e validações contra estoque negativo.

Para executar os testes:
```bash
php tests/test_freshy.php
```

---

## 🔒 Segurança Implementada

- Senhas tratadas exclusivamente com `password_hash()` (algoritmo BCRYPT) e validadas com `password_verify()`.
- Prevenção total contra SQL Injection via **PDO Prepared Statements** nativos (`ATTR_EMULATE_PREPARES => false`).
- Prevenção contra Cross-Site Scripting (XSS) via escape consistente de saída com `htmlspecialchars()`.
- Proteção contra Cross-Site Request Forgery (CSRF) com tokens dinâmicos por formulário/sessão validados com `hash_equals()`.
- Regeneração periódica de identificador de sessão (`session_regenerate_id(true)`) pós-login contra Session Fixation.
- Validação estrita de autorização em todas as operações: usuário só manipula dados vinculados ao seu próprio `usuario_id`.
- Operações de Consumo e Desperdício blindadas por **Transações Atômicas de Banco** (`beginTransaction`, `commit`, `rollBack`).

---

## 📄 Licença e Uso Acadêmico

Projeto desenvolvido para fins acadêmicos e educacionais.  
Desenvolvido por **Sofia F.** (sofietefins-design).
