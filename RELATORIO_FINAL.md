# Entrega final do Mindly — 12/11/2026

Implementação realizada diretamente no projeto existente, preservando MVC, autenticação, banco e componentes. Nenhum commit criado. Videochamada permanece fora do escopo.

## 1. Task #59 — mensagens de confirmação

O mecanismo `flash()` / `showMessage()` foi mantido. Agora aceita tipos `success`, `error` e `info`, com estilo comum às páginas públicas e restritas. O conteúdo continua escapado e é removido da sessão após a primeira exibição. Erros dos endpoints de autenticação, paciente e profissional usam o tipo de erro; falhas internas continuam recebendo textos genéricos, sem mensagens do banco, senhas ou dados clínicos.

Incluídas confirmações após cadastro de paciente e login, depois da persistência/autenticação. Mantidas as confirmações existentes de cadastro profissional pendente, perfil, senha, disponibilidade, agendamento, cancelamento, remarcação, pagamento simulado, avaliações, favoritos, registros privados e compartilhamento de resumo. A mensagem de senha alterada continua aparecendo no login após o encerramento da sessão. CSRF inválido continua respondendo HTTP 403 com orientação para recarregar o formulário.

Testados sucesso, erro, escaping, destino após redirecionamento e consumo da mensagem uma única vez. O cadastro de psicólogo informa corretamente a necessidade de aprovação.

## 2. Task #62 — exportação administrativa

O botão **Exportar CSV** está habilitado. O endpoint exige administrador autenticado e ativo. Usa `pageData('relatoriosAdmin.php', $user)`, a mesma fonte da tela, para exportar indicadores reais. Inclui usuários por perfil, profissionais ativos/aprovados, novos usuários, total de consultas, concluídas, canceladas, demais status, taxas, média diária, pacientes atendidos, agendamentos criados, consultas por profissional e evolução diária.

O CSV utiliza UTF-8 com BOM, separador `;`, campos com escape por `fputcsv` e linhas CRLF. Cada indicador informa seu período/referência e data de geração em Brasília. Os indicadores mensais seguem o mês atual; totais de contas são identificados como totais atuais; o gráfico mantém os últimos sete dias, exatamente como a tela. A alteração de período já estava desativada e permanece assim. Não existem filtros ativos a transportar para o download. Parâmetros extras, incluindo tentativas de período não suportado, são recusados com HTTP 400.

Somente dados agregados entram no arquivo. Nomes, e-mails, textos clínicos, senhas e dados de pagamento individuais não são exportados. O endpoint aceita apenas GET; o download não modifica dados.

## 3. Task #63 — prontuário em PDF

Incluído **Baixar prontuário em PDF** no início da tela de histórico do paciente. O arquivo A4 contém título Mindly, identificação pelo nome do paciente, geração em Brasília, data/hora e número da consulta, profissional, status, resumo compartilhado, data do compartilhamento e páginas numeradas. Os registros seguem ordem cronológica crescente; textos longos recebem quebra de página.

Reutilizada `MindlyData::sharedHistory()`, que restringe a leitura ao paciente autenticado, exige consulta concluída e valida o profissional autor do resumo. Não há leitura de `registros_prontuario` pelo exportador. Consultas concluídas sem resumo recebem a mesma orientação da tela. Conta sem histórico recebe PDF com estado vazio. IDs de paciente e quaisquer parâmetros adicionais são recusados. Administrador e psicólogo não podem usar essa rota de paciente.

O PDF é gerado em memória. Conteúdo clínico é escrito como texto, sem interpretação de HTML, carregamento de URLs ou imagens externas. Falha na geração redireciona à tela de origem com mensagem de erro.

## 4. Revisão das tasks #55, #58 e #64

| Task | Resultado da revisão executada |
|---|---|
| #55 — foto | Upload real de PNG por paciente e psicólogo; formato inválido e tamanho acima de 2 MB recusados; foto anterior preservada em erro; conta da sessão prevalece sobre ID enviado; persistência após novo login; acesso ao avatar privado de outro paciente bloqueado. |
| #58 — avaliação | Notas 1 e 5 persistidas, nota 6 recusada, comentário preenchido e ausente, consulta alheia e consulta cancelada recusadas, duplicação impedida, vínculos preservados e avaliação refletida no perfil profissional. A validação do servidor limita a nota inteira a 1–5. |
| #64 — senha | CSRF exigido; senha atual incorreta, confirmação divergente e nova senha igual à anterior recusadas; erro mantém sessão; sucesso altera o hash, encerra a sessão, redireciona ao login e exibe mensagem; senha antiga recusada e nova aceita. |

Essas funcionalidades foram preservadas. Foi corrigido um problema da suíte anterior: `paciente_integration.php` chamava `location()` sem definir o helper. A suíte foi ampliada para os cenários acima.

## 5. Revisão geral e banco de dados

As suítes HTTP/MySQL verificaram cadastros, autenticação, revogação de sessões, permissões por perfil, recuperação/redefinição por tokens, dashboards, perfil, busca, favoritos, agenda/disponibilidade, reservas, conflito de horários, remarcação, cancelamento, pagamentos simulados e concorrentes, histórico financeiro, leitura isolada dos prontuários, salvamento privado e compartilhamento por formulário profissional, notificações por conta e páginas administrativas. Foram conferidos os destinos locais dos links/formulários nas páginas cobertas pela suíte do paciente.

O diagnóstico confirmou PHP 8.2.12, `pdo_mysql`, `mbstring`, conexão MySQL com `mindly`, tabelas essenciais InnoDB e operações de cadastro com rollback. A auditoria somente leitura encontrou **zero** ocorrências nas seis verificações: participantes/horários ausentes, horário de outro profissional, mais de uma consulta não cancelada no mesmo horário, avaliação com vínculo/status/nota inválido, resumo de profissional divergente e registro privado vinculado à dupla divergente.

Isso cobre os cenários automatizados descritos; não equivale a testar manualmente cada combinação de campos e estados de todas as telas.

**Banco:** nenhuma migração, tabela, coluna ou SQL de instalação necessários. Não reimportar `mindly.sql`. As suítes usam contas e registros temporários e removem suas fixtures. Os dados existentes foram preservados; a suíte de dados verifica a contagem de contas antes e depois.

## 6. Arquivos criados e alterados

Criados:

- `controller/ExportController.php`: CSV administrativo e PDF do paciente.
- `processamento/exportar.php`: download, autorização, parâmetros, cabeçalhos e tratamento de falhas.
- `css/mensagens.css`: aparência comum das mensagens.
- `tests/auditoria_final.php`: verificações somente leitura de integridade e vínculos.
- `vendor/tcpdf/`: biblioteca PDF, fontes Unicode, licença e arquivos de suporte.
- `RELATORIO_FINAL.md`: esta entrega.

Alterados:

- `controller/Auth.php`: tipo e consumo das mensagens existentes.
- `controller/Controlador.php`: confirmações de cadastro/login.
- `processamento/auth.php`, `processamento/paciente.php`, `processamento/profissional.php`: classificação de erros.
- `css/componentes.css`: inclusão dos estilos de mensagem.
- `view/home.php`, `view/login.php`, `view/redefinirSenha.php`: estilo compartilhado e erro de token inválido.
- `view/relatoriosAdmin.php`, `view/prontuarioPaciente.php`: links de download.
- `tests/dados_integration.php`, `tests/paciente_integration.php`: regressões, downloads, isolamento e helper ausente.
- `README.md`: referência a este relatório.

## 7. Dependência PDF e instalação

Não havia biblioteca PDF no repositório. Foi incluída **TCPDF 6.6.2**, já distribuída no XAMPP local em `C:/xampp/phpMyAdmin/vendor/tecnickcom/tcpdf`, copiada para `vendor/tcpdf` com seus arquivos de licença e fontes DejaVu Sans. O projeto usa sua própria cópia, sem referência ao phpMyAdmin em tempo de execução. Nenhuma instalação por Composer ou conexão externa é necessária para executar a entrega: mantenha a pasta `vendor/tcpdf` junto do projeto.

Origem e versão podem ser conferidas em `vendor/tcpdf/composer.json` e `vendor/tcpdf/VERSION`; licença em `vendor/tcpdf/LICENSE.TXT`. Para repor exatamente esta instalação local, copie o diretório de origem completo para `vendor/tcpdf`. Em outro ambiente, distribua a pasta incluída nesta entrega. Os testes opcionais de extração usam o `pdftotext.exe` do Git local; ele não é dependência da aplicação nem requisito para baixar PDFs.

## 8. Testes e resultados

Executados com Apache/MySQL ativos em `http://localhost/ProjetoGestaoAgil`:

```powershell
C:\xampp\php\php.exe tests/auth_integration.php http://localhost/ProjetoGestaoAgil
C:\xampp\php\php.exe tests/dados_integration.php http://localhost/ProjetoGestaoAgil
C:\xampp\php\php.exe tests/paciente_integration.php http://localhost/ProjetoGestaoAgil
C:\xampp\php\php.exe tests/diagnostico_cadastro_mindly.php
C:\xampp\php\php.exe tests/auditoria_final.php
```

Todos passaram na execução final de cada suíte. As falhas intermediárias da ampliação dos testes foram resolvidas: helper ausente, expectativa de nome alterado pela fixture profissional, representação binária do texto no PDF e sobreposição inicial do rodapé. O rodapé agora usa o ciclo de paginação da biblioteca.

Downloads validados por HTTP, cabeçalhos de anexo, Content-Type, estrutura CSV/BOM, contagem real do banco, isolamento entre perfis e rejeição de parâmetros. PDFs abertos pelo parser TCPDF e pelo leitor independente Poppler (`C:/Program Files/Git/mingw64/bin/pdftotext.exe`), sem erros. Extração verificou acentos, duas ou mais páginas, ordem cronológica e ausência de conteúdo clínico privado/de outra conta.

Também executados: sintaxe dos 73 arquivos PHP de todo o projeto, incluindo biblioteca adicionada, e `git diff --check`, sem erros.

Nesta instalação Windows, a exclusão de algumas imagens temporárias gravadas pelo Apache exigiu execução com permissão adicional. A limpeza final foi concluída somente para arquivos com o conteúdo exato da fixture e sem vínculo em `usuarios.avatar_url`. Ao repetir testes sob permissões distintas entre Apache e terminal, conferir também a limpeza dos avatares de teste.

Não executados: `tests/cadastro_validation.js` (Node.js não localizado nesta sessão), inspeção visual/interação em navegador (ferramenta de execução necessária indisponível) e abertura manual em Excel/Adobe Reader. A compatibilidade estrutural foi validada pelos testes descritos. Entrega real de e-mails não foi testada: o transporte está desabilitado por configuração; o sistema comunica essa indisponibilidade.

## 9. Demonstração local

1. **#59 — mensagens:** acesse `http://localhost/ProjetoGestaoAgil/view/login.php` e entre como paciente. Confira a confirmação no dashboard. Abra `http://localhost/ProjetoGestaoAgil/view/perfilPaciente.php`, salve um dado válido e confira sucesso; recarregue e confirme que não reaparece. Envie um e-mail inválido para conferir o erro. Use dados da sua conta de demonstração.
2. **#62 — CSV:** entre com conta administrativa ativa e abra `http://localhost/ProjetoGestaoAgil/view/relatoriosAdmin.php`. Clique **Exportar CSV**, abra o arquivo no Excel e compare os indicadores com a tela. O download direto é `http://localhost/ProjetoGestaoAgil/processamento/exportar.php?tipo=relatorios`. Conta de paciente/psicólogo recebe 403.
3. **#63 — PDF:** entre como paciente e abra `http://localhost/ProjetoGestaoAgil/view/prontuarioPaciente.php`. Clique **Baixar prontuário em PDF** e confira nome, datas, profissionais e resumos. O download direto é `http://localhost/ProjetoGestaoAgil/processamento/exportar.php?tipo=prontuario`. Para demonstrar conteúdo, use uma consulta concluída com resumo explicitamente compartilhado pelo psicólogo. Sem histórico, o documento informa a ausência de registros. Acrescentar `&paciente_id=outro_id` deve receber 400.

Não foram criadas credenciais administrativas fixas nem alterados usuários existentes para a apresentação.

## 10. Pendências e limites

- Fazer a inspeção visual manual das telas e dos documentos no navegador/Excel/leitor PDF da apresentação.
- Repetir o teste JavaScript quando Node.js estiver disponível.
- Recuperação com envio real depende de transporte de e-mail configurado; aprovação profissional e documentos de termos/privacidade conservam as limitações já registradas no README.
- Alterar período do relatório permanece desabilitado, como no projeto recebido. O CSV respeita os critérios efetivos da visualização atual.
- Pagamento é simulação acadêmica; reembolso não é automático. Videochamada não foi implementada.

Nenhuma pendência funcional foi identificada nos cenários automatizados finais das três novas tasks. As validações visuais e os serviços externos acima permanecem explicitamente pendentes.
