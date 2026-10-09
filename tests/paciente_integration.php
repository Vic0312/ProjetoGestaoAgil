<?php
/** Integração real HTTP/MySQL; somente contas temporárias próprias, removidas no finally. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../model/MindlyData.php';
$base = $argv[1] ?? 'http://localhost/ProjetoGestaoAgil';
if (!in_array(parse_url($base, PHP_URL_HOST), ['localhost', '127.0.0.1', '::1'], true))
    exit("Execute somente em ambiente local.\n");
function verify(bool $ok, string $label): void
{
    if (!$ok) throw new RuntimeException($label);
    echo "OK: $label\n";
}
function client(): CurlHandle
{
    $c = curl_init();
    curl_setopt_array($c, [CURLOPT_COOKIEFILE => '', CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_TIMEOUT => 30]);
    return $c;
}
function http(CurlHandle $c, string $path, ?array $post = null): array
{
    global $base;
    curl_setopt_array($c, [CURLOPT_URL => $base . '/' . $path, CURLOPT_POST => $post !== null]);
    if ($post !== null) curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($post));
    $raw = curl_exec($c);
    if ($raw === false) throw new RuntimeException(curl_error($c));
    $size = curl_getinfo($c, CURLINFO_HEADER_SIZE);
    $body = substr($raw, $size);
    if (preg_match('/(?:Fatal error|Warning|Notice|Parse error)(?:<|:)/', $body)) throw new RuntimeException('Erro PHP: ' . $path);
    return ['status' => curl_getinfo($c, CURLINFO_HTTP_CODE), 'body' => $body, 'headers' => substr($raw, 0, $size)];
}
function page(CurlHandle $c, string $path): string
{
    $r = http($c, 'view/' . $path);
    verify($r['status'] === 200 && str_contains($r['body'], '</html>'), 'Página completa: ' . $path);
    return $r['body'];
}
function post(CurlHandle $c, string $endpoint, array $data): array
{
    $r = http($c, 'view/login.php');
    if (!preg_match('/name="csrf" value="([a-f0-9]+)"/', $r['body'], $m)) throw new RuntimeException('CSRF ausente');
    return http($c, 'processamento/' . $endpoint . '.php', $data + ['csrf' => $m[1]]);
}
function operation(CurlHandle $c, string $action, array $data): void
{
    verify(post($c, 'profissional', ['acao' => $action] + $data)['status'] === 303, 'POST ' . $action);
}
function consultation(int $id): array
{
    return query('SELECT * FROM consultas WHERE id=?', [$id])->fetch();
}
$ids = []; $users = []; $clients = []; $tag = bin2hex(random_bytes(8)); $pass = bin2hex(random_bytes(16));
try {
    foreach (['p1' => 'paciente', 'p2' => 'paciente', 's1' => 'psicologo', 's2' => 'psicologo'] as $key => $role) {
        query("INSERT INTO usuarios(nome,email,senha_hash,papel,status) VALUES (?,?,?,?,'ativo')", [$key . ' ' . $tag, "$key-$tag@example.invalid", password_hash($pass, PASSWORD_DEFAULT), $role]);
        $ids[$key] = (int) db()->lastInsertId();
        if ($role === 'paciente') query('INSERT INTO pacientes(usuario_id) VALUES (?)', [$ids[$key]]);
        else query("INSERT INTO psicologos(usuario_id,crp,status_verificacao,valor_consulta,duracao_padrao_minutos) VALUES (?,?,'aprovado',150,50)", [$ids[$key], '98/' . random_int(1000000, 9999999)]);
        $users[$key] = accountById($ids[$key]);
        $clients[$key] = client();
        verify(post($clients[$key], 'auth', ['acao' => 'login', 'email' => $users[$key]['email'], 'senha' => $pass])['status'] === 303, 'Login ' . $key);
    }
    [$p1, $p2, $s1, $s2] = array_values($ids);
    $profile = ['acao' => 'perfil_paciente', 'nome' => 'Paciente <Teste> ' . $tag, 'email' => "editado-$tag@example.invalid", 'telefone' => '(11) 99999-8888', 'data_nascimento' => '1995-03-12', 'usuario_id' => $p2];
    verify(post($clients['p1'], 'paciente', $profile)['status'] === 303, 'Salvar perfil');
    $html = page($clients['p1'], 'perfilPaciente.php');
    verify(str_contains($html, 'Perfil atualizado com sucesso.') && str_contains($html, 'Paciente &lt;Teste&gt;'), 'Sucesso e escaping do nome');
    verify(accountById($p1)['email'] === $profile['email'] && accountById($p2)['nome'] === $users['p2']['nome'], 'Atualização somente da conta logada');
    verify(query('SELECT data_nascimento FROM pacientes WHERE usuario_id=?', [$p1])->fetchColumn() === '1995-03-12', 'Nascimento persistido');
    foreach ([['nome' => 'A'], ['email' => 'invalido'], ['email' => $users['p2']['email']], ['telefone' => '--------'], ['data_nascimento' => '2025-02-30'], ['data_nascimento' => '2999-01-01'], ['nome' => ['indevido']]] as $invalid) {
        post($clients['p1'], 'paciente', array_replace($profile, $invalid));
        verify(accountById($p1)['nome'] === $profile['nome'] && accountById($p1)['email'] === $profile['email'], 'Perfil inválido não altera banco: ' . array_key_first($invalid));
        verify(!str_contains(page($clients['p1'], 'perfilPaciente.php'), 'Perfil atualizado com sucesso.'), 'Falha retorna formulário');
    }
    verify(http($clients['p1'], 'processamento/paciente.php', $profile)['status'] === 403, 'CSRF inválido recusado');
    verify(http($clients['p1'], 'processamento/paciente.php')['status'] === 405, 'Perfil recusa GET');
    verify(post($clients['s1'], 'paciente', $profile)['status'] === 403, 'Psicólogo não edita perfil paciente');
    post($clients['p1'], 'auth', ['acao' => 'logout']);
    post($clients['p1'], 'auth', ['acao' => 'login', 'email' => $profile['email'], 'senha' => $pass]);
    verify(str_contains(page($clients['p1'], 'perfilPaciente.php'), '1995-03-12'), 'Perfil persiste após novo login');

    $day = new DateTimeImmutable('+2 days', new DateTimeZone('America/Sao_Paulo'));
    foreach (['s1', 's2'] as $key) {
        operation($clients[$key], 'intervalo', ['dia' => $day->format('w'), 'inicio' => '09:00', 'fim' => '13:10', 'ativa' => '1']);
    }
    $m = new MindlyData(accountById($p1));
    $slots = $m->slots($s1); $otherSlots = $m->slots($s2);
    verify(count($slots) >= 5 && count($otherSlots) >= 5, 'Disponibilidade profissional gera horários reais');
    $html = page($clients['p1'], 'buscarPsicologos.php?q=' . $tag);
    verify(str_contains($html, $users['s1']['nome']) && str_contains($html, $users['s2']['nome']), 'Busca encontra profissionais cadastrados');
    $html = page($clients['p1'], 'agendarConsulta.php?psicologo_id=' . $s1);
    verify(str_contains($html, 'value="' . $slots[0]['id'] . '"'), 'Agenda exibe horário cadastrado');
    operation($clients['p1'], 'agendar', ['horario_id' => $slots[0]['id'], 'psicologo_id' => $s1, 'paciente_id' => $p2, 'valor' => '0']);
    $c = query('SELECT * FROM consultas WHERE paciente_id=?', [$p1])->fetch(); $original = (int) $c['id'];
    verify($c['psicologo_id'] == $s1 && $c['valor'] === '150.00' && $c['status'] === 'aguardando_pagamento', 'Reserva usa paciente da sessão e preço do banco');
    verify(query('SELECT status FROM horarios_agenda WHERE id=?', [$slots[0]['id']])->fetchColumn() === 'reservado', 'Horário reservado no banco');
    verify(!in_array($otherSlots[0]['id'], array_column($m->slots($s2), 'id')), 'Busca oculta conflito do próprio paciente com outro psicólogo');
    operation($clients['p2'], 'agendar', ['horario_id' => $slots[0]['id']]);
    operation($clients['p1'], 'agendar', ['horario_id' => $otherSlots[0]['id']]);
    verify((int) query('SELECT COUNT(*) FROM consultas WHERE paciente_id IN (?,?)', [$p1, $p2])->fetchColumn() === 1, 'Impede dupla reserva e consultas simultâneas do paciente');
    verify(http($clients['p2'], 'view/minhasConsultas.php?consulta_id=' . $original)['status'] === 404, 'Detalhes de outro paciente bloqueados');
    operation($clients['p2'], 'cancelar', ['consulta_id' => $original, 'motivo' => 'Teste']);
    operation($clients['p2'], 'remarcar', ['consulta_id' => $original, 'horario_id' => $slots[1]['id'], 'motivo' => 'Teste']);
    verify(consultation($original)['status'] !== 'cancelada', 'Outro paciente não cancela nem remarca');
    operation($clients['p1'], 'remarcar', ['consulta_id' => $original, 'horario_id' => $otherSlots[1]['id'], 'motivo' => 'Teste']);
    verify(consultation($original)['status'] !== 'cancelada', 'Remarcação exige mesmo psicólogo');
    operation($clients['p2'], 'agendar', ['horario_id' => $slots[1]['id']]);
    operation($clients['p1'], 'remarcar', ['consulta_id' => $original, 'horario_id' => $slots[1]['id'], 'motivo' => 'Teste']);
    verify(consultation($original)['status'] !== 'cancelada', 'Remarcação ocupada preserva consulta original');
    operation($clients['p1'], 'remarcar', ['consulta_id' => $original, 'horario_id' => $slots[2]['id'], 'motivo' => 'Novo horário']);
    $new = query('SELECT * FROM consultas WHERE consulta_origem_id=?', [$original])->fetch();
    verify($new && $new['horario_id'] == $slots[2]['id'] && $new['valor'] === '150.00' && consultation($original)['status'] === 'cancelada', 'Remarcação persiste nova reserva e preserva histórico/preço');
    verify(query('SELECT status FROM horarios_agenda WHERE id=?', [$slots[0]['id']])->fetchColumn() === 'livre', 'Remarcação libera horário anterior');
    $id = (int) $new['id'];
    foreach (['p1' => 'minhasConsultas.php?consulta_id=', 's1' => 'salaAtendimentoPsicologo.php?consulta_id='] as $key => $route) {
        $html = page($clients[$key], $route . $id);
        verify(str_contains($html, 'Remarcação da consulta #' . $original) && str_contains($html, 'Aguardando pagamento'), 'Remarcação refletida para ' . $key);
    }
    verify(count((new MindlyData($users['s1']))->consultations($id)) === 1 && count((new MindlyData($users['s2']))->consultations($id)) === 0, 'Consulta visível somente ao profissional correspondente');
    verify(str_contains(page($clients['p1'], 'dashboardPaciente.php'), 'Paciente &lt;Teste&gt;'), 'Dashboard identifica a sessão atual');
    verify(str_contains(page($clients['p1'], 'pagamentosPaciente.php'), 'Pagar consulta'), 'Reserva pendente aparece nas cobranças');
    verify(str_contains(page($clients['p1'], 'pagamentoConsulta.php?consulta_id=' . $id), 'Confirmar pagamento simulado'), 'Checkout funcional para consulta própria');
    $paymentData = ['acao' => 'pagar_consulta', 'consulta_id' => $id, 'confirmacao' => '1', 'valor' => '0', 'paciente_id' => $p2];
    post($clients['p2'], 'paciente', $paymentData);
    verify(!query('SELECT id FROM pagamentos WHERE consulta_id=?', [$id])->fetchColumn(), 'Outro paciente não pode pagar');
    verify(http($clients['p1'], 'processamento/paciente.php', $paymentData)['status'] === 403, 'Pagamento exige CSRF');
    post($clients['p1'], 'paciente', array_replace($paymentData, ['confirmacao' => '0']));
    verify(consultation($id)['status'] === 'aguardando_pagamento', 'Pagamento exige confirmação explícita');
    query("INSERT INTO pagamentos(consulta_id,metodo,status,valor) VALUES (?,'pix','processando',150)", [$id]);
    $processingId = (int) db()->lastInsertId();
    post($clients['p1'], 'paciente', $paymentData);
    verify(consultation($id)['status'] === 'aguardando_pagamento' && (int) query('SELECT COUNT(*) FROM pagamentos WHERE consulta_id=?', [$id])->fetchColumn() === 1, 'Pagamento em processamento impede cobrança adicional');
    query('DELETE FROM pagamentos WHERE id=?', [$processingId]);
    $schedule = query('SELECT * FROM horarios_agenda WHERE id=?', [$new['horario_id']])->fetch();
    query('UPDATE horarios_agenda SET inicio_em=?,fim_em=? WHERE id=?', [gmdate('Y-m-d H:i:s', time()-7200), gmdate('Y-m-d H:i:s', time()-3600), $schedule['id']]);
    post($clients['p1'], 'paciente', $paymentData);
    verify(!query('SELECT id FROM pagamentos WHERE consulta_id=?', [$id])->fetchColumn(), 'Consulta vencida não pode ser paga');
    query('UPDATE horarios_agenda SET inicio_em=?,fim_em=? WHERE id=?', [$schedule['inicio_em'], $schedule['fim_em'], $schedule['id']]);
    $secondSession = client();
    post($secondSession, 'auth', ['acao' => 'login', 'email' => $profile['email'], 'senha' => $pass]);
    $multiPayment = curl_multi_init();
    try {
        foreach ([$clients['p1'], $secondSession] as $session) {
            $login = http($session, 'view/login.php');
            preg_match('/name="csrf" value="([a-f0-9]+)"/', $login['body'], $token);
            curl_setopt_array($session, [CURLOPT_URL => $base . '/processamento/paciente.php', CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($paymentData + ['csrf' => $token[1]])]);
            curl_multi_add_handle($multiPayment, $session);
        }
        do {
            $result = curl_multi_exec($multiPayment, $running);
            if ($result !== CURLM_OK) throw new RuntimeException('Falha no pagamento concorrente.');
            if ($running) curl_multi_select($multiPayment, 1);
        } while ($running);
        foreach ([$clients['p1'], $secondSession] as $session) {
            verify(curl_getinfo($session, CURLINFO_HTTP_CODE) === 303, 'Pagamento simultâneo responde');
            curl_multi_remove_handle($multiPayment, $session);
        }
        $successMessage = str_contains(page($clients['p1'], 'pagamentosPaciente.php'), 'Pagamento simulado registrado com sucesso.') || str_contains(page($secondSession, 'pagamentosPaciente.php'), 'Pagamento simulado registrado com sucesso.');
    } finally { curl_multi_close($multiPayment); curl_close($secondSession); }
    verify((int) query('SELECT COUNT(*) FROM pagamentos WHERE consulta_id=?', [$id])->fetchColumn() === 1, 'Duas sessões simultâneas geram somente um pagamento');
    $payment = query('SELECT * FROM pagamentos WHERE consulta_id=?', [$id])->fetch();
    verify($payment && $payment['status'] === 'aprovado' && $payment['valor'] === '150.00' && $payment['pago_em'] && $payment['provedor'] === 'simulacao_academica', 'Pagamento persiste valor do banco, data e identificação de simulação');
    verify(consultation($id)['status'] === 'confirmada', 'Pagamento confirma consulta');
    verify($successMessage, 'Mensagem de sucesso na sessão que confirmou o pagamento');
    post($clients['p1'], 'paciente', $paymentData);
    verify((int) query('SELECT COUNT(*) FROM pagamentos WHERE consulta_id=?', [$id])->fetchColumn() === 1, 'Reenvio não duplica pagamento');
    verify(!str_contains(page($clients['p1'], 'pagamentoConsulta.php?consulta_id=' . $id), 'Confirmar pagamento simulado'), 'Consulta paga não oferece novo pagamento');
    verify(str_contains(page($clients['p1'], 'dashboardPaciente.php'), 'Confirmada'), 'Dashboard reflete pagamento');
    operation($clients['p1'], 'remarcar', ['consulta_id' => $id, 'horario_id' => $slots[4]['id'], 'motivo' => 'Crédito pago']);
    $paidReschedule = query('SELECT * FROM consultas WHERE consulta_origem_id=?', [$id])->fetch();
    verify($paidReschedule && $paidReschedule['status'] === 'confirmada', 'Remarcação preserva crédito pago');
    $paidId = (int) $paidReschedule['id'];
    post($clients['p1'], 'paciente', array_replace($paymentData, ['consulta_id' => $paidId]));
    verify(!query('SELECT id FROM pagamentos WHERE consulta_id=?', [$paidId])->fetchColumn(), 'Remarcação paga não cobra novamente');
    operation($clients['p1'], 'cancelar', ['consulta_id' => $paidId, 'motivo' => 'Teste reembolso']);
    verify(query('SELECT status FROM reembolsos WHERE pagamento_id=?', [$payment['id']])->fetchColumn() === 'solicitado', 'Cancelamento pago mantém regra de reembolso');
    // Uma nova reserva pendente exercita o cancelamento antes do pagamento.
    operation($clients['p1'], 'agendar', ['horario_id' => $slots[2]['id']]);
    $id = (int) query("SELECT id FROM consultas WHERE paciente_id=? AND status='aguardando_pagamento' ORDER BY id DESC LIMIT 1", [$p1])->fetchColumn();
    query("INSERT INTO pagamentos(consulta_id,metodo,status,valor) VALUES (?,'pix','pendente',150)", [$id]);
    operation($clients['p1'], 'cancelar', ['consulta_id' => $id, 'motivo' => 'Cancelamento de teste']);
    verify(consultation($id)['status'] === 'cancelada' && query('SELECT status FROM horarios_agenda WHERE id=?', [$slots[2]['id']])->fetchColumn() === 'livre', 'Cancelamento preservado e horário liberado');
    post($clients['p1'], 'paciente', array_replace($paymentData, ['consulta_id' => $id]));
    verify(query('SELECT status FROM pagamentos WHERE consulta_id=?', [$id])->fetchColumn() === 'cancelado', 'Cancelamento invalida cobrança pendente e impede pagamento');
    $before = (int) query('SELECT COUNT(*) FROM historico_status_consultas WHERE consulta_id=?', [$id])->fetchColumn();
    operation($clients['p1'], 'cancelar', ['consulta_id' => $id, 'motivo' => 'Reenvio']);
    verify((int) query('SELECT COUNT(*) FROM historico_status_consultas WHERE consulta_id=?', [$id])->fetchColumn() === $before, 'Reenvio de cancelamento não duplica histórico');
    verify(str_contains(page($clients['p1'], 'minhasConsultas.php?aba=canceladas'), 'Cancelada'), 'Aba canceladas');
    verify(str_contains(page($clients['s1'], 'agendaPsicologo.php'), 'Cancelada'), 'Cancelamento refletido na agenda profissional');
    // Um estado final registrado no banco deve aparecer na aba realizadas, sem inferir conclusão pelo relógio.
    $done = query('SELECT id FROM consultas WHERE paciente_id=?', [$p2])->fetchColumn();
    query("UPDATE consultas SET status='concluida' WHERE id=?", [$done]);
    verify(str_contains(page($clients['p2'], 'minhasConsultas.php?aba=realizadas'), 'Concluída'), 'Aba realizadas usa status persistido');
    // Duas sessões diferentes enviam a mesma reserva simultaneamente ao Apache.
    $multi = curl_multi_init();
    try {
        foreach (['p1', 'p2'] as $key) {
            $login = http($clients[$key], 'view/login.php');
            preg_match('/name="csrf" value="([a-f0-9]+)"/', $login['body'], $token);
            curl_setopt_array($clients[$key], [CURLOPT_URL => $base . '/processamento/profissional.php', CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query(['acao' => 'agendar', 'horario_id' => $slots[3]['id'], 'csrf' => $token[1]])]);
            curl_multi_add_handle($multi, $clients[$key]);
        }
        do {
            $status = curl_multi_exec($multi, $running);
            if ($status !== CURLM_OK) throw new RuntimeException('Falha no envio concorrente.');
            if ($running) curl_multi_select($multi, 1);
        } while ($running);
        foreach (['p1', 'p2'] as $key) {
            verify(curl_getinfo($clients[$key], CURLINFO_HTTP_CODE) === 303, 'Reserva simultânea responde: ' . $key);
            curl_multi_remove_handle($multi, $clients[$key]);
        }
    } finally { curl_multi_close($multi); }
    verify((int) query("SELECT COUNT(*) FROM consultas WHERE horario_id=? AND status<>'cancelada'", [$slots[3]['id']])->fetchColumn() === 1, 'Concorrência permite exatamente uma reserva');
    verify(http($clients['p1'], 'processamento/profissional.php', ['acao' => 'agendar', 'horario_id' => $slots[4]['id']])['status'] === 403, 'Agendamento exige CSRF');
    foreach (['dashboardPaciente.php', 'pagamentosPaciente.php', 'pagamentoConsulta.php?consulta_id=' . $id, 'prontuarioPaciente.php', 'minhasConsultas.php', 'minhasConsultas.php?aba=todas', 'minhasConsultas.php?consulta_id=' . $id, 'perfilPaciente.php', 'buscarPsicologos.php', 'perfilPsicologo.php?psicologo_id=' . $s1, 'agendarConsulta.php', 'agendarConsulta.php?psicologo_id=' . $s1] as $route) {
        $html = page($clients['p1'], $route);
        $dom = new DOMDocument(); @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        foreach ((new DOMXPath($dom))->query('//*[@href or @action or @src]') as $node) {
            foreach (['href', 'action', 'src'] as $attr) {
                $link = $node->getAttribute($attr);
                if (!$link || str_starts_with($link, '#') || str_starts_with($link, '?') || preg_match('~^(https?:|data:|mailto:)~', $link)) continue;
                $path = parse_url($link, PHP_URL_PATH);
                $file = str_starts_with($path, '/') ? __DIR__ . '/../../' . ltrim($path, '/') : __DIR__ . '/../view/' . $path;
                if (!is_file($file)) throw new RuntimeException('Destino local inexistente: ' . $link);
            }
        }
    }
    echo "Todos os testes de perfil, agendamento, remarcação, cancelamento e links passaram.\n";
} finally {
    if ($ids) {
        $marks = implode(',', array_fill(0, count($ids), '?')); $values = array_values($ids);
        $consultations = query("SELECT id FROM consultas WHERE paciente_id IN ($marks)", $values)->fetchAll(PDO::FETCH_COLUMN);
        if ($consultations) {
            $cm = implode(',', array_fill(0, count($consultations), '?'));
            query("DELETE r FROM reembolsos r JOIN pagamentos p ON p.id=r.pagamento_id WHERE p.consulta_id IN ($cm)", $consultations);
            query("DELETE FROM pagamentos WHERE consulta_id IN ($cm)", $consultations);
            foreach (['notificacoes', 'historico_status_consultas'] as $table) query("DELETE FROM $table WHERE consulta_id IN ($cm)", $consultations);
            query("UPDATE consultas SET consulta_origem_id=NULL WHERE id IN ($cm)", $consultations);
            query("DELETE FROM consultas WHERE id IN ($cm)", $consultations);
        }
        foreach (['horarios_agenda', 'disponibilidades_semanais', 'bloqueios_agenda'] as $table) query("DELETE FROM $table WHERE psicologo_id IN ($marks)", $values);
        foreach (['notificacoes', 'tokens_redefinicao_senha', 'tokens_verificacao_email', 'pacientes', 'psicologos'] as $table) query("DELETE FROM $table WHERE usuario_id IN ($marks)", $values);
        query("DELETE FROM usuarios WHERE id IN ($marks)", $values);
        echo "Dados temporários removidos.\n";
    }
}
