<?php
require_once __DIR__ . '/../controller/Auth.php';
if (empty($_SESSION['user_id']))
    redirect('view/login.php');
$account = accountById($_SESSION['user_id']);
if (!$account) {
    http_response_code(404);
    exit;
}
requireRole($account['papel']);
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$photo = $id ? query("SELECT u.avatar_url FROM usuarios u JOIN psicologos p ON p.usuario_id=u.id WHERE u.id=? AND u.status='ativo' AND p.status_verificacao='aprovado'", [$id])->fetchColumn() : false;
if (!$photo || !preg_match('/^[a-f0-9]{48}\.(jpg|png)$/D', $photo, $m)) {
    http_response_code(404);
    exit;
}
$file = __DIR__ . '/../storage/avatars/' . $photo;
if (!is_file($file)) {
    http_response_code(404);
    exit;
}
header('Content-Type: ' . ($m[1] === 'png' ? 'image/png' : 'image/jpeg'));
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($file));
readfile($file);
