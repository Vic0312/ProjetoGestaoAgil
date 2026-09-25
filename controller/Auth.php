<?php
require_once __DIR__ . '/../model/Database.php';
date_default_timezone_set('UTC');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('mindly_session');
session_set_cookie_params(['lifetime'=>0, 'path'=>rtrim(config()['base_path'], '/') . '/', 'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'httponly'=>true, 'samesite'=>'Lax']);
session_start();
header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path): string { return rtrim(config()['base_path'], '/') . '/' . ltrim($path, '/'); }
function redirect(string $path): void { header('Location: ' . url($path), true, 303); exit; }
function csrf(): string { return $_SESSION['csrf'] ?? ($_SESSION['csrf'] = bin2hex(random_bytes(32))); }
function csrfField(): string { return '<input type="hidden" name="csrf" value="'.e(csrf()).'">'; }
function flash(string $message): void { $_SESSION['flash'] = $message; }
function showMessage(): void {
    if (isset($_SESSION['flash'])) { echo '<p class="mensagem-formulario" role="alert">'.e($_SESSION['flash']).'</p>'; unset($_SESSION['flash']); }
}
function input(string $key): string { return isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : ''; }
function checkPost(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit; }
    if (!hash_equals(csrf(), input('csrf'))) { http_response_code(403); exit('Formulário expirado. Recarregue a página.'); }
}
function dashboard(string $role): string {
    return ['paciente'=>'view/dashboardPaciente.php','psicologo'=>'view/dashboardPsicologo.php','admin'=>'view/dashboardAdmin.php'][$role] ?? 'view/login.php';
}
function accountProblem(array $user): ?string {
    if ($user['status'] === 'suspenso') return 'Conta suspensa. Entre em contato com o suporte.';
    if ($user['papel'] === 'psicologo' && $user['status_verificacao'] !== 'aprovado') return $user['status_verificacao'] === 'pendente' ? 'Cadastro recebido e aguardando validação profissional.' : 'Cadastro profissional não aprovado. Entre em contato com o suporte.';
    if ($user['status'] !== 'ativo') return 'Conta indisponível para acesso. Entre em contato com o suporte.';
    return null;
}
function authenticate(array $user): void {
    session_regenerate_id(true);
    $_SESSION = ['user_id'=>$user['id'], 'password_stamp'=>hash('sha256', $user['senha_hash']), 'last_activity'=>time()];
}
function logout(): void {
    $_SESSION = [];
    $p = session_get_cookie_params();
    setcookie(session_name(), '', ['expires'=>time()-3600,'path'=>$p['path'],'secure'=>$p['secure'],'httponly'=>true,'samesite'=>'Lax']);
    session_destroy();
}
function requireRole(string $role): array {
    if (empty($_SESSION['user_id'])) redirect('view/login.php');
    try { $user = accountById($_SESSION['user_id']); }
    catch (Throwable $ex) { http_response_code(503); exit('Serviço temporariamente indisponível.'); }
    if (!$user || accountProblem($user) || time()-($_SESSION['last_activity'] ?? 0)>1800 || !hash_equals($_SESSION['password_stamp'] ?? '', hash('sha256',$user['senha_hash']))) {
        logout(); redirect('view/login.php');
    }
    $_SESSION['last_activity'] = time();
    if ($user['papel'] !== $role) { http_response_code(403); exit('Acesso não autorizado para este perfil.'); }
    return $user;
}
function validatePassword(string $password, string $confirm): void {
    if (mb_strlen($password, 'UTF-8') < 8) throw new DomainException('A senha deve ter no mínimo 8 caracteres.');
    if (strlen($password)>72 || strpos($password, "\0") !== false) throw new DomainException('A senha excede o limite de 72 bytes ou contém caracteres inválidos.');
    if ($password !== $confirm) throw new DomainException('As senhas não coincidem');
}
