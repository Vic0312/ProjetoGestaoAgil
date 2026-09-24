-- ============================================================================
-- MINDLY | Banco de dados completo para o prototipo atual
-- Compatibilidade: MySQL 8.0.16+ / MariaDB 10.4+ (XAMPP)
-- Importe pelo phpMyAdmin (aba Importar) ou pelo cliente MySQL/MariaDB.
-- UTF-8, InnoDB, relacionamentos, indices e dados iniciais nao pessoais.
-- ATENCAO: execute em ambiente de desenvolvimento. Nao apaga tabelas existentes,
-- mas CREATE TABLE IF NOT EXISTS nao atualiza a estrutura de tabelas antigas.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS mindly
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mindly;

-- 1. Conta e autenticacao: senha_hash recebe password_hash() do PHP.
-- Cadastro publico: paciente ou psicologo. Admin e criado por rotina controlada.
CREATE TABLE IF NOT EXISTS usuarios (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    papel ENUM('paciente','psicologo','admin') NOT NULL,
    telefone VARCHAR(20) NULL,
    avatar_url VARCHAR(500) NULL,
    status ENUM('pendente','ativo','suspenso','inativo') NOT NULL DEFAULT 'ativo',
    email_verificado_em DATETIME NULL,
    ultimo_acesso_em DATETIME NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_usuarios_email (email),
    KEY idx_usuarios_papel_status (papel, status),
    KEY idx_usuarios_cadastro (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Complemento de dados do paciente.
CREATE TABLE IF NOT EXISTS pacientes (
    usuario_id BIGINT UNSIGNED NOT NULL,
    data_nascimento DATE NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id),
    CONSTRAINT fk_pacientes_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Dados exclusivos do profissional; aprovacao e feita por administrador.
CREATE TABLE IF NOT EXISTS psicologos (
    usuario_id BIGINT UNSIGNED NOT NULL,
    crp VARCHAR(24) NOT NULL,
    nome_profissional VARCHAR(150) NULL,
    area_atuacao VARCHAR(150) NULL,
    biografia TEXT NULL,
    abordagem TEXT NULL,
    valor_consulta DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    duracao_padrao_minutos SMALLINT UNSIGNED NOT NULL DEFAULT 50,
    status_verificacao ENUM('pendente','aprovado','rejeitado') NOT NULL DEFAULT 'pendente',
    verificado_em DATETIME NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id),
    UNIQUE KEY uk_psicologos_crp (crp),
    KEY idx_psicologos_verificacao (status_verificacao),
    CONSTRAINT fk_psicologos_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_psicologos_preco CHECK (valor_consulta >= 0),
    CONSTRAINT chk_psicologos_duracao CHECK (duracao_padrao_minutos BETWEEN 20 AND 180)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4 e 5. Especialidades exibidas em busca e perfil profissional.
CREATE TABLE IF NOT EXISTS especialidades (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_especialidades_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS psicologo_especialidades (
    psicologo_id BIGINT UNSIGNED NOT NULL,
    especialidade_id SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (psicologo_id, especialidade_id),
    KEY idx_pe_especialidade (especialidade_id),
    CONSTRAINT fk_pe_psicologo FOREIGN KEY (psicologo_id)
        REFERENCES psicologos (usuario_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_pe_especialidade FOREIGN KEY (especialidade_id)
        REFERENCES especialidades (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Faixas de trabalho recorrentes: 0=domingo ... 6=sabado.
CREATE TABLE IF NOT EXISTS disponibilidades_semanais (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    psicologo_id BIGINT UNSIGNED NOT NULL,
    dia_semana TINYINT UNSIGNED NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    ativa TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_disp_faixa (psicologo_id, dia_semana, hora_inicio, hora_fim),
    CONSTRAINT fk_disp_psicologo FOREIGN KEY (psicologo_id)
        REFERENCES psicologos (usuario_id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_disp_dia CHECK (dia_semana BETWEEN 0 AND 6),
    CONSTRAINT chk_disp_horas CHECK (hora_inicio < hora_fim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Ferias, feriados e bloqueios por horario.
CREATE TABLE IF NOT EXISTS bloqueios_agenda (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    psicologo_id BIGINT UNSIGNED NOT NULL,
    inicio_em DATETIME NOT NULL,
    fim_em DATETIME NOT NULL,
    motivo VARCHAR(255) NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_bloqueios_periodo (psicologo_id, inicio_em, fim_em),
    CONSTRAINT fk_bloqueios_psicologo FOREIGN KEY (psicologo_id)
        REFERENCES psicologos (usuario_id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_bloqueios_periodo CHECK (inicio_em < fim_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Horarios concretos para agendamento. Gerar a partir das disponibilidades
-- e descartar os que colidirem com bloqueios/agendamentos.
CREATE TABLE IF NOT EXISTS horarios_agenda (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    psicologo_id BIGINT UNSIGNED NOT NULL,
    inicio_em DATETIME NOT NULL,
    fim_em DATETIME NOT NULL,
    status ENUM('livre','reservado','bloqueado') NOT NULL DEFAULT 'livre',
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_horario_psicologo_inicio (psicologo_id, inicio_em),
    UNIQUE KEY uk_horario_id_psicologo (id, psicologo_id),
    KEY idx_horarios_busca (psicologo_id, status, inicio_em),
    CONSTRAINT fk_horarios_psicologo FOREIGN KEY (psicologo_id)
        REFERENCES psicologos (usuario_id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_horarios_periodo CHECK (inicio_em < fim_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Consulta: o valor e registrado no momento da reserva (historico de preco).
-- A coluna gerada + UNIQUE impede duas consultas nao canceladas no mesmo horario.
-- Consultas canceladas continuam no historico e liberam o horario para reutilizacao.
CREATE TABLE IF NOT EXISTS consultas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    horario_id BIGINT UNSIGNED NOT NULL,
    psicologo_id BIGINT UNSIGNED NOT NULL,
    paciente_id BIGINT UNSIGNED NOT NULL,
    consulta_origem_id BIGINT UNSIGNED NULL,
    valor DECIMAL(10,2) NOT NULL,
    modalidade ENUM('online') NOT NULL DEFAULT 'online',
    status ENUM('aguardando_pagamento','confirmada','em_andamento','concluida','cancelada','nao_compareceu')
        NOT NULL DEFAULT 'aguardando_pagamento',
    motivo_cancelamento VARCHAR(500) NULL,
    cancelada_em DATETIME NULL,
    iniciada_em DATETIME NULL,
    encerrada_em DATETIME NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    horario_ocupado_id BIGINT UNSIGNED GENERATED ALWAYS AS
        (CASE WHEN status = 'cancelada' THEN NULL ELSE horario_id END) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uk_consultas_horario_ocupado (horario_ocupado_id),
    KEY idx_consultas_paciente_status (paciente_id, status),
    KEY idx_consultas_psicologo_status (psicologo_id, status),
    KEY idx_consultas_horario_psicologo (horario_id, psicologo_id),
    KEY idx_consultas_origem (consulta_origem_id),
    CONSTRAINT fk_consultas_horario_psicologo FOREIGN KEY (horario_id, psicologo_id)
        REFERENCES horarios_agenda (id, psicologo_id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_consultas_paciente FOREIGN KEY (paciente_id)
        REFERENCES pacientes (usuario_id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_consultas_origem FOREIGN KEY (consulta_origem_id)
        REFERENCES consultas (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_consultas_valor CHECK (valor >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Historico das transicoes de status (reagendamentos criam nova reserva).
CREATE TABLE IF NOT EXISTS historico_status_consultas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    consulta_id BIGINT UNSIGNED NOT NULL,
    status_anterior VARCHAR(30) NULL,
    status_novo VARCHAR(30) NOT NULL,
    alterado_por_usuario_id BIGINT UNSIGNED NULL,
    motivo VARCHAR(500) NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_historico_consulta_data (consulta_id, criado_em),
    CONSTRAINT fk_historico_consulta FOREIGN KEY (consulta_id)
        REFERENCES consultas (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_historico_autor FOREIGN KEY (alterado_por_usuario_id)
        REFERENCES usuarios (id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Identificador de sala: acesso deve ser autorizado no PHP pelos participantes.
-- Nao armazenar gravacoes nem credenciais privadas de provedores de video.
CREATE TABLE IF NOT EXISTS salas_atendimento (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    consulta_id BIGINT UNSIGNED NOT NULL,
    codigo_sala VARCHAR(100) NOT NULL,
    provedor VARCHAR(50) NULL,
    referencia_externa VARCHAR(190) NULL,
    status ENUM('preparada','em_andamento','encerrada') NOT NULL DEFAULT 'preparada',
    iniciada_em DATETIME NULL,
    encerrada_em DATETIME NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_salas_consulta (consulta_id),
    UNIQUE KEY uk_salas_codigo (codigo_sala),
    CONSTRAINT fk_salas_consulta FOREIGN KEY (consulta_id)
        REFERENCES consultas (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Prontuario restrito a uma dupla paciente/psicologo.
CREATE TABLE IF NOT EXISTS prontuarios (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    paciente_id BIGINT UNSIGNED NOT NULL,
    psicologo_id BIGINT UNSIGNED NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_prontuario_vinculo (paciente_id, psicologo_id),
    CONSTRAINT fk_prontuario_paciente FOREIGN KEY (paciente_id)
        REFERENCES pacientes (usuario_id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_prontuario_psicologo FOREIGN KEY (psicologo_id)
        REFERENCES psicologos (usuario_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Anotacao clinica privada. Nunca exibir esta coluna na area do paciente/admin.
CREATE TABLE IF NOT EXISTS registros_prontuario (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    prontuario_id BIGINT UNSIGNED NOT NULL,
    consulta_id BIGINT UNSIGNED NOT NULL,
    observacoes TEXT NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_registro_consulta (consulta_id),
    KEY idx_registros_prontuario_data (prontuario_id, criado_em),
    CONSTRAINT fk_registro_prontuario FOREIGN KEY (prontuario_id)
        REFERENCES prontuarios (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_registro_consulta FOREIGN KEY (consulta_id)
        REFERENCES consultas (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Somente o resumo explicitamente compartilhado aparece no historico paciente.
CREATE TABLE IF NOT EXISTS resumos_compartilhados (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    consulta_id BIGINT UNSIGNED NOT NULL,
    texto TEXT NOT NULL,
    compartilhado_por_psicologo_id BIGINT UNSIGNED NOT NULL,
    compartilhado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_resumo_consulta (consulta_id),
    CONSTRAINT fk_resumo_consulta FOREIGN KEY (consulta_id)
        REFERENCES consultas (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_resumo_psicologo FOREIGN KEY (compartilhado_por_psicologo_id)
        REFERENCES psicologos (usuario_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Somente token/referencia do provedor. NUNCA salvar PAN, CVV ou CPF do cartao.
CREATE TABLE IF NOT EXISTS metodos_pagamento (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    paciente_id BIGINT UNSIGNED NOT NULL,
    provedor VARCHAR(50) NOT NULL,
    referencia_token VARCHAR(190) NOT NULL,
    bandeira VARCHAR(30) NULL,
    ultimos_quatro CHAR(4) NULL,
    mes_validade TINYINT UNSIGNED NULL,
    ano_validade SMALLINT UNSIGNED NULL,
    preferencial TINYINT(1) NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_metodo_token (provedor, referencia_token),
    KEY idx_metodos_paciente (paciente_id, ativo),
    CONSTRAINT fk_metodo_paciente FOREIGN KEY (paciente_id)
        REFERENCES pacientes (usuario_id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_metodo_mes CHECK (mes_validade IS NULL OR mes_validade BETWEEN 1 AND 12)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Uma consulta pode ter varias tentativas de pagamento, inclusive recusadas.
CREATE TABLE IF NOT EXISTS pagamentos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    consulta_id BIGINT UNSIGNED NOT NULL,
    metodo_pagamento_id BIGINT UNSIGNED NULL,
    provedor VARCHAR(50) NULL,
    referencia_externa VARCHAR(190) NULL,
    metodo ENUM('pix','cartao') NOT NULL,
    status ENUM('pendente','processando','aprovado','recusado','cancelado','estornado')
        NOT NULL DEFAULT 'pendente',
    valor DECIMAL(10,2) NOT NULL,
    moeda CHAR(3) NOT NULL DEFAULT 'BRL',
    vencimento_em DATETIME NULL,
    pago_em DATETIME NULL,
    recibo_url VARCHAR(500) NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_pagamento_referencia_externa (provedor, referencia_externa),
    KEY idx_pagamentos_consulta_status (consulta_id, status),
    KEY idx_pagamentos_criacao (criado_em),
    CONSTRAINT fk_pagamento_consulta FOREIGN KEY (consulta_id)
        REFERENCES consultas (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pagamento_metodo FOREIGN KEY (metodo_pagamento_id)
        REFERENCES metodos_pagamento (id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_pagamento_valor CHECK (valor >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Reembolsos/estornos quando suportados pela integracao de pagamento.
CREATE TABLE IF NOT EXISTS reembolsos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pagamento_id BIGINT UNSIGNED NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    status ENUM('solicitado','processando','concluido','recusado') NOT NULL DEFAULT 'solicitado',
    referencia_externa VARCHAR(190) NULL,
    motivo VARCHAR(500) NULL,
    concluido_em DATETIME NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_reembolsos_pagamento (pagamento_id, status),
    CONSTRAINT fk_reembolso_pagamento FOREIGN KEY (pagamento_id)
        REFERENCES pagamentos (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_reembolso_valor CHECK (valor > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Avaliacao de consulta; validar no PHP: paciente participante e consulta concluida.
CREATE TABLE IF NOT EXISTS avaliacoes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    consulta_id BIGINT UNSIGNED NOT NULL,
    paciente_id BIGINT UNSIGNED NOT NULL,
    psicologo_id BIGINT UNSIGNED NOT NULL,
    nota TINYINT UNSIGNED NOT NULL,
    comentario TEXT NULL,
    publicada TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_avaliacao_consulta (consulta_id),
    KEY idx_avaliacoes_psicologo (psicologo_id, publicada),
    CONSTRAINT fk_avaliacao_consulta FOREIGN KEY (consulta_id)
        REFERENCES consultas (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_avaliacao_paciente FOREIGN KEY (paciente_id)
        REFERENCES pacientes (usuario_id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_avaliacao_psicologo FOREIGN KEY (psicologo_id)
        REFERENCES psicologos (usuario_id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_avaliacao_nota CHECK (nota BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. Favoritos da tela de busca de profissionais.
CREATE TABLE IF NOT EXISTS favoritos (
    paciente_id BIGINT UNSIGNED NOT NULL,
    psicologo_id BIGINT UNSIGNED NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (paciente_id, psicologo_id),
    KEY idx_favoritos_psicologo (psicologo_id),
    CONSTRAINT fk_favorito_paciente FOREIGN KEY (paciente_id)
        REFERENCES pacientes (usuario_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_favorito_psicologo FOREIGN KEY (psicologo_id)
        REFERENCES psicologos (usuario_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. Recuperacao de senha: gravar SHA-256 do token aleatorio, nao token puro.
CREATE TABLE IF NOT EXISTS tokens_redefinicao_senha (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expira_em DATETIME NOT NULL,
    utilizado_em DATETIME NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_token_redefinicao (token_hash),
    KEY idx_tokens_usuario_expiracao (usuario_id, expira_em),
    CONSTRAINT fk_token_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. Link de verificacao do e-mail: token aleatorio guardado apenas em hash.
CREATE TABLE IF NOT EXISTS tokens_verificacao_email (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expira_em DATETIME NOT NULL,
    utilizado_em DATETIME NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_token_email (token_hash),
    KEY idx_token_email_usuario (usuario_id, expira_em),
    CONSTRAINT fk_token_email_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. Opcao "lembrar de mim": seletor publico + hash do verificador, jamais senha.
CREATE TABLE IF NOT EXISTS tokens_login_persistente (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NOT NULL,
    seletor CHAR(32) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expira_em DATETIME NOT NULL,
    revogado_em DATETIME NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_login_seletor (seletor),
    KEY idx_login_usuario_expiracao (usuario_id, expira_em),
    CONSTRAINT fk_login_persistente_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. Versoes aceitas de termos e politica de privacidade.
CREATE TABLE IF NOT EXISTS consentimentos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NOT NULL,
    tipo ENUM('termos_uso','politica_privacidade','teleatendimento') NOT NULL,
    versao VARCHAR(30) NOT NULL,
    aceito_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_consentimento_versao (usuario_id, tipo, versao),
    CONSTRAINT fk_consentimento_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24. Avisos das telas logadas; evitar detalhes clinicos nas mensagens.
CREATE TABLE IF NOT EXISTS notificacoes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NOT NULL,
    consulta_id BIGINT UNSIGNED NULL,
    tipo ENUM('consulta','pagamento','conta','sistema') NOT NULL DEFAULT 'sistema',
    titulo VARCHAR(150) NOT NULL,
    mensagem TEXT NOT NULL,
    lida_em DATETIME NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notificacoes_usuario_lida (usuario_id, lida_em, criado_em),
    CONSTRAINT fk_notificacao_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_notificacao_consulta FOREIGN KEY (consulta_id)
        REFERENCES consultas (id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 25. Rastro de acoes administrativas (sem texto de prontuario no log).
CREATE TABLE IF NOT EXISTS auditoria_administrativa (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_id BIGINT UNSIGNED NOT NULL,
    usuario_alvo_id BIGINT UNSIGNED NULL,
    acao ENUM('aprovar_psicologo','rejeitar_psicologo','suspender_usuario',
              'reativar_usuario','atualizar_usuario','outra') NOT NULL,
    detalhes VARCHAR(500) NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_auditoria_admin_data (admin_id, criado_em),
    KEY idx_auditoria_alvo_data (usuario_alvo_id, criado_em),
    CONSTRAINT fk_auditoria_admin FOREIGN KEY (admin_id)
        REFERENCES usuarios (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_auditoria_alvo FOREIGN KEY (usuario_alvo_id)
        REFERENCES usuarios (id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Valores genericos de filtro utilizados nas telas; nenhuma conta ficticia criada.
INSERT IGNORE INTO especialidades (nome) VALUES
    ('Ansiedade'),
    ('Autoconhecimento'),
    ('Relacionamentos'),
    ('Estresse');

-- ============================================================================
-- ORIENTACOES PARA IMPLEMENTACAO PHP (nao sao automatizadas pelo SQL):
-- * Cadastro: criar usuarios + pacientes/psicologos + consentimentos na mesma
--   transacao; paciente ativo, psicologo pendente ate validacao administrativa.
-- * Login: buscar por e-mail, comparar com password_verify(), verificar status,
--   papel e aprovacao do psicologo; session_regenerate_id(true) apos autenticar.
-- * Agendamento: transacao + SELECT ... FOR UPDATE no horario; verificar faixa,
--   bloqueios e sobreposicoes; criar consulta e marcar horario reservado.
-- * Remarcacao: cancelar a anterior, criar a nova e apontar consulta_origem_id.
-- * Cancelamento: mudar consulta para cancelada + liberar horario na mesma
--   transacao; registro cancelado nao deve ser apagado.
-- * Preco: usar consultas.valor para pagamentos, nao o preco atual do perfil.
-- * Pagamento: validar confirmacao no servidor com o provedor; nao confiar no
--   retorno do navegador. Cartao/Pix exigem integracao externa real.
-- * Privacidade: acesso por id da sessao; apenas psicologo responsavel acessa
--   registros_prontuario; paciente ve apenas resumos_compartilhados.
-- * Indices/UNIQUE nao substituem verificacao de papéis, sobreposicoes,
--   autorizacoes, validacao de CRP ou regras de atendimento no backend.
-- * Usar UTC no backend para DATETIME e converter para horario local na interface.
-- ============================================================================
