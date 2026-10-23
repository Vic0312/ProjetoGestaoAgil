<?php
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/ProfissionalController.php';
require_once __DIR__ . '/../model/PatientProfile.php';

final class PacienteController
{
    public static function pagamento(array $user): void
    {
        (new ProfessionalWorkflow($user))->simulatePayment(ProfessionalWorkflow::integer($_POST, 'consulta_id'), $_POST);
        flash('Pagamento simulado registrado com sucesso. Consulta confirmada.');
    }
    public static function perfil(array $user): void
    {
        $previous = query('SELECT avatar_url FROM usuarios WHERE id=?', [$user['id']])->fetchColumn();
        $photo = null;
        try {
            $photo = ProfissionalController::upload();
            (new PatientProfile($user))->save($_POST, $photo);
        } catch (Throwable $error) {
            if ($photo !== null) ProfissionalController::removeOwnedAvatar($photo);
            throw $error;
        }
        if ($photo !== null) ProfissionalController::removeOwnedAvatar(is_string($previous) ? $previous : null);
        unset($_SESSION['operacao_anterior']);
        flash('Perfil atualizado com sucesso.');
    }
    public static function avaliar(array $user): void
    {
        (new PatientProfile($user))->rateConsultation($_POST);
        flash("Avalia\u{00E7}\u{00E3}o enviada com sucesso.");
    }
}
