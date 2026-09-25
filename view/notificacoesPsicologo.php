<?php require_once __DIR__ . '/../controller/Auth.php';
$currentUser = requireRole('psicologo');
require_once __DIR__ . '/../controller/DadosController.php';
$d = loadPageData(basename(__FILE__), $currentUser);
require_once __DIR__ . '/componentes/componentes.php';
require_once __DIR__ . '/componentes/dados.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <link rel="stylesheet" href="../css/componentes.css">
    <link rel="stylesheet" href="../css/notificacoesPsicologo.css">
    <title>Mindly | Notificações</title>
</head>

<body>
    <?php abrirLayout('psicologo', 'notificacoes', 'Notificações', 'Dados reais dos seus atendimentos.', $currentUser['nome']); ?>
    <section class="cartao lista-notificacoes">
        <h2>Histórico de notificações</h2>
        <p><?= count($d['notificacoes']) ?> não lidas</p><?php foreach ($d['avisos'] as $notice): ?>
            <article>
                <div>
                    <h3><?= e($notice['titulo']) ?></h3>
                    <p><?= nl2br(e($notice['mensagem'])) ?></p><small><?= dateLabel($notice['criado_em']) ?> ·
                        <?= $notice['lida_em'] ? 'Lida em ' . dateLabel($notice['lida_em']) : 'Não lida' ?></small>
                </div><?php if (!$notice['lida_em']): ?>
                    <form method="post" action="../processamento/profissional.php">
                        <?php operationFields('ler_notificacao'); ?><input type="hidden" name="id"
                            value="<?= (int) $notice['id'] ?>"><button class="botao botao-suave">Marcar como lida</button></form>
                <?php endif; ?>
            </article><?php endforeach;
           if (!$d['avisos'])
               emptyState('Você não possui notificações.'); ?>
    </section>
    <?php fecharLayout(); ?>
</body>

</html>