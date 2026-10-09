<?php
require_once __DIR__ . '/Auth.php';
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
        (new PatientProfile($user))->save($_POST);
        unset($_SESSION['operacao_anterior']);
        flash('Perfil atualizado com sucesso.');
    }
}
