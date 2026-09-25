<?php
require_once __DIR__.'/Database.php';

/** Operações transacionais. Conta/proprietário sempre derivados da sessão autenticada. */
final class ProfessionalWorkflow {
    public function __construct(private array $actor) {}
    private function id(): int { return (int)$this->actor['id']; }
    private function role(string $role): void {
        if($this->actor['papel']!==$role) throw new DomainException('Operação não autorizada para este perfil.');
    }
    private function transaction(int $professional, callable $callback, ?int $patient=null): mixed {
        db()->beginTransaction();
        try {
            // Ordem global de locks evita conflitos entre reserva e gestão da mesma consulta.
            $ids=array_unique(array_filter([$professional,$patient,$this->id()]));sort($ids,SORT_NUMERIC);
            foreach($ids as $id) query('SELECT id FROM usuarios WHERE id=? FOR UPDATE',[$id])->fetch();
            $actor=accountById($this->id());
            if(!$actor || $actor['status']!=='ativo' || $actor['papel']!==$this->actor['papel'] || ($actor['papel']==='psicologo' && $actor['status_verificacao']!=='aprovado')) throw new DomainException('Sua conta não está autorizada para esta operação.');
            $p=query('SELECT p.*,u.status FROM psicologos p JOIN usuarios u ON u.id=p.usuario_id WHERE p.usuario_id=? FOR UPDATE',[$professional])->fetch();
            if(!$p || $p['status']!=='ativo' || $p['status_verificacao']!=='aprovado') throw new DomainException('Profissional indisponível.');
            $result=$callback($p);db()->commit();return $result;
        } catch(Throwable $e) { if(db()->inTransaction()) db()->rollBack();throw $e; }
    }
    public static function text(array $data,string $key,int $max,bool $required=false): string {
        $v=isset($data[$key]) && is_string($data[$key])?trim($data[$key]):'';
        if(($required && $v==='') || mb_strlen($v)>$max || str_contains($v,"\0")) throw new DomainException('Verifique o campo '.str_replace('_',' ',$key).'.');
        return $v;
    }
    public static function integer(array $data,string $key,int $min=1,int $max=PHP_INT_MAX): int {
        $v=filter_var($data[$key] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>$min,'max_range'=>$max]]);
        if($v===false) throw new DomainException('Informe um valor válido para '.str_replace('_',' ',$key).'.');return $v;
    }
    public static function cents(string $value): int {
        $value=str_replace(',','.',$value);
        if(!preg_match('/^\d{1,8}(\.\d{1,2})?$/D',$value)) throw new DomainException('Informe um valor monetário válido, com até duas casas decimais.');
        [$whole,$fraction]=array_pad(explode('.',$value),2,'');return (int)$whole*100+(int)str_pad($fraction,2,'0');
    }
    private static function decimal(int $cents): string { return sprintf('%d.%02d',intdiv($cents,100),$cents%100); }
    private static function localDate(string $value): DateTimeImmutable {
        $date=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$value,new DateTimeZone('America/Sao_Paulo'));
        if(!$date || $date->format('Y-m-d\TH:i')!==$value) throw new DomainException('Data ou horário inválido.');return $date;
    }
    private static function utc(DateTimeImmutable $date): string { return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'); }
    private function notify(array $c,string $title,string $message): void {
        foreach(array_unique([$c['paciente_id'],$c['psicologo_id']]) as $id) query("INSERT INTO notificacoes(usuario_id,consulta_id,tipo,titulo,mensagem) VALUES (?,?,'consulta',?,?)",[$id,$c['id'],$title,$message]);
    }
    private function history(array $c,?string $old,string $new,string $reason): void {
        query('INSERT INTO historico_status_consultas(consulta_id,status_anterior,status_novo,alterado_por_usuario_id,motivo) VALUES (?,?,?,?,?)',[$c['id'],$old,$new,$this->id(),$reason]);
    }
    private function overlap(int $psy,string $start,string $end,?int $exclude=null,?int $patient=null): bool {
        $params=[$psy];$scope='c.psicologo_id=?';
        if($patient!==null){$scope='('.$scope.' OR c.paciente_id=?)';$params[]=$patient;}
        array_push($params,$end,$start,$exclude ?? 0);
        return (bool)query("SELECT c.id FROM consultas c JOIN horarios_agenda h ON h.id=c.horario_id WHERE $scope AND c.status<>'cancelada' AND h.inicio_em<? AND h.fim_em>? AND c.id<>? LIMIT 1",$params)->fetch();
    }
    private function isBlocked(int $psy,string $start,string $end): bool {
        return (bool)query('SELECT id FROM bloqueios_agenda WHERE psicologo_id=? AND inicio_em<? AND fim_em>? LIMIT 1',[$psy,$end,$start])->fetch();
    }
    private function weeklyFits(int $psy,string $start,string $end,int $duration): bool {
        $a=(new DateTimeImmutable($start,new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Sao_Paulo'));
        $b=(new DateTimeImmutable($end,new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Sao_Paulo'));
        if($a->format('Y-m-d')!==$b->format('Y-m-d') || ($b->getTimestamp()-$a->getTimestamp())!==$duration*60) return false;
        $rows=query('SELECT hora_inicio,hora_fim FROM disponibilidades_semanais WHERE psicologo_id=? AND dia_semana=? AND ativa=1',[$psy,$a->format('w')])->fetchAll();
        foreach($rows as $r){$origin=new DateTimeImmutable($a->format('Y-m-d').' '.$r['hora_inicio'],$a->getTimezone()); if($a->format('H:i:s')>=$r['hora_inicio'] && $b->format('H:i:s')<=$r['hora_fim'] && ($a->getTimestamp()-$origin->getTimestamp())%($duration*60)===0) return true;}
        return false;
    }
    /** Reconcilia somente slots desocupados; limites de slots com histórico nunca mudam. */
    private function generateLocked(array $p,int $days=60): int {
        $psy=(int)$p['usuario_id'];$duration=(int)$p['duracao_padrao_minutos'];
        query("UPDATE horarios_agenda h SET h.status='bloqueado' WHERE h.psicologo_id=? AND h.inicio_em>UTC_TIMESTAMP() AND NOT EXISTS(SELECT 1 FROM consultas c WHERE c.horario_id=h.id AND c.status<>'cancelada')",[$psy]);
        $weekly=query('SELECT * FROM disponibilidades_semanais WHERE psicologo_id=? AND ativa=1',[$psy])->fetchAll();
        $today=new DateTimeImmutable('today',new DateTimeZone('America/Sao_Paulo'));$count=0;
        for($i=0;$i<$days;$i++){
            $date=$today->modify("+$i days");
            foreach($weekly as $w){if((int)$w['dia_semana']!==(int)$date->format('w')) continue;
                $cursor=new DateTimeImmutable($date->format('Y-m-d').' '.$w['hora_inicio'],$date->getTimezone());$end=new DateTimeImmutable($date->format('Y-m-d').' '.$w['hora_fim'],$date->getTimezone());
                while(($finish=$cursor->modify("+$duration minutes"))<=$end){
                    $a=self::utc($cursor);$b=self::utc($finish);$cursor=$finish;
                    if($a<=gmdate('Y-m-d H:i:s') || $this->isBlocked($psy,$a,$b) || $this->overlap($psy,$a,$b)) continue;
                    $slot=query('SELECT h.*,EXISTS(SELECT 1 FROM consultas c WHERE c.horario_id=h.id) historico FROM horarios_agenda h WHERE h.psicologo_id=? AND h.inicio_em=?',[$psy,$a])->fetch();
                    if($slot){if($slot['historico'] && $slot['fim_em']!==$b) continue;query("UPDATE horarios_agenda SET fim_em=?,status='livre' WHERE id=?",[$b,$slot['id']]);}
                    else query("INSERT INTO horarios_agenda(psicologo_id,inicio_em,fim_em,status) VALUES (?,?,?,'livre')",[$psy,$a,$b]);
                    $count++;
                }
            }
        }
        return $count;
    }
    public function generate(int $days=60): int { $this->role('psicologo');if($days<1||$days>90)throw new DomainException('Gere de 1 a 90 dias.');return $this->transaction($this->id(),fn($p)=>$this->generateLocked($p,$days)); }
    public function duration(int $minutes): void {
        $this->role('psicologo');if($minutes<20 || $minutes>180)throw new DomainException('A duração deve estar entre 20 e 180 minutos.');
        $this->transaction($this->id(),function($p)use($minutes){query('UPDATE psicologos SET duracao_padrao_minutos=? WHERE usuario_id=?',[$minutes,$this->id()]);$p['duracao_padrao_minutos']=$minutes;$this->generateLocked($p);});
    }
    public function saveProfile(array $data,?string $avatar=null): void {
        $this->role('psicologo');
        $name=self::text($data,'nome_profissional',150,true);$area=self::text($data,'area_atuacao',150);$bio=self::text($data,'biografia',10000);$approach=self::text($data,'abordagem',10000);$phone=self::text($data,'telefone',20);
        if($phone!=='' && !preg_match('/^[+()\d\s-]{8,20}$/D',$phone)) throw new DomainException('Telefone inválido.');
        $value=self::decimal(self::cents(self::text($data,'valor_consulta',20,true)));$duration=self::integer($data,'duracao',20,180);
        $specialties=$data['especialidades'] ?? [];if(!is_array($specialties) || count($specialties)>50)throw new DomainException('Especialidades inválidas.');
        foreach($specialties as $id) if(!ctype_digit((string)$id))throw new DomainException('Especialidade inválida.');
        $specialties=array_unique(array_map('intval',$specialties));
        $this->transaction($this->id(),function($p) use($data,$name,$area,$bio,$approach,$phone,$value,$duration,$specialties,$avatar){
            if(isset($data['crp']) && $data['crp']!==$p['crp'])throw new DomainException('O CRP somente pode ser alterado pelo processo de reverificação administrativa.');
            foreach($specialties as $id)if(!query('SELECT id FROM especialidades WHERE id=?',[$id])->fetch())throw new DomainException('Especialidade não encontrada.');
            query('UPDATE psicologos SET nome_profissional=?,area_atuacao=?,biografia=?,abordagem=?,valor_consulta=?,duracao_padrao_minutos=? WHERE usuario_id=?',[$name,$area?:null,$bio?:null,$approach?:null,$value,$duration,$this->id()]);
            query('UPDATE usuarios SET telefone=? WHERE id=?',[$phone?:null,$this->id()]);
            if($avatar!==null || ($data['remover_foto']??'')==='1')query('UPDATE usuarios SET avatar_url=? WHERE id=?',[$avatar,$this->id()]);
            query('DELETE FROM psicologo_especialidades WHERE psicologo_id=?',[$this->id()]);
            foreach($specialties as $id)query('INSERT INTO psicologo_especialidades(psicologo_id,especialidade_id) VALUES (?,?)',[$this->id(),$id]);
            if((int)$p['duracao_padrao_minutos']!==$duration){$p['duracao_padrao_minutos']=$duration;$this->generateLocked($p);}
        });
    }
    public function weekly(array $data,bool $delete=false): void {
        $this->role('psicologo');$id=empty($data['id'])?null:self::integer($data,'id');
        $this->transaction($this->id(),function($p) use($data,$id,$delete){
            if($id && !query('SELECT id FROM disponibilidades_semanais WHERE id=? AND psicologo_id=? FOR UPDATE',[$id,$this->id()])->fetch())throw new DomainException('Intervalo não encontrado.');
            if($delete){if(!$id)throw new DomainException('Selecione um intervalo.');query('DELETE FROM disponibilidades_semanais WHERE id=? AND psicologo_id=?',[$id,$this->id()]);}
            else {
                $day=self::integer($data,'dia',0,6);$start=self::text($data,'inicio',5,true);$end=self::text($data,'fim',5,true);$active=($data['ativa']??'')==='1'?1:0;
                if(!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D',$start) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D',$end) || $start>=$end)throw new DomainException('O início deve ser anterior ao fim, dentro do mesmo dia.');
                if(query('SELECT id FROM disponibilidades_semanais WHERE psicologo_id=? AND dia_semana=? AND id<>? AND ((hora_inicio=? AND hora_fim=?) OR (?=1 AND ativa=1 AND hora_inicio<? AND hora_fim>?))',[$this->id(),$day,$id??0,$start,$end,$active,$end,$start])->fetch())throw new DomainException('Já existe um intervalo duplicado ou sobreposto neste dia.');
                if($id)query('UPDATE disponibilidades_semanais SET dia_semana=?,hora_inicio=?,hora_fim=?,ativa=? WHERE id=? AND psicologo_id=?',[$day,$start,$end,$active,$id,$this->id()]);
                else query('INSERT INTO disponibilidades_semanais(psicologo_id,dia_semana,hora_inicio,hora_fim,ativa) VALUES (?,?,?,?,?)',[$this->id(),$day,$start,$end,$active]);
            }
            $this->generateLocked($p);
        });
    }
    public function block(array $data,bool $delete=false): void {
        $this->role('psicologo');$id=empty($data['id'])?null:self::integer($data,'id');
        $this->transaction($this->id(),function($p) use($data,$id,$delete){
            if($id && !query('SELECT id FROM bloqueios_agenda WHERE id=? AND psicologo_id=? FOR UPDATE',[$id,$this->id()])->fetch())throw new DomainException('Bloqueio não encontrado.');
            if($delete){if(!$id)throw new DomainException('Selecione um bloqueio.');query('DELETE FROM bloqueios_agenda WHERE id=? AND psicologo_id=?',[$id,$this->id()]);}
            else {
                $start=self::localDate(self::text($data,'inicio',16,true));$end=self::localDate(self::text($data,'fim',16,true));$reason=self::text($data,'motivo',255,true);
                if($start>=$end || $end->getTimestamp()<=time() || $end->getTimestamp()-$start->getTimestamp()>366*86400)throw new DomainException('Informe um período válido, com fim no futuro e até 366 dias.');
                $a=self::utc($start);$b=self::utc($end);
                if($this->overlap($this->id(),$a,$b))throw new DomainException('O bloqueio coincide com uma consulta. Cancele ou remarque o atendimento antes.');
                if(query('SELECT id FROM bloqueios_agenda WHERE psicologo_id=? AND id<>? AND inicio_em<? AND fim_em>?',[$this->id(),$id??0,$b,$a])->fetch())throw new DomainException('Este período coincide com outro bloqueio.');
                if($id)query('UPDATE bloqueios_agenda SET inicio_em=?,fim_em=?,motivo=? WHERE id=? AND psicologo_id=?',[$a,$b,$reason,$id,$this->id()]);
                else query('INSERT INTO bloqueios_agenda(psicologo_id,inicio_em,fim_em,motivo) VALUES (?,?,?,?)',[$this->id(),$a,$b,$reason]);
            }
            $this->generateLocked($p);
        });
    }
    private function validSlot(int $id,array $professional,int $patient,?int $exclude=null): array {
        $h=query('SELECT * FROM horarios_agenda WHERE id=? AND psicologo_id=? FOR UPDATE',[$id,$professional['usuario_id']])->fetch();
        if(!$h || $h['status']!=='livre' || $h['inicio_em']<=gmdate('Y-m-d H:i:s') || !$this->weeklyFits((int)$professional['usuario_id'],$h['inicio_em'],$h['fim_em'],(int)$professional['duracao_padrao_minutos']) || $this->isBlocked((int)$professional['usuario_id'],$h['inicio_em'],$h['fim_em']) || $this->overlap((int)$professional['usuario_id'],$h['inicio_em'],$h['fim_em'],$exclude,$patient))throw new DomainException('Este horário não está disponível. Atualize a agenda e escolha outro.');
        return $h;
    }
    public function book(int $slot): int {
        $this->role('paciente');$h=query('SELECT psicologo_id FROM horarios_agenda WHERE id=?',[$slot])->fetch();
        if(!$h)throw new DomainException('Horário não encontrado.');
        return $this->transaction((int)$h['psicologo_id'],function($p) use($slot){
            $h=$this->validSlot($slot,$p,$this->id());
            query("INSERT INTO consultas(horario_id,psicologo_id,paciente_id,valor,status) VALUES (?,?,?,?,'aguardando_pagamento')",[$slot,$p['usuario_id'],$this->id(),$p['valor_consulta']]);
            $id=(int)db()->lastInsertId();query("UPDATE horarios_agenda SET status='reservado' WHERE id=?",[$slot]);
            $c=['id'=>$id,'paciente_id'=>$this->id(),'psicologo_id'=>$p['usuario_id']];
            $this->history($c,null,'aguardando_pagamento','Agendamento criado.');$this->notify($c,'Novo agendamento','Uma consulta foi agendada e aguarda confirmação financeira.');return $id;
        },$this->id());
    }
    private function ownedConsultation(int $id): array {
        $column=$this->actor['papel']==='psicologo'?'psicologo_id':($this->actor['papel']==='paciente'?'paciente_id':null);
        if(!$column)throw new DomainException('Operação não autorizada.');
        $c=query("SELECT c.*,h.inicio_em,h.fim_em FROM consultas c JOIN horarios_agenda h ON h.id=c.horario_id WHERE c.id=? AND c.$column=?",[$id,$this->id()])->fetch();
        if(!$c)throw new DomainException('Consulta não encontrada.');return $c;
    }
    private function paymentChain(int $consultation): array {
        $first=$this->ownedConsultation($consultation);$ids=[];$c=$first;
        while($c){
            if(in_array((int)$c['id'],$ids,true) || count($ids)>100)throw new DomainException('Vínculo de remarcação inválido.');
            if($c['paciente_id']!==$first['paciente_id'] || $c['psicologo_id']!==$first['psicologo_id'])throw new DomainException('Vínculo financeiro não autorizado.');
            $ids[]=(int)$c['id'];$c=$c['consulta_origem_id']?$this->ownedConsultation((int)$c['consulta_origem_id']):null;
        }
        return $ids;
    }
    public function paidCents(int $consultation): int {
        $total=0;
        foreach($this->paymentChain($consultation) as $id)$total+=self::cents((string)query("SELECT COALESCE(SUM(GREATEST(0,p.valor-(SELECT COALESCE(SUM(r.valor),0) FROM reembolsos r WHERE r.pagamento_id=p.id AND r.status IN ('solicitado','processando','concluido')))),0) FROM pagamentos p WHERE p.consulta_id=? AND p.status='aprovado' AND p.moeda='BRL' AND p.pago_em IS NOT NULL",[$id])->fetchColumn());
        return $total;
    }
    public function consultationAction(int $id,string $action,array $data=[]): int {
        $initial=$this->ownedConsultation($id);
        return $this->transaction((int)$initial['psicologo_id'],function($p) use($id,$action,$data){
            query('SELECT id FROM consultas WHERE id=? FOR UPDATE',[$id])->fetch();$c=$this->ownedConsultation($id);$old=$c['status'];
            if($action==='cancelar' || $action==='remarcar'){
                if(!in_array($old,['aguardando_pagamento','confirmada'],true) || $c['inicio_em']<=gmdate('Y-m-d H:i:s'))throw new DomainException('Somente consultas futuras, aguardando pagamento ou confirmadas, podem ser canceladas ou remarcadas.');
                $reason=self::text($data,'motivo',500,true);
                if($action==='remarcar'){
                    // Crédito conciliado acompanha a cadeia de remarcações; o lançamento original é imutável.
                    foreach($this->paymentChain($id) as $source)if(query("SELECT id FROM pagamentos WHERE consulta_id=? AND status IN ('pendente','processando') LIMIT 1",[$source])->fetch())throw new DomainException('Aguarde a conciliação do pagamento pendente antes de remarcar.');
                    $h=$this->validSlot(self::integer($data,'horario_id'),$p,(int)$c['paciente_id'],$id);
                }
                query("UPDATE consultas SET status='cancelada',cancelada_em=UTC_TIMESTAMP(),motivo_cancelamento=? WHERE id=?",[$reason,$id]);
                $this->history($c,$old,'cancelada',$action==='remarcar'?'Remarcação solicitada.':'Cancelamento solicitado.');
                if($action==='cancelar') {
                    $payments=[];foreach($this->paymentChain($id) as $source)$payments=array_merge($payments,query("SELECT * FROM pagamentos WHERE consulta_id=? AND status='aprovado' FOR UPDATE",[$source])->fetchAll());
                    foreach($payments as $payment){$reserved=query("SELECT COALESCE(SUM(valor),0) FROM reembolsos WHERE pagamento_id=? AND status IN ('solicitado','processando','concluido')",[$payment['id']])->fetchColumn();$amount=self::cents($payment['valor'])-self::cents((string)$reserved);if($amount>0)query("INSERT INTO reembolsos(pagamento_id,valor,status,motivo) VALUES (?,?,'solicitado','Cancelamento da consulta; aguarda processamento financeiro.')",[$payment['id'],self::decimal($amount)]);}
                    $this->notify($c,'Consulta cancelada','A consulta foi cancelada. Valores pagos dependem de processamento de reembolso.');
                } else {
                    $newStatus=$this->paidCents($id)>=self::cents($c['valor'])?'confirmada':'aguardando_pagamento';
                    query('INSERT INTO consultas(horario_id,psicologo_id,paciente_id,consulta_origem_id,valor,status) VALUES (?,?,?,?,?,?)',[$h['id'],$c['psicologo_id'],$c['paciente_id'],$id,$c['valor'],$newStatus]);
                    $new=(int)db()->lastInsertId();query("UPDATE horarios_agenda SET status='reservado' WHERE id=?",[$h['id']]);$c['id']=$new;
                    $this->history($c,null,$newStatus,'Remarcação da consulta #'.$id.'.');$this->notify($c,'Consulta remarcada','O atendimento foi remarcado. Consulte a nova data na agenda.');
                }
                $this->generateLocked($p);return $new ?? $id;
            }
            $this->role('psicologo');
            $next=match($action){'confirmar'=>'confirmada','iniciar'=>'em_andamento','concluir'=>'concluida','ausencia'=>'nao_compareceu',default=>throw new DomainException('Transição inválida.')};
            $valid=match($action){'confirmar'=>$old==='aguardando_pagamento' && $c['fim_em']>gmdate('Y-m-d H:i:s'),'iniciar'=>$old==='confirmada' && $c['inicio_em']<=gmdate('Y-m-d H:i:s') && $c['fim_em']>gmdate('Y-m-d H:i:s'),'concluir'=>in_array($old,['confirmada','em_andamento'],true) && $c['inicio_em']<=gmdate('Y-m-d H:i:s'),'ausencia'=>$old==='confirmada' && $c['fim_em']<=gmdate('Y-m-d H:i:s'),default=>false};
            if(!$valid)throw new DomainException('Esta transição não é permitida no status ou horário atual.');
            if(in_array($action,['confirmar','iniciar','concluir'],true) && $this->paidCents($id)<self::cents($c['valor']))throw new DomainException('A consulta ainda não possui pagamento aprovado suficiente. A confirmação depende da conciliação financeira.');
            if(in_array($action,['concluir','ausencia'],true) && ($data['confirmacao']??'')!=='1')throw new DomainException('Confirme explicitamente o resultado administrativo do atendimento.');
            query('UPDATE consultas SET status=?,iniciada_em=CASE WHEN ?=\'em_andamento\' THEN UTC_TIMESTAMP() ELSE iniciada_em END,encerrada_em=CASE WHEN ? IN (\'concluida\',\'nao_compareceu\') THEN UTC_TIMESTAMP() ELSE encerrada_em END WHERE id=?',[$next,$next,$next,$id]);
            $this->history($c,$old,$next,'Atualização administrativa explícita do atendimento.');$this->notify($c,'Atendimento atualizado','O status administrativo da consulta foi atualizado para: '.$next.'.');return $id;
        },(int)$initial['paciente_id']);
    }
    public function record(array $data,bool $share=false): int {
        $this->role('psicologo');$id=self::integer($data,'consulta_id');$initial=$this->ownedConsultation($id);
        return $this->transaction($this->id(),function()use($id,$data,$share){
            query('SELECT id FROM consultas WHERE id=? FOR UPDATE',[$id]);$c=$this->ownedConsultation($id);
            if($c['status']!=='concluida')throw new DomainException('Somente atendimentos concluídos podem receber registros e resumos.');
            if($share){
                if(($data['compartilhar']??'')!=='1')throw new DomainException('Confirme que deseja compartilhar este resumo com o paciente.');
                $text=self::text($data,'resumo',10000,true);$old=query('SELECT * FROM resumos_compartilhados WHERE consulta_id=? FOR UPDATE',[$id])->fetch();
                if(!hash_equals(hash('sha256',$old['texto']??''),(string)($data['versao']??'')))throw new DomainException('O resumo foi atualizado em outra aba. Recarregue antes de salvar.');
                if($old && (int)$old['compartilhado_por_psicologo_id']!==$this->id())throw new DomainException('Resumo não autorizado.');
                if($old)query('UPDATE resumos_compartilhados SET texto=?,compartilhado_em=UTC_TIMESTAMP() WHERE id=?',[$text,$old['id']]);
                else query('INSERT INTO resumos_compartilhados(consulta_id,texto,compartilhado_por_psicologo_id) VALUES (?,?,?)',[$id,$text,$this->id()]);
            } else {
                $text=self::text($data,'observacoes',20000,true);
                query('INSERT INTO prontuarios(paciente_id,psicologo_id) VALUES (?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)',[$c['paciente_id'],$this->id()]);$pr=query('SELECT id FROM prontuarios WHERE paciente_id=? AND psicologo_id=?',[$c['paciente_id'],$this->id()])->fetchColumn();
                $old=query('SELECT * FROM registros_prontuario WHERE consulta_id=? FOR UPDATE',[$id])->fetch();
                if($old && (int)$old['prontuario_id']!==(int)$pr)throw new DomainException('Registro não autorizado.');
                if(!hash_equals(hash('sha256',$old['observacoes']??''),(string)($data['versao']??'')))throw new DomainException('O registro foi atualizado em outra aba. Recarregue antes de salvar.');
                if($old){query('INSERT INTO versoes_registros_prontuario(registro_id,autor_id,observacoes) VALUES (?,?,?)',[$old['id'],$this->id(),$old['observacoes']]);query('UPDATE registros_prontuario SET observacoes=? WHERE id=?',[$text,$old['id']]);}
                else query('INSERT INTO registros_prontuario(prontuario_id,consulta_id,observacoes) VALUES (?,?,?)',[$pr,$id,$text]);
            }
            return (int)$c['paciente_id'];
        },(int)$initial['paciente_id']);
    }
    public function markRead(int $id): void {
        if($id<1)throw new DomainException('Notificação inválida.');
        $row=query('SELECT id FROM notificacoes WHERE id=? AND usuario_id=?',[$id,$this->id()])->fetch();
        if(!$row)throw new DomainException('Notificação não encontrada.');
        query('UPDATE notificacoes SET lida_em=COALESCE(lida_em,UTC_TIMESTAMP()) WHERE id=? AND usuario_id=?',[$id,$this->id()]);
    }
}
