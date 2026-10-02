<?php
require_once __DIR__ . '/../controller/PacienteController.php';
checkPost();
$user = requireRole('paciente');
if (input('acao') !== 'perfil_paciente') {
    http_response_code(400);
    exit('Operação inválida.');
}
try {
    PacienteController::perfil($user);
} catch (Throwable $error) {
    if ($error instanceof DomainException)
        flash($error->getMessage());
    else {
        error_log('Mindly: falha ao atualizar perfil do paciente (' . get_class($error) . ').');
        flash('Não foi possível salvar seu perfil. Tente novamente.');
    }
    $old = [];
    foreach (['nome', 'email', 'telefone', 'data_nascimento'] as $field)
        $old[$field] = mb_substr(input($field), 0, 190);
    $_SESSION['operacao_anterior'] = ['acao' => 'perfil_paciente', 'dados' => $old];
}
redirect('view/perfilPaciente.php');
