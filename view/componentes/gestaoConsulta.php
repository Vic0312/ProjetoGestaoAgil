<?php if ($c = $d['consulta']): ?>
    <section class="cartao gestao-atendimento">
        <h2><?= $currentUser['papel'] === 'paciente' ? 'Detalhes da consulta' : 'Gestão administrativa do atendimento' ?> #<?= (int) $c['id'] ?></h2>
        <?php if ($currentUser['papel'] === 'paciente'): ?><p>Psicólogo(a): <?= e($c['psicologo_nome']) ?> · Horário de São Paulo</p><?php endif; ?>
        <p><?= e(statusLabel($c['status'])) ?> · <?= money($c['valor']) ?> · <?= dateLabel($c['inicio_em']) ?></p>
        <?php if ($c['consulta_origem_id']): ?>
            <p>Remarcação da consulta #<?= (int) $c['consulta_origem_id'] ?>.</p><?php endif; ?>
        <p>Estas ações registram o estado administrativo do atendimento. Não iniciam uma videochamada.</p>
        <div class="acoes-gestao">
            <?php if ($currentUser['papel'] === 'paciente' && $c['status'] === 'aguardando_pagamento'): ?>
                <a class="botao botao-principal" href="pagamentoConsulta.php?consulta_id=<?= (int) $c['id'] ?>">Acessar pagamento</a>
            <?php endif; ?>
            <?php if ($currentUser['papel'] === 'psicologo'): ?>
                <?php if ($c['status'] === 'aguardando_pagamento'): ?>
                    <form method="post" action="../processamento/profissional.php"><?php operationFields('confirmar'); ?><input
                            type="hidden" name="consulta_id" value="<?= (int) $c['id'] ?>"><button
                            class="botao botao-principal">Confirmar após pagamento</button></form><?php endif; ?>
                <?php if ($c['status'] === 'confirmada'): ?>
                    <form method="post" action="../processamento/profissional.php"><?php operationFields('iniciar'); ?><input
                            type="hidden" name="consulta_id" value="<?= (int) $c['id'] ?>"><button
                            class="botao botao-principal">Registrar início administrativo</button></form>
                    <form class="form-operacao" method="post" action="../processamento/profissional.php">
                        <?php operationFields('ausencia'); ?><input type="hidden" name="consulta_id"
                            value="<?= (int) $c['id'] ?>"><label><input type="checkbox" name="confirmacao" value="1" required>
                            Confirmo ausência após o término do horário reservado.</label><button
                            class="botao botao-secundario">Registrar ausência</button></form><?php endif; ?>
                <?php if (in_array($c['status'], ['confirmada', 'em_andamento'], true)): ?>
                    <form class="form-operacao" method="post" action="../processamento/profissional.php">
                        <?php operationFields('concluir'); ?><input type="hidden" name="consulta_id"
                            value="<?= (int) $c['id'] ?>"><label><input type="checkbox" name="confirmacao" value="1" required>
                            Confirmo que o atendimento efetivamente aconteceu.</label><button
                            class="botao botao-principal">Registrar conclusão administrativa</button></form><?php endif; ?>
                <?php if ($c['status'] === 'concluida'): ?><a class="botao botao-principal"
                        href="prontuarioPsicologo.php?paciente_id=<?= (int) $c['paciente_id'] ?>">Abrir prontuário</a><?php endif; ?>
            <?php endif; ?>
            <?php if (in_array($c['status'], ['aguardando_pagamento', 'confirmada'], true) && $c['inicio_em'] > gmdate('Y-m-d H:i:s')): ?>
                <details id="cancelar-consulta" <?= requestText('acao') === 'cancelar' ? 'open' : '' ?>>
                    <summary>Cancelar consulta</summary>
                    <form class="form-operacao" method="post" action="../processamento/profissional.php">
                        <?php operationFields('cancelar'); ?><input type="hidden" name="consulta_id"
                            value="<?= (int) $c['id'] ?>"><label>Motivo (sem informações clínicas)<input name="motivo"
                                maxlength="500" required></label>
                        <p>O histórico será preservado. Valores aprovados gerarão solicitação de reembolso, sem estorno
                            automático.</p><button class="botao botao-secundario">Confirmar cancelamento</button>
                    </form>
                </details>
                <details id="remarcar-consulta" <?= requestText('acao') === 'remarcar' ? 'open' : '' ?>>
                    <summary>Remarcar consulta</summary>
                    <?php if (!$d['horarios']) emptyState('Nenhum novo horário disponível. Sua consulta atual será mantida.'); ?>
                    <form class="form-operacao" method="post" action="../processamento/profissional.php">
                        <?php operationFields('remarcar'); ?><input type="hidden" name="consulta_id"
                            value="<?= (int) $c['id'] ?>"><label>Novo horário<select name="horario_id" required>
                                <option value="">Selecione</option><?php foreach ($d['horarios'] as $h): ?>
                                    <option value="<?= (int) $h['id'] ?>"><?= dateLabel($h['inicio_em']) ?></option>
                                <?php endforeach; ?>
                            </select></label><label>Motivo (sem informações clínicas)<input name="motivo" maxlength="500"
                                required></label>
                        <p>A remarcação mantém a consulta original no histórico e preserva o preço. Pagamentos em processamento
                            dependem de conciliação.</p><button class="botao botao-principal" <?= !$d['horarios'] ? 'disabled' : '' ?>>Confirmar remarcação</button>
                    </form>
                </details><?php endif; ?>
        </div>
        <h3>Histórico de alterações</h3>
        <ol><?php foreach ($d['historicoStatus'] ?? [] as $h): ?>
                <li><?= dateLabel($h['criado_em']) ?> ·
                    <?= e($h['status_anterior'] ? statusLabel($h['status_anterior']) . ' → ' : '') ?>        <?= e(statusLabel($h['status_novo'])) ?>
                    · <?= e($h['motivo']) ?></li><?php endforeach; ?>
        </ol><?php if (empty($d['historicoStatus']))
            emptyState('Nenhuma alteração registrada.'); ?>
    </section>
<?php endif; ?>
