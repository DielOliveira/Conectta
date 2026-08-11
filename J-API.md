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
- Commit implantado em 2026-08-11: `843f4fa`

O serviço deve permanecer exclusivamente em `127.0.0.1`. Nunca alterar o bind para `0.0.0.0`, criar proxy público ou expor a porta 3001.

## Estado atual no Conectta

O Conectta possui drivers configuráveis para J-API e Z-API. A seleção fica na tela `Administrativo > Integrações`, preservando rollback enquanto a migração é validada. Cobranças e notificações de ordens de serviço usam a interface comum de WhatsApp.

Não remova a Z-API sem pedido explícito e validação operacional do J-API em produção.

## Endpoints do J-API

```text
GET  http://127.0.0.1:3001/status
GET  http://127.0.0.1:3001/qr
GET  http://127.0.0.1:3001/sessions
GET  http://127.0.0.1:3001/queue
GET  http://127.0.0.1:3001/queue/{jobId}
GET  http://127.0.0.1:3001/admin/queue
POST http://127.0.0.1:3001/send-text
POST http://127.0.0.1:3001/send-pix
POST http://127.0.0.1:3001/send-file
```

Para sessões nomeadas, prefixe com `/sessions/{sessao}`. Exemplo: `/sessions/financeiro/send-text`.

Consultas de fila para sessões nomeadas:

```text
GET /sessions/{sessao}/queue?limit=100
GET /sessions/{sessao}/queue/{jobId}
```

O painel é apenas operacional e local. Para vê-lo de fora da VPS, usar túnel SSH; nunca publicar a porta 3001 ou criar proxy público.

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
- O PDF é baixado e validado antes do aceite do job.
- Uma cópia privada é persistida em `data/queue-files` para o envio sobreviver a reinícios.
- O J-API remove essa cópia após o envio bem-sucedido; o Conectta não deve manipular essa área.

Para sandbox Lytex, será necessário autorizar explicitamente `sandbox-public-api-pay.lytex.com.br` na configuração do J-API antes de usar URLs desse host.

## Respostas e erros

Novo job aceito, HTTP `202 Accepted`:

```json
{
  "success": true,
  "session": "default",
  "queued": true,
  "duplicate": false,
  "jobId": "...",
  "status": "pending"
}
```

O aceite não significa que o WhatsApp recebeu a mensagem. O identificador real do WhatsApp aparece posteriormente como `whatsappMessageId` no job com status `sent`.

Estados da fila:

- `pending`: aguardando conexão, intervalo ou cota;
- `processing`: tentativa em andamento;
- `sent`: envio concluído e `whatsappMessageId` disponível;
- `failed`: erro definitivo ou tentativas esgotadas.

Consultar um job:

```json
{
  "session": "default",
  "job": {
    "id": "...",
    "type": "pdf",
    "phone": "5562999999999",
    "status": "sent",
    "attempts": 1,
    "createdAt": 1786450000000,
    "availableAt": 1786450000000,
    "sentAt": 1786450010000,
    "whatsappMessageId": "...",
    "lastError": null
  }
}
```

## Idempotência

Enviar uma chave estável e única em cada etapa lógica:

```http
Idempotency-Key: cobranca-envio-217-pdf-42
```

- A chave é isolada por sessão e aceita de 1 a 200 caracteres ASCII visíveis.
- Repetir a mesma chave devolve HTTP `200`, `duplicate: true` e o mesmo `jobId` sem enfileirar novamente.
- Não usar hash baseado somente em conteúdo: cobranças legítimas diferentes podem ter payload idêntico.
- Preferir identificadores persistentes do Conectta, incluindo o envio e a etapa (`texto`, `pix`, `pdf-{invoiceId}`).
- O Conectta deve persistir o `jobId` retornado para correlacionar o resultado futuro.

Erros retornam JSON com `success: false` e `error`. Casos comuns:

- `422`: payload, URL, arquivo ou tipo PIX inválido
- `404`: sessão inexistente
- `409`: limite de sessões ou fila cheia
- `503`: fila/serviço encerrando

A requisição não aguarda o WhatsApp. Ela termina depois que o job — e, para PDF, sua cópia privada — foi persistido. Não aumentar timeouts para esperar entrega.

## Trabalho futuro no Conectta

Quando o usuário pedir a atualização do consumidor, primeiro inspecionar a implementação atual; não assumir nomes de métodos ou colunas. Preservar o driver Z-API para rollback. Adaptar o J-API seguindo esta ordem:

1. Tratar qualquer resposta HTTP `2xx` como transporte aceito, distinguindo `202` novo de `200` idempotente.
2. Exigir `jobId` nas respostas do driver J-API; não exigir `messageId` no aceite.
3. Enviar `Idempotency-Key` estável em texto, PIX e cada PDF.
4. Persistir `jobId`, status de fila e, quando disponível, `whatsappMessageId` sem sobrecarregar campos com semânticas diferentes.
5. Não marcar cobrança como efetivamente enviada somente porque recebeu HTTP `202`; usar um estado intermediário como `queued`/`enfileirado`.
6. Implementar reconciliação consultando `GET /queue/{jobId}` até `sent` ou `failed`, de preferência por comando agendado e em lotes, sem polling por requisição web.
7. Mapear `sent` para sucesso final. Mapear `failed` para falha visível e permitir reprocessamento deliberado com nova chave somente quando apropriado.
8. Considerar `pending` normal quando limites, desconexão ou janela diária pausarem a fila; não reenviar o mesmo payload.
9. Manter compatibilidade do contrato comum de WhatsApp: a assincronicidade é específica do driver J-API; não alterar a semântica da Z-API inadvertidamente.
10. Atualizar testes para cobrir `202 pending`, repetição idempotente `200`, reconciliação `sent`, reconciliação `failed` e indisponibilidade temporária.

Antes de criar migration, verificar se as tabelas atuais de envios já possuem campos genéricos de identificador externo, status, payload de resposta ou metadados. Reutilizar campos somente se a semântica for realmente compatível; caso contrário, criar campos explícitos para não confundir `jobId` com ID de mensagem do WhatsApp.

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

Em 2026-08-11:

- Fila persistente SQLite implantada no commit `843f4fa`.
- Endpoints de envio passaram a aceitar jobs de forma assíncrona com HTTP 202 e `jobId`.
- Intervalos e cotas por sessão passaram a ser configuráveis; jobs aguardam na fila em vez de exigir reagendamento pelo Conectta.
- Retentativas, recuperação após reinício e idempotência foram adicionadas.
- PDFs passaram a ter cópia privada persistente enquanto aguardam envio.
- Painel local `/admin/queue` e APIs de consulta foram disponibilizados.
- Produção validada com serviço ativo, sessão `ready`, bind somente em localhost e painel/API da fila respondendo HTTP 200.

## Operação no Conectta

Antes de selecionar o J-API como driver principal, confirme que o serviço está `ready`, faça um envio controlado e mantenha a configuração Z-API disponível para rollback até nova decisão explícita.
