<?php
/**
 * Freshy — Suíte de Testes de Integração Automatizada
 * Valida todos os Requisitos Funcionais, Regras de Negócio e Segurança
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';

echo "====================================================\n";
echo " FRESHY — SUÍTE DE TESTES DE INTEGRAÇÃO & SEGURANÇA \n";
echo "====================================================\n\n";

$passou = 0;
$total = 0;

function assertTest(string $descricao, bool $condicao): void {
    global $passou, $total;
    $total++;
    if ($condicao) {
        $passou++;
        echo " [OK] {$descricao}\n";
    } else {
        echo " [FALHA] {$descricao}\n";
    }
}

// 1. Teste de Conexão PDO
assertTest('Conexão PDO ativa e respondendo', db() instanceof PDO);

// 2. Teste de Autenticação do Usuário Demo
$demo = Usuario::autenticar('demo@freshy.com.br', 'senha123');
assertTest('Autenticação com e-mail e senha corretos (password_verify)', $demo !== null && $demo['email'] === 'demo@freshy.com.br');

$demoErrado = Usuario::autenticar('demo@freshy.com.br', 'senha_errada_xyz');
assertTest('Rejeição de login com senha incorreta', $demoErrado === null);

// 3. Teste de Criação de Novo Usuário com Categorias e Locais Padrão
$emailTeste = 'testador_' . time() . '@freshy.com.br';
$novoUserId = Usuario::create('Testador Automatizado', $emailTeste, 'senhaForte123');
assertTest('Criação de novo usuário com hash BCRYPT', $novoUserId > 0);

$catsNovo = Categoria::allByUsuario($novoUserId);
assertTest('Inicialização automática de categorias padrão para novo usuário', count($catsNovo) >= 5);

$locsNovo = Local::allByUsuario($novoUserId);
assertTest('Inicialização automática de locais padrão para novo usuário', count($locsNovo) >= 3);

// 4. Teste de Regras de Validade Centralizadas
$statusVencido = calcular_status_validade(date('Y-m-d', strtotime('-2 days')));
assertTest("Cálculo de produto vencido (status = 'vencido')", $statusVencido['status'] === 'vencido');

$statusCritico = calcular_status_validade(date('Y-m-d', strtotime('+1 day')));
assertTest("Cálculo de produto crítico 0-3 dias (status = 'critico')", $statusCritico['status'] === 'critico');

$statusAtencao = calcular_status_validade(date('Y-m-d', strtotime('+5 days')));
assertTest("Cálculo de produto atenção 4-7 dias (status = 'atencao')", $statusAtencao['status'] === 'atencao');

$statusNormal = calcular_status_validade(date('Y-m-d', strtotime('+30 days')));
assertTest("Cálculo de produto normal > 7 dias (status = 'normal')", $statusNormal['status'] === 'normal');

// 5. Teste MyStock: Cadastro de Produto com Movimentação Inicial de ENTRADA
$catId = (int) $catsNovo[0]['id'];
$locId = (int) $locsNovo[0]['id'];
$prodId = Produto::create($novoUserId, [
    'categoria_id'      => $catId,
    'local_id'          => $locId,
    'nome'              => 'Iogurte Grego Frutas Vermelhas',
    'quantidade'        => 5.0,
    'unidade'           => 'un',
    'quantidade_minima' => 2.0,
    'data_validade'     => date('Y-m-d', strtotime('+2 days')),
]);
assertTest('Cadastro de produto no MyStock com chave estrangeira válida', $prodId > 0);

$prodCadastrado = Produto::findByIdAndUsuario($prodId, $novoUserId);
assertTest('Recuperação de produto cadastrado pertencente ao usuário', $prodCadastrado !== null && (float)$prodCadastrado['quantidade'] === 5.0);

$movsNovo = Movimentacao::allByUsuario($novoUserId);
assertTest('Registro automático de movimentação de ENTRADA no histórico', count($movsNovo) >= 1 && $movsNovo[0]['tipo'] === 'ENTRADA');

// 6. Teste de Consumo com Transação Atômica e Atualização do Estoque
$resConsumo = Movimentacao::registrarConsumo($novoUserId, $prodId, 2.0, 'Café da manhã de teste');
assertTest('Registro de consumo atômico (baixa de 2 unidades)', (float)$resConsumo['novo_estoque'] === 3.0);

$prodAposConsumo = Produto::findByIdAndUsuario($prodId, $novoUserId);
assertTest('Estoque do produto persistido corretamente no banco após consumo', (float)$prodAposConsumo['quantidade'] === 3.0);

// 7. Teste de Proteção Rigorosa Contra Estoque Negativo
$bloqueouNegativo = false;
try {
    Movimentacao::registrarConsumo($novoUserId, $prodId, 999.0, 'Tentativa abusiva de consumo');
} catch (RuntimeException $e) {
    $bloqueouNegativo = true;
}
assertTest('Bloqueio no servidor de consumo que resulte em estoque negativo', $bloqueouNegativo);

// 8. Teste de Desperdício com Motivo Obrigatório
$resDesperdicio = Movimentacao::registrarDesperdicio($novoUserId, $prodId, 1.0, 'Estragado / mofo / azedou');
assertTest('Registro de desperdício com motivo obrigatório (baixa de 1 unidade)', (float)$resDesperdicio['novo_estoque'] === 2.0);

// 9. Teste de Estoque Baixo (quantidade <= quantidade_minima)
$prodBaixo = Produto::findByIdAndUsuario($prodId, $novoUserId);
assertTest('Identificação automática de estoque baixo (2.0 <= 2.0)', $prodBaixo['is_baixo'] === true);

// 10. Teste da Lista de Compras: Inclusão Manual e Importação de Estoque Baixo
$listaId = ListaCompras::getOrCreateListaId($novoUserId);
assertTest('Geração e recuperação de lista de compras do usuário', $listaId > 0);

$importados = ListaCompras::importarEstoqueBaixo($novoUserId);
assertTest('Importação automática de produto com estoque baixo para a lista', $importados >= 1);

$itensLista = ListaCompras::getItens($novoUserId);
assertTest('Consulta de itens na lista de compras', count($itensLista) >= 1);

$itemId = (int) $itensLista[0]['id'];
ListaCompras::alternarComprado($itemId, $novoUserId);
$itensAposToggle = ListaCompras::getItens($novoUserId);
assertTest("Marcação de item como comprado na lista (comprado = 1)", (int)$itensAposToggle[0]['comprado'] === 1);

// 11. Teste de Isolamento Completo entre Usuários Diferentes (Multi-tenant)
$uDemoTentaAcessarU2 = Produto::findByIdAndUsuario($prodId, (int)$demo['id']);
assertTest('Isolamento de dados: Usuário Demo NÃO consegue acessar produtos do Novo Usuário', $uDemoTentaAcessarU2 === null);

$catsDemo = Categoria::allByUsuario((int)$demo['id']);
$catDemoId = (int) $catsDemo[0]['id'];
$u2TentaDeletarCatDemo = Categoria::delete($catDemoId, $novoUserId);
assertTest('Isolamento de dados: Novo Usuário NÃO consegue excluir categorias do Usuário Demo', $u2TentaDeletarCatDemo === false);

echo "\n----------------------------------------------------\n";
echo " RESULTADO FINAL: {$passou} / {$total} testes passaram com êxito (" . round(($passou / $total) * 100) . "%)\n";
echo "----------------------------------------------------\n";

if ($passou === $total) {
    echo " >>> TODOS OS CRITÉRIOS DE ACEITE E REGRAS DE NEGÓCIO FORAM APROVADOS! <<<\n\n";
    exit(0);
} else {
    echo " >>> ALGUNS TESTES FALHARAM. VERIFIQUE OS LOGS. <<<\n\n";
    exit(1);
}
