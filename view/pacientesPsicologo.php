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
    <link rel="stylesheet" href="../css/pacientesPsicologo.css">
    <title>Mindly | Pacientes</title>
</head>

<body>
    <?php abrirLayout('psicologo', 'pacientes', 'Meus pacientes', 'Acompanhe os pacientes vinculados aos seus atendimentos.', $currentUser['nome']); ?>
    <form class="pacientes-topo cartao" method="get">
        <div class="busca"><?= icon('search') ?><input name="q" value="<?= e($d['busca']) ?>"
                placeholder="Buscar paciente"><button class="botao botao-secundario">Buscar</button></div>
        <div class="resumo"><strong><?= count($d['pacientes']) ?></strong><span>pacientes encontrados</span></div>
    </form>
    <div class="grade-pacientes"><?php foreach ($d['pacientes'] as $p): ?>
            <article class="cartao paciente"><span class="avatar-foto grande"><?= e(initials($p['nome'])) ?></span>
                <div class="paciente-info">
                    <h2><?= e($p['nome']) ?></h2>
                    <?php $linked = array_values(array_filter($d['consultasVinculadas'], fn($c) => (int) $c['paciente_id'] === (int) $p['id']));
                    $done = array_reverse(completed($linked)); ?><span><?= $p['desde'] ? 'Paciente desde ' . dateLabel($p['desde'], 'm/Y') : 'Paciente vinculado ao prontuário' ?></span>
                    <div class="mini-dados">
                        <p><strong><?= (int) $p['sessoes'] ?></strong> sessões realizadas</p>
                        <p>Último: <strong><?= dateLabel($done[0]['inicio_em'] ?? null, 'd/m/Y') ?></strong></p>
                        <p><?= upcoming($linked) ? 'Acompanhamento com consultas agendadas' : 'Sem próximas consultas' ?></p>
                        <p><strong><?= dateLabel($p['proxima'], 'd/m') ?></strong> próxima</p>
                    </div>
                </div>
                <div class="paciente-acoes"><a class="botao botao-secundario"
                        href="<?= e(publicLink('prontuarioPsicologo.php', $p['id'], 'paciente_id')) ?>"><?= icon('file') ?>
                        Prontuário</a><a class="mais"
                        href="<?= e(publicLink('pacientesPsicologo.php', $p['id'], 'paciente_id')) ?>"
                        aria-label="Detalhes do paciente"><?= icon('dots') ?></a></div>
            </article><?php endforeach;
    if (!$d['pacientes']): ?>
            <article class="cartao paciente"><?php emptyState('Nenhum paciente encontrado.'); ?></article><?php endif; ?>
    </div>
    <?php if ($p = $d['paciente']): ?>
        <section class="cartao detalhes-paciente">
            <h2>Histórico de <?= e($p['nome']) ?></h2><a class="botao botao-suave"
                href="<?= e(publicLink('prontuarioPsicologo.php', $p['id'], 'paciente_id')) ?>">Abrir prontuário</a>
            <div class="tabela-responsiva">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Duração</th>
                            <th>Status</th>
                            <th>Detalhes</th>
                        </tr>
                    </thead>
                    <tbody><?php foreach (array_reverse($d['consultasPaciente']) as $c): ?>
                            <tr>
                                <td><?= dateLabel($c['inicio_em']) ?></td>
                                <td><?= (int) $c['duracao'] ?> min</td>
                                <td><?= e(statusLabel($c['status'])) ?></td>
                                <td><a href="salaAtendimentoPsicologo.php?consulta_id=<?= (int) $c['id'] ?>">Consulta
                                        #<?= (int) $c['id'] ?></a></td>
                            </tr><?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section><?php endif; ?><?php fecharLayout(); ?>
</body>

</html>