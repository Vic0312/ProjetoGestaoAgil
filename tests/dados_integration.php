<?php
/** Integração HTTP/MySQL. Cria somente fixtures aleatórias e as remove no finally. */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../model/MindlyData.php';
$base=$argv[1] ?? 'http://localhost/ProjetoGestaoAgil';
if(!in_array(parse_url($base,PHP_URL_HOST),['localhost','127.0.0.1','::1'],true)) exit("Execute apenas em ambiente local.\n");
function verify($ok,string $label): void { if(!$ok) throw new RuntimeException($label);echo "OK: $label\n"; }
function browserClient(){ $c=curl_init();curl_setopt_array($c,[CURLOPT_COOKIEFILE=>'',CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_TIMEOUT=>15]);return $c; }
function http($client,string $path,?array $data=null): array {
    global $base;curl_setopt_array($client,[CURLOPT_URL=>$base.'/'.$path,CURLOPT_POST=>$data!==null]);
    if($data!==null) curl_setopt($client,CURLOPT_POSTFIELDS,http_build_query($data));
    $raw=curl_exec($client);if($raw===false) throw new RuntimeException(curl_error($client));
    $size=curl_getinfo($client,CURLINFO_HEADER_SIZE);$body=substr($raw,$size);
    if(preg_match('/(?:Warning|Fatal error|Notice)<\/b>/',$body)) throw new RuntimeException('Erro PHP em '.$path);
    return ['status'=>curl_getinfo($client,CURLINFO_HTTP_CODE),'headers'=>substr($raw,0,$size),'body'=>$body];
}
function sendForm($client,string $action,array $data=[]): array {
    $body=http($client,'view/login.php')['body'];
    if(!preg_match('/name="csrf" value="([a-f0-9]+)"/',$body,$m)) throw new RuntimeException('Token CSRF não encontrado');
    return http($client,'processamento/auth.php',array_merge(['acao'=>$action,'csrf'=>$m[1]],$data));
}
function htmlPage($client,string $page): string { $r=http($client,'view/'.$page);if($r['status']!==200) throw new RuntimeException($page.' retornou '.$r['status']); return $r['body']; }
function fixture(string $table,array $values): int {
    global $created;
    query('INSERT INTO '.$table.' ('.implode(',',array_keys($values)).') VALUES ('.implode(',',array_fill(0,count($values),'?')).')',array_values($values));
    $id=(int)db()->lastInsertId();$created[$table][]=$id;return $id;
}
function appointment(int $patient,int $psych,string $date,string $status,string $amount): int {
    $end=(new DateTimeImmutable($date,new DateTimeZone('UTC')))->modify('+50 minutes')->format('Y-m-d H:i:s');
    $slot=fixture('horarios_agenda',['psicologo_id'=>$psych,'inicio_em'=>$date,'fim_em'=>$end,'status'=>'reservado']);
    return fixture('consultas',['paciente_id'=>$patient,'psicologo_id'=>$psych,'horario_id'=>$slot,'valor'=>$amount,'status'=>$status]);
}
$created=[];$emails=[];$accounts=[];$clients=[];$tag=bin2hex(random_bytes(6));$pass=bin2hex(random_bytes(12));
$original=(int)query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
$utc=new DateTimeImmutable('now',new DateTimeZone('UTC'));
try {
    foreach(['p1'=>'paciente','p2'=>'paciente','s1'=>'psicologo','s2'=>'psicologo'] as $key=>$role){
        $clients[$key]=browserClient();$email="$key-$tag@example.invalid";$emails[]=$email;
        $form=['nome'=>strtoupper($key).' Conta'.$tag,'email'=>$email,'senha'=>$pass,'confirmarSenha'=>$pass,'termos'=>'on','papel'=>$role,'crp'=>'99/'.random_int(1000000,9999999),'nome_profissional'=>'Profissional '.$key.' '.$tag,'area_atuacao'=>'Área '.$key.' '.$tag];
        $r=sendForm($clients[$key],'cadastro',array_replace($form,['senha'=>'1234','confirmarSenha'=>'1234']));
        verify($r['status']===303,'senha curta redireciona: '.$key);
        $html=htmlPage($clients[$key],'home.php');
        verify(str_contains($html,'A senha deve ter no mínimo 8 caracteres.'),'mensagem PHP exata para 4 caracteres: '.$key);
        verify(str_contains($html,'value="'.$email.'"') && str_contains($html,'value="'.$form['nome'].'"'),'campos preservados: '.$key);
        verify(!preg_match('/type="password"[^>]*value=/',$html),'senhas não reapresentadas: '.$key);
        if($role==='psicologo') verify(str_contains($html,'id="papel" value="psicologo"') && str_contains($html,'value="'.$form['crp'].'"'),'perfil e CRP preservados');
        verify(!accountByEmail($email),'senha curta não gera conta');
        sendForm($clients[$key],'cadastro',array_replace($form,['senha'=>'áááá','confirmarSenha'=>'áááá']));
        verify(!accountByEmail($email),'quatro caracteres multibyte não contam como oito');
        sendForm($clients[$key],'cadastro',array_replace($form,['confirmarSenha'=>$pass.'x']));
        verify(str_contains(htmlPage($clients[$key],'home.php'),'As senhas não coincidem'),'confirmação validada no PHP');
        $r=sendForm($clients[$key],'cadastro',$form);$accounts[$key]=accountByEmail($email);
        verify($r['status']===303 && $accounts[$key] && password_verify($pass,$accounts[$key]['senha_hash']),'cadastro válido persistido: '.$key);
        if($role==='psicologo'){
            verify(http($clients[$key],'view/dashboardPsicologo.php')['status']===303,'profissional pendente bloqueado');
            query("UPDATE usuarios SET status='ativo' WHERE id=?",[$accounts[$key]['id']]);
            query("UPDATE psicologos SET status_verificacao='aprovado' WHERE usuario_id=?",[$accounts[$key]['id']]);
            sendForm($clients[$key],'login',['email'=>$email,'senha'=>$pass]);
        }
        $dash=htmlPage($clients[$key],$role==='paciente'?'dashboardPaciente.php':'dashboardPsicologo.php');
        verify(!preg_match('/Ana Martins|Camila Souza|Bruno Santos|Rafael Lima|00:18:42/',$dash),'dashboard sem demonstrações: '.$key);
        verify(str_contains($dash,'Olá, '.strtoupper($key).'!'),'saudação usa primeiro nome: '.$key);
        if($role==='paciente') {
            verify(str_contains($dash,'Você ainda não possui consultas agendadas.') && str_contains($dash,'<strong>0</strong><span>consultas realizadas</span>'),'indicadores zerados para novo paciente');
            verify(str_contains(htmlPage($clients[$key],'pagamentosPaciente.php'),'Você ainda não possui pagamentos registrados.'),'pagamentos vazios');
            verify(str_contains(htmlPage($clients[$key],'prontuarioPaciente.php'),'Nenhum atendimento realizado até o momento.'),'histórico vazio');
        } else verify(str_contains($dash,'Nenhum horário disponível cadastrado.'),'dashboard profissional vazio');
    }
    $p1=(int)$accounts['p1']['id'];$p2=(int)$accounts['p2']['id'];$s1=(int)$accounts['s1']['id'];$s2=(int)$accounts['s2']['id'];
    $private1='CLINICO-PRIVADO-1-'.$tag;$private2='CLINICO-PRIVADO-2-'.$tag;
    $shared1='RESUMO-COMPARTILHADO-1-'.$tag;$shared2='RESUMO-COMPARTILHADO-2-'.$tag;
    $next1=appointment($p1,$s1,$utc->modify('+2 days')->format('Y-m-d H:i:s'),'confirmada','137.42');
    $past1=appointment($p1,$s1,$utc->modify('-2 days')->format('Y-m-d H:i:s'),'concluida','137.42');
    $next2=appointment($p2,$s2,$utc->modify('+3 days')->format('Y-m-d H:i:s'),'confirmada','283.91');
    $past2=appointment($p2,$s2,$utc->modify('-3 days')->format('Y-m-d H:i:s'),'concluida','283.91');
    foreach([[$p1,$s1,$past1,$private1,$shared1,'137.42'],[$p2,$s2,$past2,$private2,$shared2,'283.91']] as [$patient,$psych,$consultation,$private,$shared,$amount]){
        $chart=fixture('prontuarios',['paciente_id'=>$patient,'psicologo_id'=>$psych]);
        fixture('registros_prontuario',['prontuario_id'=>$chart,'consulta_id'=>$consultation,'observacoes'=>$private]);
        fixture('resumos_compartilhados',['consulta_id'=>$consultation,'texto'=>$shared,'compartilhado_por_psicologo_id'=>$psych]);
        fixture('pagamentos',['consulta_id'=>$consultation,'valor'=>$amount,'metodo'=>'pix','status'=>'aprovado','pago_em'=>$utc->format('Y-m-d H:i:s')]);
        fixture('notificacoes',['usuario_id'=>$patient,'titulo'=>'Aviso '.$patient,'mensagem'=>'NOTIFICACAO-'.$patient.'-'.$tag]);
        fixture('avaliacoes',['consulta_id'=>$consultation,'paciente_id'=>$patient,'psicologo_id'=>$psych,'nota'=>5,'publicada'=>1]);
    }
    fixture('metodos_pagamento',['paciente_id'=>$p1,'provedor'=>'fixture','referencia_token'=>bin2hex(random_bytes(16)),'ultimos_quatro'=>'7381','bandeira'=>'TESTE']);
    fixture('disponibilidades_semanais',['psicologo_id'=>$s1,'dia_semana'=>1,'hora_inicio'=>'09:10:00','hora_fim'=>'11:20:00']);
    $slot=fixture('horarios_agenda',['psicologo_id'=>$s1,'inicio_em'=>$utc->modify('+5 days')->format('Y-m-d H:i:s'),'fim_em'=>$utc->modify('+5 days +50 minutes')->format('Y-m-d H:i:s'),'status'=>'livre']);
    $blocked=fixture('horarios_agenda',['psicologo_id'=>$s1,'inicio_em'=>$utc->modify('+6 days')->format('Y-m-d H:i:s'),'fim_em'=>$utc->modify('+6 days +50 minutes')->format('Y-m-d H:i:s'),'status'=>'livre']);
    fixture('bloqueios_agenda',['psicologo_id'=>$s1,'inicio_em'=>$utc->modify('+6 days -1 minute')->format('Y-m-d H:i:s'),'fim_em'=>$utc->modify('+6 days +1 hour')->format('Y-m-d H:i:s'),'motivo'=>'BLOQUEIO-'.$tag]);
    $m1=new MindlyData($accounts['p1']);$m2=new MindlyData($accounts['p2']);
    verify(count($m1->consultations())===2 && count($m2->consultations())===2,'consultas individualizadas no Model');
    verify(count($m1->slots($s1))===1 && (int)$m1->slots($s1)[0]['id']===$slot,'horários bloqueados não são anunciados como livres');
    foreach(['p1','p2'] as $key){
        $client=$clients[$key];$own=$key==='p1'?$shared1:$shared2;$other=$key==='p1'?$shared2:$shared1;
        $dash=htmlPage($client,'dashboardPaciente.php');
        verify(str_contains($dash,'<strong>1</strong><span>consultas agendadas</span>') && str_contains($dash,'<strong>1</strong><span>consultas realizadas</span>'),'dashboard calcula dados reais: '.$key);
        $history=htmlPage($client,'prontuarioPaciente.php');
        verify(str_contains($history,$own) && !str_contains($history,$other) && !str_contains($history,$private1) && !str_contains($history,$private2),'histórico exibe só resumo autorizado: '.$key);
        $payments=htmlPage($client,'pagamentosPaciente.php');
        verify(str_contains($payments,$key==='p1'?'137,42':'283,91') && !str_contains($payments,$key==='p1'?'283,91':'137,42'),'pagamentos individualizados: '.$key);
        if($key==='p2') verify(!str_contains($payments,'7381'),'cartão do primeiro paciente não vaza');
        $otherId=$key==='p1'?$next2:$next1;
        foreach(['pagamentoConsulta.php','salaAtendimento.php'] as $page) verify(http($client,'view/'.$page.'?consulta_id='.$otherId)['status']===404,'IDOR bloqueado: '.$key.' '.$page);
        verify(http($client,'view/salaAtendimento.php?consulta_id[]=1')['status']===404,'parâmetro de ID inválido recusado');
        verify(str_contains(htmlPage($client,'dashboardPaciente.php'),$own)===false,'resumo clínico não aparece fora do histórico');
        sendForm($client,'logout');sendForm($client,'login',['email'=>$accounts[$key]['email'],'senha'=>$pass]);
        verify(str_contains(htmlPage($client,'prontuarioPaciente.php'),$own),'dados persistem após logout/login: '.$key);
    }
    foreach(['s1','s2'] as $key){
        $ownPatient=$key==='s1'?$p1:$p2;$otherPatient=$key==='s1'?$p2:$p1;$ownPrivate=$key==='s1'?$private1:$private2;$otherPrivate=$key==='s1'?$private2:$private1;
        $html=htmlPage($clients[$key],'prontuarioPsicologo.php?paciente_id='.$ownPatient);
        verify(str_contains($html,$ownPrivate) && !str_contains($html,$otherPrivate),'prontuário restrito à dupla: '.$key);
        verify(http($clients[$key],'view/prontuarioPsicologo.php?paciente_id='.$otherPatient)['status']===404,'psicólogo não acessa prontuário de outro');
        verify(http($clients[$key],'view/salaAtendimentoPsicologo.php?consulta_id='.($key==='s1'?$next2:$next1))['status']===404,'sala exige psicólogo participante');
        $dash=htmlPage($clients[$key],'dashboardPsicologo.php');
        verify(str_contains($dash,$accounts[$key==='s1'?'p1':'p2']['nome']) && !str_contains($dash,$accounts[$key==='s1'?'p2':'p1']['nome']),'dashboard profissional individual: '.$key);
    }
    verify(str_contains(htmlPage($clients['s1'],'disponibilidadePsicologo.php'),'09:10') && !str_contains(htmlPage($clients['s2'],'disponibilidadePsicologo.php'),'BLOQUEIO-'.$tag),'disponibilidade e bloqueios individuais');
    verify(str_contains(htmlPage($clients['p1'],'perfilPsicologo.php?psicologo_id='.$s1),'1 avaliações'),'avaliações reais no perfil público');
    query("UPDATE psicologos SET status_verificacao='pendente' WHERE usuario_id=?",[$s2]);
    verify(http($clients['p1'],'view/perfilPsicologo.php?psicologo_id='.$s2)['status']===404,'perfil público pendente oculto');
    query("UPDATE psicologos SET status_verificacao='aprovado' WHERE usuario_id=?",[$s2]);
    // Administrador de teste, sem credencial fixa e removido no encerramento.
    $adminEmail='admin-'.$tag.'@example.invalid';$emails[]=$adminEmail;
    fixture('usuarios',['nome'=>'Admin '.$tag,'email'=>$adminEmail,'senha_hash'=>password_hash($pass,PASSWORD_DEFAULT),'papel'=>'admin','status'=>'ativo']);
    $clients['admin']=browserClient();sendForm($clients['admin'],'login',['email'=>$adminEmail,'senha'=>$pass]);
    foreach(glob(__DIR__.'/../view/*.php') as $file){
        $source=file_get_contents($file);if(!preg_match("/requireRole\('([^']+)'\)/",$source,$match)) continue;
        $key=['paciente'=>'p1','psicologo'=>'s1','admin'=>'admin'][$match[1]];
        $body=htmlPage($clients[$key],basename($file));
        verify(!str_contains($body,$private2) && !str_contains($body,$shared2),'sem vazamento entre contas: '.basename($file));
        if($match[1]==='admin') verify(!str_contains($body,$private1) && !str_contains($body,$shared1),'admin sem conteúdo clínico: '.basename($file));
    }
    // Mesmo navegador alternando paciente: não pode manter dados da sessão anterior.
    sendForm($clients['p1'],'logout');sendForm($clients['p1'],'login',['email'=>$accounts['p2']['email'],'senha'=>$pass]);
    $body=htmlPage($clients['p1'],'dashboardPaciente.php');
    verify(str_contains($body,'NOTIFICACAO-'.$p2.'-'.$tag) && !str_contains($body,'NOTIFICACAO-'.$p1.'-'.$tag),'notificações não permanecem ao trocar usuário');
    verify(str_contains(htmlPage($clients['p1'],'prontuarioPaciente.php'),$shared2),'mesmo navegador carrega histórico da nova conta');
    echo "Todos os testes de dados reais passaram.\n";
} finally {
    // Ordem das FKs; apenas IDs que este processo criou.
    foreach(['avaliacoes','notificacoes','resumos_compartilhados','registros_prontuario','prontuarios','pagamentos','metodos_pagamento','consultas','horarios_agenda','disponibilidades_semanais','bloqueios_agenda'] as $table){
        foreach($created[$table] ?? [] as $id) query("DELETE FROM $table WHERE id=?",[$id]);
    }
    foreach($emails as $email){$u=accountByEmail($email);if(!$u) continue;
        foreach(['consentimentos','pacientes','psicologos'] as $table) query("DELETE FROM $table WHERE usuario_id=?",[$u['id']]);
        query('DELETE FROM usuarios WHERE id=?',[$u['id']]);
    }
    verify((int)query('SELECT COUNT(*) FROM usuarios')->fetchColumn()===$original,'contas reais preservadas; fixtures removidas');
}
