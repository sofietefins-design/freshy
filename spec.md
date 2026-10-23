# Freshy — Seu estoque doméstico, mais inteligente

**Documento:** Especificação inicial do sistema (`spec.md`)  
**Versão:** 0.1  
**Status:** Planejamento inicial  
**Escopo:** Aplicação web para controle de estoque doméstico

## 1. Objetivo

O Freshy é um sistema web para ajudar pessoas a organizar os produtos que possuem em casa, acompanhar quantidades e datas de validade e planejar reposições. O sistema reúne em um só lugar o cadastro e a consulta de produtos, os registros de consumo e desperdício e uma lista de compras, com o objetivo de facilitar a organização doméstica e ajudar a reduzir compras desnecessárias e desperdício.

## 2. Usuários

- **Visitante não autenticado:** pode acessar as telas públicas de cadastro e login. Não pode consultar nem alterar dados do estoque.
- **Usuário cadastrado e autenticado:** pode gerenciar apenas os próprios dados: cadastrar e consultar produtos, editar e excluir produtos próprios, organizar categorias e locais de armazenamento, registrar consumo e desperdício, consultar o histórico e gerenciar a própria lista de compras.

Na versão inicial não haverá perfil de administrador nem compartilhamento de estoque entre diferentes contas. O servidor deverá verificar a propriedade dos registros em toda operação protegida.

## 3. Casos de uso principais

1. **Criar conta:** visitante informa nome, e-mail e senha; o sistema valida os dados e cria a conta.
2. **Entrar e sair:** usuário informa e-mail e senha válidos para iniciar uma sessão e pode encerrá-la pelo logout.
3. **Cadastrar produto:** usuário autenticado informa nome, categoria, local, quantidade, unidade, quantidade mínima e, quando aplicável, data de validade; o sistema valida e grava o produto no banco.
4. **Consultar e organizar estoque:** usuário visualiza os próprios produtos, pesquisa por nome e filtra por categoria, local ou situação de estoque/validade.
5. **Editar ou excluir produto:** usuário altera os dados de um produto próprio ou solicita sua exclusão, com confirmação antes da operação destrutiva.
6. **Registrar consumo:** usuário seleciona um produto e uma quantidade; o sistema verifica se há quantidade suficiente, atualiza o estoque e grava uma movimentação.
7. **Registrar desperdício:** usuário informa produto, quantidade e motivo; o sistema atualiza o estoque e registra a movimentação.
8. **Gerenciar lista de compras:** usuário adiciona itens à lista, informa a quantidade desejada, marca itens como comprados e remove itens.
9. **Consultar alertas e histórico:** usuário visualiza produtos com estoque baixo, próximos da validade ou vencidos, além das movimentações registradas.

## 4. Requisitos funcionais (RF)

- **RF01 — Cadastro de usuário:** o sistema deve permitir a criação de conta com nome, e-mail e senha, impedindo o cadastro de e-mail já utilizado.
- **RF02 — Login e logout:** o sistema deve autenticar usuários com e-mail e senha e permitir encerrar a sessão.
- **RF03 — Proteção de páginas:** o sistema deve exigir autenticação para páginas e ações privadas.
- **RF04 — Categorias:** o usuário deve poder cadastrar, consultar, editar e excluir suas categorias, respeitando vínculos existentes com produtos.
- **RF05 — Locais de armazenamento:** o usuário deve poder cadastrar, consultar, editar e excluir locais, como despensa, geladeira e freezer, respeitando vínculos existentes.
- **RF06 — Cadastro de produtos:** o usuário deve poder cadastrar produtos com nome, categoria, local, quantidade, unidade, quantidade mínima e data de validade opcional.
- **RF07 — Consulta e pesquisa:** o sistema deve listar apenas os produtos do usuário autenticado e permitir pesquisar por nome e filtrar por categoria, local e situação.
- **RF08 — Edição e exclusão de produtos:** o usuário deve poder editar e excluir apenas produtos pertencentes à sua conta; a exclusão deve solicitar confirmação.
- **RF09 — Situação do estoque:** o sistema deve identificar estoque baixo quando a quantidade atual for menor ou igual à quantidade mínima configurada.
- **RF10 — Situação da validade:** o sistema deve identificar produtos vencidos e produtos próximos da validade com base na data atual e nos intervalos definidos nas regras de negócio.
- **RF11 — Registro de consumo:** o usuário deve poder registrar o consumo de uma quantidade positiva de um produto; a quantidade não pode exceder o estoque disponível.
- **RF12 — Registro de desperdício:** o usuário deve poder registrar desperdício com quantidade positiva e motivo; a quantidade não pode exceder o estoque disponível.
- **RF13 — Histórico de movimentações:** o sistema deve registrar e permitir consultar entradas iniciais, consumos e desperdícios, com produto, tipo, quantidade, data e motivo quando aplicável.
- **RF14 — Lista de compras:** o usuário deve poder adicionar itens, informar quantidade, consultar a lista, marcar itens como comprados e remover itens.
- **RF15 — Inclusão na lista a partir do estoque:** o usuário deve poder enviar um produto com estoque baixo para a lista de compras.
- **RF16 — Dashboard:** o sistema deve apresentar dados reais do banco, incluindo quantidade de produtos cadastrados, itens com estoque baixo, produtos próximos da validade e produtos vencidos.
- **RF17 — Segurança de propriedade:** toda consulta, edição, exclusão ou movimentação deverá verificar no servidor se o registro pertence ao usuário autenticado.

## 5. Requisitos não funcionais (RNF)

- **RNF01 — Senhas:** senhas nunca devem ser armazenadas em texto puro. O sistema deve usar `password_hash()` para armazená-las e `password_verify()` para verificá-las.
- **RNF02 — Banco de dados:** o acesso ao MySQL deve usar PDO e prepared statements para consultas com dados fornecidos pelo usuário.
- **RNF03 — Validação no servidor:** todos os formulários devem ser validados no PHP, mesmo quando houver validação no navegador. Quantidades devem ser numéricas e não negativas; operações de consumo e desperdício exigem quantidade positiva.
- **RNF04 — Proteção contra XSS:** valores inseridos pelo usuário devem ser escapados na saída HTML, por exemplo com `htmlspecialchars()`.
- **RNF05 — Proteção CSRF:** formulários que criam, alteram ou excluem dados devem validar token CSRF no servidor.
- **RNF06 — Sessões:** após autenticação, o identificador da sessão deve ser regenerado; cookies de sessão devem usar configurações seguras compatíveis com HTTPS; o logout deve invalidar a sessão.
- **RNF07 — Autorização:** o servidor deve validar a propriedade dos dados em cada operação. Não basta ocultar botões na interface.
- **RNF08 — Credenciais:** credenciais do banco e outros segredos devem ser fornecidos por variáveis de ambiente ou configuração equivalente fora do controle de versão. O repositório deve conter apenas um `.env.example` sem segredos reais.
- **RNF09 — Erros:** mensagens apresentadas ao usuário não devem revelar senhas, consultas SQL, caminhos internos ou detalhes sensíveis do servidor. Detalhes técnicos podem ser registrados em logs protegidos.
- **RNF10 — Responsividade:** as telas devem funcionar em navegadores modernos de computadores e celulares, com formulários e tabelas adaptáveis a telas menores.
- **RNF11 — Integridade:** o sistema não deve permitir estoque negativo. Atualização do estoque e gravação da movimentação devem ocorrer na mesma transação de banco de dados.
- **RNF12 — Usabilidade:** o sistema deve apresentar mensagens claras de sucesso, validação e erro, além de confirmação para exclusões.
- **RNF13 — Manutenibilidade:** o código deve ser organizado em arquivos com responsabilidades claras e acompanhado de `README.md`, `spec.md` e `CHANGELOG.md`.
- **RNF14 — Compatibilidade:** o sistema deve usar PHP 8.2 ou superior e MySQL 8.0 ou versão compatível com os recursos utilizados.

## 6. Modelo de dados (DER simples)

### Tabelas principais

- **usuarios** (`id` PK, `nome`, `email` UNIQUE, `senha_hash`, `created_at`)
- **categorias** (`id` PK, `usuario_id` FK → `usuarios.id`, `nome`, `created_at`)
- **locais** (`id` PK, `usuario_id` FK → `usuarios.id`, `nome`, `created_at`)
- **produtos** (`id` PK, `usuario_id` FK → `usuarios.id`, `categoria_id` FK → `categorias.id`, `local_id` FK → `locais.id`, `nome`, `quantidade`, `unidade`, `quantidade_minima`, `data_validade` NULL, `created_at`, `updated_at`)
- **movimentacoes** (`id` PK, `usuario_id` FK → `usuarios.id`, `produto_id` FK → `produtos.id`, `tipo`, `quantidade`, `motivo` NULL, `created_at`)
- **listas_compras** (`id` PK, `usuario_id` FK → `usuarios.id`, `created_at`, `updated_at`)
- **itens_lista_compras** (`id` PK, `lista_id` FK → `listas_compras.id`, `produto_id` FK → `produtos.id` NULL, `nome_item`, `quantidade`, `comprado`, `created_at`)

`produto_id` em `itens_lista_compras` poderá ser nulo para permitir itens adicionados manualmente que ainda não estejam cadastrados no estoque. Quando o item estiver vinculado a um produto, o servidor deve confirmar que ambos pertencem ao mesmo usuário.

### Relacionamentos

- Um usuário possui várias categorias, locais, produtos, movimentações e listas de compras.
- Uma categoria pode estar associada a vários produtos; cada produto pertence a uma categoria do mesmo usuário.
- Um local pode estar associado a vários produtos; cada produto pertence a um local do mesmo usuário.
- Um produto pode ter várias movimentações.
- Uma lista de compras possui vários itens.
- Um item da lista pode estar vinculado a um produto cadastrado ou ser um item manual.

### Regras de integridade

As relações devem ser implementadas com chaves estrangeiras. O banco deve impedir vínculos entre registros de usuários diferentes. Exclusões de categorias ou locais em uso devem ser bloqueadas ou exigir que os produtos relacionados sejam reassociados primeiro. Movimentações históricas não devem ser apagadas automaticamente ao editar a quantidade atual.

## 7. Critérios de aceite

- **CA01 — Cadastro:** dado que o visitante informa nome, e-mail válido e senha que atende à política definida, quando envia o formulário, então a conta deve ser criada e a senha deve ser armazenada somente como hash. Se o e-mail já existir, o sistema deve apresentar uma mensagem e não criar outra conta.
- **CA02 — Login:** dado que uma conta existe, quando o usuário informa credenciais corretas, então deve entrar no sistema; com credenciais incorretas, deve receber uma mensagem genérica e continuar sem acesso.
- **CA03 — Páginas protegidas:** dado que a pessoa não está autenticada, quando tenta abrir uma página privada, então o sistema deve impedir o acesso e encaminhá-la ao login.
- **CA04 — Cadastro de produto:** dado que o usuário está autenticado e informa dados válidos, quando salva um produto, então ele deve ser gravado no MySQL e aparecer na consulta de estoque.
- **CA05 — Isolamento de dados:** dado que existem duas contas, quando cada usuário consulta ou tenta alterar um produto da outra conta, então o sistema não deve revelar nem permitir alterar esse registro.
- **CA06 — Edição e exclusão:** dado que um produto pertence ao usuário, quando ele salva uma edição válida, então os dados devem ser atualizados; quando solicita exclusão e confirma, então o produto deve ser removido conforme as regras de integridade. Sem confirmação, a exclusão não deve ocorrer.
- **CA07 — Estoque baixo:** dado que a quantidade de um produto é menor ou igual à quantidade mínima, quando o estoque é consultado, então o produto deve aparecer identificado como estoque baixo.
- **CA08 — Validade:** dado que um produto tem data de validade cadastrada, quando o sistema calcula sua situação, então deve identificá-lo como vencido se a data for anterior à data atual e como próximo da validade se estiver dentro do intervalo configurado.
- **CA09 — Consumo:** dado que há estoque suficiente, quando o usuário registra um consumo válido, então a quantidade deve diminuir e uma movimentação de consumo deve ser gravada. Se a quantidade solicitada exceder o estoque, nenhuma alteração deve ser realizada.
- **CA10 — Desperdício:** dado que há estoque suficiente, quando o usuário registra desperdício com quantidade e motivo válidos, então a quantidade deve diminuir e a movimentação de desperdício deve ser gravada.
- **CA11 — Histórico:** dado que existem movimentações, quando o usuário abre o histórico, então deve ver apenas as movimentações de sua conta com tipo, quantidade e data.
- **CA12 — Lista de compras:** dado que o usuário adiciona um item à lista, quando consulta a lista, então o item deve aparecer; quando o marca como comprado, o estado deve ser salvo no banco.
- **CA13 — Dashboard:** dado que existem produtos com diferentes quantidades e validades, quando o usuário abre o dashboard, então os indicadores devem refletir os dados atuais do banco, e não valores fixos de demonstração.
- **CA14 — Segurança de formulários:** dado que uma requisição de alteração não contém token CSRF válido ou contém dados inválidos, quando chega ao servidor, então a operação deve ser rejeitada sem alterar os dados.
- **CA15 — Layout responsivo:** dado que o sistema é aberto em computador ou celular, quando o usuário navega pelas telas principais, então os conteúdos e formulários devem permanecer utilizáveis sem sobreposição que impeça as ações.

## 8. Decisão de stack e deploy

### Stack escolhida

- **Backend:** PHP 8.2 ou superior, com páginas HTML renderizadas pelo PHP.
- **Banco de dados:** MySQL 8.0 ou compatível.
- **Acesso ao banco:** PDO com prepared statements.
- **Frontend:** HTML5, CSS3 e JavaScript para interações necessárias.
- **Biblioteca visual:** Bootstrap 5 para acelerar a criação de telas responsivas e consistentes.
- **Gráficos:** Chart.js, caso os gráficos previstos sejam implementados após as funcionalidades essenciais.
- **Versionamento:** Git e repositório GitHub.
- **Configuração:** variáveis de ambiente para credenciais e configurações específicas do ambiente.

### Estratégia de deploy

O sistema será publicado em uma hospedagem web que ofereça suporte a PHP 8.2 ou superior, MySQL e HTTPS, pois essa opção é compatível com PHP tradicional e permite ao professor acessar o sistema por uma URL sem instalar ferramentas. O banco e as credenciais de produção serão configurados diretamente no ambiente de hospedagem, nunca no repositório. A escolha do provedor deverá considerar disponibilidade de PHP/MySQL, HTTPS, configuração de variáveis de ambiente ou arquivo de configuração protegido e acesso ao banco.

O desenvolvimento e os testes iniciais serão realizados localmente. Antes da entrega, serão testados cadastro, login, operações de estoque, isolamento de dados entre contas e acesso pela URL publicada.

### Escopo e limitações da versão inicial

A primeira versão será voltada ao uso doméstico, com contas individuais. Não incluirá, inicialmente, integração com supermercados, leitura de código de barras, notificações por WhatsApp/e-mail, inteligência artificial, aplicativo móvel nativo ou gestão comercial. Essas possibilidades poderão ser avaliadas futuramente, sem fazer parte dos critérios de conclusão desta versão.
