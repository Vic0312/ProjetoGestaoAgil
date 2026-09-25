<?php
require_once __DIR__ . '/../controller/ProfissionalController.php';
checkPost();
$action = input('acao');
// Papel não vem do formulário: estes são os únicos endpoints aceitos por pacientes.
$shared = ['cancelar', 'remarcar', 'ler_notificacao'];
$role = $action === 'agendar' ? 'paciente' : 'psicologo';
if (in_array($action, $shared, true)) {
    $candidate = !empty($_SESSION['user_id']) ? accountById($_SESSION['user_id']) : null;
    $role = $candidate['papel'] ?? 'psicologo';
}
$user = requireRole($role);
$allowed = ['perfil', 'intervalo', 'excluir_intervalo', 'bloqueio', 'excluir_bloqueio', 'duracao', 'gerar', 'agendar', 'confirmar', 'cancelar', 'remarcar', 'iniciar', 'concluir', 'ausencia', 'registro', 'resumo', 'ler_notificacao'];
if (!in_array($action, $allowed, true)) {
    http_response_code(400);
    exit('Operação inválida.');
}
$back = match ($action) { 'perfil' => 'view/perfilProfissional.php', 'intervalo', 'excluir_intervalo', 'bloqueio', 'excluir_bloqueio', 'duracao', 'gerar' => 'view/disponibilidadePsicologo.php', 'registro', 'resumo' => 'view/prontuarioPsicologo.php', 'agendar' => 'view/agendarConsulta.php', default => dashboard($role)};
// Volta somente a rotas conhecidas e IDs tipados; nenhuma URL arbitrária é aceita.
if (in_array($action, ['registro', 'resumo'], true) && ctype_digit(input('paciente_id')))
    $back .= '?paciente_id=' . input('paciente_id');
if ($action === 'agendar' && ctype_digit(input('psicologo_id')))
    $back .= '?psicologo_id=' . input('psicologo_id');
if (in_array($action, ['confirmar', 'cancelar', 'remarcar', 'iniciar', 'concluir', 'ausencia'], true) && ctype_digit(input('consulta_id')))
    $back = 'view/' . ($role === 'psicologo' ? 'salaAtendimentoPsicologo.php' : 'salaAtendimento.php') . '?consulta_id=' . input('consulta_id');
try {
    $back = ProfissionalController::run($user, $action);
    unset($_SESSION['operacao_anterior']);
} catch (DomainException $error) {
    flash($error->getMessage());
    // Dados clínicos e arquivos não são copiados para a sessão nem para logs.
    $fields = match ($action) { 'perfil' => ['nome_profissional', 'area_atuacao', 'biografia', 'abordagem', 'telefone', 'valor_consulta', 'duracao', 'especialidades'], 'intervalo', 'bloqueio' => ['id', 'dia', 'inicio', 'fim', 'ativa', 'motivo'], default => []};
    $old = [];
    foreach ($fields as $field)
        if (isset($_POST[$field]))
            $old[$field] = is_string($_POST[$field]) ? mb_substr($_POST[$field], 0, 10000) : ($field === 'especialidades' && is_array($_POST[$field]) ? array_slice(array_filter($_POST[$field], 'is_scalar'), 0, 50) : '');
    $_SESSION['operacao_anterior'] = ['acao' => $action, 'dados' => $old];
} catch (Throwable $error) {
    error_log('Mindly: falha na operação profissional (' . get_class($error) . ').');
    flash('Não foi possível concluir a operação. Tente novamente.');
}
redirect($back);
