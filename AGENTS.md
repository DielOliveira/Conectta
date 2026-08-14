# Conectta - Contexto Para Codex

## Projeto

- Aplicacao Laravel/Filament do sistema Conectta.
- Raiz do repositório: `/home/diel_/Conectta`.
- Aplicacao principal: `/home/diel_/Conectta/app`.
- Trabalhe normalmente dentro de `/home/diel_/Conectta/app`.
- Stack local: Laravel 13, Filament, MySQL, Vite/NPM.
- Idioma da interface: portugues do Brasil.
- Painel Filament em `/admin`; a rota raiz redireciona para `/admin/login`.
- Navegacao superior do Filament, nesta ordem: `Cadastro`, `Financeiro`, `Estoque`, `Rotinas`, `Administrativo`.
- Principais pastas:
  - `app/Filament/Pages`: telas customizadas como Financeiro, Boletos, Faturamento, Integracoes e Restore.
  - `app/Filament/Resources`: CRUDs Filament de Clientes, Rastreadores, Contratos, Vendedores, Tecnicos, Usuarios, Auditoria e Rotinas.
  - `app/Services`: integracoes Lytex/ZapSign/WhatsApp, cobrancas, auditoria e restore.
  - `routes/api.php`: webhooks externos.
  - `routes/console.php`: comandos artisan e scheduler.
  - `scripts/`: automacoes locais de deploy, ngrok e backups.

## Cuidados Gerais

- Nao commitar `.env`, tokens, senhas, payloads de API ou arquivos de backup.
- A pasta `payloads-whatsapp/` deve ficar ignorada no git porque contem traces e tokens.
- `.env` e `storage/app/private` estao ignorados; dumps `.sql.gz` de producao nao devem entrar no Git.
- Antes de alterar algo, preferir ler o padrao existente do projeto.
- Usar `rg` para buscas.
- Usar `apply_patch` para edicoes manuais.
- Nao reverter mudancas do usuario sem pedido explicito.
- Evitar comandos destrutivos como `git reset --hard` ou `git checkout --` sem autorizacao clara.
- Em alertas GitGuardian, conferir o commit e procurar valor real de segredo. Em 2026-07-07 houve alerta de `Generic Database Assignment` por causa do script de backup com nomes `DB_PASSWORD`/MySQL; nao havia senha real versionada.

## Comandos Locais Uteis

Rodar servidor local:

```bash
cd ~/Conectta/app
php artisan serve --host=127.0.0.1 --port=8000
```

Expor local via ngrok:

```bash
/home/diel_/bin/ngrok http 8000
curl -s http://127.0.0.1:4040/api/tunnels
```

Build frontend:

```bash
cd ~/Conectta/app
npm run build
```

Migrations:

```bash
cd ~/Conectta/app
php artisan migrate
php artisan migrate:status --pending
```

Limpar caches:

```bash
cd ~/Conectta/app
php artisan optimize:clear
```

Testes e lint simples:

```bash
php -l app/Services/Backup/BackupRestoreService.php
php artisan test
```

Seeders usados com frequencia:

```bash
php artisan db:seed --class=PaisSeeder
php artisan db:seed --class=CobrancaMensagemModeloSeeder
```

## Git E Deploy

- Branch principal: `main`.
- O script de deploy exige worktree limpo antes de publicar.
- Script de deploy criado:

```bash
cd ~/Conectta/app
./scripts/deploy-production.sh
```

Para salvar log do deploy:

```bash
./scripts/deploy-production.sh 2>&1 | tee /tmp/conectta-deploy.log
```

Ao analisar deploy:

```bash
tail -n 100 /tmp/conectta-deploy.log
grep -nEi "error|failed|fatal|exception|denied|timeout|not found" /tmp/conectta-deploy.log || true
```

Script de commit local:

```bash
cd ~/Conectta/app
./scripts/commit-local.sh
```

Backup manual/instalacao de rotina do banco em producao:

```bash
cd ~/Conectta/app
./scripts/backup-production-db.sh
```

O script instala/atualiza `/usr/local/sbin/conectta-db-backup` na VPS, configura o cron diario `15 2 * * *` em `/etc/cron.d/conectta-db-backup`, cria dump MySQL com `mysqldump | gzip`, guarda os backups remotos em `/var/backups/conectta/mysql` e baixa o backup gerado para `storage/app/private/backups/production-db/`.

Observacao do script de backup: apos alerta GitGuardian em 2026-07-07, a versao local pendente passou a evitar `MYSQL_PWD` e usa arquivo temporario MySQL com permissao `600`.

Outras acoes:

```bash
./scripts/backup-production-db.sh install
./scripts/backup-production-db.sh run
./scripts/backup-production-db.sh download-latest
./scripts/list-production-backups.sh
```

O deploy usa SSH para GitHub com `~/.ssh/id_ed25519`. Se falhar com `Permission denied (publickey)`, carregar a chave antes:

```bash
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/id_ed25519
ssh -T git@github.com
```

## VPS Producao

- IP: `191.252.200.172`.
- Host: `vps68591.publiccloud.com.br`.
- Usuario SSH: `root`.
- Chave SSH local: `~/.ssh/conectta_vps`.
- Comando SSH:

```bash
ssh -F /dev/null -i ~/.ssh/conectta_vps -o IdentitiesOnly=yes root@191.252.200.172
```

- App na VPS: `/var/www/conectta/repo/app`.
- Repo na VPS: `/var/www/conectta/repo`.
- Dominio: `https://sistemaconectta.com.br`.
- Login admin local/producao inicial: `admin@conectta.local`.
- Senha admin da VPS fica em `/root/conectta-admin-password`.
- Senha DB da VPS fica em `/root/conectta-db-password`.
- Timezone esperado: `America/Sao_Paulo`.

Validacoes comuns na VPS:

```bash
ssh -F /dev/null -i ~/.ssh/conectta_vps -o IdentitiesOnly=yes root@191.252.200.172 'cd /var/www/conectta/repo && git status --short --branch && git rev-parse --short HEAD && cd app && php artisan migrate:status --pending'
curl -I https://sistemaconectta.com.br/admin/login
```

Observacao: a VPS pode emitir `Deprecation Notice` do Composer por usar Composer antigo com PHP 8.4. Se o deploy concluir, isso nao bloqueia. Melhor melhoria futura: atualizar Composer global da VPS.

## Integracoes

Para tarefas envolvendo o serviço próprio de WhatsApp, envio de PIX ou boleto/PDF pelo J-API, ler primeiro [`J-API.md`](J-API.md). Esse guia documenta endpoints localhost, payloads, segurança, limitações do WhatsApp Web, estado de produção e o fluxo recomendado de migração com rollback para Z-API. Não iniciar a migração apenas por encontrar o documento; aguardar pedido explícito do usuário.

Endpoints de producao para webhooks:

```text
Lytex:   https://sistemaconectta.com.br/api/webhooks/lytex
ZapSign: https://sistemaconectta.com.br/api/webhooks/zapsign
```

Endpoints locais quando usar ngrok:

```text
Lytex:   {NGROK_URL}/api/webhooks/lytex
ZapSign: {NGROK_URL}/api/webhooks/zapsign
```

### Lytex

- Configuracoes ficam na tela `Integracoes`.
- Existe suporte a producao e homologacao.
- Ambiente local pode estar apontado para homologacao.
- Servico principal: `App\Services\Lytex\LytexInvoiceService`.
- Helper de campos da invoice: `App\Services\Lytex\LytexInvoiceData`.
- Webhook: `App\Http\Controllers\LytexWebhookController`.
- Logs de webhook: `lytex_webhook_logs`.
- Invoices ficam em `invoices`.
- Campos importantes:
  - `fatura_id`
  - `hash_id`
  - `link_checkout`
  - `link_boleto`
  - `linha_digitavel`
  - `pix_copia_cola`

Links Lytex por `hash_id`:

```text
Producao invoice: https://checkout-pay.lytex.com.br/fatura/{hashId}
Producao boleto:  https://public-api-pay.lytex.com.br/v1/invoices/print/{hashId}
Sandbox invoice:  https://sandbox-checkout-pay.lytex.com.br/fatura/{hashId}
Sandbox boleto:   https://sandbox-public-api-pay.lytex.com.br/v1/invoices/print/{hashId}
```

`detalhesFatura` pode retornar dados de pagamento em `transactions`:

- `transactions[].paymentMethod = boleto`
- `transactions[].boleto.digitableLine`
- `transactions[].pix.qrcode`
- `transactions[].boleto.qrCode.emv`

### WhatsApp / Z-API

- Servico: `App\Services\Whatsapp\ZapiWhatsappService`.
- Orquestracao de cobrancas: `App\Services\Cobranca\CobrancaWhatsappService`.
- Variaveis esperadas:

```env
WHATSAPP_ZAPI_BASE_URL=https://api.z-api.io
WHATSAPP_ZAPI_INSTANCE_ID=
WHATSAPP_ZAPI_TOKEN=
WHATSAPP_ZAPI_CLIENT_TOKEN=
WHATSAPP_ZAPI_TIMEOUT=30
WHATSAPP_ZAPI_PIX_ENDPOINT=send-button-pix
```

Metodos usados:

- `send-text`
- `send-document/PDF`
- `send-button-pix` configuravel por env.

### ZapSign

- Servico: `App\Services\ZapSign\ZapSignService`.
- Webhook: `App\Http\Controllers\ZapSignWebhookController`.
- Logs de webhook: `zapsign_webhook_logs`.
- Contratos usam rota autenticada para documento em `/admin/contratos/{contrato}/documento`.

## Restore E Dados Legados

- Tela `Administrativo > Restore Backup` existe e deve ficar disponivel para `Tecnico`/admin; nao remover porque ajuda investigar bugs de importacao.
- Servico principal: `App\Services\Backup\BackupRestoreService`.
- Leitor XLSX: `App\Services\Backup\XlsxTableReader`.
- Arquivos esperados pelo restore incluem: `Cliente.xlsx`, `Veiculo.xlsx`, `Lancamento.xlsx`, `Contrato.xlsx`, `Rastreador.xlsx`, `Chip.xlsx`, `Vendedor.xlsx`, `Tecnico.xlsx`.
- O restore trunca/importa tabelas controladas e depois garante dados padrao via seeders quando aplicavel.
- O restore corrige relacoes por criterio/label em alguns pontos; cuidado com tabelas legadas de tipo/status porque IDs antigos podem nao bater com IDs atuais.
- No restore, somente para `lancamentos.numero_boleto` e `lancamentos.observacao`, os marcadores `-` e `'-` devem ser preservados como `-`; nas demais tabelas o marcador de importacao continua sendo tratado como vazio quando aplicavel.

Correcoes de dados ja aplicadas em producao em 2026-07-07:

- `tipo_veiculo_id`: corrigido por mapa legado; 9.230 veiculos atualizados. Caso validado: Juscelon Ferreira de Jesus voltou para `Carro`.
- `cliente_origem_id`: 793 clientes corrigidos.
- `status_contrato_id`: 2.202 clientes e 457 contratos corrigidos.
- `status_rastreador_id`: 1 rastreador corrigido para `Lixo`.
- `lancamentos.numero_boleto`: 6.756 registros corrigidos para `-`, com base nos arquivos iniciais do restore.
- `lancamentos.observacao`: 6.637 registros corrigidos para `-`; registros com conteudo real foram preservados.
- Backup antes da correcao dos hifens: `storage/app/private/backups/production-db/conectta-conectta-20260707-195453.sql.gz`, SHA256 `c4eb88e94e21298db005d194ea15614e8858d94a125edd02d501737847e41945`.

## Rotinas De Cobranca

Menu principal: `Rotinas`.

No menu superior, `Rotinas` deve ficar entre `Estoque` e `Administrativo`.

O acesso, visualizacao e manipulacao das telas de `Rotinas` deve ser restrito a usuarios com permissao `Tecnico`; admin continua acessando porque `User::hasPermission()` libera tudo para `is_admin`.

Telas:

- `Cobranças automáticas`
- `Mensagens de cobrança`

`Envios de cobrança` existe como resource interno, mas nao deve aparecer no menu. Os envios aparecem no detalhe de uma execucao.

Tabelas:

- `cobranca_execucoes`
- `cobranca_envios`
- `cobranca_mensagem_modelos`

Modelo atual:

- Cada execucao representa um unico `tipo`.
- Ao rodar o comando completo, ele cria uma execucao separada para cada tipo.

Tipos:

- `boleto_7_dias`
- `lembrete_vencimento`
- `atraso_2`
- `atraso_5`
- `atraso_7`
- `atraso_10`
- `atraso_12`
- `atraso_15`

Regras principais:

- Cliente inativo tambem pode receber cobranca.
- Roda sabado, domingo e feriado normalmente.
- `valor_planejado` precisa ser maior que zero.
- Se houver qualquer `valor_efetivado > 0` na referencia, nao cobra mais, mesmo pagamento parcial.
- Dia de pagamento 30/31 em mes menor cai no ultimo dia do mes.
- `boleto_7_dias`:
  - vencimento daqui a 7 dias;
  - se `numero_boleto` vazio, gera boleto na Lytex;
  - ao gerar, grava `numero_boleto = Lytex`;
  - cria envio `pendente_whatsapp` quando executado de verdade.
- `lembrete_vencimento`:
  - vencimento hoje;
  - envia apenas mensagem, sem boleto.
- Atrasos:
  - vencido ha 2, 5, 7, 10, 12 ou 15 dias;
  - nao gera boleto;
  - exige `numero_boleto = Lytex`;
  - usa invoice existente;
  - se houver outros boletos vencidos de meses anteriores do mesmo cliente, inclui tambem no mesmo envio;
  - no atraso, o WhatsApp envia apenas mensagem principal e PDF(s) de boleto, sem linha digitavel, PIX ou finalizacao.

Comando de processamento:

```bash
php artisan cobrancas:processar
```

Por seguranca, sem `--executar` roda em simulacao.

Exemplos:

```bash
php artisan cobrancas:processar --limit=1
php artisan cobrancas:processar --tipo=boleto_7_dias --limit=1
php artisan cobrancas:processar --cliente=1470 --limit=1
php artisan cobrancas:processar --tipo=boleto_7_dias --cliente=1470 --limit=1 --executar
```

Comando de envio WhatsApp:

```bash
php artisan cobrancas:enviar-whatsapp
```

Tambem simula por padrao. Para enviar real:

```bash
php artisan cobrancas:enviar-whatsapp --envio=ID --executar
php artisan cobrancas:enviar-whatsapp --cliente=1470 --limit=1 --executar
```

Fluxo WhatsApp de boleto 7 dias antes:

1. mensagem principal;
2. PDF do boleto;
3. linha digitavel;
4. mensagem de instrucao PIX;
5. botao PIX;
6. finalizacao.

Fluxo WhatsApp de lembrete de vencimento:

1. mensagem principal apenas.

Fluxo WhatsApp de atraso:

1. mensagem principal;
2. PDF do boleto principal;
3. PDFs de outros boletos vencidos de meses anteriores do mesmo cliente, quando existirem.

Os PDFs de boleto usam nome com mes/ano da referencia, por exemplo `BoletoConectta_Julho_2026.pdf`.

Os textos principais ficam em `cobranca_mensagem_modelos`, pela tela `Rotinas > Mensagens de cobrança`. A ordem/tipo das etapas ainda fica fixa em `App\Services\Cobranca\CobrancaWhatsappService`.

O restore garante os modelos padrao de mensagens de cobranca ao final do processo. O seeder `CobrancaMensagemModeloSeeder` usa `firstOrCreate`, entao cria quando faltar, mas nao sobrescreve textos editados pela tela.

## Financeiro

- Tela principal: `App\Filament\Pages\Financeiro` + `resources/views/filament/pages/financeiro.blade.php`.
- Acesso: `Financeiro_Leitura`; alteracoes usam `Financeiro_Escrita`.
- Busca digitavel principal usa sessao `conectta.busca_cadastros` e sincroniza com `Cadastro > Clientes` e `Cadastro > Rastreadores`.
- Filtro de status do cliente usa sessao `conectta.status_cliente` e sincroniza somente entre `Financeiro` e `Cadastro > Clientes`.
- Ao limpar filtros no Financeiro, a busca compartilhada e o status compartilhado tambem sao atualizados.
- Ordenacao permitida no bloco de clientes: `qtd`, `vendedor`, `cliente`, `vencimento`. Nao ordenar pelos blocos de meses.
- Layout atual do bloco de clientes prioriza espaco para `Cliente` e `Anotacoes`; vendedor deve ficar mais compacto.
- Valores `0,00` nos blocos dos meses devem aparecer em branco.
- O botao `Historico` fica dentro da tela Financeiro, entre `Limpar` e `Exportar`; a tela de historico nao aparece no menu.
- Export CSV do Financeiro usa os filtros/linhas atuais e inclui dados dos dois meses exibidos.
- Modal de lancamento:
  - aba `Lancamento` salva tambem ao apertar Enter nos campos da aba;
  - campo `Data Lancamento` abre com a data salva quando existir; se estiver vazio, abre preenchido com a data do dia;
  - gerar boleto mostra estado de loading no botao/link `Gerar Boleto` ate a resposta da Lytex;
  - boleto gerado pela Lytex grava `numero_boleto = Lytex` no lancamento da referencia.
- Modal de boleto usa vencimento padrao calculado pelo dia de pagamento do cliente; dia 30/31 em mes menor cai no ultimo dia do mes.
- A tela `Financeiro > Historico Financeiro` usa a tabela `audit_logs` para exibir alteracoes de lancamentos e parcelamentos financeiros.
- O historico financeiro deve exibir somente logs em que houve alteracao de `valor_efetivado` ou `data_lancamento`; alteracoes de observacao, numero de boleto, valor planejado ou outros campos nao devem aparecer nessa tela.
- A tela considera as acoes:
  - `financeiro.lancamento_criado`;
  - `financeiro.lancamento_editado`;
  - `financeiro.parcelamento_criado`;
  - `financeiro.parcelamento_excluido`.
- Os campos `Total Antes` e `Total Depois` sao gravados no `contexto` dos logs novos; logs antigos podem aparecer sem esses totais porque esse dado nao era persistido no momento da operacao.
- O acesso ao historico financeiro usa a permissao `Financeiro_Leitura`.

## Playbook De Incidentes Financeiros

Use este procedimento ao investigar boletos duplicados, cobrancas ou mensagens financeiras indevidas em producao:

1. Comecar somente com diagnostico e avisar o usuario antes de qualquer alteracao.
2. Identificar todos os casos afetados, sem presumir que os exemplos informados sao a lista completa.
3. Correlacionar `clientes`, `lancamentos`, `invoices`, `cobranca_envios`, webhooks, auditorias e o estado real na Lytex.
4. Conter primeiro a causa no codigo, com idempotencia, bloqueio de duplicidade e testes proporcionais ao risco.
5. Antes do saneamento em PD, confirmar os alvos e gerar backup quando o procedimento autorizado permitir.
6. Classificar os casos por estado: nao pago/cancelavel, pago unico e pagamentos multiplos ou ambiguos. Nao decidir casos ambiguos sem confirmacao da central.
7. Preferir saneamento reversivel. Nao excluir lancamentos, invoices, envios, webhooks ou auditorias quando for possivel invalidar o registro operacionalmente.
8. Para lancamentos duplicados, usar `invalidado_em` e `motivo_invalidacao`. Consultas operacionais usam `Lancamento::validos()` ou `WHERE invalidado_em IS NULL`; telas historicas e auditorias preservam os invalidos.
9. Cancelar na Lytex somente o boleto duplicado confirmado como nao pago. Verificar novamente o status imediatamente antes de cancelar ou comunicar o cliente.
10. Quando necessario, enviar correcao ao cliente com apenas o boleto/PDF e PIX validos, mantendo auditoria de cada etapa e evitando novo envio duplicado.
11. Validar depois da mudanca: commit em PD, migration aplicada, quantidade de registros afetados, ausencia de neutralizados nas consultas operacionais e resposta HTTP da aplicacao.
12. Nunca preencher ou consolidar automaticamente `valor_efetivado`, `data_lancamento` ou `is_baixado` com base apenas no status da Lytex. Esses campos sao responsabilidade da central apos a validacao financeira. A investigacao pode relatar o pagamento confirmado, mas deve aguardar autorizacao explicita da central para registrar a baixa.
13. Se uma acao indevida precisar ser revertida, usar os snapshots de `audit_logs`, preservar alteracoes posteriores feitas por usuarios/central e registrar uma nova auditoria da reversao.

Principio operacional: conter, diagnosticar, preservar evidencias, corrigir de forma reversivel, comunicar, auditar e validar em producao.

## Rastreadores E Estoque

- Existem duas telas com nomes parecidos:
  - `Cadastro > Rastreadores`: lista `veiculos` com rastreador vinculado/instalado.
  - `Estoque > Rastreadores`: lista a tabela real `rastreadores`.
- A combo `IMEI` em `Cadastro > Rastreadores` mostra apenas rastreadores com status `Disponivel`, alem do rastreador ja vinculado quando estiver editando um registro existente.
- Ao criar rastreador em `Estoque > Rastreadores`, o status padrao deve ser `Disponivel`, para evitar criar estoque novo como `Ativo`.
- Chips sao vinculados ao rastreador, nao ao veiculo. A tabela `rastreadores` possui `chip_id`; o campo legado `veiculos.chip_id` nao deve ser usado em novas regras/telas.
- Em `Estoque > Chips`, o formulario usa schema Filament e o campo `IMEI` e um `Select` pesquisavel por busca remota, sem preload da tabela inteira. Pode haver chip sem rastreador vinculado.
- Em `Estoque > Chips`, `Numero Chip` e `ICCID` sao campos separados: `numero_chip` contem o numero telefonico/numero atual do chip; `iccid` contem o ICCID real, deve ser unico quando preenchido e ter exatamente 20 digitos no formulario.
- Em `Cadastro > Rastreadores`, o chip nao e selecionado manualmente. O campo `Numero Chip` e somente leitura e exibe o chip vinculado ao IMEI/rastreador escolhido.
- Em `Cadastro > Rastreadores`, se o IMEI escolhido nao tiver chip vinculado, o campo `Numero Chip` mostra aviso amarelo: `O rastreador selecionado nao possui chip vinculado.`
- Ao salvar um veiculo ativo com rastreador/chip pela tela `Cadastro > Rastreadores`, os equipamentos instalados devem sair do estoque do tecnico:
  - o rastreador fica com status `Ativo`, `tecnico_id = null` e `is_estoque = false`;
  - o chip vinculado fica com status `Ativo` e `tecnico_id = null`;
  - o vinculo do chip permanece em `rastreadores.chip_id` e o rastreador permanece em `veiculos.rastreador_id`;
  - antes de limpar o tecnico do equipamento, o tecnico de instalacao deve ficar preservado em `veiculos.tecnico_instala_id` e `veiculos.instalador`;
  - edicoes posteriores do veiculo nao podem apagar o tecnico de instalacao somente porque o rastreador instalado ja esta com `tecnico_id = null`.
- Regra de integridade: um mesmo IMEI/rastreador nao pode estar vinculado a outro veiculo ativo. Clientes com frota podem ter varios rastreadores ativos; nao bloquear por cliente nem por placa duplicada.
- Regra de integridade: um chip deve ficar vinculado a no maximo um rastreador. Na migracao de dados legados, quando um chip aparecia em mais de um rastreador ativo, foi mantido no registro mais recente por `data_instalacao`, depois `updated_at`, depois `id`; os demais ficaram sem chip para verificacao manual.
- O restore cria/encontra chips por `numero_chip` e vincula o chip ao `rastreador_id` importado, deixando `veiculos.chip_id` nulo.
- A busca em `Cadastro > Rastreadores` pesquisa placa, veiculo e cliente. A busca por IMEI so deve entrar quando a parte numerica tiver pelo menos 6 digitos, para uma placa como `QDW-9C47` nao buscar IMEIs contendo `947`.
- A busca em `Cadastro > Rastreadores` tambem pesquisa CPF/CNPJ do cliente apenas quando a parte numerica tiver pelo menos 6 digitos, para placas Mercosul como `RBO-6G53` nao buscarem CPFs/CNPJs contendo poucos digitos.
- A busca digitavel principal e compartilhada via sessao entre `Financeiro`, `Cadastro > Clientes` e `Cadastro > Rastreadores`; ao digitar em uma dessas telas, o mesmo termo deve ser reaplicado ao navegar para as outras. O botao `Limpar` dessas telas tambem limpa a busca compartilhada.
- O filtro de status do cliente e compartilhado via sessao somente entre `Financeiro` e `Cadastro > Clientes`; `Cadastro > Rastreadores` nao participa desse status compartilhado.
- Em `Cadastro > Rastreadores`, a busca tambem deve encontrar pelo CPF/CNPJ do cliente vinculado.
- O export CSV de `Cadastro > Clientes` deve conter: Data Adesao, Nome, RG, CPF CNPJ, Telefone, DN, Email, Status, Empresa, Qtd Veiculos, Origem e Vendedor.
- Nas listas `Clientes` e `Cadastro > Rastreadores`, a linha inteira nao deve abrir o detalhe. A navegacao deve ficar nos icones/botoes de acao, especialmente `Editar`.
- Usuarios com `Cadastro_Leitura` podem abrir `Clientes` e `Cadastro > Rastreadores` pelo botao `Ver`, que reaproveita a tela de edicao com formulario desabilitado e sem botao de salvar; `Cadastro_Escrita` ve a mesma acao como `Editar`.
- Essas duas listas usam a classe `ct-selectable-table` e o CSS `public/css/conectta-admin.css` para permitir selecionar/copiar texto das celulas.

## Menus E Permissoes

- Grupo `Cadastro`: Clientes, Rastreadores, Contratos e Vendedores.
- Grupo `Financeiro`: Financeiro, Relatorio Geral, Boletos, Faturamento; Historico Financeiro existe por botao interno e nao registra menu.
- Grupo `Estoque`: Rastreadores, Chips e Tecnicos.
- Grupo `Rotinas`: Cobrancas automaticas e Mensagens de cobranca; Envios/Execucoes podem existir como resources internos.
- Grupo `Administrativo`: Integracoes, Usuarios, Auditoria e Restore Backup, conforme permissoes abaixo.
- `Vendedores` usa permissoes de cadastro:
  - leitura: `Cadastro_Leitura`;
  - criar/editar: `Cadastro_Escrita`;
  - excluir: `Cadastro_Exclusao`.
- As telas administrativas `Integracoes` e `Restore Backup` ficam visiveis apenas para usuarios com a permissao `Tecnico`; admin continua acessando porque `User::hasPermission()` libera tudo para `is_admin`.
- As telas administrativas `Usuarios` e `Auditoria` ficam visiveis para usuarios com a permissao `Coordenador`; em `Usuarios`, essa permissao permite criar, editar e excluir usuarios.
- Usuarios com permissao `Coordenador` nao podem criar/promover administradores, editar/excluir usuarios admin nem alterar permissoes de usuarios admin; somente admin pode mexer em admin.
- Usuarios com permissao `Coordenador` tambem podem manter `Vendedores` e `Tecnicos`, mesmo sem as permissoes de `Cadastro` ou `Estoque`.
- Usuarios admin continuam com acesso total porque `User::hasPermission()` libera tudo para `is_admin`.
- `canAccessPanel()` retorna true para usuarios autenticados; o controle real fica em `canAccess`, `canCreate`, `canEdit`, `canDelete` e visibilidade de actions/resources.
- Catalogo central de permissoes: `App\Models\Permission::catalogo()`. Seeders/migrations recentes garantem `Tecnico` e `Coordenador`.

## Ordens De Servico

- O fluxo funcional completo esta documentado em `app/fluxo_de_OS.md`; este arquivo e a fonte principal para regras, status, agenda, atendimento tecnico, conferencia, estoque, mensagens e cancelamentos.
- O modulo de OS foi integrado a `main` e publicado em producao em 2026-08-04.
- Grupo de menu: `Ordens de Servico`, com `Ordens de servico`, `Disponibilidades` e `Agenda de OS`.
- Permissoes: `OS_Leitura` e `OS_Escrita`; admin continua com acesso total.
- Cada disponibilidade gera blocos fixos de 1 hora. A atribuicao ocorre pela agenda e envia ao tecnico um link publico unico protegido por token.
- O tecnico aceita ou rejeita, inicia o atendimento, informa equipamentos e fotos e solicita conferencia. A central aprova, devolve como pendencia ou cancela.
- Tipo, cliente e veiculo ficam bloqueados depois da criacao. Uma OS ativa por veiculo e preservada; OS nao e excluida definitivamente.
- Instalacao, retirada e manutencao movimentam rastreadores e chips pelo fluxo da OS, reduzindo atualizacoes manuais do estoque.
- Regras de movimentacao de equipamentos ao finalizar a OS:
  - instalacao: rastreador e chip novos ficam `Ativo`, sem tecnico; o rastreador recebe `is_estoque = false`; ambos ficam vinculados ao veiculo, e o tecnico da OS fica registrado como tecnico de instalacao;
  - manutencao sem troca de equipamento: nao movimenta rastreador nem chip;
  - manutencao trocando somente o chip: o chip novo fica `Ativo` e sem tecnico; o chip retirado fica `Disponivel` e vinculado ao tecnico da OS;
  - manutencao trocando rastreador e chip: os equipamentos novos ficam `Ativo`, sem tecnico, vinculados ao veiculo e com o rastreador fora do estoque; os equipamentos retirados ficam `Disponivel`, vinculados ao tecnico da OS, e o rastreador retirado recebe `is_estoque = true`;
  - retirada: rastreador e chip saem do veiculo, ficam `Disponivel` e vinculados ao tecnico da OS; o rastreador recebe `is_estoque = true`;
  - chips nao possuem campo `is_estoque`; sua disponibilidade e posse sao controladas por `status_rastreador_id` e `tecnico_id`.
- O historico da OS preserva eventos, fotos e todas as mensagens geradas para tecnico e cliente.
- Mensagens de OS ficam fixas e centralizadas em `App\Services\OrdemServico\OrdemServicoNotificacaoService`; usam saudacao, dados do atendimento e formatacao adequada ao WhatsApp.
- `Enviada` no historico de mensagens significa que a Z-API aceitou a chamada sem erro; nao confirma entrega nem leitura. Webhooks de entregue/lida nao fazem parte da primeira versao.
- O scheduler executa `ordens-servico:enviar-notificacoes` a cada minuto e o lembrete da OS a cada cinco minutos.
- O tratamento atual de mensagens com erro deve permanecer como esta ate nova decisao explicita; nao implementar retentativa automaticamente.
- Em producao, novas fotos sao gravadas diretamente em `gdrive:Conectta/ordens-servico` via rclone, usando configuracao privada `/etc/conectta/rclone.conf` com permissao `root:www-data 640`.
- O banco guarda o caminho remoto e a visualizacao continua pela rota protegida da OS; o Drive nao e exposto diretamente.
- A unica foto criada antes dessa correcao continua local em `storage/app/private/ordens-servico/1/` e com caminho local no banco. Sua migracao para o Drive exige autorizacao explicita do usuario por envolver arquivo real de producao.
- Em desenvolvimento, o driver de fotos permanece `local` por padrao. Em producao, o `.env` usa `ORDENS_SERVICO_FOTOS_DRIVER=rclone`.
- Ultimos commits do modulo: `df3f3ea` (primeira versao), `cf26028` (validacao visivel ao criar), `da301a7` (mensagens aprimoradas) e `d19afa2` (fotos no Google Drive).
- Producao foi validada no commit `d19afa2`, versao `1.1.3`, sem migrations pendentes.
- Testes relevantes: `tests/Feature/OrdemServicoFlowTest.php` e `tests/Unit/OrdemServicoFotoStorageTest.php`.
- Materiais de apresentacao gerados localmente:
  - `/home/diel_/Conectta/tmp/banner-fluxo-ordem-servico-conectta.png`;
  - `/home/diel_/Conectta/tmp/banner-vantagens-ordem-servico-conectta.png`.

## Identidade Visual

- O favicon do painel Filament usa `public/favicon.svg`, um marcador de mapa cinza.

## Cliente De Teste Usado

- Cliente: `Diel Oliveira de Faria`.
- ID: `1470`.
- Pode ser usado para testes controlados quando o usuario autorizar.
- Ja foram criados lancamentos de homologacao locais para testes; se poluirem a tela, podem ser removidos com cuidado.

## Estado Atual Importante

- Ultimo commit funcional conhecido em `main`/GitHub/producao: `d19afa2 Armazena fotos das ordens de servico no Drive`.
- Em 2026-08-04, producao estava na versao `1.1.3`, commit `d19afa2`, com o modulo de OS ativo e sem migrations pendentes.
- Em 2026-07-08, producao estava no commit `58cc7e1`; deploy validado com `/admin/login` HTTP 200 e `php artisan migrate:status --pending` sem pendencias.
- Em 2026-07-08, a migracao de chips para rastreadores foi aplicada em producao. Validacao: `rastreadores.chip_id` existe, 4.259 rastreadores ficaram com chip migrado, e nao restaram chips compartilhados entre rastreadores ativos.
- Em 2026-07-08, antes da migracao em producao, 12 chips ainda estavam compartilhados. Foi mantido o vinculo no registro mais recente e 12 rastreadores ficaram sem chip para verificacao manual:
  - veiculo `19942`, rastreador `23138`, IMEI `865209075229493`, cliente `Bacana Veículos LTDA`, placa `BBZ-2H06`;
  - veiculo `19952`, rastreador `20724`, IMEI `869731052574415`, cliente `SPEED Proteção Veicular`, placa `RBM-3F20`;
  - veiculo `20222`, rastreador `19937`, IMEI `865209074809212`, cliente `Rejane Ribeiro e Silva`, placa `PQR-9271`;
  - veiculo `20468`, rastreador `21205`, IMEI `861768073280786`, cliente `Jorge Alberto Vaz da Silva Junior`, placa `JHS-1A11`;
  - veiculo `21456`, rastreador `18807`, IMEI `869412079536501`, cliente `Patrícia Monica da Costa Baldez`, placa `TDZ-4C68`;
  - veiculo `22071`, rastreador `31176`, IMEI `862667083497381`, cliente `Maxientregas Servicos de Entregas LTDA`, placa `PRB-8035`;
  - veiculo `22207`, rastreador `18741`, IMEI `865209070405411`, cliente `Paulo Cesar Silva Coelho`, placa `PRO-4D30`;
  - veiculo `23323`, rastreador `31833`, IMEI `863767071751630`, cliente `Nayane Maria da Silva`, placa `QCU-0J36`;
  - veiculo `23329`, rastreador `31868`, IMEI `863767071642953`, cliente `Matias Loaiza`, placa `RBY-3C78`;
  - veiculo `23533`, rastreador `22974`, IMEI `868018073761341`, cliente `José Carlos Flausino`, placa `IJB-1J99`;
  - veiculo `23799`, rastreador `31957`, IMEI `863767071680847`, cliente `Conectta INVESTIDORES`, placa `TGM-4J46`;
  - veiculo `23918`, rastreador `21106`, IMEI `861768071902563`, cliente `Juliano Cezar Montelo da Silva`, placa `TFD-5G56`.
- Arquivos locais auxiliares gerados em 2026-07-08 ficam em `/home/diel_/Conectta/tmp/`: `conflitos-veiculos-rastreadores-ativos.xlsx`, `chips-vinculados-a-mais-de-um-rastreador-ativo.txt` e `rastreadores-sem-chip-apos-migracao.txt`.
- Alteracao local pendente apos o alerta GitGuardian: `scripts/backup-production-db.sh` foi ajustado para evitar `MYSQL_PWD` e usar arquivo temporario MySQL com permissao `600`; precisa commit/deploy se quiser atualizar a rotina versionada.
- Este `AGENTS.md` foi atualizado no fim do dia 2026-07-07 para economizar contexto na proxima sessao.
- As rotinas de cobranca e WhatsApp foram implementadas localmente.
- Foi validado envio real de WhatsApp para o cliente de teste, envio `11`, com sucesso.
- Foi validada geracao de boleto em homologacao Lytex.
- Foi validada recuperacao de linha digitavel/PIX via `transactions` do `detalhesFatura`.
- A producao ja esta em uso em `https://sistemaconectta.com.br`.
- As rotinas de cobranca existem e o scheduler esta instalado na VPS, mas os agendamentos devem permanecer inativos ate autorizacao explicita.
- Antes de ativar cobrancas em producao: configurar Z-API em `Integracoes`, rodar simulacoes/controladas e ativar os agendamentos desejados.
- GitGuardian em 2026-07-07: alerta de `Generic Database Assignment` no commit `62644c6`; varredura local nao encontrou senha real, `.env` e backups estao ignorados, e o alerta pode ser tratado como falso positivo salvo se o GitGuardian mostrar valor real de segredo.
