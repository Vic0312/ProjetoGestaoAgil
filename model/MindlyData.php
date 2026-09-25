<?php
require_once __DIR__ . '/Database.php';

/** Leituras com escopo da conta já autenticada pelo controller. Nunca recebe papel da URL. */
final class MindlyData
{
    private int $id;
    private string $role;
    public function __construct(array $user)
    {
        $this->id = (int) $user['id'];
        $this->role = $user['papel'];
    }
    private function only(string $role): void
    {
        if ($this->role !== $role)
            throw new LogicException('Perfil não autorizado.');
    }
    private function scope(string $alias = 'c'): array
    {
        if ($this->role === 'admin')
            return ['1=1', []];
        $column = ['paciente' => 'paciente_id', 'psicologo' => 'psicologo_id'][$this->role] ?? null;
        if (!$column)
            throw new LogicException('Perfil inválido.');
        return ["$alias.$column=?", [$this->id]];
    }
    public function consultations(?int $id = null): array
    {
        [$where, $params] = $this->scope();
        if ($id !== null) {
            $where .= ' AND c.id=?';
            $params[] = $id;
        }
        return query("SELECT c.id,c.consulta_origem_id,c.paciente_id,c.psicologo_id,c.valor,c.modalidade,c.status,c.criado_em,c.iniciada_em,c.encerrada_em,
            h.inicio_em,h.fim_em,TIMESTAMPDIFF(MINUTE,h.inicio_em,h.fim_em) duracao,
            pa.nome paciente_nome,COALESCE(NULLIF(p.nome_profissional,''),pu.nome) psicologo_nome,p.area_atuacao,p.crp
            FROM consultas c JOIN horarios_agenda h ON h.id=c.horario_id AND h.psicologo_id=c.psicologo_id
            JOIN usuarios pa ON pa.id=c.paciente_id JOIN usuarios pu ON pu.id=c.psicologo_id
            JOIN psicologos p ON p.usuario_id=c.psicologo_id WHERE $where ORDER BY h.inicio_em,c.id", $params)->fetchAll();
    }
    public function profile(): array
    {
        if ($this->role === 'paciente')
            return query('SELECT data_nascimento FROM pacientes WHERE usuario_id=?', [$this->id])->fetch() ?: [];
        $this->only('psicologo');
        return $this->professional($this->id, true) ?: [];
    }
    private function professionalSelect(): string
    {
        return "SELECT p.usuario_id,u.avatar_url,p.crp,p.area_atuacao,p.biografia,p.abordagem,p.valor_consulta,p.duracao_padrao_minutos,p.status_verificacao,
            COALESCE(NULLIF(p.nome_profissional,''),u.nome) nome_profissional,
            (SELECT GROUP_CONCAT(e.nome ORDER BY e.nome SEPARATOR ', ') FROM psicologo_especialidades pe JOIN especialidades e ON e.id=pe.especialidade_id WHERE pe.psicologo_id=p.usuario_id) especialidades,
            (SELECT AVG(a.nota) FROM avaliacoes a JOIN consultas ac ON ac.id=a.consulta_id AND ac.psicologo_id=a.psicologo_id AND ac.paciente_id=a.paciente_id WHERE a.psicologo_id=p.usuario_id AND a.publicada=1 AND ac.status='concluida') nota,
            (SELECT COUNT(*) FROM avaliacoes a JOIN consultas ac ON ac.id=a.consulta_id AND ac.psicologo_id=a.psicologo_id AND ac.paciente_id=a.paciente_id WHERE a.psicologo_id=p.usuario_id AND a.publicada=1 AND ac.status='concluida') avaliacoes,
            (SELECT COUNT(*) FROM consultas pc WHERE pc.psicologo_id=p.usuario_id AND pc.status='concluida') atendimentos
            FROM psicologos p JOIN usuarios u ON u.id=p.usuario_id";
    }
    public function professional(int $id, bool $own = false): ?array
    {
        if ($own) {
            $this->only('psicologo');
            if ($id !== $this->id)
                throw new LogicException('Perfil não autorizado.');
        } else
            $this->only('paciente');
        $where = $own ? '' : " AND p.status_verificacao='aprovado' AND u.status='ativo'";
        return query($this->professionalSelect() . " WHERE p.usuario_id=?$where", [$id])->fetch() ?: null;
    }
    public function professionals(string $search = '', string $specialty = ''): array
    {
        $this->only('paciente');
        $params = ['%' . $search . '%', '%' . $search . '%', '%' . $search . '%'];
        $where = " WHERE p.status_verificacao='aprovado' AND u.status='ativo' AND (u.nome LIKE ? OR p.nome_profissional LIKE ? OR p.area_atuacao LIKE ?)";
        if ($specialty !== '') {
            $where .= ' AND EXISTS (SELECT 1 FROM psicologo_especialidades pe JOIN especialidades e ON e.id=pe.especialidade_id WHERE pe.psicologo_id=p.usuario_id AND e.nome=?)';
            $params[] = $specialty;
        }
        return query($this->professionalSelect() . $where . ' ORDER BY nome_profissional,p.usuario_id', $params)->fetchAll();
    }
    public function specialties(): array
    {
        return query('SELECT nome FROM especialidades ORDER BY nome')->fetchAll(PDO::FETCH_COLUMN);
    }
    public function slots(int $professional, ?int $limit = null): array
    {
        if ($this->role === 'psicologo' && $professional !== $this->id)
            throw new LogicException('Agenda não autorizada.');
        if (!in_array($this->role, ['paciente', 'psicologo'], true))
            throw new LogicException('Perfil inválido.');
        return query("SELECT h.id,h.inicio_em,h.fim_em FROM horarios_agenda h
            JOIN psicologos p ON p.usuario_id=h.psicologo_id JOIN usuarios u ON u.id=p.usuario_id
            WHERE h.psicologo_id=? AND h.status='livre' AND h.inicio_em>UTC_TIMESTAMP()
            AND p.status_verificacao='aprovado' AND u.status='ativo'
            AND NOT EXISTS (SELECT 1 FROM bloqueios_agenda b WHERE b.psicologo_id=h.psicologo_id AND b.inicio_em<h.fim_em AND b.fim_em>h.inicio_em)
            AND NOT EXISTS (SELECT 1 FROM consultas c JOIN horarios_agenda ch ON ch.id=c.horario_id WHERE c.psicologo_id=h.psicologo_id AND c.status<>'cancelada' AND ch.inicio_em<h.fim_em AND ch.fim_em>h.inicio_em)
            ORDER BY h.inicio_em" . ($limit === null ? '' : ' LIMIT ' . max(1, min(100, $limit))), [$professional])->fetchAll();
    }
    public function availability(): array
    {
        $this->only('psicologo');
        return [
            'weekly' => query('SELECT id,dia_semana,hora_inicio,hora_fim,ativa FROM disponibilidades_semanais WHERE psicologo_id=? ORDER BY dia_semana,hora_inicio', [$this->id])->fetchAll(),
            'blocks' => query('SELECT id,inicio_em,fim_em,motivo FROM bloqueios_agenda WHERE psicologo_id=? AND fim_em>UTC_TIMESTAMP() ORDER BY inicio_em', [$this->id])->fetchAll(),
        ];
    }
    public function patients(string $search = ''): array
    {
        $this->only('psicologo');
        return query("SELECT u.id,u.nome,
            (SELECT MIN(c.criado_em) FROM consultas c WHERE c.paciente_id=u.id AND c.psicologo_id=? AND c.status IN ('aguardando_pagamento','confirmada','em_andamento','concluida','cancelada','nao_compareceu')) desde,
            (SELECT COUNT(*) FROM consultas c WHERE c.paciente_id=u.id AND c.psicologo_id=? AND c.status='concluida') sessoes,
            (SELECT MIN(h.inicio_em) FROM consultas c JOIN horarios_agenda h ON h.id=c.horario_id WHERE c.paciente_id=u.id AND c.psicologo_id=? AND c.status IN ('confirmada','aguardando_pagamento') AND h.inicio_em>=UTC_TIMESTAMP()) proxima
            FROM usuarios u JOIN pacientes p ON p.usuario_id=u.id WHERE u.nome LIKE ? AND
            (EXISTS (SELECT 1 FROM consultas c WHERE c.paciente_id=u.id AND c.psicologo_id=? AND c.status IN ('aguardando_pagamento','confirmada','em_andamento','concluida','cancelada','nao_compareceu'))
            OR EXISTS (SELECT 1 FROM prontuarios pr WHERE pr.paciente_id=u.id AND pr.psicologo_id=?)) ORDER BY u.nome,u.id",
            [$this->id, $this->id, $this->id, '%' . $search . '%', $this->id, $this->id]
        )->fetchAll();
    }
    public function records(int $patient): array
    {
        $this->only('psicologo');
        return query('SELECT r.id,r.observacoes,r.criado_em,r.atualizado_em,c.id consulta_id,h.inicio_em FROM registros_prontuario r
            JOIN prontuarios pr ON pr.id=r.prontuario_id JOIN consultas c ON c.id=r.consulta_id AND c.psicologo_id=pr.psicologo_id AND c.paciente_id=pr.paciente_id
            JOIN horarios_agenda h ON h.id=c.horario_id WHERE pr.psicologo_id=? AND pr.paciente_id=? ORDER BY h.inicio_em DESC,r.id DESC', [$this->id, $patient])->fetchAll();
    }
    public function sharedHistory(): array
    {
        $this->only('paciente');
        return query("SELECT c.id,h.inicio_em,COALESCE(NULLIF(p.nome_profissional,''),u.nome) psicologo_nome,s.texto,s.compartilhado_em
            FROM consultas c JOIN horarios_agenda h ON h.id=c.horario_id JOIN psicologos p ON p.usuario_id=c.psicologo_id JOIN usuarios u ON u.id=p.usuario_id
            LEFT JOIN resumos_compartilhados s ON s.consulta_id=c.id AND s.compartilhado_por_psicologo_id=c.psicologo_id
            WHERE c.paciente_id=? AND c.status='concluida' ORDER BY h.inicio_em DESC", [$this->id])->fetchAll();
    }
    public function payments(): array
    {
        [$scope, $params] = $this->scope();
        return query("SELECT pg.id,pg.consulta_id,pg.status,pg.valor,pg.moeda,pg.metodo,pg.pago_em,pg.vencimento_em,pg.recibo_url,h.inicio_em,
            COALESCE(NULLIF(p.nome_profissional,''),u.nome) psicologo_nome,
            (SELECT COALESCE(SUM(r.valor),0) FROM reembolsos r WHERE r.pagamento_id=pg.id AND r.status='concluido') reembolsado
            FROM pagamentos pg JOIN consultas c ON c.id=pg.consulta_id JOIN horarios_agenda h ON h.id=c.horario_id
            JOIN psicologos p ON p.usuario_id=c.psicologo_id JOIN usuarios u ON u.id=p.usuario_id WHERE $scope ORDER BY pg.criado_em DESC,pg.id DESC", $params)->fetchAll();
    }
    public function paymentMethods(): array
    {
        $this->only('paciente');
        return query('SELECT bandeira,ultimos_quatro,mes_validade,ano_validade FROM metodos_pagamento WHERE paciente_id=? AND ativo=1 ORDER BY preferencial DESC,id', [$this->id])->fetchAll();
    }
    public function notifications(): array
    {
        return query('SELECT id,titulo,mensagem,criado_em FROM notificacoes WHERE usuario_id=? AND lida_em IS NULL ORDER BY criado_em DESC,id DESC', [$this->id])->fetchAll();
    }
    public function users(string $search = '', string $role = ''): array
    {
        $this->only('admin');
        $params = ['%' . $search . '%', '%' . $search . '%'];
        $where = '(u.nome LIKE ? OR u.email LIKE ?)';
        if (in_array($role, ['paciente', 'psicologo', 'admin'], true)) {
            $where .= ' AND u.papel=?';
            $params[] = $role;
        }
        return query("SELECT u.id,u.nome,u.email,u.papel,u.status,u.criado_em,p.status_verificacao FROM usuarios u LEFT JOIN psicologos p ON p.usuario_id=u.id WHERE $where ORDER BY u.criado_em DESC,u.id DESC", $params)->fetchAll();
    }
    public function specialtyOptions(): array
    {
        return query('SELECT id,nome FROM especialidades ORDER BY nome')->fetchAll();
    }
    public function selectedSpecialties(): array
    {
        $this->only('psicologo');
        return query('SELECT especialidade_id FROM psicologo_especialidades WHERE psicologo_id=?', [$this->id])->fetchAll(PDO::FETCH_COLUMN);
    }
    public function statusHistory(int $consultation): array
    {
        [$where, $params] = $this->scope();
        array_unshift($params, $consultation);
        return query("SELECT h.status_anterior,h.status_novo,h.motivo,h.criado_em FROM historico_status_consultas h JOIN consultas c ON c.id=h.consulta_id WHERE c.id=? AND $where ORDER BY h.id", $params)->fetchAll();
    }
    public function clinicalContext(int $patient): array
    {
        $this->only('psicologo');
        return query("SELECT c.id,c.paciente_id,h.inicio_em,r.id registro_id,r.observacoes,r.criado_em,r.atualizado_em,s.texto resumo,s.compartilhado_em
            FROM consultas c JOIN horarios_agenda h ON h.id=c.horario_id
            LEFT JOIN prontuarios pr ON pr.paciente_id=c.paciente_id AND pr.psicologo_id=c.psicologo_id
            LEFT JOIN registros_prontuario r ON r.consulta_id=c.id AND r.prontuario_id=pr.id
            LEFT JOIN resumos_compartilhados s ON s.consulta_id=c.id AND s.compartilhado_por_psicologo_id=c.psicologo_id
            WHERE c.psicologo_id=? AND c.paciente_id=? AND c.status='concluida' ORDER BY h.inicio_em DESC", [$this->id, $patient])->fetchAll();
    }
    public function recordVersions(int $patient): array
    {
        $this->only('psicologo');
        return query('SELECT v.id,v.registro_id,v.observacoes,v.salvo_em FROM versoes_registros_prontuario v JOIN registros_prontuario r ON r.id=v.registro_id JOIN prontuarios p ON p.id=r.prontuario_id WHERE p.psicologo_id=? AND p.paciente_id=? ORDER BY v.id DESC', [$this->id, $patient])->fetchAll();
    }
    public function allNotifications(): array
    {
        return query('SELECT id,titulo,mensagem,criado_em,lida_em FROM notificacoes WHERE usuario_id=? ORDER BY criado_em DESC,id DESC', [$this->id])->fetchAll();
    }
}
