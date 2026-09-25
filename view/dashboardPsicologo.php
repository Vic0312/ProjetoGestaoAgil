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
    <link rel="stylesheet" href="../css/dashboardPsicologo.css">
    <title>Mindly | Início</title>
</head>

<body>
    <?php abrirLayout('psicologo', 'inicio', 'Olá!', 'Aqui está um resumo da sua agenda e atendimentos.', $currentUser['nome']); ?>
    <div class="cards-indicadores">
        <article class="cartao indicador"><span><?= icon('calendar') ?></span>
            <div><small>Consultas
                    hoje</small><strong><?= count($d['hoje']) ?></strong><em><?= count(completed($d['hoje'])) ?>
                    realizadas · <?= count(upcoming($d['hoje'])) ?> próximas</em></div>
        </article>
        <article class="cartao indicador"><span><?= icon('users') ?></span>
            <div><small>Pacientes
                    vinculados</small><strong><?= count($d['pacientes']) ?></strong><em><?= count($d['realizadas']) ?>
                    consultas realizadas</em></div>
        </article>
        <article class="cartao indicador"><span><?= icon('clock') ?></span>
            <div><small>Próxima
                    consulta</small><strong><?= dateLabel($d['proxima']['inicio_em'] ?? null, 'd/m H:i') ?></strong><em><?= e($d['proxima']['paciente_nome'] ?? 'Nenhuma consulta agendada') ?></em>
            </div>
        </article>
    </div>
    <div class="psico-grid">
        <section class="cartao agenda-hoje">
            <div class="cartao-cabecalho">
                <div>
                    <h2>Agenda de hoje</h2>
                    <p><?= mindlyTime()->format('d/m/Y') ?></p>
                </div><a class="link-verde" href="agendaPsicologo.php">Ver agenda completa</a>
            </div>
            <div class="agenda-lista">
                <?php foreach ($d['hoje'] as $c): ?>
                    <article class="horario <?= $c['status'] === 'concluida' ? 'concluido' : '' ?>">
                        <time><?= dateLabel($c['inicio_em'], 'H:i') ?></time><span class="linha"></span>
                        <div><strong><?= e($c['paciente_nome']) ?></strong><small>Consulta online ·
                                <?= (int) $c['duracao'] ?> min</small></div><a class="tag"
                            href="<?= e(publicLink('salaAtendimentoPsicologo.php', $c['id'], 'consulta_id')) ?>"><?= e(statusLabel($c['status'])) ?></a>
                    </article>
                <?php endforeach;
                if (!$d['hoje'])
                    emptyState('Você ainda não possui consultas agendadas para hoje.'); ?>
            </div>
        </section>
        <aside class="coluna-lateral">
            <article class="cartao disponibilidade">
                <div class="cartao-cabecalho">
                    <div>
                        <h2>Disponibilidade</h2>
                        <p>Seus próximos horários livres.</p>
                    </div>
                </div>
                <div class="dias-disponiveis"><?php foreach (array_slice($d['horarios'], 0, 5) as $h): ?>
                        <div>
                            <strong><?= dateLabel($h['inicio_em'], 'd/m') ?></strong><span><?= dateLabel($h['inicio_em'], 'H:i') ?></span>
                        </div><?php endforeach;
                if (!$d['horarios'])
                    emptyState('Nenhum horário disponível cadastrado.'); ?>
                </div><a class="botao botao-suave" href="disponibilidadePsicologo.php">Gerenciar horários</a>
            </article>
            <article class="cartao lembrete-prontuario"><span><?= icon('file') ?></span>
                <div><strong>Prontuários</strong>
                    <p><?= count(array_filter($d['consultas'], fn($c) => $c['status'] === 'aguardando_pagamento')) ?>
                        atendimentos aguardam pagamento.
                        <?= count(array_filter($d['consultas'], fn($c) => $c['status'] === 'em_andamento')) ?> em andamento.
                    </p><a href="prontuarioPsicologo.php">Acessar prontuários</a>
                </div>
            </article>
            <article class="cartao disponibilidade">
                <h2>Pagamentos aprovados no mês</h2><strong><?= money($d['recebido']) ?></strong>
                <p>Valores em reais registrados nas suas consultas, descontados reembolsos concluídos. Não representa
                    repasse.</p><a href="financeiroPsicologo.php">Ver histórico financeiro</a>
            </article>
        </aside>
    </div>
    <?php fecharLayout(); ?>
</body>

</html>