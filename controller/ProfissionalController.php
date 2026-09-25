<?php
require_once __DIR__.'/Auth.php';
require_once __DIR__.'/../model/ProfessionalWorkflow.php';
final class ProfissionalController {
    public static function upload(): ?string {
        $file=$_FILES['foto'] ?? null;
        if(!$file || ($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return null;
        if(!is_int($file['error']) || $file['error']!==UPLOAD_ERR_OK || !is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name']) || $file['size']>2*1024*1024)throw new DomainException('Envie uma foto JPG ou PNG de até 2 MB.');
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$size=@getimagesize($file['tmp_name']);
        if(!in_array($mime,['image/jpeg','image/png'],true) || !$size || $size[0]>4096 || $size[1]>4096 || $size[0]<1 || $size[1]<1)throw new DomainException('Foto inválida. Use JPG ou PNG com até 4096 × 4096 pixels.');
        $directory=__DIR__.'/../storage/avatars';if(!is_dir($directory) && !mkdir($directory,0700,true))throw new RuntimeException('Storage unavailable');
        $name=bin2hex(random_bytes(24)).($mime==='image/png'?'.png':'.jpg');
        if(!move_uploaded_file($file['tmp_name'],$directory.'/'.$name))throw new RuntimeException('Upload failed');
        return $name;
    }
    public static function run(array $user,string $action): string {
        $model=new ProfessionalWorkflow($user);$data=$_POST;
        switch($action){
            case 'perfil':
                $photo=null;try{$photo=self::upload();$model->saveProfile($data,$photo);}catch(Throwable $e){if($photo!==null)unlink(__DIR__.'/../storage/avatars/'.$photo);throw $e;}
                flash('Perfil atualizado com sucesso.');return 'view/perfilProfissional.php';
            case 'intervalo': case 'excluir_intervalo': $model->weekly($data,$action==='excluir_intervalo');flash('Disponibilidade atualizada; consultas existentes foram preservadas.');return 'view/disponibilidadePsicologo.php';
            case 'bloqueio': case 'excluir_bloqueio': $model->block($data,$action==='excluir_bloqueio');flash('Bloqueios atualizados.');return 'view/disponibilidadePsicologo.php';
            case 'duracao': $model->duration(ProfessionalWorkflow::integer($data,'duracao',20,180));flash('Duração atualizada para novos horários.');return 'view/disponibilidadePsicologo.php';
            case 'gerar': $count=$model->generate(ProfessionalWorkflow::integer($data,'dias',1,90));flash($count.' horários livres disponíveis no período gerado.');return 'view/disponibilidadePsicologo.php';
            case 'agendar': $id=$model->book(ProfessionalWorkflow::integer($data,'horario_id'));flash('Agendamento registrado. A confirmação financeira depende de pagamento válido.');return 'view/salaAtendimento.php?consulta_id='.$id;
            case 'confirmar': case 'cancelar': case 'remarcar': case 'iniciar': case 'concluir': case 'ausencia':
                $id=$model->consultationAction(ProfessionalWorkflow::integer($data,'consulta_id'),$action,$data);flash($action==='cancelar'?'Consulta cancelada. Eventual reembolso aguarda processamento financeiro.':'Atendimento atualizado com sucesso.');return 'view/'.($user['papel']==='psicologo'?'salaAtendimentoPsicologo.php':'salaAtendimento.php').'?consulta_id='.$id;
            case 'registro': case 'resumo': $patient=$model->record($data,$action==='resumo');flash($action==='resumo'?'Resumo compartilhado explicitamente com o paciente.':'Registro clínico salvo.');return 'view/prontuarioPsicologo.php?paciente_id='.$patient;
            case 'ler_notificacao': $model->markRead(ProfessionalWorkflow::integer($data,'id'));flash('Notificação marcada como lida.');return dashboard($user['papel']);
            default: throw new DomainException('Operação inválida.');
        }
    }
}
