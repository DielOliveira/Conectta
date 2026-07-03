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
  - se faltar linha digitavel/PIX, tenta buscar por `detalhesFatura`.

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

Fluxo WhatsApp com boleto:

1. mensagem principal;
2. PDF do boleto;
3. linha digitavel;
4. mensagem de instrucao PIX;
5. botao PIX;
6. finalizacao.

Fluxo WhatsApp de lembrete de vencimento:

1. mensagem principal apenas.

## Cliente De Teste Usado

- Cliente: `Diel Oliveira de Faria`.
- ID: `1470`.
- Pode ser usado para testes controlados quando o usuario autorizar.
- Ja foram criados lancamentos de homologacao locais para testes; se poluirem a tela, podem ser removidos com cuidado.

## Estado Atual Importante

- As rotinas de cobranca e WhatsApp foram implementadas localmente.
- Foi validado envio real de WhatsApp para o cliente de teste, envio `11`, com sucesso.
- Foi validada geracao de boleto em homologacao Lytex.
- Foi validada recuperacao de linha digitavel/PIX via `transactions` do `detalhesFatura`.
- Antes de ir para producao: revisar, commitar, subir para GitHub, rodar deploy e configurar envs da Z-API na VPS.
