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
    <link rel="stylesheet" href="../css/pagamentosPaciente.css">
    <title>Mindly | Pagamentos</title>
</head>

<body>
    <?php abrirLayout('paciente', 'pagamentos', 'Pagamentos', 'Acompanhe cobranças, recibos e forma de pagamento.', $currentUser['nome']); ?>
    <div class="pagamento-topo">
        <article class="cartao saldo-card"><span class="icone-card"><?= icon('wallet') ?></span>
            <div><span>Próxima
                    consulta</span><strong><?= $d['proxima'] ? money($d['proxima']['valor']) : '—' ?></strong><small><?= dateLabel($d['proxima']['inicio_em'] ?? null) ?></small>
            </div><?php if ($d['proxima']): ?><a class="botao botao-principal"
                    href="<?= e(publicLink('pagamentoConsulta.php', $d['proxima']['id'], 'consulta_id')) ?>">Ver
                    cobrança</a><?php else:
                emptyState('Você ainda não possui consultas agendadas.'); endif; ?>
        </article>
        <article class="cartao metodo">
            <div class="cartao-cabecalho">
                <div>
                    <h2>Forma de pagamento</h2>
                    <p>Métodos cadastrados</p>
                </div><button class="editar" disabled title="Edição ainda não disponível"><?= icon('edit') ?></button>
            </div><?php foreach ($d['metodos'] as $m): ?>
                <div class="cartao-visual">
                    <span><?= $m['ultimos_quatro'] ? '•••• •••• •••• ' . e($m['ultimos_quatro']) : 'Final não informado' ?></span><small><?= e($m['bandeira'] ?? 'Bandeira não informada') ?>
                        ·
                        <?= $m['mes_validade'] && $m['ano_validade'] ? sprintf('%02d/%04d', $m['mes_validade'], $m['ano_validade']) : 'Validade não informada' ?></small>
                </div><?php endforeach;
            if (!$d['metodos'])
                emptyState('Nenhuma forma de pagamento cadastrada.'); ?>
        </article>
    </div>
    <section class="cartao historico-pagamentos">
        <div class="cartao-cabecalho">
            <div>
                <h2>Histórico de pagamentos</h2>
                <p>Consulte os pagamentos das suas consultas.</p>
            </div><select disabled>
                <option>Todos os registros</option>
            </select>
        </div>
        <div class="tabela">
            <div class="tr cab">
                <span>Consulta</span><span>Profissional</span><span>Valor</span><span>Status</span><span>Recibo</span>
            </div><?php foreach ($d['pagamentos'] as $p): ?>
                <div class="tr">
                    <span><strong><?= dateLabel($p['inicio_em'], 'd/m/Y') ?></strong><small><?= dateLabel($p['inicio_em'], 'H:i') ?></small></span><span><?= e($p['psicologo_nome']) ?></span><span><?= $p['moeda'] === 'BRL' ? money($p['valor']) : e($p['moeda'] . ' ' . $p['valor']) ?><?php if ($p['reembolsado'] > 0): ?><small>Reembolsado:
                                <?= money($p['reembolsado']) ?></small><?php endif; ?></span><span><i
                            class="tag"><?= e(statusLabel($p['status'])) ?></i></span><span><?php if ($link = safeReceipt($p['recibo_url'])): ?><a
                                class="download" href="<?= e($link) ?>" rel="noopener noreferrer" target="_blank"
                                aria-label="Abrir recibo"><?= icon('download') ?></a><?php else: ?>Indisponível<?php endif; ?></span>
                </div>
            <?php endforeach;
            if (!$d['pagamentos'])
                emptyState('Você ainda não possui pagamentos registrados.'); ?>
        </div>
    </section>
    <?php fecharLayout(); ?>
</body>

</html>