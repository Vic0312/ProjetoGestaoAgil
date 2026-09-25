<?php require_once __DIR__ . '/../controller/Auth.php';
$currentUser = isset($_GET['preview']) && $_GET['preview'] === '1' ? requireRole('psicologo') : requireRole('paciente'); ?>
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
    <link rel="stylesheet" href="../css/perfilPsicologo.css">
    <title>Mindly | Perfil profissional</title>
</head>

<body>
    <?php abrirLayout($currentUser['papel'], 'perfil', 'Conheça o profissional', 'Informações, abordagem e horários disponíveis.', $currentUser['nome']); ?>
    <div class="perfil-grid">
        <div class="coluna-principal">
            <article class="cartao hero-perfil"><?php if ($p = $d['profissional']): ?>
                    <div class="identidade"><?php avatar($p, 'avatar-foto foto-xl'); ?>
                        <div>
                            <div class="nome-verificado">
                                <h2><?= e($p['nome_profissional']) ?></h2><span class="verificado"
                                    title="Cadastro aprovado"><?= icon('check') ?></span>
                            </div>
                            <p><?= e($p['area_atuacao'] ?? 'Área não informada') ?> · CRP <?= e($p['crp']) ?></p>
                            <div class="avaliacao">
                                <?= icon('star') ?><strong><?= rating($p) ?></strong><span><?= (int) $p['avaliacoes'] ?>
                                    avaliações · <?= (int) $p['atendimentos'] ?> atendimentos</span>
                            </div>
                        </div>
                    </div>
                    <div class="tags"><?php professionalTags($p); ?></div>
                <?php else:
                emptyState('Selecione um profissional na busca para visualizar seu perfil.'); ?><a
                        href="buscarPsicologos.php" class="botao botao-principal">Buscar psicólogos</a><?php endif; ?>
            </article>
            <article class="cartao sobre">
                <h2>Sobre mim</h2>
                <p><?= nl2br(e($p['biografia'] ?? 'Nenhuma biografia disponível.')) ?></p>
                <h3>Abordagem</h3>
                <p><?= nl2br(e($p['abordagem'] ?? 'Nenhuma abordagem informada.')) ?></p>
            </article>
            <article class="cartao experiencia">
                <h2>Experiência e atendimento</h2>
                <div class="itens">
                    <div><span class="icone-redonda"><?= icon('video') ?></span><strong>Atendimento
                            online</strong><small>Videochamada ainda não disponível</small></div>
                    <div><span
                            class="icone-redonda"><?= icon('clock') ?></span><strong><?= isset($p['duracao_padrao_minutos']) ? (int) $p['duracao_padrao_minutos'] . ' minutos' : '—' ?></strong><small>Duração
                            da sessão</small></div>
                    <div><span class="icone-redonda"><?= icon('shield') ?></span><strong>Ambiente
                            seguro</strong><small>Privacidade e confidencialidade</small></div>
                </div>
            </article>
        </div>
        <aside class="cartao agendamento-lateral">
            <div class="preco"><span>Valor da
                    consulta</span><strong><?= isset($p['valor_consulta']) ? money($p['valor_consulta']) : '—' ?></strong>
            </div>
            <div class="linha-divisoria"></div>
            <h3>Próximos horários</h3>
            <div class="horarios"><?php foreach (array_slice($d['horarios'], 0, 12) as $h): ?><button
                        disabled><?= dateLabel($h['inicio_em']) ?></button><?php endforeach;
            if (!$d['horarios'])
                emptyState('Nenhum horário disponível cadastrado.'); ?>
            </div><?php if ($p && $currentUser['papel'] === 'paciente'): ?><a class="botao botao-principal agendar"
                    href="<?= e(publicLink('agendarConsulta.php', $p['usuario_id'])) ?>"><?= icon('calendar') ?> Consultar
                    agenda</a><?php endif; ?>
            <p>Escolha um horário na agenda para reservar sua consulta.</p>
        </aside>
    </div>
    <?php fecharLayout(); ?>
</body>

</html>