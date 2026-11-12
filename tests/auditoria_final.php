<?php
/** Auditoria somente leitura; não altera nem identifica os dados existentes. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../model/Database.php';
$checks = [
    'Consultas com paciente, profissional ou horário ausente' => 'SELECT COUNT(*) FROM consultas c LEFT JOIN pacientes p ON p.usuario_id=c.paciente_id LEFT JOIN psicologos s ON s.usuario_id=c.psicologo_id LEFT JOIN horarios_agenda h ON h.id=c.horario_id WHERE p.usuario_id IS NULL OR s.usuario_id IS NULL OR h.id IS NULL',
    'Horários de consultas vinculados a outro profissional' => 'SELECT COUNT(*) FROM consultas c JOIN horarios_agenda h ON h.id=c.horario_id WHERE h.psicologo_id<>c.psicologo_id',
    'Horários com mais de uma consulta não cancelada' => "SELECT COUNT(*) FROM (SELECT horario_id FROM consultas WHERE status<>'cancelada' GROUP BY horario_id HAVING COUNT(*)>1) duplicates",
    'Avaliações com participante divergente ou consulta não concluída' => "SELECT COUNT(*) FROM avaliacoes a JOIN consultas c ON c.id=a.consulta_id WHERE a.paciente_id<>c.paciente_id OR a.psicologo_id<>c.psicologo_id OR c.status<>'concluida' OR a.nota NOT BETWEEN 1 AND 5",
    'Resumos compartilhados por profissional divergente' => 'SELECT COUNT(*) FROM resumos_compartilhados s JOIN consultas c ON c.id=s.consulta_id WHERE s.compartilhado_por_psicologo_id<>c.psicologo_id',
    'Registros privados vinculados à dupla divergente' => 'SELECT COUNT(*) FROM registros_prontuario r JOIN prontuarios p ON p.id=r.prontuario_id JOIN consultas c ON c.id=r.consulta_id WHERE p.paciente_id<>c.paciente_id OR p.psicologo_id<>c.psicologo_id',
];
$issues = 0;
foreach ($checks as $label => $sql) {
    $count = (int) query($sql)->fetchColumn();
    echo ($count ? 'ATENÇÃO: ' : 'OK: ') . $label . ': ' . $count . PHP_EOL;
    $issues += $count;
}
echo 'Auditoria somente leitura concluída.' . PHP_EOL;
exit($issues ? 1 : 0);
