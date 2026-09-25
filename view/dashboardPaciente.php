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
    <link rel="stylesheet" href="../css/dashboardPaciente.css">
    <title>Mindly | Início</title>
</head>

<body><?php abrirLayout('paciente', 'inicio', 'Olá!', 'Seu espaço de cuidado e acompanhamento.', $currentUser['nome']); ?>
    <div class="boas-vindas cartao">
        <div><span class="mini-label">Seu cuidado, no seu ritmo</span>
            <h2>Como você está se sentindo hoje?</h2>
            <p>Organize suas próximas consultas e encontre apoio quando precisar.</p>
            <div class="acoes-boas-vindas"><a class="botao botao-principal"
                    href="buscarPsicologos.php"><?= icon('search') ?> Encontrar psicólogo</a><a
                    class="botao botao-secundario" href="minhasConsultas.php"><?= icon('calendar') ?> Minhas
                    consultas</a></div>
        </div>
        <div class="ilustracao-calma"><span class="circulo c1"></span><span class="circulo c2"></span><span
                class="folha f1"></span><span class="folha f2"></span><span class="vaso"></span></div>
    </div>
    <div class="grid-resumo">
        <article class="cartao resumo"><span class="icone-card"><?= icon('calendar') ?></span>
            <div><strong><?= count($d['proximas']) ?></strong><span>consultas agendadas</span></div>
        </article>
        <article class="cartao resumo"><span class="icone-card"><?= icon('clock') ?></span>
            <div><strong><?= dateLabel($d['proxima']['inicio_em'] ?? null, 'd/m/Y') ?></strong><span>próximo
                    atendimento</span></div>
        </article>
        <article class="cartao resumo"><span class="icone-card"><?= icon('heart') ?></span>
            <div><strong><?= count($d['realizadas']) ?></strong><span>consultas realizadas</span></div>
        </article>
    </div>
    <div class="grid-principal">
        <article class="cartao proxima">
            <div class="cartao-cabecalho">
                <div>
                    <h2>Próxima consulta</h2>
                    <p>Seus próximos atendimentos.</p>
                </div><?php if ($c = $d['proxima']): ?><span
                        class="tag"><?= e(statusLabel($c['status'])) ?></span><?php endif; ?>
            </div>
            <?php if ($c): ?>
                <div class="consulta-info"><span class="avatar-foto grande"><?= e(initials($c['psicologo_nome'])) ?></span>
                    <div>
                        <h3><?= e($c['psicologo_nome']) ?></h3><span><?= e($c['area_atuacao'] ?? '') ?></span>
                        <p><b><?= dateLabel($c['inicio_em'], 'd/m/Y') ?></b> · <?= dateLabel($c['inicio_em'], 'H:i') ?> ·
                            <?= (int) $c['duracao'] ?> min</p>
                    </div>
                </div>
                <div class="acoes-consulta"><a class="botao botao-suave"
                        href="<?= e(publicLink('perfilPsicologo.php', $c['psicologo_id'])) ?>">Ver profissional</a><a
                        class="botao botao-principal"
                        href="<?= e(publicLink('salaAtendimento.php', $c['id'], 'consulta_id')) ?>"><?= icon('video') ?> Ver
                        atendimento</a></div>
            <?php else:
                emptyState('Você ainda não possui consultas agendadas.'); endif; ?>
        </article>
        <article class="cartao lembrete">
            <div class="cartao-cabecalho">
                <div>
                    <h2>Para você</h2>
                    <p>Pequenos lembretes de cuidado.</p>
                </div>
            </div>
            <blockquote>“Cuidar de si também é reconhecer quando precisamos de apoio.”</blockquote>
            <div class="seguranca"><?= icon('shield') ?><span>Seus dados e atendimentos são tratados com
                    privacidade.</span></div>
        </article>
    </div>
    <?php fecharLayout(); ?>
</body>

</html>