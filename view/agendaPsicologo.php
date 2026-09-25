<?php require_once __DIR__ . '/../controller/Auth.php';
$currentUser = requireRole('psicologo'); ?>
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
    <link rel="stylesheet" href="../css/agendaPsicologo.css">
    <title>Mindly | Agenda</title>
</head>

<body>
    <?php abrirLayout('psicologo', 'agenda', 'Minha agenda', 'Visualize e acompanhe os atendimentos agendados.', $currentUser['nome']); ?>
    <div class="agenda-toolbar">
        <form class="navegacao-data" method="get"><button name="semana"
                value="<?= $d['inicioSemana']->modify('-7 days')->format('Y-m-d') ?>"
                aria-label="Semana anterior">‹</button><strong><?= $d['inicioSemana']->format('d/m/Y') ?> –
                <?= $d['inicioSemana']->modify('+6 days')->format('d/m/Y') ?></strong><button name="semana"
                value="<?= $d['inicioSemana']->modify('+7 days')->format('Y-m-d') ?>"
                aria-label="Próxima semana">›</button></form>
        <div class="acoes"><a class="botao botao-secundario" href="agendaPsicologo.php">Hoje</a><a
                class="botao botao-principal" href="disponibilidadePsicologo.php"><?= icon('plus') ?>
                Disponibilidade</a></div>
    </div>
    <?php if (!$d['semana'])
        emptyState('Nenhum atendimento agendado nesta semana.');
    $rows = $d['horaFinal'] - $d['horaInicial']; ?>
    <section class="cartao calendario-semanal">
        <div class="horas-coluna" style="grid-template-rows:60px repeat(<?= $rows ?>,58px)">
            <span></span><?php for ($hour = $d['horaInicial']; $hour < $d['horaFinal']; $hour++): ?><time><?= sprintf('%02d:00', $hour) ?></time><?php endfor; ?>
        </div>
        <div class="dias-grid" style="grid-template-columns:repeat(7,minmax(110px,1fr))">
            <?php $days = ['SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SÁB', 'DOM'];
            for ($day = 0; $day < 7; $day++):
                $date = $d['inicioSemana']->modify("+$day days"); ?>
                <div class="dia-col">
                    <header><span><?= $days[$day] ?></span><strong><?= $date->format('d') ?></strong></header>
                    <div class="grade-horas" style="height:<?= $rows * 58 ?>px">
                        <?php foreach ($d['semana'] as $c):
                            $time = mindlyTime($c['inicio_em']);
                            if ($time->format('Y-m-d') !== $date->format('Y-m-d'))
                                continue;
                            $top = ((int) $time->format('G') - $d['horaInicial'] + (int) $time->format('i') / 60) * 58; ?><a
                                class="evento"
                                style="top:<?= (float) $top ?>px;height:<?= max(36, min(120, (int) $c['duracao'] * 58 / 60)) ?>px"
                                href="<?= e(publicLink('salaAtendimentoPsicologo.php', $c['id'], 'consulta_id')) ?>"
                                title="<?= e($c['paciente_nome'] . ' · ' . statusLabel($c['status'])) ?>"><strong><?= $time->format('H:i') ?>
                                    · <?= e($c['paciente_nome']) ?></strong><span><?= (int) $c['duracao'] ?> min ·
                                    <?= e(statusLabel($c['status'])) ?></span></a><?php endforeach; ?></div>
                </div><?php endfor; ?>
        </div>
    </section>
    <section class="cartao agenda-livres">
        <h2>Horários livres nesta semana</h2>
        <div class="horarios-reais">
            <?php $free = 0;
            foreach ($d['horarios'] as $h):
                $t = mindlyTime($h['inicio_em']);
                if ($t < $d['inicioSemana'] || $t >= $d['inicioSemana']->modify('+7 days'))
                    continue;
                $free++; ?><span
                    class="tag"><?= dateLabel($h['inicio_em']) ?> –
                    <?= dateLabel($h['fim_em'], 'H:i') ?></span><?php endforeach;
            if (!$free)
                emptyState('Nenhum horário livre nesta semana.'); ?>
        </div>
    </section>
    <section class="cartao agenda-historico">
        <h2>Todos os atendimentos e histórico</h2>
        <form class="form-operacao" method="get"><label>Status<select name="status">
                    <option value="">Todos</option>
                    <?php foreach (['aguardando_pagamento', 'confirmada', 'em_andamento', 'concluida', 'cancelada', 'nao_compareceu'] as $status): ?>
                        <option value="<?= $status ?>" <?= requestText('status') === $status ? 'selected' : '' ?>>
                            <?= e(statusLabel($status)) ?></option><?php endforeach; ?>
                </select></label><button class="botao botao-suave">Filtrar</button></form>
        <div class="tabela-responsiva">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Paciente</th>
                        <th>Status</th>
                        <th>Detalhes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $found = false;
                    foreach (array_reverse($d['consultas']) as $c):
                        if (requestText('status') !== '' && requestText('status') !== $c['status'])
                            continue;
                        $found = true; ?>
                        <tr>
                            <td><?= dateLabel($c['inicio_em']) ?></td>
                            <td><?= e($c['paciente_nome']) ?></td>
                            <td><?= e(statusLabel($c['status'])) ?></td>
                            <td><a href="salaAtendimentoPsicologo.php?consulta_id=<?= (int) $c['id'] ?>">Gerenciar
                                    #<?= (int) $c['id'] ?></a></td>
                        </tr><?php endforeach; ?>
                </tbody>
            </table>
        </div><?php if (!$found)
            emptyState('Nenhum atendimento encontrado.'); ?>
    </section><?php fecharLayout(); ?>
</body>

</html>