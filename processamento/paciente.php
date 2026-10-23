<?php
require_once __DIR__ . '/../controller/PacienteController.php';
checkPost();
$user = requireRole('paciente');
if (in_array(input('acao'), ['favoritar', 'desfavoritar'], true)) {
    $action = input('acao');
    $rawId = input('psicologo_id');
    $id = filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $back = input('origem') === 'perfil' && $id ? 'view/perfilPsicologo.php?psicologo_id=' . $id : 'view/buscarPsicologos.php';
    try {
        if ($id === false) throw new DomainException('Psicólogo inválido.');
        if ($action === 'favoritar') {
            $professional = query("SELECT p.usuario_id FROM psicologos p JOIN usuarios u ON u.id=p.usuario_id WHERE p.usuario_id=? AND u.papel='psicologo' AND u.status='ativo' AND p.status_verificacao='aprovado'", [$id])->fetch();
            if (!$professional || (int)$id === (int)$user['id']) throw new DomainException('Este profissional não está disponível para favoritos.');
            query('INSERT IGNORE INTO favoritos (paciente_id,psicologo_id) VALUES (?,?)', [$user['id'], $id]);
            flash('Psicólogo adicionado aos favoritos.');
        } else {
            query('DELETE FROM favoritos WHERE paciente_id=? AND psicologo_id=?', [$user['id'], $id]);
            flash('Psicólogo removido dos favoritos.');
        }
    } catch (DomainException $error) { flash($error->getMessage()); }
      catch (Throwable $error) { error_log('Mindly: falha ao atualizar favoritos (' . get_class($error) . ').'); flash('Não foi possível atualizar seus favoritos. Tente novamente.'); }
    redirect($back);
}
if (input('acao') === 'pagar_consulta') {
    try {
        PacienteController::pagamento($user);
    } catch (DomainException $error) {
        flash($error->getMessage());
    } catch (Throwable $error) {
        error_log('Mindly: falha no pagamento simulado (' . get_class($error) . ').');
        flash('Não foi possível registrar o pagamento. Tente novamente.');
    }
    redirect('view/pagamentosPaciente.php');
}
if (input('acao') === 'avaliar_consulta') {
    try {
        PacienteController::avaliar($user);
    } catch (DomainException $error) {
        flash($error->getMessage());
    } catch (Throwable $error) {
        error_log('Mindly: falha ao registrar avaliacao (' . get_class($error) . ').');
        flash("N\u{00E3}o foi poss\u{00ED}vel registrar sua avalia\u{00E7}\u{00E3}o. Tente novamente.");
    }
    redirect('view/minhasConsultas.php?aba=realizadas');
}
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
