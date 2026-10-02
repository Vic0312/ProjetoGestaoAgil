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
    <link rel="stylesheet" href="../css/perfilPaciente.css">
    <title>Mindly | Meu perfil</title>
</head>

<body>
    <?php abrirLayout('paciente', 'perfil', 'Meu perfil', 'Mantenha suas informações de acesso e contato atualizadas.', $currentUser['nome']); ?>
    <div class="perfil-layout">
        <aside class="cartao resumo-perfil">
            <div class="foto-perfil"><span
                    class="avatar-foto foto"><?= e(initials($currentUser['nome'])) ?></span><button disabled
                    title="Alteração de foto ainda não disponível"><?= icon('camera') ?></button></div>
            <h2><?= e($currentUser['nome']) ?></h2>
            <p><?= e($currentUser['email']) ?></p><span class="tag">Paciente</span>
            <div class="linha-divisoria"></div>
            <div class="conta-info"><span>Membro
                    desde</span><strong><?= dateLabel($currentUser['criado_em'], 'd/m/Y') ?></strong></div>
        </aside>
        <section class="cartao formulario-perfil">
            <div class="cartao-cabecalho">
                <div>
                    <h2>Informações pessoais</h2>
                    <p>Dados utilizados no seu perfil Mindly.</p>
                </div><button form="perfil-paciente" class="botao botao-principal"><?= icon('edit') ?> Salvar alterações</button>
            </div>
            <form id="perfil-paciente" method="post" action="../processamento/paciente.php">
                <?php operationFields('perfil_paciente'); ?>
                <div class="grade-campos">
                    <div class="campo"><label for="nome">Nome completo</label><input id="nome" name="nome" autocomplete="name" required minlength="2" maxlength="150"
                            value="<?= e(oldOperation('perfil_paciente', 'nome', $currentUser['nome'])) ?>"></div>
                    <div class="campo"><label for="nascimento">Data de nascimento (opcional)</label><input id="nascimento" name="data_nascimento" type="date" autocomplete="bday" min="1000-01-01" max="<?= mindlyTime()->format('Y-m-d') ?>"
                            value="<?= e(oldOperation('perfil_paciente', 'data_nascimento', $d['perfil']['data_nascimento'] ?? '')) ?>"></div>
                    <div class="campo"><label for="email">E-mail</label><input id="email" name="email" type="email" autocomplete="email" required maxlength="190" value="<?= e(oldOperation('perfil_paciente', 'email', $currentUser['email'])) ?>">
                    </div>
                    <div class="campo"><label for="telefone">Telefone (opcional)</label><input id="telefone" name="telefone" type="tel" autocomplete="tel" maxlength="20" value="<?= e(oldOperation('perfil_paciente', 'telefone', $currentUser['telefone'] ?? '')) ?>"
                            placeholder="(11) 99999-9999"></div>
                </div>
                <div class="secao-form">
                    <h3>Segurança</h3>
                    <p>Atualize sua senha quando necessário.</p>
                    <div class="senha-linha">
                        <div><strong>Senha</strong><span>Recuperação por e-mail</span></div><a href="redefinirSenha.php"
                            class="botao botao-suave">Alterar senha</a>
                    </div>
                </div>
                <div class="secao-form">
                    <h3>Privacidade</h3>
                    <p>Seus dados pessoais são usados somente para a experiência e operação da plataforma.</p>
                    <div class="privacidade-linha"><?= icon('shield') ?><span>Informações protegidas e acesso
                            controlado.</span></div>
                </div>
            </form>
        </section>
    </div>
    <?php fecharLayout(); ?>
</body>

</html>
