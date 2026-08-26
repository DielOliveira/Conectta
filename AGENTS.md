# Conectta - Contexto Para Codex

## Projeto

- Aplicacao Laravel/Filament do sistema Conectta.
- Raiz do repositorio: `/home/diel_/Conectta`.
- Aplicacao principal e diretorio normal de trabalho: `/home/diel_/Conectta/app`.
- Stack local: Laravel 13, Filament, MySQL e Vite/NPM.
- Interface em portugues do Brasil.
- Painel Filament em `/admin`; a raiz redireciona para `/admin/login`.
- Pastas principais:
  - `app/Filament/Pages`: telas customizadas.
  - `app/Filament/Resources`: CRUDs Filament.
  - `app/Services`: integracoes, cobrancas, auditoria, estoque e restore.
  - `routes/api.php`: webhooks externos.
  - `routes/console.php`: comandos e scheduler.
  - `scripts/`: deploy, ngrok e backups.
- Historico de migracoes, incidentes e correcoes antigas: [`docs/historico-operacional.md`](docs/historico-operacional.md). Consultar somente quando a tarefa depender desses eventos.

## Cuidados Gerais

- Antes de alterar algo, ler o padrao existente do projeto.
- Usar `rg` para buscas e `apply_patch` para edicoes manuais.
- Nao reverter mudancas do usuario sem pedido explicito.
- Nao commitar `.env`, tokens, senhas, payloads de API ou backups.
- `payloads-whatsapp/`, `.env`, `storage/app/private` e dumps `.sql.gz` devem permanecer fora do Git.
- Commits, pushes e deploys sao sempre executados pelo usuario. O Codex deve preparar e validar as alteracoes, mas nao deve executar `git commit`, `git push` nem publicar em producao.
- Nao usar comandos destrutivos como `git reset --hard` ou `git checkout --` sem autorizacao clara.
- Em producao, comecar por diagnostico. Antes de qualquer saneamento, confirmar alvos, impacto, backup e autorizacao.
- Mudancas de dados em producao devem ser protegidas por pre-condicoes, preferencialmente transacionais, auditadas e validadas depois.
- Nao confiar em commits, migrations ou estados descritos em historicos como se fossem atuais; verificar Git, banco e VPS no momento da tarefa.

## Comandos Essenciais

```bash
cd /home/diel_/Conectta/app
php artisan serve --host=127.0.0.1 --port=8000
npm run build
php artisan migrate
php artisan migrate:status --pending
php artisan optimize:clear
php artisan test
```

Deploy e manutencao:

```bash
./scripts/commit-local.sh
./scripts/deploy-production.sh
./scripts/backup-production-db.sh
./scripts/list-production-backups.sh
```

- O deploy exige worktree limpo.
- Para expor o ambiente local: `/home/diel_/bin/ngrok http 8000`.
- Seeders usados com frequencia: `PaisSeeder` e `CobrancaMensagemModeloSeeder`.

## Producao

- Dominio: `https://sistemaconectta.com.br`.
- VPS: `root@191.252.200.172` (`vps68591.publiccloud.com.br`).
- Chave SSH: `~/.ssh/conectta_vps`.
- Repositorio: `/var/www/conectta/repo`.
- Aplicacao: `/var/www/conectta/repo/app`.
- Timezone esperado: `America/Sao_Paulo`.
- Senha admin: `/root/conectta-admin-password`.
- Senha do banco: `/root/conectta-db-password`.

```bash
ssh -F /dev/null -i ~/.ssh/conectta_vps -o IdentitiesOnly=yes root@191.252.200.172
```

- O backup de producao usa `/usr/local/sbin/conectta-db-backup`, cron diario `15 2 * * *`, diretorio remoto `/var/backups/conectta/mysql` e copia local em `storage/app/private/backups/production-db/`.
- O script de backup usa arquivo temporario MySQL com permissao `600`; nao voltar a usar `MYSQL_PWD`.
- O Composer antigo da VPS pode emitir avisos deprecados no PHP 8.4; isso nao bloqueia um deploy concluido.

## Integracoes

- Para WhatsApp proprio, PIX ou boleto/PDF via J-API, ler primeiro [`J-API.md`](J-API.md).
- Nao iniciar migracao de Z-API para J-API sem pedido explicito.
- Webhooks de producao:
  - Lytex: `https://sistemaconectta.com.br/api/webhooks/lytex`.
  - ZapSign: `https://sistemaconectta.com.br/api/webhooks/zapsign`.
- Em desenvolvimento, trocar a base pelo endereco do ngrok.

### Lytex

- Configuracao pela tela `Integracoes`, com producao e homologacao.
- Servico: `App\Services\Lytex\LytexInvoiceService`.
- Helper: `App\Services\Lytex\LytexInvoiceData`.
- Webhook: `App\Http\Controllers\LytexWebhookController`.
- Tabelas: `invoices` e `lytex_webhook_logs`.
- Campos importantes: `fatura_id`, `hash_id`, `link_checkout`, `link_boleto`, `linha_digitavel` e `pix_copia_cola`.
- `detalhesFatura` pode trazer linha digitavel e PIX em `transactions`.
- A API v2 da Lytex nao possui campo de pais/DDI em `client.cellphone`; o telefone e opcional na criacao de fatura.
- Ao gerar boleto manualmente ou pela cobranca automatica, validar e enviar `cellphone` somente quando `clientes.telefone1_pais` for `BR` (ou estiver vazio por legado). Para telefone estrangeiro, omitir `cellphone` do payload Lytex sem alterar o telefone salvo no Conectta.

### WhatsApp

- Z-API: `App\Services\Whatsapp\ZapiWhatsappService`.
- Cobrancas: `App\Services\Cobranca\CobrancaWhatsappService`.
- Metodos usados: `send-text`, `send-document/PDF` e endpoint configuravel de PIX.
- Nunca expor variaveis `WHATSAPP_ZAPI_*` ou seus valores.

### ZapSign

- Servico: `App\Services\ZapSign\ZapSignService`.
- Webhook: `App\Http\Controllers\ZapSignWebhookController`.
- Logs: `zapsign_webhook_logs`.
- Documentos de contrato usam rota autenticada `/admin/contratos/{contrato}/documento`.

## Restore E Dados Legados

- `Administrativo > Restore Backup` deve permanecer disponivel para `Tecnico` e admin.
- Servicos: `App\Services\Backup\BackupRestoreService` e `App\Services\Backup\XlsxTableReader`.
- Arquivos esperados incluem `Cliente.xlsx`, `Veiculo.xlsx`, `Lancamento.xlsx`, `Contrato.xlsx`, `Rastreador.xlsx`, `Chip.xlsx`, `Vendedor.xlsx` e `Tecnico.xlsx`.
- O restore trunca/importa somente tabelas controladas e garante dados padrao ao final.
- Relacoes legadas devem ser corrigidas por criterio/label; IDs antigos de tipos e status podem nao coincidir com os atuais.
- Somente em `lancamentos.numero_boleto` e `lancamentos.observacao`, os marcadores `-` e `'-` devem ser preservados como `-`. Nos demais casos, o marcador pode representar vazio.
- O seeder `CobrancaMensagemModeloSeeder` usa `firstOrCreate` e nao pode sobrescrever textos editados pela interface.

## Cobrancas Automaticas

- Menu `Rotinas`, restrito a permissao `Tecnico`; admin continua com acesso total.
- Telas: `Cobrancas automaticas` e `Mensagens de cobranca`.
- `Envios de cobranca` e resource interno e nao aparece no menu.
- Tabelas: `cobranca_execucoes`, `cobranca_envios` e `cobranca_mensagem_modelos`.
- Cada execucao representa um unico tipo: `boleto_7_dias`, `lembrete_vencimento`, `atraso_2`, `atraso_5`, `atraso_7`, `atraso_10`, `atraso_12` ou `atraso_15`.
- Cliente inativo tambem pode receber; a rotina roda em fins de semana e feriados.
- Exigir `valor_planejado > 0`. Qualquer `valor_efetivado > 0` na referencia impede nova cobranca, inclusive pagamento parcial.
- Dia de pagamento 30/31 em mes menor cai no ultimo dia.
- `boleto_7_dias`: vencimento em sete dias; gera Lytex se necessario, grava `numero_boleto = Lytex` e cria envio `pendente_whatsapp` em execucao real.
- `lembrete_vencimento`: vencimento no dia e somente mensagem.
- Atrasos: nao geram boleto, exigem invoice existente e `numero_boleto = Lytex`; incluem PDFs vencidos de referencias anteriores do cliente quando existirem.
- Fluxo boleto: mensagem, PDF, linha digitavel, instrucao PIX, botao PIX e finalizacao.
- Fluxo atraso: mensagem principal e PDF(s), sem linha digitavel, PIX ou finalizacao.
- Os comandos `cobrancas:processar` e `cobrancas:enviar-whatsapp` simulam por padrao. Envio real exige `--executar`.
- Os agendamentos de cobranca em producao devem permanecer inativos ate autorizacao explicita.

## Financeiro

- Tela: `App\Filament\Pages\Financeiro` e `resources/views/filament/pages/financeiro.blade.php`.
- Leitura: `Financeiro_Leitura`; alteracoes: `Financeiro_Escrita`.
- Busca principal usa `conectta.busca_cadastros` e sincroniza Financeiro, Clientes e Cadastro de Rastreadores.
- Na tela Clientes, a busca consulta nome, CPF/CNPJ, telefone principal e telefone secundario; telefones usam diretamente o texto pesquisado, sem normalizacao adicional.
- Status do cliente usa `conectta.status_cliente` e sincroniza somente Financeiro e Clientes.
- Ordenacao do bloco de clientes: `qtd`, `vendedor`, `cliente` e `vencimento`; nao ordenar pelos meses.
- Valores mensais `0,00` aparecem em branco.
- `Historico` fica dentro do Financeiro e nao no menu. Export CSV respeita filtros e os dois meses exibidos.
- Modal de lancamento salva com Enter, preserva/preenche data e mostra loading ao gerar boleto.
- Boleto Lytex grava `numero_boleto = Lytex`; vencimentos 30/31 respeitam o ultimo dia do mes.
- Historico financeiro usa `audit_logs` e mostra somente alteracoes de `valor_efetivado` ou `data_lancamento` nas acoes financeiras suportadas.
- Logs antigos podem nao possuir `Total Antes` e `Total Depois` no contexto.

## Incidentes Financeiros

1. Comecar somente com diagnostico e avisar antes de alterar.
2. Procurar todos os casos afetados, nao apenas os exemplos informados.
3. Correlacionar clientes, lancamentos, invoices, envios, webhooks, auditorias e Lytex.
4. Conter a causa no codigo com idempotencia, protecao contra duplicidade e testes.
5. Confirmar os alvos e gerar backup antes do saneamento quando o procedimento autorizado permitir.
6. Classificar em nao pago/cancelavel, pago unico e pagamentos multiplos/ambiguos. Casos ambiguos dependem da central.
7. Preferir saneamento reversivel e preservar evidencias.
8. Invalidar lancamentos duplicados por `invalidado_em` e `motivo_invalidacao`; consultas operacionais usam `Lancamento::validos()`.
9. Cancelar na Lytex somente duplicata confirmada como nao paga e reverificar o status imediatamente antes.
10. Auditar comunicacoes e correcoes ao cliente.
11. Validar commit, migrations, quantidades, consultas operacionais e HTTP depois da mudanca.
12. Nunca preencher `valor_efetivado`, `data_lancamento` ou `is_baixado` apenas pelo status Lytex; a baixa depende de autorizacao da central.
13. Reversoes usam snapshots de `audit_logs`, preservam alteracoes posteriores e geram nova auditoria.

Principio: conter, diagnosticar, preservar evidencias, corrigir de forma reversivel, comunicar, auditar e validar.

## Rastreadores E Estoque

- Nas importacoes de relatorios da Tracksolid, ignorar registros cujo modelo seja `TAG`; eles nao devem ser gravados em `tracksolid_dispositivos_importados` nem participar dos relatorios de conciliacao.
- `Cadastro > Rastreadores` lista `veiculos`; `Estoque > Rastreadores` lista a tabela `rastreadores`.
- Chips pertencem ao rastreador por `rastreadores.chip_id`; nao usar o legado `veiculos.chip_id` em novas regras.
- Um rastreador pode estar em no maximo um veiculo ativo. Um chip pode estar em no maximo um rastreador.
- Clientes com frota podem ter varios rastreadores; nao bloquear por cliente.
- A exclusao de veiculo e logica (`veiculos.data_exclusao`), libera placa e preserva historicos. Cancela transacionalmente OS ativas; OS finalizadas/canceladas nao mudam.
- Ao excluir ou cancelar veiculo, liberar rastreador/chip somente se nenhum outro veiculo ativo usar o rastreador.
- Ao cancelar diretamente, rastreador e chip ficam `Disponivel` com o tecnico da remocao; rastreador recebe `is_estoque = true`.
- Equipamento instalado fica `Ativo`, sem tecnico; rastreador fica fora do estoque. Preservar o tecnico de instalacao no veiculo antes de limpar a posse.
- Novos chips e rastreadores entram sempre `Disponivel`, ignorando status manual na criacao.
- Temporariamente, `Estoque_Escrita` pode alterar status de equipamentos existentes nas duas telas de estoque.
- Ao editar rastreador no estoque, preservar `is_estoque`; mudar status somente quando escolhido pelo usuario.
- A protecao central de status esta em `App\Services\Estoque\EquipamentoStatusWorkflow` e nos eventos de `Chip` e `Rastreador`. Fluxos autorizados devem usar `EquipamentoStatusWorkflow::executar()`.
- `Adicionar chip` exige rastreador e chip `Disponivel`, sem vinculos/reservas conflitantes; `is_estoque` nao participa dessa validacao.
- Em `Estoque > Chips`, `numero_chip` e telefone; `iccid` e unico quando preenchido e tem exatamente 20 digitos no formulario.
- Em `Cadastro > Rastreadores`, o chip e somente leitura e vem do IMEI selecionado; mostrar aviso quando o rastreador nao possuir chip.
- Busca por IMEI e CPF/CNPJ somente entra quando a parte numerica tiver ao menos seis digitos, evitando conflito com placas Mercosul.
- Busca compartilhada: Financeiro, Clientes e Rastreadores. Status compartilhado: somente Financeiro e Clientes.
- Listas de Clientes e Rastreadores nao abrem pela linha inteira. Leitura usa `Ver`; escrita usa `Editar`. Preservar selecao de texto via `ct-selectable-table`.
- Testes centrais: `VeiculoExclusaoTest`, `EquipamentoStatusWorkflowTest`, testes de estoque, cadastro de rastreadores e OS.
- Anomalias globais ou dados legados nao devem ser corrigidos em massa sem autorizacao especifica e revisao por categoria.

## Menus E Permissoes

- Ordem principal: `Cadastro`, `Financeiro`, `Estoque`, `Rotinas`, `Administrativo`.
- Cadastro: Clientes, Rastreadores, Contratos e Vendedores.
- Financeiro: Financeiro, Relatorio Geral, Boletos e Faturamento.
- Estoque: Rastreadores, Chips e Tecnicos.
- Rotinas: Cobrancas automaticas e Mensagens de cobranca.
- Administrativo: Integracoes, Usuarios, Auditoria e Restore Backup.
- Vendedores: `Cadastro_Leitura`, `Cadastro_Escrita` e `Cadastro_Exclusao`.
- Integracoes e Restore: permissao `Tecnico`.
- Usuarios e Auditoria: permissao `Coordenador`.
- Coordenador pode manter Vendedores e Tecnicos, mas nao pode criar/promover admin nem editar/excluir usuarios admin.
- Admin tem acesso total por `User::hasPermission()`.
- `canAccessPanel()` libera autenticados; autorizacao real fica em resources, pages e actions.
- Catalogo central: `App\Models\Permission::catalogo()`.

## Ordens De Servico

- Fonte principal: [`app/fluxo_de_OS.md`](app/fluxo_de_OS.md). Nao duplicar aqui detalhes que possam ser consultados nesse documento.
- Menu `Ordens de Servico`: Ordens de servico, Disponibilidades e Agenda de OS.
- Permissoes: `OS_Leitura` e `OS_Escrita`.
- Uma OS ativa por veiculo; OS nao e excluida definitivamente. Tipo, cliente e veiculo ficam bloqueados apos criacao.
- Disponibilidades usam blocos de uma hora; atribuicao ocorre exclusivamente pelo calendario e gera link publico protegido por token.
- Tecnico `Outros` exige `nome_tecnico_externo`; o vinculo operacional continua no cadastro `Outros`.
- Campos de workflow nao podem ser sobrescritos pelo salvamento comum do formulario.
- Instalacao: equipamentos novos ficam `Ativo`, sem tecnico; rastreador fora do estoque; tecnico registrado no veiculo.
- Retirada: rastreador e chip ficam `Disponivel` com o tecnico; rastreador em estoque.
- Manutencao sem troca nao movimenta equipamentos.
- Troca de chip: novo fica ativo; retirado fica disponivel com o tecnico.
- Troca de rastreador/chip: novos ficam ativos; retirados ficam disponiveis com o tecnico.
- Troca somente de rastreador reaproveita o chip atual no rastreador novo.
- Historico preserva eventos, fotos e mensagens.
- `Enviada` significa aceite da chamada pela Z-API, nao entrega/leitura.
- Nao implementar retentativa automatica de mensagens com erro sem decisao explicita.
- Fotos usam armazenamento privado e rota protegida. Producao usa `ORDENS_SERVICO_FOTOS_DRIVER=rclone` e `/etc/conectta/rclone.conf`; desenvolvimento usa `local`.
- Arquivamento diario move fotos elegiveis para `gdrive:Conectta/ordens-servico`.
- Testes principais: `tests/Feature/OrdemServicoFlowTest.php` e `tests/Unit/OrdemServicoFotoStorageTest.php`.

## Identidade Visual

- Favicon do Filament: `public/favicon.svg`, marcador de mapa cinza.
