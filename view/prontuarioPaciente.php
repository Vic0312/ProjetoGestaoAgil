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
    <link rel="stylesheet" href="../css/prontuarioPaciente.css">
    <title>Mindly | Meu histórico</title>
</head>

<body>
    <?php abrirLayout('paciente', 'historico', 'Histórico de atendimentos', 'Consulte suas sessões e informações compartilhadas com você.', $currentUser['nome']); ?>
    <div class="historico-grid">
        <section class="cartao timeline-card">
            <div class="cartao-cabecalho">
                <div>
                    <h2>Histórico de consultas</h2>
                    <p>Seus atendimentos mais recentes.</p>
                </div><button class="botao botao-secundario" disabled
                    title="Filtro ainda não disponível"><?= icon('filter') ?> Filtrar</button>
            </div>
            <div class="timeline"><?php foreach ($d['historico'] as $h): ?>
                    <article><span class="ponto"></span>
                        <div class="data"><?= dateLabel($h['inicio_em']) ?></div>
                        <div class="sessao">
                            <div class="sessao-topo">
                                <div>
                                    <h3><?= e($h['psicologo_nome']) ?></h3><span>Atendimento online</span>
                                </div><span class="tag concluida">Concluída</span>
                            </div>
                            <p><?= nl2br(e($h['texto'] ?? 'Nenhum resumo compartilhado para este atendimento.')) ?></p>
                            <?php if ($h['compartilhado_em']): ?><small>Compartilhado em
                                    <?= dateLabel($h['compartilhado_em']) ?></small><?php endif; ?>
                        </div>
                    </article>
                <?php endforeach;
            if (!$d['historico'])
                emptyState('Nenhum atendimento realizado até o momento.'); ?>
            </div>
        </section>
        <aside class="cartao privacidade-card"><span class="escudo"><?= icon('shield') ?></span>
            <h2>Sobre seu histórico</h2>
            <p>Você visualiza somente as informações que o profissional compartilhou com você.</p>
            <div class="linha-divisoria"></div><strong><?= count($d['historico']) ?> consultas
                realizadas</strong><span>Histórico da sua conta</span>
        </aside>
    </div>
    <?php fecharLayout(); ?>
</body>

</html>