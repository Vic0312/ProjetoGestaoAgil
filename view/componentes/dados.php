<?php
function dateLabel(?string $value,string $format='d/m/Y H:i'): string { return $value ? mindlyTime($value)->format($format) : '—'; }
function money($amount): string { return 'R$ '.number_format((float)$amount,2,',','.'); }
function initials(string $name): string {
    $parts=preg_split('/\s+/u',trim($name));
    return mb_strtoupper(mb_substr($parts[0] ?? '',0,1).(count($parts)>1 ? mb_substr(end($parts),0,1):''));
}
function statusLabel(string $status): string {
    return ['aguardando_pagamento'=>'Aguardando pagamento','confirmada'=>'Confirmada','em_andamento'=>'Em andamento','concluida'=>'Concluída','cancelada'=>'Cancelada','nao_compareceu'=>'Não compareceu','pendente'=>'Pendente','processando'=>'Processando','aprovado'=>'Aprovado','recusado'=>'Recusado','cancelado'=>'Cancelado','estornado'=>'Estornado','ativo'=>'Ativo','suspenso'=>'Suspenso','inativo'=>'Inativo','paciente'=>'Paciente','psicologo'=>'Psicólogo(a)','admin'=>'Administrador','rejeitado'=>'Rejeitado'][$status] ?? $status;
}
function emptyState(string $message): void { echo '<p class="estado-vazio" role="status">'.e($message).'</p>'; }
function unavailable(string $feature): void { echo '<p class="aviso-disponibilidade">'.e($feature).' ainda não disponível.</p>'; }
function rating(array $p): string { return $p['nota']===null ? 'Sem avaliações' : number_format((float)$p['nota'],1,',','.'); }
function publicLink(string $page,$id,string $key='psicologo_id'): string { return $page.'?'.$key.'='.rawurlencode((string)$id); }
function safeReceipt(?string $url): ?string {
    if (!$url || !filter_var($url,FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($url,PHP_URL_SCHEME)),['http','https'],true)) return null;
    return $url;
}
function professionalTags(array $p): void {
    foreach(explode(', ',$p['especialidades'] ?? '') as $tag) if($tag!=='') echo '<span class="tag">'.e($tag).'</span>';
}
