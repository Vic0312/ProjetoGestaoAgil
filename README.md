# Mindly — Protótipo visual completo

Projeto visual em PHP + CSS organizado conforme a lógica estrutural observada no projeto Xhopii.

## Como abrir no XAMPP
1. Copie a pasta `Mindly` para `C:\xampp\htdocs\`.
2. Inicie o Apache no XAMPP.
3. Abra `http://localhost/Mindly/`.

A raiz redireciona para `view/home.php`.

## Arquitetura
- `controller/` — estrutura reservada para controladores.
- `model/` — estrutura reservada para models.
- `view/` — telas PHP.
- `view/componentes/` — helper visual compartilhado para ícones e navegação.
- `css/` — **um CSS específico para cada tela** + `componentes.css` apenas para elementos repetidos da área logada.
- `img/` — identidade visual.
- `js/` — reservado para scripts futuros (sem funcionalidades nesta etapa).
- `processamento/` — reservado para processamento futuro.

## Telas públicas
- `view/home.php`
- `view/login.php`
- `view/redefinirSenha.php`

## Paciente
- `view/dashboardPaciente.php`
- `view/buscarPsicologos.php`
- `view/perfilPsicologo.php`
- `view/agendarConsulta.php`
- `view/minhasConsultas.php`
- `view/salaAtendimento.php`
- `view/prontuarioPaciente.php`
- `view/pagamentosPaciente.php`
- `view/pagamentoConsulta.php`
- `view/perfilPaciente.php`

## Psicólogo
- `view/dashboardPsicologo.php`
- `view/agendaPsicologo.php`
- `view/disponibilidadePsicologo.php`
- `view/pacientesPsicologo.php`
- `view/prontuarioPsicologo.php`
- `view/salaAtendimentoPsicologo.php`
- `view/perfilProfissional.php`

## Administração
- `view/dashboardAdmin.php`
- `view/usuariosAdmin.php`
- `view/atendimentosAdmin.php`
- `view/relatoriosAdmin.php`

## Escopo desta versão
Esta entrega implementa apenas a estrutura visual. Campos, botões, videochamada, pagamentos, filtros, autenticação e salvamento de dados são demonstrações de interface e ainda não possuem regras de negócio, banco de dados ou integrações.
