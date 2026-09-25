<?php
require_once __DIR__ . '/Auth.php';
class Controlador {
    public static function cadastro(): void {
        $name = trim(input('nome')); $email = strtolower(trim(input('email'))); $role = input('papel');
        if (mb_strlen($name)<2 || mb_strlen($name)>150) throw new DomainException('Informe um nome entre 2 e 150 caracteres.');
        if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>190) throw new DomainException('Informe um e-mail válido.');
        validatePassword(input('senha'),input('confirmarSenha'));
        if (input('termos') !== 'on') throw new DomainException('Aceite os Termos de Uso e a Política de Privacidade.');
        if (!in_array($role,['paciente','psicologo'],true)) throw new DomainException('Selecione um perfil válido.');
        $crp = trim(input('crp')); $professional = trim(input('nome_profissional')); $area = trim(input('area_atuacao'));
        if ($role === 'psicologo') {
            if (!preg_match('~^[0-9]{2}/[0-9]{4,7}$~D',$crp)) throw new DomainException('Informe o CRP no formato 00/00000. O registro será verificado separadamente.');
            if (mb_strlen($professional)>150 || mb_strlen($area)>150) throw new DomainException('Os campos profissionais devem ter até 150 caracteres.');
        }
        $hash = password_hash(input('senha'),PASSWORD_DEFAULT);
        db()->beginTransaction();
        try {
            if (accountByEmail($email)) throw new DomainException('Não foi possível cadastrar com este e-mail. Tente entrar ou recuperar a senha.');
            if ($role==='psicologo' && query('SELECT usuario_id FROM psicologos WHERE crp=?',[$crp])->fetch()) throw new DomainException('Não foi possível cadastrar com este CRP. Entre em contato com o suporte.');
            query('INSERT INTO usuarios (nome,email,senha_hash,papel,status) VALUES (?,?,?,?,?)',[$name,$email,$hash,$role,$role==='paciente'?'ativo':'pendente']);
            $id=db()->lastInsertId();
            if ($role==='paciente') query('INSERT INTO pacientes (usuario_id) VALUES (?)',[$id]);
            else query('INSERT INTO psicologos (usuario_id,crp,nome_profissional,area_atuacao,status_verificacao) VALUES (?,?,?,?,?)',[$id,$crp,$professional ?: null,$area ?: null,'pendente']);
            foreach (['termos_uso','politica_privacidade'] as $type) query('INSERT INTO consentimentos (usuario_id,tipo,versao) VALUES (?,?,?)',[$id,$type,config()['terms_version']]);
            db()->commit();
        } catch (Throwable $ex) {
            if (db()->inTransaction()) db()->rollBack();
            if ($ex instanceof PDOException && $ex->getCode()==='23000') throw new DomainException('E-mail ou CRP indisponível para cadastro.');
            throw $ex;
        }
        if ($role==='paciente') { authenticate(accountById($id)); redirect(dashboard($role)); }
        unset($_SESSION['user_id'],$_SESSION['password_stamp']);
        flash('Cadastro recebido e aguardando validação profissional. A análise do CRP não é automática.'); redirect('view/login.php');
    }
    public static function login(): void {
        $email = strtolower(trim(input('email')));
        $user = strlen($email)<=190 ? accountByEmail($email) : false;
        // Hash de comparação sem conta associada; evita pular o custo da senha para e-mail desconhecido.
        $hash = $user ? $user['senha_hash'] : '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        $valid = password_verify(input('senha'),$hash);
        if (!$user || !$valid) throw new DomainException('E-mail ou senha inválidos.');
        if ($problem=accountProblem($user)) throw new DomainException($problem);
        query('UPDATE usuarios SET ultimo_acesso_em=UTC_TIMESTAMP() WHERE id=?',[$user['id']]);
        authenticate($user); redirect(dashboard($user['papel']));
    }
    public static function recuperar(): void {
        $email=strtolower(trim(input('email')));
        if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>190) throw new DomainException('Informe um e-mail válido.');
        $c=config();
        if (!$c['mail_enabled']) throw new DomainException('A recuperação por e-mail está indisponível no momento. Entre em contato com o suporte.');
        if (!filter_var($c['mail_from'],FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/',$c['mail_from']) || !filter_var($c['app_url'],FILTER_VALIDATE_URL)) throw new RuntimeException('Mail configuration invalid');
        $user=accountByEmail($email);
        if ($user) {
            $token=bin2hex(random_bytes(32)); $hash=hash('sha256',$token);
            query('INSERT INTO tokens_redefinicao_senha (usuario_id,token_hash,expira_em) VALUES (?,?,DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 MINUTE))',[$user['id'],$hash]);
            $link=rtrim($c['app_url'],'/').'/view/redefinirSenha.php?token='.$token;
            $sent=@mail($user['email'],'Mindly - Redefinir senha',"Para criar uma nova senha, acesse em até 30 minutos:\n".$link,"From: ".$c['mail_from']."\r\nContent-Type: text/plain; charset=UTF-8");
            if (!$sent) {
                query('DELETE FROM tokens_redefinicao_senha WHERE token_hash=?',[$hash]);
                error_log('Mindly: transporte de e-mail indisponível.');
            }
        }
        flash('Se houver uma conta para este e-mail, as orientações serão encaminhadas.'); redirect('view/redefinirSenha.php');
    }
    public static function redefinir(): void {
        validatePassword(input('senha'),input('confirmarSenha'));
        $token=input('token');
        if (!preg_match('/^[a-f0-9]{64}$/D',$token)) throw new DomainException('Link inválido ou expirado. Solicite novas orientações.');
        $hash=hash('sha256',$token);
        $candidate=query('SELECT usuario_id FROM tokens_redefinicao_senha WHERE token_hash=?',[$hash])->fetch();
        if (!$candidate) throw new DomainException('Link inválido ou expirado. Solicite novas orientações.');
        $newHash=password_hash(input('senha'),PASSWORD_DEFAULT);
        db()->beginTransaction();
        try {
            // Serializa redefinições da mesma conta mesmo com tokens diferentes.
            query('SELECT id FROM usuarios WHERE id=? FOR UPDATE',[$candidate['usuario_id']]);
            $row=query('SELECT * FROM tokens_redefinicao_senha WHERE token_hash=? AND utilizado_em IS NULL AND expira_em>UTC_TIMESTAMP() FOR UPDATE',[$hash])->fetch();
            if (!$row) throw new DomainException('Link inválido ou expirado. Solicite novas orientações.');
            query('UPDATE usuarios SET senha_hash=? WHERE id=?',[$newHash,$row['usuario_id']]);
            query('UPDATE tokens_redefinicao_senha SET utilizado_em=UTC_TIMESTAMP() WHERE usuario_id=? AND utilizado_em IS NULL',[$row['usuario_id']]);
            query('UPDATE tokens_login_persistente SET revogado_em=UTC_TIMESTAMP() WHERE usuario_id=? AND revogado_em IS NULL',[$row['usuario_id']]);
            db()->commit();
        } catch (Throwable $ex) { if(db()->inTransaction()) db()->rollBack(); throw $ex; }
        unset($_SESSION['user_id'],$_SESSION['password_stamp']);
        session_regenerate_id(true);
        flash('Senha atualizada. Entre com sua nova senha.'); redirect('view/login.php');
    }
}
