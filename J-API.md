# J-API — Integração local de WhatsApp para o Conectta

## Objetivo

O J-API é o serviço próprio de envio por WhatsApp disponível para o Conectta. Ele usa Baileys/WhatsApp Web, roda como processo separado e expõe uma API HTTP somente em localhost.

Este documento é o ponto de entrada para futuras tarefas no Conectta. Antes de diagnosticar, integrar ou publicar alterações relacionadas ao J-API, leia também:

- `/home/diel_/J-API/AGENTS.md`
- `/home/diel_/J-API/OPERATIONS.local.md` — privado e ignorado pelo Git
- `/home/diel_/J-API/README.md`

Não copie credenciais, sessões, QR Codes, telefones, links privados de boletos ou dados de `OPERATIONS.local.md` para commits, issues ou logs públicos.

## Localização e produção

- Repositório local do serviço: `/home/diel_/J-API`
- Instalação própria na VPS: `/opt/whatsapp-service`
- Serviço systemd: `whatsapp-service.service`
- Endpoint usado pelo Conectta na VPS: `http://127.0.0.1:3001`
- Sessão existente: `default`
- Branch de produção do J-API: `main`
- Commit implantado em 2026-08-10: `4f1aebd`

O serviço deve permanecer exclusivamente em `127.0.0.1`. Nunca alterar o bind para `0.0.0.0`, criar proxy público ou expor a porta 3001.

## Estado atual no Conectta

O Conectta possui drivers configuráveis para J-API e Z-API. A seleção fica na tela `Administrativo > Integrações`, preservando rollback enquanto a migração é validada. Cobranças e notificações de ordens de serviço usam a interface comum de WhatsApp.

Não remova a Z-API sem pedido explícito e validação operacional do J-API em produção.

## Endpoints do J-API

```text
GET  http://127.0.0.1:3001/status
GET  http://127.0.0.1:3001/qr
GET  http://127.0.0.1:3001/sessions
POST http://127.0.0.1:3001/send-text
POST http://127.0.0.1:3001/send-pix
POST http://127.0.0.1:3001/send-file
```

Para sessões nomeadas, prefixe com `/sessions/{sessao}`. Exemplo: `/sessions/financeiro/send-text`.

### Texto

```json
{
  "phone": "5562999999999",
  "message": "Mensagem"
}
```

### PIX nativo

```json
{
  "phone": "5562999999999",
  "message": "Pague usando o PIX:",
  "pix": "chave ou código PIX",
  "merchantName": "Nome do recebedor",
  "keyType": "EVP"
}
```

- `merchantName` é opcional; padrão `Pix`.
- `keyType` é opcional; padrão `EVP`.
- Tipos aceitos: `EVP`, `EMAIL`, `PHONE` e `CPF`.
- O cartão usa o fluxo nativo `payment_info` e mostra logomarca e botão **Copiar chave Pix** no celular.
- Limitação confirmada: pode não renderizar no WhatsApp Web. A Z-API possui uma implementação proprietária/mais específica que não existe no protobuf do Baileys oficial usado pelo J-API.

### PDF existente na VPS

```json
{
  "phone": "5562999999999",
  "path": "/var/www/conectta/repo/app/storage/app/private/whatsapp/documento.pdf",
  "filename": "documento.pdf",
  "caption": "Segue seu documento."
}
```

O caminho precisa estar dentro de `ALLOWED_FILE_PATHS`. Esse fluxo não apaga o arquivo original; a aplicação que o criou continua responsável por sua retenção.

### Boleto/PDF diretamente por URL

Este é o fluxo recomendado para boletos da Lytex:

```json
{
  "phone": "5562999999999",
  "url": "https://public-api-pay.lytex.com.br/v1/invoices/print/{hashId}",
  "filename": "boleto.pdf",
  "caption": "Segue seu boleto."
}
```

- `POST /send-file` aceita exatamente um entre `path` e `url`.
- URLs precisam usar HTTPS e pertencer a `ALLOWED_DOWNLOAD_HOSTS`.
- Produção autoriza `public-api-pay.lytex.com.br`.
- O PDF é baixado dentro da fila somente para memória RAM, limitado por `MAX_PDF_SIZE_MB`, validado pela assinatura real e enviado como documento nativo.
- Nenhum PDF temporário ou permanente é criado na VPS nesse fluxo; a memória é liberada pelo Node.js após a requisição.
- Não é necessária rotina de limpeza de boletos enviados por URL.

Para sandbox Lytex, será necessário autorizar explicitamente `sandbox-public-api-pay.lytex.com.br` na configuração do J-API antes de usar URLs desse host.

## Respostas e erros

Sucesso:

```json
{
  "success": true,
  "session": "default",
  "messageId": "..."
}
```

Erros retornam JSON com `success: false` e `error`. Casos comuns:

- `422`: payload, URL, arquivo ou tipo PIX inválido
- `404`: sessão inexistente
- `409`: limite de sessões
- `503`: WhatsApp desconectado ou serviço encerrando

A requisição aguarda o envio terminar na fila. O cliente HTTP do Conectta deve usar timeout suficiente; o exemplo PHP do J-API usa 60 segundos.

## Segurança da integração

- O Conectta e o J-API rodam na mesma VPS e devem conversar apenas por `127.0.0.1`.
- Não enviar ao J-API URLs arbitrárias fornecidas por usuários. Use somente URLs construídas a partir dos dados confiáveis da integração Lytex.
- Não registrar o conteúdo completo de `pix`, URLs autenticadas de boletos ou payloads sensíveis em logs/auditoria.
- Não apagar sessões por falhas temporárias.
- Não versionar `.env`, `data/sessions/`, QR Codes ou credenciais.
- Antes de publicar o J-API, executar `npm run check`, `npm audit --omit=dev` e conferir `git status --short --ignored`.

## Validação já realizada

Em 2026-08-10:

- Texto enviado com sucesso.
- Cartão PIX nativo testado: bonito e funcional no celular; limitação no Web confirmada.
- PDF por caminho enviado com sucesso.
- PDF por URL da Lytex enviado como documento nativo, sem criar arquivo em disco.
- Resolução de números brasileiros com e sem nono dígito validada em envio real.
- Solução alternativa de imagem com link foi testada, rejeitada e removida.
- J-API validado com 15 testes, PHP sem erro de sintaxe e `npm audit --omit=dev` com 0 vulnerabilidades.
- Produção atualizada para `4f1aebd`; serviço ativo, habilitado, sessão `default` pronta e bind somente em localhost.

## Operação no Conectta

Antes de selecionar o J-API como driver principal, confirme que o serviço está `ready`, faça um envio controlado e mantenha a configuração Z-API disponível para rollback até nova decisão explícita.
