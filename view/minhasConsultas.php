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
    <link rel="stylesheet" href="../css/minhasConsultas.css">
    <title>Mindly | Minhas consultas</title>
</head>

<body>
    <?php abrirLayout('paciente', 'consultas', 'Suas consultas', 'Acompanhe próximos atendimentos e seu histórico.', $currentUser['nome']); ?>
    <?php if ($d['consulta']): ?>
        <a class="botao botao-suave" href="minhasConsultas.php">Voltar para minhas consultas</a>
        <?php require __DIR__ . '/componentes/gestaoConsulta.php'; ?>
    <?php endif; ?>
    <form class="tabs" method="get">
        <?php foreach (['proximas' => 'Próximas', 'realizadas' => 'Realizadas', 'canceladas' => 'Canceladas', 'todas' => 'Todas'] as $key => $label): ?><button
                name="aba" value="<?= $key ?>"
                class="<?= $d['aba'] === $key ? 'ativo' : '' ?>"><?= $label ?><?php if ($key === 'proximas'): ?>
                    <span><?= count($d['proximas']) ?></span><?php endif; ?></button><?php endforeach; ?></form>
    <div class="lista-consultas"><?php foreach ($d['listaConsultas'] as $c): ?>
            <article class="cartao consulta">
                <div class="data-bloco">
                    <strong><?= dateLabel($c['inicio_em'], 'd') ?></strong><span><?= dateLabel($c['inicio_em'], 'm/Y') ?></span>
                </div>
                <div class="info">
                    <div class="linha-nome">
                        <div>
                            <h2><?= e($c['psicologo_nome']) ?></h2>
                            <p><?= e($c['area_atuacao'] ?? '') ?></p>
                        </div><span class="tag"><?= e(statusLabel($c['status'])) ?></span>
                    </div>
                    <div class="detalhes"><span><?= icon('clock') ?>     <?= dateLabel($c['inicio_em'], 'H:i') ?> ·
                            <?= (int) $c['duracao'] ?> min</span><span><?= icon('video') ?> Atendimento online</span></div>
                </div>
                <div class="acoes"><a class="botao botao-secundario"
                        href="<?= e(publicLink('perfilPsicologo.php', $c['psicologo_id'])) ?>">Ver profissional</a><a
                        class="botao botao-principal"
                        href="<?= e(publicLink('minhasConsultas.php', $c['id'], 'consulta_id')) ?>">Ver detalhes / gerenciar</a>
                    <?php if (in_array($c['status'], ['aguardando_pagamento', 'confirmada'], true) && $c['inicio_em'] > gmdate('Y-m-d H:i:s')): ?>
                        <a class="botao botao-suave" href="?consulta_id=<?= (int) $c['id'] ?>&amp;acao=remarcar#remarcar-consulta">Remarcar</a>
                        <a class="botao botao-suave" href="?consulta_id=<?= (int) $c['id'] ?>&amp;acao=cancelar#cancelar-consulta">Cancelar</a>
                    <?php endif; ?></div>
                <?php if ($c['status'] === 'concluida'): ?>
                    <div class="avaliacao-consulta">
                        <?php if (!empty($c['avaliacao_id'])): ?>
                            <strong>Você avaliou esta consulta com <?= (int) $c['avaliacao_nota'] ?> de 5 estrelas.</strong>
                            <?php if (!empty($c['avaliacao_comentario'])): ?><p><?= e($c['avaliacao_comentario']) ?></p><?php endif; ?>
                        <?php else: ?>
                            <form method="post" action="../processamento/paciente.php">
                                <?= csrfField() ?><input type="hidden" name="acao" value="avaliar_consulta"><input type="hidden" name="consulta_id" value="<?= (int) $c['id'] ?>">
                                <label for="nota-<?= (int) $c['id'] ?>">Avalie esta consulta</label>
                                <fieldset class="nota-estrelas"><legend>Nota</legend><?php for ($star = 1; $star <= 5; $star++): ?><input id="nota-<?= (int) $c['id'] ?>-<?= $star ?>" name="nota" type="radio" value="<?= $star ?>" required><label for="nota-<?= (int) $c['id'] ?>-<?= $star ?>" aria-label="<?= $star ?> de 5 estrelas" title="<?= $star ?> de 5 estrelas">&#9733;</label><?php endfor; ?></fieldset>
                                <label for="comentario-<?= (int) $c['id'] ?>">Comentário (opcional)</label><textarea id="comentario-<?= (int) $c['id'] ?>" name="comentario" maxlength="2000" rows="2" placeholder="Compartilhe como foi sua experiência"></textarea>
                                <button class="botao botao-principal" type="submit">Enviar avaliação</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </article><?php endforeach;
    if (!$d['listaConsultas']): ?>
            <article class="cartao consulta">
                <?php emptyState($d['aba'] === 'proximas' ? 'Você ainda não possui consultas agendadas.' : ($d['aba'] === 'realizadas' ? 'Nenhum atendimento realizado até o momento.' : 'Nenhuma consulta encontrada.')); ?>
            </article><?php endif; ?>
    </div>
    <div class="ajuda cartao"><?= icon('heart') ?>
        <div><strong>Precisa remarcar?</strong><p>Abra sua consulta e escolha outro horário disponível do mesmo psicólogo.</p></div><a
            href="buscarPsicologos.php">Ver profissionais <?= icon('arrow') ?></a>
    </div>
    <?php fecharLayout(); ?>
</body>

</html>
