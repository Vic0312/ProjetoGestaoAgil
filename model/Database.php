<?php
function config(): array {
    static $config;
    if ($config === null) {
        $config = require __DIR__ . '/../config.example.php';
        $local = __DIR__ . '/../config/local.php';
        if (is_file($local)) $config = array_replace($config, require $local);
    }
    return $config;
}
function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $c = config();
        $pdo = new PDO("mysql:host={$c['db_host']};port={$c['db_port']};dbname=mindly;charset=utf8mb4", $c['db_user'], $c['db_password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
        $pdo->exec("SET time_zone = '+00:00'");
    }
    return $pdo;
}
function query(string $sql, array $params = []): PDOStatement {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}
function accountById($id) {
    return query('SELECT u.*, p.status_verificacao FROM usuarios u LEFT JOIN psicologos p ON p.usuario_id=u.id WHERE u.id=?', [$id])->fetch();
}
function accountByEmail(string $email) {
    return query('SELECT u.*, p.status_verificacao FROM usuarios u LEFT JOIN psicologos p ON p.usuario_id=u.id WHERE u.email=?', [$email])->fetch();
}
