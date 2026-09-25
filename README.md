# Mindly — autenticação PHP/MySQL

Cadastro, login, sessão, logout e recuperação de senha integrados ao banco `mindly`. Todas as telas restritas exibem dados reais consultados por conta/perfil e estados vazios quando não há registros. As operações de agendamento, cobrança, videochamada e edição de prontuário ainda não estão disponíveis; seus controles ficam desativados e identificados na interface.

## Executar no XAMPP

1. Mantenha o projeto em `C:\xampp\htdocs\ProjetoGestaoAgil` e inicie Apache e MySQL.
2. No phpMyAdmin, use **Importar** e selecione o arquivo original `mindly.sql` da raiz. Ele já cria e seleciona `mindly`; não crie outro banco. Se já foi importado com a mesma estrutura, não é necessário repetir. O SQL não migra tabelas antigas de estrutura diferente.
3. Crie a pasta `config`, se necessário, e copie `config.example.php` para `config/local.php`. Preencha `db_user` e `db_password` com as credenciais locais. No XAMPP padrão, geralmente o usuário é `root` e a senha é vazia. Esse arquivo está ignorado pelo Git; nunca versione credenciais reais.
4. Verifique `db_host`/`db_port`, `base_path` (`/ProjetoGestaoAgil`) e `app_url` (`http://localhost/ProjetoGestaoAgil`). Se mudar a pasta ou porta, ajuste os dois últimos. O banco permanece `mindly`.
5. Habilite `pdo_mysql` e `mbstring` no PHP 8.2+. A pasta `session.save_path` do PHP precisa permitir escrita pelo servidor. Abra `http://localhost/ProjetoGestaoAgil/`.

Use HTTPS na implantação: o cookie assume `Secure` quando o PHP recebe HTTPS. Atrás de proxy, configure o servidor para reconhecer corretamente HTTPS, sem confiar em cabeçalhos arbitrários do cliente. Desative `display_errors` em produção. Restrinja o usuário MySQL da aplicação às operações necessárias. Não publique arquivos de configuração, backups ou diretórios internos em servidores sem processamento PHP.

## Testar paciente

1. Em `view/home.php`, escolha **Sou paciente**, informe nome, e-mail novo, senha (mínimo de 8 caracteres; limite técnico de 72 bytes), confirmação e aceite os termos.
2. Ao enviar, confira o dashboard autenticado e o nome na navegação. No banco, confira `usuarios`, `pacientes` e dois registros de `consentimentos` (termos e privacidade); `senha_hash` contém um hash.
3. Clique **Sair**. Tente abrir novamente o dashboard ou outra página restrita: deve ir ao login.
4. Entre com o e-mail e a senha cadastrados. Tente senha incorreta e e-mail repetido no cadastro. Tente acessar uma página de psicólogo/admin: deve receber HTTP 403.

## Testar psicólogo

1. Escolha **Sou psicólogo(a)**. Informe os dados de conta e CRP no formato `00/00000` (dois dígitos, barra e quatro a sete dígitos). Nome profissional e área de atuação são opcionais. A checagem de formato não verifica o registro profissional.
2. O cadastro gera `usuarios.status=pendente`, `psicologos.status_verificacao=pendente` e consentimentos. A tela informa que aguarda validação; não há acesso ao dashboard profissional.
3. A aprovação real exige análise administrativa do registro. Esta entrega não implementa um fluxo administrativo de análise. **Somente para teste local**, após identificar o ID da conta de teste, altere no phpMyAdmin `usuarios.status` para `ativo` e, em `psicologos`, `status_verificacao` para `aprovado` e `verificado_em` para a data UTC atual. Não execute isso sobre profissionais reais sem análise.
4. Faça login: deve abrir `dashboardPsicologo.php`. Teste CRP/e-mail duplicados e acesso indevido às páginas de paciente/admin. Voltar a pendente/rejeitado ou suspender a conta bloqueia inclusive uma sessão já aberta na próxima requisição.

O papel do login vem exclusivamente do banco. Não existem credenciais administrativas públicas nem cadastro público de admin. Uma conta administrativa deve ser provisionada por operação controlada, com senha individual gerada usando `password_hash()`; todas as suas rotas exigem o papel `admin` e conta ativa.

## Recuperação de senha

Por padrão `mail_enabled=false`: a tela informa indisponibilidade e não gera token nem simula envio. Para habilitar, configure um transporte real para `mail()` no `php.ini`/sendmail do XAMPP ou no servidor (SMTP/provedor), preencha `mail_from`, ajuste a URL confiável `app_url` e só então habilite `mail_enabled` em `config/local.php`. `mail()` aceitar uma mensagem não garante entrega; confira o transporte e a caixa de entrada. Uma falha do transporte registra somente uma mensagem técnica sem token ou endereço e invalida o token recém-gerado; a resposta ao público permanece genérica.

Com o envio configurado, solicite a recuperação, abra o link recebido e defina a senha. O banco armazena somente SHA-256 de 32 bytes aleatórios, com validade de 30 minutos. O token é de uso único; redefinir invalida todos os tokens de recuperação e de login persistente da conta e bloqueia as sessões antigas na próxima requisição. O status/papel da conta não muda. Teste link expirado, alterado e reutilizado, senha antiga recusada e nova senha aceita. Tokens não são exibidos em mensagens ou logs da aplicação. Configure os logs de acesso do servidor para omitir parâmetros de URLs de recuperação; o link recebido contém o token até o redirecionamento para a URL limpa.

## Organização e arquivos alterados

- `config.example.php`, `config/local.php` (somente local) e `.gitignore`: configurações centralizadas, exemplo e proteção contra versionamento de segredos.
- `model/Database.php`: conexão PDO, consultas preparadas e leitura de contas; datas do banco em UTC.
- `controller/Auth.php`: sessão, CSRF, escaping, autorização por perfil, expiração após 30 minutos de inatividade e revogação por status/senha.
- `controller/Controlador.php`: cadastro transacional, login e recuperação/redefinição.
- `processamento/auth.php`: entrada POST com CSRF para cadastro, login, logout e recuperação.
- `view/home.php`, `view/login.php`, `view/redefinirSenha.php`: integração dos formulários; atalhos públicos de demonstração removidos e “Lembrar de mim” desativado.
- `js/cadastro.js`: alternância entre paciente e campos profissionais.
- `view/componentes/componentes.php`: nome autenticado e logout POST.
- Todas as 21 páginas restritas em `view/`: proteção antes de emitir HTML. `perfilPsicologo.php` é o perfil de profissional visto pelo paciente; `perfilProfissional.php` é a área do próprio psicólogo. Paciente, psicólogo e admin têm permissões distintas.
- `tests/auth_integration.php`: testes HTTP e MySQL com contas aleatórias temporárias, removidas ao final.

A identidade visual, os layouts, a logo e o SQL original foram preservados. `css/home.css` recebeu somente estilos de orientação/erro e `css/componentes.css`, estados vazios e o painel de notificações. Os demais arquivos CSS não foram alterados. Os links de termos/privacidade ainda são os placeholders originais; publique os documentos correspondentes à versão configurada antes de disponibilizar o cadastro ao público.

## Verificações automatizadas

No PowerShell, confira sintaxe:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { & C:\xampp\php\php.exe -l $_.FullName }
```

Com banco/configuração local prontos, execute o servidor de desenvolvimento em um terminal:

```powershell
C:\xampp\php\php.exe -S 127.0.0.1:8085 -t C:\xampp\htdocs
```

Em outro terminal:

```powershell
C:\xampp\php\php.exe tests/auth_integration.php
```

Alternativamente, informe `http://localhost/ProjetoGestaoAgil` como argumento para testar pelo Apache. Os testes exigem a extensão PHP `curl` e acesso ao mesmo banco da instância HTTP. Execute somente em desenvolvimento: o script cria e remove exclusivamente suas próprias contas/consentimentos/tokens temporários. A aprovação e conta admin no teste são fixtures aleatórias, removidas no encerramento. O teste não envia e-mail; verifica redefinição com tokens de teste inseridos no banco.

Resultado da validação desta entrega: sintaxe de todos os arquivos PHP aprovada; testes HTTP/MySQL passaram para cadastros, duplicidades, CSRF, 21 rotas para cada perfil, suspensão/rejeição, logout e redefinição/expiração/reuso de tokens. As contas de teste foram removidas. O SQL original permaneceu inalterado no repositório. Nesta correção, o banco já existente foi utilizado sem nova importação ou alteração de estrutura. Envio real de e-mail e inspeção visual em navegador não foram validados nesta sessão.

## Integração das telas com dados reais

- `model/MindlyData.php`: leituras PDO de consultas, perfis públicos/privados, horários, bloqueios, pacientes vinculados, prontuários, resumos, pagamentos, métodos mascarados, avaliações, notificações e usuários administrativos. O identificador vem da conta autenticada; parâmetros de URL somente selecionam registros dentro desse escopo.
- `controller/DadosController.php`: prepara os dados de cada página, valida IDs e filtros e calcula indicadores usando os registros consultados. Datas de atendimentos são lidas em UTC e exibidas em `America/Sao_Paulo`; datas de nascimento não sofrem conversão de fuso. Faixas semanais de disponibilidade mantêm os horários cadastrados.
- `view/componentes/dados.php`: formatação, escaping, estados vazios e links seguros para recibos existentes. Somente URLs HTTP/HTTPS são aceitas.
- As 21 telas restritas e `view/componentes/componentes.php` foram integradas. O sino abre as notificações não lidas da conta atual; sem registros, não aparece o ponto indicador. Não há cache de registros privados na sessão.
- `controller/Auth.php`, `processamento/auth.php`, `view/home.php`, `js/cadastro.js` e `css/home.css`: mínimo de oito caracteres Unicode, confirmação, mensagens em vermelho, bloqueio de envio pelo JavaScript e validação independente no PHP. Em falhas, nome, e-mail, perfil, CRP, nome profissional, área e aceite são preservados uma vez; senhas nunca são reapresentadas. O limite de 72 bytes evita truncamento pelo algoritmo de hash atual.

### Critérios dos indicadores e permissões

Paciente: consultas futuras em `aguardando_pagamento`/`confirmada`, além de consultas com status `em_andamento`, compõem as próximas consultas. `concluida` compõe o total realizado. A aba “Todas” também permite consultar registros antigos que permaneçam com outros status. O histórico mostra somente `resumos_compartilhados` da própria consulta, nunca `registros_prontuario`.

Psicólogo: os indicadores, a agenda, os horários e os pagamentos pertencem exclusivamente ao profissional autenticado e aprovado. Pacientes vinculados são aqueles com consultas confirmadas/em andamento/concluídas ou com prontuário atribuído ao profissional. Anotações são consultadas pela dupla paciente/psicólogo, verificando também a correspondência da consulta. Os valores financeiros mostram pagamentos BRL aprovados no mês, descontados reembolsos concluídos; não representam repasses. A agenda permite navegar entre semanas e inclui fins de semana.

Busca/perfil público: somente psicólogos ativos e aprovados. Nome, CRP, área, biografia, abordagem, especialidades, preços, duração e indicadores publicados vêm do banco. Avaliações precisam estar publicadas e vinculadas a uma consulta concluída da dupla correspondente. Horários anunciados precisam estar livres, no futuro e sem conflito com bloqueios ou consultas não canceladas. Nenhum dado privado de pacientes é incluído nos perfis públicos.

Administração: usuários, dados operacionais de atendimentos e agregados da plataforma; não consulta anotações clínicas ou resumos compartilhados. Os relatórios consideram o mês corrente completo por data agendada, inclusive agendamentos futuros; a média diária divide pelo número de dias desse mês. Os gráficos diários mostram os últimos sete dias e suas quantidades reais. Não há percentual fictício de uso de videochamada.

IDs de consulta e de paciente não autorizados retornam 404 com mensagem genérica. A proteção por perfil continua retornando 403, e acesso sem sessão redireciona ao login. Mudar `usuario_id`/`paciente_id` em uma URL não altera a conta autenticada. Falhas de banco retornam indisponibilidade; não são mascaradas como ausência de registros.

### Funcionalidades que continuam futuras

A interface permite consultar dados existentes, buscar profissionais/usuários/pacientes, filtrar consultas e navegar na agenda. Ainda não executa criação/remarcação/cancelamento de consultas, geração/edição de disponibilidade, edição de perfis/fotos, gravação de prontuários, novos pagamentos/cartões, videochamada, favoritos, publicação de avaliações, gestão administrativa de usuários ou exportação de relatórios. Esses controles foram desativados e identificados. A listagem de recibos abre somente URLs já registradas. Ler notificações não altera seu estado; marcar como lidas continua futuro. Não foram criados registros operacionais para simular utilização.

### Testes desta correção

Com Apache e MySQL do XAMPP iniciados:

```powershell
C:\xampp\php\php.exe tests/auth_integration.php http://localhost/ProjetoGestaoAgil
C:\xampp\php\php.exe tests/dados_integration.php http://localhost/ProjetoGestaoAgil
```

A segunda suíte cria dois pacientes, dois psicólogos e um administrador temporários. Testa senhas de quatro caracteres (incluindo multibyte), mensagem exata, preservação dos campos, senha válida, dashboards novos zerados, registros reais distintos por conta, pagamentos/cartões/notificações, prontuários privados versus resumos compartilhados, bloqueios de agenda, avaliações, manipulação de IDs, atualização e troca de sessão. Os dados de teste são inseridos apenas para a execução e removidos em ordem de dependências, sem excluir registros anteriores.

`tests/cadastro_validation.js` exercita os eventos do JavaScript em um DOM de teste: erro durante digitação, bloqueio do envio, remoção do erro, confirmação, Unicode e troca de perfil. Pode ser repetido com `node tests/cadastro_validation.js`, se Node estiver instalado. Nesta sessão foram executadas 28 verificações dessa lógica em um motor JavaScript; isso não substitui inspeção visual em navegador.

Resultado: as duas suítes PHP/HTTP passaram no Apache/MySQL local; os testes JavaScript passaram. Os registros reais preexistentes foram preservados. Sem commit automático. A inspeção visual em navegador e o envio real de e-mail permanecem não validados nesta sessão.

Para conferir manualmente, digite `1234` no cadastro: o erro deve surgir em vermelho abaixo da senha. Complete oito caracteres e confirme a mesma senha: os erros devem desaparecer. Cadastre duas contas e confira que cada uma começa sem consultas, pagamentos ou histórico. Para verificar dados já existentes, use a conta participante correspondente; somente o psicólogo responsável pode abrir suas anotações e o paciente recebe apenas o resumo compartilhado.
