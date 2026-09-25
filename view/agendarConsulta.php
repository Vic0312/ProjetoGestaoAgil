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
    <link rel="stylesheet" href="../css/agendarConsulta.css">
    <title>Mindly | Agendar consulta</title>
</head>

<body>
    <?php abrirLayout('paciente', 'psicologos', 'Agende sua consulta', 'Escolha data, horário e confira os detalhes.', $currentUser['nome']); ?>
    <?php $p = $d['profissional'];
    $chosen = requestText('data');
    $selected = DateTimeImmutable::createFromFormat('!Y-m-d', $chosen, new DateTimeZone('America/Sao_Paulo'));
    if (!$selected || $selected->format('Y-m-d') !== $chosen)
        $selected = $d['horarios'] ? mindlyTime($d['horarios'][0]['inicio_em']) : mindlyTime();
    $calendar = $selected->modify('first day of this month')->setTime(0, 0);
    $slots = array_values(array_filter($d['horarios'], fn($h) => mindlyTime($h['inicio_em'])->format('Y-m-d') === $selected->format('Y-m-d'))); ?>
    <div class="agendamento-grid">
        <section class="cartao etapas">
            <div class="passos">
                <div class="passo <?= $p ? 'concluido' : 'ativo' ?>"><span>1</span><strong>Profissional</strong></div>
                <i></i>
                <div class="passo ativo"><span>2</span><strong>Horário</strong></div><i></i>
                <div class="passo"><span>3</span><strong>Pagamento</strong></div>
            </div>
            <div class="profissional-resumo"><?php if ($p)
                avatar($p); ?>
                <div>
                    <strong><?= e($p['nome_profissional'] ?? 'Selecione um profissional') ?></strong><small><?= $p ? 'CRP ' . e($p['crp']) : 'Nenhum profissional selecionado.' ?></small>
                </div><a href="buscarPsicologos.php">Alterar</a>
            </div>
            <div class="selecao-data">
                <h2>Escolha uma data</h2>
                <div class="calendario">
                    <form class="cal-topo" method="get"><input type="hidden" name="psicologo_id"
                            value="<?= (int) ($p['usuario_id'] ?? 0) ?>"><button name="data"
                            value="<?= $calendar->modify('-1 month')->format('Y-m-d') ?>"
                            aria-label="Mês anterior">‹</button><strong><?= $calendar->format('m/Y') ?></strong><button
                            name="data" value="<?= $calendar->modify('+1 month')->format('Y-m-d') ?>"
                            aria-label="Próximo mês">›</button></form>
                    <div class="semana">
                        <span>D</span><span>S</span><span>T</span><span>Q</span><span>Q</span><span>S</span><span>S</span>
                    </div>
                    <div class="dias-cal"><?php for ($i = 0; $i < (int) $calendar->format('w'); $i++): ?><span
                                class="fora"></span><?php endfor;
                    for ($i = 1; $i <= (int) $calendar->format('t'); $i++):
                        $date = $calendar->setDate((int) $calendar->format('Y'), (int) $calendar->format('m'), $i); ?><a
                                class="<?= $date->format('Y-m-d') === $selected->format('Y-m-d') ? 'hoje ativo' : '' ?>"
                                href="?psicologo_id=<?= (int) ($p['usuario_id'] ?? 0) ?>&amp;data=<?= $date->format('Y-m-d') ?>"><?= $i ?></a><?php endfor; ?>
                    </div>
                </div>
            </div>
            <div class="selecao-horario">
                <h2>Horários disponíveis</h2>
                <p><?= $selected->format('d/m/Y') ?> · horário de São Paulo</p>
                <div class="horarios"><?php foreach ($slots as $h): ?><label class="tag"><input form="agendar-form"
                                type="radio" name="horario_id" value="<?= (int) $h['id'] ?>" required>
                            <?= dateLabel($h['inicio_em'], 'H:i') ?> –
                            <?= dateLabel($h['fim_em'], 'H:i') ?></label><?php endforeach;
                if (!$slots)
                    emptyState('Nenhum horário disponível nesta data. Selecione outro dia.'); ?>
                </div>
            </div>
        </section>
        <aside class="cartao resumo-pedido">
            <h2>Resumo da consulta</h2>
            <div class="resumo-linha"><span><?= icon('calendar') ?>
                    Data</span><strong><?= $selected->format('d/m/Y') ?></strong></div>
            <div class="resumo-linha"><span><?= icon('clock') ?>
                    Duração</span><strong><?= (int) ($p['duracao_padrao_minutos'] ?? 0) ?> min</strong></div>
            <div class="resumo-linha"><span><?= icon('video') ?> Modalidade</span><strong>Online</strong></div>
            <div class="linha-divisoria"></div>
            <div class="total"><span>Valor</span><strong><?= $p ? money($p['valor_consulta']) : '—' ?></strong></div>
            <form id="agendar-form" method="post" action="../processamento/profissional.php">
                <?php operationFields('agendar'); ?><input type="hidden" name="psicologo_id"
                    value="<?= (int) ($p['usuario_id'] ?? 0) ?>"><button class="botao botao-principal continuar"
                    <?= !$slots ? 'disabled' : '' ?>>Confirmar agendamento <?= icon('arrow') ?></button></form>
            <p>O horário será reservado aguardando pagamento. Nenhuma cobrança é realizada por este botão.</p>
            <p>Pagamento externo e videochamada ainda não disponíveis.</p>
        </aside>
    </div>
    <?php fecharLayout(); ?>
</body>

</html>
