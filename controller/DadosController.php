<?php
require_once __DIR__ . '/../model/MindlyData.php';
function requestText(string $key): string { return isset($_GET[$key]) && is_string($_GET[$key]) ? mb_substr(trim($_GET[$key]),0,190) : ''; }
function requestId(string $key): ?int {
    if (!isset($_GET[$key])) return null;
    $value=filter_var($_GET[$key],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
    if ($value===false) { http_response_code(404); exit('Registro não encontrado.'); }
    return $value;
}
function mindlyTime(?string $date=null): DateTimeImmutable {
    return (new DateTimeImmutable($date ?: 'now',new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Sao_Paulo'));
}
function upcoming(array $rows): array {
    return array_values(array_filter($rows,fn($c)=>in_array($c['status'],['aguardando_pagamento','confirmada','em_andamento'],true) && ($c['status']==='em_andamento' || mindlyTime($c['inicio_em'])>=mindlyTime())));
}
function completed(array $rows): array { return array_values(array_filter($rows,fn($c)=>$c['status']==='concluida')); }
function pageData(string $page,array $user): array {
    $model=new MindlyData($user);
    $d=['notificacoes'=>$model->notifications(),'consulta'=>null,'profissional'=>null,'horarios'=>[], 'busca'=>requestText('q')];
    if (in_array($page,['dashboardPaciente.php','dashboardPsicologo.php','dashboardAdmin.php','minhasConsultas.php','agendaPsicologo.php','atendimentosAdmin.php','relatoriosAdmin.php','pagamentosPaciente.php'],true)) {
        $d['consultas']=$model->consultations();
        $d['proximas']=upcoming($d['consultas']); $d['realizadas']=completed($d['consultas']);
        $d['proxima']=$d['proximas'][0] ?? null;
        $d['hoje']=array_values(array_filter($d['consultas'],fn($c)=>mindlyTime($c['inicio_em'])->format('Y-m-d')===mindlyTime()->format('Y-m-d')));
    }
    switch ($page) {
        case 'dashboardPsicologo.php':
            $d['pacientes']=$model->patients(); $d['horarios']=$model->slots((int)$user['id']);
            $d['pagamentos']=$model->payments();
            $d['recebido']=array_sum(array_map(fn($p)=>$p['status']==='aprovado' && $p['moeda']==='BRL' && $p['pago_em'] && mindlyTime($p['pago_em'])->format('Y-m')===mindlyTime()->format('Y-m') ? max(0,(float)$p['valor']-(float)$p['reembolsado']):0,$d['pagamentos']));
            break;
        case 'perfilPaciente.php': case 'perfilProfissional.php':
            $d['perfil']=$model->profile();
            if($page==='perfilProfissional.php'){$d['opcoesEspecialidades']=$model->specialtyOptions();$d['selecionadas']=$model->selectedSpecialties();}
            break;
        case 'buscarPsicologos.php':
            $d['especialidade']=requestText('especialidade'); $d['especialidades']=$model->specialties();
            $d['profissionais']=$model->professionals($d['busca'],$d['especialidade']);
            foreach ($d['profissionais'] as &$professional) {
                $professional['proximo_horario']=$model->slots((int)$professional['usuario_id'],1)[0]['inicio_em'] ?? null;
            }
            unset($professional);
            break;
        case 'perfilPsicologo.php': case 'agendarConsulta.php':
            $id=$page==='perfilPsicologo.php' && $user['papel']==='psicologo' ? (int)$user['id'] : requestId('psicologo_id');
            if ($id!==null) {
                $d['profissional']=$model->professional($id,$user['papel']==='psicologo');
                if (!$d['profissional']) { http_response_code(404); exit('Profissional não encontrado.'); }
                $d['horarios']=$model->slots($id);
            }
            break;
        case 'disponibilidadePsicologo.php':
            $d['perfil']=$model->profile(); $d['disponibilidade']=$model->availability(); $d['horarios']=$model->slots((int)$user['id']); break;
        case 'pacientesPsicologo.php': case 'prontuarioPsicologo.php':
            $d['pacientes']=$model->patients($page==='pacientesPsicologo.php'?$d['busca']:'');
            $d['paciente']=null; $d['registros']=[]; $d['consultasPaciente']=[]; $d['contextoClinico']=[]; $d['versoes']=[];
            $d['consultasVinculadas']=$model->consultations();
            if (in_array($page,['prontuarioPsicologo.php','pacientesPsicologo.php'],true)) {
                $id=requestId('paciente_id');
                if ($id!==null) {
                    foreach($d['pacientes'] as $p) if ((int)$p['id']===$id) $d['paciente']=$p;
                    if (!$d['paciente']) { http_response_code(404); exit('Registro não encontrado.'); }
                    $d['consultasPaciente']=array_values(array_filter($d['consultasVinculadas'],fn($c)=>(int)$c['paciente_id']===$id));
                    if($page==='prontuarioPsicologo.php'){$d['registros']=$model->records($id);$d['contextoClinico']=$model->clinicalContext($id);$d['versoes']=$model->recordVersions($id);}
                }
            }
            break;
        case 'prontuarioPaciente.php': $d['historico']=$model->sharedHistory(); break;
        case 'pagamentosPaciente.php':
            $d['pagamentos']=$model->payments();$d['metodos']=$model->paymentMethods(); break;
        case 'pagamentoConsulta.php': case 'salaAtendimento.php': case 'salaAtendimentoPsicologo.php':
            $id=requestId('consulta_id');
            if ($id!==null) {
                $d['consulta']=$model->consultations($id)[0] ?? null;
                if (!$d['consulta']) { http_response_code(404); exit('Registro não encontrado.'); }
                $d['historicoStatus']=$model->statusHistory($id);
                $d['horarios']=$model->slots((int)$d['consulta']['psicologo_id']);
            }
            break;
        case 'financeiroPsicologo.php': $d['pagamentos']=$model->payments(); $d['consultas']=$model->consultations(); break;
        case 'notificacoesPsicologo.php': $d['avisos']=$model->allNotifications(); break;
        case 'usuariosAdmin.php':
            $d['filtroPerfil']=requestText('perfil');$d['usuarios']=$model->users($d['busca'],$d['filtroPerfil']); break;
        case 'dashboardAdmin.php': case 'relatoriosAdmin.php':
            $d['usuarios']=$model->users();
            $d['totaisPerfis']=array_count_values(array_column($d['usuarios'],'papel'));
            $d['psicologosAtivos']=count(array_filter($d['usuarios'],fn($u)=>$u['papel']==='psicologo' && $u['status']==='ativo' && $u['status_verificacao']==='aprovado'));
            $month=mindlyTime()->format('Y-m');
            $d['mes']=array_values(array_filter($d['consultas'],fn($c)=>mindlyTime($c['inicio_em'])->format('Y-m')===$month));
            $d['statusMes']=array_count_values(array_column($d['mes'],'status'));
            $d['novosUsuarios']=count(array_filter($d['usuarios'],fn($u)=>mindlyTime($u['criado_em'])->format('Y-m')===$month));
            $d['grafico']=[];
            for ($i=6;$i>=0;$i--) {
                $date=mindlyTime()->modify("-$i days")->format('Y-m-d');
                $d['grafico'][$date]=count(array_filter($d['consultas'],fn($c)=>mindlyTime($c['inicio_em'])->format('Y-m-d')===$date));
            }
            break;
    }
    if ($page==='minhasConsultas.php') {
        $d['aba']=requestText('aba') ?: 'proximas';
        if(!in_array($d['aba'],['proximas','realizadas','canceladas','todas'],true)) $d['aba']='proximas';
        $d['listaConsultas']=match($d['aba']) {
            'realizadas'=>array_reverse($d['realizadas']), 'canceladas'=>array_values(array_filter($d['consultas'],fn($c)=>$c['status']==='cancelada')),
            'todas'=>array_reverse($d['consultas']), default=>$d['proximas']
        };
    }
    if ($page==='agendaPsicologo.php') {
        $d['horarios']=$model->slots((int)$user['id']);
        $start=requestText('semana');
        $date=$start ? DateTimeImmutable::createFromFormat('!Y-m-d',$start,new DateTimeZone('America/Sao_Paulo')) : mindlyTime();
        if (!$date || ($start && $date->format('Y-m-d')!==$start)) { http_response_code(400);exit('Data inválida.'); }
        $d['inicioSemana']=$date->modify('monday this week')->setTime(0,0);
        $end=$d['inicioSemana']->modify('+7 days');
        $d['semana']=array_values(array_filter($d['consultas'],fn($c)=>mindlyTime($c['inicio_em']) >= $d['inicioSemana'] && mindlyTime($c['inicio_em']) < $end));
        $d['horaInicial']=8; $d['horaFinal']=19;
        foreach($d['semana'] as $c) { $d['horaInicial']=min($d['horaInicial'],(int)mindlyTime($c['inicio_em'])->format('G')); $d['horaFinal']=max($d['horaFinal'],min(24,(int)mindlyTime($c['fim_em'])->format('G')+1)); }
    }
    $d['anterior']=$_SESSION['operacao_anterior'] ?? [];
    unset($_SESSION['operacao_anterior']);
    return $d;
}
function loadPageData(string $page,array $user): array {
    try { return pageData($page,$user); }
    catch (Throwable $error) { error_log('Mindly: falha ao consultar dados da página ('.get_class($error).').'); http_response_code(503);exit('Não foi possível carregar os dados. Tente novamente mais tarde.'); }
}
