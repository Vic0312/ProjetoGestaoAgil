<?php
require_once __DIR__ . '/../controller/Auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    exit;
}
$type = $_GET['tipo'] ?? null;
if (!is_string($type) || !in_array($type, ['relatorios', 'prontuario'], true)) {
    http_response_code(400);
    exit('Exportação inválida.');
}
$user = requireRole($type === 'relatorios' ? 'admin' : 'paciente');
// Estes relatórios têm escopo fixo. Não aceitar filtros/IDs que seriam ignorados.
if (array_diff(array_keys($_GET), ['tipo'])) {
    http_response_code(400);
    exit('Parâmetros de exportação inválidos.');
}
require_once __DIR__ . '/../controller/ExportController.php';
try {
    $body = $type === 'relatorios' ? ExportController::report($user) : ExportController::record($user);
} catch (Throwable $error) {
    error_log('Mindly: falha ao exportar documento (' . get_class($error) . ').');
    flash('Não foi possível gerar o arquivo. Tente novamente.', 'error');
    redirect($type === 'relatorios' ? 'view/relatoriosAdmin.php' : 'view/prontuarioPaciente.php');
}
header('Content-Type: ' . ($type === 'relatorios' ? 'text/csv; charset=UTF-8' : 'application/pdf'));
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: attachment; filename="mindly-' . $type . '-' . mindlyTime()->format('Y-m-d') . ($type === 'relatorios' ? '.csv' : '.pdf') . '"');
header('Content-Length: ' . strlen($body));
echo $body;
