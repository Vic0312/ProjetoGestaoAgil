<?php require_once __DIR__ . '/../controller/Auth.php';
$currentUser = requireRole('paciente'); ?>
<?php require_once __DIR__ . '/../controller/DadosController.php';
$d = loadPageData(basename(__FILE__), $currentUser);
require_once __DIR__ . '/componentes/componentes.php';
require_once __DIR__ . '/componentes/dados.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <link rel="stylesheet" href="../css/componentes.css">
    <link rel="stylesheet" href="../css/pagamentoConsulta.css">
    <title>Mindly | Pagamento da consulta</title>
</head>

<body>
    <?php abrirLayout('paciente', 'pagamentos', 'Pagamento da consulta', 'Confira os dados antes de confirmar.', $currentUser['nome']); ?>
    <?php $c = $d['consulta']; ?>
    <div class="checkout-grid">
        <section class="cartao pagamento-form">
            <div class="seguranca-topo"><?= icon('shield') ?>
                <div><strong>Pagamento da consulta</strong><span>Nenhuma cobrança é executada nesta tela.</span></div>
            </div><?php unavailable('Pagamento por cartão ou PIX'); ?>
            <h2>Forma de pagamento</h2>
            <div class="metodos"><button disabled><?= icon('card') ?> Cartão</button><button disabled>PIX</button></div>
            <form>
                <div class="campo"><label>Número do cartão</label><input disabled
                        placeholder="Pagamento ainda não disponível"></div>
                <div class="grade-campos">
                    <div class="campo"><label>Nome no cartão</label><input disabled></div>
                    <div class="campo"><label>CPF</label><input disabled></div>
                    <div class="campo"><label>Validade</label><input disabled></div>
                    <div class="campo"><label>CVV</label><input disabled></div>
                </div><label class="salvar"><input type="checkbox" disabled> Salvar cartão para próximos
                    pagamentos</label>
            </form>
        </section>
        <aside class="cartao resumo-checkout">
            <h2>Resumo</h2>
            <div class="prof"><span class="avatar-foto"><?= e(initials($c['psicologo_nome'] ?? '')) ?></span>
                <div>
                    <strong><?= e($c['psicologo_nome'] ?? 'Nenhuma consulta selecionada') ?></strong><small><?= e($c['area_atuacao'] ?? '') ?></small>
                </div>
            </div>
            <div class="resumo-linha"><span><?= icon('calendar') ?>
                    Data</span><strong><?= dateLabel($c['inicio_em'] ?? null, 'd/m/Y') ?></strong></div>
            <div class="resumo-linha"><span><?= icon('clock') ?>
                    Horário</span><strong><?= dateLabel($c['inicio_em'] ?? null, 'H:i') ?></strong></div>
            <div class="resumo-linha"><span>Status da
                    consulta</span><strong><?= $c ? e(statusLabel($c['status'])) : '—' ?></strong></div>
            <div class="linha-divisoria"></div>
            <div class="total"><span>Total da consulta</span><strong><?= $c ? money($c['valor']) : '—' ?></strong></div>
            <button class="botao botao-principal confirmar" disabled>Confirmar pagamento</button><a
                href="pagamentosPaciente.php">Voltar para pagamentos</a>
        </aside>
    </div>
    <?php fecharLayout(); ?>
</body>

</html>