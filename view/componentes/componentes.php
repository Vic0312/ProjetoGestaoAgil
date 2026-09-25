<?php
function icon($name, $class = '') {
    $icons = [
        'home' => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10.5V20h13v-9.5"/><path d="M9.5 20v-6h5v6"/>',
        'search' => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/>',
        'calendar' => '<rect x="3.5" y="5.5" width="17" height="15" rx="3"/><path d="M8 3.5v4M16 3.5v4M3.5 10h17"/>',
        'video' => '<rect x="3.5" y="6.5" width="12.5" height="11" rx="2.5"/><path d="M16 10l4.5-2.5v9L16 14"/>',
        'file' => '<path d="M6.5 3.5h8l3 3v14h-11z"/><path d="M14.5 3.5v4h4M9 12h6M9 16h5"/>',
        'wallet' => '<rect x="3.5" y="6" width="17" height="13" rx="3"/><path d="M3.5 9h17M15.5 13h3"/>',
        'user' => '<circle cx="12" cy="8" r="3.5"/><path d="M5.5 20c.4-4.2 2.7-6.5 6.5-6.5s6.1 2.3 6.5 6.5"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3.5 19c.4-3.7 2.3-5.6 5.5-5.6 2.9 0 4.8 1.6 5.4 4.7"/><circle cx="17" cy="9" r="2.4"/><path d="M15.5 14.2c2.9-.5 5 1.1 5.5 4.3"/>',
        'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5v5l3.5 2"/>',
        'bell' => '<path d="M6.5 16.5h11l-1.5-2V10a4 4 0 0 0-8 0v4.5z"/><path d="M10 19a2 2 0 0 0 4 0"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19 13.5v-3l-2-.7a6 6 0 0 0-.8-1.9l.9-1.9-2.1-2.1-1.9.9a6 6 0 0 0-1.9-.8L10.5 2h-3l-.7 2a6 6 0 0 0-1.9.8L3 3.9.9 6l.9 1.9A6 6 0 0 0 1 9.8l-2 .7v3l2 .7a6 6 0 0 0 .8 1.9L.9 18l2.1 2.1 1.9-.9a6 6 0 0 0 1.9.8l.7 2h3l.7-2a6 6 0 0 0 1.9-.8l1.9.9L18 18l-.9-1.9a6 6 0 0 0 .8-1.9z" transform="translate(2 0) scale(.83)"/>',
        'logout' => '<path d="M10 4H5.5A2.5 2.5 0 0 0 3 6.5v11A2.5 2.5 0 0 0 5.5 20H10"/><path d="M14 8l4 4-4 4M8 12h10"/>',
        'chevron' => '<path d="m9 5 7 7-7 7"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'edit' => '<path d="M4 20h4l11-11-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>',
        'filter' => '<path d="M4 6h16M7 12h10M10 18h4"/>',
        'heart' => '<path d="M12 20s-7-4.2-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.8-7 10-7 10z"/>',
        'shield' => '<path d="M12 3.5l7 3v5.2c0 4.3-2.9 7.5-7 8.8-4.1-1.3-7-4.5-7-8.8V6.5z"/><path d="m9 12 2 2 4-4"/>',
        'star' => '<path d="m12 3 2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8-4.3-4.1 5.9-.9z"/>',
        'phone' => '<path d="M7.2 3.5h3l1.2 4-2 1.2a14 14 0 0 0 5.9 5.9l1.2-2 4 1.2v3c0 1.1-.9 2-2 2C10.5 18.8 5.2 13.5 5.2 5.5c0-1.1.9-2 2-2z"/>',
        'mail' => '<rect x="3.5" y="5.5" width="17" height="13" rx="2.5"/><path d="M5 7l7 5 7-5"/>',
        'lock' => '<rect x="5" y="10" width="14" height="10" rx="2.5"/><path d="M8.5 10V7.8a3.5 3.5 0 0 1 7 0V10"/>',
        'card' => '<rect x="3.5" y="5.5" width="17" height="13" rx="2.5"/><path d="M3.5 10h17M7 15h4"/>',
        'download' => '<path d="M12 3v12M8 11l4 4 4-4M5 20h14"/>',
        'chart' => '<path d="M4 20V10M10 20V5M16 20v-8M22 20V8"/>',
        'activity' => '<path d="M3 12h4l2-5 4 10 2-5h6"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'dots' => '<circle cx="5" cy="12" r="1" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1" fill="currentColor" stroke="none"/><circle cx="19" cy="12" r="1" fill="currentColor" stroke="none"/>',
        'camera' => '<path d="M5 7h3l1-2h6l1 2h3a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2z"/><circle cx="12" cy="13" r="3.5"/>',
        'mic' => '<rect x="9" y="3" width="6" height="11" rx="3"/><path d="M6.5 11a5.5 5.5 0 0 0 11 0M12 16.5V21M9 21h6"/>',
        'monitor' => '<rect x="3" y="4" width="18" height="13" rx="2"/><path d="M9 21h6M12 17v4"/>',
        'end' => '<path d="M6 8a8 8 0 1 0 12 0"/><path d="M12 3v9"/>',
    ];
    $svg = $icons[$name] ?? $icons['home'];
    return '<svg class="icone '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$svg.'</svg>';
}

function navItems($perfil) {
    if ($perfil === 'paciente') return [
        ['dashboardPaciente.php','home','Início','inicio'],
        ['buscarPsicologos.php','search','Psicólogos','psicologos'],
        ['minhasConsultas.php','calendar','Consultas','consultas'],
        ['prontuarioPaciente.php','file','Meu histórico','historico'],
        ['pagamentosPaciente.php','wallet','Pagamentos','pagamentos'],
        ['perfilPaciente.php','user','Meu perfil','perfil'],
    ];
    if ($perfil === 'psicologo') return [
        ['dashboardPsicologo.php','home','Início','inicio'],
        ['agendaPsicologo.php','calendar','Agenda','agenda'],
        ['disponibilidadePsicologo.php','clock','Disponibilidade','disponibilidade'],
        ['pacientesPsicologo.php','users','Pacientes','pacientes'],
        ['prontuarioPsicologo.php','file','Prontuários','prontuarios'],
        ['perfilProfissional.php','user','Meu perfil','perfil'],
        ['financeiroPsicologo.php','wallet','Financeiro','financeiro'],
        ['notificacoesPsicologo.php','bell','Notificações','notificacoes'],
    ];
    return [
        ['dashboardAdmin.php','home','Visão geral','inicio'],
        ['usuariosAdmin.php','users','Usuários','usuarios'],
        ['atendimentosAdmin.php','video','Atendimentos','atendimentos'],
        ['relatoriosAdmin.php','chart','Relatórios','relatorios'],
    ];
}

function abrirLayout($perfil, $ativo, $titulo, $subtitulo, $nome) {
    global $currentUser, $d;
    $nome = e($currentUser['nome']);
    if ($ativo === 'inicio' && in_array($perfil, ['paciente','psicologo'], true)) {
        $primeiroNome = preg_split('/\s+/u', trim($currentUser['nome']))[0];
        $titulo = 'Olá, '.e($primeiroNome).'!';
    }
    $perfilLabel = $perfil === 'paciente' ? 'Paciente' : ($perfil === 'psicologo' ? 'Psicólogo(a)' : 'Administrador');
    $iniciais = e(mb_strtoupper(mb_substr($currentUser['nome'],0,1)));
    echo '<div class="app">';
    echo '<aside class="sidebar">';
    echo '<a class="sidebar-logo" href="home.php"><img src="../img/logo-mindly.svg" alt="Mindly"></a>';
    echo '<div class="perfil-lateral"><span class="avatar avatar-pequeno">'.$iniciais.'</span><div><strong>'.$nome.'</strong><span>'.$perfilLabel.'</span></div></div>';
    echo '<nav class="menu-lateral">';
    foreach (navItems($perfil) as $item) {
        [$href,$ico,$label,$key] = $item;
        $classe = $key === $ativo ? 'item-menu ativo' : 'item-menu';
        echo '<a class="'.$classe.'" href="'.$href.'">'.icon($ico).'<span>'.$label.'</span></a>';
    }
    echo '</nav>';
    echo '<div class="sidebar-rodape"><form method="post" action="../processamento/auth.php">'.csrfField().'<input type="hidden" name="acao" value="logout"><button class="item-menu" style="border:0;background:transparent;width:100%;cursor:pointer;font:inherit" type="submit">'.icon('logout').'<span>Sair</span></button></form></div>';
    echo '</aside>';
    echo '<main class="area-app">';
    echo '<header class="topbar"><div class="titulo-topbar"><h1>'.$titulo.'</h1><p>'.$subtitulo.'</p></div><div class="acoes-topbar"><details class="notificacoes"><summary class="botao-icone" aria-label="Notificações">'.icon('bell');
    if (!empty($d['notificacoes'])) echo '<span class="contador-notificacoes">'.count($d['notificacoes']).'</span>';
    echo '</summary><div class="painel-notificacoes"><h2>Notificações</h2>';
    if (empty($d['notificacoes'])) emptyState('Você não possui novas notificações.');
    foreach ($d['notificacoes'] ?? [] as $notification) {
        echo '<article><strong>'.e($notification['titulo']).'</strong><p>'.nl2br(e($notification['mensagem'])).'</p><small>'.dateLabel($notification['criado_em']).'</small><form method="post" action="../processamento/profissional.php">'.csrfField().'<input type="hidden" name="acao" value="ler_notificacao"><input type="hidden" name="id" value="'.(int)$notification['id'].'"><button class="botao botao-suave">Marcar como lida</button></form></article>';
    }
    echo '</div></details><span class="avatar">'.$iniciais.'</span></div></header>';
    echo '<section class="conteudo-app">';
    showMessage();
}
function fecharLayout() { echo '</section></main></div>'; }
?>
