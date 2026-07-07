# Conectta - Contexto Para Codex

## Projeto

- Aplicacao Laravel/Filament do sistema Conectta.
- Raiz do repositório: `/home/diel_/Conectta`.
- Aplicacao principal: `/home/diel_/Conectta/app`.
- Trabalhe normalmente dentro de `/home/diel_/Conectta/app`.
- Stack local: Laravel 13, Filament, MySQL, Vite/NPM.
- Idioma da interface: portugues do Brasil.

## Cuidados Gerais

- Nao commitar `.env`, tokens, senhas, payloads de API ou arquivos de backup.
- A pasta `payloads-whatsapp/` deve ficar ignorada no git porque contem traces e tokens.
- Antes de alterar algo, preferir ler o padrao existente do projeto.
- Usar `rg` para buscas.
- Usar `apply_patch` para edicoes manuais.
- Nao reverter mudancas do usuario sem pedido explicito.
- Evitar comandos destrutivos como `git reset --hard` ou `git checkout --` sem autorizacao clara.

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

Seeders usados com frequencia:

```bash
php artisan db:seed --class=PaisSeeder
php artisan db:seed --class=CobrancaMensagemModeloSeeder
```

## Git E Deploy

- Branch principal: `main`.
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

### Lytex

- Configuracoes ficam na tela `Integracoes`.
- Existe suporte a producao e homologacao.
- Ambiente local pode estar apontado para homologacao.
- Servico principal: `App\Services\Lytex\LytexInvoiceService`.
- Helper de campos da invoice: `App\Services\Lytex\LytexInvoiceData`.
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

## Rotinas De Cobranca

Menu principal: `Rotinas`.

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

## Rastreadores E Estoque

- Existem duas telas com nomes parecidos:
  - `Cadastro > Rastreadores`: lista `veiculos` com rastreador vinculado/instalado.
  - `Estoque > Rastreadores`: lista a tabela real `rastreadores`.
- A combo `IMEI` em `Cadastro > Rastreadores` mostra apenas rastreadores com status `Disponivel`, alem do rastreador ja vinculado quando estiver editando um registro existente.
- Ao criar rastreador em `Estoque > Rastreadores`, o status padrao deve ser `Disponivel`, para evitar criar estoque novo como `Ativo`.
- A busca em `Cadastro > Rastreadores` pesquisa placa, veiculo e cliente. A busca por IMEI so deve entrar quando a parte numerica tiver pelo menos 6 digitos, para uma placa como `QDW-9C47` nao buscar IMEIs contendo `947`.
- Nas listas `Clientes` e `Cadastro > Rastreadores`, a linha inteira nao deve abrir o detalhe. A navegacao deve ficar nos icones/botoes de acao, especialmente `Editar`.
- Usuarios com `Cadastro_Leitura` podem abrir `Clientes` e `Cadastro > Rastreadores` pelo botao `Ver`, que reaproveita a tela de edicao com formulario desabilitado e sem botao de salvar; `Cadastro_Escrita` ve a mesma acao como `Editar`.
- Essas duas listas usam a classe `ct-selectable-table` e o CSS `public/css/conectta-admin.css` para permitir selecionar/copiar texto das celulas.

## Menus E Permissoes

- Grupo `Cadastro`: Clientes, Rastreadores, Contratos e Vendedores.
- `Vendedores` usa permissoes de cadastro:
  - leitura: `Cadastro_Leitura`;
  - criar/editar: `Cadastro_Escrita`;
  - excluir: `Cadastro_Exclusao`.
- As telas administrativas `Integracoes` e `Restore Backup` ficam visiveis apenas para usuarios com a permissao `Tecnico`; admin continua acessando porque `User::hasPermission()` libera tudo para `is_admin`.
- As telas administrativas `Usuarios` e `Auditoria` ficam visiveis para usuarios com a permissao `Coordenador`; em `Usuarios`, essa permissao permite criar, editar e excluir usuarios.
- Usuarios com permissao `Coordenador` nao podem criar/promover administradores, editar/excluir usuarios admin nem alterar permissoes de usuarios admin; somente admin pode mexer em admin.
- Usuarios com permissao `Coordenador` tambem podem manter `Vendedores` e `Tecnicos`, mesmo sem as permissoes de `Cadastro` ou `Estoque`.
- Usuarios admin continuam com acesso total porque `User::hasPermission()` libera tudo para `is_admin`.

## Cliente De Teste Usado

- Cliente: `Diel Oliveira de Faria`.
- ID: `1470`.
- Pode ser usado para testes controlados quando o usuario autorizar.
- Ja foram criados lancamentos de homologacao locais para testes; se poluirem a tela, podem ser removidos com cuidado.

## Estado Atual Importante

- Ultimo commit conhecido no app em producao/desenvolvimento: `6074e00 Melhora na navegação de Rastreadores e Clientes`.
- Em 2026-07-07, `/home/diel_/Conectta/app` estava limpo e sincronizado com `origin/main`; o unico arquivo pendente era este `AGENTS.md`.
- As rotinas de cobranca e WhatsApp foram implementadas localmente.
- Foi validado envio real de WhatsApp para o cliente de teste, envio `11`, com sucesso.
- Foi validada geracao de boleto em homologacao Lytex.
- Foi validada recuperacao de linha digitavel/PIX via `transactions` do `detalhesFatura`.
- A producao ja esta em uso em `https://sistemaconectta.com.br`.
- As rotinas de cobranca existem e o scheduler esta instalado na VPS, mas os agendamentos devem permanecer inativos ate autorizacao explicita.
- Antes de ativar cobrancas em producao: configurar Z-API em `Integracoes`, rodar simulacoes/controladas e ativar os agendamentos desejados.
