<?php
/**
 * Diagnóstico local do cadastro Mindly.
 * Coloque este arquivo em tests/diagnostico_cadastro_mindly.php e execute no CLI
 * com o PHP do XAMPP. Não o publique como rota web.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Execute este diagnostico somente pelo terminal.');
}

function linha(string $texto): void { echo $texto . PHP_EOL; }
function falha(Throwable $ex): void {
    $code = $ex instanceof PDOException ? (string)$ex->getCode() : get_class($ex);
    linha('ERRO [' . $code . ']: ' . $ex->getMessage());
}
function fechaTransacao(PDO $pdo): void { if ($pdo->inTransaction()) $pdo->rollBack(); }

linha('=== DIAGNOSTICO DE CADASTRO - MINDLY ===');
linha('PHP: ' . PHP_VERSION);
foreach (['pdo_mysql', 'mbstring'] as $ext) {
    linha('Extensao ' . $ext . ': ' . (extension_loaded($ext) ? 'OK' : 'AUSENTE'));
}
if (!extension_loaded('pdo_mysql') || !extension_loaded('mbstring')) {
    linha('Habilite as extensoes ausentes no php.ini usado pelo Apache/XAMPP e reinicie o Apache.');
    exit(1);
}

$databasePath = dirname(__DIR__) . '/model/Database.php';
if (!is_file($databasePath)) {
    linha('ERRO: coloque este arquivo dentro da pasta tests/ do projeto ProjetoGestaoAgil.');
    exit(1);
}
require_once $databasePath;
$c = config();
linha('Configuracao de destino: ' . $c['db_host'] . ':' . $c['db_port'] . ' / banco mindly');

try {
    $pdo = db();
    $server = $pdo->query('SELECT DATABASE() db, @@hostname server, @@port port')->fetch();
    linha('Conexao: OK');
    linha('Servidor MySQL: ' . $server['server'] . ':' . $server['port']);
    linha('Banco efetivamente selecionado: ' . ($server['db'] ?? '(nenhum)'));
} catch (Throwable $ex) {
    falha($ex);
    linha('Conferir: MySQL iniciado no XAMPP, porta, credenciais, nome do banco e extensao pdo_mysql.');
    exit(1);
}

$required = [
    'usuarios' => ['id','nome','email','senha_hash','papel','status'],
    'pacientes' => ['usuario_id'],
    'psicologos' => ['usuario_id','crp','nome_profissional','area_atuacao','status_verificacao'],
    'consentimentos' => ['usuario_id','tipo','versao'],
];
$hasProblem = false;
foreach ($required as $table => $columns) {
    $s = $pdo->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
    $s->execute([$table]);
    $engine = $s->fetchColumn();
    if (!$engine) {
        linha("Tabela {$table}: NAO EXISTE");
        $hasProblem = true;
        continue;
    }
    $s = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
    $s->execute([$table]);
    $actual = $s->fetchAll(PDO::FETCH_COLUMN);
    $missing = array_values(array_diff($columns, $actual));
    linha('Tabela ' . $table . ': ' . ($missing ? 'COLUNAS AUSENTES: ' . implode(', ', $missing) : 'OK') . ' [engine=' . $engine . ']');
    if ($missing || strcasecmp($engine, 'InnoDB') !== 0) $hasProblem = true;
}
if ($hasProblem) {
    linha('PARE: banco existente nao corresponde ao mindly.sql ou nao usa InnoDB.');
    linha('ATENCAO: CREATE TABLE IF NOT EXISTS nao altera tabelas antigas. Nao apague dados reais.');
    exit(1);
}

try {
    $n = $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    linha('Quantidade atual em mindly.usuarios: ' . $n);
} catch (Throwable $ex) { falha($ex); exit(1); }

foreach (['paciente', 'psicologo'] as $role) {
    $tag = bin2hex(random_bytes(8));
    $email = 'diag-' . $role . '-' . $tag . '@example.invalid';
    linha('Teste de gravacao ' . $role . ':');
    try {
        $pdo->beginTransaction();
        $s = $pdo->prepare('INSERT INTO usuarios (nome,email,senha_hash,papel,status) VALUES (?,?,?,?,?)');
        $s->execute(['Diagnostico temporario', $email, password_hash('SenhaTeste_123456', PASSWORD_DEFAULT), $role, $role === 'paciente' ? 'ativo' : 'pendente']);
        $id = $pdo->lastInsertId();
        if ($role === 'paciente') {
            $pdo->prepare('INSERT INTO pacientes (usuario_id) VALUES (?)')->execute([$id]);
        } else {
            $crp = '99/' . random_int(1000000, 9999999);
            $pdo->prepare('INSERT INTO psicologos (usuario_id,crp,nome_profissional,area_atuacao,status_verificacao) VALUES (?,?,?,?,?)')->execute([$id,$crp,null,null,'pendente']);
        }
        foreach (['termos_uso', 'politica_privacidade'] as $tipo) {
            $pdo->prepare('INSERT INTO consentimentos (usuario_id,tipo,versao) VALUES (?,?,?)')->execute([$id,$tipo,$c['terms_version']]);
        }
        $n = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE id=?');
        $n->execute([$id]);
        if ((int)$n->fetchColumn() !== 1) throw new RuntimeException('Registro nao visivel mesmo antes de confirmar a transacao.');
        fechaTransacao($pdo); // Teste nao cria conta permanente.
        $n = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE email=?');
        $n->execute([$email]);
        linha((int)$n->fetchColumn() === 0 ? 'OK: INSERT nas tabelas funcionou; ROLLBACK removeu dados de teste.' : 'ERRO: ROLLBACK nao desfez o teste; verifique o engine das tabelas.');
    } catch (Throwable $ex) {
        fechaTransacao($pdo);
        falha($ex);
    }
}
linha('=== FIM. Compare o servidor e o banco informados com o phpMyAdmin. ===');
