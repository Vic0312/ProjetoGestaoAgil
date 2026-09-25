<?php require_once __DIR__ . '/../controller/Auth.php';
$old=$_SESSION['cadastro_anterior'] ?? []; unset($_SESSION['cadastro_anterior']);
$professional= ($old['papel'] ?? '') === 'psicologo';
function oldRegistration(string $key): string { global $old; return e($old[$key] ?? ''); }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Mindly - plataforma de atendimento psicológico online.">
    <link rel="stylesheet" href="../css/home.css">
    <title>Mindly | Cuidado psicológico online</title>
</head>
<body>
    <div class="pagina-inicial">
        <header class="cabecalho-principal">
            <a class="marca-mindly" href="home.php" aria-label="Mindly - página inicial">
                <img src="../img/logo-mindly.svg" alt="Mindly">
            </a>

            <nav class="acesso-conta" aria-label="Acesso à conta">
                <span>Já tem uma conta?</span>
                <a class="botao botao-contorno" href="login.php">Entrar</a>
            </nav>
        </header>

        <main class="conteudo-principal">
            <section class="apresentacao" aria-labelledby="titulo-principal">
                <span class="etiqueta">Cuidado psicológico online</span>

                <h1 id="titulo-principal">
                    Cuidado que<br>
                    <em>conecta</em>
                </h1>

                <p class="texto-apresentacao">
                    A Mindly conecta pacientes e psicólogos em um ambiente simples,
                    acolhedor e seguro para cuidar da saúde mental de onde você estiver.
                </p>

                <a class="botao botao-principal botao-apresentacao" href="#cadastro">
                    Começar agora
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M5 12h14M13 6l6 6-6 6"/>
                    </svg>
                </a>

                <div class="recursos" aria-label="Principais recursos da Mindly">
                    <article class="recurso">
                        <span class="icone-recurso" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <rect x="3.5" y="5.5" width="17" height="15" rx="3"/>
                                <path d="M8 3.5v4M16 3.5v4M3.5 10h17M8 14h2M14 14h2"/>
                            </svg>
                        </span>
                        <div>
                            <h2>Agendamento simples</h2>
                            <p>Escolha o profissional e encontre o melhor horário para sua consulta.</p>
                        </div>
                    </article>

                    <article class="recurso">
                        <span class="icone-recurso" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <rect x="3.5" y="6.5" width="12.5" height="11" rx="2.5"/>
                                <path d="M16 10l4.5-2.5v9L16 14"/>
                            </svg>
                        </span>
                        <div>
                            <h2>Atendimento online</h2>
                            <p>Consultas por vídeo com conforto, privacidade e praticidade.</p>
                        </div>
                    </article>

                    <article class="recurso">
                        <span class="icone-recurso" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M6.5 3.5h8l3 3v14h-11z"/>
                                <path d="M14.5 3.5v4h4M9 12h6M9 16h5"/>
                            </svg>
                        </span>
                        <div>
                            <h2>Histórico organizado</h2>
                            <p>Acompanhe suas consultas de forma clara e centralizada.</p>
                        </div>
                    </article>

                    <article class="recurso">
                        <span class="icone-recurso" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 3.5l7 3v5.2c0 4.3-2.9 7.5-7 8.8-4.1-1.3-7-4.5-7-8.8V6.5z"/>
                                <rect x="9" y="10" width="6" height="5" rx="1.3"/>
                                <path d="M10.5 10V8.8a1.5 1.5 0 013 0V10"/>
                            </svg>
                        </span>
                        <div>
                            <h2>Ambiente seguro</h2>
                            <p>Privacidade e cuidado com as informações em toda a experiência.</p>
                        </div>
                    </article>
                </div>
            </section>

            <section class="area-cadastro" id="cadastro" aria-labelledby="titulo-cadastro">
                <div class="cartao-cadastro">
                    <div class="cabecalho-cadastro">
                        <span class="icone-boas-vindas" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 20s-7-4.2-7-10a4 4 0 017-2.6A4 4 0 0119 10c0 5.8-7 10-7 10z"/>
                            </svg>
                        </span>
                        <h2 id="titulo-cadastro">Crie sua conta</h2>
                        <p>Preencha seus dados para começar.</p>
                    </div>

                    <div class="seletor-perfil" role="group" aria-label="Tipo de perfil">
                        <button class="opcao-perfil <?= !$professional ? 'ativa' : '' ?>" type="button" aria-pressed="<?= !$professional ? 'true' : 'false' ?>" data-perfil="paciente">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="12" cy="8" r="3.5"/>
                                <path d="M5.5 20c.4-4.2 2.7-6.5 6.5-6.5s6.1 2.3 6.5 6.5"/>
                            </svg>
                            Sou paciente
                        </button>
                        <button class="opcao-perfil <?= $professional ? 'ativa' : '' ?>" type="button" aria-pressed="<?= $professional ? 'true' : 'false' ?>" data-perfil="psicologo">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="12" cy="7.5" r="3.2"/>
                                <path d="M6.2 19.5c.5-3.7 2.6-5.8 5.8-5.8s5.3 2.1 5.8 5.8M18 8.5h3M19.5 7v3"/>
                            </svg>
                            Sou psicólogo(a)
                        </button>
                    </div>

                    <?php showMessage(); ?><form class="formulario-cadastro" method="post" action="../processamento/auth.php">
<?= csrfField() ?><input type="hidden" name="acao" value="cadastro"><input type="hidden" name="papel" id="papel" value="<?= $professional ? 'psicologo' : 'paciente' ?>">
                        <label class="campo-formulario">
                            <span class="rotulo-campo">Nome completo</span>
                            <span class="controle-campo">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <circle cx="12" cy="8" r="3.2"/>
                                    <path d="M5.5 20c.4-4.2 2.7-6.5 6.5-6.5s6.1 2.3 6.5 6.5"/>
                                </svg>
                                <input type="text" name="nome" value="<?= oldRegistration('nome') ?>" required placeholder="Digite seu nome" autocomplete="name">
                            </span>
                        </label>

                        <label class="campo-formulario">
                            <span class="rotulo-campo">E-mail</span>
                            <span class="controle-campo">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <rect x="3.5" y="5.5" width="17" height="13" rx="2.5"/>
                                    <path d="M5 7l7 5 7-5"/>
                                </svg>
                                <input type="email" name="email" value="<?= oldRegistration('email') ?>" required placeholder="seuemail@exemplo.com" autocomplete="email">
                            </span>
                        </label>

                        <label class="campo-formulario">
                            <span class="rotulo-campo">Senha</span>
                            <span class="controle-campo">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <rect x="5" y="10" width="14" height="10" rx="2.5"/>
                                    <path d="M8.5 10V7.8a3.5 3.5 0 017 0V10"/>
                                </svg>
                                <input type="password" name="senha" id="senha" aria-describedby="senha-orientacao senha-erro" required placeholder="Crie uma senha" autocomplete="new-password">
                            </span>
<small id="senha-orientacao" class="orientacao-senha">A senha deve conter no mínimo 8 caracteres</small><small id="senha-erro" class="erro-campo" aria-live="polite" hidden></small>
                        </label>

                        <label class="campo-formulario">
                            <span class="rotulo-campo">Confirmar senha</span>
                            <span class="controle-campo">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <rect x="5" y="10" width="14" height="10" rx="2.5"/>
                                    <path d="M8.5 10V7.8a3.5 3.5 0 017 0V10"/>
                                </svg>
                                <input type="password" name="confirmarSenha" id="confirmarSenha" aria-describedby="confirmacao-erro" required placeholder="Repita sua senha" autocomplete="new-password">
                            </span>
<small id="confirmacao-erro" class="erro-campo" aria-live="polite" hidden></small>
                        </label>

                        <div id="campos-profissionais" <?= !$professional ? 'hidden' : '' ?>><label class="campo-formulario"><span class="rotulo-campo">CRP</span><span class="controle-campo"><input type="text" name="crp" value="<?= oldRegistration('crp') ?>" placeholder="00/00000" maxlength="24" pattern="[0-9]{2}/[0-9]{4,7}" <?= !$professional ? 'disabled' : '' ?>></span></label><label class="campo-formulario"><span class="rotulo-campo">Nome profissional (opcional)</span><span class="controle-campo"><input type="text" name="nome_profissional" value="<?= oldRegistration('nome_profissional') ?>" maxlength="150" <?= !$professional ? 'disabled' : '' ?>></span></label><label class="campo-formulario"><span class="rotulo-campo">Área de atuação (opcional)</span><span class="controle-campo"><input type="text" name="area_atuacao" value="<?= oldRegistration('area_atuacao') ?>" maxlength="150" <?= !$professional ? 'disabled' : '' ?>></span></label><p>Seu cadastro ficará pendente até a análise profissional. O formato do CRP não comprova a validade do registro.</p></div><label class="termos">
                            <input type="checkbox" name="termos" required <?= ($old['termos'] ?? '') === 'on' ? 'checked' : '' ?>>
                            <span>Aceito os <a href="#">Termos de Uso</a> e a <a href="#">Política de Privacidade</a>.</span>
                        </label>

                        <button class="botao botao-principal botao-cadastro" type="submit">Criar conta</button>
                    </form>

                    <p class="ja-cadastrado">Já possui uma conta? <a href="login.php">Entrar</a></p>
                </div>
            </section>
        </main>

        <footer class="rodape-principal">
            <span>© 2026 Mindly</span>
            <span>Cuidado, acolhimento e conexão.</span>
        </footer>
    </div>
<script src="../js/cadastro.js" defer></script></body>
</html>
