<?php
require_once __DIR__ . '/../controller/Auth.php';
if (isset($_GET['token']) && is_string($_GET['token'])) {
    if (preg_match('/^[a-f0-9]{64}$/D', $_GET['token'])) $_SESSION['reset_token'] = $_GET['token'];
    else { unset($_SESSION['reset_token']); flash('Link inválido. Solicite novas orientações.'); }
    redirect('view/redefinirSenha.php');
}
$token = $_SESSION['reset_token'] ?? '';
unset($_SESSION['reset_token']);
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><link rel="stylesheet" href="../css/redefinirSenha.css"><title>Mindly | Redefinir senha</title></head><body><main><a class="logo" href="home.php"><img src="../img/logo-mindly.svg" alt="Mindly"></a><section class="card"><div class="lock">⌁</div><h1>Redefinir senha</h1><p>Informe o e-mail cadastrado. Se o envio de e-mail estiver disponível, enviaremos as orientações para criar uma nova senha.</p><?php showMessage(); ?><form method="post" action="../processamento/auth.php"><?= csrfField() ?>
<?php if ($token): ?>
<input type="hidden" name="acao" value="redefinir"><input type="hidden" name="token" value="<?= e($token) ?>">
<label>Nova senha<input type="password" name="senha" minlength="8" maxlength="72" required autocomplete="new-password"></label>
<label>Confirmar senha<input type="password" name="confirmarSenha" minlength="8" maxlength="72" required autocomplete="new-password"></label>
<button type="submit">Salvar nova senha</button>
<?php else: ?>
<input type="hidden" name="acao" value="recuperar"><label>E-mail<input type="email" name="email" maxlength="190" required autocomplete="email" placeholder="seuemail@exemplo.com"></label><button type="submit">Enviar orientações</button>
<?php endif; ?></form><a class="voltar" href="login.php">← Voltar para o login</a></section></main></body></html>