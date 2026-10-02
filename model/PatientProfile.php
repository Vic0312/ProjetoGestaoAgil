<?php
require_once __DIR__ . '/ProfessionalWorkflow.php';

final class PatientProfile
{
    public function __construct(private array $actor)
    {
    }
    public function save(array $data): void
    {
        if ($this->actor['papel'] !== 'paciente')
            throw new DomainException('Operação não autorizada para este perfil.');
        $name = ProfessionalWorkflow::text($data, 'nome', 150, true);
        if (mb_strlen($name) < 2)
            throw new DomainException('Informe um nome entre 2 e 150 caracteres.');
        $email = strtolower(ProfessionalWorkflow::text($data, 'email', 190, true));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            throw new DomainException('Informe um e-mail válido.');
        $phone = ProfessionalWorkflow::text($data, 'telefone', 20);
        if ($phone !== '' && (!preg_match('/^[+()\d\s-]+$/D', $phone) || !preg_match('/^\d{8,15}$/D', preg_replace('/\D/', '', $phone))))
            throw new DomainException('Informe um telefone válido, com 8 a 15 dígitos.');
        $birth = ProfessionalWorkflow::text($data, 'data_nascimento', 10);
        if ($birth !== '') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $birth, new DateTimeZone('America/Sao_Paulo'));
            if (!$date || $date->format('Y-m-d') !== $birth || $birth < '1000-01-01' || $date > new DateTimeImmutable('today', new DateTimeZone('America/Sao_Paulo')))
                throw new DomainException('Informe uma data de nascimento válida, que não esteja no futuro.');
        }
        $id = (int) $this->actor['id'];
        db()->beginTransaction();
        try {
            $user = query('SELECT * FROM usuarios WHERE id=? FOR UPDATE', [$id])->fetch();
            if (!$user || $user['papel'] !== 'paciente' || $user['status'] !== 'ativo' || !query('SELECT usuario_id FROM pacientes WHERE usuario_id=? FOR UPDATE', [$id])->fetch())
                throw new DomainException('Sua conta não está disponível para esta operação.');
            if (query('SELECT id FROM usuarios WHERE email=? AND id<>?', [$email, $id])->fetch())
                throw new DomainException('Este e-mail não está disponível. Informe outro e-mail.');
            if ($user['email'] !== $email) {
                query('UPDATE usuarios SET email_verificado_em=NULL WHERE id=?', [$id]);
                query('DELETE FROM tokens_verificacao_email WHERE usuario_id=?', [$id]);
                query('UPDATE tokens_redefinicao_senha SET utilizado_em=UTC_TIMESTAMP() WHERE usuario_id=? AND utilizado_em IS NULL', [$id]);
            }
            query('UPDATE usuarios SET nome=?,email=?,telefone=? WHERE id=?', [$name, $email, $phone ?: null, $id]);
            query('UPDATE pacientes SET data_nascimento=? WHERE usuario_id=?', [$birth ?: null, $id]);
            db()->commit();
        } catch (Throwable $error) {
            if (db()->inTransaction())
                db()->rollBack();
            if ($error instanceof PDOException && $error->getCode() === '23000')
                throw new DomainException('Este e-mail não está disponível. Informe outro e-mail.');
            throw $error;
        }
    }
}
