<?php
// Executar somente por CLI contra uma instalação local de desenvolvimento.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../model/Database.php';
$base = $argv[1] ?? 'http://127.0.0.1:8085/ProjetoGestaoAgil';
if (!in_array(parse_url($base, PHP_URL_HOST), ['127.0.0.1','localhost','::1'],true)) exit("Use um servidor local.\n");
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); echo "OK: $message\n"; }
function client() { $c=curl_init(); curl_setopt_array($c,[CURLOPT_COOKIEFILE=>'',CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_TIMEOUT=>10]);return $c; }
function request($c,$path,$data=null) {
    global $base;
    curl_setopt_array($c,[CURLOPT_URL=>$base.'/'.$path,CURLOPT_POST=>$data!==null]);
    if($data!==null) curl_setopt($c,CURLOPT_POSTFIELDS,http_build_query($data));
    $result=curl_exec($c); if($result===false) throw new RuntimeException(curl_error($c));
    $size=curl_getinfo($c,CURLINFO_HEADER_SIZE);
    return ['status'=>curl_getinfo($c,CURLINFO_HTTP_CODE),'headers'=>substr($result,0,$size),'body'=>substr($result,$size)];
}
function token($c,$page='view/login.php') { $r=request($c,$page); if(!preg_match('/name="csrf" value="([a-f0-9]+)"/',$r['body'],$m)) throw new RuntimeException('CSRF ausente'); return $m[1]; }
function submit($c,$action,$data=[]) { return request($c,'processamento/auth.php',array_merge(['acao'=>$action,'csrf'=>token($c)],$data)); }
function location($r,$path) { return $r['status']===303 && str_contains($r['headers'],'/ProjetoGestaoAgil/'.$path); }
$tag=bin2hex(random_bytes(8));$pass=bin2hex(random_bytes(16));$emails=[];
$patientEmail="patient-$tag@example.invalid"; $psychEmail="psych-$tag@example.invalid";
$emails=[$patientEmail,$psychEmail,"duplicate-$tag@example.invalid","admin-$tag@example.invalid","invalid-$tag@example.invalid"];
$p=client();$s=client();$anonymous=client();
$registration=['nome'=>'Teste <Autenticação>','email'=>$patientEmail,'senha'=>$pass,'confirmarSenha'=>$pass,'termos'=>'on','papel'=>'paciente'];
try {
    $pages=[];
    foreach(glob(__DIR__.'/../view/*.php') as $file) if(str_contains(file_get_contents($file),'requireRole(')) $pages[]=basename($file);
    foreach($pages as $page) check(location(request($anonymous,'view/'.$page),'view/login.php'),'anônimo bloqueado: '.$page);
    check(request($p,'processamento/auth.php')['status']===405,'endpoint recusa GET');
    check(request($p,'processamento/auth.php',['acao'=>'cadastro'])['status']===403,'CSRF ausente recusado');
    foreach ([['termos'=>''],['confirmarSenha'=>'diferente'],['papel'=>'admin'],['nome'=>''],['email'=>'invalido'],['senha'=>'curta','confirmarSenha'=>'curta']] as $invalid) {
        check(location(submit($p,'cadastro',array_replace($registration,$invalid)),'view/home.php'),'validação de cadastro: '.key($invalid));
    }
    check(!accountByEmail($patientEmail),'cadastros inválidos sem persistência');
    check(location(submit($p,'cadastro',$registration),'view/dashboardPaciente.php'),'cadastro paciente autentica');
    $patient=accountByEmail($patientEmail);$pid=$patient['id'];
    check(password_verify($pass,$patient['senha_hash']) && $patient['senha_hash']!==$pass,'senha armazenada em hash');
    check((int)query('SELECT COUNT(*) FROM pacientes WHERE usuario_id=?',[$pid])->fetchColumn()===1,'perfil paciente persistido');
    check((int)query('SELECT COUNT(*) FROM consentimentos WHERE usuario_id=?',[$pid])->fetchColumn()===2,'dois consentimentos persistidos');
    $r=request($p,'view/dashboardPaciente.php');check($r['status']===200 && str_contains($r['body'],'Teste &lt;Autenticação&gt;'),'nome autenticado com escaping');
    check(str_contains($r['headers'],'no-store'),'área restrita sem cache');
    foreach($pages as $page) {
        $source=file_get_contents(__DIR__.'/../view/'.$page);
        $own=str_contains($source,"requireRole('paciente')");
        check(request($p,'view/'.$page)['status']===($own?200:403),'permissão paciente: '.$page);
    }
    check(location(submit($anonymous,'cadastro',$registration),'view/home.php'),'e-mail duplicado recusado');
    $crp='99/'.random_int(1000000,9999999);
    $psych=array_replace($registration,['email'=>$psychEmail,'papel'=>'psicologo','crp'=>$crp]);
    check(location(submit($s,'cadastro',array_replace($psych,['crp'=>'inválido'])),'view/home.php'),'formato CRP inválido recusado');
    check(location(submit($s,'cadastro',$psych),'view/login.php'),'psicólogo cadastrado sem autenticação');
    $professional=accountByEmail($psychEmail);$sid=$professional['id'];
    check($professional['status']==='pendente' && $professional['status_verificacao']==='pendente','psicólogo permanece pendente');
    check(location(submit($s,'login',['email'=>$psychEmail,'senha'=>$pass]),'view/login.php'),'login pendente recusado');
    check(location(request($s,'view/dashboardPsicologo.php'),'view/login.php'),'pendente sem acesso profissional');
    check(location(submit($s,'cadastro',array_replace($psych,['email'=>$emails[2]])),'view/home.php'),'CRP duplicado recusado');
    check(!accountByEmail($emails[2]),'duplicidade não deixa conta incompleta');
    query("UPDATE usuarios SET status='ativo' WHERE id=?",[$sid]);
    query("UPDATE psicologos SET status_verificacao='aprovado',verificado_em=UTC_TIMESTAMP() WHERE usuario_id=?",[$sid]);
    check(location(submit($s,'login',['email'=>$psychEmail,'senha'=>$pass,'papel'=>'admin']),'view/dashboardPsicologo.php'),'login usa papel do banco');
    foreach($pages as $page) {
        $own=$page!=='perfilPsicologo.php' && str_contains(file_get_contents(__DIR__.'/../view/'.$page),"requireRole('psicologo')");
        check(request($s,'view/'.$page)['status']===($own?200:403),'permissão psicólogo: '.$page);
    }
    query("UPDATE psicologos SET status_verificacao='rejeitado' WHERE usuario_id=?",[$sid]);
    check(location(request($s,'view/dashboardPsicologo.php'),'view/login.php'),'rejeição revoga sessão existente');
    query("UPDATE usuarios SET status='suspenso' WHERE id=?",[$pid]);
    check(location(request($p,'view/dashboardPaciente.php'),'view/login.php'),'suspensão revoga sessão existente');
    check(location(submit($p,'login',['email'=>$patientEmail,'senha'=>$pass]),'view/login.php'),'login suspenso recusado');
    query("UPDATE usuarios SET status='ativo' WHERE id=?",[$pid]);
    check(location(submit($p,'login',['email'=>$patientEmail,'senha'=>'errada']),'view/login.php'),'senha incorreta recusada');
    $r=submit($p,'login',['email'=>$patientEmail,'senha'=>$pass]);
    check(location($r,'view/dashboardPaciente.php') && str_contains($r['headers'],'Set-Cookie: mindly_session='),'login renova sessão');
    check(str_contains($r['headers'],'HttpOnly') && str_contains($r['headers'],'SameSite=Lax'),'atributos do cookie');
    check(location(submit($p,'logout'),'view/login.php'),'logout redireciona');
    check(location(request($p,'view/dashboardPaciente.php'),'view/login.php'),'logout invalida sessão');
    submit($p,'login',['email'=>$patientEmail,'senha'=>$pass]);
    // Fixture de token: testa consumo, expiração e revogação sem simular envio de e-mail.
    $reset=bin2hex(random_bytes(32));
    query('INSERT INTO tokens_redefinicao_senha (usuario_id,token_hash,expira_em) VALUES (?,?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 30 MINUTE))',[$pid,hash('sha256',$reset)]);
    $expired=bin2hex(random_bytes(32));
    query('INSERT INTO tokens_redefinicao_senha (usuario_id,token_hash,expira_em) VALUES (?,?,DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MINUTE))',[$pid,hash('sha256',$expired)]);
    $new=bin2hex(random_bytes(16));
    check(location(submit($anonymous,'redefinir',['token'=>$expired,'senha'=>$new,'confirmarSenha'=>$new]),'view/redefinirSenha.php'),'token expirado recusado');
    check(password_verify($pass,accountById($pid)['senha_hash']),'token expirado não altera senha');
    check(location(submit($anonymous,'redefinir',['token'=>$reset,'senha'=>$new,'confirmarSenha'=>$new]),'view/login.php'),'token válido redefine senha');
    check(location(request($p,'view/dashboardPaciente.php'),'view/login.php'),'redefinição invalida sessão anterior');
    check(location(submit($anonymous,'redefinir',['token'=>$reset,'senha'=>$pass,'confirmarSenha'=>$pass]),'view/redefinirSenha.php'),'token usado recusado');
    check(password_verify($new,accountById($pid)['senha_hash']),'token não reutilizado');
    check(location(submit($p,'login',['email'=>$patientEmail,'senha'=>$pass]),'view/login.php'),'senha antiga recusada');
    check(location(submit($p,'login',['email'=>$patientEmail,'senha'=>$new]),'view/dashboardPaciente.php'),'nova senha aceita');
    if(!config()['mail_enabled']) {
        submit($anonymous,'recuperar',['email'=>$patientEmail]);
        check(str_contains(request($anonymous,'view/redefinirSenha.php')['body'],'indisponível'),'ausência de e-mail comunicada sem simulação');
    }
    query("INSERT INTO usuarios (nome,email,senha_hash,papel,status) VALUES (?,?,?,'admin','ativo')",['Admin temporário',$emails[3],password_hash($pass,PASSWORD_DEFAULT)]);
    $a=client();check(location(submit($a,'login',['email'=>$emails[3],'senha'=>$pass]),'view/dashboardAdmin.php'),'login admin');
    foreach($pages as $page) {
        $own=str_contains(file_get_contents(__DIR__.'/../view/'.$page),"requireRole('admin')");
        check(request($a,'view/'.$page)['status']===($own?200:403),'permissão admin: '.$page);
    }
    echo "Todos os testes passaram.\n";
} finally {
    foreach($emails as $email) {
        $u=accountByEmail($email);if(!$u) continue;
        foreach(['notificacoes','tokens_redefinicao_senha','tokens_login_persistente','consentimentos','pacientes','psicologos'] as $table) query("DELETE FROM $table WHERE usuario_id=?",[$u['id']]);
        query('DELETE FROM usuarios WHERE id=?',[$u['id']]);
    }
    echo "Contas e tokens temporários removidos.\n";
}
