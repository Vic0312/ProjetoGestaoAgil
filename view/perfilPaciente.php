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
                </div><button class="botao botao-secundario" disabled><?= icon('edit') ?> Editar</button>
            </div><?php unavailable('Edição de perfil'); ?>
            <form>
                <div class="grade-campos">
                    <div class="campo"><label>Nome completo</label><input value="<?= e($currentUser['nome']) ?>"
                            readonly></div>
                    <div class="campo"><label>Data de nascimento</label><input
                            value="<?= !empty($d['perfil']['data_nascimento']) ? e(DateTimeImmutable::createFromFormat('!Y-m-d', $d['perfil']['data_nascimento'])->format('d/m/Y')) : '' ?>"
                            placeholder="Não informada" readonly></div>
                    <div class="campo"><label>E-mail</label><input value="<?= e($currentUser['email']) ?>" readonly>
                    </div>
                    <div class="campo"><label>Telefone</label><input value="<?= e($currentUser['telefone'] ?? '') ?>"
                            placeholder="Não informado" readonly></div>
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