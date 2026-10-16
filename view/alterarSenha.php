<?php
require_once __DIR__ . '/../controller/Auth.php';
$sessionUser = !empty($_SESSION['user_id']) ? accountById($_SESSION['user_id']) : null;
if (!$sessionUser || !in_array($sessionUser['papel'], ['paciente', 'psicologo'], true)) redirect('view/login.php');
$currentUser = requireRole($sessionUser['papel']);
$d = ['notificacoes' => []];
require_once __DIR__ . '/componentes/componentes.php';
require_once __DIR__ . '/componentes/dados.php';
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="../css/componentes.css"><link rel="stylesheet" href="../css/alterarSenha.css"><title>Mindly | Alterar senha</title></head>
<body><?php abrirLayout($currentUser['papel'], 'seguranca', 'Alterar senha', 'Confirme sua senha atual para proteger sua conta.', $currentUser['nome']); ?>
<section class="cartao alterar-senha"><h2>Segurança da conta</h2><p>Use uma senha com pelo menos 8 caracteres.</p><form method="post" action="../processamento/auth.php" autocomplete="on"><?= csrfField() ?><input type="hidden" name="acao" value="alterarSenha"><label>Senha atual<input type="password" name="senha_atual" autocomplete="current-password" required maxlength="72"></label><label>Nova senha<input type="password" name="nova_senha" autocomplete="new-password" minlength="8" maxlength="72" required></label><label>Confirmar nova senha<input type="password" name="confirmar_senha" autocomplete="new-password" minlength="8" maxlength="72" required></label><div class="acoes-senha"><a class="botao botao-suave" href="<?= $currentUser['papel'] === 'paciente' ? 'perfilPaciente.php' : 'perfilProfissional.php' ?>">Voltar</a><button class="botao botao-principal" type="submit">Salvar nova senha</button></div></form></section>
<?php fecharLayout(); ?></body></html>
