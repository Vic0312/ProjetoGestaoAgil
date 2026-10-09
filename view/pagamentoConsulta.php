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
                <div><strong>Pagamento simulado</strong><span>Projeto acadêmico: nenhum dinheiro será cobrado.</span></div>
            </div>
            <?php if ($c): ?>
                <h2><?= e($d['financeiro']['status']) ?></h2>
                <p>Saldo a pagar: <?= money($d['financeiro']['saldo']) ?>.</p>
                <?php if ($d['financeiro']['pode_pagar']): ?>
                    <form method="post" action="../processamento/paciente.php" class="form-operacao">
                        <?php operationFields('pagar_consulta'); ?>
                        <input type="hidden" name="consulta_id" value="<?= (int) $c['id'] ?>">
                        <label><input type="checkbox" name="confirmacao" value="1" required> Confirmo o pagamento simulado desta consulta.</label>
                        <button class="botao botao-principal confirmar">Confirmar pagamento simulado</button>
                    </form>
                <?php else: emptyState('Esta consulta não permite novo pagamento. Consultas canceladas, vencidas, pagas ou em processamento não podem ser pagas aqui.'); endif; ?>
            <?php else: emptyState('Selecione uma consulta na área de pagamentos.'); endif; ?>
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
            <a
                href="pagamentosPaciente.php">Voltar para pagamentos</a>
        </aside>
    </div>
    <?php fecharLayout(); ?>
</body>

</html>