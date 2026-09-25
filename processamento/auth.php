<?php
require_once __DIR__ . '/../controller/Controlador.php';
checkPost();
$action=input('acao');
$routes=['cadastro'=>'view/home.php#cadastro','login'=>'view/login.php','recuperar'=>'view/redefinirSenha.php','redefinir'=>'view/redefinirSenha.php'];
if ($action==='logout') { logout(); redirect('view/login.php'); }
if (!isset($routes[$action])) { http_response_code(400); exit('Ação inválida.'); }
try { Controlador::$action(); }
catch (DomainException $ex) { flash($ex->getMessage()); }
catch (Throwable $ex) { error_log('Mindly: falha na autenticação (' . get_class($ex) . ').'); flash('Serviço temporariamente indisponível. Tente novamente mais tarde.'); }
if ($action === 'cadastro') {
    // Lista explícita: nunca armazenar senhas na sessão ou reapresentá-las no HTML.
    foreach (['nome','email','papel','crp','nome_profissional','area_atuacao','termos'] as $field) {
        $_SESSION['cadastro_anterior'][$field] = mb_substr(input($field), 0, 190);
    }
}
// Mantém o token só na sessão durante a correção do formulário, nunca em mensagens/logs.
if ($action==='redefinir' && preg_match('/^[a-f0-9]{64}$/D',input('token'))) $_SESSION['reset_token']=input('token');
redirect($routes[$action]);
