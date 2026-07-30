# Integração Tracksolidpro com o Conectta

## Objetivo

Este documento consolida a análise do manual **Tracksolidpro API v2.7.9**, armazenado em:

```text
docs/Tracksolidpro api v2.7.9.pdf
```

O objetivo é evitar a releitura integral das 109 páginas do PDF e servir como referência para planejar e implementar a integração entre a Tracksolidpro e o sistema Conectta.

O manual analisado é a especificação **JIMI Open API v2.7.9**, publicada em **25/06/2024**.

## Conclusão geral

A API permite trazer para o Conectta grande parte da operação atualmente realizada na Tracksolid:

- conferência e sincronização de rastreadores;
- posição atual e estado de comunicação;
- histórico de trajetos;
- viagens, quilometragem, paradas e marcha lenta;
- alarmes e notificações;
- cercas eletrônicas;
- contas e acessos de clientes;
- comandos remotos;
- fotos, vídeos, transmissão ao vivo e RFID, para equipamentos compatíveis.

O vínculo natural entre os dois sistemas é o **IMEI**. O Conectta já relaciona IMEI, rastreador, chip, veículo, cliente, técnico e status, portanto existe uma boa base para integração.

A primeira versão recomendada é somente de leitura: conferir inventário, consultar situação atual, posição, alarmes e trajetos. Alterações de contas, cercas e comandos remotos devem ficar para etapas posteriores.

## Requisitos de acesso

Para utilizar a API são necessários:

- conta Tracksolid com Open API habilitada;
- `appKey` e `appSecret`, fornecidos pela Tracksolid/JIMI;
- usuário e senha da conta Tracksolid;
- identificação do nó onde a conta está hospedada;
- confirmação dos métodos liberados comercialmente para a conta.

O manual apresenta estes endpoints:

| Nó | Endpoint |
|---|---|
| Tracksolid antigo/TS | `http://open.10000track.com/route/rest` |
| Tracksolidpro Hong Kong | `https://hk-open.tracksolidpro.com/route/rest` |
| Tracksolidpro Europa | `https://eu-open.tracksolidpro.com/route/rest` |
| Tracksolidpro Estados Unidos | `https://us-open.tracksolidpro.com/route/rest` |

Todos os métodos utilizam o mesmo endpoint. A operação é identificada pelo parâmetro `method`.

O manual determina que o navegador ou aplicativo do usuário não deve acessar diretamente a Tracksolid. As chamadas devem partir do servidor do Conectta.

## Autenticação e assinatura

### Token

O token é obtido por:

```text
jimi.oauth.token.get
```

Parâmetros específicos:

- `user_id`: conta Tracksolid;
- `user_pwd_md5`: MD5 minúsculo da senha da conta;
- `expires_in`: validade entre 60 e 7.200 segundos.

O retorno contém:

- `accessToken`;
- `refreshToken`;
- `expiresIn`;
- `account`;
- `appKey`;
- horário de geração.

O token deve ser armazenado e reutilizado. O manual alerta para não solicitar um token em cada chamada, pois há controle de frequência.

A renovação é feita por:

```text
jimi.oauth.token.refresh
```

### Parâmetros comuns

As chamadas incluem:

- `method`;
- `timestamp`;
- `app_key`;
- `sign`;
- `sign_method`;
- `v`;
- `format`.

O horário padrão é UTC, no formato:

```text
yyyy-MM-dd HH:mm:ss
```

A diferença admitida pelo servidor é de aproximadamente dez minutos.

### Assinatura

A assinatura documentada usa MD5:

1. Ordenar alfabeticamente os parâmetros pelo nome.
2. Não incluir `sign` nem parâmetros do tipo byte stream.
3. Concatenar nome e valor, sem `=` e sem separadores.
4. Colocar o `appSecret` antes e depois da string.
5. Calcular MD5 em UTF-8.
6. Enviar o resultado hexadecimal em letras maiúsculas.

O manual menciona `v=0.9` sem verificação de assinatura e `v=1.0` com verificação. A integração deve usar **v1.0**.

## Catálogo de serviços

O manual documenta 52 operações.

### Tokens

| Método | Finalidade |
|---|---|
| `jimi.oauth.token.get` | Obter access token |
| `jimi.oauth.token.refresh` | Renovar access token |

### Contas e usuários

| Método | Finalidade |
|---|---|
| `jimi.user.child.list` | Listar subcontas |
| `jimi.user.child.create` | Criar subconta |
| `jimi.user.child.del` | Excluir subconta |
| `jimi.user.child.move` | Mover subconta |
| `jimi.user.child.update` | Editar dados e permissões do usuário |
| `jimi.user.device.expiration.update` | Alterar expiração dos equipamentos do usuário |
| `jimi.open.device.bind` | Vincular equipamento a usuário do aplicativo |
| `jimi.open.device.unbind` | Desvincular equipamento do usuário do aplicativo |

A criação de subconta permite configurar permissões para:

- login web;
- login no aplicativo;
- envio de comandos;
- configuração de modo de trabalho;
- edição pela web;
- edição pelo aplicativo.

### Equipamentos e inventário

| Método | Finalidade |
|---|---|
| `jimi.user.device.list` | Listar equipamentos de uma conta |
| `jimi.track.device.detail` | Consultar detalhes de um IMEI |
| `jimi.open.device.update` | Atualizar veículo/equipamento pelo IMEI |
| `jimi.open.device.move` | Mover equipamentos entre subcontas |

Os detalhes disponíveis podem incluir:

- IMEI;
- nome e modelo do rastreador;
- tipo de utilização;
- SIM;
- ICCID;
- IMSI;
- data de ativação;
- importação e expiração;
- situação habilitado/desabilitado;
- placa;
- nome, modelo e marca do veículo;
- VIN e número do motor;
- motorista e telefone;
- consumo médio;
- quilometragem atual.

### Localização

| Método | Finalidade |
|---|---|
| `jimi.user.device.location.list` | Última localização de todos os equipamentos da conta |
| `jimi.device.location.get` | Última localização de um ou vários IMEIs |
| `jimi.device.location.URL.share` | Gerar link de compartilhamento da posição |
| `jimi.lbs.address.get` | Resolver localização por Wi-Fi ou estação celular |

A consulta de posição por IMEI aceita no máximo **100 IMEIs por chamada**.

Os dados de posição podem incluir:

- latitude e longitude;
- data da posição GPS;
- último heartbeat;
- online ou offline;
- ativo ou não ativado;
- expirado ou válido;
- GPS, LBS, Wi-Fi ou beacon;
- descrição da localização;
- velocidade;
- ignição/ACC;
- direção;
- quantidade de satélites;
- sinal GSM;
- bateria interna;
- alimentação externa;
- temperatura;
- combustível;
- quilometragem atual.

#### Inconsistência do manual

Em algumas tabelas o texto descreve `lat` como longitude e `lng` como latitude. Os exemplos usam o padrão convencional:

- `lat`: latitude;
- `lng`: longitude.

Isso deve ser validado com um IMEI real antes de armazenar ou exibir coordenadas.

A API de localização por Wi-Fi/LBS tem cota documentada de **dez consultas por dia por equipamento**, considerando também as subcontas.

### Trajetos, viagens e quilometragem

| Método | Finalidade |
|---|---|
| `jimi.device.track.list` | Consultar pontos do trajeto |
| `jimi.device.track.mileage` | Consultar viagens e quilometragem |
| `jimi.open.platform.report.trips` | Relatório detalhado ou diário de viagens |
| `jimi.open.platform.report.parking` | Relatório de estacionamento ou marcha lenta |
| `jimi.open.platform.fence.duration` | Entrada, saída e permanência em cercas |

O trajeto retorna:

- latitude e longitude;
- horário GPS;
- direção;
- velocidade;
- GPS, LBS ou Wi-Fi;
- intensidade/quantidade de satélites;
- ignição;
- ACC;
- quilometragem do período.

Limitações documentadas:

- cada consulta de trajeto cobre no máximo **sete dias**;
- só podem ser consultados trajetos dos **últimos três meses**;
- a consulta de quilometragem aceita um ou vários IMEIs;
- relatórios maiores utilizam paginação.

O relatório de viagens pode trazer:

- início e fim da viagem;
- posições inicial e final;
- quilometragem;
- tempo de deslocamento;
- velocidade média e máxima;
- consumo estimado;
- odômetro inicial e final;
- totais por dia.

O relatório de estacionamento/marcha lenta traz:

- início e fim;
- duração;
- posição e endereço;
- modelo;
- situação do ACC.

### Alarmes e notificações

| Método/evento | Finalidade |
|---|---|
| `jimi.push.device.alarm` | Push de alarme para callback do Conectta |
| `jimi.device.alarm.list` | Consultar histórico de alarmes |

Para receber push, o Conectta deve fornecer uma URL à Tracksolid, que é cadastrada manualmente por eles.

O evento contém:

- IMEI;
- nome do equipamento;
- tipo e nome do alarme;
- latitude e longitude;
- horário.

A busca de alarmes:

- aceita intervalo máximo de um mês;
- retorna no máximo mil registros;
- sem intervalo informado, retorna os cinquenta alertas mais recentes do último mês.

Alarmes relevantes descritos no apêndice:

- SOS;
- corte ou falta de alimentação;
- bateria interna ou externa baixa;
- remoção/desconexão do rastreador;
- violação e desmontagem;
- ignição ligada/desligada;
- entrada e saída de cerca;
- excesso de velocidade;
- deslocamento;
- dispositivo offline;
- colisão e capotamento;
- aceleração, frenagem e curva brusca;
- inibidor de GPS ou LTE;
- condução fatigada;
- alertas de câmera e DMS;
- temperatura e umidade;
- abertura de portas;
- falha de motor;
- combustível e possível furto;
- marcha lenta e estacionamento prolongado;
- manutenção por quilometragem.

#### Segurança do callback

O manual não descreve assinatura, segredo compartilhado ou cabeçalho de autenticação do callback de alarmes.

Antes de confiar nos eventos, é necessário confirmar com a Tracksolid:

- se há assinatura;
- se existem IPs fixos;
- se pode ser usado segredo no cabeçalho ou URL;
- como ocorre a repetição de eventos quando o callback falha.

Também será necessário deduplicar eventos recebidos.

### Cercas eletrônicas do equipamento

| Método | Finalidade |
|---|---|
| `jimi.open.device.fence.create` | Criar cerca no equipamento |
| `jimi.open.device.fence.delete` | Excluir cerca do equipamento |

Características:

- cerca circular;
- alertas de entrada, saída ou ambos;
- comunicação por GPRS ou SMS + GPRS;
- criação e exclusão dependem de o equipamento estar online;
- raio documentado de 1 a 9.999 em unidades de 100 metros;
- retorna um número de instrução usado para exclusão.

A unidade do raio deve ser confirmada em teste, pois a descrição do manual é incomum.

### Cercas eletrônicas da plataforma

| Método | Finalidade |
|---|---|
| `jimi.open.platform.fence.create` | Criar ou editar cerca |
| `jimi.open.platform.fence.delete` | Excluir cerca |
| `jimi.open.platform.fence.bind` | Vincular IMEIs e alarmes à cerca |
| `jimi.open.platform.fence.list` | Listar cercas da conta |
| `jimi.open.platform.fence.detail` | Consultar uma cerca |

Características:

- círculos ou polígonos;
- cor e descrição;
- círculos com raio entre 200 e 5.000 metros;
- associação a vários IMEIs;
- alerta de entrada;
- alerta de saída;
- alerta quando não entra por determinado número de dias;
- alerta quando não sai por determinado número de dias.

O manual menciona conversão para o sistema de coordenadas “Mars”, usado na China. A necessidade dessa conversão deve ser confirmada para o nó e os equipamentos brasileiros.

### Comandos remotos

| Método | Finalidade |
|---|---|
| `jimi.open.instruction.list` | Listar comandos suportados pelo modelo |
| `jimi.open.instruction.send` | Enviar comando estruturado |
| `jimi.open.instruction.result` | Consultar resultado do comando |
| `jimi.open.instruction.raw.send` | Enviar comando bruto hexadecimal |
| `jimi.open.instruction.raw.receive` | Receber dados brutos do rastreador |

A lista de comandos informa:

- código;
- nome;
- template;
- explicação;
- parâmetros;
- suporte ou não a comando offline.

Possíveis estados de execução:

- falhou;
- executado com sucesso;
- aguardando envio;
- cancelado.

Erros documentados incluem:

- timeout;
- parâmetro inválido;
- equipamento offline;
- comando não suportado;
- equipamento ocupado;
- erro de rede;
- resposta em formato inválido.

Comandos de relé/bloqueio aparecem nos exemplos, mas a disponibilidade real depende do modelo e firmware.

#### Requisitos de segurança no Conectta

Comandos remotos devem exigir:

- permissão específica;
- confirmação reforçada;
- identificação do usuário;
- motivo da operação;
- registro de payload e resposta em auditoria;
- consulta posterior do resultado;
- tratamento de equipamento offline;
- restrições adicionais para bloqueio do veículo.

Não devem fazer parte da primeira versão da integração.

### Câmera, vídeo e RFID

| Método | Finalidade |
|---|---|
| `jimi.device.media.URL` | Consultar fotos/vídeos produzidos por comando remoto |
| `jimi.device.meida.cmd.send` | Solicitar foto ou vídeo |
| `jimi.device.live.page.url` | Obter página de transmissão ao vivo |
| APIs 7.35 a 7.38 | Histórico de vídeo, instrução de vídeo, RTMP e URLs JIMI |
| `jimi.open.device.rfid.list` | Consultar registros RFID |

Essas funções dependem de equipamento com câmera ou leitor RFID.

Podem retornar:

- câmera frontal ou interna;
- miniatura;
- arquivo de foto/vídeo;
- MIME type;
- tamanho;
- horário da captura ou alarme;
- URL da página ao vivo;
- URL RTMP;
- cartão RFID;
- foto relacionada à leitura RFID.

Os métodos 7.33 e 7.34, específicos para scooters, estão marcados como obsoletos.

## Encaixe com o Conectta

### Dados que já existem

O Conectta já possui:

- `rastreadores.imei`;
- modelo e data de ativação;
- vínculo rastreador-chip;
- número do chip e ICCID;
- veículo, placa, tipo, cor e ano;
- cliente;
- técnico de instalação e remoção;
- data de instalação e retirada;
- status do rastreador;
- login e senha associados ao veículo;
- histórico de auditoria;
- tela administrativa de integrações.

O IMEI deve ser a chave de integração. IDs internos do Conectta e da Tracksolid não devem ser tratados como equivalentes.

### Possíveis conferências automáticas

Uma sincronização somente de leitura pode identificar:

- IMEI no Conectta e ausente na Tracksolid;
- IMEI na Tracksolid e ausente no Conectta;
- IMEI duplicado;
- divergência de modelo;
- divergência de SIM, número do chip ou ICCID;
- divergência de placa e veículo;
- equipamento expirado;
- equipamento desabilitado;
- rastreador ativo no Conectta, mas offline ou inativo na Tracksolid;
- rastreador vinculado à subconta errada;
- chip vinculado a IMEI diferente;
- data de ativação divergente.

Nenhuma divergência deve ser corrigida automaticamente na primeira versão.

### Dados novos necessários

Dependendo da arquitetura escolhida, podem ser necessárias tabelas para:

- tokens Tracksolid;
- vínculo entre cliente Conectta e subconta Tracksolid;
- última situação conhecida do rastreador;
- histórico de sincronizações;
- alarmes recebidos;
- cercas;
- comandos e seus resultados;
- eventos de webhook.

Não é recomendável gravar todos os pontos de localização continuamente sem uma necessidade definida. Para a posição atual, cache ou tabela resumida pode ser suficiente. Trajetos antigos podem ser consultados sob demanda, respeitando a janela de três meses.

### Credenciais

A credencial mestra da Open API deve ficar na configuração da integração e ser criptografada.

Os campos `login` e `senha` atualmente associados ao veículo não devem ser reutilizados como credencial mestra da API. Se representarem o acesso individual do cliente à Tracksolid, deve-se avaliar:

- criptografar a senha;
- deixar de armazenar senha recuperável;
- guardar apenas o identificador da subconta;
- administrar redefinição de senha por fluxo específico.

## Implementação recomendada

### Fase 1 — Conexão e diagnóstico

- adicionar Tracksolid na tela de Integrações;
- configurar nó/URL, conta, `appKey`, `appSecret`, senha e timeout;
- criptografar os segredos;
- implementar assinatura v1.0;
- armazenar e renovar o token;
- criar ação “Testar conexão”;
- consultar um IMEI conhecido;
- registrar falhas sem expor credenciais.

### Fase 2 — Inventário e situação atual

- listar equipamentos da conta;
- cruzar pelo IMEI;
- gerar relatório de divergências;
- consultar detalhes do equipamento;
- mostrar online/offline;
- mostrar última comunicação e posição;
- mostrar ACC, alimentação, bateria, sinal e expiração;
- gerar link de compartilhamento da posição.

Essa é a primeira entrega recomendada.

### Fase 3 — Operação e relatórios

- mapa com última posição;
- consulta de trajeto sob demanda;
- viagens e quilometragem;
- paradas e marcha lenta;
- alarmes consultados pela API;
- callback de alarmes;
- central de eventos;
- notificações internas e, quando apropriado, WhatsApp.

### Fase 4 — Cercas

- criar e listar cercas da plataforma;
- associar IMEIs;
- registrar entrada, saída e permanência;
- permitir cercas por cliente ou veículo;
- manter auditoria de alterações.

### Fase 5 — Contas e acessos

- associar cliente a subconta Tracksolid;
- criar ou editar subconta;
- vincular/desvincular usuário do aplicativo;
- mover equipamento em instalação, troca ou cancelamento;
- alterar expiração somente depois de definir a regra comercial correta.

### Fase 6 — Comandos, câmera e RFID

- descobrir comandos suportados por IMEI;
- liberar apenas comandos homologados;
- adicionar permissões e auditoria;
- implementar captura e vídeo somente para modelos compatíveis;
- implementar RFID somente se houver equipamentos utilizados pela operação.

## Cuidados operacionais

- Não solicitar token em toda requisição.
- Sincronizar o relógio do servidor.
- Usar UTC na comunicação com a API.
- Armazenar segredos criptografados.
- Nunca enviar `appSecret` ou senha ao navegador.
- Aplicar timeout e retentativas controladas.
- Não repetir automaticamente comandos perigosos.
- Tratar códigos Tracksolid como falha mesmo quando o HTTP retornar 200.
- Registrar método, IMEI, resultado, duração e código da API.
- Remover segredos e tokens dos logs.
- Aplicar paginação e limites por lote.
- Validar IMEI como pertencente à conta antes de expor dados.
- Restringir localização, alarmes e trajetos por permissão.
- Considerar os dados de localização como dados pessoais.
- Definir retenção e acesso de acordo com a LGPD.

## Inconsistências e pontos frágeis do manual

- O documento é de junho de 2024 e deve ser confirmado como vigente.
- Alguns exemplos JSON contêm vírgulas ausentes ou aspas incorretas.
- Há troca textual entre latitude e longitude em algumas descrições.
- O método de edição de cerca aparece com o mesmo nome do método de criação.
- `jimi.device.meida.cmd.send` contém “meida” no nome documentado; deve ser testado exatamente como informado.
- Algumas respostas usam `result`, outras usam `data`, e algumas usam ambos.
- Tipos variam entre número e string.
- O manual menciona controle de frequência, mas não informa os limites numéricos gerais.
- A segurança do callback de alarmes não está documentada.
- Alguns nomes e descrições estão traduzidos de forma imprecisa.
- Recursos e campos dependem do modelo e firmware.

O cliente HTTP deve ser tolerante a essas diferenças, mas não deve esconder respostas inesperadas.

## Informações a solicitar à Tracksolid

Antes da implementação, confirmar:

1. Qual nó hospeda a conta da Conectta.
2. Se o Open API está habilitado.
3. `appKey` e `appSecret`.
4. Conta técnica que será usada.
5. Se existe ambiente de homologação.
6. Quais métodos estão liberados no contrato.
7. Limites de requisição por minuto e por dia.
8. Política de bloqueio temporário por excesso de chamadas.
9. Forma de autenticar callbacks.
10. IPs de origem dos callbacks.
11. Política de repetição de callbacks.
12. Modelos e firmwares utilizados pela Conectta.
13. Recursos suportados por cada modelo.
14. Se coordenadas brasileiras são retornadas sem conversão.
15. Regra correta para suspensão e reativação de cliente.
16. Se a v2.7.9 continua vigente ou existe documentação mais recente.

## Prioridade recomendada

| Prioridade | Recurso | Motivo |
|---|---|---|
| Alta | Conferência de IMEI e inventário | Baixo risco e corrige divergências operacionais |
| Alta | Situação atual e última comunicação | Ajuda suporte e diagnóstico |
| Alta | Última posição e compartilhamento | Valor direto para operação |
| Alta | Alarmes principais | Permite atuação proativa |
| Média | Trajetos, viagens e quilometragem | Relatórios e atendimento |
| Média | Paradas e marcha lenta | Indicadores operacionais |
| Média | Cercas da plataforma | Automação de eventos |
| Média/baixa | Subcontas e acessos | Exige conciliação com contas existentes |
| Baixa e controlada | Comandos remotos | Alto risco operacional |
| Condicional | Câmera, vídeo e RFID | Depende dos modelos instalados |

## Recomendação final

Começar com:

1. configuração e teste da conexão;
2. token armazenado e renovado corretamente;
3. consulta de detalhes por IMEI;
4. relatório Tracksolid x Conectta;
5. posição e saúde atual do equipamento;
6. consulta de alarmes e trajetos sob demanda.

Somente depois de validar a API com IMEIs reais devem ser implementadas alterações remotas, contas, cercas e comandos.

## Registro da tentativa de integração local — 29 e 30/07/2026

### Escopo e segurança

- Todo o trabalho no Conectta foi realizado no ambiente local.
- Nenhum comando, deploy ou alteração foi executado na produção do Conectta.
- As chamadas à Tracksolid utilizaram a conta real, mas somente o método de autenticação `jimi.oauth.token.get`.
- Nenhum equipamento, cliente, vínculo, cerca ou comando remoto foi alterado.
- Credenciais, assinaturas e respostas com tokens não foram registradas neste documento.

### Banco local utilizado

Foi restaurado localmente o backup de produção já baixado:

```text
storage/app/private/backups/production-db/conectta-conectta-20260725-122918.sql.gz
```

Antes da restauração foi criado um backup recuperável do banco local:

```text
storage/app/private/backups/development-db/conectta-local-before-tracksolid-20260729-091213.sql.gz
```

Validação após a restauração:

- 43 migrations;
- 2.228 clientes;
- 9.497 veículos;
- 6.668 rastreadores;
- 7.710 chips.

Qualidade inicial dos IMEIs:

- 6.668 registros de rastreadores;
- 6.644 IMEIs únicos;
- nenhum rastreador sem IMEI;
- 24 IMEIs duplicados.

### Implementação local preparada

Foram adicionados:

- configuração `services.tracksolid`;
- cliente HTTP com assinatura MD5 v1.0;
- obtenção de token;
- consulta de detalhes por IMEI;
- listagem de equipamentos;
- serviço de conciliação Tracksolid x Conectta;
- comando local `tracksolid:diagnostico`;
- testes automatizados da assinatura e leitura do inventário.

Arquivos principais:

```text
app/Services/Tracksolid/TracksolidService.php
app/Services/Tracksolid/TracksolidException.php
app/Services/Tracksolid/TracksolidDiagnosticService.php
tests/Unit/Services/Tracksolid/TracksolidServiceTest.php
routes/console.php
config/services.php
```

Os testes locais concluíram com sucesso: 2 testes e 3 asserções.

O comando preparado é:

```bash
php artisan tracksolid:diagnostico \
    --base-url=https://NO-CORRETO/route/rest \
    --imei=IMEI_CONHECIDO \
    --output=tracksolid/diagnostico-inicial.json
```

Ele existe apenas fora de produção, usa métodos de leitura e grava o relatório no storage privado.

### Credenciais verificadas sem exposição

As quatro variáveis estavam preenchidas no `.env`:

```text
TRACKSOLID_ACCOUNT
TRACKSOLID_APP_KEY
TRACKSOLID_APP_SECRET
TRACKSOLID_PASSWORD_MD5
```

Na última conferência:

- conta com 5 caracteres;
- AppKey com 32 caracteres hexadecimais;
- AppSecret com 32 caracteres hexadecimais;
- senha MD5 com 32 caracteres hexadecimais.

O tamanho de 32 caracteres não comprova erro: versões diferentes da documentação oficial mostram exemplos de AppKey com 32 e 48 caracteres.

### Chamadas realizadas

O método testado foi exclusivamente:

```text
jimi.oauth.token.get
```

Resultados:

| Nó | Endpoint | Resultado |
|---|---|---|
| TSP US | `https://us-open.tracksolidpro.com/route/rest` | HTTP 500, sem token |
| TSP HK | `https://hk-open.tracksolidpro.com/route/rest` | Código 1001, AppKey ausente ou inválida |
| TSP EU | `https://eu-open.tracksolidpro.com/route/rest` | Código 1001, AppKey ausente ou inválida |
| TS por HTTPS | `https://open.10000track.com/route/rest` | Código 1001, AppKey ausente ou inválida |
| TS legado | `http://open.10000track.com/route/rest` | Código 1001, AppKey ausente ou inválida |

Foi feita somente uma chamada com credenciais ao endpoint TS sem HTTPS, após autorização explícita. Como houve transmissão por HTTP, é recomendável solicitar rotação das credenciais se o fornecedor confirmar que essa chave pertence ao nó TS.

Também foram comparados os formatos GET e POST. O problema persistiu e ocorre na validação da AppKey, antes da validação da senha e da assinatura.

### Conclusão do diagnóstico

O código e o formato da requisição estão de acordo com o manual:

- POST `application/x-www-form-urlencoded`;
- horário UTC;
- `v=1.0`;
- `sign_method=md5`;
- parâmetros ordenados alfabeticamente;
- assinatura MD5 em maiúsculas;
- senha MD5 em minúsculas.

O erro `Missing AppKey parameter or invalid AppKey` indica que o gateway consultado não reconhece a AppKey. Como todos os nós documentados foram testados, as hipóteses restantes são:

1. AppKey ainda não ativada ou provisionada;
2. AppKey associada a outro usuário;
3. conta hospedada em nó privado ou white-label;
4. endpoint personalizado não informado;
5. cadastro interno da Open API ainda pendente.

O erro não aparenta ser whitelist de IP, assinatura ou senha:

- IP bloqueado possui código específico 1005;
- frequência excessiva possui código 1006;
- usuário ou senha incorretos normalmente retornam código 1002;
- assinatura inválida possui mensagem específica.

### Informações necessárias para retomar

Solicitar ao fornecedor:

1. endpoint exato associado à AppKey;
2. nó da conta: TS, HK/SG, EU, US ou privado;
3. confirmação de que a AppKey está ativa;
4. confirmação de que a AppKey pertence ao UserID informado;
5. existência de endpoint white-label;
6. necessidade de whitelist de IP;
7. possibilidade de usar HTTPS caso a conta esteja no TS legado;
8. nova credencial, caso a anterior tenha sido transmitida por HTTP.

Mensagem sugerida:

```text
Hello,

We received Open API credentials for our UserID.
The method jimi.oauth.token.get returns code 1001,
"Missing AppKey parameter or invalid AppKey", on all documented nodes:

- TS: http://open.10000track.com/route/rest
- TSP HK: https://hk-open.tracksolidpro.com/route/rest
- TSP EU: https://eu-open.tracksolidpro.com/route/rest
- TSP US: https://us-open.tracksolidpro.com/route/rest

Please confirm:
1. The exact Open API endpoint/node assigned to this AppKey.
2. Whether the AppKey is active and provisioned.
3. Whether it belongs to our UserID.
4. Whether an IP whitelist is required.
5. Whether this account uses a private or white-label API endpoint.
```

### Próximo passo quando chegarem as informações

1. atualizar apenas `TRACKSOLID_BASE_URL` e, se necessário, as credenciais no `.env`;
2. limpar o cache local com `php artisan config:clear`;
3. testar a obtenção do token uma única vez;
4. consultar um IMEI ativo conhecido;
5. listar o inventário da conta;
6. gerar `storage/app/private/tracksolid/diagnostico-inicial.json`;
7. revisar as divergências antes de qualquer sincronização ou correção.
